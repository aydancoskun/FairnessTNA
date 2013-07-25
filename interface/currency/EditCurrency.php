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
 * $Id: EditCurrency.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('currency','enabled')
		OR !( $permission->Check('currency','edit') OR $permission->Check('currency','edit_own') ) ) {
	$permission->Redirect( FALSE ); //Redirect
}

$smarty->assign('title', TTi18n::gettext($title = 'Edit Currency')); // See index.php

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'id',
												'data',
												'data_saved',
												) ) );

$cf = TTnew( 'CurrencyFactory' );
$action = Misc::findSubmitButton();
$action = strtolower($action);
switch ($action) {
	case 'submit':
		//Debug::setVerbosity(11);
		Debug::Text('Submit!', __FILE__, __LINE__, __METHOD__,10);

		$cf->setId( $data['id'] );
		$cf->setCompany( $current_company->getId() );
		$cf->setStatus( $data['status'] );
		$cf->setName( $data['name'] );
		$cf->setISOCode( $data['iso_code'] );
		$cf->setConversionRate( $data['conversion_rate'] );
		if ( isset($data['auto_update']) AND $data['auto_update'] == 1) {
			$cf->setAutoUpdate( TRUE );
		} else {
			$cf->setAutoUpdate( FALSE );
		}
		
		if ( isset($data['is_base']) AND $data['is_base'] == 1) {
			$cf->setBase( TRUE );
		} else {
			$cf->setBase( FALSE );
		}

		if ( isset($data['is_default']) AND $data['is_default'] == 1) {
			$cf->setDefault( TRUE );
		} else {
			$cf->setDefault( FALSE );
		}

		$cf->setRateModifyPercent( $data['rate_modify_percent'] );
		
		if ( $cf->isValid() ) {
			$cf->Save();

			//Redirect::Page( URLBuilder::getURL( array('id' => $data['id'], 'data_saved' => TRUE), 'EditCurrency.php') );
			Redirect::Page( URLBuilder::getURL( NULL, 'CurrencyList.php') );

			break;
		}
		
	default:
		if ( isset($id) ) {
			BreadCrumb::setCrumb($title);

			$clf = TTnew( 'CurrencyListFactory' );

			$clf->getByIdAndCompanyId($id, $current_company->getId() );

			foreach ($clf as $c_obj) {
				//Debug::Arr($branch,'branch', __FILE__, __LINE__, __METHOD__,10);

				$data = array(
									'id' => $c_obj->getId(),
									'status' => $c_obj->getStatus(),
									'name' => $c_obj->getName(),
									'iso_code' => $c_obj->getISOCode(),
									'conversion_rate' => $c_obj->getConversionRate(),
									'auto_update' => $c_obj->getAutoUpdate(),
									'rate_modify_percent' =>  $c_obj->getRateModifyPercent(),
									'actual_rate' => (float)$c_obj->getActualRate(),
									'actual_rate_updated_date' => $c_obj->getActualRateUpdatedDate(),
									'is_base' => $c_obj->getBase(),
									'is_default' => $c_obj->getDefault(),
									'created_date' => $c_obj->getCreatedDate(),
									'created_by' => $c_obj->getCreatedBy(),
									'updated_date' => $c_obj->getUpdatedDate(),
									'updated_by' => $c_obj->getUpdatedBy(),
									'deleted_date' => $c_obj->getDeletedDate(),
									'deleted_by' => $c_obj->getDeletedBy()
								);
			}
		} elseif ( $action != 'submit' ) {
			$data = array(
						'conversion_rate' => '1.0000000000',
						'rate_modify_percent' => '1.0000000000',
						);
		}

		//Select box options;
		$data['status_options'] = $cf->getOptions('status');
		$data['iso_code_options'] = $cf->getISOCodesArray();

		$smarty->assign_by_ref('data', $data);
		$smarty->assign_by_ref('data_saved', $data_saved);

		break;
}

$smarty->assign_by_ref('cf', $cf);

$smarty->display('currency/EditCurrency.tpl');
?>