<?php
// Copyright (c) 2015 Mike Benoit, all rights reserved
/* ******************************************************************************
    Released under both BSD license and Lesser GPL library license.
    Whenever there is any discrepancy between the two licenses,
    the BSD license will take precedence.
*******************************************************************************/
/**
 * ADOdb loadbalancer is a class that allows the user to do read/write splitting and load balancing across multiple connections.
 * It can handle and load balance any number of master or slaves, including dealing with connection failures.
 *
 * Last Editor: $Author: Mike Benoit $
 * @author Mike Benoit
 * @version $Revision: 1.0 $
 *
 */
/*
 * Example Usage:
 *  $db = new ADOdbLoadBalancer( 'postgres8' );
 *  $db_connection_obj = new ADOdbLoadBalancerConnection( 'master', 10, $dsn ); //Master with weight of 10
 *  $db_connection_obj->getADODbObject()->SetFetchMode(ADODB_FETCH_ASSOC); //Pass specific settings to the ADOdb object itself.
 *  $db->addConnection( $db_connection_obj );
 *
 *  $db_connection_obj = new ADOdbLoadBalancerConnection( 'slave', 100, $dsn ); //Slave with weight of 100
 *  $db_connection_obj->getADODbObject()->SetFetchMode(ADODB_FETCH_ASSOC); //Pass specific settings to the ADOdb object itself.
 *  $db->addConnection( $db_connection_obj );
 *
 *  $db_connection_obj = new ADOdbLoadBalancerConnection( 'slave', 100, $dsn ); //Slave with weight of 100
 *  $db_connection_obj->getADODbObject()->SetFetchMode(ADODB_FETCH_ASSOC); //Pass specific settings to the ADOdb object itself.
 *  $db->addConnection( $db_connection_obj );
 *
 *  //Perform ADODB calls as normal..
 *  $db->Excute( 'SELECT * FROM MYTABLE' );
 */

class ADOdbLoadBalancer
{
    protected $connections = false;
    protected $connections_master = false; //Links to just master connections
    protected $connections_slave = false; //Links to just slave connections

    protected $total_connections = array('all' => 0, 'master' => 0, 'slave' => 0);
    protected $total_connection_weights = array('all' => 0, 'master' => 0, 'slave' => 0);

    protected $enable_sticky_sessions = true; //Once a master or slave connection is made, stick to that connection for the entire request.
    protected $pinned_connection_id = false; //When in transactions, always use this connection.
    protected $last_connection_id = array('master' => false, 'slave' => false, 'all' => false);

    protected $session_variables = false; //Session variables that must be maintained across all connections, ie: SET TIME ZONE.

    protected $blacklist_functions = false; //List of functions to blacklist as write-only (must run on master) **NOT YET IMPLEMENTED**

    protected $user_defined_session_init_sql = false; //Called immediately after connecting to any DB.

    public function setBlackListFunctions($arr)
    {
        $this->blacklist_functions = (array)$arr;
        return true;
    }

    public function setSessionInitSQL($sql)
    {
        $this->user_defined_session_init_sql[] = $sql;
        return true;
    }

    public function addConnection($obj)
    {
        if ($obj instanceof ADOdbLoadBalancerConnection) {
            $this->connections[] = $obj;
            end($this->connections);
            $i = key($this->connections);

            $this->total_connections[$obj->type]++;
            $this->total_connections['all']++;

            $this->total_connection_weights[$obj->type] += abs($obj->weight);
            $this->total_connection_weights['all'] += abs($obj->weight);

            if ($obj->type == 'master') {
                $this->connections_master[] = $i;
            } else {
                $this->connections_slave[] = $i;
            }

            return true;
        }

        throw new Exception('Connection object is not an instance of ADOdbLoadBalancerConnection');

        return false;
    }

    public function setSessionVariable($name, $value, $execute_immediately = true)
    {
        $this->session_variables[$name] = $value;

        if ($execute_immediately == true) {
            return $this->executeSessionVariables();
        } else {
            return true;
        }
    }

    private function executeSessionVariables($adodb_obj = false)
    {
        if (is_array($this->session_variables)) {
            $sql = '';
            foreach ($this->session_variables as $name => $value) {
                //$sql .= 'SET SESSION '. $name .' '. $value;
                //MySQL uses: SET SESSION foo_bar='foo'
                //PGSQL uses: SET SESSION foo_bar 'foo'
                //So leave it up to the user to pass the proper value with '=' if needed.
                //This may be a candidate to move into ADOdb proper.
                $sql .= 'SET SESSION ' . $name . ' ' . $value;
            }

            //Debug::Text( 'Session SQL: '. $sql, __FILE__, __LINE__, __METHOD__, 10);
            if ($adodb_obj !== false) {
                return $adodb_obj->Execute($sql);
            } else {
                return $this->ClusterExecute($sql);
            }
        }

        return false;
    }

    public function ClusterExecute($sql, $inputarr = false, $return_all_results = false, $existing_connections_only = true)
    {
        if (is_array($this->connections) and count($this->connections) > 0) {
            foreach ($this->connections as $key => $connection_obj) {
                if ($existing_connections_only == false or ($existing_connections_only == true and $connection_obj->getADOdbObject()->_connectionID !== false)) {
                    $adodb_obj = $this->_getConnection($key);
                    if (is_object($adodb_obj)) {
                        //Debug::Text( 'ADOdb GlobalExceute on Connection ID: '. $key, __FILE__, __LINE__, __METHOD__, 10);
                        $result_arr[] = $adodb_obj->Execute($sql, $inputarr);
                    }
                }
            }

            if (isset($result_arr) and $return_all_results == true) {
                //Debug::Text( '  Returning all results...', __FILE__, __LINE__, __METHOD__, 10);
                return $result_arr;
            } else {
                //Loop through all results checking to see if they match, if they do return the first one
                //otherwise return an array of all results.
                if (isset($result_arr)) {
                    foreach ($result_arr as $result) {
                        if ($result == false) {
                            //Debug::Text( '  Results differ, returning array of results...', __FILE__, __LINE__, __METHOD__, 10);
                            return $result_arr;
                        }
                    }

                    //Debug::Text( '  Results all match, returning first result...', __FILE__, __LINE__, __METHOD__, 10);
                    return $result_arr[0];
                } else {
                    //When using lazy connections, there are cases where setSessionVariable() is called early on, but there are no connections to execute the queries on yet.
                    //This captures that case and forces a RETURN TRUE to occur. As likely the queries will be exectued as soon as a connection is established.
                    Debug::Text('No active connections execute query on yet...', __FILE__, __LINE__, __METHOD__, 10);
                    return true;
                }
            }
        }

        //Debug::Text( 'No results...', __FILE__, __LINE__, __METHOD__, 10);
        return false;
    }

    public function _getConnection($connection_id)
    {
        if (isset($this->connections[$connection_id])) {
            $connection_obj = $this->connections[$connection_id];
            $adodb_obj = $connection_obj->getADOdbObject();
            if (is_object($adodb_obj) and $adodb_obj->_connectionID == false) {
                //Debug::Text('ADOdb initiating new connection...' , __FILE__, __LINE__, __METHOD__, 10);
                try {
                    if ($connection_obj->persistent_connection == true) {
                        $connection_result = $adodb_obj->Pconnect($connection_obj->host, $connection_obj->user, $connection_obj->password, $connection_obj->database);
                    } else {
                        $connection_result = $adodb_obj->Connect($connection_obj->host, $connection_obj->user, $connection_obj->password, $connection_obj->database);
                    }
                } catch (Exception $e) {
                    //Connection error, see if there are other connections to try still.
                    Debug::Text('ADOdb connection FAILED! Total Connections: ' . count($this->connections), __FILE__, __LINE__, __METHOD__, 10);
                    throw $e; //No connections left, reThrow exception so application can catch it.
                }

                if (is_array($this->user_defined_session_init_sql)) {
                    foreach ($this->user_defined_session_init_sql as $session_init_sql) {
                        $adodb_obj->Execute($session_init_sql);
                    }
                }
                $this->executeSessionVariables($adodb_obj);
            }

            //Debug::Text('Returning connection of type: '. $connection_obj->type .' Host: '. $connection_obj->host .' ID: '. $connection_id, __FILE__, __LINE__, __METHOD__, 10);
            return $adodb_obj;
        } else {
            throw new Exception('Unable to return Connection object...');
        }

        return false;
    }

    public function Execute($sql, $inputarr = false)
    {
        $type = 'master';
        $pin_connection = null;

        $sql = trim($sql); //Prevent leading spaces from causing isReadOnlyQuery/stripos from failing.

        //SELECT queries that can write and therefore must be run on MASTER.
        //SELECT ... FOR UPDATE;
        //SELECT ... INTO ...
        //SELECT .. LOCK IN ... (MYSQL)
        if ($this->isReadOnlyQuery($sql) == true) {
            $type = 'slave';
        } elseif (stripos($sql, 'SET') === 0) {
            //SET SQL statements should likely use setSessionVariable() instead,
            //so state is properly maintained across connections, especially when they are lazily created.
            return $this->ClusterExecute($sql, $inputarr);
        }

        $adodb_obj = $this->getConnection($type, $pin_connection);
        if ($adodb_obj !== false) {
            return $adodb_obj->Execute($sql, $inputarr);
        }

        return false;
    }

    public function isReadOnlyQuery($sql)
    {
        if (stripos($sql, 'SELECT') === 0 and stripos($sql, 'FOR UPDATE') === false and stripos($sql, ' INTO ') === false and stripos($sql, 'LOCK IN') === false) {
            return true;
        }

        return false;
    }

    //Allow setting session variables that are maintained across connections.

    public function getConnection($type = 'master', $pin_connection = null, $force_connection_id = false)
    {
        while (($type == 'master' and $this->total_connections['master'] > 0) or ($type == 'slave' and $this->total_connections['all'] > 0)) {
            if ($this->pinned_connection_id !== false) {
                $connection_id = $this->pinned_connection_id;
            } else {
                $connection_id = $this->getLoadBalancedConnection($type);
            }

            if ($connection_id !== false) {
                try {
                    $adodb_obj = $this->_getConnection($connection_id);
                    //$connection_obj = $this->connections[$connection_id];
                    break;
                } catch (Exception $e) {
                    //Connection error, see if there are other connections to try still.
                    Debug::Text('ADOdb connection FAILED! Total Connections: ' . count($this->connections), __FILE__, __LINE__, __METHOD__, 10);
                    $this->removeConnection($connection_id);
                    if (($type == 'master' and $this->total_connections['master'] == 0) or ($type == 'slave' and $this->total_connections['all'] == 0)) {
                        throw $e;
                    }
                }
            } else {
                throw Exception('Connection ID is invalid!');
            }
        }

        $this->last_connection_id[$type] = $connection_id;

        if ($pin_connection === true) {
            //Debug::Text('Pinning Connection: '. $connection_id .' Type: '. $type, __FILE__, __LINE__, __METHOD__, 10);
            $this->pinned_connection_id = $connection_id;
        } elseif ($pin_connection === false and $adodb_obj->transOff <= 1) { //UnPin connection only if we are 1 level deep in a transaction.
            //Debug::Text('UnPinning Connection: '. $this->pinned_connection_id, __FILE__, __LINE__, __METHOD__, 10);
            $this->pinned_connection_id = false;

            //When unpinning connection, reset last_connection_id so slave queries don't get stuck on the master.
            $this->last_connection_id['master'] = false;
            $this->last_connection_id['slave'] = false;
        }

        //Debug::Text('Returning connection of type: '. $connection_obj->type .' Host: '. $connection_obj->host .' ID: '. $connection_id, __FILE__, __LINE__, __METHOD__, 10);
        return $adodb_obj;
    }

    public function getLoadBalancedConnection($type)
    {
        if ($this->total_connections == 0) {
            $connection_id = 0;
        } else {
            if ($this->enable_sticky_sessions == true and $this->last_connection_id[$type] !== false) {
                $connection_id = $this->last_connection_id[$type];
                //Debug::Text('  Using sticky session connection ID: '. $connection_id .' Type: '. $type, __FILE__, __LINE__, __METHOD__, 1);
            } else {
                if ($type == 'master' and $this->total_connections['master'] == 1) {
                    //Debug::Text('  Only one master connection availabe, using it...', __FILE__, __LINE__, __METHOD__, 1);
                    $connection_id = $this->connections_master[0];
                } else {
                    $connection_id = $this->getConnectionByWeight($type);
                }
            }
        }

        //Debug::Text('FOUND load balanced connection ID: '. $connection_id, __FILE__, __LINE__, __METHOD__, 1);
        return $connection_id;
    }

    //Executes the same QUERY on the entire cluster of connections.
    //Would be used for things like SET SESSION TIME ZONE calls and such.

    public function getConnectionByWeight($type)
    {
        if ($type == 'slave') {
            $total_weight = $this->total_connection_weights['all'];
        } else {
            $total_weight = $this->total_connection_weights['master'];
        }

        $i = false;
        if (is_array($this->connections)) {
            $n = 0;
            $num = mt_rand(0, $total_weight);
            foreach ($this->connections as $i => $connection_obj) {
                if ($connection_obj->weight > 0 and ($type == 'slave' or $connection_obj->type == 'master')) {
                    $n += $connection_obj->weight;
                    if ($n >= $num) {
                        break;
                    }
                }
            }
            //Debug::Text('  Found connection by weight ID: '. $i .' Type: '. $type, __FILE__, __LINE__, __METHOD__, 10);
        }
        return $i;
    }

    public function removeConnection($i)
    {
        Debug::Text('Removing Connection: ' . $i, __FILE__, __LINE__, __METHOD__, 10);

        if (isset($this->connections[$i])) {
            $obj = $this->connections[$i];

            $this->total_connections[$obj->type]--;
            $this->total_connections['all']--;

            $this->total_connection_weights[$obj->type] -= abs($obj->weight);
            $this->total_connection_weights['all'] -= abs($obj->weight);

            if ($obj->type == 'master') {
                unset($this->connections_master[array_search($i, $this->connections_master)]);
                $this->connections_master = array_values($this->connections_master); //Reindex array.
            } else {
                unset($this->connections_slave[array_search($i, $this->connections_slave)]);
                $this->connections_slave = array_values($this->connections_slave); //Reindex array.
            }

            //Remove any sticky connections as well.
            if ($this->last_connection_id[$obj->type] == $i) {
                $this->last_connection_id[$obj->type] = false;
            }

            unset($this->connections[$i]);

            return true;
        }

        return false;
    }

    //Use this instead of __call() as it significantly reduces the overhead of call_user_func_array().

    public function __call($method, $args)
    { //Intercept ADOdb functions
        $type = 'master';
        $pin_connection = null;

        //Debug::Arr( $args, 'ADOdb Call: Method: '. $method , __FILE__, __LINE__, __METHOD__, 10);

        //Intercept specific methods to determine if they are read-only or not.
        $method = strtolower($method);
        switch ($method) {
            //case 'execute': //This is the direct overloaded function above instead.
            case 'getone':
            case 'getrow':
            case 'getall':
            case 'getcol':
            case 'getassoc':
            case 'selectlimit':
                if ($this->isReadOnlyQuery(trim($args[0])) == true) {
                    $type = 'slave';
                }
                break;
            case 'cachegetone':
            case 'cachegetrow':
            case 'cachegetall':
            case 'cachegetcol':
            case 'cachegetassoc':
            case 'cacheexecute':
            case 'cacheselect':
            case 'pageexecute':
            case 'cachepageexecute':
                $type = 'slave';
                break;
            //case 'ignoreerrors':
            //	//When ignoreerrors is called, PIN to the connection until its called again.
            //	if ( !isset($args[0]) OR ( isset($args[0]) AND $args[0] == FALSE ) ) {
            //		$pin_connection = TRUE;
            //	} else {
            //		$pin_connection = FALSE;
            //	}
            //	break;

            //Manual transactions
            case 'begintrans':
            case 'settransactionmode':
                $pin_connection = true;
                break;
            case 'rollbacktrans':
            case 'committrans':
                $pin_connection = false;
                break;
            //Smart transactions
            case 'starttrans':
                $pin_connection = true;
                break;
            case 'completetrans':
            case 'failtrans':
                //getConnection() will only unpin the transaction if we're exiting the last nested transaction
                $pin_connection = false;
                break;

            //Functions that don't require any connection and therefore shouldn't force a connection be established before they run.
            case 'qstr':
            case 'escape':
            case 'binddate':
            case 'bindtimestamp':
            case 'setfetchmode':
                $type = false; //No connection necessary.
                break;

            //Default to assuming master connection is required to be on the safe side.
            default:
                //Debug::Text( 'Method not Slave, assuming master...', __FILE__, __LINE__, __METHOD__, 10);
                break;
        }

        //Debug::Text( 'ADOdb Intercepted... Method: '. $method .' Type: '. $type .' Arg: ', __FILE__, __LINE__, __METHOD__, 10);
        if ($type === false) {
            if (is_array($this->connections) and count($this->connections) > 0) {
                foreach ($this->connections as $key => $connection_obj) {
                    $adodb_obj = $connection_obj->getADOdbObject();
                    return call_user_func_array(array($adodb_obj, $method), $this->makeValuesReferenced($args)); //Just makes the function call on the first object.
                }
            }
        } else {
            $adodb_obj = $this->getConnection($type, $pin_connection);
            if (is_object($adodb_obj)) {
                $result = call_user_func_array(array($adodb_obj, $method), $this->makeValuesReferenced($args));

                return $result;
            }

            return false;
        }
    }

    public function makeValuesReferenced($arr)
    {
        $refs = array();

        //This is a hack to work around pass by reference error.
        //Parameter 1 to ADOConnection::GetInsertSQL() expected to be a reference, value given in adodb-loadbalancer.inc.php on line 83
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }

        return $refs;
    }

    public function __get($property)
    {
        //return $this->getConnection()->$property;
        if (is_array($this->connections) and count($this->connections) > 0) {
            foreach ($this->connections as $key => $connection_obj) {
                return $connection_obj->getADOdbObject()->$property; //Just returns the property from the first object.
            }
        }

        return false;
    }

    public function __set($property, $value)
    {
        //return $this->getConnection()->$property = $value;
        //Special function to set object properties on all objects without initiating a connection to the database.
        if (is_array($this->connections) and count($this->connections) > 0) {
            foreach ($this->connections as $key => $connection_obj) {
                $connection_obj->getADOdbObject()->$property = $value;
            }
        }

        return true;
    }

    private function __clone()
    {
    }
}

class ADOdbLoadBalancerConnection
{
    //ADOdb data
    public $type = 'master';
    public $weight = 1;

    //Load balancing data
    public $persistent_connection = false;
    public $host = '';
    public $user = '';

    //DB connection data
    public $password = '';
    public $database = '';
    protected $driver = false;
    protected $adodb_obj = false;

    public function __construct($driver, $type = 'master', $weight = 1, $persistent_connection = false, $argHostname = '', $argUsername = '', $argPassword = '', $argDatabaseName = '')
    {
        if ($type !== 'master' and $type !== 'slave') {
            return false;
        }

        $this->adodb_obj = ADONewConnection($driver);

        $this->type = $type;
        $this->weight = $weight;
        $this->persistent_connection = $persistent_connection;

        $this->host = $argHostname;
        $this->user = $argUsername;
        $this->password = $argPassword;
        $this->database = $argDatabaseName;

        return true;
    }

    public function getADOdbObject()
    {
        return $this->adodb_obj;
    }
}
