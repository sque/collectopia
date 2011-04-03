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


require_once(dirname(__FILE__) . '/../Cache.class.php');

//! Implementation for SQLite caching
class Cache_Sqlite extends Cache
{
    //! The sqlite connection handle
	public $dbhandle;
	
	//! Construct a new sqlite based caching engine
	/**
	 * @param $db The file name of the database to open/create.
	 */
	public function __construct($db)
	{
	    $new_db = false;
		if (!file_exists($db))
			$new_db = true;
		
		// Open database
		if (($this->dbhandle = sqlite_open($db, 0666, $error_message)) === FALSE)
			throw new Exception("Cannot open sqlite cache database. " . $error_message);
		
		// Create schema if needed
		if ($new_db)
		{
			$res = sqlite_query($this->dbhandle,
				'CREATE TABLE cache_sqlite(\'key\' VARCHAR(255) PRIMARY KEY, value TEXT, expir_time INTEGER);', 
				SQLITE_ASSOC,	$error_message);
			
			if ($res === FALSE)
			{	sqlite_close($this->dbhandle);
				unlink($db);
				throw new Exception("Cannot build sqlite cache database. " . $error_message);
			}
		}
	}
	
	public function __destruct()
	{	sqlite_close($this->dbhandle);	}
	
	public function set($key, $value, $ttl = 0)
	{	$expir_time = (($ttl === 0)?0:(time() + $ttl));
    	$res = @sqlite_query($this->dbhandle,
			"UPDATE cache_sqlite SET " .
				"value = '" . sqlite_escape_string(serialize($value)) . "', " .
				"expir_time = '" . $expir_time . "' " .
				"WHERE key = '" . sqlite_escape_string($key) . "';");
		if (($res !== FALSE) && (sqlite_changes($this->dbhandle) !== 0))
		    return true;
		
		return $this->add($key, $value, $ttl);		
	}
	
	public function set_multi($values, $ttl = 0)
	{	foreach($values as $key => $value)
			$this->set($key, $value, $ttl);
		return true;
	}
	
	public function add($key, $value, $ttl = 0)
	{ 	$expir_time = (($ttl === 0)?0:(time() + $ttl));
	    $res = @sqlite_query($this->dbhandle,
			"INSERT INTO cache_sqlite (key, value, expir_time) VALUES( '" .
				sqlite_escape_string($key) . "', '" .
				sqlite_escape_string(serialize($value)) . "', '" .
				$expir_time . "');");
		
		return ($res !== FALSE);
	}
	
	public function get($key, & $succeded)
	{	// Execute query
		if (($res = sqlite_query($this->dbhandle, 
				"SELECT * FROM cache_sqlite WHERE key = '" . sqlite_escape_string($key) . "' LIMIT 1;")) === FALSE)
		{	$succeded = false;
			return false;
		}
		
		// Fetch data
		if (count($data = sqlite_fetch_all($res)) != 1)
		{	$succeded = false;
			return false;
		}
		
		// Check if it is expired and erase it
		if (($data[0]['expir_time']) && ($data[0]['expir_time'] < time()))
		{	$this->delete($key);
			$succeded = false;
			return false;
		}
		$succeded = true;
		return unserialize($data[0]['value']);
	}
	
	public function get_multi($keys)
	{	$result = array();
		foreach($keys as $key)
		{	$value = $this->get($key, $succ);
			if ($succ === TRUE)
				$result[$key] = $value; 
		}
		return $result;
	}
	
	public function delete($key)
	{	$res = sqlite_query($this->dbhandle,
			"DELETE FROM cache_sqlite WHERE key = '" . sqlite_escape_string($key) . "'");
	    if (($res === false) || (sqlite_changes($this->dbhandle) === 0))
	        return false;
        return true;
	}
	
	public function delete_all()
	{	return (FALSE !== sqlite_query($this->dbhandle,
			"DELETE FROM cache_sqlite"));
	}
}

?>
