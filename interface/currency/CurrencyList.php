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
 * $Revision: 4104 $
 * $Id: CurrencyList.php 4104 2011-01-04 19:04:05Z ipso $
 * $Date: 2011-01-04 11:04:05 -0800 (Tue, 04 Jan 2011) $
 */
require_once('../../includes/global.inc.php');
require_once(Environment::getBasePath() .'includes/Interface.inc.php');

if ( !$permission->Check('currency','enabled')
		OR !( $permission->Check('currency','view') OR $permission->Check('currency','view_own') ) ) {
	$permission->Redirect( FALSE ); //Redirect
}

//Debug::setVerbosity(11);

$smarty->assign('title', TTi18n::gettext($title = 'Currency List') );

/*
 * Get FORM variables
 */
extract	(FormVariables::GetVariables(
										array	(
												'action',
												'page',
												'sort_column',
												'sort_order',
												'ids'
												) ) );

URLBuilder::setURL($_SERVER['SCRIPT_NAME'],
											array(
													'sort_column' => $sort_column,
													'sort_order' => $sort_order,
													'page' => $page
												) );

$sort_array = NULL;
if ( $sort_column != '' ) {
	$sort_array = array($sort_column => $sort_order);
}

Debug::Arr($ids,'Selected Objects', __FILE__, __LINE__, __METHOD__,10);

$action = Misc::findSubmitButton();
switch ($action) {
	case 'update_rates':
		CurrencyFactory::updateCurrencyRates( $current_company->getId() );

		Redirect::Page( URLBuilder::getURL(NULL, 'CurrencyList.php') );
		break;
	case 'add':
		Redirect::Page( URLBuilder::getURL(NULL, 'EditCurrency.php') );
		break;
	case 'delete' OR 'undelete':
		if ( strtolower($action) == 'delete' ) {
			$delete = TRUE;
		} else {
			$delete = FALSE;
		}

		$clf = TTnew( 'CurrencyListFactory' );

		if ( isset($ids) AND is_array($ids) ) {
			foreach ($ids as $id) {
				$clf->getByIdAndCompanyId($id, $current_company->getId() );
				foreach ($clf as $c_obj) {
					$c_obj->setDeleted($delete);
					if ( $c_obj->isValid() ) {
						$c_obj->Save();
					}
				}
			}
		}

		Redirect::Page( URLBuilder::getURL(NULL, 'CurrencyList.php') );

		break;

	default:
		BreadCrumb::setCrumb($title);
		$clf = TTnew( 'CurrencyListFactory' );

		$clf->getByCompanyId($current_company->getId(), $current_user_prefs->getItemsPerPage(),$page, NULL, $sort_array );

		$pager = new Pager($clf);

		$iso_code_options = $clf->getISOCodesArray();

		$base_currency = FALSE;
		foreach ($clf as $c_obj) {
			if ( $c_obj->getBase() === TRUE ) {
				$base_currency = TRUE;
			}
			$rows[] = array(
								'id' => $c_obj->GetId(),
								'status_id' => $c_obj->getStatus(),
								'name' => $c_obj->getName(),
								'iso_code' => $c_obj->getISOCode(),
								'currency_name' => Option::getByKey($c_obj->getISOCode(), $iso_code_options ),
								'conversion_rate' => $c_obj->getConversionRate(),
								'auto_update' => $c_obj->getAutoUpdate(),
								'is_base' => $c_obj->getBase(),
								'is_default' => $c_obj->getDefault(),
								'deleted' => $c_obj->getDeleted()
							);

		}
		$smarty->assign_by_ref('currencies', $rows);
		$smarty->assign_by_ref('base_currency', $base_currency);

		$smarty->assign_by_ref('sort_column', $sort_column );
		$smarty->assign_by_ref('sort_order', $sort_order );

		$smarty->assign_by_ref('paging_data', $pager->getPageVariables() );

		break;
}
$smarty->display('currency/CurrencyList.tpl');
?>