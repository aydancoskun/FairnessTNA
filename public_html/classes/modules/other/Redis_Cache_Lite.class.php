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
class Redis_Cache_Lite extends Cache_Lite
{
public $createdir = false;

    public function Redis_Cache_Lite($options = array(null))
    {
        $this->Cache_Lite($options);

        if (defined('ADODB_DIR')) {
            include_once(ADODB_DIR . '/adodb-csvlib.inc.php');
        }

        $this->redisConnectMaster();

        return true;
    }

    public function redisConnectMaster()
    {
        Debug::text('Connecting to REDIS Host: ' . $this->_redisHost, __FILE__, __LINE__, __METHOD__, 10);
        if (isset($this->_redisHost) and $this->_redisHost != '') {
            $split_server = explode(',', $this->_redisHost);
            $i = 0;
            foreach ($split_server as $server) {
                if ($i == 0) {
                    $this->_redisHostHost['master'] = $server;
                } else {
                    $this->_redisHostHost['slave_' . $i] = $server;
                }
                $i++;
            }
            //Debug::Arr($this->_redisHostHost, 'REDIS Hosts: ', __FILE__, __LINE__, __METHOD__, 10);

            return $this->redisConnect('master');
        }
    }

    public function redisConnect($key)
    {
        if (isset($this->_redisHostConn[$key]) and $this->_redisHostConn[$key] === false) {
            Debug::Text('Previous error connecting to the Redis database, not attempting again during this request...', __FILE__, __LINE__, __METHOD__, 1);
            return false;
        }

        try {
            global $config_vars;
            if (!isset($config_vars['database']['persistent_connections'])) {
                $config_vars['database']['persistent_connections'] = false;
            }

            $this->_redisHostConn[$key] = new Redis();

            //Try with 2 second timeout, we don't want redis to block requests if its down.
            if ($config_vars['database']['persistent_connections'] == true) {
                $connection_retval = $this->_redisHostConn[$key]->pconnect(trim($this->_redisHostHost[$key]), null, 2);
            } else {
                $connection_retval = $this->_redisHostConn[$key]->connect(trim($this->_redisHostHost[$key]), null, 2);
            }

            if ($connection_retval === true) {
                if (isset($this->_redisDB) and $this->_redisDB != '') {
                    if ($this->_redisHostConn[$key]->select($this->_redisDB) === false) {
                        //return $this->raiseError('Cache_Lite : Unable to switch redis DB to: '. $this->_redisDB, -2);  //In order to catch these we need to include PEAR.php all the time.
                        return false;
                    }
                    //else {
                    //	Debug::text('Switched REDIS DB to: '. $this->_redisDB, __FILE__, __LINE__, __METHOD__, 10);
                    //}
                }
                return $this->_redisHostConn[$key];
            } else {
                $this->_redisHostConn[$key] = false; //Prevent further connections from timing out during this request...
                Debug::Text('Error connecting to the Redis database! (a)', __FILE__, __LINE__, __METHOD__, 1);
            }
        } catch (Exception $e) {
            $this->_redisHostConn[$key] = false; //Prevent further connections from timing out during this request...
            Debug::Text('Error connecting to the Redis database! (b)', __FILE__, __LINE__, __METHOD__, 1);
            unset($e);
            //throw new DBError($e);
        }

        //return $this->raiseError('Cache_Lite : Unable to connect to redis host: '. $key, -2);  //In order to catch these we need to include PEAR.php all the time.
        return false;
    }

    public function setOption($name, $value)
    {
        $availableOptions = array('redisHost', 'redisDB', 'errorHandlingAPIBreak', 'hashedDirectoryUmask', 'hashedDirectoryLevel', 'automaticCleaningFactor', 'automaticSerialization', 'fileNameProtection', 'memoryCaching', 'onlyMemoryCaching', 'memoryCachingLimit', 'cacheDir', 'caching', 'lifeTime', 'fileLocking', 'writeControl', 'readControl', 'readControlType', 'pearErrorMode', 'hashedDirectoryGroup', 'cacheFileMode', 'cacheFileGroup');
        if (in_array($name, $availableOptions)) {
            $property = '_' . $name;
            $this->$property = $value;
        }
    }

    public function _unlink($file, $skip_master = false)
    {
        //When multiple redis servers are specified, we need to expire cache on them all.
        foreach ($this->_redisHostHost as $server_key => $value) {
            if ($skip_master == false or ($skip_master == true and $server_key != 'master')) {
                $redis = $this->redisConnect($server_key);
                //if ( !PEAR::isError($redis) ) {
                if (is_object($redis) and get_class($redis) == 'Redis') {
                    //Debug::text('Deleting REDIS as KEY: '. $this->_file .' Server Key: '. $server_key, __FILE__, __LINE__, __METHOD__, 10);
                    try {
                        if ($redis->del($this->_file) === false) {
                            //return $this->raiseError('Cache_Lite : Unable to delete cache file : '.$this->_file, -1);  //In order to catch these we need to include PEAR.php all the time.
                            return false;
                        }
                    } catch (Exception $e) {
                        Debug::Text('Redis Error: Message: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 1);
                    }
                }
            }
        }
        unset($value);//code standards

        return true;
    }

    public function writecache($filename, $contents, $debug = false)
    {
        return $this->save($contents, $filename, 'adodb');
    }

    public function save($data, $id = null, $group = 'default')
    {
        if ($this->_caching) {
            if ($this->_automaticSerialization) {
                $data = serialize($data);
            }
            if (isset($id)) {
                $this->_setFileName($id, $group);
            }
            if ($this->_memoryCaching) {
                $this->_memoryCacheAdd($data);
                if ($this->_onlyMemoryCaching) {
                    return true;
                }
            }
            if ($this->_automaticCleaningFactor > 0 and ($this->_automaticCleaningFactor == 1 or mt_rand(1, $this->_automaticCleaningFactor) == 1)) {
                $this->clean(false, 'old');
            }
            $res = $this->_write($data);

            if (is_object($res)) {
                // $res is a PEAR_Error object
                if (!($this->_errorHandlingAPIBreak)) {
                    return false; // we return false (old API)
                }
            }
            return $res;
        }
        return false;
    }

    public function _setFileName($id, $group)
    {
        //if ($this->_fileNameProtection) {
        //    $suffix = md5($group).'_'.md5($id);
        //} else {
        $suffix = $group . '_' . $id;
        //}

        $this->_fileName = $suffix;
        $this->_file = $suffix;
    }

    public function clean($group = false, $mode = 'ingroup', $skip_master = false)
    {
        //When multiple redis servers are specified, we need to expire cache on them all.
        foreach ($this->_redisHostHost as $server_key => $value) {
            if ($skip_master == false or ($skip_master == true and $server_key != 'master')) {
                $redis = $this->redisConnect($server_key);
                //if ( !PEAR::isError($redis) ) {
                if (is_object($redis) and get_class($redis) == 'Redis') {
                    try {
                        if ($group != '') {
                            $redis->eval('return redis.call(\'del\', unpack(redis.call(\'keys\', ARGV[1])))', array($group . '_*'));
                        } else {
                            $redis->flushdb(); //If no group is specified, flush all keys in DB.
                        }
                    } catch (Exception $e) {
                        Debug::Text('Redis Error: Message: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 1);
                    }
                }
            }
        }
        unset($value);//code standards

        return true;
    }

    public function _write($data)
    {
        $redis = $this->redisConnect('master');
        //if ( !PEAR::isError($redis) ) {
        if (is_object($redis) and get_class($redis) == 'Redis') {
            //Debug::text('Writing to REDIS as KEY: '. $this->_file, __FILE__, __LINE__, __METHOD__, 10);
            try {
                return $redis->set($this->_file, $data, $this->_lifeTime);
            } catch (Exception $e) {
                Debug::Text('Redis Error: Message: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 1);
            }
        }

        //return $this->raiseError('Cache_Lite : Unable to write cache file : '.$this->_file, -1); //In order to catch these we need to include PEAR.php all the time.
        return false;
    }


    /*
     * Support ADODB Cache module.
     */

    public function readcache($filename, &$err, $secs2cache, $rsClass)
    {
        $rs = explode("\n", $this->get($filename, 'adodb'));
        unset($rs[0]);
        $rs = join("\n", $rs);
        return unserialize($rs);
    } // do not set this to true unless you use temp directories in cache path

    public function get($id, $group = 'default', $doNotTestCacheValidity = false)
    {
        $this->_id = $id;
        $this->_group = $group;
        $data = false;
        if ($this->_caching) {
            $this->_setRefreshTime();
            $this->_setFileName($id, $group);
            clearstatcache();
            if ($this->_memoryCaching) {
                if (isset($this->_memoryCachingArray[$this->_file])) {
                    if ($this->_automaticSerialization) {
                        return unserialize($this->_memoryCachingArray[$this->_file]);
                    }
                    return $this->_memoryCachingArray[$this->_file];
                }
                if ($this->_onlyMemoryCaching) {
                    return false;
                }
            }
            $data = $this->_read();
            if (($data) and ($this->_memoryCaching)) {
                $this->_memoryCacheAdd($data);
            }
            if (($this->_automaticSerialization) and (is_string($data))) {
                $data = unserialize($data);
            }
            return $data;
        }
        return false;
    }

    public function _read()
    {
        $redis = $this->redisConnect('master');
        //if ( !PEAR::isError($redis) ) {
        if (is_object($redis) and get_class($redis) == 'Redis') {
            try {
                return $redis->get($this->_file);
            } catch (Exception $e) {
                Debug::Text('Redis Error: Message: ' . $e->getMessage(), __FILE__, __LINE__, __METHOD__, 1);
            }
        }

        //return $this->raiseError('Cache_Lite : Unable to read cache !', -2); //In order to catch these we need to include PEAR.php all the time.
        return false;
    }

    public function flushall($debug = false)
    {
        return $this->clean('adodb');
    }

    public function flushcache($filename, $debug = false)
    {
        return $this->remove($filename, 'adodb');
    }

    public function createdir($dir, $hash)
    {
        return true;
    }
}
