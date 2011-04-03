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


require_once(dirname(__FILE__) . '/Stupid/Condition.class.php');

//! A simple expert system processor
/**
 * Stupid is designed to work like a simple expert system. At first
 * you declare all the rules and actions. Starting the chain reaction it will
 * evaluate rules and trigger the most appropriate action. 
 * 
 * @note Stupid system will trigger ONLY THE FIRST matching rule and no other one. This
 * 	cannot be changed.
 * 
 * @par How to define rules
 * Stupid system is designed to be modular. Stupid class is a rule evaluator
 * by using registered Stupid_Condition evaluators. There are some standard
 * evaluators that ship with the engine but you can always expand it.
 * \n
 * Every evaluator has a unique @b "type", which is used when we are defining rules along
 * with specific evaluator parameters. All the parameters of each rule are given in an associative array.
 * @b Example
 * @code
 * // This rule uses grouping (parenthesis) in regex that act as backreferences
 * // and are given as argument in the action 
 * Stupid::add_rule('show_news',
 *     array('type' => 'url_path', 'path' => '/\/news\/([\d]+)/'));
 * // A rule that uses more than one condition
 * Stupid::add_rule('create_news',
 *     array('type' => 'url_path', 'path' => '/\/news\/\+create/'),
 *     array('type' => 'auth', 'op' => 'ingroup', 'group' => 'admin'));
 * // Evaluate rules and trigger apropriate action
 * Stupid::chain_reaction();
 * 
 * // Show news implementation
 * function show_news($id)
 * ...
 * 
 * // Create news implementation
 * function create_news()
 * ...
 * @endcode
 * You can see add_rule() for syntax information.
 * 
 * @par Standard Condition Evaluators
 * Stupid system ships with a set of standard evaluators, those evaluators
 * will probably fullfill the needs of most cases. Each evaluator has its
 * own parameters and you should read its documentation for more information.
 * \n
 * - UrlParamsCondition\n
 * 	<i> Condition evaluator for various checks on uri parameters </i>
 * - UrlPathCondition\n
 *  <i> Condition evaluator for checks on the path part of the uri. It suppots
 *  full path, PATH_INFO for "index.php/example" routing schema etc </i>
 * - AuthenticationCondition\n
 *  <i> Condition evaluator for checks on WAAS and Group</i>
 * .
 * 
 * @author sque
 *
 */
class Stupid
{
	//! Rules registered in stupid system
	private static $rules = array();

	//! Default action of system
	private static $def_action = false;

	//! Add a new rule in stupid system
	/**
	 * Its rule has one action and one or more rules.
	 * @param $action The action must be a valid php callback object
	 * 	like the name of a function or the object, method array schema.
	 * @param $conditions One or more coditions that ALL must be true
	 * 	for the action to be triggered. Each rule is given as an associative
	 *  array with the parameters of the conditions. Check condition evaluators
	 *  for acceptable options.
	 *  
	 *  @note If you want to reverse the effect of a condtion add an array entry
	 *  	named "not" =\> true
	 *  
	 * @code
	 * // A rule that uses more than one condition
	 * Stupid::add_rule('create_news',
	 *     array('type' => 'url_path', 'path' => '/\/news\/\+create/'),
	 *     array('type' => 'auth', 'op' => 'ingroup', 'group' => 'admin'));
	 * @endcode
	 * @return NULL
	 */
	public static function add_rule()
	{	// Analyze function arguments
		$args = func_get_args();
		if (count($args) < 2)
			return false;
		$action = $args[0];
		$conditions = array_slice($args, 1);
			
		$processed_conditions = array();
		foreach($conditions as $condition)
		{
			if (($cond_obj = Stupid_Condition::create($condition)) === false)
				return false;
			$processed_conditions[]  = $cond_obj;
		}
		$rule['conditions'] = $processed_conditions;
		$rule['action'] = $action;
		self::$rules[] = $rule;
	}
	
	//! Reset system to initial state
	/**
	 * Brings stupid system at its initial state. All rules and default action
	 * will be deleted.
	 * @return NULL
	 */
	public static function reset()
	{
		self::$rules = array();
		self::$def_action = false;
	}
	
	//! Evaluate rules and trigger reactions
	/**
	 * It will start evaluating rules one-by-one in the same order
	 * as they were defined. At the first rule that is true it will
	 * @b reset stupid system and trigger action of this rule. After
	 * that no more actions are evaluated.
	 * 
	 * If all the rules return false, then it triggers the default action.
	 * 
	 * @note Stupid always resets the system before executing an action
	 * 	so that it is reusable inside the action.
	 * 
	 * @return NULL
	 */
	public static function chain_reaction()
	{
		foreach(self::$rules as $rule)
		{	$cond_res = true;
			$action_args = array();
			foreach($rule['conditions'] as $condition)
				if (! ($cond_res = $condition->evaluate($action_args)))
					break;
				else
					$action_args = array_merge($action_args, $condition->action_arguments());

			if ($cond_res)
			{	self::reset();
				call_user_func_array($rule['action'], $action_args);
				return;
			}
		}
		
		// Nothing matched default action
		if (self::$def_action !== false)
		{	$def_action = self::$def_action; 
			self::reset();
			call_user_func($def_action);
		}
	}
	
	//! Set the default action of system
	/**
	 * The action that will be executed in case that no rule is true. 
	 * @param $func The callback function 
	 * @return NULL
	 */
	public static function set_default_action($func)
	{	
	    self::$def_action = $func;
    }
}
?>
