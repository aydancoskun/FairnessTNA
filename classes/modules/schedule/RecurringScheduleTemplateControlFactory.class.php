<?php
/*********************************************************************************
 * FairnessTNA is a Workforce Management program forked from TimeTrex in 2013,
 * copyright Aydan Coskun. Original code base is copyright TimeTrex Software Inc.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * You can contact Aydan Coskun via issue tracker on github.com/aydancoskun
 ********************************************************************************/


/**
 * @package Modules\Schedule
 */
class RecurringScheduleTemplateControlFactory extends Factory {
	protected $table = 'recurring_schedule_template_control';
	protected $pk_sequence_name = 'recurring_schedule_template_control_id_seq'; //PK Sequence name

	protected $company_obj = NULL;

	/**
	 * @param $name
	 * @param null $parent
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $parent = NULL ) {
		$retval = NULL;
		switch( $name ) {
			case 'columns':
				$retval = array(
										'-1030-name' => TTi18n::gettext('Name'),
										'-1040-description' => TTi18n::gettext('Description'),

										'-1900-in_use' => TTi18n::gettext('In Use'),

										'-2000-created_by' => TTi18n::gettext('Created By'),
										'-2010-created_date' => TTi18n::gettext('Created Date'),
										'-2020-updated_by' => TTi18n::gettext('Updated By'),
										'-2030-updated_date' => TTi18n::gettext('Updated Date'),
							);
				break;
			case 'list_columns':
				$retval = Misc::arrayIntersectByKey( $this->getOptions('default_display_columns'), Misc::trimSortPrefix( $this->getOptions('columns') ) );
				break;
			case 'default_display_columns': //Columns that are displayed by default.
				$retval = array(
								'name',
								'description',
								'updated_date',
								'updated_by',
								);
				break;
			case 'unique_columns': //Columns that are unique, and disabled for mass editing.
				$retval = array(
								);
				break;
			case 'linked_columns': //Columns that are linked together, mainly for Mass Edit, if one changes, they all must.
				$retval = array(
								);
				break;
		}

		return $retval;
	}

	/**
	 * @param $data
	 * @return array
	 */
	function _getVariableToFunctionMap( $data ) {
		$variable_function_map = array(
										'id' => 'ID',
										'company_id' => 'Company',
										'name' => 'Name',
										'description' => 'Description',
										'in_use' => FALSE,
										'deleted' => 'Deleted',
										'created_by' => 'CreatedBy', //Needed to change the "owner" of the template for permission purposes.
										);
		return $variable_function_map;
	}

	/**
	 * @return bool
	 */
	function getCompanyObject() {
		return $this->getGenericObject( 'CompanyListFactory', $this->getCompany(), 'company_obj' );
	}

	/**
	 * @return bool|mixed
	 */
	function getCompany() {
		return $this->getGenericDataValue( 'company_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setCompany( $value ) {
		$value = TTUUID::castUUID( $value );
		return $this->setGenericDataValue( 'company_id', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getName() {
		return $this->getGenericDataValue( 'name' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setName( $value ) {
		$value = trim($value);
/*
				AND	$this->Validator->isTrue(	'name',
												$this->isUniqueName($name),
												TTi18n::gettext('Name is already in use')
												)
*/
		return $this->setGenericDataValue( 'name', $value );
	}

	/**
	 * @return bool|mixed
	 */
	function getDescription() {
		return $this->getGenericDataValue( 'description' );
	}

	/**
	 * @param $value
	 * @return bool
	 */
	function setDescription( $value ) {
		$value = trim($value);
		return $this->setGenericDataValue( 'description', $value );
	}
	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Company
		$clf = TTnew( 'CompanyListFactory' ); /** @var CompanyListFactory $clf */
		$this->Validator->isResultSetWithRows(	'company',
														$clf->getByID($this->getCompany()),
														TTi18n::gettext('Company is invalid')
													);
		// Name
		if ( $this->getName() !== FALSE ) {
			$this->Validator->isLength(	'name',
												$this->getName(),
												TTi18n::gettext('Name is invalid'),
												2, 50
											);
		}
		// Description
		$this->Validator->isLength(	'description',
											$this->getDescription(),
											TTi18n::gettext('Description is invalid'),
											0, 255
										);

		//
		// ABOVE: Validation code moved from set*() functions.
		//

		if ( $this->getDeleted() == TRUE ) {
			//Check to make sure nothing else references this, so we can be sure its okay to delete it.
			$rsclf = TTnew('RecurringScheduleControlListFactory'); /** @var RecurringScheduleControlListFactory $rsclf */
			$rsclf->getByCompanyIdAndTemplateID( $this->getCompany(), $this->getId() );
			if ( $rsclf->getRecordCount() > 0 ) {
				$this->Validator->isTRUE( 'in_use',
										  FALSE,
										  TTi18n::gettext( 'This recurring template is currently in use' ) );
			}
		}

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postSave() {
		//
		//**THIS IS DONE IN RecurringScheduleControlFactory, RecurringScheduleTemplateControlFactory, HolidayFactory postSave() as well.
		//

		//Loop through all RecurringScheduleControl rows associated with this template, so we can recalculate the recurring schedules for them.
		$rsclf = TTNew('RecurringScheduleControlListFactory'); /** @var RecurringScheduleControlListFactory $rsclf */
		$rsclf->getByCompanyIdAndTemplateID( $this->getCompany(), $this->getId() );
		if ( $rsclf->getRecordCount() > 0 ) {
			Debug::text('Found RecurringScheduleControl records assigned to this template: '. $rsclf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

			foreach( $rsclf as $rsc_obj ) {
				//Handle generating recurring schedule rows, so they are as real-time as possible.
				$current_epoch = TTDate::getBeginWeekEpoch( TTDate::getBeginWeekEpoch( time() ) - 86400 );

				$rsf = TTnew('RecurringScheduleFactory'); /** @var RecurringScheduleFactory $rsf */
				$rsf->setAMFMessageID( $this->getAMFMessageID() );
				$rsf->StartTransaction();
				$rsf->clearRecurringSchedulesFromRecurringScheduleControl( $rsc_obj->getID(), ( $current_epoch - (86400 * 720) ), ( $current_epoch + (86400 * 720) ) );
				if ( $this->getDeleted() == FALSE ) {
					//FIXME: Put a cap on this perhaps, as 3mths into the future so we don't spend a ton of time doing this
					//if the user puts sets it to display 1-2yrs in the future. Leave creating the rest of the rows to the maintenance job?
					//Since things may change we will want to delete all schedules with each change, but only add back in X weeks at most unless from a maintenance job.
					$maximum_end_date = ( TTDate::getEndWeekEpoch($current_epoch + ( 86400 * 7 ) ) + ( $rsc_obj->getDisplayWeeks() * ( 86400 * 7 ) ) );
					if ( $rsc_obj->getEndDate() != '' AND $maximum_end_date > $rsc_obj->getEndDate() ) {
						$maximum_end_date = $rsc_obj->getEndDate();
					}
					Debug::text('Recurring Schedule ID: '. $rsc_obj->getID() .' Start Date: '. TTDate::getDate('DATE+TIME', $current_epoch ) .' Maximum End Date: '. TTDate::getDate('DATE+TIME', $maximum_end_date ), __FILE__, __LINE__, __METHOD__, 10);

					$rsf->addRecurringSchedulesFromRecurringScheduleControl( $rsc_obj->getCompany(), $rsc_obj->getID(), $current_epoch, $maximum_end_date );
				}
				$rsf->CommitTransaction();
			}
		}

		return TRUE;
	}

	/**
	 * @param $data
	 * @return bool
	 */
	function setObjectFromArray( $data ) {
		if ( is_array( $data ) ) {
			$variable_function_map = $this->getVariableToFunctionMap();
			foreach( $variable_function_map as $key => $function ) {
				if ( isset($data[$key]) ) {

					$function = 'set'.$function;
					switch( $key ) {
						default:
							if ( method_exists( $this, $function ) ) {
								$this->$function( $data[$key] );
							}
							break;
					}
				}
			}

			$this->setCreatedAndUpdatedColumns( $data, $variable_function_map );

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param null $include_columns
	 * @return array
	 */
	function getObjectAsArray( $include_columns = NULL ) {
		$variable_function_map = $this->getVariableToFunctionMap();
		$data = array();
		if ( is_array( $variable_function_map ) ) {
			foreach( $variable_function_map as $variable => $function_stub ) {
				if ( $include_columns == NULL OR ( isset($include_columns[$variable]) AND $include_columns[$variable] == TRUE ) ) {

					$function = 'get'.$function_stub;
					switch( $variable ) {
						case 'in_use':
							$data[$variable] = $this->getColumn( $variable );
							break;
						default:
							if ( method_exists( $this, $function ) ) {
								$data[$variable] = $this->$function();
							}
							break;
					}

				}
			}
			$this->getPermissionColumns( $data, $this->getCreatedBy(), $this->getCreatedBy(), FALSE, $include_columns );
			$this->getCreatedAndUpdatedColumns( $data, $include_columns );
		}

		return $data;
	}

	/**
	 * @param $log_action
	 * @return bool
	 */
	function addLog( $log_action ) {
		return TTLog::addEntry( $this->getId(), $log_action, TTi18n::getText('Recurring Schedule Template').': '. $this->getName(), NULL, $this->getTable(), $this );
	}
}
?>
