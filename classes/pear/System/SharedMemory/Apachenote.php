<?php
/**
*
* The ApacheNote driver for SharedMemory
*
* PHP versions 4 and 5
*
* LICENSE: This source file is subject to version 3.0 of the PHP license
* that is available through the world-wide-web at the following URI:
* http://www.php.net/license/3_0.txt.  If you did not receive a copy of
* the PHP License and are unable to obtain it through the web, please
* send a note to license@php.net so we can mail you a copy immediately.
*
* @category   System
* @package    System_Sharedmemory
* @author     Evgeny Stepanischev <bolk@lixil.ru>
* @copyright  2005 Evgeny Stepanischev
* @license    http://www.php.net/license/3_0.txt  PHP License 3.0
* @version    CVS: $Id:$
* @link       http://pear.php.net/package/System_SharedMemory
*/

/**
*
* The methods PEAR SharedMemory uses to interact with PHP's apache extension
* for interacting with ApacheNote shared memory
*
* These methods overload the ones declared System_SharedMemory_Common
*
* @category   System
* @package    System_Sharedmemory
* @package    System_Sharedmemory
* @author     Evgeny Stepanischev <bolk@lixil.ru>
* @copyright  2005 Evgeny Stepanischev
* @license    http://www.php.net/license/3_0.txt  PHP License 3.0
* @version    CVS: $Id:$
* @link       http://pear.php.net/package/System_SharedMemory
*/

require_once 'System/SharedMemory/Common.php';

// {{{ class System_SharedMemory_Apachenote

class System_SharedMemory_Apachenote extends System_SharedMemory_Common
{
    // {{{ get()
    /**
     * returns value of variable in shared mem
     *
     * @param string $name name of variable
     *
     * @return mixed value of the variable
     * @access public
     */
    function get($name)
    {
        return unserialize(apache_note($name));
    }
    // }}}
    // {{{ set()

    /**
     * set value of variable in shared mem
     *
     * @param string $name  name of the variable
     * @param string $value value of the variable
     *
     * @return bool true on success
     * @access public
     */
    function set($name, $value)
    {
        apache_note($name, serialize($value));
        return true;
    }
    // }}}
    // {{{ rm()

    /**
     * remove variable from memory
     *
     * @param string $name  name of the variable
     *
     * @return bool true on success
     * @access public
     */
    function rm($name)
    {
        apache_note($name, null);
        return true;
    }
    // }}}
}
// }}}
?>