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

//! Implementation of func Stupid_Condition
/**
 * A condition evaluator that will execute a user defined function.\n
 * This evaluator implements the <b> type = "func"</b> 
 * 
 * @par Acceptable condition options
 * - @b func [@b Mandatory]: A callback to the function (see php official documentation for callbacks)
 * - @b args [Default = array()]: An array with arguments to pass on function 
 * - @b backref_as_arg[Default = true]: Pass as function arguments the backrefernces from previous evaluations in the rule.
 * - @b backref_first[Default = true]: If both @b args and @b backref exists, put backref first 
 * 		and then @b args, otherwise use @b args firstly and then @b backrefs.
 * .
 * 
 * @par Examples
 * @code
 * function is_day(){
 * 	// Check if current time is morning and return true or false
 * }
 * // Adding a rule that checks what part of day is it
 * Stupid::add_rule("view_forum",
 *     array('type' => 'func', 'func' => 'is_morning'));
 * 
 * @endcode
 * @author sque
 */
class Stupid_Condition_Func extends Stupid_Condition
{
	public static function type()
	{	return 'func';	}
	
	public function evaluate_impl($previous_backrefs)
	{
		// Default condition values
		$defcond = array(
			'args' => array(),
			'backref_as_arg' => true,
			'backref_first' => true
		);
		
		// Merge default with user supplied parameters
		$options = array_merge($defcond, $this->options);
		
		$args = $options['args'];
		if ($options['backref_as_arg'])
			if ($options['backref_first'])
				$args = array_merge($previous_backrefs, $args);
			else
				$args = array_merge($args, $previous_backrefs);
		
		return call_user_func_array($options['func'], $args);
	}
};
Stupid_Condition_Func::register();

?>
