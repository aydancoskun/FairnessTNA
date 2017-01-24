<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at                              |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Ian Eure <ieure@php.net>                                    |
// |          Joe Stump <joe@joestump.net>                                |
// +----------------------------------------------------------------------+
//
// $Id: Type.php,v 1.22 2006/01/24 23:21:49 ieure Exp $

define('PAYMENT_PROCESS_CC_VISA', 100);
define('PAYMENT_PROCESS_CC_MASTERCARD', 101);
define('PAYMENT_PROCESS_CC_AMEX', 102);
define('PAYMENT_PROCESS_CC_DISCOVER', 103);
define('PAYMENT_PROCESS_CC_JCB', 104);
define('PAYMENT_PROCESS_CC_DINERS', 105);
define('PAYMENT_PROCESS_CC_CARTEBLANCHE', 106);
define('PAYMENT_PROCESS_CC_ENROUTE', 107);

define('PAYMENT_PROCESS_CK_SAVINGS', 1000);
define('PAYMENT_PROCESS_CK_CHECKING', 1001);

/**
 * Payment_Process_Type
 *
 * @author Joe Stump <joe@joestump.net>
 * @category Payment
 * @package Payment_Process
 * @version @version@
 */
class Payment_Process_Type
{
    // {{{ properties
    /**
     * $_type
     *
     * @var string $type Type of payment (ie. 'CreditCard' or 'eCheck')
     */
    public $_type = null;

    /**
     * $firstName
     *
     * @var string $firstName
     */
    public $firstName;

    /**
     * $lastName
     *
     * @var string $lastName
     */
    public $lastName;

    /**
     * $company
     *
     * @var string $company
     */
    public $company;

    /**
     * $address
     *
     * @var string $addres
     */
    public $address;

    /**
     * $city
     *
     * @var string $city
     */
    public $city;

    /**
     * $state
     *
     * @var string $state State/Province of customer
     */
    public $state;

    /**
     * $zip
     *
     * @var string $zip Zip/Postal code of customer
     */
    public $zip;

    /**
     * $country
     *
     * @var string $country Country code of customer (ie. US)
     */
    public $country;

    /**
     * $phone
     *
     * @var string $phone Phone number of customer
     */
    public $phone;

    /**
     * $fax
     *
     * @var string $fax Fax number of customer
     */
    public $fax;

    /**
     * $city
     *
     * @var string $email Email address of customer
     */
    public $email;

    /**
     * $ipAddress
     *
     * @var string $ipAddress Remote IP address of customer
     */
    public $ipAddress;
    // }}}
    // {{{ __construct()


    public static function &factory($type)
    {
        $class = 'Payment_Process_Type_' . $type;
        $file = 'Payment/Process/Type/' . $type . '.php';
        if (include_once($file)) {
            if (class_exists($class)) {
                $ret = new $class();
                return $ret;
            }
        }

        $ret = PEAR::raiseError('Invalid Payment_Process_Type: ' . $type);
        return $ret;
    }
    // }}}
    // {{{ Payment_Process_Type()

    /**
     * isValid
     *
     * Validate a payment type object
     *
     * @author Joe Stump <joe@joestump.net>
     * @access public
     * @param mixed $obj Type object to validate
     * @return mixed true on success, PEAR_Error on failure
     */
    public static function isValid($obj)
    {
        if (!is_a($obj, 'Payment_Process_Type')) {
            return PEAR::raiseError('Not a valid payment type');
        }

        $vars = get_object_vars($obj);
        foreach ($vars as $validate => $value) {
            $method = '_validate' . ucfirst($validate);
            if (method_exists($obj, $method)) {
                $result = $obj->$method();
                if (PEAR::isError($result)) {
                    return $result;
                }
            }
        }

        return true;
    }
    // }}}
    // {{{ &factory($type)
    /**
     * factory
     *
     * Creates and returns an instance of a payment type. If an error occurs
     * a PEAR_Error is returned.
     *
     * @author Joe Stump <joe@joestump.net>
     * @param string $type
     * @return mixed
     */
    // static function, avoid PHP strict error.
    public function Payment_Process_Type()
    {
        $this->__construct();
    }
    // }}}
    // {{{ isValid()

    public function __construct()
    {
    }
    // }}}
    // {{{ getType()

    /**
     * getType
     *
     * @author Joe Stump <joe@joestump.net>
     * @access public
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }
    // }}}
    // {{{ _validateEmail()
    /**
     * Validate an email address.
     *
     * @author Ian Eure <ieure@php.net>
     * @access private
     * @return boolean true on success, false on failure.
     */
    public function _validateEmail()
    {
        if (isset($this->email) && strlen($this->email)) {
            return Validate::email($this->email, false);
        }

        return true;
    }
    // }}}
    // {{{ _validateZip()
    /**
     * Validate the zip code.
     *
     * This only validates U.S. zipcodes; country must be set to 'us' for zip to
     * be validated.
     *
     * @author Ian Eure <ieure@php.net>
     * @access private
     * @return boolean true on success, false otherwise
     * @todo use Validate_*::postalCode() method
     */
    public function _validateZip()
    {
        if (isset($this->zip) && strtolower($this->country) == 'us') {
            #            return ereg('^[0-9]{5}(-[0-9]{4})?$', $this->zip);
            return preg_match('/^[0-9]{5}(-[0-9]{4})?$/', $this->zip);
        }

        return true;
    }
    // }}}
}
