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
 * $Revision: 2095 $
 * $Id: TTLog.class.php 2095 2008-09-01 07:04:25Z ipso $
 * $Date: 2008-09-01 00:04:25 -0700 (Mon, 01 Sep 2008) $
 */

/**
 * @package Core
 */


/*
 Config options:

 [mail]
 delivery_method = mail/smtp/soap
 smtp_host=mail.domain.com
 smtp_port=25
 smtp_username=test1
 smtp_password=testpass
*/
class TTMail {
	private $mime_obj = NULL;
	private $mail_obj = NULL;

	private $data = NULL;
	public $default_mime_config = array(
										'html_charset' => 'UTF-8',
										'text_charset' => 'UTF-8',
										'head_charset' => 'UTF-8',
										);

	function __construct() {
		//For some reason the EOL defaults to \r\n, which seems to screw with Amavis
		//This also prevents wordwrapping at 70 chars.
		if ( !defined('MAIL_MIMEPART_CRLF') ) {
			define('MAIL_MIMEPART_CRLF', "\n");
		}

		return TRUE;
	}

	function getMimeObject() {
		if ( $this->mime_obj == NULL ) {
			require_once('Mail/mime.php');
			$this->mime_obj = @new Mail_Mime();
		}

		return $this->mime_obj;
	}
	function getMailObject() {
		if ( $this->mail_obj == NULL ) {
			require_once('Mail.php');

			//Determine if use Mail/SMTP, or SOAP.
			$delivery_method = $this->getDeliveryMethod();

			if ( $delivery_method == 'mail' ) {
				$this->mail_obj = Mail::factory('mail');
			} elseif ( $delivery_method == 'smtp' ) {
				$smtp_config = $this->getSMTPConfig();

				$mail_config = array(
									'host' => $smtp_config['host'],
									'port' => $smtp_config['port'],
									);

				if ( isset($smtp_config['username']) AND $smtp_config['username'] != '' ) {
					$mail_config['user_name'] = $smtp_config['username'];
					$mail_config['password'] = $smtp_config['password'];
					$mail_config['auth'] = TRUE;
				}

				$this->mail_obj = Mail::factory('smtp', $mail_config );
				Debug::Arr($mail_config, 'SMTP Config: ', __FILE__, __LINE__, __METHOD__,10);
			}
		}

		return $this->mail_obj;
	}

	function getDeliveryMethod() {
		global $config_vars;

		$possible_values = array( 'mail','soap','smtp' );
		if ( isset( $config_vars['mail']['delivery_method'] ) AND in_array( strtolower( trim($config_vars['mail']['delivery_method']) ), $possible_values ) ) {
			return $config_vars['mail']['delivery_method'];
		}

		return 'soap'; //Default to SOAP as it has a better chance of working than mail/SMTP
	}

	function getSMTPConfig() {
		global $config_vars;

		$retarr = array(
						'host' => NULL,
						'port' => 25,
						'username' => NULL,
						'password' => NULL,
						);

		if ( isset( $config_vars['mail']['smtp_host'] ) ) {
			$retarr['host'] = $config_vars['mail']['smtp_host'];
		}

		if ( isset( $config_vars['mail']['smtp_port'] ) ) {
			$retarr['port'] = $config_vars['mail']['smtp_port'];
		}

		if ( isset( $config_vars['mail']['smtp_username'] ) ) {
			$retarr['username'] = $config_vars['mail']['smtp_username'];
		}
		if ( isset( $config_vars['mail']['smtp_password'] ) ) {
			$retarr['password'] = $config_vars['mail']['smtp_password'];
		}

		return $retarr;
	}

	function getMIMEHeaders() {
		$mime_headers = @$this->getMIMEObject()->headers( $this->getHeaders() );
		//Debug::Arr($this->data['headers'], 'MIME Headers: ', __FILE__, __LINE__, __METHOD__,10);
		return $mime_headers;
	}
	function getHeaders() {
		if ( isset( $this->data['headers'] ) ) {
			return $this->data['headers'];
		}

		return FALSE;
	}
	function setHeaders( $headers, $include_default = FALSE ) {
		$this->data['headers'] = $headers;

		if ( $include_default == TRUE ) {
			//May have to go to base64 encoding all data for proper UTF-8 support.
			$this->data['headers']['Content-type'] = 'text/html; charset="UTF-8"';
		}

		//Debug::Arr($this->data['headers'], 'Headers: ', __FILE__, __LINE__, __METHOD__,10);

		return TRUE;
	}

	function getTo() {
		if ( isset( $this->data['to'] ) ) {
			return $this->data['to'];
		}

		return FALSE;
	}
	function setTo( $email ) {
		$this->data['to'] = $email;

		return TRUE;
	}

	function getBody() {
		if ( isset( $this->data['body'] ) ) {
			return $this->data['body'];
		}

		return FALSE;
	}
	function setBody( $body ) {
		$this->data['body'] = $body;

		return TRUE;
	}

	function Send() {
		Debug::Text('Attempting to send email To: '. $this->getTo(), __FILE__, __LINE__, __METHOD__,10);

		if ( $this->getTo() == FALSE ) {
			Debug::Text('To Address invalid...', __FILE__, __LINE__, __METHOD__,10);
			return FALSE;
		}

		if ( $this->getBody() == FALSE ) {
			Debug::Text('Body invalid...', __FILE__, __LINE__, __METHOD__,10);
			return FALSE;
		}

		Debug::Text('Sending Email To: '. $this->getTo() .' Body Size: '. strlen( $this->getBody() ) .' Method: '. $this->getDeliveryMethod(), __FILE__, __LINE__, __METHOD__,10);

		if ( PRODUCTION == FALSE ) {
			Debug::Text('Not in production mode, not sending emails...', __FILE__, __LINE__, __METHOD__,10);
			//$to = 'root@localhost';
			return FALSE;
		}

		if ( DEMO_MODE == TRUE ) {
			Debug::Text('In DEMO mode, not sending emails...', __FILE__, __LINE__, __METHOD__,10);
			return FALSE;
		}

		switch ( $this->getDeliveryMethod() ) {
			case 'smtp':
			case 'mail':
				$send_retval = $this->getMailObject()->send( $this->getTo(), $this->getMIMEHeaders(), $this->getBody() );
				break;
			case 'soap':
				Debug::Text("Soap as mail transport diabled", __FILE__, __LINE__, __METHOD__,10);
/*			$ttsc = new TimeTrexSoapClient();
				$send_retval = $ttsc->sendEmail( $this->getTo(), $this->getMIMEHeaders(), $this->getBody() );
*/
				break;
		}

		if ( $send_retval == TRUE ) {
			return TRUE;
		}

		Debug::Arr($send_retval, 'Send Email Failed!', __FILE__, __LINE__, __METHOD__,10);
		return FALSE;
	}
}
?>
