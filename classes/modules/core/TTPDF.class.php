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

require_once(Environment::getBasePath() .'/classes/tcpdf/tcpdf.php');

//Automatically create TCPDF cache path if it doesn't exist.
if ( !file_exists( K_PATH_CACHE ) ) {
	mkdir( K_PATH_CACHE );
}

/**
 * @package Core
 */
class TTPDF extends tcpdf {
	/**
	 * @param $f
	 * @return number
	 */
	protected function _freadint( $f) {
		//Read a 4-byte integer from file
		$a = unpack('Ni', fread($f, 4));

		//Fixed bug in PHP v5.2.1 and less where it is returning a huge negative number.
		//See: http://ca3.php.net/manual/en/function.unpack.php
		//If you are trying to make unpack 'N' work with unsigned long on 64 bit machines, you should take a look to this bug:
		//http://bugs.php.net/bug.php?id=40543
		$b = sprintf("%b", $a['i']); // binary representation
		if ( strlen($b) == 64 ) {
			$new = substr($b, 33);
			$a['i'] = bindec($new);
		}
		return $a['i'];
	}

	/**
	 * TTPDF constructor.
	 * @param string $orientation
	 * @param string $unit
	 * @param string $format
	 * @param string $encoding
	 * @param bool $diskcache
	 */
	function __construct( $orientation='P', $unit='mm', $format='LETTER', $encoding='UTF-8', $diskcache=FALSE) {
		if ( TTi18n::getPDFDefaultFont() != 'freeserif' AND $encoding == 'ISO-8859-1' ) {
			parent::__construct($orientation, $unit, $format, FALSE, 'ISO-8859-1', $diskcache); //Make sure TCPDF constructor is called with all the arguments
		} else {
			parent::__construct($orientation, $unit, $format, TRUE, $encoding, $diskcache); //Make sure TCPDF constructor is called with all the arguments
		}
		Debug::Text('PDF Encoding: '. $encoding, __FILE__, __LINE__, __METHOD__, 10);

		/*
		if ( TTi18n::getPDFDefaultFont() == 'freeserif' ) {
			Debug::Text('Using unicode PDF: Font: freeserif Unicode: '. (int)$unicode .' Encoding: '. $encoding, __FILE__, __LINE__, __METHOD__, 10);
		} else {
			//If we're only using English, default to faster non-unicode settings.
			//unicode=FALSE and encoding='ISO-8859-1' is about 20-30% faster.
			Debug::Text('Using non-unicode PDF: Font: helvetica Unicode: '. (int)$unicode .' Encoding: '. $encoding, __FILE__, __LINE__, __METHOD__, 10);
			parent::__construct($orientation, $unit, $format, FALSE, 'ISO-8859-1', $diskcache); //Make sure TCPDF constructor is called with all the arguments
		}
		*/

		//Using freeserif font enabling font subsetting is slow and produces PDFs at least 1mb. Helvetica is fine though.
		$this->setFontSubsetting(TRUE); //When enabled, makes PDFs smaller, but severly slows down TCPDF if enabled. (+6 seconds per PDF)

		$this->SetCreator( APPLICATION_NAME.' '. getTTProductEditionName() .' v'. APPLICATION_VERSION );

		return TRUE;
	}

	/**
	 * TCPDF oddly enough defines standard header/footers, instead of disabling them
	 * in every script, just override them as blank here.
	 * @return bool
	 */
	function header() {
		return TRUE;
	}

	/**
	 * @return bool
	 */
	function footer() {
		return TRUE;
	}
}

?>
