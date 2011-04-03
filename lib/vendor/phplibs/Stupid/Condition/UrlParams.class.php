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


require_once(dirname(__FILE__) . '/../Condition.class.php');

//! Implementation of url_params Stupid_Condition
/**
 * A condition evaluator that can perform checks on the
 * given uri parameters.\n
 * This evaluator implements the <b> type = "url_params"</b> 
 * 
 * @par Acceptable condition options
 * - @b op [Default = equal]: equal, isset, isnumeric, regexp
 * - @b param: The parameter to check.
 * - @b param_type [Default=both]: The type of parameter from the url.\n
 *   Acceptable values are "get", "post", "both" 
 * - @b value : The value that the operand may need to cross check.
 * .
 * 
 * @par Examples
 * @code
 * // Adding a rule that checks if parameter id is set and is of type numeric
 * Stupid::add_rule("view_forum",
 *     array('type' => 'url_params', 'op' => 'isnumeric', 'param' => 'id'));
 * 
 * // A rule with two conditions
 * Stupid::add_rule("delete_forum",
 *     // Check that parameter "action" is set and is equal to "delete"
 *     array('type' => 'url_params', 'op' => 'equal', 'param' => 'action', 'value' => 'delete'),
 *     // Check that parameter "action" is set and is equal to 'id'
 *     array('type' => 'url_params', 'op' => 'isnumeric', 'param' => 'id'));
 * @endcode
 * @author sque
 */
class Stupid_Condition_UrlParams extends Stupid_Condition
{
	public static function type()
	{	return 'url_params';	}
	
 
	public function evaluate_impl($previous_backrefs)
	{
		// Default condition values
		$defcond = array(
			'op' => 'equal',
			'param' => '',
			'value' => '',
			'param_type' => 'both',
		);
		
		// Merge default with user supplied parameters
		$options = array_merge($defcond, $this->options);
		
		// Check that parameter is set
		if (($value = Net_HTTP_RequestParam::get($options['param'], $options['param_type'])) === NULL)
			return false;
			
		// Per operand
		switch($options['op']){
		case 'isset':
			return true;
		case 'equal':
			return $value == $options['value'];
		case 'regexp':
			return (preg_match($options['value'], $value) == 1);
		case 'isnumeric':
			return is_numeric($value);
		}
		return false;		
	}
};
Stupid_Condition_UrlParams::register();
?>
