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
 * @package PayrollDeduction\CA
 */
class PayrollDeduction_CA_NS extends PayrollDeduction_CA {
	var $provincial_income_tax_rate_options = array(
			20110101 => array(
					array('income' => 29590, 'rate' => 8.79, 'constant' => 0),
					array('income' => 59180, 'rate' => 14.95, 'constant' => 1823),
					array('income' => 93000, 'rate' => 16.67, 'constant' => 2841),
					array('income' => 150000, 'rate' => 17.5, 'constant' => 3613),
					array('income' => 150000, 'rate' => 21.0, 'constant' => 8863),
			),
			20100701 => array(
					array('income' => 29590, 'rate' => 8.79, 'constant' => 0),
					array('income' => 59180, 'rate' => 14.95, 'constant' => 1823),
					array('income' => 93000, 'rate' => 16.67, 'constant' => 2841),
					array('income' => 150000, 'rate' => 17.5, 'constant' => 3613),
					array('income' => 150000, 'rate' => 24.5, 'constant' => 14113),
			),
			20070101 => array(
					array('income' => 29590, 'rate' => 8.79, 'constant' => 0),
					array('income' => 59180, 'rate' => 14.95, 'constant' => 1823),
					array('income' => 93000, 'rate' => 16.67, 'constant' => 2841),
					array('income' => 93000, 'rate' => 17.5, 'constant' => 3613),
			),
	);

	function getProvincialTotalClaimAmount() {
		/*
		BPA = 	Where A ≤ $25,000, BPA is equal to $11,481;
				Where A > $25,000 < $75,000, BPA is equal to:
				$11,481 – [(A – $25,000) × 6%)];*
				Where A ≥ $75,000, BPA is equal to $8,481

		$11,481 High Basic Claim Amount -- **This should be set in Data.class.php**
		$8,481 Low Basic Claim Amount
		*/

		$BPA = parent::getProvincialTotalClaimAmount();
		if ( $this->getDate() >= 20180101 AND $BPA > 0 ) {
			$high_claim_amount = $this->getBasicProvinceClaimCodeAmount();
			$low_claim_amount = 8481;

			$A = $this->getAnnualTaxableIncome();

			if ( $A <= 25000 ) {
				$BPA = $high_claim_amount;
			} elseif ( $A > 25000 AND $A < 75000 ) {
				$BPA = $high_claim_amount - ( ( $A - 25000 ) * 0.06 );
			} elseif ( $A > 75000 ) {
				$BPA = $low_claim_amount;
			}

			Debug::text( 'BPA: ' . $BPA . ' Claim Amount: High: ' . $high_claim_amount . ' Low: ' . $low_claim_amount . ' A: ' . $A, __FILE__, __LINE__, __METHOD__, 10 );
		}

		return $BPA;
	}
}

?>
