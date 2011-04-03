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

//! Implementation for APC cache engine
class Cache_Apc extends Cache
{
	private $apc_key_prefix;
	
	/**
	 * @param $apc_key_prefix Because APC is by designed shared memory inside all
	 *  executed scripts of apache, you can prefix the key values with a unique string.
	 * @param $serialize_data A flag to serialize/unserialize data before
	 * pushing/fetching them from apc sma.

     */
	public function __construct($apc_key_prefix = '', $serialize_data = false)
	{	$this->apc_key_prefix = $apc_key_prefix;
		$this->serialize_data = $serialize_data;
	}
	
	public function add($key, $value, $ttl = 0)
	{	if ($this->serialize_data)
			return apc_add($this->apc_key_prefix . $key, serialize($value), $ttl);
		else
			return apc_add($this->apc_key_prefix . $key, $value, $ttl);
	}

	public function set($key, $value, $ttl = 0)
	{	if ($this->serialize_data)
			return apc_store($this->apc_key_prefix . $key, serialize($value), $ttl);
		else
			return apc_store($this->apc_key_prefix . $key, $value, $ttl);
	}

	public function set_multi($values, $ttl = 0)
	{	foreach($values as $key => $value)
			$this->set($key, $value, $ttl);
	    return true;
	}
	
	public function get($key, & $succeded)
	{	if ($this->serialize_data)
			return unserialize(apc_fetch($this->apc_key_prefix . $key, $succeded));
		else
			return apc_fetch($this->apc_key_prefix . $key, $succeded);
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
	{	return apc_delete($this->apc_key_prefix . $key);	}
	
	public function delete_all()
	{	return apc_clear_cache("user");	}
}
?>
