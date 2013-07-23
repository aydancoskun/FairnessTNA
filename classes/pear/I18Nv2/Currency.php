<?php
// +----------------------------------------------------------------------+
// | PEAR :: I18Nv2 :: Currency                                           |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is available at http://www.php.net/license/3_0.txt              |
// | If you did not receive a copy of the PHP license and are unable      |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Copyright (c) 2004 Michael Wallner <mike@iworks.at>                  |
// +----------------------------------------------------------------------+
//
// $Id: Currency.php,v 1.1 2005/01/04 16:24:38 mike Exp $

/**
 * I18Nv2::Currency
 * 
 * @package     I18Nv2
 * @category    Internationalization
 */

require_once 'I18Nv2/CommonList.php';

/**
 * I18Nv2_Currency
 * 
 * @author      Michael Wallner <mike@php.net>
 * @version     $Revision: 1.1 $
 * @access      public
 * @package     I18Nv2
 */
class I18Nv2_Currency extends I18Nv2_CommonList
{
    /**
     * Load language file
     *
     * @access  protected
     * @return  bool
     * @param   string  $language
     */
    function loadLanguage($language)
    {
        return @include 'I18Nv2/Currency/' . $language . '.php';
    }
    
    /**
     * Change case of code key
     *
     * @access  protected
     * @return  string
     * @param   string  $code
     */
    function changeKeyCase($code)
    {
        return strToUpper($code);
    }
}
?>
