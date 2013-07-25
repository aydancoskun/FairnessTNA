<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coscun (aydan.ayfer.coskun@gmail.com)
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
 * $Revision: 4104 $
 * $Id: EditMealPolicy.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('meal_policy','enabled')
		OR !( $permission->Check('meal_policy','edit') OR $permission->Check('meal_policy','edit_own') ) ) {

	$permission->Redirect( FALSE ); //Redirect

}

$smarty->assign('title', TTi18n::gettext($title = 'Edit Meal Policy')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'id',
												'data'
												) ) );

if ( isset($data['trigger_time'] ) ) {
	$data['trigger_time'] = TTDate::parseTimeUnit($data['trigger_time']);
	$data['amount'] = TTDate::parseTimeUnit($data['amount']);
	$data['start_window'] = TTDate::parseTimeUnit($data['start_window']);
	$data['window_length'] = TTDate::parseTimeUnit($data['window_length']);
	$data['minimum_punch_time'] = TTDate::parseTimeUnit($data['minimum_punch_time']);
	$data['maximum_punch_time'] = TTDate::parseTimeUnit($data['maximum_punch_time']);
}

$mpf = TTnew( 'MealPolicyFactory' );

$action = Misc::findSubmitButton();
$action = strtolower($action);
switch ($action) {
	case 'submit':
		Debug::Text('Submit!', __FILE__, __LINE__, __METHOD__,10);

		$mpf->setId( $data['id'] );
		$mpf->setCompany( $current_company->getId() );
		$mpf->setName( $data['name'] );
		$mpf->setType( $data['type_id'] );
		$mpf->setTriggerTime( $data['trigger_time'] );
		$mpf->setAmount( $data['amount'] );

		$mpf->setAutoDetectType( $data['auto_detect_type_id'] );
		$mpf->setStartWindow( $data['start_window'] );
		$mpf->setWindowLength( $data['window_length'] );
		$mpf->setMinimumPunchTime( $data['minimum_punch_time'] );
		$mpf->setMaximumPunchTime( $data['maximum_punch_time'] );

		if ( isset($data['include_lunch_punch_time']) ) {
			$mpf->setIncludeLunchPunchTime( TRUE );
		} else {
			$mpf->setIncludeLunchPunchTime( FALSE );
		}

		if ( $mpf->isValid() ) {
			$mpf->Save();

			Redirect::Page( URLBuilder::getURL( NULL, 'MealPolicyList.php') );

			break;
		}

	default:
		if ( isset($id) ) {
			BreadCrumb::setCrumb($title);

			$mplf = TTnew( 'MealPolicyListFactory' );
			$mplf->getByIdAndCompanyID( $id, $current_company->getID() );

			foreach ($mplf as $mp_obj) {
				//Debug::Arr($station,'Department', __FILE__, __LINE__, __METHOD__,10);

				$data = array(
									'id' => $mp_obj->getId(),
									'name' => $mp_obj->getName(),
									'type_id' => $mp_obj->getType(),
									'trigger_time' => $mp_obj->getTriggerTime(),
									'amount' => $mp_obj->getAmount(),
									'auto_detect_type_id' => $mp_obj->getAutoDetectType(),
									'start_window' => $mp_obj->getStartWindow(),
									'window_length' => $mp_obj->getWindowLength(),
									'minimum_punch_time' => $mp_obj->getMinimumPunchTime(),
									'maximum_punch_time' => $mp_obj->getMaximumPunchTime(),
									'include_lunch_punch_time' => $mp_obj->getIncludeLunchPunchTime(),
									'created_date' => $mp_obj->getCreatedDate(),
									'created_by' => $mp_obj->getCreatedBy(),
									'updated_date' => $mp_obj->getUpdatedDate(),
									'updated_by' => $mp_obj->getUpdatedBy(),
									'deleted_date' => $mp_obj->getDeletedDate(),
									'deleted_by' => $mp_obj->getDeletedBy()
								);
			}
		} elseif ( $action != 'submit' ) {
			$data = array(
						'trigger_time' => 3600 * 5,
						'amount' => 3600,
						'auto_detect_type_id' => 10,
						'start_window' => 3600*4,
						'window_length' => 3600*2,
						'minimum_punch_time' => 60*30,
						'maximum_punch_time' => 60*60,
						);
		}

		//Select box options;
		$data['type_options'] = $mpf->getOptions('type');
		$data['auto_detect_type_options'] = $mpf->getOptions('auto_detect_type');

		$smarty->assign_by_ref('data', $data);

		break;
}

$smarty->assign_by_ref('mpf', $mpf);

$smarty->display('policy/EditMealPolicy.tpl');
?>