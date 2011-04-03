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

//! Implementation for filesystem caching
class Cache_File extends Cache
{
	//! Directory to save cache files
	private $directory;
	
	//! Prefix to add at filenames
	private $file_prefix;

	private function filename_by_key($key)
	{
	    return $this->directory . '/' . $this->file_prefix . md5($key);
	}
	
	//! Construct a new file-based cache engine
	/**
	 * @param $directory
	 *  - A valid directory path to store cached objects.
	 *  - @b null If you want to use system's temporary directory.
	 *  .
	 * @param $file_prefix
	 *  A string to prefix all filenames to avoid colision with existing files.
	 */
	public function __construct($directory = NULL, $file_prefix = 'cache_file_')
	{
	    $this->file_prefix = $file_prefix;
		$this->directory = $directory;
		if ($this->directory  === NULL)
			$this->directory = sys_get_temp_dir();
			
		if (!is_writeable($this->directory ))
			throw new Exception("Directory {$this->directory} is not writable by Cache_File");		
	}
	
	public function set($key, $value, $ttl = 0)
	{
	    if (($fh = @fopen($this->filename_by_key($key),'w+')) === false)
			return false; 
		
		// Lock file
		if (flock($fh, LOCK_EX) === false)
		{
		    fclose($fh);
		    return false;
		}
		
		// Write data
		fwrite($fh, serialize(array(
			'key' => $key,
			'value' => $value,
			'expires' => (($ttl > 0)?time() + $ttl:0)
		)));
		
		fclose($fh);
		return true;
	}
	
	public function set_multi($values, $ttl = 0)
	{
	    foreach($values as $key => $value)
			$this->set($key, $value, $ttl);
	    return true;
	}
	
	public function add($key, $value, $ttl = 0)
	{
	    if (file_exists($this->filename_by_key($key)))
			return false;
		return $this->set($key, $value, $ttl);
	}
	
	public function get($key, & $succeded)
	{
	    if (! is_file($fname = $this->filename_by_key($key)))
		{
		    $succeded = false;
			return false;
		}

	    if (($fh = @fopen($fname,'r')) === false)
		{
		    $succeded = false;
			return false;
		}
		
		// Lock file
		if (flock($fh, LOCK_SH) === false)
		{
		    fclose($fh);
			$succeded = false;
			return false;
		}
		
		// Read data
		$data = file_get_contents($fname);
		fclose($fh);
		
		// Unserialize data
		if (($data = @unserialize($data)) === FALSE)
		{
		    unlink($fname);
			$succeded = false;
			return false;
		}
		
		// Check expired
		if (($data['expires'] !== 0) && ($data['expires'] < time()))
		{
		    unlink($fname);
			$succeded = false;
			return false;
		}
		
		$succeded = true;
		return $data['value'];
	}

	public function get_multi($keys)
	{
	    $result = array();
		foreach($keys as $key)
		{	$value = $this->get($key, $succ);
			if ($succ === TRUE)
				$result[$key] = $value; 
		}
		return $result;
	}
	
	public function delete($key)
	{
	    $fname = $this->filename_by_key($key);
	    if (!is_file($fname))
	        return false;
	    return @unlink($fname);
	}
	
	public function delete_all()
	{
	    if (($dh = opendir($this->directory)) === FALSE)
			return false;
			
		while((($entry = readdir($dh)) !== FALSE))
		{	if (!is_file($this->directory . '/' . $entry))
				continue;
			
			// Delete all files with that prefix
			if (substr($entry, 0, strlen($this->file_prefix)) === $this->file_prefix)
				unlink($this->directory . '/' . $entry);
		}
		return true;		
	}
}
?>
