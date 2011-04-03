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


//! ABC of the intra-session 'Singleton' design pattern.
abstract class IntraSessionSingleton
{
	//! Reference to the instaces repository
	private static $m_instances;
	
    //! Returns the unique instance of an object, or creates it if not found.
	protected static function get_class_instance($class)
	{	$lower_class_name = strtolower($class);

		// Check if the instances repository is initialized
		if (!isset(self::$m_instances))
		{	// Allocate a unique singleton instances object per session (Version 1)
			if (!isset($_SESSION['singleton_instances_v1']))
			{
				 $_SESSION['singleton_instances_v1'] = array();
			}
			self::$m_instances =& $_SESSION['singleton_instances_v1'];	
		}
		
		// Check if the instance is created
		if (!array_key_exists($lower_class_name, self::$m_instances))
		{	// Create object
			self::$m_instances[$lower_class_name] = new $class;
		}
		$instance =& self::$m_instances[$lower_class_name];
		return $instance;
	}
}

?>
