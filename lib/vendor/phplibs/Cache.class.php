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


require_once(dirname(__FILE__) . '/./functions.lib.php');

//! Abstract interface for caching engines
/**
 * To create a custom engine subclass Cache class
 * and populate all abstract methods.
 * 
 * Cache module ships with the following caching engine implementations
 * - Cache_Apc
 * - Cache_Memcached
 * - Cache_File
 * - Cache_Sqlite
 * .
 * @author sque
 */
abstract class Cache
{
	//! Set an entry in cache database
	/**
	 * Add or replace the value of a key in cache database
	 * @param $key Unique identifier of the cache entry
	 * @param $value Value of cache entry
	 * @param $ttl Maximum time, in seconds, that this entry will be valid
	 * @return
	 * - @b TRUE if value was stored succesfully.
	 * - @b FALSE on any kind of error.
	 */
	abstract public function set($key, $value, $ttl = 0);
	
	//! Set multiple entries
	/**
	 * This is like set() but it stores multiple entries at
	 * the same time. Some engines (like memcached) supports
	 * acceleration of this action. For the rest of the engines
	 * it will be emulated, without a significant performance penalty.
	 * @param $values Associative array of key-value pairs to
	 *  be stored in cache db.
	 * @param $ttl Maximum time, in seconds, that all these entry will be valid
	 * @return
	 * - @b TRUE if all values were stored succesfully.
	 * - @b FALSE on any kind of error.
	 */
	abstract public function set_multi($values, $ttl = 0);
	
	//! Add new entry in cache database
	/**
	 * Add a @b new value in database. This function will fail if
	 * there is already an entry with the same key. 
	 * @param $key Unique identifier of the cache entry
	 * @param $value Value of cache entry.
	 * @param $ttl Maximum time, in seconds, that this entry will be valid
	 * @return
	 * - @b TRUE if value was stored succesfully.
	 * - @b FALSE on any kind of error.
	 */
	abstract public function add($key, $value, $ttl = 0);
	
	//! Read an entry from cache database
	/**
	 * Retrieve an entry from cache db based on its key.
	 * @param $key Unique identifier of the cache entry
	 * @param $succeded By reference variable to store
	 * the result status of the function. @b True will be
	 * stored in case of success or @b false on any error. 
	 * @return The value of the entry in database or @b FALSE
	 * if entry was not found.
	 * 
	 * @note Return value is not always the safest way to check if the
	 * entry was succesfull, as there is a posibility the entry to have
	 * false value. For success check the value of the $succeded parameter
	 * after the execution of function.
	 */
	abstract public function get($key, & $succeded);
	
	//! Read multiple entries from cache database
	/**
	 * This works like get() but with multiple entries at
	 * the same time. Some engines (like memcached) supports
	 * acceleration of this action. For the rest of the engines
	 * it will be emulated, without a significant performance penalty.
	 * @param $keys Simple array with the keys of entries that will be read.
	 * @return Associative array with all the keys that were retrieved
	 * succesfully. Those that failed will be omited from the return result. 
	 */
	abstract public function get_multi($keys);
	
	//! Delete an entry from cache database
	/**
	 * Delete an entry based on its key.
	 * @param $key Unique identifier of the cache entry
	 * @return
	 * - @b TRUE if entry was found and deleted
	 * - @b FALSE on any kind of error
	 * .
	 */
	abstract public function delete($key);
	
	//! Empty cache from all entries
	/**
	 * It will delete all entries from the cache.
	 * database.
	 * @return
	 * - @b TRUE on success whetever was the number of deleted entries.
	 * - @b FALSE on error.
	 * 
	 * @note This function will delete @b ALL entries, even
	 * those that were not created by you.
	 */
	abstract public function delete_all();
}

?>
