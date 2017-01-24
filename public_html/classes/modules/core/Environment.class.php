<?php
/*********************************************************************************
 * This file is part of "Fairness", a Payroll and Time Management program.
 * Fairness is Copyright 2013 Aydan Coskun (aydan.ayfer.coskun@gmail.com)
 * Portions of this software are Copyright of T i m e T r e x Software Inc.
 * Fairness is a fork of "T i m e T r e x Workforce Management" Software.
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


/**
 * @package Core
 */
class Environment
{
    protected static $template_dir = 'templates';
    protected static $template_compile_dir = 'templates_c';

    public static function getHostName()
    {
        return Misc::getHostName(true);
    }

    public static function getDefaultInterfaceBaseURL()
    {
        return self::getBaseURL();
    }

    public static function getBaseURL()
    {
        global $config_vars;

        $retval = '/';

        if (isset($config_vars['path']['base_url'])) {
            if (substr($config_vars['path']['base_url'], -1) != '/') {
                $retval = $config_vars['path']['base_url'] . '/'; //Don't use directory separator here
            } else {
                $retval = $config_vars['path']['base_url'];
            }
        }

        return self::stripDuplicateSlashes($retval);
    }

    public static function stripDuplicateSlashes($path)
    {
        return preg_replace('/([^:])(\/{2,})/', '$1/', $path);
    }

    //Due to how the legacy interface is handled, we need to use the this function to determine the URL to redirect too,
    //as the base_url needs to be /interface most of the time, for images and such to load properly.

    public static function getCookieBaseURL()
    {
        //  "/fairness/interface"
        //  "/fairness/api/json"
        //  "/fairness" <- cookie must go here.
        $retval = str_replace('\\', '/', dirname(dirname(self::getAPIBaseURL()))); //PHP5 compatible. dirname(self::getAPIBaseURL(), 2) only works in PHP7. Also Windows tends to use backslashes in some cases, since this is a URL switch to forward slash always.

        if ($retval == '') {
            $retval = '/';
        }

        return $retval;
    }

    public static function getAPIBaseURL($api = null)
    {
        global $config_vars;

        //If "interface" appears in the base URL, replace it with API directory
        $base_url = str_replace(array('/interface', '/api'), '', $config_vars['path']['base_url']);

        if ($api == '') {
            if (defined('FAIRNESS_AMF_API') and FAIRNESS_AMF_API == true) {
                $api = 'amf';
            } elseif (defined('FAIRNESS_SOAP_API') and FAIRNESS_SOAP_API == true) {
                $api = 'soap';
            } elseif (defined('FAIRNESS_JSON_API') and FAIRNESS_JSON_API == true) {
                $api = 'json';
            }
        }

        $base_url = self::stripDuplicateSlashes($base_url . '/api/' . $api . '/');

        return $base_url;
    }

    //Returns the BASE_URL for the API functions.

    public static function getAPIURL($api)
    {
        return self::getAPIBaseURL($api) . 'api.php';
    }

    public static function getTemplateDir()
    {
        return self::getBasePath() . self::$template_dir . DIRECTORY_SEPARATOR;
    }

    public static function getBasePath()
    {
        //return dirname( __FILE__ ) . DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR .'..'. DIRECTORY_SEPARATOR;
        return str_replace('classes' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'core', '', dirname(__FILE__));
    }

    public static function getTemplateCompileDir()
    {
        global $config_vars;

        if (isset($config_vars['path']['templates'])) {
            return $config_vars['path']['templates'] . DIRECTORY_SEPARATOR;
        } else {
            return self::getBasePath() . self::$template_compile_dir . DIRECTORY_SEPARATOR;
        }
    }

    public static function getImagesPath()
    {
        return self::getBasePath() . DIRECTORY_SEPARATOR . 'interface' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;
    }

    public static function getImagesURL()
    {
        return self::getBaseURL() . 'images/';
    }

    public static function getStorageBasePath()
    {
        global $config_vars;

        return $config_vars['path']['storage'] . DIRECTORY_SEPARATOR;
    }

    public static function getLogBasePath()
    {
        global $config_vars;

        return $config_vars['path']['log'] . DIRECTORY_SEPARATOR;
    }
}
