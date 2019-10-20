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
 * @package PayrollDeduction\US
 */
class PayrollDeduction_US_MO extends PayrollDeduction_US {
	/*
															10 => 'Single',
															20 => 'Married - Spouse Works',
															30 => 'Married - Spouse does not Work',
															40 => 'Head of Household',
	*/

	var $state_income_tax_rate_options = array(
		//Constants are calculated strange from the Government, just use their values. Remember to add all constant values from bottom to top together for each bracket. ie: 16 + 37 + 63 + 95, ...
		20190101 => array(
				10 => array(
						array('income' => 1053, 'rate' => 1.5, 'constant' => 0),
						array('income' => 2106, 'rate' => 2.0, 'constant' => 16),
						array('income' => 3159, 'rate' => 2.5, 'constant' => 37),
						array('income' => 4212, 'rate' => 3.0, 'constant' => 63),
						array('income' => 5265, 'rate' => 3.5, 'constant' => 95),
						array('income' => 6318, 'rate' => 4.0, 'constant' => 132),
						array('income' => 7371, 'rate' => 4.5, 'constant' => 174),
						array('income' => 8424, 'rate' => 5.0, 'constant' => 221),
						array('income' => 8424, 'rate' => 5.4, 'constant' => 274),
				),
				20 => array(
						array('income' => 1053, 'rate' => 1.5, 'constant' => 0),
						array('income' => 2106, 'rate' => 2.0, 'constant' => 16),
						array('income' => 3159, 'rate' => 2.5, 'constant' => 37),
						array('income' => 4212, 'rate' => 3.0, 'constant' => 63),
						array('income' => 5265, 'rate' => 3.5, 'constant' => 95),
						array('income' => 6318, 'rate' => 4.0, 'constant' => 132),
						array('income' => 7371, 'rate' => 4.5, 'constant' => 174),
						array('income' => 8424, 'rate' => 5.0, 'constant' => 221),
						array('income' => 8424, 'rate' => 5.4, 'constant' => 267),
				),
				30 => array(
						array('income' => 1053, 'rate' => 1.5, 'constant' => 0),
						array('income' => 2106, 'rate' => 2.0, 'constant' => 16),
						array('income' => 3159, 'rate' => 2.5, 'constant' => 37),
						array('income' => 4212, 'rate' => 3.0, 'constant' => 63),
						array('income' => 5265, 'rate' => 3.5, 'constant' => 95),
						array('income' => 6318, 'rate' => 4.0, 'constant' => 132),
						array('income' => 7371, 'rate' => 4.5, 'constant' => 174),
						array('income' => 8424, 'rate' => 5.0, 'constant' => 221),
						array('income' => 8424, 'rate' => 5.4, 'constant' => 267),
				),
				40 => array(
						array('income' => 1053, 'rate' => 1.5, 'constant' => 0),
						array('income' => 2106, 'rate' => 2.0, 'constant' => 16),
						array('income' => 3159, 'rate' => 2.5, 'constant' => 37),
						array('income' => 4212, 'rate' => 3.0, 'constant' => 63),
						array('income' => 5265, 'rate' => 3.5, 'constant' => 95),
						array('income' => 6318, 'rate' => 4.0, 'constant' => 132),
						array('income' => 7371, 'rate' => 4.5, 'constant' => 174),
						array('income' => 8424, 'rate' => 5.0, 'constant' => 221),
						array('income' => 8424, 'rate' => 5.4, 'constant' => 267),
				),
		),
		20180101 => array(
				10 => array(
						array('income' => 103, 'rate' => 0, 'constant' => 0),
						array('income' => 1028, 'rate' => 1.5, 'constant' => 0.00),
						array('income' => 2056, 'rate' => 2.0, 'constant' => 15.00),
						array('income' => 3084, 'rate' => 2.5, 'constant' => 36.00),
						array('income' => 4113, 'rate' => 3.0, 'constant' => 62.00),
						array('income' => 5141, 'rate' => 3.5, 'constant' => 93.00),
						array('income' => 6169, 'rate' => 4.0, 'constant' => 129.00),
						array('income' => 7197, 'rate' => 4.5, 'constant' => 170.00),
						array('income' => 8225, 'rate' => 5.0, 'constant' => 216.00),
						array('income' => 9253, 'rate' => 5.5, 'constant' => 267.00),
						array('income' => 9253, 'rate' => 5.9, 'constant' => 324.00),
				),
				20 => array(
						array('income' => 103, 'rate' => 0, 'constant' => 0),
						array('income' => 1028, 'rate' => 1.5, 'constant' => 0.00),
						array('income' => 2056, 'rate' => 2.0, 'constant' => 15.00),
						array('income' => 3084, 'rate' => 2.5, 'constant' => 36.00),
						array('income' => 4113, 'rate' => 3.0, 'constant' => 62.00),
						array('income' => 5141, 'rate' => 3.5, 'constant' => 93.00),
						array('income' => 6169, 'rate' => 4.0, 'constant' => 129.00),
						array('income' => 7197, 'rate' => 4.5, 'constant' => 170.00),
						array('income' => 8225, 'rate' => 5.0, 'constant' => 216.00),
						array('income' => 9253, 'rate' => 5.5, 'constant' => 267.00),
						array('income' => 9253, 'rate' => 5.9, 'constant' => 324.00),
				),
				30 => array(
						array('income' => 103, 'rate' => 0, 'constant' => 0),
						array('income' => 1028, 'rate' => 1.5, 'constant' => 0.00),
						array('income' => 2056, 'rate' => 2.0, 'constant' => 15.00),
						array('income' => 3084, 'rate' => 2.5, 'constant' => 36.00),
						array('income' => 4113, 'rate' => 3.0, 'constant' => 62.00),
						array('income' => 5141, 'rate' => 3.5, 'constant' => 93.00),
						array('income' => 6169, 'rate' => 4.0, 'constant' => 129.00),
						array('income' => 7197, 'rate' => 4.5, 'constant' => 170.00),
						array('income' => 8225, 'rate' => 5.0, 'constant' => 216.00),
						array('income' => 9253, 'rate' => 5.5, 'constant' => 267.00),
						array('income' => 9253, 'rate' => 5.9, 'constant' => 324.00),
				),
				40 => array(
						array('income' => 103, 'rate' => 0, 'constant' => 0),
						array('income' => 1028, 'rate' => 1.5, 'constant' => 0.00),
						array('income' => 2056, 'rate' => 2.0, 'constant' => 15.00),
						array('income' => 3084, 'rate' => 2.5, 'constant' => 36.00),
						array('income' => 4113, 'rate' => 3.0, 'constant' => 62.00),
						array('income' => 5141, 'rate' => 3.5, 'constant' => 93.00),
						array('income' => 6169, 'rate' => 4.0, 'constant' => 129.00),
						array('income' => 7197, 'rate' => 4.5, 'constant' => 170.00),
						array('income' => 8225, 'rate' => 5.0, 'constant' => 216.00),
						array('income' => 9253, 'rate' => 5.5, 'constant' => 267.00),
						array('income' => 9253, 'rate' => 5.9, 'constant' => 324.00),
				),
		),
		20170101 => array(
				10 => array(
						array('income' => 1008, 'rate' => 1.5, 'constant' => 0),
						array('income' => 2016, 'rate' => 2.0, 'constant' => 15.00),
						array('income' => 3024, 'rate' => 2.5, 'constant' => 35.00),
						array('income' => 4032, 'rate' => 3.0, 'constant' => 60.00),
						array('income' => 5040, 'rate' => 3.5, 'constant' => 90.00),
						array('income' => 6048, 'rate' => 4.0, 'constant' => 125.00),
						array('income' => 7056, 'rate' => 4.5, 'constant' => 165.00),
						array('income' => 8064, 'rate' => 5.0, 'constant' => 210.00),
						array('income' => 9072, 'rate' => 5.5, 'constant' => 260.00),
						array('income' => 9072, 'rate' => 6.0, 'constant' => 315.00),
				),
				20 => array(
						array('income' => 1008, 'rate' => 1.5, 'constant' => 0),
						array('income' => 2016, 'rate' => 2.0, 'constant' => 15.00),
						array('income' => 3024, 'rate' => 2.5, 'constant' => 35.00),
						array('income' => 4032, 'rate' => 3.0, 'constant' => 60.00),
						array('income' => 5040, 'rate' => 3.5, 'constant' => 90.00),
						array('income' => 6048, 'rate' => 4.0, 'constant' => 125.00),
						array('income' => 7056, 'rate' => 4.5, 'constant' => 165.00),
						array('income' => 8064, 'rate' => 5.0, 'constant' => 210.00),
						array('income' => 9072, 'rate' => 5.5, 'constant' => 260.00),
						array('income' => 9072, 'rate' => 6.0, 'constant' => 315.00),
				),
				30 => array(
						array('income' => 1008, 'rate' => 1.5, 'constant' => 0),
						array('income' => 2016, 'rate' => 2.0, 'constant' => 15.00),
						array('income' => 3024, 'rate' => 2.5, 'constant' => 35.00),
						array('income' => 4032, 'rate' => 3.0, 'constant' => 60.00),
						array('income' => 5040, 'rate' => 3.5, 'constant' => 90.00),
						array('income' => 6048, 'rate' => 4.0, 'constant' => 125.00),
						array('income' => 7056, 'rate' => 4.5, 'constant' => 165.00),
						array('income' => 8064, 'rate' => 5.0, 'constant' => 210.00),
						array('income' => 9072, 'rate' => 5.5, 'constant' => 260.00),
						array('income' => 9072, 'rate' => 6.0, 'constant' => 315.00),
				),
				40 => array(
						array('income' => 1008, 'rate' => 1.5, 'constant' => 0),
						array('income' => 2016, 'rate' => 2.0, 'constant' => 15.00),
						array('income' => 3024, 'rate' => 2.5, 'constant' => 35.00),
						array('income' => 4032, 'rate' => 3.0, 'constant' => 60.00),
						array('income' => 5040, 'rate' => 3.5, 'constant' => 90.00),
						array('income' => 6048, 'rate' => 4.0, 'constant' => 125.00),
						array('income' => 7056, 'rate' => 4.5, 'constant' => 165.00),
						array('income' => 8064, 'rate' => 5.0, 'constant' => 210.00),
						array('income' => 9072, 'rate' => 5.5, 'constant' => 260.00),
						array('income' => 9072, 'rate' => 6.0, 'constant' => 315.00),
				),
		),
		20060101 => array(
				10 => array(
						array('income' => 1000, 'rate' => 1.5, 'constant' => 0),
						array('income' => 2000, 'rate' => 2.0, 'constant' => 15.00),
						array('income' => 3000, 'rate' => 2.5, 'constant' => 35.00),
						array('income' => 4000, 'rate' => 3.0, 'constant' => 60.00),
						array('income' => 5000, 'rate' => 3.5, 'constant' => 90.00),
						array('income' => 6000, 'rate' => 4.0, 'constant' => 125.00),
						array('income' => 7000, 'rate' => 4.5, 'constant' => 165.00),
						array('income' => 8000, 'rate' => 5.0, 'constant' => 210.00),
						array('income' => 9000, 'rate' => 5.5, 'constant' => 260.00),
						array('income' => 9000, 'rate' => 6.0, 'constant' => 315.00),
				),
				20 => array(
						array('income' => 1000, 'rate' => 1.5, 'constant' => 0),
						array('income' => 2000, 'rate' => 2.0, 'constant' => 15.00),
						array('income' => 3000, 'rate' => 2.5, 'constant' => 35.00),
						array('income' => 4000, 'rate' => 3.0, 'constant' => 60.00),
						array('income' => 5000, 'rate' => 3.5, 'constant' => 90.00),
						array('income' => 6000, 'rate' => 4.0, 'constant' => 125.00),
						array('income' => 7000, 'rate' => 4.5, 'constant' => 165.00),
						array('income' => 8000, 'rate' => 5.0, 'constant' => 210.00),
						array('income' => 9000, 'rate' => 5.5, 'constant' => 260.00),
						array('income' => 9000, 'rate' => 6.0, 'constant' => 315.00),
				),
				30 => array(
						array('income' => 1000, 'rate' => 1.5, 'constant' => 0),
						array('income' => 2000, 'rate' => 2.0, 'constant' => 15.00),
						array('income' => 3000, 'rate' => 2.5, 'constant' => 35.00),
						array('income' => 4000, 'rate' => 3.0, 'constant' => 60.00),
						array('income' => 5000, 'rate' => 3.5, 'constant' => 90.00),
						array('income' => 6000, 'rate' => 4.0, 'constant' => 125.00),
						array('income' => 7000, 'rate' => 4.5, 'constant' => 165.00),
						array('income' => 8000, 'rate' => 5.0, 'constant' => 210.00),
						array('income' => 9000, 'rate' => 5.5, 'constant' => 260.00),
						array('income' => 9000, 'rate' => 6.0, 'constant' => 315.00),
				),
				40 => array(
						array('income' => 1000, 'rate' => 1.5, 'constant' => 0),
						array('income' => 2000, 'rate' => 2.0, 'constant' => 15.00),
						array('income' => 3000, 'rate' => 2.5, 'constant' => 35.00),
						array('income' => 4000, 'rate' => 3.0, 'constant' => 60.00),
						array('income' => 5000, 'rate' => 3.5, 'constant' => 90.00),
						array('income' => 6000, 'rate' => 4.0, 'constant' => 125.00),
						array('income' => 7000, 'rate' => 4.5, 'constant' => 165.00),
						array('income' => 8000, 'rate' => 5.0, 'constant' => 210.00),
						array('income' => 9000, 'rate' => 5.5, 'constant' => 260.00),
						array('income' => 9000, 'rate' => 6.0, 'constant' => 315.00),
				),
		),
	);

	var $state_options = array(
			20190101 => array(
					'standard_deduction'  => array(
							'10' => 12200.00,
							'20' => 12200.00,
							'30' => 24400.00,
							'40' => 18350.00,
					),
					'allowance'           => array( //Removed in 2018.
													'10' => array(2100.00, 1200.00, 1200.00),
													'20' => array(2100.00, 1200.00, 1200.00),
													'30' => array(2100.00, 2100.00, 1200.00),
													'40' => array(3500.00, 1200.00, 1200.00),
					),
					'federal_tax_maximum' => array( //Removed in 2019.
							'10' => 5000.00,
							'20' => 5000.00,
							'30' => 10000.00,
							'40' => 5000.00,
					),
			),
			20180101 => array(
					'standard_deduction'  => array(
							'10' => 12000.00,
							'20' => 12000.00,
							'30' => 24000.00,
							'40' => 18000.00,
					),
					'allowance'           => array( //Removed in 2018.
							'10' => array(2100.00, 1200.00, 1200.00),
							'20' => array(2100.00, 1200.00, 1200.00),
							'30' => array(2100.00, 2100.00, 1200.00),
							'40' => array(3500.00, 1200.00, 1200.00),
					),
					'federal_tax_maximum' => array(
							'10' => 5000.00,
							'20' => 5000.00,
							'30' => 10000.00,
							'40' => 5000.00,
					),
			),
			20170101 => array(
					'standard_deduction'  => array(
							'10' => 6350.00,
							'20' => 6350.00,
							'30' => 12700.00,
							'40' => 9350.00,
					),
					'allowance'           => array(
							'10' => array(2100.00, 1200.00, 1200.00),
							'20' => array(2100.00, 1200.00, 1200.00),
							'30' => array(2100.00, 2100.00, 1200.00),
							'40' => array(3500.00, 1200.00, 1200.00),
					),
					'federal_tax_maximum' => array(
							'10' => 5000.00,
							'20' => 5000.00,
							'30' => 10000.00,
							'40' => 5000.00,
					),
			),
			20160101 => array(
					'standard_deduction'  => array(
							'10' => 6300.00,
							'20' => 6300.00,
							'30' => 12600.00,
							'40' => 9300.00,
					),
					'allowance'           => array(
							'10' => array(2100.00, 1200.00, 1200.00),
							'20' => array(2100.00, 1200.00, 1200.00),
							'30' => array(2100.00, 2100.00, 1200.00),
							'40' => array(3500.00, 1200.00, 1200.00),
					),
					'federal_tax_maximum' => array(
							'10' => 5000.00,
							'20' => 5000.00,
							'30' => 10000.00,
							'40' => 5000.00,
					),
			),
			20150101 => array( //01-Jan-15
							   'standard_deduction'  => array(
									   '10' => 6300.00,
									   '20' => 6300.00,
									   '30' => 12600.00,
									   '40' => 9250.00,
							   ),
							   'allowance'           => array(
									   '10' => array(2100.00, 1200.00, 1200.00),
									   '20' => array(2100.00, 1200.00, 1200.00),
									   '30' => array(2100.00, 2100.00, 1200.00),
									   '40' => array(3500.00, 1200.00, 1200.00),
							   ),
							   'federal_tax_maximum' => array(
									   '10' => 5000.00,
									   '20' => 5000.00,
									   '30' => 10000.00,
									   '40' => 5000.00,
							   ),
			),
			20140101 => array( //01-Jan-14
							   'standard_deduction'  => array(
									   '10' => 6200.00,
									   '20' => 6200.00,
									   '30' => 12400.00,
									   '40' => 9100.00,
							   ),
							   'allowance'           => array(
									   '10' => array(2100.00, 1200.00, 1200.00),
									   '20' => array(2100.00, 1200.00, 1200.00),
									   '30' => array(2100.00, 2100.00, 1200.00),
									   '40' => array(3500.00, 1200.00, 1200.00),
							   ),
							   'federal_tax_maximum' => array(
									   '10' => 5000.00,
									   '20' => 5000.00,
									   '30' => 10000.00,
									   '40' => 5000.00,
							   ),
			),
			20130101 => array( //01-Jan-13
							   'standard_deduction'  => array(
									   '10' => 6100.00,
									   '20' => 6100.00,
									   '30' => 12200.00,
									   '40' => 8950.00,
							   ),
							   'allowance'           => array(
									   '10' => array(2100.00, 1200.00, 1200.00),
									   '20' => array(2100.00, 1200.00, 1200.00),
									   '30' => array(2100.00, 2100.00, 1200.00),
									   '40' => array(3500.00, 1200.00, 1200.00),
							   ),
							   'federal_tax_maximum' => array(
									   '10' => 5000.00,
									   '20' => 5000.00,
									   '30' => 10000.00,
									   '40' => 5000.00,
							   ),
			),
			20120101 => array( //01-Jan-12
							   'standard_deduction'  => array(
									   '10' => 5800.00,
									   '20' => 5800.00,
									   '30' => 11600.00,
									   '40' => 8500.00,
							   ),
							   'allowance'           => array(
									   '10' => array(2100.00, 1200.00, 1200.00),
									   '20' => array(2100.00, 1200.00, 1200.00),
									   '30' => array(2100.00, 2100.00, 1200.00),
									   '40' => array(3500.00, 1200.00, 1200.00),
							   ),
							   'federal_tax_maximum' => array(
									   '10' => 5000.00,
									   '20' => 5000.00,
									   '30' => 10000.00,
									   '40' => 5000.00,
							   ),
			),
			20090101 => array( //01-Jan-09
							   'standard_deduction'  => array(
									   '10' => 5700.00,
									   '20' => 5700.00,
									   '30' => 11400.00,
									   '40' => 8350.00,
							   ),
							   'allowance'           => array(
									   '10' => array(2100.00, 1200.00, 1200.00),
									   '20' => array(2100.00, 1200.00, 1200.00),
									   '30' => array(2100.00, 2100.00, 1200.00),
									   '40' => array(3500.00, 1200.00, 1200.00),
							   ),
							   'federal_tax_maximum' => array(
									   '10' => 5000.00,
									   '20' => 5000.00,
									   '30' => 10000.00,
									   '40' => 5000.00,
							   ),
			),
			20070101 => array(
					'standard_deduction'  => array(
							'10' => 5350.00,
							'20' => 5350.00,
							'30' => 10700.00,
							'40' => 7850.00,
					),
					'allowance'           => array(
							'10' => array(1200.00, 1200.00, 1200.00),
							'20' => array(1200.00, 1200.00, 1200.00),
							'30' => array(1200.00, 1200.00, 1200.00),
							'40' => array(3500.00, 1200.00, 1200.00),
					),
					'federal_tax_maximum' => array(
							'10' => 5000.00,
							'20' => 5000.00,
							'30' => 10000.00,
							'40' => 5000.00,
					),
			),
			20060101 => array(
					'standard_deduction'  => array(
							'10' => 5150.00,
							'20' => 5150.00,
							'30' => 10300.00,
							'40' => 7550.00,
					),
					'allowance'           => array(
							'10' => array(1200.00, 1200.00, 1200.00),
							'20' => array(1200.00, 1200.00, 1200.00),
							'30' => array(1200.00, 1200.00, 1200.00),
							'40' => array(3500.00, 1200.00, 1200.00),
					),
					'federal_tax_maximum' => array(
							'10' => 5000.00,
							'20' => 5000.00,
							'30' => 10000.00,
							'40' => 5000.00,
					),

			),
	);

	function getStatePayPeriodDeductionRoundedValue( $amount ) {
		return $this->RoundNearestDollar( $amount );
	}

	function getStateAnnualTaxableIncome() {
		$annual_income = $this->getAnnualTaxableIncome();
		$federal_tax = $this->getFederalTaxPayable();
		$state_deductions = $this->getStateStandardDeduction();
		$state_allowance = $this->getStateAllowanceAmount();

		Debug::text( 'State Federal Tax: ' . $federal_tax, __FILE__, __LINE__, __METHOD__, 10 );
		if ( $this->getDate() < 20190101 ) { //Removed for 2019
			if ( $federal_tax > $this->getStateFederalTaxMaximum() ) {
				$federal_tax = $this->getStateFederalTaxMaximum();
			}
		} else {
			$federal_tax = 0;
		}

		$income = bcsub( bcsub( bcsub( $annual_income, $federal_tax ), $state_deductions ), $state_allowance );

		Debug::text( 'State Annual Taxable Income: ' . $income, __FILE__, __LINE__, __METHOD__, 10 );

		return $income;
	}

	function getStateFederalTaxMaximum() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;
		}

		$maximum = $retarr['federal_tax_maximum'][ $this->getStateFilingStatus() ];

		Debug::text( 'Maximum State allowed Federal Tax: ' . $maximum, __FILE__, __LINE__, __METHOD__, 10 );

		return $maximum;
	}

	function getStateStandardDeduction() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;

		}

		$deduction = $retarr['standard_deduction'][ $this->getStateFilingStatus() ];

		Debug::text( 'Standard Deduction: ' . $deduction, __FILE__, __LINE__, __METHOD__, 10 );

		return $deduction;
	}

	function getStateAllowanceAmount() {
		$retarr = $this->getDataFromRateArray( $this->getDate(), $this->state_options );
		if ( $retarr == FALSE ) {
			return FALSE;

		}

		if ( $this->getDate() < 20180101 ) { //Removed for 2018
			$allowance_arr = $retarr['allowance'][ $this->getStateFilingStatus() ];

			if ( $this->getStateAllowance() == 0 ) {
				$retval = 0;
			} elseif ( $this->getStateAllowance() == 1 ) {
				$retval = $allowance_arr[0];
			} elseif ( $this->getStateAllowance() == 2 ) {
				$retval = bcadd( $allowance_arr[0], $allowance_arr[1] );
			} else {
				$retval = bcadd( $allowance_arr[0], bcadd( $allowance_arr[1], bcmul( bcsub( $this->getStateAllowance(), 2 ), $allowance_arr[2] ) ) );
			}
		} else {
			$retval = 0;
		}

		Debug::text( 'State Allowance Amount: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}

	function _getStateTaxPayable() {
		$annual_income = $this->getStateAnnualTaxableIncome();

		$retval = 0;

		if ( $annual_income > 0 ) {
			$rate = $this->getData()->getStateRate( $annual_income );
			$prev_income = $this->getData()->getStateRatePreviousIncome( $annual_income );
			$state_constant = $this->getData()->getStateConstant( $annual_income );

			$retval = bcadd( bcmul( bcsub( $annual_income, $prev_income ), $rate ), $state_constant );
		}

		if ( $retval < 0 ) {
			$retval = 0;
		}

		Debug::text( 'State Annual Tax Payable: ' . $retval, __FILE__, __LINE__, __METHOD__, 10 );

		return $retval;
	}

	function getStateEmployerUI() {
		if ( $this->getUIExempt() == TRUE ) {
			return 0;
		}

		$pay_period_income = $this->getGrossPayPeriodIncome();
		$rate = bcdiv( $this->getStateUIRate(), 100 );
		$maximum_contribution = bcmul( $this->getStateUIWageBase(), $rate );
		$ytd_contribution = $this->getYearToDateStateUIContribution();

		Debug::text( 'Rate: ' . $rate . ' YTD Contribution: ' . $ytd_contribution . ' Maximum: ' . $maximum_contribution, __FILE__, __LINE__, __METHOD__, 10 );

		$amount = bcmul( $pay_period_income, $rate );
		$max_amount = bcsub( $maximum_contribution, $ytd_contribution );

		if ( $amount > $max_amount ) {
			$retval = $max_amount;
		} else {
			$retval = $amount;
		}

		return $retval;
	}

}

?>
