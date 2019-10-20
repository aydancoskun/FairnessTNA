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
 * @package Core
 */
class CalculatePayStub extends PayStubFactory {

	var $transaction_date = FALSE;
	var $wage_obj = NULL;
	var $user_obj = NULL;
	var $user_wage_obj = NULL;
	var $pay_period_obj = NULL;
	var $pay_period_schedule_obj = NULL;
	var $payroll_deduction_obj = NULL;
	var $pay_stub_entry_account_link_obj = NULL;
	var $pay_stub_entry_accounts_type_obj = NULL;

	/**
	 * @return mixed
	 */
	function getUser() {
		if ( isset($this->data['user_id']) ) {
			return $this->data['user_id'];
		}

		return FALSE;
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setUser( $id) {
		$id = trim($id);

		$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */

		if ( $id == TTUUID::getZeroID()
				OR $this->Validator->isResultSetWithRows(	'user',
															$ulf->getByID($id),
															TTi18n::gettext('Invalid Employee')
															) ) {
			$this->data['user_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getPayPeriod() {
		if ( isset($this->data['pay_period_id']) ) {
			return $this->data['pay_period_id'];
		}

		return FALSE;
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setPayPeriod( $id) {
		$id = TTUUID::castUUID($id);

		$pplf = TTnew( 'PayPeriodListFactory' ); /** @var PayPeriodListFactory $pplf */

		if (  $this->Validator->isResultSetWithRows(	'pay_period',
														$pplf->getByID($id),
														TTi18n::gettext('Invalid Pay Period')
														) ) {
			$this->data['pay_period_id'] = $id;

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return int
	 */
	function getRun() {
		if ( isset($this->run) ) {
			return $this->run;
		}

		return 1;
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setRun( $id) {
		$this->run = (int)$id;

		return TRUE;
	}

	/**
	 * @return int
	 */
	function getType() {
		if ( isset($this->type_id) ) {
			return $this->type_id;
		}

		return 10; //Periodic
	}

	/**
	 * @param string $id UUID
	 * @return bool
	 */
	function setType( $id) {
		$this->type_id = (int)$id;

		return TRUE;
	}

	/**
	 * @param bool $raw
	 * @return bool
	 */
	function getTransactionDate( $raw = FALSE ) {
		if ( isset($this->transaction_date) ) {
			return $this->transaction_date;
		}

		return FALSE;
	}

	/**
	 * @param int $epoch EPOCH
	 * @return bool
	 */
	function setTransactionDate( $epoch) {
		$epoch = ( !is_int($epoch) ) ? trim($epoch) : $epoch; //Dont trim integer values, as it changes them to strings.

		if ( $epoch != '' ) {
			//Make sure all pay periods transact at noon.
			$epoch = TTDate::getTimeLockedDate( strtotime('12:00:00', $epoch), $epoch);
		}

		if	(	$this->Validator->isDate(		'transaction_date',
												$epoch,
												TTi18n::gettext('Incorrect transaction date'))
			) {

			$this->transaction_date = $epoch;

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @return bool
	 */
	function getEnablePostTerminationCalculation() {
		if ( isset($this->post_termination_calc) ) {
			return $this->post_termination_calc;
		}

		return FALSE;
	}

	/**
	 * @param $bool
	 * @return bool
	 */
	function setEnablePostTerminationCalculation( $bool) {
		$this->post_termination_calc = (bool)$bool;

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function getEnableCorrection() {
		if ( isset($this->correction) ) {
			return $this->correction;
		}

		return FALSE;
	}

	/**
	 * @param $bool
	 * @return bool
	 */
	function setEnableCorrection( $bool) {
		$this->correction = (bool)$bool;

		return TRUE;
	}

	/**
	 * @return bool|null
	 */
	function getUserObject() {
		if ( is_object($this->user_obj) ) {
			return $this->user_obj;
		} else {
			$ulf = TTnew( 'UserListFactory' ); /** @var UserListFactory $ulf */
			$ulf->getById( $this->getUser() );
			if ( $ulf->getRecordCount() > 0 ) {
				$this->user_obj = $ulf->getCurrent();

				return $this->user_obj;
			}

			return FALSE;
		}
	}

	/**
	 * @return bool|null
	 */
	function getPayStubEntryAccountLinkObject() {
		if ( is_object($this->pay_stub_entry_account_link_obj) ) {
			return $this->pay_stub_entry_account_link_obj;
		} else {
			$pseallf = TTnew( 'PayStubEntryAccountLinkListFactory' ); /** @var PayStubEntryAccountLinkListFactory $pseallf */
			$pseallf->getByCompanyId( $this->getUserObject()->getCompany() );
			if ( $pseallf->getRecordCount() > 0 ) {
				$this->pay_stub_entry_account_link_obj = $pseallf->getCurrent();
				return $this->pay_stub_entry_account_link_obj;
			}

			return FALSE;
		}
	}

	/**
	 * @return bool|null
	 */
	function getPayPeriodObject() {
		if ( is_object($this->pay_period_obj) ) {
			return $this->pay_period_obj;
		} else {
			$pplf = TTnew( 'PayPeriodListFactory' ); /** @var PayPeriodListFactory $pplf */
			//$this->pay_period_obj = $pplf->getById( $this->getPayPeriod() )->getCurrent();
			$pplf->getById( $this->getPayPeriod() );
			if ( $pplf->getRecordCount() > 0 ) {
				$this->pay_period_obj = $pplf->getCurrent();

				return $this->pay_period_obj;
			}

			return FALSE;
		}
	}

	/**
	 * @return null
	 */
	function getPayPeriodScheduleObject() {
		if ( is_object($this->pay_period_schedule_obj) ) {
			return $this->pay_period_schedule_obj;
		} else {
			$ppslf = TTnew( 'PayPeriodScheduleListFactory' ); /** @var PayPeriodScheduleListFactory $ppslf */
			$this->pay_period_schedule_obj = $ppslf->getById( $this->getPayPeriodObject()->getPayPeriodSchedule() )->getCurrent();

			return $this->pay_period_schedule_obj;
		}
	}

	/**
	 * @return null|Wage
	 */
	function getWageObject() {
		if ( is_object($this->wage_obj) ) {
			return $this->wage_obj;
		} else {

			$this->wage_obj = new Wage( $this->getUser(), $this->getPayPeriod() );

			return $this->wage_obj;
		}
	}

	/**
	 * @return array|bool|null
	 */
	function getPayStubEntryAccountsTypeArray() {
		if ( is_array($this->pay_stub_entry_accounts_type_obj) ) {
			//Debug::text('Returning Cached data...', __FILE__, __LINE__, __METHOD__, 10);
			return $this->pay_stub_entry_accounts_type_obj;
		} else {
			$psealf = TTnew( 'PayStubEntryAccountListFactory' ); /** @var PayStubEntryAccountListFactory $psealf */
			$this->pay_stub_entry_accounts_type_obj = $psealf->getByTypeArrayByCompanyIdAndStatusId( $this->getUserObject()->getCompany(), 10 );

			if ( is_array( $this->pay_stub_entry_accounts_type_obj ) ) {
				return $this->pay_stub_entry_accounts_type_obj;
			}

			Debug::text('Returning FALSE...', __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}
	}

	/**
	 * @param $type_order
	 * @param $calculation_order
	 * @param string $id UUID
	 * @return int
	 */
	function getDeductionObjectSortValue( $type_order, $calculation_order, $id ) {
		if ( PHP_INT_MAX == 2147483647 ) {
			//32bit, can't handle using $id fields as well as it will exceed the largest int value.
			//This can't exceed 9 digits in total.
			$retval = (int)($type_order . str_pad( $calculation_order, 5, 0, STR_PAD_LEFT) . str_pad( substr( TTUUID::convertUUIDToInt($id), -2), 2, 0, STR_PAD_LEFT));
		} else {
			//This can't exceed 9223372036854775807 (19 digits)
			$retval = (int)($type_order . str_pad( $calculation_order, 5, 0, STR_PAD_LEFT) . str_pad( substr( TTUUID::convertUUIDToInt($id), -10), 10, 0, STR_PAD_LEFT));
		}
		//Debug::text('Type Order: '. $type_order .' Calculation Order: '. $calculation_order .' Deduction Object Sort Value: '. $retval .' INT Max: '. PHP_INT_MAX, __FILE__, __LINE__, __METHOD__, 10);

		return $retval;
	}

	/**
	 * @param object $obj
	 * @return array|bool
	 */
	function getDeductionObjectArrayForSorting( $obj ) {
		$type_map_arr = $this->getPayStubEntryAccountsTypeArray();
		//Debug::Arr($type_map_arr, 'PS Account Type Map Array: ', __FILE__, __LINE__, __METHOD__, 10);

		if ( !is_object($obj) ) {
			Debug::text('ERROR! Object not specified!', __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}

		$arr = array();
		if ( is_a( $obj, 'UserDeductionFactory' ) ) {
			if ( !is_object( $obj->getCompanyDeductionObject() ) ) {
				Debug::text('ERROR! Unable to get CompanyDeduction Object!', __FILE__, __LINE__, __METHOD__, 10);
				return FALSE;
			}

			if ( !is_object( $obj->getCompanyDeductionObject()->getPayStubEntryAccountObject() ) ) {
				Debug::text('Bad PS Entry Account(s) for Company Deduction. Skipping... ID: '. $obj->getCompanyDeductionObject()->getId(), __FILE__, __LINE__, __METHOD__, 10);
				return FALSE;
			}
			//Debug::Arr($obj->getCompanyDeductionObject()->getIncludePayStubEntryAccount(), 'Include Accounts: ', __FILE__, __LINE__, __METHOD__, 10);
			//Debug::Arr($obj->getCompanyDeductionObject()->getExcludePayStubEntryAccount(), 'Exclude Accounts: ', __FILE__, __LINE__, __METHOD__, 10);

			$arr['type'] = get_class( $obj );
			$arr['obj_id'] = $obj->getId();
			$arr['id'] = substr($arr['type'], 0, 1).$obj->getId();
			$arr['name'] = $obj->getCompanyDeductionObject()->getName();
			//Need more than just TypeCalculationOrder to prevent Federal/Prov income tax from being calculated BEFORE CPP/EI.
			//Include the ID at the end of order value so we can keep consistent orders primarily for unit tests or as a tie breaker.
			$arr['order'] = $this->getDeductionObjectSortValue( $obj->getCompanyDeductionObject()->getPayStubEntryAccountObject()->getTypeCalculationOrder(), $obj->getCompanyDeductionObject()->getCalculationOrder(), $obj->getCompanyDeductionObject()->getID() );

			//If we put TypeCalculationOrder at the beginning, it trumps the specific calculation order itself when dealing with calculations
			//that require different types or cirucular depedencies that require/provide different types, (ie: ER-DED that requires earnings, and an earnings that requires ER-Ded, for scratch calculations)
			//So put TypeCalculation order at the end. However this breaks existing tax calculations as it relies too much on the calculation order specified manually.
			//FIXME: Will need to come up with another situation that can trigger this another way. Perhaps
			//		 when calculation orders exceed 5 digits it can squeeze out the TypeCalculationOrder?
			$arr['obj'] = $obj;
			$arr['require_accounts'] = array();

			//Make sure we expand all Total PS accounts to include their individual accounts so we can properly determine the order to calculate in. Especially important for Tax/Deductions that use Net Pay.
			$include_accounts = $obj->getCompanyDeductionObject()->getExpandedPayStubEntryAccountIDs( $obj->getCompanyDeductionObject()->getIncludePayStubEntryAccount(), TRUE );
			if ( !is_array( $include_accounts ) ) {
				$include_accounts = array();
			}
			$include_accounts = array_merge( $include_accounts, $obj->getAdditionalRequiredPayStubAccounts() );

			if ( is_array($include_accounts) ) {
				foreach( $include_accounts as $include_account ) {
					if ( isset($type_map_arr[$include_account]) ) {
						foreach ($type_map_arr[$include_account] as $type_account ) {
							$arr['require_accounts'][] = $type_account;
						}
					} else {
						$arr['require_accounts'][] = $include_account;
					}
				}
			}
			unset($include_accounts, $include_account, $type_account);

			$exclude_accounts = $obj->getCompanyDeductionObject()->getExcludePayStubEntryAccount();
			if ( is_array($exclude_accounts) ) {
				foreach( $exclude_accounts as $exclude_account ) {
					if ( isset($type_map_arr[$exclude_account]) ) {
						foreach ($type_map_arr[$exclude_account] as $type_account ) {
							$arr['require_accounts'][] = $type_account;
						}
					} else {
						$arr['require_accounts'][] = $exclude_account;
					}
				}
			}
			unset($exclude_accounts, $exclude_account, $type_account);

			$arr['affect_accounts'] = $obj->getCompanyDeductionObject()->getPayStubEntryAccount();

			return $arr;
		} elseif ( is_a( $obj, 'PayStubAmendmentFactory' ) ) {
			$arr['type'] = get_class( $obj );
			$arr['obj_id'] = $obj->getId();
			$arr['id'] = substr($arr['type'], 0, 1).$obj->getId();
			$arr['name'] = $obj->getDescription();
			$arr['order'] = $this->getDeductionObjectSortValue( $obj->getPayStubEntryNameObject()->getTypeCalculationOrder(), $obj->getPayStubEntryNameObject()->getOrder(), $obj->getID() );
			$arr['obj'] = $obj;
			$arr['affect_accounts'] = $obj->getPayStubEntryNameId();

			if ( $obj->getType() == 10 ) { //Fixed
				$arr['require_accounts'][] = NULL;
			} else { //Percent
				$arr['require_accounts'][] = $obj->getPercentAmountEntryNameId();
			}

			return $arr;
		} elseif ( is_a( $obj, 'UserExpenseFactory' ) AND is_object( $obj->getExpensePolicyObject() ) AND is_object( $obj->getExpensePolicyObject()->getPayStubEntryNameObject() ) ) {
			$arr['type'] = get_class( $obj );
			$arr['obj_id'] = $obj->getId();
			$arr['id'] = 'E'.$obj->getId();
			$arr['name'] = '';
			$arr['order'] = $this->getDeductionObjectSortValue( $obj->getExpensePolicyObject()->getPayStubEntryNameObject()->getTypeCalculationOrder(), $obj->getExpensePolicyObject()->getPayStubEntryNameObject()->getOrder(), $obj->getID() );
			$arr['obj'] = $obj;
			$arr['affect_accounts'] = $obj->getExpensePolicyObject()->getPayStubEntryAccount();
			$arr['require_accounts'][] = NULL;

			return $arr;
		} else {
			Debug::text('ERROR! Unrecognized object: '. get_class($obj), __FILE__, __LINE__, __METHOD__, 10);
		}

		return FALSE;
	}

	/**
	 * @param $udlf
	 * @param $psalf
	 * @param $uelf
	 * @return array|bool
	 */
	function getOrderedDeductionAndPSAmendment( $udlf, $psalf, $uelf ) {
		global $profiler;

		$dependency_tree = new DependencyTree();

		$deduction_order_arr = array();
		if ( is_object($udlf) ) {
			//Loop over all User Deductions getting Include/Exclude and PS accounts.
			if ( $udlf->getRecordCount() > 0 ) {
				foreach ( $udlf as $ud_obj ) {
					//Debug::text('User Deduction: ID: '. $ud_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
					if ( $ud_obj->getCompanyDeductionObject()->getStatus() == 10 ) {
						$global_id = substr(get_class( $ud_obj ), 0, 1) . $ud_obj->getId();
						$deduction_order_arr[$global_id] = $this->getDeductionObjectArrayForSorting( $ud_obj );

						//Debug::Arr( array($deduction_order_arr[$global_id]['require_accounts'], $deduction_order_arr[$global_id]['affect_accounts']), 'Deduction Name: '. $deduction_order_arr[$global_id]['name'], __FILE__, __LINE__, __METHOD__, 10);

						//If the calculation uses lookback, that utilizes previous pay stubs and should be calculated first so it doesn't
						//contribute to circular dependancies when calculating current pay stub amounts.
						if ( $ud_obj->getCompanyDeductionObject()->isLookbackCalculation() == TRUE ) {
							$dependency_tree->addNode( $global_id, array(), array(), $deduction_order_arr[$global_id]['order'] );
						} else {
							$dependency_tree->addNode( $global_id, $deduction_order_arr[$global_id]['require_accounts'], $deduction_order_arr[$global_id]['affect_accounts'], $deduction_order_arr[$global_id]['order']);
						}
					} else {
						Debug::text('Company Deduction is DISABLED!', __FILE__, __LINE__, __METHOD__, 10);
					}
				}
			}
		}
		unset($udlf, $ud_obj);

		if ( is_object( $psalf) ) {
			if ( $psalf->getRecordCount() > 0 ) {
				foreach ( $psalf as $psa_obj ) {
					//Debug::text('PS Amendment ID: '. $psa_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
					$global_id = substr(get_class( $psa_obj ), 0, 1) . $psa_obj->getId();
					$deduction_order_arr[$global_id] = $this->getDeductionObjectArrayForSorting( $psa_obj );

					$dependency_tree->addNode( $global_id, $deduction_order_arr[$global_id]['require_accounts'], $deduction_order_arr[$global_id]['affect_accounts'], $deduction_order_arr[$global_id]['order']);
				}
			}
		}
		unset($psalf, $psa_obj);

		if ( is_object($uelf) ) {
			if ( $uelf->getRecordCount() > 0 ) {
				foreach ( $uelf as $ue_obj ) {
					//Debug::text('User Expense ID: '. $ue_obj->getId(), __FILE__, __LINE__, __METHOD__, 10);
					$global_id = 'E' . $ue_obj->getId();
					$deduction_order_arr[$global_id] = $this->getDeductionObjectArrayForSorting( $ue_obj );

					$dependency_tree->addNode( $global_id, $deduction_order_arr[$global_id]['require_accounts'], $deduction_order_arr[$global_id]['affect_accounts'], $deduction_order_arr[$global_id]['order']);
				}
			}
		}
		unset($ue_obj);

		$profiler->startTimer( 'Calculate Dependency Tree');
		$sorted_deduction_ids = $dependency_tree->getAllNodesInOrder();
		$profiler->stopTimer( 'Calculate Dependency Tree');

		$retarr = array();
		//Debug::Arr($sorted_deduction_ids , 'Sorted Deduction IDs Array: ', __FILE__, __LINE__, __METHOD__, 10);
		if ( is_array($sorted_deduction_ids) ) {
			foreach( $sorted_deduction_ids as $deduction_id ) {
				$retarr[$deduction_id] = $deduction_order_arr[$deduction_id];
			}
		}

		//Debug::Arr($retarr, 'AFTER - Deduction Order Array: ', __FILE__, __LINE__, __METHOD__, 10);

		if ( empty($retarr) == FALSE ) {
			return $retarr;
		}

		return FALSE;
	}

	/**
	 * @return bool
	 */
	function calculate() {
		if ( $this->getUserObject() == FALSE ) {
			return FALSE;
		}

		if (  $this->getPayPeriodObject() == FALSE ) {
			return FALSE;
		}

		//Use User Termination Date instead of ROE.
		//Also need to handle cases where vacation accrual is being released after they have been terminated.
		//  so when the pay period is after the termination date, only generate pay stubs if Pay Stub Amendments exist.
		if ( $this->getUserObject()->getTerminationDate() != ''
				AND (
						(
							$this->getUserObject()->getTerminationDate() >= $this->getPayPeriodObject()->getStartDate()
							AND
							$this->getUserObject()->getTerminationDate() <= $this->getPayPeriodObject()->getEndDate()
						)
					)
				) {
			Debug::text('User has been terminated in this pay period!', __FILE__, __LINE__, __METHOD__, 10);
			$is_termination_pay_period = TRUE;
		} else {
			$is_termination_pay_period = FALSE;
		}

		//Scenarios to handle:
		// 1. Employees last day for which paid could be Aug 25th (PP ends on Aug 30th), but they never gave any notice and dont work again, so vacation pay must be released after their termination date, but in the following pay period.
		//       this requires: $this->getUserObject()->getTerminationDate() < $this->getPayPeriodObject()->getStartDate()
		if ( ( $this->getEnablePostTerminationCalculation() == TRUE AND $this->getUserObject()->getTerminationDate() < $this->getPayPeriodObject()->getStartDate() ) ) {
			Debug::text('User has been terminated AFTER this pay period... Also setting Out-of-Cycle pay run...', __FILE__, __LINE__, __METHOD__, 10);
			$is_post_termination_pay_period = TRUE;
			$this->setType( 20 ); //When post termination, make sure its a Out-of-Cycle run.
		} else {
			$is_post_termination_pay_period = FALSE;
		}

		//Allow generating pay stubs for employees who have any status, but if its not ID=10
		//Then the termination date must fall within the start/end date of the pay period, or after the end date (if its the current pay period)
		//The idea here is to allow employees to be marked terminated (or on leave) and still get their previous or final pay stub generated.
		//Also allow pay stubs to be generated in pay periods *before* their termination date.
		if ( $this->getUserObject()->getStatus() != 10
				AND ( ( $is_termination_pay_period == FALSE AND $is_post_termination_pay_period == FALSE ) AND
					( $this->getUserObject()->getTerminationDate() == '' OR $this->getUserObject()->getTerminationDate() < $this->getPayPeriodObject()->getStartDate() ) )
			) {
			Debug::text('Pay Period is after users termination date ('. TTDate::getDate('DATE+TIME', $this->getUserObject()->getTerminationDate() ) .'), or no termination date is set...', __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}

		Debug::text('User Id: '. $this->getUser() .' Type: '. $this->getType() .' Run: '. $this->getRun() .' Pay Period End Date: '. TTDate::getDate('DATE+TIME', $this->getPayPeriodObject()->getEndDate() ), __FILE__, __LINE__, __METHOD__, 10);

		$generic_queue_status_label = $this->getUserObject()->getFullName(TRUE).' - '. TTi18n::gettext('Pay Stub');

		$pay_stub = TTnew( 'PayStubFactory' ); /** @var PayStubFactory $pay_stub */
		$pay_stub->StartTransaction();

		$old_pay_stub_id = NULL;
		if ( $this->getEnableCorrection() == TRUE ) {
			Debug::text('Correction Enabled!', __FILE__, __LINE__, __METHOD__, 10);
			$pay_stub->setTemp(TRUE);

			//When generating pay stubs, we always increase the payroll run by 1.
			// However when generating corrections, we can't do that, otherwise the calculations will be incorrect because the "previous" pay stub will the one we are actually trying to compare against.
			// This mostly manifests itself by incorrect calculations when the employee is approaching wage base thresholds on their *next* pay stub, resulting in negative pay stub amendments being created after the comparison.
			if ( $this->getRun() > 1 ) {
				$this->setRun( ( $this->getRun() - 1 ) );
			}

			//Check for current pay stub ID so we can compare against it.
			$pslf = TTnew( 'PayStubListFactory' ); /** @var PayStubListFactory $pslf */
			$pslf->getByUserIdAndPayPeriodId( $this->getUser(), $this->getPayPeriod() );
			if ( $pslf->getRecordCount() > 0 ) {
				$old_pay_stub_id = $pslf->getCurrent()->getId();
				Debug::text('Comparing Against Pay Stub ID: '. $old_pay_stub_id, __FILE__, __LINE__, __METHOD__, 10);
			} else {
				Debug::text('No pay stub to compare against...', __FILE__, __LINE__, __METHOD__, 10);
			}
		}
		$pay_stub->setUser( $this->getUser() );
		$pay_stub->setPayPeriod( $this->getPayPeriod() );
		$pay_stub->setType( $this->getType() );
		$pay_stub->setRun( $this->getRun() );
		$pay_stub->setCurrency( $this->getUserObject()->getCurrency() );
		$pay_stub->setStatus( 10 ); //New

		if ( $is_termination_pay_period == TRUE OR $is_post_termination_pay_period == TRUE ) {
			Debug::text('User is Terminated, assuming final pay, setting End Date to terminated date: '. TTDate::getDate('DATE+TIME', $this->getUserObject()->getTerminationDate() ), __FILE__, __LINE__, __METHOD__, 10);

			$pay_stub->setStartDate( $pay_stub->getPayPeriodObject()->getStartDate() );

			//If the termination date falls within this pay period, use the termination date as the end date.
			//Otherwise use the transaction date as the end date.
			if ( $this->getUserObject()->getTerminationDate() >= $pay_stub->getPayPeriodObject()->getStartDate() AND $this->getUserObject()->getTerminationDate() <= $pay_stub->getPayPeriodObject()->getEndDate() ) {
				$pay_stub->setEndDate( $this->getUserObject()->getTerminationDate() );
			} else {
				if ( $this->getTransactionDate() >= $pay_stub->getPayPeriodObject()->getEndDate() ) {
					$pay_stub->setEndDate( $pay_stub->getPayPeriodObject()->getEndDate() );
				} else {
					$pay_stub->setEndDate( $this->getTransactionDate() );
				}
			}

			//Use the PS generation date instead of terminated date...
			//Unlikely they would pay someone before the pay stub is generated.
			//Perhaps still use the pay period transaction date for this too?
			//Anything we set won't be correct for everyone. Maybe a later date is better though?
			//Perhaps add to the user factory under Termination Date a: "Final Transaction Date" for this purpose?
			//Use the end of the current date for the transaction date, as if the employee is terminated
			//on the same day they are generating the pay stub, the transaction date could be before the end date
			//as the end date is at 11:59PM
			//
			//Since adding multiple payroll runs per pay period, its more critical that the transaction dates are the same so new payroll runs aren't assumed,
			//so when terminating an employee, switch the default transaction date to the current pay period transaction date.
			//  This is also more popular in the US. If in Canada, they can generate an ROE and set the specific transaction date then.
			//  If they want to pay the employee earlier, they can manually modify the date to earlier.

			//For now make sure that the transaction date for a terminated employee is never before their termination date.
			if ( $this->getTransactionDate() != '' ) {
				$pay_stub->setTransactionDate( $this->getTransactionDate() );
			} else {
				$pay_stub->setTransactionDate( $pay_stub->getPayPeriodObject()->getTransactionDate() );
			}
		} else {
			Debug::text('User Termination Date is NOT set, assuming normal pay.', __FILE__, __LINE__, __METHOD__, 10);
			$pay_stub->setDefaultDates();
		}

		if ( $this->getTransactionDate() != FALSE AND $this->getType() != 10 ) {
			Debug::text('Overriding Transaction Date To: '. $this->getTransactionDate(), __FILE__, __LINE__, __METHOD__, 10);

			//If transaction date is earlier than the pay period end date, back date it also, otherwise the transaction date will be moved forward to the end date in PayStubFactory->setTransactionDate().
			//  This allows users to create out-of-cycle pay stubs that are paid before the pay period end date more easily.
			if ( $this->getTransactionDate() < $pay_stub->getEndDate() AND $this->getTransactionDate() >= $pay_stub->getStartDate() ) {
				Debug::text('  Back dating End Date to: '. $this->getTransactionDate(), __FILE__, __LINE__, __METHOD__, 10);
				$pay_stub->setEndDate( $this->getTransactionDate() );
			}
			$pay_stub->setTransactionDate( $this->getTransactionDate() );
		}

		//This must go after setting advance
		if ( $this->getEnableCorrection() == FALSE AND $pay_stub->IsUniquePayStub() == FALSE ) {
			Debug::text('Pay Stub already exists', __FILE__, __LINE__, __METHOD__, 10);
			$this->CommitTransaction();

			UserGenericStatusFactory::queueGenericStatus( $generic_queue_status_label, 20, TTi18n::gettext('Pay Stub for this employee already exists, skipping...'), NULL );

			return FALSE;
		}

		if ( $pay_stub->isValid() == TRUE ) {
			$pay_stub->Save(FALSE);
			$pay_stub->setStatus( 25 ); //Open
		} else {
			Debug::text('Pay Stub isValid failed!', __FILE__, __LINE__, __METHOD__, 10);

			UserGenericStatusFactory::queueGenericStatus( $generic_queue_status_label, 10, $pay_stub->Validator->getTextErrors(), NULL );

			$this->FailTransaction();
			$this->CommitTransaction();
			return FALSE;
		}

		$pay_stub->loadPreviousPayStub();

		//Only calculate TimeSheet data if we are in or before the terminated pay period.
		//If we are after the terminated pay period, only calculate pay stub amendments.
		if ( $this->getType() == 10 AND $is_post_termination_pay_period == FALSE ) {
			$user_date_total_arr = $this->getWageObject()->getUserDateTotalArray();
			if ( isset($user_date_total_arr['entries']) AND is_array( $user_date_total_arr['entries'] ) ) {
				foreach( $user_date_total_arr['entries'] as $udt_arr ) {
					//Allow negative amounts so flat rate premium policies can reduce an employees wage if need be.
					//  Add entries even if amount = 0 if total_time != 0, so Tax/Deduction records that depend on just units can still be calculated.
					if ( $udt_arr['amount'] != 0 OR $udt_arr['total_time'] != 0 ) {
						Debug::text('  Adding Pay Stub Entry: '. $udt_arr['pay_stub_entry'] .' Amount: '. $udt_arr['amount'] .' Total Time: '. $udt_arr['total_time'], __FILE__, __LINE__, __METHOD__, 10);
						$pay_stub->addEntry( $udt_arr['pay_stub_entry'], $udt_arr['amount'], TTDate::getHours( $udt_arr['total_time'] ), $udt_arr['rate'], $udt_arr['description'] );
					} else {
						Debug::text('  NOT Adding ($0 amount) Pay Stub Entry: '. $udt_arr['pay_stub_entry'] .' Amount: '. $udt_arr['amount'], __FILE__, __LINE__, __METHOD__, 10);
					}
				}
			} else {
				//No Earnings, CHECK FOR PS AMENDMENTS next for earnings.
				Debug::text('  NO TimeSheet EARNINGS ON PAY STUB... Checking for PS amendments', __FILE__, __LINE__, __METHOD__, 10);
			}
			unset($user_date_total_arr);
		} else {
			Debug::text('  Not using TimeSheet data as type is: '. $this->getType(), __FILE__, __LINE__, __METHOD__, 10);
		}

		//Get all PS amendments and Tax / Deductions so we can determine the proper order to calculate them in.
		$psalf = TTnew( 'PayStubAmendmentListFactory' ); /** @var PayStubAmendmentListFactory $psalf */
		if ( $this->getEnableCorrection() == TRUE ) { //When doing post-adjustment carry-forward, we need to take into account already paid amendments, otherwise the differeces will always be incorrect.
			$pay_stub_amendment_status_ids = array(50, 52, 55); //50=Active, 52=In Use, 55=Paid
		} else {
			$pay_stub_amendment_status_ids = array(50); //Active
		}
		$psalf->getByUserIdAndAuthorizedAndStatusIDAndStartDateAndEndDate( $this->getUser(), TRUE, $pay_stub_amendment_status_ids, $this->getPayPeriodObject()->getStartDate(), $this->getPayPeriodObject()->getEndDate() );
		unset($pay_stub_amendment_status_ids);

		$udlf = TTnew( 'UserDeductionListFactory' ); /** @var UserDeductionListFactory $udlf */
		$udlf->getByCompanyIdAndUserId( $this->getUserObject()->getCompany(), $this->getUserObject()->getId() );
		Debug::text('Total PayStubAmendments: '. $psalf->getRecordCount() .' UserDeductions: '. $udlf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);

		//Only include expenses when calculating in-cycle payroll runs, as we currently can't tell if they have already been included on a pay stub or not.
		if ( getTTProductEdition() >= TT_PRODUCT_ENTERPRISE AND $this->getType() == 10 AND $this->getUserObject()->getCompanyObject()->getProductEdition() >= TT_PRODUCT_ENTERPRISE ) {
			$uelf = TTnew( 'UserExpenseListFactory' ); /** @var UserExpenseListFactory $uelf */
			//$uelf->getByUserIdAndAuthorizedAndStartDateAndEndDate( $this->getUser(), TRUE, $this->getPayPeriodObject()->getStartDate(), $this->getPayPeriodObject()->getEndDate() );
			if ( $this->getEnableCorrection() == TRUE ) { //When doing post-adjustment carry-forward, we need to take into account already paid expenses, otherwise the differeces will always be incorrect.
				$user_expense_status_ids = array(30, 35, 40, 50); //30=Active, 35=In Use, 40=Paid/Reimbursed, 50=Authorized
			} else {
				$user_expense_status_ids = array(30, 50); //30=Active, 50=Authorized
			}
			$uelf->getByUserIdAndAuthorizedAndStatusIDAndStartDateAndEndDate( $this->getUser(), TRUE, $user_expense_status_ids, $this->getPayPeriodObject()->getStartDate(), $this->getPayPeriodObject()->getEndDate() );
			unset($user_expense_status_ids);

			Debug::text('Total User Expenses: '. $uelf->getRecordCount(), __FILE__, __LINE__, __METHOD__, 10);
		} else {
			$uelf = FALSE;
		}

		//Ensure that the employee was hired before the end day of the pay period. Especially important if they are using Tax/Deductions to affect earnings to prevent the employee from being paid before their hire date. (This used to checked below with the CompanyDeduction criteria)
		//Make sure we don't try to generate a pay stub before the employees hire date, since we don't calculate Tax/Deduction before their hire date anyways (similar check below), this could result in just earnings and no tax deductions.
		// This can occur when a user tries to terminate an employee, generate an ROE, then immediately re-hire an employee a few days later, then generate pay stubs for the prior pay pay period (the employees last pay period).
		// Essentially they are doing it out of order, they should terminate the employee, generate the final pay stub, generate the ROE, close the pay period, then re-hire the employee.
		if ( $this->getEnableCorrection() == FALSE AND ( $this->getUserObject()->getHireDate() != '' AND TTDate::getMiddleDayEpoch( $this->getUserObject()->getHireDate() ) > TTDate::getMiddleDayEpoch( $this->getPayPeriodObject()->getEndDate() ) ) ) {
			$pay_stub->Validator->isTRUE(	'hire_date',
											 FALSE,
											 TTi18n::gettext('Employee was hired after this pay period already ended, check hire date' ) // Employee Hire Date is after Pay Period End Date
			);
		}

		$deduction_order_arr = $this->getOrderedDeductionAndPSAmendment( $udlf, $psalf, $uelf );
		if ( is_array($deduction_order_arr) AND count($deduction_order_arr) > 0 ) {
			foreach($deduction_order_arr as $calculation_order => $data_arr ) {
				Debug::text('Found PS Amendment/Deduction: Type: '. $data_arr['type'] .' Name: '. $data_arr['name'] .' Order: '. $calculation_order, __FILE__, __LINE__, __METHOD__, 10);
				if ( isset($data_arr['obj']) AND is_object($data_arr['obj']) ) {
					if ( $data_arr['type'] == 'UserDeductionListFactory' ) {
						$ud_obj = $data_arr['obj'];

						//Ensure that if a Tax/Deduction assigned to this user checks age eligibility that the employee has a birth date specified. Otherwise don't generate the pay stub.
						if ( $this->getUserObject()->getBirthDate() == '' AND $ud_obj->getCompanyDeductionObject()->isUserAgeEligibility() == TRUE ) {
							$pay_stub->Validator->isTRUE(	'birth_date',
														 FALSE,
														 TTi18n::gettext('Birth Date not specified, unable to determine age eligibility for %1', $ud_obj->getCompanyDeductionObject()->getName() )
							);
						}

						//Hire Date is always defaulted, so this likely will never get triggered.
						if ( $this->getUserObject()->getHireDate() == '' AND $ud_obj->getCompanyDeductionObject()->isUserLengthOfServiceEligibility() == TRUE ) {
							$pay_stub->Validator->isTRUE(	'hire_date',
															 FALSE,
															 TTi18n::gettext('Hire Date not specified, unable to determine length of service eligibility for %1', $ud_obj->getCompanyDeductionObject()->getName() )
							);
						}

						//Determine if this deduction is valid based on start/end dates.
						//Determine if this deduction is valid based on min/max length of service.
						//Determine if this deduction is valid based on min/max user age.
						//Determine if the Payroll Run Type matches.
						if ( $ud_obj->getCompanyDeductionObject()->isActiveDate( $ud_obj, $pay_stub->getPayPeriodObject()->getEndDate(), $pay_stub->getTransactionDate() ) == TRUE
								AND $ud_obj->getCompanyDeductionObject()->isActiveLengthOfService( $ud_obj, $pay_stub->getPayPeriodObject()->getEndDate(), $pay_stub->getPayPeriodObject()->getStartDate() ) == TRUE
								AND $ud_obj->getCompanyDeductionObject()->isActiveUserAge( $this->getUserObject()->getBirthDate(), $pay_stub->getPayPeriodObject()->getEndDate(), $pay_stub->getTransactionDate() ) == TRUE
								AND $ud_obj->getCompanyDeductionObject()->inApplyFrequencyWindow( $pay_stub->getPayPeriodObject()->getStartDate(), $pay_stub->getPayPeriodObject()->getEndDate(), $this->getUserObject()->getHireDate(), $this->getUserObject()->getTerminationDate(), $this->getUserObject()->getBirthDate() ) == TRUE
								AND $ud_obj->getCompanyDeductionObject()->inApplyPayrollRunType( $this->getType() ) ) {

							$amount = $ud_obj->getDeductionAmount( $this->getUserObject(), $pay_stub, $this->getPayPeriodObject(), $this->getType(), $this->getRun() );
							Debug::text('User Deduction: '. $ud_obj->getCompanyDeductionObject()->getName() .' Amount: '. $amount .' Calculation Order: '. $ud_obj->getCompanyDeductionObject()->getCalculationOrder(), __FILE__, __LINE__, __METHOD__, 10);

							//Allow negative amounts, so they can reduce previously calculated deductions or something.
							if ( isset($amount) AND $amount != 0 ) {
								$pay_stub->addEntry( $ud_obj->getCompanyDeductionObject()->getPayStubEntryAccount(), $amount, NULL, NULL, $ud_obj->getCompanyDeductionObject()->getPayStubEntryDescription() );
							} else {
								Debug::text('Amount is 0, skipping...', __FILE__, __LINE__, __METHOD__, 10);
							}
						} else {
							Debug::text('Eligibility criteria not met, skipping...', __FILE__, __LINE__, __METHOD__, 10);
						}
						unset($amount, $ud_obj);
					} elseif ( $data_arr['type'] == 'PayStubAmendmentListFactory' ) {
						$psa_obj = $data_arr['obj'];

						Debug::text('Found Pay Stub Amendment: ID: '. $psa_obj->getID() .' Entry Name ID: '. $psa_obj->getPayStubEntryNameId() .' Type: '. $psa_obj->getType(), __FILE__, __LINE__, __METHOD__, 10);

						$amount = $psa_obj->getCalculatedAmount( $pay_stub );
						if ( isset($amount) AND $amount != 0 ) {
							Debug::text('Pay Stub Amendment Amount: '. $amount, __FILE__, __LINE__, __METHOD__, 10);
							$pay_stub->addEntry( $psa_obj->getPayStubEntryNameId(), $amount, $psa_obj->getUnits(), $psa_obj->getRate(), $psa_obj->getDescription(), $psa_obj->getID(), NULL, NULL, $psa_obj->getYTDAdjustment() );
						} else {
							Debug::text('bPay Stub Amendment Amount is not set...', __FILE__, __LINE__, __METHOD__, 10);
						}
						unset($amount, $psa_obj);
					} elseif ( $data_arr['type'] == 'UserExpenseListFactory' ) {
						$ue_obj = $data_arr['obj'];

						Debug::text('Found User Expense: ID: '. $ue_obj->getID() .' Expense Policy ID: '. $ue_obj->getExpensePolicy(), __FILE__, __LINE__, __METHOD__, 10);

						$amount = $ue_obj->getReimburseAmount();
						if ( isset($amount) AND $amount != 0 ) {
							Debug::text('User Expense reimbursable Amount: '. $amount, __FILE__, __LINE__, __METHOD__, 10);
							$pay_stub->addEntry( $ue_obj->getExpensePolicyObject()->getPayStubEntryAccount(), $amount, NULL, NULL, NULL, NULL, NULL, NULL, FALSE, $ue_obj->getID() );
						} else {
							Debug::text('bUser Expense Amount is not set...', __FILE__, __LINE__, __METHOD__, 10);
						}
						unset($amount, $ue_obj);
					} else {
						Debug::text('  ERROR: PS Amendment/Deduction object type not recognized!', __FILE__, __LINE__, __METHOD__, 10);
					}
				} else {
					Debug::text('  ERROR: PS Amendment/Deduction object not found, or not an object! ', __FILE__, __LINE__, __METHOD__, 10);
				}
			}
		}
		unset($deduction_order_arr, $calculation_order, $data_arr);

		$pay_stub_id = $pay_stub->getId();

		$pay_stub->setEnableProcessEntries(TRUE);
		$pay_stub->processEntries();

		$pay_stub->setEnableProcessTransactions(TRUE);
		$pay_stub->calculateDefaultTransactions();
		//$pay_stub->processTransactions();

		if ( $pay_stub->isValid() == TRUE ) {
			Debug::text('Pay Stub is valid, final save.', __FILE__, __LINE__, __METHOD__, 10);
			$pay_stub->setEnableCalcYTD( TRUE ); //When recalculating old pay stubs in the middle of the year, we need to make sure YTD values are updated.

			$pay_stub->Save( FALSE );
			//$pay_stub->Save( FALSE ) could have validation errors in the postSave() function, we want to try and capture those as well.
			//Usually it has to do with pay stub amendments being after the employees termination date.
			if ( $pay_stub->isValid() == TRUE  ) {
				if ( $this->getEnableCorrection() == TRUE ) {
					Debug::text('bCorrection Enabled - Doing Comparison here', __FILE__, __LINE__, __METHOD__, 10);
					PayStubFactory::CalcDifferences( $pay_stub_id, $old_pay_stub_id, $pay_stub->getPayPeriodObject()->getEndDate(), $this->getTransactionDate() );

					//Delete newly created temp paystub.
					//This used to be in the above IF block that depended on $old_pay_stub_id
					//being set, however in cases where the old pay stub didn't exist
					//FairnessTNA wouldn't delete these temporary pay stubs.
					//Moving this code outside that IF statement so it only depends on EnableCorrection()
					//to be TRUE should fix that issue.
					$pslf = TTnew( 'PayStubListFactory' ); /** @var PayStubListFactory $pslf */
					$pslf->getById( $pay_stub_id );
					if ( $pslf->getRecordCount() > 0 ) {
						$tmp_ps_obj = $pslf->getCurrent();
						$tmp_ps_obj->setDeleted(TRUE);
						$tmp_ps_obj->Save();
						unset($tmp_ps_obj);
					}
				}

				$pay_stub->CommitTransaction();

				//Check for PSAs that are ACTIVE before the pay period start date.
				//$psalf->getByUserIdAndAuthorizedAndStartDateAndEndDate( $this->getUser(), TRUE, strtotime('01-Jan-2000'), ( $this->getPayPeriodObject()->getStartDate() - 86400 ) );
				$psalf->getByUserIdAndAuthorizedAndStatusIDAndStartDateAndEndDate( $this->getUser(), TRUE, 50, strtotime('01-Jan-2000'), ( $this->getPayPeriodObject()->getStartDate() - 86400 ) ); //50=Active


				if ( $this->getEnableCorrection() == FALSE AND ( $is_termination_pay_period == TRUE OR $is_post_termination_pay_period == TRUE ) AND $pay_stub->isAccrualBalanceOutstanding() == TRUE ) {
					UserGenericStatusFactory::queueGenericStatus( $generic_queue_status_label, 20, TTi18n::gettext('Employee is terminated, but accrual balances haven\'t been released'), NULL );
				} elseif ( $psalf->getRecordCount() > 0 ) {
					UserGenericStatusFactory::queueGenericStatus( $generic_queue_status_label, 20, TTi18n::gettext('Employee has %1 active pay stub amendments before this pay period that have not been paid', array($psalf->getRecordCount()) ), NULL );
				} else {
					UserGenericStatusFactory::queueGenericStatus( $generic_queue_status_label, 30, TTi18n::gettext('Total Gross') .': '. Misc::MoneyFormat( $pay_stub->getGrossPay() ) .' '. TTi18n::gettext('Net Pay') .': '. Misc::MoneyFormat( $pay_stub->getNetPay() ), NULL );
				}

				return TRUE;
			}
		}

		Debug::text('Pay Stub is NOT valid returning FALSE', __FILE__, __LINE__, __METHOD__, 10);

		UserGenericStatusFactory::queueGenericStatus( $generic_queue_status_label, 10, $pay_stub->Validator->getTextErrors(), NULL );

		$pay_stub->FailTransaction(); //Reduce transaction count by one.
		$pay_stub->CommitTransaction();

		return FALSE;
	}
}
?>
