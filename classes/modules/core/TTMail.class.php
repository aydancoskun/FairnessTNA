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

	/**
	 * TTMail constructor.
	 */
	function __construct() {
		//For some reason the EOL defaults to \r\n, which seems to screw with Amavis
		//This also prevents wordwrapping at 70 chars.
		if ( !defined('MAIL_MIMEPART_CRLF') ) {
			define('MAIL_MIMEPART_CRLF', "\n");
		}

		return TRUE;
	}

	/**
	 * @return Mail_Mime|null
	 */
	function getMimeObject() {
		if ( $this->mime_obj == NULL ) {
			require_once('Mail/mime.php');
			$this->mime_obj = @new Mail_Mime();
		}

		return $this->mime_obj;
	}

	/**
	 * @return null|object
	 */
	function getMailObject() {
		if ( $this->mail_obj == NULL ) {
			require_once('Mail.php');

			//Determine if use Mail/SMTP, or SOAP.
			$delivery_method = $this->getDeliveryMethod();

			if ( $delivery_method == 'mail' ) {
				$this->mail_obj = Mail::factory('mail');
			} elseif ( $delivery_method == 'sendmail' ) {
				$this->mail_obj = Mail::factory('sendmail');
			} elseif ( $delivery_method == 'smtp' ) {
				$smtp_config = $this->getSMTPConfig();

				$mail_config = array(
									'host' => $smtp_config['host'],
									'port' => $smtp_config['port'],
									);

				if ( isset($smtp_config['username']) AND $smtp_config['username'] != '' ) {
					//Removed 'user_name' as it wasn't working with postfix.
					$mail_config['username'] = $smtp_config['username'];
					$mail_config['password'] = $smtp_config['password'];
					$mail_config['auth'] = TRUE;
				}

				//Allow self-signed TLS certificates by default, which were disabled by default in PHP v5.6 -- See comments here: http://php.net/manual/en/migration56.openssl.php
				//This should fix error messages like: authentication failure [SMTP: STARTTLS failed (code: 220, response: 2.0.0 SMTP server ready)
				$mail_config['socket_options'] = array( 'ssl' => array( 'verify_peer' => FALSE, 'verify_peer_name' => FALSE, 'allow_self_signed' => TRUE ) );

				$this->mail_obj = Mail::factory('smtp', $mail_config );
				Debug::Arr($mail_config, 'SMTP Config: ', __FILE__, __LINE__, __METHOD__, 10);
			}
		}

		return $this->mail_obj;
	}

	/**
	 * @return string
	 */
	function getDeliveryMethod() {
		global $config_vars;

		$possible_values = array( 'mail', 'soap', 'smtp', 'sendmail' );
		if ( isset( $config_vars['mail']['delivery_method'] ) AND in_array( strtolower( trim($config_vars['mail']['delivery_method']) ), $possible_values ) ) {
			return $config_vars['mail']['delivery_method'];
		}

		if ( DEPLOYMENT_ON_DEMAND == TRUE ) {
			return 'mail';
		}

		return 'soap'; //Default to SOAP as it has a better chance of working than mail/SMTP
	}

	/**
	 * @return array
	 */
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

	/**
	 * @return array
	 */
	function getMIMEHeaders() {
		$mime_headers = @$this->getMIMEObject()->headers( $this->getHeaders(), TRUE );
		//Debug::Arr($this->data['headers'], 'MIME Headers: ', __FILE__, __LINE__, __METHOD__, 10);
		return $mime_headers;
	}

	/**
	 * @return bool
	 */
	function getHeaders() {
		if ( isset( $this->data['headers'] ) ) {
			return $this->data['headers'];
		}

		return FALSE;
	}

	/**
	 * @param $headers
	 * @param bool $include_default
	 * @return bool
	 */
	function setHeaders( $headers, $include_default = FALSE ) {
		$this->data['headers'] = $headers;

		if ( $include_default == TRUE ) {
			//May have to go to base64 encoding all data for proper UTF-8 support.
			$this->data['headers']['Content-type'] = 'text/html; charset="UTF-8"';
		}

		//Debug::Arr($this->data['headers'], 'Headers: ', __FILE__, __LINE__, __METHOD__, 10);

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function getTo() {
		if ( isset( $this->data['to'] ) ) {
			return $this->data['to'];
		}

		return FALSE;
	}

	/**
	 * @param $email
	 * @return bool
	 */
	function setTo( $email ) {
		$this->data['to'] = $email;

		return TRUE;
	}

	/**
	 * @return bool
	 */
	function getBody() {
		if ( isset( $this->data['body'] ) ) {
			return $this->data['body'];
		}

		return FALSE;
	}

	/**
	 * @param $body
	 * @return bool
	 */
	function setBody( $body ) {
		$this->data['body'] = $body;

		return TRUE;
	}

	/**
	 * Adds a plain text body to an HTML email stating that it contains HTML content.
	 * @return mixed
	 */
	function setDefaultTXTBody() {
		return @$this->getMIMEObject()->setTXTBody( TTi18n::getText('This email contains HTML content, please open in a HTML enabled email viewer.') ); //Having a text/plain body helps reduce spam score.
	}

	/**
	 * Extracts just the email address part from a string that may contain the name part, etc...
	 * @param $address
	 * @return mixed
	 */
	function parseEmailAddress( $address ) {
		if ( preg_match('/(?<=[<\[]).*?(?=[>\]]$)/', $address, $match) ) {
			$retval = $match[0];
		} else {
			$retval = $address;
		}

		return filter_var($retval, FILTER_VALIDATE_EMAIL); //Make sure we filter the email address here, so if using -f params, we aren't exploitable, for example an email address like: "Attacker -Param2 -Param3"@test.com
	}

	/**
	 * @param bool $force
	 * @return bool
	 */
	function Send( $force = FALSE ) {
		global $config_vars;
		Debug::Arr($this->getTo(), 'Attempting to send email To: ', __FILE__, __LINE__, __METHOD__, 10);

		if ( $this->getTo() == FALSE ) {
			Debug::Text('To Address invalid...', __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}

		if ( $this->getBody() == FALSE ) {
			Debug::Text('Body invalid...', __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}

		Debug::Text('Sending Email: Body Size: '. strlen( $this->getBody() ) .' Method: '. $this->getDeliveryMethod() .' To: ', __FILE__, __LINE__, __METHOD__, 10);

		if ( PRODUCTION == FALSE AND $force !== TRUE ) {
			Debug::Text('Not in production mode, not sending emails...', __FILE__, __LINE__, __METHOD__, 10);
			//$to = 'root@localhost';
			return FALSE;
		}

		if ( DEMO_MODE == TRUE ) {
			Debug::Text('In DEMO mode, not sending emails...', __FILE__, __LINE__, __METHOD__, 10);
			return FALSE;
		}

		//if ( !isset($this->data['headers']['Date']) ) {
		//	$this->data['headers']['Date'] = date( 'D, d M Y H:i:s O');
		//}

		if ( !is_array( $this->getTo() ) ) {
			$to = array( $this->getTo() );
		} else {
			//Even though the RFC says the local part of email address is case sensitive, it should never really be required in the real-world.
			//This prevents duplicate emails from being sent to email@mydomain.com and Email@mydomain.com or EMAIL@mydomain.com
			$to = Misc::arrayIUnique( $this->getTo() );
		}

		//When using SMTP, we have to manually send to each envelope-to, but make sure the original TO header is set.
		$secondary_to = array();
		if ( $this->getDeliveryMethod() == 'smtp' AND isset($this->data['headers']['Cc']) AND $this->data['headers']['Cc'] != '' ) {
			$secondary_to = array_merge( $secondary_to, array_map('trim', explode(',', $this->data['headers']['Cc'] ) ) );
		}
		if ( $this->getDeliveryMethod() == 'smtp' AND isset($this->data['headers']['Bcc']) AND $this->data['headers']['Bcc'] != '' ) {
			$secondary_to = array_merge( $secondary_to, array_map('trim', explode(',', $this->data['headers']['Bcc'] ) ) );
		}
		$secondary_to = array_diff( $secondary_to, $to ); //Make sure the CC/BCC doesn't contain any of the TO addresses, so we don't send duplicate emails.

		$i = 0;
		foreach( $to as $recipient ) {
			Debug::Text($i .'. Recipient: '. $recipient, __FILE__, __LINE__, __METHOD__, 10);
			//Make sure its at least a partially valid email address, otherwise just skip without returning FALSE.
			//  Without this a CC recipient of '"John Doe" <>' will fail and return FALSE making other functions think email failed.
			if ( strpos( $recipient, '@' ) === FALSE ) {
				Debug::Text('  Recipient email address is invalid, skipping...', __FILE__, __LINE__, __METHOD__, 10);
				continue;
			}

			$this->data['headers']['To'] = $recipient; //Always set the TO header to the primary recipient. When using SMTP method it won't do that automatically. We shouldn't be setting this in the case of a Bcc, then its not blind anymore.

			//Check to see if they want to force a return-path for better bounce handling.
			//However if the envelope from header does not match the From header
			//It may trigger spam filtering due to email mismatch/forgery (EDT_SDHA_ADR_FRG)
			if ( !isset($this->data['headers']['Return-Path']) AND isset($config_vars['other']['email_return_path_local_part']) AND $config_vars['other']['email_return_path_local_part'] != '' ) {
				$this->data['headers']['Return-Path'] = Misc::getEmailReturnPathLocalPart( $recipient ) .'@'. Misc::getEmailDomain();
			}

			//Debug::Arr($this->getMIMEHeaders(), 'Sending Email To: '. $recipient, __FILE__, __LINE__, __METHOD__, 10);
			switch ( $this->getDeliveryMethod() ) {
				case 'smtp':
				case 'sendmail':
				case 'mail':
					if ( $this->getDeliveryMethod() == 'mail' ) {
						$this->getMailObject()->_params = '-t'; //The -t option specifies that exim should build the recipients list from the 'To', 'Cc', and 'Bcc' headers rather than from the arguments list.
						if ( isset($this->data['headers']['Return-Path']) ) {
							$this->getMailObject()->_params .= ' -f'. $this->parseEmailAddress( $this->data['headers']['Return-Path'] );
						} elseif ( isset($this->data['headers']['From']) ) {
							$this->getMailObject()->_params .= ' -f'. $this->parseEmailAddress( $this->data['headers']['From'] );
						}
					}

					$send_retval = $this->getMailObject()->send( $recipient, $this->getMIMEHeaders(), $this->getBody() );
					if ( PEAR::isError($send_retval) ) {
						Debug::Text('Send Email Failed... Error: '. $send_retval->getMessage(), __FILE__, __LINE__, __METHOD__, 10);
						$send_retval = FALSE;
					}

					//When using SMTP, we have to manually send to each envelope-to, but make sure the original TO header is set.
					if ( $this->getDeliveryMethod() == 'smtp' AND isset($secondary_to) AND is_array($secondary_to) AND count($secondary_to) > 0 ) {
						$x = 0;
						foreach( $secondary_to as $cc_key => $cc_recipient ) {
							//Make sure its at least a partially valid email address, otherwise just skip without returning FALSE.
							//  Without this a CC recipient of '"John Doe" <>' will fail and return FALSE making other functions think email failed.
							if ( strpos( $cc_recipient, '@' ) === FALSE ) {
								Debug::Text('  CC Recipient email address is invalid, skipping...', __FILE__, __LINE__, __METHOD__, 10);
								continue;
							}

							Debug::Text('  '. $x .'. CC Recipient: '. $cc_recipient, __FILE__, __LINE__, __METHOD__, 10);
							$send_retval = $this->getMailObject()->send( $cc_recipient, $this->getMIMEHeaders(), $this->getBody() );
							if ( PEAR::isError( $send_retval ) ) {
								Debug::Text( 'Send Email Failed... Error: ' . $send_retval->getMessage(), __FILE__, __LINE__, __METHOD__, 10 );
								$send_retval = FALSE;
							}
							unset($secondary_to[$cc_key]); //Remove CC recipient from array so we don't send it multiple times if there are multiple recipients.
							$x++;
						}
					}
					break;
			}

			if ( $send_retval != TRUE ) {
				Debug::Arr($send_retval, 'Send Email Failed To: '. $recipient, __FILE__, __LINE__, __METHOD__, 10);
			}

			$i++;
		}

		if ( $send_retval == TRUE ) {
			return TRUE;
		}

		Debug::Arr($send_retval, 'Send Email Failed!', __FILE__, __LINE__, __METHOD__, 10);
		return FALSE;
	}
}
?>
