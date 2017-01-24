<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright of T i m e T r e x Software Inc.
 * Fairness is a fork of "T i m e T r e x Workforce Management" Software.
 *
 * Fairness is free software; you can redistribute it and/or modify it under the
 * terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation, either version 3 of the License, or (at you option )
 * any later version.
 *
 * Fairness is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along
 * with this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 ********************************************************************************/


/**
 * @package Modules\Punch
 */
class PunchControlFactory extends Factory
{
    public $old_date_stamps = array();
        protected $table = 'punch_control'; //PK Sequence name
protected $pk_sequence_name = 'punch_control_id_seq';
    protected $tmp_data = null;
    protected $shift_data = null;

    protected $user_obj = null;
    protected $pay_period_obj = null;
    protected $pay_period_schedule_obj = null;
    protected $job_obj = null;
    protected $job_item_obj = null;
    protected $meal_policy_obj = null;
    protected $punch_obj = null;

    protected $in_punch_obj = null;
    protected $out_punch_obj = null;

    protected $plf = null;
    protected $is_total_time_calculated = false;

    public static function dragNdropPunch($company_id, $src_punch_id, $dst_punch_id, $dst_status_id = null, $position = 0, $action = 0, $dst_date = null)
    {
        /*
            FIXME: This needs to handle batches to be able to handle all the differnet corner cases.
            Operations to handle:
                - Moving punch from Out to In, or In to Out in same punch pair, this is ALWAYS a move, and not a copy.
                - Move punch from one pair to another in the same day, this can be a copy or move.
                    - Check moving AND copying Out punch from one punch pair to In in another on the same day. ie: In 8:00AM, Out 1:00PM, Out 5:00PM. Move the 1PM punch to pair with 5PM.
                - Move punch from one day to another, inserting inbetween other punches if necessary.
                - Move punch from one day to another without any other punches.


                - Inserting BEFORE on a dst_punch_id that is an In punch doesn't do any splitting.
                - Inserting AFTER on a dst_punch_id that is on a Out punch doesn't do any splitting.
                - Overwriting should just take the punch time and overwrite the existing punch time.
                - The first thing this function does it check if there are two punches assigned to the punch control of the destination punch, if there is, it splits the punches
                    across two punch_controls, it then attaches the src_punch_id to the same punch_control_id as the dst_punch_id.
                - If no dst_punch_id is specified, assume copying to a blank cell, just copy the punch to that date along with the punch_control?
                - Copying punches that span midnight work, however moving punches does not always
                    since we don't move punches in batches, we do it one at a time, and when the first punch punch
                    gets moved, it can cause other punches to follow it automatically.
        */
        $dst_date = TTDate::getMiddleDayEpoch($dst_date);
        Debug::text('Src Punch ID: ' . $src_punch_id . ' Dst Punch ID: ' . $dst_punch_id . ' Dst Status ID: ' . $dst_status_id . ' Position: ' . $position . ' Action: ' . $action . ' Dst Date: ' . $dst_date, __FILE__, __LINE__, __METHOD__, 10);

        $retval = false;

        //Get source and destination punch objects.
        $plf = TTnew('PunchListFactory');
        $plf->StartTransaction();

        $plf->getByCompanyIDAndId($company_id, $src_punch_id);
        if ($plf->getRecordCount() == 1) {
            $src_punch_obj = $plf->getCurrent();
            $src_punch_date = TTDate::getMiddleDayEpoch($src_punch_obj->getPunchControlObject()->getDateStamp());
            Debug::text('Found SRC punch ID: ' . $src_punch_id . ' Source Punch Date: ' . $src_punch_date, __FILE__, __LINE__, __METHOD__, 10);

            //Get the PunchControlObject as early as possible, before the punch is deleted, as it will be cleared even if Save(FALSE) is called below.
            $src_punch_control_obj = clone $src_punch_obj->getPunchControlObject();

            if (TTDate::getMiddleDayEpoch($src_punch_date) != TTDate::getMiddleDayEpoch($src_punch_obj->getTimeStamp())) {
                Debug::text('Punch spans midnight... Source Punch Date: ' . TTDate::getDATE('DATE+TIME', $src_punch_date) . ' Source Punch TimeStamp: ' . TTDate::getDATE('DATE+TIME', $src_punch_obj->getTimeStamp()), __FILE__, __LINE__, __METHOD__, 10);
                $dst_date_modifier = 86400; //Bump day by 24hrs.
            } else {
                $dst_date_modifier = 0;
            }

            //If we are moving the punch, we need to delete the source punch first so it doesn't conflict with the new punch.
            //Especially if we are just moving a punch to fill a gap in the same day.
            //If the punch being moved is in the same day, or within the same punch pair, we don't want to delete the source punch, instead we just modify
            //the necessary bits later on. So we need to short circuit the move functionality when copying/moving punches within the same day.
            if (
                ($action == 1 and $src_punch_id != $dst_punch_id and $src_punch_date != $dst_date)
                or
                ($action == 1 and $src_punch_id != $dst_punch_id and $src_punch_date == $dst_date)
                //OR
                //( $action == 0 AND $src_punch_id != $dst_punch_id AND $src_punch_date == $dst_date ) //Since we have dst_status_id, we don't need to force-move punches even though the user selected copy.
            ) { //Move
                Debug::text('Deleting original punch ID: ' . $src_punch_id . ' User Date: ' . TTDate::getDate('DATE', $src_punch_control_obj->getDateStamp()) . ' ID: ' . $src_punch_control_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);

                $src_punch_obj->setUser($src_punch_control_obj->getUser());
                $src_punch_obj->setDeleted(true);

                $punch_image_data = $src_punch_obj->getImage();

                //These aren't doing anything because they aren't acting on the PunchControl object?
                $src_punch_obj->setEnableCalcTotalTime(true);
                $src_punch_obj->setEnableCalcSystemTotalTime(true);
                $src_punch_obj->setEnableCalcWeeklySystemTotalTime(true);
                $src_punch_obj->setEnableCalcUserDateTotal(true);
                $src_punch_obj->setEnableCalcException(true);
                $src_punch_obj->Save(false); //Keep object around for later.
            } else {
                Debug::text('NOT Deleting original punch, either in copy mode or condition is not met...', __FILE__, __LINE__, __METHOD__, 10);
            }

            if ($src_punch_id == $dst_punch_id or $dst_punch_id == '') {
                //Assume we are just moving a punch within the same punch pair, unless a new date is specfied.
                //However if we're simply splitting an existing punch pair, like dragging the Out punch from an In/Out pair into its own separate pair.
                if ($src_punch_date != $dst_date or $src_punch_date == $dst_date and $dst_punch_id == '') {
                    Debug::text('aCopying punch to new day...', __FILE__, __LINE__, __METHOD__, 10);

                    //Moving punch to a new date.
                    //Copy source punch to proper location by destination punch.
                    $src_punch_obj->setId(false);
                    $src_punch_obj->setPunchControlId((int)$src_punch_control_obj->getNextInsertId());
                    $src_punch_obj->setDeleted(false); //Just in case it was marked deleted by the MOVE action.

                    $new_time_stamp = TTDate::getTimeLockedDate($src_punch_obj->getTimeStamp(), ($dst_date + $dst_date_modifier));
                    Debug::text('SRC TimeStamp: ' . TTDate::getDate('DATE+TIME', $src_punch_obj->getTimeStamp()) . ' DST TimeStamp: ' . TTDate::getDate('DATE+TIME', $new_time_stamp), __FILE__, __LINE__, __METHOD__, 10);

                    $src_punch_obj->setTimeStamp($new_time_stamp, false);
                    $src_punch_obj->setActualTimeStamp($new_time_stamp);
                    $src_punch_obj->setOriginalTimeStamp($new_time_stamp);
                    if ($dst_status_id != '') {
                        $src_punch_obj->setStatus($dst_status_id); //Change the status to fit in the proper place.
                    }

                    //When drag&drop copying punches, clear some fields that shouldn't be copied.
                    if ($action == 0) { //Copy
                        $src_punch_obj->setStation(null);
                        $src_punch_obj->setHasImage(false);
                        $src_punch_obj->setLongitude(0);
                        $src_punch_obj->setLatitude(0);
                    } elseif (isset($punch_image_data) and $punch_image_data != '') {
                        $src_punch_obj->setImage($punch_image_data);
                    }

                    if ($src_punch_obj->isValid() == true) {
                        $insert_id = $src_punch_obj->Save(false);

                        $src_punch_control_obj->shift_data = null; //Need to clear the shift data so its obtained from the DB again, otherwise shifts will appear on strange days.
                        $src_punch_control_obj->user_date_obj = null; //Need to clear user_date_obj from cache so a new one is obtained.
                        $src_punch_control_obj->setId($src_punch_obj->getPunchControlID());
                        $src_punch_control_obj->setPunchObject($src_punch_obj);

                        if ($src_punch_control_obj->isValid() == true) {
                            Debug::Text(' Punch Control is valid, saving...: ', __FILE__, __LINE__, __METHOD__, 10);

                            //We need to calculate new total time for the day and exceptions because we are never guaranteed that the gaps will be filled immediately after
                            //in the case of a drag & drop or something.
                            $src_punch_control_obj->setEnableStrictJobValidation(true);
                            $src_punch_control_obj->setEnableCalcUserDateID(true);
                            $src_punch_control_obj->setEnableCalcTotalTime(true);
                            $src_punch_control_obj->setEnableCalcSystemTotalTime(true);
                            $src_punch_control_obj->setEnableCalcWeeklySystemTotalTime(true);
                            $src_punch_control_obj->setEnableCalcUserDateTotal(true);
                            $src_punch_control_obj->setEnableCalcException(true);
                            if ($src_punch_control_obj->isValid() == true) {
                                if ($src_punch_control_obj->Save(true, true) == true) {
                                    //Return newly inserted punch_id, so Flex can base other actions on it.
                                    $retval = $insert_id;
                                }
                            }
                        }
                    }
                } else {
                    Debug::text('Copying punch within the same pair/day...', __FILE__, __LINE__, __METHOD__, 10);
                    //Moving punch within the same punch pair.
                    $src_punch_obj->setStatus($src_punch_obj->getNextStatus()); //Change just the punch status.
                    //$src_punch_obj->setDeleted(FALSE); //Just in case it was marked deleted by the MOVE action.
                    if ($src_punch_obj->isValid() == true) {
                        //Return punch_id, so Flex can base other actions on it.
                        $retval = $src_punch_obj->Save(false);

                        $src_punch_control_obj->shift_data = null; //Need to clear the shift data so its obtained from the DB again, otherwise shifts will appear on strange days.
                        $src_punch_control_obj->user_date_obj = null; //Need to clear user_date_obj from cache so a new one is obtained.
                        $src_punch_control_obj->setId($src_punch_obj->getPunchControlID());
                        $src_punch_control_obj->setPunchObject($src_punch_obj);

                        if ($src_punch_control_obj->isValid() == true) {
                            Debug::Text(' Punch Control is valid, saving...: ', __FILE__, __LINE__, __METHOD__, 10);
                            //Need to make sure we calculate the exceptions if they are moving punches from in/out, as there is likely to be a missing punch exception either way.
                            $src_punch_control_obj->setEnableStrictJobValidation(false);
                            $src_punch_control_obj->setEnableCalcUserDateID(false);
                            $src_punch_control_obj->setEnableCalcTotalTime(false);
                            $src_punch_control_obj->setEnableCalcSystemTotalTime(true);
                            $src_punch_control_obj->setEnableCalcWeeklySystemTotalTime(false);
                            $src_punch_control_obj->setEnableCalcUserDateTotal(false);
                            $src_punch_control_obj->setEnableCalcException(true);
                            if ($src_punch_control_obj->isValid() == true) {
                                $src_punch_control_obj->Save(true, true);
                            }
                        }
                    }
                }
            } else {
                Debug::text('bCopying punch to new day...', __FILE__, __LINE__, __METHOD__, 10);
                $plf->getByCompanyIDAndId($company_id, $dst_punch_id);
                if ($plf->getRecordCount() == 1) {
                    Debug::text('Found DST punch ID: ' . $dst_punch_id, __FILE__, __LINE__, __METHOD__, 10);
                    $dst_punch_obj = $plf->getCurrent();
                    $dst_punch_control_obj = $dst_punch_obj->getPunchControlObject();
                    Debug::text('aSRC TimeStamp: ' . TTDate::getDate('DATE+TIME', $src_punch_obj->getTimeStamp()) . ' DST TimeStamp: ' . TTDate::getDate('DATE+TIME', $dst_punch_obj->getTimeStamp()), __FILE__, __LINE__, __METHOD__, 10);

                    $is_punch_control_split = false;
                    if ($position == 0) { //Overwrite
                        Debug::text('Overwriting...', __FILE__, __LINE__, __METHOD__, 10);
                        //All we need to do is update the time of the destination punch.
                        $punch_obj = $dst_punch_obj;
                    } else { //Before or After
                        //Determine if the destination punch needs to split from another punch
                        //Check to make sure that when splitting an existing punch pair, the new punch is after the IN punch and before the OUT punch.
                        //Otherwise don't split the punch pair and just put it in its own pair.
                        if (($position == -1 and $dst_punch_obj->getStatus() == 20 and ($dst_status_id == false or $src_punch_obj->getTimeStamp() < $dst_punch_obj->getTimeStamp()))
                            or ($position == 1 and $dst_punch_obj->getStatus() == 10 and ($dst_status_id == false or $src_punch_obj->getTimeStamp() > $dst_punch_obj->getTimeStamp()))
                        ) { //Before on Out punch, After on In Punch,
                            Debug::text('Need to split destination punch out to its own Punch Control row...', __FILE__, __LINE__, __METHOD__, 10);
                            $is_punch_control_split = PunchControlFactory::splitPunchControl($dst_punch_obj->getPunchControlID());

                            //Once a split occurs, we need to re-get the destination punch as the punch_control_id may have changed.
                            //We could probably optimize this to only occur when the destination punch is an In punch, as the
                            //Out punch is always the one to be moved to a new punch_control_id
                            if ($src_punch_obj->getStatus() != $dst_punch_obj->getStatus()) {
                                $plf->getByCompanyIDAndId($company_id, $dst_punch_id);
                                if ($plf->getRecordCount() == 1) {
                                    $dst_punch_obj = $plf->getCurrent();
                                    Debug::text('Found DST punch ID: ' . $dst_punch_id . ' Punch Control ID: ' . $dst_punch_obj->getPunchControlID(), __FILE__, __LINE__, __METHOD__, 10);
                                }
                            }

                            $punch_control_id = $dst_punch_obj->getPunchControlID();
                        } else {
                            Debug::text('No Need to split destination punch, simply add a new punch/punch_control all on its own.', __FILE__, __LINE__, __METHOD__, 10);
                            //Check to see if the src and dst punches are the same status though.
                            $punch_control_id = (int)$dst_punch_control_obj->getNextInsertId();
                        }

                        //Take the source punch and base our new punch on that.
                        $punch_obj = $src_punch_obj;

                        //Copy source punch to proper location by destination punch.
                        $punch_obj->setId(false);
                        $punch_obj->setDeleted(false); //Just in case it was marked deleted by the MOVE action.
                        $punch_obj->setPunchControlId($punch_control_id);
                    }

                    //$new_time_stamp = TTDate::getTimeLockedDate($src_punch_obj->getTimeStamp(), $dst_punch_obj->getTimeStamp()+$dst_date_modifier );
                    $new_time_stamp = TTDate::getTimeLockedDate($src_punch_obj->getTimeStamp(), ($dst_punch_obj->getPunchControlObject()->getDateStamp() + $dst_date_modifier));
                    Debug::text('SRC TimeStamp: ' . TTDate::getDate('DATE+TIME', $src_punch_obj->getTimeStamp()) . ' DST TimeStamp: ' . TTDate::getDate('DATE+TIME', $dst_punch_obj->getTimeStamp()) . ' New TimeStamp: ' . TTDate::getDate('DATE+TIME', $new_time_stamp), __FILE__, __LINE__, __METHOD__, 10);

                    $punch_obj->setTimeStamp($new_time_stamp, false);
                    $punch_obj->setActualTimeStamp($new_time_stamp);
                    $punch_obj->setOriginalTimeStamp($new_time_stamp);
                    $punch_obj->setTransfer(false); //Always set transfer to FALSE so we don't try to create In/Out punch automatically later.

                    //When drag&drop copying punches, clear some fields that shouldn't be copied.
                    if ($action == 0) { //Copy
                        $src_punch_obj->setStation(null);
                        $src_punch_obj->setHasImage(false);
                        $src_punch_obj->setLongitude(0);
                        $src_punch_obj->setLatitude(0);
                    } elseif (isset($punch_image_data) and $punch_image_data != '') {
                        $src_punch_obj->setImage($punch_image_data);
                    }

                    //Need to take into account copying a Out punch and inserting it BEFORE another Out punch in a punch pair.
                    //In this case a split needs to occur, and the status needs to stay the same.
                    //Status also needs to stay the same when overwriting an existing punch.
                    Debug::text('Punch Status: ' . $punch_obj->getStatus() . ' DST Punch Status: ' . $dst_punch_obj->getStatus() . ' Split Punch Control: ' . (int)$is_punch_control_split, __FILE__, __LINE__, __METHOD__, 10);
                    if (($position != 0 and $is_punch_control_split == false and $punch_obj->getStatus() == $dst_punch_obj->getStatus() and $punch_obj->getPunchControlID() == $dst_punch_obj->getPunchControlID())) {
                        Debug::text('Changing punch status to opposite: ' . $dst_punch_obj->getNextStatus(), __FILE__, __LINE__, __METHOD__, 10);
                        $punch_obj->setStatus($dst_punch_obj->getNextStatus()); //Change the status to fit in the proper place.
                    }
                    if ($punch_obj->isValid() == true) {
                        $insert_id = $punch_obj->Save(false);

                        $dst_punch_control_obj->shift_data = null; //Need to clear the shift data so its obtained from the DB again, otherwise shifts will appear on strange days, or cause strange conflicts.
                        $dst_punch_control_obj->setID($punch_obj->getPunchControlID());
                        $dst_punch_control_obj->setPunchObject($punch_obj);

                        if ($dst_punch_control_obj->isValid() == true) {
                            Debug::Text(' Punch Control is valid, saving...: ', __FILE__, __LINE__, __METHOD__, 10);

                            //We need to calculate new total time for the day and exceptions because we are never guaranteed that the gaps will be filled immediately after
                            //in the case of a drag & drop or something.
                            $dst_punch_control_obj->setEnableStrictJobValidation(true);
                            $dst_punch_control_obj->setEnableCalcUserDateID(true);
                            $dst_punch_control_obj->setEnableCalcTotalTime(true);
                            $dst_punch_control_obj->setEnableCalcSystemTotalTime(true);
                            $dst_punch_control_obj->setEnableCalcWeeklySystemTotalTime(true);
                            $dst_punch_control_obj->setEnableCalcUserDateTotal(true);
                            $dst_punch_control_obj->setEnableCalcException(true);
                            if ($dst_punch_control_obj->isValid() == true) {
                                if ($dst_punch_control_obj->Save(true, true) == true) { //Force isNew() lookup.
                                    //Return newly inserted punch_id, so Flex can base other actions on it.
                                    $retval = $insert_id;
                                    //$retval = TRUE;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($retval == false) {
            $plf->FailTransaction();
        }
        //$plf->FailTransaction();
        $plf->CommitTransaction();

        Debug::text('Returning: ' . (int)$retval, __FILE__, __LINE__, __METHOD__, 10);
        return $retval;
    }

    public static function splitPunchControl($punch_control_id)
    {
        $retval = false;
        if ($punch_control_id != '') {
            $plf = TTnew('PunchListFactory');
            $plf->StartTransaction();
            $plf->getByPunchControlID($punch_control_id, null, array('time_stamp' => 'desc')); //Move out punch to new punch_control_id.
            if ($plf->getRecordCount() == 2) {
                $pclf = TTnew('PunchControlListFactory');
                $new_punch_control_id = (int)$pclf->getNextInsertId();
                Debug::text(' Punch Control ID: ' . $punch_control_id . ' only has two punches assigned, splitting... New Punch Control ID: ' . $new_punch_control_id, __FILE__, __LINE__, __METHOD__, 10);
                $i = 0;
                foreach ($plf as $p_obj) {
                    if ($i == 0) {
                        //First punch (out)
                        //Get the PunchControl Object before we change to the new punch_control_id
                        $pc_obj = $p_obj->getPunchControlObject();

                        $p_obj->setPunchControlId($new_punch_control_id);
                        if ($p_obj->isValid() == true) {
                            $p_obj->Save(false);

                            $pc_obj->setId($new_punch_control_id);
                            $pc_obj->setPunchObject($p_obj);

                            if ($pc_obj->isValid() == true) {
                                Debug::Text(' Punch Control is valid, saving Punch ID: ' . $p_obj->getID() . ' To new Punch Control ID: ' . $new_punch_control_id, __FILE__, __LINE__, __METHOD__, 10);

                                //We need to calculate new total time for the day and exceptions because we are never guaranteed that the gaps will be filled immediately after
                                //in the case of a drag & drop or something.
                                $pc_obj->setEnableStrictJobValidation(true);
                                $pc_obj->setEnableCalcUserDateID(true);
                                $pc_obj->setEnableCalcTotalTime(true);
                                $pc_obj->setEnableCalcSystemTotalTime(false); //Do this for In punch only.
                                $pc_obj->setEnableCalcWeeklySystemTotalTime(false); //Do this for In punch only.
                                $pc_obj->setEnableCalcUserDateTotal(true);
                                $pc_obj->setEnableCalcException(true);
                                $retval = $pc_obj->Save(true, true); //Force isNew() lookup.
                            }
                        }
                    } else {
                        //Second punch (in), need to recalculate user_date_total for this one to clear the total time, as well as recalculate the entire week
                        //for system totals so those are updated as well.
                        Debug::text(' ReCalculating total time for In punch...', __FILE__, __LINE__, __METHOD__, 10);
                        $pc_obj = $p_obj->getPunchControlObject();
                        $pc_obj->setEnableStrictJobValidation(true);
                        $pc_obj->setEnableCalcUserDateID(true);
                        $pc_obj->setEnableCalcTotalTime(true);
                        $pc_obj->setEnableCalcSystemTotalTime(true);
                        $pc_obj->setEnableCalcWeeklySystemTotalTime(true);
                        $pc_obj->setEnableCalcUserDateTotal(true);
                        $pc_obj->setEnableCalcException(true);
                        $retval = $pc_obj->Save();
                    }

                    $i++;
                }
            } else {
                Debug::text(' Punch Control ID: ' . $punch_control_id . ' only has one punch assigned, doing nothing...', __FILE__, __LINE__, __METHOD__, 10);
            }

            //$plf->FailTransaction();
            $plf->CommitTransaction();
        }

        return $retval;
    }

    public function _getVariableToFunctionMap($data)
    {
        $variable_function_map = array(
            'id' => 'ID',
            //'user_date_id' => 'UserDateID',
            'user_id' => 'User',
            'date_stamp' => 'DateStamp',
            'pay_period_id' => 'PayPeriod',
            'branch_id' => 'Branch',
            'department_id' => 'Department',
            'job_id' => 'Job',
            'job_item_id' => 'JobItem',
            'quantity' => 'Quantity',
            'bad_quantity' => 'BadQuantity',
            'total_time' => 'TotalTime',
            'actual_total_time' => 'ActualTotalTime',
            //'meal_policy_id' => 'MealPolicyID',
            'note' => 'Note',
            'other_id1' => 'OtherID1',
            'other_id2' => 'OtherID2',
            'other_id3' => 'OtherID3',
            'other_id4' => 'OtherID4',
            'other_id5' => 'OtherID5',
            'deleted' => 'Deleted',
        );
        return $variable_function_map;
    }

    public function getJobItemObject()
    {
        return $this->getGenericObject('JobItemListFactory', $this->getJobItem(), 'job_item_obj');
    }

    public function getJobItem()
    {
        if (isset($this->data['job_item_id'])) {
            return (int)$this->data['job_item_id'];
        }

        return false;
    }

    public function setPunchObject($obj)
    {
        if (is_object($obj)) {
            $this->punch_obj = $obj;

            //Set the user/datestamp based on the punch.
            if ($obj->getUser() != false and $obj->getUser() != $this->getUser()) {
                $this->setUser($obj->getUser());
            }
            if ($obj->getTimeStamp() != false and TTDate::getMiddleDayEpoch($obj->getTimeStamp()) != TTDate::getMiddleDayEpoch($this->getDateStamp())) {
                $this->setDateStamp($obj->getTimeStamp());
            }

            return true;
        }

        return false;
    }

    public function getUser()
    {
        if (isset($this->data['user_id'])) {
            return (int)$this->data['user_id'];
        }

        return false;
    }

    public function setUser($id)
    {
        $id = (int)$id;

        $ulf = TTnew('UserListFactory');

        //Need to be able to support user_id=0 for open shifts. But this can cause problems with importing punches with user_id=0.
        if ($this->Validator->isResultSetWithRows('user',
            $ulf->getByID($id),
            TTi18n::gettext('Invalid User')
        )
        ) {
            $this->data['user_id'] = $id;

            return true;
        }

        return false;
    }

    public function getDateStamp($raw = false)
    {
        if (isset($this->data['date_stamp'])) {
            if ($raw === true) {
                return $this->data['date_stamp'];
            } else {
                return TTDate::getMiddleDayEpoch(TTDate::strtotime($this->data['date_stamp']));
            }
        }

        return false;
    }

    public function setDateStamp($epoch)
    {
        $epoch = (int)$epoch;
        if ($epoch > 0) {
            $epoch = TTDate::getMiddleDayEpoch($epoch);
        }

        if ($this->Validator->isDate('date_stamp',
            $epoch,
            TTi18n::gettext('Incorrect date') . '(a)')
        ) {
            if ($epoch > 0) {
                if ($this->getDateStamp() !== $epoch and $this->getOldDateStamp() != $this->getDateStamp() and (int)$this->getDateStamp() != 0) {
                    //Only set OldDateStamp if its not empty, that way it won't override an already set OldDateStamp that is valid.
                    Debug::Text(' Setting Old DateStamp... Current Old DateStamp: ' . (int)$this->getOldDateStamp() . ' Current DateStamp: ' . (int)$this->getDateStamp(), __FILE__, __LINE__, __METHOD__, 10);
                    $this->setOldDateStamp($this->getDateStamp());
                }

                $this->data['date_stamp'] = $epoch;

                $this->setPayPeriod(); //Force pay period to be set as soon as the date is.
                return true;
            } else {
                $this->Validator->isTRUE('date_stamp',
                    false,
                    TTi18n::gettext('Incorrect date') . '(b)');
            }
        }

        return false;
    }

    public function getOldDateStamp()
    {
        if (isset($this->tmp_data['old_date_stamp'])) {
            return $this->tmp_data['old_date_stamp'];
        }

        return false;
    }

    public function setOldDateStamp($date_stamp)
    {
        Debug::Text(' Setting Old DateStamp: ' . TTDate::getDate('DATE', $date_stamp), __FILE__, __LINE__, __METHOD__, 10);
        $this->tmp_data['old_date_stamp'] = TTDate::getMiddleDayEpoch($date_stamp);

        return true;
    }

    public function setPayPeriod($id = null)
    {
        $id = trim($id);

        if ($id == null) {
            $id = (int)PayPeriodListFactory::findPayPeriod($this->getUser(), $this->getDateStamp());
        }

        $pplf = TTnew('PayPeriodListFactory');

        //Allow NULL pay period, incase its an absence or something in the future.
        //Cron will fill in the pay period later.
        if (
            $id == 0
            or
            $this->Validator->isResultSetWithRows('pay_period',
                $pplf->getByID($id),
                TTi18n::gettext('Invalid Pay Period')
            )
        ) {
            $this->data['pay_period_id'] = $id;

            return true;
        }

        return false;
    }

    public function getNote()
    {
        if (isset($this->data['note'])) {
            return $this->data['note'];
        }

        return false;
    }

    public function setNote($val)
    {
        $val = trim($val);

        if ($val == ''
            or
            $this->Validator->isLength('note',
                $val,
                TTi18n::gettext('Note is too long'),
                0,
                1024)
        ) {
            $this->data['note'] = $val;

            return true;
        }

        return false;
    }

    public function getOtherID1()
    {
        if (isset($this->data['other_id1'])) {
            return $this->data['other_id1'];
        }

        return false;
    }

    //This must be called after PunchObject() has been set and before isValid() is called.

    public function setOtherID1($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id1',
                $value,
                TTi18n::gettext('Other ID 1 is invalid'),
                1, 255)
        ) {
            $this->data['other_id1'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID2()
    {
        if (isset($this->data['other_id2'])) {
            return $this->data['other_id2'];
        }

        return false;
    }

    public function setOtherID2($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id2',
                $value,
                TTi18n::gettext('Other ID 2 is invalid'),
                1, 255)
        ) {
            $this->data['other_id2'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID3()
    {
        if (isset($this->data['other_id3'])) {
            return $this->data['other_id3'];
        }

        return false;
    }

    public function setOtherID3($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id3',
                $value,
                TTi18n::gettext('Other ID 3 is invalid'),
                1, 255)
        ) {
            $this->data['other_id3'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID4()
    {
        if (isset($this->data['other_id4'])) {
            return $this->data['other_id4'];
        }

        return false;
    }

    public function setOtherID4($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id4',
                $value,
                TTi18n::gettext('Other ID 4 is invalid'),
                1, 255)
        ) {
            $this->data['other_id4'] = $value;

            return true;
        }

        return false;
    }

    public function getOtherID5()
    {
        if (isset($this->data['other_id5'])) {
            return $this->data['other_id5'];
        }

        return false;
    }

    public function setOtherID5($value)
    {
        $value = trim($value);

        if ($value == ''
            or
            $this->Validator->isLength('other_id5',
                $value,
                TTi18n::gettext('Other ID 5 is invalid'),
                1, 255)
        ) {
            $this->data['other_id5'] = $value;

            return true;
        }

        return false;
    }

    public function setEnableCalcSystemTotalTime($bool)
    {
        $this->calc_system_total_time = $bool;

        return true;
    }

    public function getEnableCalcWeeklySystemTotalTime()
    {
        if (isset($this->calc_weekly_system_total_time)) {
            return $this->calc_weekly_system_total_time;
        }

        return false;
    }

    public function setEnableCalcWeeklySystemTotalTime($bool)
    {
        $this->calc_weekly_system_total_time = $bool;

        return true;
    }

    public function setEnableCalcException($bool)
    {
        $this->calc_exception = $bool;

        return true;
    }

    public function setEnablePreMatureException($bool)
    {
        $this->premature_exception = $bool;

        return true;
    }

    public function setEnableCalcUserDateTotal($bool)
    {
        $this->calc_user_date_total = $bool;

        return true;
    }

    public function setEnableCalcUserDateID($bool)
    {
        $this->calc_user_date_id = $bool;

        return true;
    }

    public function setEnableCalcTotalTime($bool)
    {
        $this->calc_total_time = $bool;

        return true;
    }

    public function getEnableStrictJobValidation()
    {
        if (isset($this->strict_job_validiation)) {
            return $this->strict_job_validiation;
        }

        return false;
    }

    public function setEnableStrictJobValidation($bool)
    {
        $this->strict_job_validiation = $bool;

        return true;
    }

    /*
        function getMealPolicyID() {
            if ( isset($this->data['meal_policy_id']) ) {
                return (int)$this->data['meal_policy_id'];
            }

            return FALSE;
        }
        function setMealPolicyID($id) {
            $id = trim($id);

            if ( $id == '' OR empty($id) ) {
                $id = NULL;
            }

            $mplf = TTnew( 'MealPolicyListFactory' );

            if ( $id == NULL
                    OR
                    $this->Validator->isResultSetWithRows(	'meal_policy',
                                                            $mplf->getByID($id),
                                                            TTi18n::gettext('Meal Policy is invalid')
                                                        ) ) {

                $this->data['meal_policy_id'] = $id;

                return TRUE;
            }

            return FALSE;
        }
    */

    public function Validate($ignore_warning = true)
    {
        Debug::text('Validating...', __FILE__, __LINE__, __METHOD__, 10);

        //Call this here so getShiftData can get the correct total time, before we call findUserDate.
        if ($this->getEnableCalcTotalTime() == true) {
            $this->calcTotalTime();
        }

        if (is_object($this->getPunchObject())) {
            $this->findUserDate();
        }
        Debug::text('DateStamp: ' . $this->getDateStamp(), __FILE__, __LINE__, __METHOD__, 10);

        if ($this->getUser() == false) {
            $this->Validator->isTRUE('user_id',
                false,
                TTi18n::gettext('Employee is invalid'));
        }

        //Don't check for a valid pay period here, do that in PunchFactory->Validate(), as we need to allow users to delete punches that were created outside pay periods in legacy versions.
        if ($this->getDeleted() == false and $this->getDateStamp() == false) {
            $this->Validator->isTRUE('date_stamp',
                false,
                TTi18n::gettext('Date/Time is incorrect, or pay period does not exist for this date. Please create a pay period schedule and assign this employee to it if you have not done so already'));
        } elseif ($this->getDateStamp() != false and is_object($this->getPayPeriodObject()) and $this->getPayPeriodObject()->getIsLocked() == true) {
            $this->Validator->isTRUE('date_stamp',
                false,
                TTi18n::gettext('Pay Period is Currently Locked'));
        }

        //Make sure the user isn't entering punches before the employees hire or after termination date, as its likely they wouldn't have a wage
        //set for that anyways and wouldn't get paid for it.
        if (($this->getDeleted() == false and (is_object($this->getPunchObject()) and $this->getPunchObject()->getDeleted() == false)) and $this->getDateStamp() != false and is_object($this->getUserObject())) {
            if ($this->getUserObject()->getHireDate() != '' and TTDate::getBeginDayEpoch($this->getDateStamp()) < TTDate::getBeginDayEpoch($this->getUserObject()->getHireDate())) {
                $this->Validator->isTRUE('date_stamp',
                    false,
                    TTi18n::gettext('Punch is before employees hire date'));
            }

            if ($this->getUserObject()->getTerminationDate() != '' and TTDate::getEndDayEpoch($this->getDateStamp()) > TTDate::getEndDayEpoch($this->getUserObject()->getTerminationDate())) {
                $this->Validator->isTRUE('date_stamp',
                    false,
                    TTi18n::gettext('Punch is after employees termination date'));
            }
        }

        //Skip these checks if they are deleting a punch.
        if (is_object($this->getPunchObject()) and $this->getPunchObject()->getDeleted() == false) {
            $plf = $this->getPLFByPunchControlID();
            if ($plf !== null and (($this->isNew() and $plf->getRecordCount() == 2) or $plf->getRecordCount() > 2)) {
                //TTi18n::gettext('Punch Control can not have more than two punches. Please use the Add Punch button instead')
                //They might be trying to insert a punch inbetween two others?
                $this->Validator->isTRUE('punch_control',
                    false,
                    TTi18n::gettext('Time conflicts with another punch on this day (c)'));
            }

            //Sometimes shift data won't return all the punches to proper check for conflicting punches.
            //So we need to make sure other punches assigned to this punch_control record are proper.
            //This fixes the bug of having shifts: 2:00AM Lunch Out, 2:30AM Lunch In, 6:00AM Out, 10:00PM In (in that order), then trying to move the 10PM punch to the open IN slot before the 2AM punch.
            if ($plf->getRecordCount() > 0) {
                foreach ($plf as $p_obj) {
                    if ($p_obj->getID() != $this->getPunchObject()->getID()) {
                        if ($this->getPunchObject()->getStatus() == 10 and $p_obj->getStatus() == 20 and $this->getPunchObject()->getTimeStamp() > $p_obj->getTimeStamp()) {
                            //Make sure we match on status==10 for both sides, otherwise this fails to catch the problem case.
                            // Also test $p_obj->getStatus() == 20, to catch cases where a Break In punch is followed by a Lunch Out punch, but the Break In timestamp is AFTER the Lunch Out timestamp.
                            $this->Validator->isTRUE('time_stamp',
                                false,
                                TTi18n::gettext('In punches cannot occur after an out punch, in the same punch pair (a)'));
                        } elseif ($this->getPunchObject()->getStatus() == 20 and $p_obj->getStatus() == 10 and $this->getPunchObject()->getTimeStamp() < $p_obj->getTimeStamp()) {
                            $this->Validator->isTRUE('time_stamp',
                                false,
                                TTi18n::gettext('Out punches cannot occur before an in punch, in the same punch pair (a)'));
                        }
                    }
                }
            }
            unset($p_obj);

            if ($this->Validator->isValid() == true) { //Don't bother checking these resource intensive issues if there are already validation errors.

                $shift_data = $this->getShiftData();
                if (is_array($shift_data) and $this->Validator->hasError('time_stamp') == false) {
                    foreach ($shift_data['punches'] as $punch_data) {
                        //Make sure there aren't two In punches, or two Out punches in the same pair.
                        //This fixes the bug where if you have an In punch, then click the blank cell below it
                        //to add a new punch, but change the status from Out to In instead.
                        if (isset($punches[$punch_data['punch_control_id']][$punch_data['status_id']])) {
                            if ($punch_data['status_id'] == 10) {
                                $this->Validator->isTRUE('time_stamp',
                                    false,
                                    TTi18n::gettext('In punches cannot occur twice in the same punch pair, you may want to make this an out punch instead') . '(b)');
                            } else {
                                $this->Validator->isTRUE('time_stamp',
                                    false,
                                    TTi18n::gettext('Out punches cannot occur twice in the same punch pair, you may want to make this an in punch instead') . '(b)');
                            }
                        }

                        //Debug::text(' Current Punch Object: ID: '. $this->getPunchObject()->getId() .' TimeStamp: '. $this->getPunchObject()->getTimeStamp() .' Status: '. $this->getPunchObject()->getStatus(), __FILE__, __LINE__, __METHOD__, 10);
                        //Debug::text(' Looping Punch Object: ID: '. $punch_data['id'] .' TimeStamp: '. $punch_data['time_stamp'] .' Status: '.$punch_data['status_id'], __FILE__, __LINE__, __METHOD__, 10);

                        //Check for another punch that matches the timestamp and status.
                        if ($this->getPunchObject()->getID() != $punch_data['id']) {
                            if ($this->getPunchObject()->getTimeStamp() == $punch_data['time_stamp'] and $this->getPunchObject()->getStatus() == $punch_data['status_id']) {
                                $this->Validator->isTRUE('time_stamp',
                                    false,
                                    TTi18n::gettext('Time and status match that of another punch, this could be due to rounding') . ' (' . TTDate::getDate('DATE+TIME', $punch_data['time_stamp']) . ')');
                                break; //Break the loop on validation error, so we don't get multiple errors that may be confusing.
                            }
                        }

                        //Check for another punch that matches the timestamp and NOT status in the SAME punch pair.
                        if ($this->getPunchObject()->getID() != $punch_data['id'] and $this->getID() == $punch_data['punch_control_id']) {
                            if ($this->getPunchObject()->getTimeStamp() == $punch_data['time_stamp'] and $this->getPunchObject()->getStatus() != $punch_data['status_id']) {
                                $this->Validator->isTRUE('time_stamp',
                                    false,
                                    TTi18n::gettext('Time matches another punch in the same punch pair, this could be due to rounding') . ' (' . TTDate::getDate('DATE+TIME', $punch_data['time_stamp']) . ')');
                                break; //Break the loop on validation error, so we don't get multiple errors that may be confusing.
                            }
                        }

                        $punches[$punch_data['punch_control_id']][$punch_data['status_id']] = $punch_data;
                    }
                    unset($punch_data);

                    if (isset($punches[$this->getID()])) {
                        Debug::text('Current Punch ID: ' . $this->getPunchObject()->getId() . ' Punch Control ID: ' . $this->getID() . ' Status: ' . $this->getPunchObject()->getStatus(), __FILE__, __LINE__, __METHOD__, 10);
                        //Debug::Arr($punches, 'Punches Arr: ', __FILE__, __LINE__, __METHOD__, 10);

                        if ($this->getPunchObject()->getStatus() == 10 and isset($punches[$this->getID()][20]) and $this->getPunchObject()->getTimeStamp() > $punches[$this->getID()][20]['time_stamp']) {
                            $this->Validator->isTRUE('time_stamp',
                                false,
                                TTi18n::gettext('In punches cannot occur after an out punch, in the same punch pair'));
                        } elseif ($this->getPunchObject()->getStatus() == 20 and isset($punches[$this->getID()][10]) and $this->getPunchObject()->getTimeStamp() < $punches[$this->getID()][10]['time_stamp']) {
                            $this->Validator->isTRUE('time_stamp',
                                false,
                                TTi18n::gettext('Out punches cannot occur before an in punch, in the same punch pair'));
                        } else {
                            Debug::text('bPunch does not match any other punch pair.', __FILE__, __LINE__, __METHOD__, 10);

                            $punch_neighbors = Misc::getArrayNeighbors($punches, $this->getID(), 'both');
                            //Debug::Arr($punch_neighbors, ' Punch Neighbors: ', __FILE__, __LINE__, __METHOD__, 10);

                            if (isset($punch_neighbors['next']) and isset($punches[$punch_neighbors['next']])) {
                                Debug::text('Found Next Punch...', __FILE__, __LINE__, __METHOD__, 10);
                                if ((isset($punches[$punch_neighbors['next']][10]) and $this->getPunchObject()->getTimeStamp() > $punches[$punch_neighbors['next']][10]['time_stamp'])
                                    or (isset($punches[$punch_neighbors['next']][20]) and $this->getPunchObject()->getTimeStamp() > $punches[$punch_neighbors['next']][20]['time_stamp'])
                                ) {
                                    $this->Validator->isTRUE('time_stamp',
                                        false,
                                        TTi18n::gettext('Time conflicts with another punch on this day') . ' (a)');
                                }
                            }

                            if (isset($punch_neighbors['prev']) and isset($punches[$punch_neighbors['prev']])) {
                                Debug::text('Found prev Punch...', __FILE__, __LINE__, __METHOD__, 10);

                                //This needs to take into account DST. Specifically if punches are like this:
                                //03-Nov-12: IN: 10:00PM
                                //04-Nov-12: OUT: 1:00AM L
                                //04-Nov-12: IN: 1:30AM L
                                //04-Nov-12: OUT: 6:30AM L
                                //Since the 1AM to 2AM occur twice due to the "fall back" DST change, we need to allow those punches to be entered.
                                if ((isset($punches[$punch_neighbors['prev']][10]) and ($this->getPunchObject()->getTimeStamp() < $punches[$punch_neighbors['prev']][10]['time_stamp'] and TTDate::doesRangeSpanDST($this->getPunchObject()->getTimeStamp(), $punches[$punch_neighbors['prev']][10]['time_stamp']) == false))
                                    or
                                    (isset($punches[$punch_neighbors['prev']][20]) and ($this->getPunchObject()->getTimeStamp() < $punches[$punch_neighbors['prev']][20]['time_stamp'] and TTDate::doesRangeSpanDST($this->getPunchObject()->getTimeStamp(), $punches[$punch_neighbors['prev']][20]['time_stamp']) == false))
                                ) {
                                    $this->Validator->isTRUE('time_stamp',
                                        false,
                                        TTi18n::gettext('Time conflicts with another punch on this day') . ' (b)');
                                }
                            }
                        }

                        //Check to make sure punches don't exceed maximum shift time.
                        $maximum_shift_time = $plf->getPayPeriodMaximumShiftTime($this->getPunchObject()->getUser());
                        Debug::text('Maximum shift time: ' . $maximum_shift_time, __FILE__, __LINE__, __METHOD__, 10);
                        if ($shift_data['total_time'] > $maximum_shift_time) {
                            $this->Validator->isTRUE('time_stamp',
                                false,
                                TTi18n::gettext('Punch exceeds maximum shift time of') . ' ' . TTDate::getTimeUnit($maximum_shift_time) . ' ' . TTi18n::getText('hrs set for this pay period schedule'));
                        }
                    }
                    unset($punches);
                }
            }
        }

        if ($ignore_warning == false) {
            //Warn users if they are trying to insert punches too far in the future.
            if ($this->getDateStamp() != false and $this->getDateStamp() > (time() + (86400 * 366))) {
                $this->Validator->Warning('date_stamp', TTi18n::gettext('Date is more than one year in the future'));
            }

            //Check to see if timesheet is verified, if so show warning to notify the user.
            if (is_object($this->getPayPeriodScheduleObject())
                and $this->getPayPeriodScheduleObject()->getTimeSheetVerifyType() != 10
            ) {
                //Find out if timesheet is verified or not.
                $pptsvlf = TTnew('PayPeriodTimeSheetVerifyListFactory');
                $pptsvlf->getByPayPeriodIdAndUserId($this->getPayPeriod(), $this->getUser());
                if ($pptsvlf->getRecordCount() > 0) {
                    //Pay period is verified
                    $this->Validator->Warning('date_stamp', TTi18n::gettext('Pay period is already verified, saving these changes will require it to be reverified'));
                }
            }
        }

        return true;
    }

    public function getEnableCalcTotalTime()
    {
        if (isset($this->calc_total_time)) {
            return $this->calc_total_time;
        }

        return false;
    }

    public function calcTotalTime($force = true)
    {
        if ($force == true or $this->is_total_time_calculated == false) {
            $this->is_total_time_calculated = true;

            $plf = TTnew('PunchListFactory');
            $plf->getByPunchControlId($this->getId());
            //Make sure punches are in In/Out pairs before we bother calculating.
            if ($plf->getRecordCount() > 0 and ($plf->getRecordCount() % 2) == 0) {
                Debug::text(' Found Punches to calculate.', __FILE__, __LINE__, __METHOD__, 10);
                $in_pair = false;
                foreach ($plf as $punch_obj) {
                    //Check for proper in/out pairs
                    //First row should be an Out status (reverse ordering)
                    Debug::text(' Punch: Status: ' . $punch_obj->getStatus() . ' TimeStamp: ' . $punch_obj->getTimeStamp(), __FILE__, __LINE__, __METHOD__, 10);
                    if ($punch_obj->getStatus() == 20) {
                        //Debug::text(' Found Out Status, starting pair: ', __FILE__, __LINE__, __METHOD__, 10);
                        $this->out_punch_obj = $punch_obj;

                        $out_stamp = $punch_obj->getTimeStamp();
                        $out_actual_stamp = $punch_obj->getActualTimeStamp();
                        $in_pair = true;
                    } elseif ($in_pair == true) {
                        $this->in_punch_obj = $punch_obj;

                        $punch_obj->setScheduleID($punch_obj->findScheduleID(null, $this->getUser())); //Find Schedule Object for this Punch
                        $in_stamp = $punch_obj->getTimeStamp();
                        $in_actual_stamp = $punch_obj->getActualTimeStamp();
                        //Got a pair... Totaling.
                        //Debug::text(' Found a pair... Totaling: ', __FILE__, __LINE__, __METHOD__, 10);
                        if ($out_stamp != '' and $in_stamp != '') {
                            //Due to DST, always pay the employee based on the time they actually worked,
                            //which is handled automatically by simple epoch math.
                            //Therefore in fall they get paid one hour more, and spring one hour less.
                            $total_time = ($out_stamp - $in_stamp);// + TTDate::getDSTOffset( $in_stamp, $out_stamp );
                        }
                        if ($out_actual_stamp != '' and $in_actual_stamp != '') {
                            $actual_total_time = ($out_actual_stamp - $in_actual_stamp);
                        }
                    }
                }

                if (isset($total_time)) {
                    Debug::text(' Setting TotalTime: ' . $total_time, __FILE__, __LINE__, __METHOD__, 10);

                    $this->setTotalTime($total_time);
                    $this->setActualTotalTime($actual_total_time);

                    return true;
                }
            } else {
                Debug::text(' No Punches to calculate, or punches arent in pairs. Set total to 0', __FILE__, __LINE__, __METHOD__, 10);
                $this->setTotalTime(0);
                $this->setActualTotalTime(0);

                return true;
            }
        }

        return false;
    }

    public function setTotalTime($int)
    {
        $int = (int)$int;

        if ($this->Validator->isNumeric('total_time',
            $int,
            TTi18n::gettext('Incorrect total time'))
        ) {
            $this->data['total_time'] = $int;

            return true;
        }

        return false;
    }

    public function setActualTotalTime($int)
    {
        $int = (int)$int;

        if ($int < 0) {
            $int = 0;
        }

        if ($this->Validator->isNumeric('actual_total_time',
            $int,
            TTi18n::gettext('Incorrect actual total time'))
        ) {
            $this->data['actual_total_time'] = $int;

            return true;
        }

        return false;
    }

    public function getPunchObject()
    {
        if (is_object($this->punch_obj)) {
            return $this->punch_obj;
        }

        return false;
    }

    public function findUserDate()
    {
        /*
            Issues to consider:
                ** Timezones, if one employee is in PST and the payroll administrator/pay period is in EST, if the employee
                ** punches in at 11:00PM PST, its actually 2AM EST on the next day, so which day does the time get assigned to?
                ** Use the employees preferred timezone to determine the proper date, otherwise if we use the PP schedule timezone it may
                ** be a little confusing to employees because they may punch in on one day and have the time appears under different day.

                1. Employee punches out at 11:00PM, then is called in early at 4AM to start a new shift.
                Don't want to pair these punches.

                2. Employee starts 11:00PM shift late at 1:00AM the next day. Works until 7AM, then comes in again
                at 11:00PM the same day and works until 4AM, then 4:30AM to 7:00AM. The 4AM-7AM punches need to be paired on the same day.

                3. Ambulance EMT works 36hours straight in a single punch.

                *Perhaps we should handle lunch punches and normal punches differently? Lunch punches have
                a different "continuous time setting then normal punches.

                *Change daily continuous time to:
                * Group (Normal) Punches: X hours before midnight to punches X hours after midnight
                * Group (Lunch/Break) Punches: X hours before midnight to punches X hours after midnight
                *	Normal punches X hours after midnight group to punches X hours before midnight.
                *	Lunch/Break punches X hours after midnight group to punches X hours before midnight.

                OR, what if we change continuous time to be just the gap between punches that cause
                    a new day to start? Combine this with daily cont. time so we know what the window
                    is for punches to begin the gap search. Or we can always just search for a previous
                    punch Xhrs before the current punch.
                    - Do we look back to a In punch, or look back to an Out punch though? I think an Out Punch.
                        What happens if they forgot to punch out though?
                    Logic:
                        If this is an Out punch:
                            Find previous punch back to maximum shift time to find an In punch to pair it with.
                        Else, if this is an In punch:
                            Find previous punch back to maximum shift time to find an Out punch to combine it with.
                            If out punch is found inside of new_shift trigger time, we place this punch on the previous day.
                            Else: we place this punch on todays date.


                * Minimum time between punches to cause a new shift to start: Xhrs (default: 4hrs)
                    new_day_trigger_time
                    Call it: Minimum time-off that triggers new shift:
                        Minimum Time-Off Between Shifts:
                * Maximum shift time: Xhrs (for ambulance service) default to 16 or 24hrs?
                    This is essentially how far back we look for In punch to pair out punches with.
                    maximum_shift_length
                    - Add checks to ensure that no punch pair exceeds the maximum_shift_length
        */

        //Don't allow user_id=0, that is only used for open scheduled shifts, and sometimes this can sneak through during import.
        if ($this->getUser() == 0) {
            Debug::Text('ERROR: User ID is 0!: ' . $this->getUser(), __FILE__, __LINE__, __METHOD__, 10);
            return false;
        }

        /*
        This needs to be able to run before Validate is called, so we can validate the pay period schedule.
        */
        if ($this->getDateStamp() == false) {
            $this->setDateStamp($this->getPunchObject()->getTimeStamp());
        }

        Debug::Text(' Finding DateStamp: ' . TTDate::getDate('DATE+TIME', $this->getPunchObject()->getTimeStamp()) . ' Punch Control: ' . $this->getID() . ' User: ' . $this->getUser(), __FILE__, __LINE__, __METHOD__, 10);
        $shift_data = $this->getShiftData();
        if (is_array($shift_data)) {
            switch ($this->getPayPeriodScheduleObject()->getShiftAssignedDay()) {
                default:
                case 10: //Day they start on
                case 40: //Split at midnight
                    if (!isset($shift_data['first_in']['time_stamp'])) {
                        $shift_data['first_in']['time_stamp'] = $shift_data['last_out']['time_stamp'];
                    }
                    //Can't use the First In user_date_id because it may need to be changed when editing a punch.
                    //Debug::Text('Assign Shifts to the day they START on... Date: '. TTDate::getDate('DATE', $shift_data['first_in']['time_stamp']), __FILE__, __LINE__, __METHOD__, 10);
                    $user_date_epoch = $shift_data['first_in']['time_stamp'];
                    break;
                case 20: //Day they end on
                    if (!isset($shift_data['last_out']['time_stamp'])) {
                        $shift_data['last_out']['time_stamp'] = $shift_data['first_in']['time_stamp'];
                    }
                    Debug::Text('Assign Shifts to the day they END on... Date: ' . TTDate::getDate('DATE', $shift_data['last_out']['time_stamp']), __FILE__, __LINE__, __METHOD__, 10);
                    $user_date_epoch = $shift_data['last_out']['time_stamp'];
                    break;
                case 30: //Day with most time worked
                    Debug::Text('Assign Shifts to the day they WORK MOST on... Date: ' . TTDate::getDate('DATE', $shift_data['day_with_most_time']), __FILE__, __LINE__, __METHOD__, 10);
                    $user_date_epoch = $shift_data['day_with_most_time'];
                    break;
            }
        } else {
            Debug::Text('Not using shift data...', __FILE__, __LINE__, __METHOD__, 10);
            if ($this->getPunchObject()->getDeleted() == true) {
                //Check to see if there is another punch in the punch pair, and use that timestamp to assign days instead.
                Debug::Text('Punch is being deleted, use timestamp from other punch in pair if it exists...', __FILE__, __LINE__, __METHOD__, 10);

                $plf = TTNew('PunchListFactory');
                $plf->getByPunchControlId($this->getId());
                if ($plf->getRecordCount() > 0) {
                    foreach ($plf as $p_obj) {
                        if ($p_obj->getId() != $this->getPunchObject()->getId()) {
                            $user_date_epoch = $p_obj->getTimeStamp();
                            Debug::Text('Using timestamp from Punch: ' . $this->getPunchObject()->getId(), __FILE__, __LINE__, __METHOD__, 10);
                            break;
                        }
                    }
                } else {
                    Debug::Text('No punches left in punch pair...', __FILE__, __LINE__, __METHOD__, 10);
                    return true;
                }
                unset($plf, $p_obj);
            } else {
                $user_date_epoch = $this->getPunchObject()->getTimeStamp();
            }
        }

        if (isset($user_date_epoch) and $user_date_epoch > 0) {
            Debug::Text('Found DateStamp: ' . $user_date_epoch . ' Based On: ' . TTDate::getDate('DATE+TIME', $user_date_epoch), __FILE__, __LINE__, __METHOD__, 10);

            return $this->setDateStamp($user_date_epoch);
        }

        Debug::Text('No shift data to use to find DateStamp, using timestamp only: ' . TTDate::getDate('DATE+TIME', $this->getPunchObject()->getTimeStamp()), __FILE__, __LINE__, __METHOD__, 10);
        return true;
    }

    public function getShiftData()
    {
        if ($this->shift_data == null and is_object($this->getPunchObject()) and $this->getUser() > 0) {
            if (is_object($this->getPayPeriodScheduleObject())) {
                $this->shift_data = $this->getPayPeriodScheduleObject()->getShiftData(null, $this->getUser(), $this->getPunchObject()->getTimeStamp(), 'nearest_shift', $this);
            } else {
                Debug::Text('No pay period schedule found for user ID: ' . $this->getUser(), __FILE__, __LINE__, __METHOD__, 10);
            }
        }

        return $this->shift_data;
    }

    public function getPayPeriodScheduleObject()
    {
        if (is_object($this->pay_period_schedule_obj)) {
            return $this->pay_period_schedule_obj;
        } else {
            if ($this->getUser() > 0) {
                $ppslf = TTnew('PayPeriodScheduleListFactory');
                $ppslf->getByUserId($this->getUser());
                if ($ppslf->getRecordCount() == 1) {
                    $this->pay_period_schedule_obj = $ppslf->getCurrent();
                    return $this->pay_period_schedule_obj;
                }
            }

            return false;
        }
    }

    public function getPayPeriodObject()
    {
        return $this->getGenericObject('PayPeriodListFactory', $this->getPayPeriod(), 'pay_period_obj');
    }

    public function getPayPeriod()
    {
        if (isset($this->data['pay_period_id'])) {
            return (int)$this->data['pay_period_id'];
        }

        return false;
    }

    public function getUserObject()
    {
        return $this->getGenericObject('UserListFactory', $this->getUser(), 'user_obj');
    }

    public function getPLFByPunchControlID()
    {
        if ($this->plf == null and $this->getID() != false) {
            $this->plf = TTnew('PunchListFactory');
            $this->plf->getByPunchControlID($this->getID());
        }

        return $this->plf;
    }

    public function preSave()
    {
        if ($this->getBranch() === false) {
            $this->setBranch(0);
        }

        if ($this->getDepartment() === false) {
            $this->setDepartment(0);
        }

        if ($this->getJob() === false) {
            $this->setJob(0);
        }

        if ($this->getJobItem() === false) {
            $this->setJobItem(0);
        }

        if ($this->getQuantity() === false) {
            $this->setQuantity(0);
        }

        if ($this->getBadQuantity() === false) {
            $this->setBadQuantity(0);
        }

        if ($this->getPayPeriod() == false) {
            $this->setPayPeriod();
        }

        //Set Job default Job Item if required.
        if ($this->getJob() != false and $this->getJobItem() == '') {
            Debug::text(' Job is set (' . $this->getJob() . '), but no task is... Using default job item...', __FILE__, __LINE__, __METHOD__, 10);

            if (is_object($this->getJobObject())) {
                Debug::text(' Default Job Item: ' . $this->getJobObject()->getDefaultItem(), __FILE__, __LINE__, __METHOD__, 10);
                $this->setJobItem($this->getJobObject()->getDefaultItem());
            }
        }

        if ($this->getEnableCalcTotalTime() == true) {
            $this->calcTotalTime();
        }

        if (is_object($this->getPunchObject())) {
            $this->findUserDate();
        }

        //Check to see if timesheet is verified, if so unverify it on modified punch.
        //Make sure exceptions are calculated *after* this so TimeSheet Not Verified exceptions can be triggered again.
        if (is_object($this->getPayPeriodScheduleObject())
            and $this->getPayPeriodScheduleObject()->getTimeSheetVerifyType() != 10
        ) {
            //Find out if timesheet is verified or not.
            $pptsvlf = TTnew('PayPeriodTimeSheetVerifyListFactory');
            $pptsvlf->getByPayPeriodIdAndUserId($this->getPayPeriod(), $this->getUser());
            if ($pptsvlf->getRecordCount() > 0) {
                //Pay period is verified, delete all records and make log entry.
                Debug::text('Pay Period is verified, deleting verification records: ' . $pptsvlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
                foreach ($pptsvlf as $pptsv_obj) {
                    if (is_object($this->getPunchObject())) {
                        TTLog::addEntry($pptsv_obj->getId(), 500, TTi18n::getText('TimeSheet Modified After Verification') . ': ' . UserListFactory::getFullNameById($this->getUser()) . ' ' . TTi18n::getText('Punch') . ': ' . TTDate::getDate('DATE+TIME', $this->getPunchObject()->getTimeStamp()), null, $pptsvlf->getTable());
                    }
                    $pptsv_obj->setDeleted(true);
                    if ($pptsv_obj->isValid()) {
                        $pptsv_obj->Save();
                    }
                }
            }
        }

        $this->changePreviousPunchType();

        return true;
    }

    public function getBranch()
    {
        if (isset($this->data['branch_id'])) {
            return (int)$this->data['branch_id'];
        }

        return false;
    }

    public function setBranch($id)
    {
        $id = trim($id);

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        if ($this->getUser() != '' and is_object($this->getUserObject()) and $id == -1) { //Find default
            $id = $this->getUserObject()->getDefaultBranch();
            Debug::Text('Using Default Branch: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        }

        $blf = TTnew('BranchListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('branch',
                $blf->getByID($id),
                TTi18n::gettext('Branch does not exist')
            )
        ) {
            $this->data['branch_id'] = $id;

            return true;
        }

        return false;
    }

    public function getDepartment()
    {
        if (isset($this->data['department_id'])) {
            return (int)$this->data['department_id'];
        }

        return false;
    }

    public function setDepartment($id)
    {
        $id = trim($id);

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        if ($this->getUser() != '' and is_object($this->getUserObject()) and $id == -1) { //Find default
            $id = $this->getUserObject()->getDefaultDepartment();
            Debug::Text('Using Default Department: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        }

        $dlf = TTnew('DepartmentListFactory');

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('department',
                $dlf->getByID($id),
                TTi18n::gettext('Department does not exist')
            )
        ) {
            $this->data['department_id'] = $id;

            return true;
        }

        return false;
    }

    public function getJob()
    {
        if (isset($this->data['job_id'])) {
            return (int)$this->data['job_id'];
        }

        return false;
    }

    public function setJob($id)
    {
        $id = trim($id);

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        $id = 0;

        if ($this->getUser() != '' and is_object($this->getUserObject()) and $id == -1) { //Find default
            $id = $this->getUserObject()->getDefaultJob();
            Debug::Text('Using Default Job: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        }

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('job',
                $jlf->getByID($id),
                TTi18n::gettext('Job does not exist')
            )
        ) {
            $this->data['job_id'] = $id;

            return true;
        }

        return false;
    }

    public function setJobItem($id)
    {
        $id = trim($id);

        if ($id == false or $id == 0 or $id == '') {
            $id = 0;
        }

        $id = 0;

        if ($this->getUser() != '' and is_object($this->getUserObject()) and $id == -1) { //Find default
            $id = $this->getUserObject()->getDefaultJobItem();
            Debug::Text('Using Default Job Item: ' . $id, __FILE__, __LINE__, __METHOD__, 10);
        }

        if ($id == 0
            or
            $this->Validator->isResultSetWithRows('job_item',
                $jilf->getByID($id),
                TTi18n::gettext('Job Item does not exist')
            )
        ) {
            $this->data['job_item_id'] = $id;

            return true;
        }

        return false;
    }

    public function getQuantity()
    {
        if (isset($this->data['quantity'])) {
            return (float)$this->data['quantity'];
        }

        return false;
    }

    public function setQuantity($val)
    {
        $val = TTi18n::parseFloat($val);

        if ($val == false or $val == 0 or $val == '') {
            $val = 0;
        }

        if ($val == 0
            or
            $this->Validator->isFloat('quantity',
                $val,
                TTi18n::gettext('Incorrect quantity'))
        ) {
            $this->data['quantity'] = $val;

            return true;
        }

        return false;
    }

    public function getBadQuantity()
    {
        if (isset($this->data['bad_quantity'])) {
            return (float)$this->data['bad_quantity'];
        }

        return false;
    }

    public function setBadQuantity($val)
    {
        $val = TTi18n::parseFloat($val);

        if ($val == false or $val == 0 or $val == '') {
            $val = 0;
        }

        if ($val == 0
            or
            $this->Validator->isFloat('bad_quantity',
                $val,
                TTi18n::gettext('Incorrect bad quantity'))
        ) {
            $this->data['bad_quantity'] = $val;

            return true;
        }

        return false;
    }

    public function getJobObject()
    {
        return $this->getGenericObject('JobListFactory', $this->getJob(), 'job_obj');
    }

    public function changePreviousPunchType()
    {
        Debug::text(' Previous Punch to Lunch/Break...', __FILE__, __LINE__, __METHOD__, 10);

        if (is_object($this->getPunchObject())) {
            if ($this->getPunchObject()->getType() == 20 and $this->getPunchObject()->getStatus() == 10) {
                Debug::text(' bbPrevious Punch to Lunch...', __FILE__, __LINE__, __METHOD__, 10);

                //We used to use getShiftData() then pull out the previous punch from that, however that can cause problems
                //based on the Minimum Time-Off Between Shifts. Either way though that can't be less than the lunch/break autodetection time.
                $previous_punch_obj = $this->getPunchObject()->getPreviousPunchObject($this->getPunchObject()->getActualTimeStamp());
                if (is_object($previous_punch_obj) and $previous_punch_obj->getType() != 20) {
                    Debug::text(' Previous Punch ID: ' . $previous_punch_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
                    $this->getPunchObject()->setScheduleID($this->getPunchObject()->findScheduleID());
                    if ($this->getPunchObject()->inMealPolicyWindow($this->getPunchObject()->getTimeStamp(), $previous_punch_obj->getTimeStamp(), $previous_punch_obj->getStatus()) == true) {
                        Debug::text(' Previous Punch needs to change to Lunch...', __FILE__, __LINE__, __METHOD__, 10);

                        $plf = TTnew('PunchListFactory');
                        $plf->getById($previous_punch_obj->getId());
                        if ($plf->getRecordCount() == 1) {
                            Debug::text(' Modifying previous punch...', __FILE__, __LINE__, __METHOD__, 10);
                            $pf = $plf->getCurrent();
                            $pf->setUser($this->getUser());
                            $pf->setType(20); //Lunch
                            //If we start re-rounding this punch we have to recalculate the total for the previous punch_control too.
                            $pf->setTimeStamp($pf->getTimeStamp()); //Re-round timestamp now that its a lunch punch.
                            if ($pf->Save(false) == true) {
                                $pcf = $pf->getPunchControlObject();
                                $pcf->setPunchObject($pf);
                                $pcf->setEnableCalcUserDateID(true);
                                $pcf->setEnableCalcTotalTime(true);
                                $pcf->setEnableCalcSystemTotalTime(true);
                                $pcf->setEnableCalcWeeklySystemTotalTime(true);
                                $pcf->setEnableCalcUserDateTotal(true);
                                if ($pcf->isValid() == true) {
                                    Debug::Text(' Punch Control is valid, saving...: ', __FILE__, __LINE__, __METHOD__, 10);
                                    if ($pcf->Save(true, true) == true) { //Force isNew() lookup.\
                                        Debug::text(' Returning TRUE!', __FILE__, __LINE__, __METHOD__, 10);
                                        return true;
                                    }
                                }
                            }
                        }
                    }
                }
            } elseif ($this->getPunchObject()->getType() == 30 and $this->getPunchObject()->getStatus() == 10) {
                Debug::text(' bbPrevious Punch to Break...', __FILE__, __LINE__, __METHOD__, 10);

                //We used to use getShiftData() then pull out the previous punch from that, however that can cause problems
                //based on the Minimum Time-Off Between Shifts. Either way though that can't be less than the lunch/break autodetection time.
                $previous_punch_obj = $this->getPunchObject()->getPreviousPunchObject($this->getPunchObject()->getActualTimeStamp());
                if (is_object($previous_punch_obj) and $previous_punch_obj->getType() != 30) {
                    Debug::text(' Previous Punch ID: ' . $previous_punch_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
                    $this->getPunchObject()->setScheduleID($this->getPunchObject()->findScheduleID());
                    if ($this->getPunchObject()->inBreakPolicyWindow($this->getPunchObject()->getTimeStamp(), $previous_punch_obj->getTimeStamp(), $previous_punch_obj->getStatus()) == true) {
                        Debug::text(' Previous Punch needs to change to Break...', __FILE__, __LINE__, __METHOD__, 10);

                        $plf = TTnew('PunchListFactory');
                        $plf->getById($previous_punch_obj->getId());
                        if ($plf->getRecordCount() == 1) {
                            Debug::text(' Modifying previous punch...', __FILE__, __LINE__, __METHOD__, 10);

                            $pf = $plf->getCurrent();
                            $pf->setUser($this->getUser());
                            $pf->setType(30); //Break
                            //If we start re-rounding this punch we have to recalculate the total for the previous punch_control too.
                            $pf->setTimeStamp($pf->getTimeStamp()); //Re-round timestamp now that its a break punch.
                            if ($pf->Save(false) == true) {
                                $pcf = $pf->getPunchControlObject();
                                $pcf->setPunchObject($pf);
                                $pcf->setEnableCalcUserDateID(true);
                                $pcf->setEnableCalcTotalTime(true);
                                $pcf->setEnableCalcSystemTotalTime(true);
                                $pcf->setEnableCalcWeeklySystemTotalTime(true);
                                $pcf->setEnableCalcUserDateTotal(true);
                                if ($pcf->isValid() == true) {
                                    Debug::Text(' Punch Control is valid, saving...: ', __FILE__, __LINE__, __METHOD__, 10);
                                    if ($pcf->Save(true, true) == true) { //Force isNew() lookup.\
                                        Debug::text(' Returning TRUE!', __FILE__, __LINE__, __METHOD__, 10);
                                        return true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        Debug::text(' Returning false!', __FILE__, __LINE__, __METHOD__, 10);

        return false;
    }

    public function postSave()
    {
        $this->removeCache($this->getId());

        $this->calcUserDate();
        $this->calcUserDateTotal();

        if ($this->getEnableCalcSystemTotalTime() == true and is_object($this->getUserObject())) {
            //old_date_stamps can contain other dates from calcUserDate() as well.
            $this->old_date_stamps[] = $this->getDateStamp(); //Make sure the current date is calculated
            if ($this->getOldDateStamp() != '') {
                $this->old_date_stamps[] = $this->getOldDateStamp(); //Make sure the old date is calculated
            }
            UserDateTotalFactory::reCalculateDay($this->getUserObject(), $this->old_date_stamps, $this->getEnableCalcException(), $this->getEnablePreMatureException());
        }

        return true;
    }

    public function calcUserDate()
    {
        if ($this->getEnableCalcUserDateID() == true) {
            $date_stamp = TTDate::getMiddleDayEpoch($this->getDateStamp()); //preSave should already be called before running this function.

            Debug::Text(' Calculating User ID: ' . $this->getUser() . ' DateStamp: ' . $this->getDateStamp(), __FILE__, __LINE__, __METHOD__, 10);

            $shift_data = $this->getShiftData();
            if (is_array($shift_data)) {
                //Don't re-arrange shifts until all punches are paired and we have enough information.
                //Thats what the count() % 2 is used for.
                if ($this->getUser() != false
                    and isset($date_stamp) and $date_stamp > 0
                    and (isset($shift_data['punch_control_ids']) and is_array($shift_data['punch_control_ids']))
                    and (isset($shift_data['punches']) and count($shift_data['punches']) % 2 == 0)
                ) {
                    Debug::Text('Assigning all punch_control_ids to User ID: ' . $this->getUser() . ' DateStamp: ' . $date_stamp, __FILE__, __LINE__, __METHOD__, 10);

                    //$this->old_user_date_ids[] = $user_date_id;
                    //$this->old_user_date_ids[] = $this->getOldUserDateID();
                    $this->old_date_stamps[] = $date_stamp;
                    if ($this->getOldDateStamp() != false) {
                        $this->old_date_stamps[] = $this->getOldDateStamp();
                    }

                    $processed_punch_control_ids = array();
                    foreach ($shift_data['punch_control_ids'] as $punch_control_id) {
                        $pclf = TTnew('PunchControlListFactory');
                        $pclf->getById($punch_control_id);
                        if ($pclf->getRecordCount() == 1) {
                            $processed_punch_control_ids[] = $punch_control_id;
                            $pc_obj = $pclf->getCurrent();
                            if (TTDate::getMiddleDayEpoch($pc_obj->getDateStamp()) != $date_stamp) {
                                Debug::Text(' Saving Punch Control ID: ' . $punch_control_id . ' with new DateStamp: ' . $date_stamp . ' Old DateStamp: ' . $pc_obj->getDateStamp(), __FILE__, __LINE__, __METHOD__, 10);

                                $this->old_date_stamps[] = $pc_obj->getDateStamp();
                                $pc_obj->setDateStamp($date_stamp);
                                $pc_obj->setEnableCalcUserDateTotal(true);
                                $pc_obj->setEnableCalcTotalTime(true); //This is required to make sure Start/End timestamps are populated. This help fix strange bugs with OT being calculated incorrectly due to missing timestamps.
                                $pc_obj->Save();
                            } else {
                                Debug::Text(' NOT Saving Punch Control ID, as DateStamp didnt change: ' . $punch_control_id, __FILE__, __LINE__, __METHOD__, 10);
                            }
                        }
                    }
                    unset($pclf, $pc_obj);
                    //Debug::Arr($this->old_date_stamps, 'aOld User Date IDs: ', __FILE__, __LINE__, __METHOD__, 10);

                    //Handle cases where shift times change enough to cause shifts spanning midnight to be reassigned to different days.
                    //For example the punches may look like this:
                    // Nov 12th 1:00PM
                    // Nov 12th 11:30PM
                    // Nov 13th 12:30AM
                    // Nov 13th 2:00AM
                    //Then the Nov12th 11:30PM punch is modified to be say 2PM, the Nov 13th 12:30AM punch should then be moved to 13th rather than combined with the 12th.
                    if (count($processed_punch_control_ids) > 0) {
                        $plf = TTNew('PunchListFactory');
                        $plf->getByUserIdAndDateStampAndNotPunchControlId($this->getUser(), $date_stamp, $processed_punch_control_ids);
                        if ($plf->getRecordCount() > 0) {
                            foreach ($plf as $p_obj) {
                                if (!in_array($p_obj->getPunchControlID(), $processed_punch_control_ids)) {
                                    Debug::Text('aPunches from other shifts exist on this day still... Punch ID: ' . $p_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);

                                    $src_punch_control_obj = $p_obj->getPunchControlObject();
                                    $src_punch_control_obj->setPunchObject($p_obj);
                                    if ($src_punch_control_obj->isValid() == true) {
                                        //We need to calculate new total time for the day and exceptions because we are never guaranteed that the gaps will be filled immediately after
                                        //in the case of a drag & drop or something.
                                        $src_punch_control_obj->setEnableStrictJobValidation(true);
                                        $src_punch_control_obj->setEnableCalcUserDateID(false);
                                        $src_punch_control_obj->setEnableCalcTotalTime(true);
                                        $src_punch_control_obj->setEnableCalcSystemTotalTime(true);
                                        $src_punch_control_obj->setEnableCalcWeeklySystemTotalTime(true);
                                        $src_punch_control_obj->setEnableCalcUserDateTotal(true);
                                        $src_punch_control_obj->setEnableCalcException(true);
                                        if ($src_punch_control_obj->isValid() == true) {
                                            $src_punch_control_obj->Save();
                                            $processed_punch_control_ids[] = $src_punch_control_obj->getID();
                                        }
                                    }
                                }
                            }
                        }
                        unset($plf, $src_punch_control_obj, $p_obj);
                    }

                    Debug::Text('Returning TRUE', __FILE__, __LINE__, __METHOD__, 10);
                    return true;
                } else {
                    Debug::Text('Punches are not paired, not re-arranging days...', __FILE__, __LINE__, __METHOD__, 10);
                }
            } else {
                Debug::Text('No shift data, check for other punches on the same day in case they need to be moved back...', __FILE__, __LINE__, __METHOD__, 10);

                //Handle cases where a punch pair was moved from one day to this day, then the punches that caused that were deleted, and now
                //it needs to be moved back to the original day.
                $plf = TTNew('PunchListFactory');
                $plf->getByUserIdAndDateStamp($this->getUser(), $date_stamp);
                if ($plf->getRecordCount() > 0) {
                    foreach ($plf as $p_obj) {
                        Debug::Text('bPunches from other shifts exist on this day still... Punch ID: ' . $p_obj->getID(), __FILE__, __LINE__, __METHOD__, 10);

                        $src_punch_control_obj = $p_obj->getPunchControlObject();
                        $src_punch_control_obj->setPunchObject($p_obj);
                        if ($src_punch_control_obj->isValid() == true) {
                            //We need to calculate new total time for the day and exceptions because we are never guaranteed that the gaps will be filled immediately after
                            //in the case of a drag & drop or something.
                            $src_punch_control_obj->setEnableStrictJobValidation(true);
                            $src_punch_control_obj->setEnableCalcUserDateID(false);
                            $src_punch_control_obj->setEnableCalcTotalTime(true);
                            $src_punch_control_obj->setEnableCalcSystemTotalTime(true);
                            $src_punch_control_obj->setEnableCalcWeeklySystemTotalTime(true);
                            $src_punch_control_obj->setEnableCalcUserDateTotal(true);
                            $src_punch_control_obj->setEnableCalcException(true);
                            if ($src_punch_control_obj->isValid() == true) {
                                $src_punch_control_obj->Save();
                                $processed_punch_control_ids[] = $src_punch_control_obj->getID();
                            }
                        }
                    }
                }
                unset($plf, $src_punch_control_obj, $p_obj);

                return true;
            }
        }

        Debug::Text('Returning FALSE', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function getEnableCalcUserDateID()
    {
        if (isset($this->calc_user_date_id)) {
            return $this->calc_user_date_id;
        }

        return false;
    }

    public function calcUserDateTotal()
    {
        if ($this->getEnableCalcUserDateTotal() == true) {
            Debug::Text(' Calculating User Date Total...', __FILE__, __LINE__, __METHOD__, 10);

            $udtlf = TTnew('UserDateTotalListFactory');
            //Always include OldDateStamp, as punches can move between days (timezone differences), and we need to always update proper records based on punch_control_id.
            $udtlf->getByUserIdAndDateStampAndOldDateStampAndPunchControlId($this->getUser(), $this->getDateStamp(), $this->getOldDateStamp(), $this->getId());
            Debug::text(' Checking for Conflicting User Date Total Records, count: ' . $udtlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
            if ($this->getDeleted() == true) {
                //Add a row to the user date total table, as "worked" hours.
                //Edit if it already exists and is not set as override.
                if ($udtlf->getRecordCount() > 0) {
                    Debug::text(' Found Conflicting User Date Total Records, removing them before re-calc', __FILE__, __LINE__, __METHOD__, 10);
                    foreach ($udtlf as $udt_obj) {
                        if ($udt_obj->getOverride() == false) {
                            Debug::text(' bFound Conflicting User Date Total Records, removing them before re-calc', __FILE__, __LINE__, __METHOD__, 10);
                            $udt_obj->Delete();
                        }
                    }
                }
            } else {
                if ($udtlf->getRecordCount() > 0) {
                    //Delete all but the first row, in case there happens to be multiple rows with the same punch_control_id?
                    $found_first_record = false;
                    foreach ($udtlf as $udt_obj) {
                        //Only keep the first record for the current date stamp. Delete all other records, or records on other dates.
                        //This is required due to getting records from OldDateStamp, as commented on above.
                        if ($found_first_record == false
                            and TTDate::getMiddleDayEpoch($udt_obj->getDateStamp()) == TTDate::getMiddleDayEpoch($this->getDateStamp())
                        ) {
                            $udtf = $udt_obj;
                            $found_first_record = true;
                            continue;
                        }

                        if ($udt_obj->getOverride() == false) {
                            Debug::text(' bFound Conflicting User Date Total Records, removing them before re-calc: Date: ' . TTDate::getDate('DATE', $udt_obj->getDateStamp()), __FILE__, __LINE__, __METHOD__, 10);
                            $udt_obj->Delete();
                        } else {
                            Debug::text(' Found overridden User Date Total Records, not removing...', __FILE__, __LINE__, __METHOD__, 10);
                        }
                    }
                }

                if (!isset($udtf)) {
                    Debug::text(' No Conflicting User Date Total Records, inserting the first one.', __FILE__, __LINE__, __METHOD__, 10);
                    $udtf = TTnew('UserDateTotalFactory');
                } else {
                    Debug::text(' Updating UserDateTotal row ID: ' . (int)$udtf->getId(), __FILE__, __LINE__, __METHOD__, 10);
                }

                $udtf->setUser($this->getUser());
                $udtf->setDateStamp($this->getDateStamp());
                $udtf->setPunchControlID($this->getId());
                $udtf->setObjectType(10); //Worked

                $udtf->setBranch($this->getBranch());
                $udtf->setDepartment($this->getDepartment());

                $udtf->setJob($this->getJob());
                $udtf->setJobItem($this->getJobItem());
                $udtf->setQuantity($this->getQuantity());
                $udtf->setBadQuantity($this->getBadQuantity());

                $udtf->setTotalTime($this->getTotalTime());
                $udtf->setActualTotalTime($this->getActualTotalTime());

                //We always need to make sure both Start/End timestamps are set, we can't necessarily get this
                //from just getPunchObject(), we have to get it from calcTotalTime() instead.
                if (is_object($this->in_punch_obj)) {
                    $udtf->setStartType($this->in_punch_obj->getType());
                    $udtf->setStartTimeStamp($this->in_punch_obj->getTimeStamp());
                } else {
                    Debug::text('No IN PunchObject!', __FILE__, __LINE__, __METHOD__, 10);
                    if (is_object($this->getPunchObject()) and $this->getPunchObject()->getStatus() == 10) {
                        Debug::text('  Using passed PunchObject instead... Deleted: ' . $this->getPunchObject()->getDeleted(), __FILE__, __LINE__, __METHOD__, 10);
                        //Make sure when deleting a punch we clear out the timestamp from the UDT record.
                        if ($this->getPunchObject()->getDeleted() == true) {
                            $udtf->setStartType(null);
                            $udtf->setStartTimeStamp(null);
                        } else {
                            $udtf->setStartType($this->getPunchObject()->getType());
                            $udtf->setStartTimeStamp($this->getPunchObject()->getTimeStamp());
                        }
                    } else {
                        Debug::text('  ERROR: No PunchObject!', __FILE__, __LINE__, __METHOD__, 10);
                    }
                }
                if (is_object($this->out_punch_obj)) {
                    $udtf->setEndType($this->out_punch_obj->getType());
                    $udtf->setEndTimeStamp($this->out_punch_obj->getTimeStamp());
                } else {
                    Debug::text('No OUT PunchObject!', __FILE__, __LINE__, __METHOD__, 10);
                    if (is_object($this->getPunchObject()) and $this->getPunchObject()->getStatus() == 20) {
                        Debug::text('  Using passed PunchObject instead... Deleted: ' . $this->getPunchObject()->getDeleted(), __FILE__, __LINE__, __METHOD__, 10);
                        //Make sure when deleting a punch we clear out the timestamp from the UDT record.
                        if ($this->getPunchObject()->getDeleted() == true) {
                            $udtf->setEndType(null);
                            $udtf->setEndTimeStamp(null);
                        } else {
                            $udtf->setEndType($this->getPunchObject()->getType());
                            $udtf->setEndTimeStamp($this->getPunchObject()->getTimeStamp());
                        }
                    } else {
                        Debug::text('  ERROR: No PunchObject!', __FILE__, __LINE__, __METHOD__, 10);
                    }
                }

                //Let smartReCalculate handle calculating totals/exceptions.
                if ($udtf->isValid()) {
                    return $udtf->Save();
                } else {
                    Debug::text('ERROR: Validation error saving UDT row!', __FILE__, __LINE__, __METHOD__, 10);
                }
            }
        }

        return false;
    }

    public function getEnableCalcUserDateTotal()
    {
        if (isset($this->calc_user_date_total)) {
            return $this->calc_user_date_total;
        }

        return false;
    }

    public function getTotalTime()
    {
        if (isset($this->data['total_time'])) {
            return (int)$this->data['total_time'];
        }
        return false;
    }

    public function getActualTotalTime()
    {
        if (isset($this->data['actual_total_time'])) {
            return (int)$this->data['actual_total_time'];
        }
        return false;
    }

    //This function handles when th UI wants to drag and drop punches around the time sheet.
    //$action = 0 (Copy), 1 (Move)
    //$position = -1 (Before), 0 (Overwrite), 1 (After)
    //$dst_status_id = 10 (In), 20 (Out), this is the status of the row the punch is being dragged too, or the resulting status_id in *most* (not all) cases.
    //					It is really only needed when using the overwrite position setting, and dragging a punch to a blank cell. Other than that it can be left NULL.

    public function getEnableCalcSystemTotalTime()
    {
        if (isset($this->calc_system_total_time)) {
            return $this->calc_system_total_time;
        }

        return false;
    }

    //When passed a punch_control_id, if it has two punches assigned to it, a new punch_control_id row is created and the punches are split between the two.

    public function getEnableCalcException()
    {
        if (isset($this->calc_exception)) {
            return $this->calc_exception;
        }

        return false;
    }

    public function getEnablePreMatureException()
    {
        if (isset($this->premature_exception)) {
            return $this->premature_exception;
        }

        return false;
    }

    public function setObjectFromArray($data)
    {
        if (is_array($data)) {
            $variable_function_map = $this->getVariableToFunctionMap();
            foreach ($variable_function_map as $key => $function) {
                if (isset($data[$key])) {
                    $function = 'set' . $function;
                    switch ($key) {
                        //Ignore any user_date_id, as we will figure it out on our own based on the time_stamp and pay period settings ($pcf->setEnableCalcUserDateID(TRUE))
                        //This breaks smartRecalculate() as it doesn't know the previous user_date_id to calculate.	So when shifts are reassigned to new days
                        //the old days are not recalculated properly.
                        //case 'user_date_id':
                        //	break;
                        case 'date_stamp': //HTML5 interface sends punch_date rather than date_stamp when saving a new punch.
                            break;
                        case 'punch_date':
                            $this->setDateStamp($data[$key]);
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $this->$function($data[$key]);
                            }
                            break;
                    }
                }
            }

            $this->setCreatedAndUpdatedColumns($data);

            return true;
        }

        return false;
    }

    public function getObjectAsArray($include_columns = null, $permission_children_ids = false)
    {
        $data = array();
        $variable_function_map = $this->getVariableToFunctionMap();
        if (is_array($variable_function_map)) {
            foreach ($variable_function_map as $variable => $function_stub) {
                if ($include_columns == null or (isset($include_columns[$variable]) and $include_columns[$variable] == true)) {
                    $function = 'get' . $function_stub;
                    switch ($variable) {
                        case 'total_time':    //Ignore total time, as its calculated later anyways, so if its set here it will cause a validation error.
                            break;
                        default:
                            if (method_exists($this, $function)) {
                                $data[$variable] = $this->$function();
                            }
                            break;
                    }
                }
            }
            $this->getPermissionColumns($data, $this->getColumn('user_id'), $this->getCreatedBy(), $permission_children_ids, $include_columns);
            $this->getCreatedAndUpdatedColumns($data, $include_columns);
        }

        return $data;
    }

    public function addLog($log_action)
    {
        return TTLog::addEntry($this->getId(), $log_action, TTi18n::getText('Punch Control - Employee') . ': ' . UserListFactory::getFullNameById($this->getUser()), null, $this->getTable(), $this);
    }
}
