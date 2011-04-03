<?php
/*
 *  This file is part of PHPLibs <http://phplibs.kmfa.net/>.
 *  
 *  Copyright (c) 2010 < squarious at gmail dot com > .
 *  
 *  PHPLibs is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  PHPLibs is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with PHPLibs.  If not, see <http://www.gnu.org/licenses/>.
 *  
 */


require_once(dirname(__FILE__) . '/../EventDispatcher.class.php');
require_once(dirname(__FILE__) . '/../Exceptions.lib.php');

//! Interact with the connection to database
/**
 * An easy way to organize prepared statements and execute them
 * with centralized error handling. It support optional delayed connection
 * and/or delayed prepation to increase overall perfromance.
 */
class DB_Conn
{
    //! Connection handler
    static private $dbconn = null;
    
    //! Connection options
    static private $connection_options = null;

    //! The array with all statemenets
    static private $stmts;

    //! Set error handler function
    static private $error_handler_func;

    //! Delayed preparation flag
    static private $delayed_preparation;
    
    //! Delayed connection flag
    static private $delayed_connection;

    //! Events dispatcher
    static private $events = null;

    //! Packet size when sending binary packets
    static private $max_packet_allowed = null;
    
    //! Queries that must be run after connection
    static private $initialization_queries = null;

    //! Get the events dispatcher of DB_Conn
    /**
    * Events are announced through an EventDispatcher object. The following
    * events are valid:
    *  - @b connected: Executed after DB_Conn has been connected.
    *  - @b disconnected: Executed when DB_Conn has been disconnected.
    * 	- @b error : Executed on any error that has been emitted from DB_Conn.
    *  - @b query: Perform a direct query on the connection.
    *  - @b stmt.declared: Request preparation of a statement.
    *  - @b stmt.prepared: A requested statement was prepared.
    *  - @b stmt.executed: A prepared statement was executed.
    * .
    */
    static public function events()
    {   
        if (self::$events === null)
            self::$events = new EventDispatcher(array(
	        	'connected',
	        	'disconnected',
	        	'error',
	        	'query',
	        	'stmt.declared',
	        	'stmt.prepared',
	        	'stmt.executed',
	        	'stmt.released',
            ));
        return self::$events;
    }

    //! Initialize db connection
    /**
    * @param $server The dns or ip of the server to connect at.
    * @param $user The user to use for authentication.
    * @param $pass The password that will be used for authentication.
    * @param $schema The schema to use as default for this connection.
    * @param $delayed_preparation Flag if delayed preparation should be used to
    *   improve performance.
    * @param $delayed_connection Flag if delayed connection should be used to
    *   improve performance.
    */
    static public function connect($server, $user, $pass, $schema, $delayed_preparation = true, $delayed_connection = false)
    {   
        self::$delayed_preparation = $delayed_preparation;
        self::$delayed_connection = $delayed_connection;
        self::$max_packet_allowed = null;                

        // Create events dispatcher if it does not exist
        self::events();
    
        // Disconnect
        self::disconnect();
    
        // Prepare conection
        self::$connection_options = array(
        	'host' => $server,
        	'username' => $user,
        	'password' => $pass,
        	'schema' => $schema
        );
        self::$initialization_queries = array();
        
        // Connect if it is needed
        if (!self::$delayed_connection)
        	return self::assure_connect();

        // For delayed connection we assume ok
        return true;
    }
    
    //! Execute queries that initialize connection
    /**
     * The execution of this querie may be postponed until the actual
     * connection is done. It is used for queries that initialize
     * connection, like setting up time_zone, character set. etc.
     * @param $query The query that will be executed by the server.
     * @return boolean - @b false If there was any error.
     *  - @b true If everything went ok. 
     */
    static public function initialization_query($query)
    {
    	// If there is connection execute it
    	if (self::$dbconn !== null)
    		return self::query($query);
    	// or push it to stack
    	self::$initialization_queries[] = $query;
    	return true;
    }
    
    static private function assure_connect()
    {
    	if (is_object(self::$dbconn))
    		return true;	// We are connected
    	if (self::$connection_options === null)
    		return false;	// We are not on (pre)connection state.
    		
    	// Try to connect
    	self::$dbconn = new mysqli(
    		self::$connection_options['host'], 
    		self::$connection_options['username'], 
    		self::$connection_options['password'],
    		self::$connection_options['schema']
    	);
        if (self::$dbconn->connect_error)
        {
            self::raise_error('Error connecting to database. ' . self::$dbconn->connect_error);
            self::$dbconn = null;
            self::$connection_options = null;
            return false;
        }
        self::$stmts = array();
        self::$events->notify('connected', self::$connection_options);
        
        // Execute initialization queries
        foreach(self::$initialization_queries as $query)
        	self::query($query);
        return true;
    } 

    //! Disconnect db connection
    /**
    * @return @b true if no error.
    */
    static public function disconnect()
    {   
    	self::$connection_options = null;
        if (self::$dbconn !== null)
        {
        	self::$dbconn = null;            
        	self::events()->notify('disconnected');
        }
        return true;
    }

    //! Check if it is connected
    /**
    * @return - @b true if it is connected.
    *   - @b false if disconnected.
    */
    static public function is_connected()
    {   
        return (self::$dbconn !== null);
    }

    //! Get the max_packet_allowed for this connection
    /**
     * @return integer The max_allowed_packet that is asked from te server.
     */
    static public function get_max_allowed_packet()
    {
    	if (self::$max_packet_allowed !== null)
    		return self::$max_packet_allowed;
    	
    	$res = self::query_fetch_all('SELECT @@max_allowed_packet');
    	return self::$max_packet_allowed = $res[0][0]; 
    }
    
    //! Change the default character set of the connection
    /**
     * @param $charset The default charset to be used for this connection
     * @throws NotConnectedException if DB_Conn is not connected
     */
    static public function set_charset($charset)
    {   
        if (self::$dbconn === null)
        	if (!self::assure_connect())
            	throw new NotConnectedException('DB_Conn::' . __FUNCTION__ . '() demands established connection!');

        if (!self::$dbconn->set_charset($charset))
        {
            self::raise_error('Cannot change the character set. ' . self::$dbconn->error);
            return false;
        }
        return true;
    }

    //! Get the mysqli connection object
    /**
     * @throws NotConnectedException if DB_Conn is not connected
     * @return mysqli Object of the link used for tihs connection.
     */
    static public function get_link()
    {   
        if (self::$dbconn === null)
        	if (!self::assure_connect())
            	throw new NotConnectedException('DB_Conn::' . __FUNCTION__ . '() demands established connection!');

        return self::$dbconn;
    }

    //! Escape a string for mysql usage
    /**
     * @param $str The string to be escaped.
     * @throws NotConnectedException if DB_Conn is not connected
     */
    static public function escape_string($str)
    {	
        if (self::$dbconn === null)
        	if (!self::assure_connect())
            	throw new NotConnectedException('DB_Conn::' . __FUNCTION__ . '() demands established connection!');

        return self::$dbconn->real_escape_string($str);
    }

    //! Get the id generated by the last insert command.
    /**
    * Get id of the last inserted row through this connection.
    * @throws NotConnectedException if DB_Conn is not connected
    */
    static public function last_insert_id()
    {   
        if (self::$dbconn === null)
        	if (!self::assure_connect())
            	throw new NotConnectedException('DB_Conn::' . __FUNCTION__ . '() demands established connection!');

        return self::$dbconn->insert_id;
    }

    //! It does the actual statement prepartion (used for delayed prepartion)
    static private function assure_preparation($key)
    {
        // Check if it must be prepared now
        if (!isset(self::$stmts[$key]['handler']))
        {
            // Prepare statement
            if (!($stmt = self::$dbconn->prepare(self::$stmts[$key]['query'])))
            {   
                self::raise_error("Cannot prepare statement '" . $key . "'. " . self::$dbconn->error);
                // Release statement as it is invalid
                unset(self::$stmts[$key]);
                return false;
            }
            self::$stmts[$key]['handler'] = $stmt;

            self::$events->notify('stmt.prepared', array('key' => $key));
        }
        return true;
    }

    //! Check if a statement key is used
    /**
    * Check if this key is already used in prepared statements
    * @param $key The key to be checked
    * @return -@b true if it is already used.
    *   - @b false if it is not used.
    * @throws NotConnectedException if DB_Conn is not connected
    */
    static public function is_key_used($key)
    {   
        if (self::$dbconn === null)
        	if (!self::assure_connect())
            	throw new NotConnectedException('DB_Conn::' . __FUNCTION__ . '() demands established connection!');
        return isset(self::$stmts[$key]);
    }

    //! Prepare a statment and save it internally
    /**
    * @note prepare() will not actually compile statement
    *   unless delayed_preparation is set to false at connect().
    * @note If the query is wrong, the slot will be released automatically
    *   at the time of the actual compilation.
    * @param $key The unique name of the prepared statement, this will be used to execute
    * 	the statement too.
    * @param $query The query of the statement.
    * @return - @b true if the statement was accepted for preparation.
    *  - @b false on any error.
    * @throws NotConnectedException if DB_Conn is not connected
    */
    static public function prepare($key, $query)
    {   
        if (self::$dbconn === null)
        	if (!self::assure_connect())
            	throw new NotConnectedException('DB_Conn::' . __FUNCTION__ . '() demands established connection!');

        // Check if the key is free
        if (isset(self::$stmts[$key]))
        {
        	self::raise_error('There is already a statement prepared with this key "' . $key . '".');
            return false;
        }
    
        // Create statement entry
        self::$stmts[$key] = array('query' => $query);
    
        // Statement declared
        self::$events->notify('stmt.declared', array('key' => $key, 'query' => $query));
    
        // Delayed preparation check
        if (self::$delayed_preparation === false)
            return self::assure_preparation($key);
    
        return true;
    }

    //! Release a prepared statement
    /**
    * @param $key The unique name that was used on prepare().
    * @return - @b true If the statement was found released.
    * - @b false on any error
    * @throws NotConnectedException if DB_Conn is not connected
    */
    static public function release($key)
    {   
        if (self::$dbconn === null)
        	if (!self::assure_connect())
            	throw new NotConnectedException('DB_Conn::' . __FUNCTION__ . '() demands established connection!');

        // Check if the key is free
        if (!isset(self::$stmts[$key]))
        {   
            self::raise_error('Cannot release the statement "' . $key . '" that does not exist.');
            return false;
        }
    
        // Check if it is prepared
        if (isset(self::$stmts[$key]['handler']))
            self::$stmts[$key]['handler']->close();
    
        // Free slot
        unset(self::$stmts[$key]);
    
        // Notify
        self::$events->notify('stmt.released', array('key' => $key));
    
        return true;
    }

    //! Prepare multiple statements with one call.
    /**
    * @param $statements All statement in associative array(key => statement, key => statement)..
    * @throws NotConnectedException if DB_Conn is not connected
    * @return - @b true If all statements were prepared
    *  - @b false on any error
    */
    static public function multi_prepare($statements)
    {
    	if (self::$dbconn === null)
    		if (!self::assure_connect())
            	throw new NotConnectedException('DB_Conn::' . __FUNCTION__ . '() demands established connection!');

        foreach($statements as $key => $query)
            if (!self::prepare($key, $query))
                return false;
    
        return true;
    }

    //! Raise an error
    static private function raise_error($msg)
    {	
    	// Notify about the error
        self::$events->notify('error', array('message' => $msg));

        // Log it as notice
        trigger_error($msg);
    }

    //! Execute a direct query in database and return result set
    /**
    * @param $query The command to be executed on server
    * @throws NotConnectedException if DB_Conn is not connected
    * @return - @b mysqli_result object with the result set
    * - @b false on any kind of error
    */
    static public function query($query)
    {   
        if (self::$dbconn === null)
        	if (!self::assure_connect())
            	throw new NotConnectedException('DB_Conn::' . __FUNCTION__ . '() demands established connection!');

        // Query db connection
        if (!$res = self::$dbconn->query($query))
        {   
            self::raise_error('DB_Conn::query(' . $query . ') error on executing query.' . self::$dbconn->error);
            return false;
        }
    
        // Command executed
        self::$events->notify('query', array('query' => $query));
        return $res;
    }

    //! Execute a direct query in database and get all results immediatly
    /**
    * @param $query The command to be executed on server
    * @return - An array with all records. Each record is an array with field values ordered
    *  by column order and by column name.
    *  - @b false on any kind of error
    * @throws NotConnectedException if DB_Conn is not connected
    */
    static public function query_fetch_all($query)
    {   
        if (!$res = self::query($query))
            return false;

        $results = array();
        while($row = $res->fetch_array())
            $results[] = $row;
        $res->free_result();
    
        return $results;
    }

    //! A macro for binding and executing a statement
    /**
    * @param $key The key of the statement that was used to prepare.
    * @param $param_data An associative array with all data that will be passed as parameters to prepared statement.
    * 	Key of array must be the order of parameter in the statement or the name of parameter if it was declared
    *  using names in the statement.
    * @param $param_types An associative array with type of data of previous array. If an entry is missing
    * 	it defaults to string type.
    * @return It will return false on fail or the statement handler to fetch data.
    * @note If you are executing statement that contains a binary parameter (marked with "b") the data are
    *	send in chunks.
    * @throws NotConnectedException if DB_Conn is not connected
    */
    static public function execute($key, $param_data = null, $param_types = null)
    {	
        if (self::$dbconn === null)
        	if (!self::assure_connect())
            	throw new NotConnectedException('DB_Conn::' . __FUNCTION__ . '() demands established connection!');

        // Check if statement exist
        if (!isset(self::$stmts[$key]))
        {
            self::raise_error('DB_Conn::execute("' . $key . '") The supplied statement ".
           	        "must first be prepared using DB_Conn::prepare().');
            return false;
        }

        // Assure preparation
        if (!self::assure_preparation($key))
            return false;
    
        // Bind parameters if it is needed
        if (($param_data !== null) && (count($param_data) !== 0))
        {
        	$null = null;
            $params = array('');
            $norm_types = array();	//< Normalized types
            foreach($param_data as $index => $data)
            {	
            	// Normalize type
            	$norm_types[$index] = (isset($param_types[$index]))?$param_types[$index]:'s';
            	if (($norm_types[$index] == 'b') && (strlen($param_data[$index]) < self::get_max_allowed_packet()))
            		 $norm_types[$index] = 's';
                
            	$params[0] .= $norm_types[$index];
                if ($norm_types[$index] != 'b')
                	$params[] = & $param_data[$index];
                else
                    $params[] = & $null;
            }
            
            // Bind parameters
            if (!call_user_func_array(array(self::$stmts[$key]['handler'], 'bind_param'), $params))
            {
            	self::raise_error('Cannot bind params to prepared statement "' . $key . '". ' . self::$stmts[$key]['handler']->error);
            	return false;
            }
            	
            // Send blob data
            if ($param_types !== null)
            {
                foreach($norm_types as $pos => $type)
                {
	                if ($norm_types[$pos] == 'b')
	                {	
	                    foreach(str_split($param_data[$pos], self::get_max_allowed_packet()-5) as $data )
	                    {                    
	                        if (!self::$stmts[$key]['handler']->send_long_data($pos, $data))
	                        {
	                        	self::raise_error('Cannot send long data to prepared statement "' . $key . '". ' . self::$stmts[$key]['handler']->error);
	                        	return false;
	                        }
	                    }
	                }
                }
            }
        }
    
        // Execute statement
        if (!self::$stmts[$key]['handler']->execute())
        {   
            self::raise_error('Cannot execute the prepared statement "' . $key . '". ' . self::$stmts[$key]['handler']->error);
            return false;
        }
    
        self::$events->notify('stmt.executed', array_merge(array($key), (isset($args)?$args:array())));
    
        return self::$stmts[$key]['handler'];
    }

    //! A macro for executing a statement and getting all results in one query
    /**
    * @note This function is not slower than getting manually one-by-one rows and loading in memory.
    * 	To use this function check the documentation of DB_Conn::execute().
    * @return It will return false on fail or an array with all results.
    * @throws NotConnectedException if DB_Conn is not connected
    */
    static public function & execute_fetch_all($key, $param_data = null, $param_types = null)
    {
        if (! ($stmt = self::execute($key, $param_data, $param_types)))
        {	
            $res = false;
            return $res;
        }

        if ($stmt->field_count <= 0)
        {
            $res = array();
            return $res;        // This statement has no result
        }

        // Get the name of fields
        if (($result = $stmt->result_metadata()) === null)
            return array();	// This query has no result set
        $fields = $result->fetch_fields();
        $result->close();

        // Bind results on each cell of bnd_res array
        $bnd_res = array_fill(0, $stmt->field_count, null);
        $bnd_param = array();
        foreach($bnd_res as $k => &$bnd)
        	$bnd_param[] = & $bnd;
        unset($bnd);
        $stmt->store_result();
        call_user_func_array(array($stmt, 'bind_result'), $bnd_param);

        // Get results one by one
        $array_result = array();
        while($stmt->fetch())
        {	
            $row = array();
            for($i = 0; $i < $stmt->field_count; $i++)
            {
                $row[$i] = $bnd_res[$i];
                $row[$fields[$i]->name] = & $row[$i];
            }
            $array_result[] = $row;
        }
        $stmt->free_result();

        return $array_result;
    }
};

?>
