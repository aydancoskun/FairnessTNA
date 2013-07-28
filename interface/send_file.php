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
 * $Revision: 9851 $
 * $Id: send_file.php 9851 2013-05-10 20:43:50Z ipso $
 * $Date: 2013-05-10 13:43:50 -0700 (Fri, 10 May 2013) $
 */
require_once('../includes/global.inc.php');

require_once('PEAR.php');
require_once('HTTP/Download.php');

extract	(FormVariables::GetVariables(
										array	(
												'action',
												'api', //Called from Flex
												'object_type',
												'object_id',
												'parent_id',
												) ) );

if ( isset($api) AND $api == TRUE ) {
	require_once('../includes/API.inc.php');
}

$object_type = strtolower($object_type);

if ( $object_type != 'primary_company_logo' AND $object_type != 'copyright' ) {
	$skip_message_check = TRUE;
	require_once(Environment::getBasePath() .'includes/Interface.inc.php');
}

switch ($object_type) {
	case 'document':
		Debug::Text('Document...', __FILE__, __LINE__, __METHOD__,10);

		$drlf = TTnew( 'DocumentRevisionListFactory' );
		$drlf->getByIdAndDocumentId( $object_id, $parent_id );
		Debug::Text('Record Count: '. $drlf->getRecordCount(), __FILE__, __LINE__, __METHOD__,10);
		if ( $drlf->getRecordCount() == 1 ) {
			//echo "File Name: $file_name<br>\n";
			$dr_obj = $drlf->getCurrent();

			$file_name = $dr_obj->getStoragePath().$dr_obj->getLocalFileName();
			Debug::Text('File Name: '. $file_name .' Mime: '. $dr_obj->getMimeType(), __FILE__, __LINE__, __METHOD__,10);
			if ( file_exists($file_name) ) {
				$params['file'] = $file_name;
				$params['ContentType'] = $dr_obj->getMimeType();
				$params['ContentDisposition'] = array( HTTP_DOWNLOAD_ATTACHMENT, $dr_obj->getRemoteFileName() );
				$params['cache'] = FALSE;
			} else {
				Debug::Text('File does not exist... File Name: '. $file_name .' Mime: '. $dr_obj->getMimeType(), __FILE__, __LINE__, __METHOD__,10);
			}
		}
		Debug::writeToLog(); //Write to log when downloading documents.
		break;
	case 'client_payment_signature':
		Debug::Text('Client Payment Signature...', __FILE__, __LINE__, __METHOD__,10);

		$cplf = TTnew( 'ClientPaymentListFactory' );
		$cplf->getByIdAndClientId($object_id, $parent_id);
		if ( $cplf->getRecordCount() == 1 ) {
			//echo "File Name: $file_name<br>\n";
			$cp_obj = $cplf->getCurrent();

			$file_name = $cp_obj->getSignatureFileName();
			Debug::Text('File Name: '. $file_name, __FILE__, __LINE__, __METHOD__,10);
			if ( file_exists($file_name) ) {
				$params['file'] = $file_name;
				$params['ContentType'] = 'image/png';
				$params['ContentDisposition'] = array( HTTP_DOWNLOAD_ATTACHMENT, 'signature.png' );
				$params['cache'] = FALSE;
			}
		}
		break;
	case 'invoice_config':
		Debug::Text('Invoice Config...', __FILE__, __LINE__, __METHOD__,10);

		$icf = TTNew('InvoiceConfigFactory');
		$file_name = $icf->getLogoFileName( $current_company->getId() );
		Debug::Text('File Name: '. $file_name, __FILE__, __LINE__, __METHOD__,10);
		if ( file_exists($file_name) ) {
			$params['file'] = $file_name;
			$params['cache'] = TRUE;
		}
		break;
	case 'company_logo':
		Debug::Text('Company Logo...', __FILE__, __LINE__, __METHOD__,10);

		$cf = TTnew( 'CompanyFactory' );
		$file_name = $cf->getLogoFileName( $current_company->getId() );
		Debug::Text('File Name: '. $file_name, __FILE__, __LINE__, __METHOD__,10);
		if ( $file_name != '' AND file_exists($file_name) ) {
			$params['file'] = $file_name;
			$params['ContentType'] = 'image/'. strtolower( pathinfo($file_name, PATHINFO_EXTENSION) );
			$params['ContentDisposition'] = array( HTTP_DOWNLOAD_INLINE, $file_name );
			$params['cache'] = TRUE;
		}
		break;
	case 'primary_company_logo':
		Debug::Text('Primary Company Logo...', __FILE__, __LINE__, __METHOD__,10);

		$cf = TTnew( 'CompanyFactory' );
		$file_name = $cf->getLogoFileName( PRIMARY_COMPANY_ID, TRUE, TRUE );
		Debug::Text('File Name: '. $file_name, __FILE__, __LINE__, __METHOD__,10);
		if ( $file_name != '' AND file_exists($file_name) ) {
			$params['file'] = $file_name;
			$params['ContentType'] = 'image/'. strtolower( pathinfo($file_name, PATHINFO_EXTENSION) );
			$params['ContentDisposition'] = array( HTTP_DOWNLOAD_INLINE, $file_name );
			$params['cache'] = TRUE;
		}
		break;
	case 'user_photo':
		Debug::Text('User Photo...', __FILE__, __LINE__, __METHOD__,10);

		$uf = TTnew( 'UserFactory' );
		$file_name = $uf->getPhotoFileName( $current_company->getId(), $object_id );
		Debug::Text('File Name: '. $file_name, __FILE__, __LINE__, __METHOD__,10);
		if ( $file_name != '' AND file_exists($file_name) ) {
			$params['file'] = $file_name;
			$params['ContentType'] = 'image/'. strtolower( pathinfo($file_name, PATHINFO_EXTENSION) );
			$params['ContentDisposition'] = array( HTTP_DOWNLOAD_INLINE, $file_name );
			$params['cache'] = TRUE;
		}
		break;
	case 'copyright':
		Debug::Text('Copyright Logo...', __FILE__, __LINE__, __METHOD__,10);

		$file_name = Environment::getImagesPath().'/powered_by.jpg';
		Debug::Text('File Name: '. $file_name, __FILE__, __LINE__, __METHOD__,10);
		if ( $file_name != '' AND file_exists($file_name) ) {
			$params['file'] = $file_name;
			$params['contentdisposition'] = 'attachment; filename=copyright.jpg';
			$params['data'] = file_get_contents($file_name);
			$params['cache'] = TRUE;
		}
		break;
	case 'copyright_wide':
	case 'smcopyright':
		Debug::Text('Copyright Logo...', __FILE__, __LINE__, __METHOD__,10);
		$file_name = Environment::getImagesPath().'/powered_by_wide.png';
		Debug::Text('File Name: '. $file_name, __FILE__, __LINE__, __METHOD__,10);
		if ( $file_name != '' AND file_exists($file_name) ) {
			$params['file'] = $file_name;
			$params['contentdisposition'] = 'attachment; filename=copyright.jpg';
			$params['data'] = file_get_contents($file_name);
			$params['cache'] = TRUE;
		}
		break;

	default:
		break;
}

//Debug::Arr($params, 'Download Params:', __FILE__, __LINE__, __METHOD__,10);
if ( isset($params) ) {
	HTTP_Download::staticSend($params);
} else {
	echo "File does not exist, unable to download!<br>\n";
	Debug::writeToLog();
}
?>
