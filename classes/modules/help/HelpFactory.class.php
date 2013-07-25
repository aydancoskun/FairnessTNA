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
 * $Revision: 8371 $
 * $Id: HelpFactory.class.php 8371 2012-11-22 21:18:57Z ipso $
 * $Date: 2012-11-22 13:18:57 -0800 (Thu, 22 Nov 2012) $
 */

/**
 * @package Modules\Help
 */
class HelpFactory extends Factory {
	protected $table = 'help';
	protected $pk_sequence_name = 'help_id_seq'; //PK Sequence name


	function _getFactoryOptions( $name ) {
	
		$retval = NULL;
		switch( $name ) {
			case 'type':
				$retval = array(
										10 => TTi18n::gettext('Form'),
										20 => TTi18n::gettext('Page')
									);
				break;
			case 'status':
				$retval = array(
										10 => TTi18n::gettext('NEW'),
										15 => TTi18n::gettext('Pending Approval'), 
										20 => TTi18n::gettext('ACTIVE')
									);
				break;

		}

		return $retval;
	}


	function getType() {
		return $this->data['type_id'];
	}
	function setType($type) {
		$type = trim($type);
		
		$key = Option::getByValue($type, $this->getOptions('type') );
		if ($key !== FALSE) {
			$type = $key;	
		}
		
		Debug::Text('bType: '. $type , __FILE__, __LINE__, __METHOD__,10);
		if ( $this->Validator->inArrayKey(	'type',
											$type,
											TTi18n::gettext('Incorrect Type'),
											$this->getOptions('type')) ) {
			
			$this->data['type_id'] = $type;
			
			return FALSE;
		}
		
		return FALSE;
	}

	function getStatus() {
		return $this->data['status_id'];
	}
	function setStatus($status) {
		$status = trim($status);
		
		$key = Option::getByValue($status, $this->getOptions('status') );
		if ($key !== FALSE) {
			$status = $key;	
		}
		
		if ( $this->Validator->inArrayKey(	'status',
											$status,
											TTi18n::gettext('Incorrect Status'),
											$this->getOptions('status')) ) {
			
			$this->data['status_id'] = $status;
			
			return FALSE;
		}
		
		return FALSE;
	}

	function getHeading() {
		return $this->data['heading'];
	}
	function setHeading($value) {
		$value = trim($value);

		if (	$value == NULL
				OR
				$this->Validator->isLength(	'heading',
											$value,
											TTi18n::gettext('Incorrect Heading length'),
											2,255) ) {

			$this->data['heading'] = $value;

			return FALSE;
		}

		return FALSE;
	}

	function getBody() {
		return $this->data['body'];
	}
	function setBody($value) {
		$value = trim($value);

		if (	$value == NULL
				OR
				$this->Validator->isLength(	'body',
											$value,
											TTi18n::gettext('Incorrect Body length'),
											2,2048) ) {

			$this->data['body'] = $value;

			return FALSE;
		}

		return FALSE;
	}

	function getKeywords() {
		return $this->data['keywords'];
	}
	function setKeywords($value) {
		$value = trim($value);

		if (	$value == NULL
				OR
				$this->Validator->isLength(	'keywords',
											$value,
											TTi18n::gettext('Incorrect Keywords length'),
											2,1024) ) {

			$this->data['keywords'] = $value;

			return FALSE;
		}

		return FALSE;
	}

	function getPrivate() {
		return $this->fromBool( $this->data['private'] );
	}
	function setPrivate($bool) {
		$this->data['private'] = $this->toBool($bool);

		return true;
	}

}
?>
