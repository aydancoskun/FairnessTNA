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
 * @package Modules\Hierarchy
 */
class HierarchyObjectTypeFactory extends Factory {
	protected $table = 'hierarchy_object_type';
	protected $pk_sequence_name = 'hierarchy_object_type_id_seq'; //PK Sequence name

	var $hierarchy_control_obj = NULL;

	/**
	 * @param $name
	 * @param null $params
	 * @return array|null
	 */
	function _getFactoryOptions( $name, $params = NULL ) {
		//Attempt to get the edition of the currently logged in users company, so we can better tailor the columns to them.
		$product_edition_id = Misc::getCurrentCompanyProductEdition();

		$retval = NULL;
		switch( $name ) {
			case 'object_type':
				$retval = array(
										//10 => TTi18n::gettext('Recurring Schedule'),
										//20 => TTi18n::gettext('Schedule Amendment'),
										//30 => TTi18n::gettext('Shift Amendment'),
										//40 => TTi18n::gettext('Pay Stub Amendment')
										//50 => TTi18n::gettext('Request'),

										100 => TTi18n::gettext('Permission'),
										80 => TTi18n::gettext('Exception'),
										90 => TTi18n::gettext('TimeSheet'),

										//Add 1000 to request type_id's. Make sure no other objects pass 1000.
										1010 => TTi18n::gettext('Request: Missed Punch'),
										1020 => TTi18n::gettext('Request: Time Adjustment'),
										1030 => TTi18n::gettext('Request: Absence (incl. Vacation)'),
										1040 => TTi18n::gettext('Request: Schedule Adjustment'),
										1100 => TTi18n::gettext('Request: Other'),
									);

				if ( $product_edition_id >= 25 ) {
					$retval[200] = TTi18n::gettext('Expense');
				}

				break;
			case 'short_object_type': //Defines a short form of the names.
				$retval = array(
										//10 => TTi18n::gettext('Recurring Schedule'),
										//20 => TTi18n::gettext('Schedule Amendment'),
										//30 => TTi18n::gettext('Shift Amendment'),
										//40 => TTi18n::gettext('Pay Stub Amendment')
										//50 => TTi18n::gettext('Request'),

										100 => TTi18n::gettext('Permission'),
										80 => TTi18n::gettext('Exception'),
										90 => TTi18n::gettext('TimeSheet'),

										//Add 1000 to request type_id's. Make sure no other objects pass 1000.
										1010 => TTi18n::gettext('R:Missed Punch'),
										1020 => TTi18n::gettext('R:Adjustment'),
										1030 => TTi18n::gettext('R:Absence'),
										1040 => TTi18n::gettext('R:Schedule'),
										1100 => TTi18n::gettext('R:Other'),

										200 => TTi18n::gettext('Expense'),
									);
				break;

		}

		$retval = Misc::addSortPrefix( $retval );

		return $retval;
	}

	/**
	 * @return null
	 */
	function getHierarchyControlObject() {
		if ( is_object($this->hierarchy_control_obj) ) {
			return $this->hierarchy_control_obj;
		} else {
			$hclf = TTnew( 'HierarchyControlListFactory' ); /** @var HierarchyControlListFactory $hclf */
			$this->hierarchy_control_obj = $hclf->getById( $this->getHierarchyControl() )->getCurrent();

			return $this->hierarchy_control_obj;
		}
	}

	/**
	 * @return bool|mixed
	 */
	function getHierarchyControl() {
		return $this->getGenericDataValue( 'hierarchy_control_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setHierarchyControl( $value) {
		$value = TTUUID::castUUID( $value );
		Debug::Text('Hierarchy Control ID: '. $value, __FILE__, __LINE__, __METHOD__, 10);
		return $this->setGenericDataValue( 'hierarchy_control_id', $value );
	}

	/**
	 * @param $object_type
	 * @return bool
	 */
	function isUniqueObjectType( $object_type) {
/*
		$company_id = $this->getHierarchyControlObject()->getCompany();

		$hotlf = TTnew( 'HierarchyObjectTypeListFactory' );
		$hotlf->getByCompanyId( $company_id );
		foreach ( $hotlf as $object_type_obj) {
			if ( $object_type_obj->getId() !== $this->getId() ) {
				$assigned_object_types[] = $object_type_obj->getObjectType();
			}
		}

		if ( isset($assigned_object_types) AND is_array($assigned_object_types) AND in_array( $object_type, $assigned_object_types) ) {
			return FALSE;
		}
*/
		return TRUE;
	}

	/**
	 * @return int
	 */
	function getObjectType() {
		return $this->getGenericDataValue( 'object_type_id' );
	}

	/**
	 * @param string $value UUID
	 * @return bool
	 */
	function setObjectType( $value) {
		$value = (int)trim($value);
		return $this->setGenericDataValue( 'object_type_id', $value );
	}
	/**
	 * @return bool
	 */
	function Validate() {
		//
		// BELOW: Validation code moved from set*() functions.
		//
		// Hierarchy Control
		if ( $this->getHierarchyControl() == TTUUID::getZeroID() ) {
			$hclf = TTnew( 'HierarchyControlListFactory' ); /** @var HierarchyControlListFactory $hclf */
			$this->Validator->isResultSetWithRows(	'hierarchy_control_id',
															$hclf->getByID($this->getHierarchyControl()),
															TTi18n::gettext('Invalid Hierarchy Control')
														);
		}
		// Object Type
		$this->Validator->inArrayKey(	'object_type',
												$this->getObjectType(),
												TTi18n::gettext('Object Type is invalid'),
												Misc::trimSortPrefix( $this->getOptions('object_type') )
											);
		if ( $this->Validator->isError('object_type') == FALSE ) {
			$this->Validator->isTrue(		'object_type',
													$this->isUniqueObjectType($this->getObjectType()),
													TTi18n::gettext('Object Type is already assigned to another hierarchy')
												);
		}

		//
		// ABOVE: Validation code moved from set*() functions.
		//
		return TRUE;
	}

	/**
	 * @return bool
	 */
	function postSave() {
		$cache_id = $this->getHierarchyControlObject()->getCompany().$this->getObjectType();
		$this->removeCache( $cache_id );

		return TRUE;
	}

	//This table doesn't have any of these columns, so overload the functions.

	/**
	 * @return bool
	 */
	function getDeleted() {
		return FALSE;
	}

	/**
	 * @param $bool
	 * @return bool
	 */
	function setDeleted( $bool) {
		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getCreatedDate() {
		return FALSE;
	}

	/**
	 * @param int $epoch EPOCH
	 * @return bool
	 */
	function setCreatedDate( $epoch = NULL) {
		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getCreatedBy() {
		return FALSE;
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setCreatedBy( $id = NULL) {
		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getUpdatedDate() {
		return FALSE;
	}

	/**
	 * @param int $epoch EPOCH
	 * @return bool
	 */
	function setUpdatedDate( $epoch = NULL) {
		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getUpdatedBy() {
		return FALSE;
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setUpdatedBy( $id = NULL) {
		return FALSE;
	}


	/**
	 * @return bool
	 */
	function getDeletedDate() {
		return FALSE;
	}

	/**
	 * @param int $epoch EPOCH
	 * @return bool
	 */
	function setDeletedDate( $epoch = NULL) {
		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getDeletedBy() {
		return FALSE;
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setDeletedBy( $id = NULL) {
		return FALSE;
	}

	/**
	 * @param $log_action
	 * @return bool
	 */
	function addLog( $log_action ) {
		$object_type = Option::getByKey($this->getObjectType(), Misc::TrimSortPrefix( $this->getOptions('object_type') ) );
		return TTLog::addEntry( $this->getHierarchyControl(), $log_action, TTi18n::getText('Object').': '. $object_type, NULL, $this->getTable() );
	}
}
?>
