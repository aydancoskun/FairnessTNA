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
 * @package GovernmentForms
 */

//This is the header record for submitting XML forms to the CRA.
include_once( 'US.class.php' );

class GovernmentForms_US_RETURN940 extends GovernmentForms_US {
	public $xml_schema = '94x/94x/Return940.xsd';

	public function getFilterFunction( $name ) {
		$variable_function_map = array(
			//'year' => 'isNumeric',
			//'ein' => array( 'stripNonNumeric', 'isNumeric'),
		);

		if ( isset( $variable_function_map[ $name ] ) ) {
			return $variable_function_map[ $name ];
		}

		return FALSE;
	}

	public function getTemplateSchema( $name = NULL ) {
		$template_schema = array();

		if ( isset( $template_schema[ $name ] ) ) {
			return $name;
		} else {
			return $template_schema;
		}
	}

	//Set the submission status. Original, Amended, Cancel.
	function getStatus() {
		if ( isset( $this->status ) ) {
			return $this->status;
		}

		return 'O'; //Original
	}

	function setStatus( $value ) {
		if ( strtoupper( $value ) == 'C' ) {
			$value = 'A'; //Cancel isn't valid for this, only original and amendment.
		}
		$this->status = strtoupper( trim( $value ) );

		return TRUE;
	}

	function _outputXML() {

		$xml = new SimpleXMLElement( '<ReturnData xsi:schemaLocation="http://www.irs.gov/efile ReturnData940.xsd" xmlns="http://www.irs.gov/efile" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></ReturnData>' ); //IRS940 must be wrapped in <ReturnData></ReturnData>
		$xml->addAttribute( 'documentCount', 0 ); // The number of return documents in the return.

		$this->setXMLObject( $xml );

		$xml->addChild( 'ContentLocation', '-' ); // Must be unique within the transmission file and must match the value on the MIME Content-Location: line

		$xml->addChild( 'ReturnHeader94x' );
		$xml->ReturnHeader94x->addAttribute( 'documentId', '-' ); // Must be unique within the return.

		$xml->ReturnHeader94x->addChild( 'TaxPeriodEndDate', $this->TaxPeriodEndDate );
		$xml->ReturnHeader94x->addChild( 'ReturnType', $this->ReturnType );

		$xml->ReturnHeader94x->addChild( 'Business' );

		$xml->ReturnHeader94x->Business->addChild( 'EIN', $this->ein );
		$xml->ReturnHeader94x->Business->addChild( 'BusinessName1', $this->BusinessName1 );
		$xml->ReturnHeader94x->Business->addChild( 'BusinessNameControl', $this->BusinessNameControl );

		$xml->ReturnHeader94x->Business->addChild( 'USAddress' );
		$xml->ReturnHeader94x->Business->USAddress->addChild( 'AddressLine', $this->AddressLine );
		$xml->ReturnHeader94x->Business->USAddress->addChild( 'City', $this->City );
		$xml->ReturnHeader94x->Business->USAddress->addChild( 'State', $this->State );
		$xml->ReturnHeader94x->Business->USAddress->addChild( 'ZIPCode', $this->ZIPCode );

		$xml->addChild( 'IRS940' );


		return TRUE;
	}

	function _outputPDF() {
		return FALSE;
	}
}

?>