<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright (C) 2003 - 2013 TimeTrex Software Inc.
 * because Fairness is a fork of "TimeTrex Workforce Management" Software.
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
/*
 * $Revision: 9993 $
 * $Id: Punch.php 9993 2013-05-24 20:16:41Z ipso $
 * $Date: 2013-05-24 13:16:41 -0700 (Fri, 24 May 2013) $
 */
require_once('../../includes/global.inc.php');

//Debug::setVerbosity(11);
$skip_message_check = TRUE;
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('punch','enabled')
		OR !( $permission->Check('punch','punch_in_out') ) ) {

	$permission->Redirect( FALSE ); //Redirect

}

$smarty->assign('title', TTi18n::gettext($title = 'Punch In / Out')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'id',
												'data'
												) ) );

if ( isset($data) AND isset($data['time_stamp']) ) {
	$data['punch_full_time_stamp'] = $data['time_stamp'];

	//Make sure employees don't try to circumvent the disabled timestamp field. By allowing a small variance.
	$max_variance = 300; //5minutes.
	if ( $data['punch_full_time_stamp'] > (TTDate::getTime()+$max_variance) OR $data['punch_full_time_stamp'] < (TTDate::getTime()-$max_variance) ) {
		Debug::Text('TimeStamp is outside allowed variance window, resetting to actual time.', __FILE__, __LINE__, __METHOD__,10);
		$data['punch_full_time_stamp'] = TTDate::getTime();
	}
}

$pcf = TTnew( 'PunchControlFactory' );
$pf = TTnew( 'PunchFactory' );

$action = Misc::findSubmitButton();
switch ($action) {
	case 'submit':
		//Debug::setVerbosity(11);
		Debug::Text('Submit!', __FILE__, __LINE__, __METHOD__,10);

		$pf->StartTransaction();

		//Set User before setTimeStamp so rounding can be done properly.
		$pf->setUser( $current_user->getId() );

		if ( isset($data['transfer']) ) {
			$data['type_id'] = 10;
			$data['status_id'] = 10;
			$pf->setTransfer( TRUE, $data['punch_full_time_stamp'] ); //Include timestamp so we can tell if its the first punch or not.
		}

		$pf->setType( $data['type_id'] );
		$pf->setStatus( $data['status_id'] );
		$pf->setTimeStamp( $data['punch_full_time_stamp'] );

		if ( isset($data['status_id']) AND $data['status_id'] == 20 AND isset( $pc_data['punch_control_id'] ) AND $pc_data['punch_control_id']  != '' ) {
			$pf->setPunchControlID( $pc_data['punch_control_id'] );
		} else {
			$pf->setPunchControlID( $pf->findPunchControlID() );
		}

		$pf->setStation( $current_station->getID() );

		if ( $pf->isNew() ) {
			$pf->setActualTimeStamp( $data['punch_full_time_stamp'] );
			//$pf->setOriginalTimeStamp( $pf->getTimeStamp() ); //set in preSave()
		}

		if ( $pf->isValid() == TRUE ) {

			if ( $pf->Save( FALSE ) == TRUE ) {
				$pcf = TTnew( 'PunchControlFactory' );
				$pcf->setId( $pf->getPunchControlID() );
				$pcf->setPunchObject( $pf );

				if ( isset($data['user_date_id']) AND $data['user_date_id'] == '' ) {
					//$pcf->setUserDateID( $data['user_date_id'] );
				}

				if ( isset($data['branch_id']) ) {
					$pcf->setBranch( $data['branch_id'] );
				}
				if ( isset($data['department_id']) ) {
					$pcf->setDepartment( $data['department_id'] );
				}

				if ( isset($data['job_id']) ) {
					$pcf->setJob( $data['job_id'] );
				}
				if ( isset($data['job_item_id']) ) {
					$pcf->setJobItem( $data['job_item_id'] );
				}
				if ( isset($data['quantity']) ) {
					$pcf->setQuantity( $data['quantity'] );
				}
				if ( isset($data['bad_quantity']) ) {
					$pcf->setBadQuantity( $data['bad_quantity'] );
				}
				if ( isset($data['note']) ) {
					$pcf->setNote( $data['note'] );
				}

				if ( isset($data['other_id1']) ) {
					$pcf->setOtherID1( $data['other_id1'] );
				}
				if ( isset($data['other_id2']) ) {
					$pcf->setOtherID2( $data['other_id2'] );
				}
				if ( isset($data['other_id3']) ) {
					$pcf->setOtherID3( $data['other_id3'] );
				}
				if ( isset($data['other_id4']) ) {
					$pcf->setOtherID4( $data['other_id4'] );
				}
				if ( isset($data['other_id5']) ) {
					$pcf->setOtherID5( $data['other_id5'] );
				}

				$pcf->setEnableStrictJobValidation( TRUE );
				$pcf->setEnableCalcUserDateID( TRUE );
				$pcf->setEnableCalcTotalTime( TRUE );
				$pcf->setEnableCalcSystemTotalTime( TRUE );
				$pcf->setEnableCalcUserDateTotal( TRUE );
				$pcf->setEnableCalcException( TRUE );
				$pcf->setEnablePreMatureException( TRUE ); //Enable pre-mature exceptions at this point.

				if ( $pcf->isValid() == TRUE ) {
					Debug::Text(' Punch Control is valid, saving...: ', __FILE__, __LINE__, __METHOD__,10);

					if ( $pcf->Save( TRUE, TRUE ) == TRUE ) { //Force isNew() lookup.
						//$pf->FailTransaction();
						$pf->CommitTransaction();

						Redirect::Page( URLBuilder::getURL( NULL, '../CloseWindow.php') );

						break;
					}
				}
			}
		}

		$pf->FailTransaction();
	default:
		$epoch = TTDate::getTime();

		$slf = TTnew( 'ScheduleListFactory' );

		//Get last punch for this day, for this user.
		$plf = TTnew( 'PunchListFactory' );

		if ( $action != 'submit' ) {
			$data = $plf->getDefaultPunchSettings( $current_user, $epoch, $current_station, $permission );
			$data['time_stamp'] = $data['date_stamp'] = $epoch;
			$data['user_full_name'] = $current_user->getFullName();

			/*
			$plf->getPreviousPunchByUserIDAndEpoch( $current_user->getId(), $epoch );
			if ($plf->getRecordCount() > 0 ) {
				$prev_punch_obj = $plf->getCurrent();
				$prev_punch_obj->setUser( $current_user->getId() );
				Debug::Text(' Found Previous Punch within Continuous Time from now: '. TTDate::getDate('DATe+TIME', $prev_punch_obj->getTimeStamp() ), __FILE__, __LINE__, __METHOD__,10);

				//Due to split shifts or multiple schedules on a single day that are close to one another, we have to be smarter about how we default punch settings.
				//We only base default punch settings on the previous punch if it was a Normal IN punch, or a Break/Lunch OUT punch, with the idea that the employee
				//would likely want to continue working on the same job after they come back from lunch/break, or if they haven't punched out for the end of this shift yet.
				if ( ( is_object( $prev_punch_obj ) AND ( ( $prev_punch_obj->getStatus() == 10 AND $prev_punch_obj->getType() == 10 ) OR ( $prev_punch_obj->getStatus() == 20 AND $prev_punch_obj->getType() > 10 ) ) ) ) {
					$branch_id = $prev_punch_obj->getPunchControlObject()->getBranch();
					$department_id = $prev_punch_obj->getPunchControlObject()->getDepartment();
					$job_id = $prev_punch_obj->getPunchControlObject()->getJob();
					$job_item_id = $prev_punch_obj->getPunchControlObject()->getJobItem();
				} else {
					$branch_id = $department_id = $job_id = $job_item_id = FALSE;
				}

				//Don't enable transfer by default if the previous punch was any OUT punch.
				//Transfer does the OUT punch for them, so if the previous punch is an OUT punch
				//we don't gain anything anyways.
				if ( $permission->Check('punch','default_transfer') AND $prev_punch_obj->getStatus() == 10 ) {
					$transfer = TRUE;
				} else {
					$transfer = FALSE;
				}

				if ( $branch_id == '' OR empty($branch_id)
						OR $department_id == '' OR empty($department_id)
						OR $job_id == '' OR empty($job_id)
						OR $job_item_id == '' OR empty($job_item_id) ) {
					Debug::Text(' Branch or department are null. ', __FILE__, __LINE__, __METHOD__,10);

					$s_obj = $slf->getScheduleObjectByUserIdAndEpoch( $current_user->getId(), $epoch );

					if ( is_object($s_obj) ) {
						Debug::Text(' Found Schedule!: ', __FILE__, __LINE__, __METHOD__,10);

						if ( $branch_id == '' OR empty($branch_id) ) {
							Debug::Text(' overrriding branch: '. $s_obj->getBranch(), __FILE__, __LINE__, __METHOD__,10);
							$branch_id = $s_obj->getBranch();
						}
						if ( $department_id == '' OR empty($department_id) ) {
							Debug::Text(' overrriding department: '. $s_obj->getDepartment(), __FILE__, __LINE__, __METHOD__,10);
							$department_id = $s_obj->getDepartment();
						}

						if ( $job_id == '' OR empty($job_id) ) {
							Debug::Text(' overrriding job: '. $s_obj->getJob(), __FILE__, __LINE__, __METHOD__,10);
							$job_id = $s_obj->getJob();
						}
						if ( $job_item_id == '' OR empty($job_item_id) ) {
							Debug::Text(' overrriding job item: '. $s_obj->getJobItem(), __FILE__, __LINE__, __METHOD__,10);
							$job_item_id = $s_obj->getJobItem();
						}

					}
				}

				$next_type = $prev_punch_obj->getNextType( $epoch ); //Detects breaks/lunches too.

				if ( $prev_punch_obj->getNextStatus() == 10 ) {
					//In punch - Carry over just certain data
					$data = array(
									'user_id' => $current_user->getId(),
									'user_full_name' => $current_user->getFullName(),
									'time_stamp' => $epoch,
									'date_stamp' => $epoch,
									'transfer' => $transfer,
									'branch_id' => $branch_id,
									'department_id' => $department_id,
									'job_id' => $job_id,
									'job_item_id' => $job_item_id,
									'quantity' => 0,
									'bad_quantity' => 0,
									'status_id' => $prev_punch_obj->getNextStatus(),
									'type_id' => $next_type,
									'punch_control_id' => $prev_punch_obj->getNextPunchControlID(),
									//'user_date_id' => $prev_punch_obj->getPunchControlObject()->getUserDateID()
									);
				} else {
					//Out punch
					$data = array(
									'user_id' => $current_user->getId(),
									'user_full_name' => $current_user->getFullName(),
									'time_stamp' => $epoch,
									'date_stamp' => $epoch,
									'transfer' => $transfer,
									'branch_id' => $branch_id,
									'department_id' => $department_id,
									'job_id' => $job_id,
									'job_item_id' => $job_item_id,
									'quantity' => (float)$prev_punch_obj->getPunchControlObject()->getQuantity(),
									'bad_quantity' => (float)$prev_punch_obj->getPunchControlObject()->getBadQuantity(),
									'note' => $prev_punch_obj->getPunchControlObject()->getNote(),
									'other_id1' => $prev_punch_obj->getPunchControlObject()->getOtherID1(),
									'other_id2' => $prev_punch_obj->getPunchControlObject()->getOtherID2(),
									'other_id3' => $prev_punch_obj->getPunchControlObject()->getOtherID3(),
									'other_id4' => $prev_punch_obj->getPunchControlObject()->getOtherID4(),
									'other_id5' => $prev_punch_obj->getPunchControlObject()->getOtherID5(),
									'status_id' => $prev_punch_obj->getNextStatus(),
									'type_id' => $next_type,
									'punch_control_id' => $prev_punch_obj->getNextPunchControlID(),
									//'user_date_id' => $prev_punch_obj->getPunchControlObject()->getUserDateID()
									);

				}
			} else {
				Debug::Text(' DID NOT Find Previous Punch within Continuous Time from now: ', __FILE__, __LINE__, __METHOD__,10);
				$branch_id = NULL;
				$department_id = NULL;
				$job_id = NULL;
				$job_item_id = NULL;

				$s_obj = $slf->getScheduleObjectByUserIdAndEpoch( $current_user->getId(), $epoch );
				if ( is_object($current_station) AND $current_station->getDefaultBranch() !== FALSE AND $current_station->getDefaultBranch() != 0 ) {
					$branch_id = $current_station->getDefaultBranch();
				} elseif ( is_object($s_obj) AND $s_obj->getBranch() != 0 ) {
					$branch_id = $s_obj->getBranch();
				} elseif ( $current_user->getDefaultBranch() != 0 ) {
					$branch_id = $current_user->getDefaultBranch();
				}

				if ( is_object($current_station) AND $current_station->getDefaultDepartment() !== FALSE AND $current_station->getDefaultDepartment() != 0 ) {
					$department_id = $current_station->getDefaultDepartment();
				} elseif ( is_object($s_obj) AND $s_obj->getDepartment() != 0 ) {
					$department_id = $s_obj->getDepartment();
				} elseif ( $current_user->getDefaultDepartment() != 0 ) {
					$department_id = $current_user->getDefaultDepartment();
				}

				if ( is_object($current_station) AND $current_station->getDefaultJob() !== FALSE AND $current_station->getDefaultJob() != 0 ) {
					$job_id = $current_station->getDefaultJob();
				} elseif ( is_object($s_obj) AND $s_obj->getJob() != 0 ) {
					$job_id = $s_obj->getJob();
				}

				if ( is_object($current_station) AND $current_station->getDefaultJobItem() !== FALSE AND $current_station->getDefaultJobItem() != 0 ) {
					$job_item_id = $current_station->getDefaultJobItem();
				} elseif ( is_object($s_obj) AND $s_obj->getJobItem() != 0 ) {
					$job_item_id = $s_obj->getJobItem();
				}

				$data = array(
								'user_id' => $current_user->getId(),
								'user_full_name' => $current_user->getFullName(),
								'time_stamp' => $epoch,
								'date_stamp' => $epoch,
								'branch_id' => $branch_id,
								'department_id' => $department_id,
								'job_id' => $job_id,
								'job_item_id' => $job_item_id,
								'quantity' => 0,
								'bad_quantity' => 0,
								'status_id' => 10, //In
								'type_id' => 10, //Normal
								);
			}
			*/
		} else {
			$data['user_id'] = $current_user->getId();
			$data['user_full_name'] = $current_user->getFullName();
			$data['time_stamp'] = $epoch;
			$data['date_stamp'] = $epoch;
		}

		$blf = TTnew( 'BranchListFactory' );
		$blf->getByCompanyId( $current_company->getId() );
		$branch_options = $blf->getArrayByListFactory( $blf, TRUE, FALSE );
		//$branch_options = $blf->getByCompanyIdArray( $current_company->getId() );

		$dlf = TTnew( 'DepartmentListFactory' );
		$dlf->getByCompanyId( $current_company->getId() );
		$department_options = $dlf->getArrayByListFactory( $dlf, TRUE, FALSE);
		//$department_options = $dlf->getByCompanyIdArray( $current_company->getId() );

		/* aydancoskun
			if ( $current_company->getProductEdition() >= 20 ) {
			$jlf = TTnew( 'JobListFactory' );
			$jlf->getByCompanyIdAndUserIdAndStatus( $current_company->getId(), $current_user->getId(), array(10) );
			$data['job_options'] = $jlf->getArrayByListFactory( $jlf, TRUE, TRUE );
			$data['job_manual_id_options'] = $jlf->getManualIDArrayByListFactory($jlf, TRUE);

			$jilf = TTnew( 'JobItemListFactory' );
			$jilf->getByCompanyIdAndStatus( $current_company->getId(), 10 );
			$data['job_item_options'] = $jilf->getArrayByListFactory( $jilf, TRUE, FALSE );
			$data['job_item_manual_id_options'] = $jilf->getManualIdArrayByListFactory( $jilf, FALSE );
		}
		*/
		//Select box options;
		$data['status_options'] = $pf->getOptions('status');
		$data['type_options'] = $pf->getOptions('type');
		$data['branch_options'] = $branch_options;
		$data['department_options'] = $department_options;

		//Get other field names
		$oflf = TTnew( 'OtherFieldListFactory' );
		$data['other_field_names'] = $oflf->getByCompanyIdAndTypeIdArray( $current_company->getId(), 15 );

		//Make sure current station is allowed.
		if ( isset( $current_station ) AND is_object( $current_station ) ) {
			if ( isset($_GET['ibutton']) ) {
				$station_is_allowed = $current_station->checkAllowed( NULL, NULL, 'iBUTTON' );
			} else {
				//$station_is_allowed = $current_station->checkAllowed();
				$station_is_allowed = $current_station->checkAllowed( NULL, NULL, ( Misc::detectMobileBrowser() == FALSE ) ? 10 : 26 );
			}
		} else {
			Debug::Text('No Station Found!', __FILE__, __LINE__, __METHOD__,10);
			$station_is_allowed = FALSE; //No station present.
		}
		//var_dump($pc_data);

		$smarty->assign_by_ref('data', $data);
		$smarty->assign_by_ref('station_is_allowed', $station_is_allowed);

		break;
}

$smarty->assign_by_ref('pcf', $pcf);
$smarty->assign_by_ref('pf', $pf);

$smarty->display('punch/Punch.tpl');
?>
