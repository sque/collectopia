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


class Net_HTTP_RequestParam
{
	//! Assure that a parameter is given through 'get', 'post' or 'both'.
	/**
	 * If the parameter is not set it will terminate the script execution.
	 * @param $name The name of the parameter.
	 * @param $param_type The type of this parameter. Accepted values are 'get', 'post' or 'both'.
	 * @return The value of the parameter
	 */
	public static function assure($name, $param_type = 'both')
	{	if ($param_type == 'post')
			$array = & $_POST;
		else if ($param_type == 'get')
			$array = & $_GET;
		else
			$array = & $_REQUEST;
			
		if (!isset($array[$name]))
			exit;
		return $array[$name];
	}
	
	//! Safe check that a parameter is equal with a value
	/**
	 * @param $name The name of the parameter.
	 * @param $check_value The value to check that the param is equal to.
	 * @param $param_type The type of this parameter. Accepted values are 'get', 'post' or 'both'.
	 * @return
	 *  - @b false If the parameter is not set or the parameter is not equal.
	 *  - @b true If the parameter is set and is equal to $check_value
	 *  .
	 */			
	public static function is_equal($name, $check_value, $param_type = 'both')
	{	if ($param_type == 'post')
			$array = & $_POST;
		else if ($param_type == 'get')
			$array = & $_GET;
		else
			$array = & $_REQUEST;
	    
	    if (isset($array[$name]) && ($array[$name] == $check_value))
	        return true;        
	    return false;
	}
	
	//! Read the value of a parameter
	/**
	 * @param $name The name of the parameter.
     * @param $param_type The type of this parameter. Accepted values are 'get', 'post' or 'both'.
	 * @return
	 *  - The value of the parameter.
	 *  - @b NULL If the value is not set at all.
	 *  .
	 */
	public static function get($name, $param_type = 'both')
	{
		if ($param_type == 'post')
			$array = & $_POST;
		else if ($param_type == 'get')
			$array = & $_GET;
		else
			$array = & $_REQUEST;

		return (isset($array[$name]))?$array[$name]:NULL;
	}
	
	//! Check if a parameter is set
	/**
	 * @param $name The name of the parameter
     * @param $param_type The type of this parameter. Accepted values are 'get', 'post' or 'both'.
     * @return
     *  - @b true If this parameter is set
     *  - @b false If the parameter is not set
     *  .
	 */
	public static function is_set($name, $param_type = 'both')
	{
		if ($param_type == 'post')
			$array = & $_POST;
		else if ($param_type == 'get')
			$array = & $_GET;
		else
			$array = & $_REQUEST;
			
		return isset($array[$name]);
	}
	
    //! Check if a parameter is set and is numeric type
	/**
	 * @param $name The name of the parameter
     * @param $param_type The type of this parameter. Accepted values are 'get', 'post' or 'both'.
     * @return
     *  - @b true If this parameter is set and is numeric
     *  - @b false If the parameter is not set or it is not numeric
     *  .
	 */
	public static function is_numeric($name, $param_type = 'both')
	{
		if ($param_type == 'post')
			$array = & $_POST;
		else if ($param_type == 'get')
			$array = & $_GET;
		else
			$array = & $_REQUEST;
			
		return (isset($array[$name]))?is_numeric($array[$name]):false;;
	}
};

?>
