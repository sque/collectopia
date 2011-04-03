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

require_once(dirname(__FILE__) . '/../functions.lib.php');
require_once(dirname(__FILE__) . '/Condition/Authentication.class.php');
require_once(dirname(__FILE__) . '/Condition/Authorization.class.php');
require_once(dirname(__FILE__) . '/Condition/Func.class.php');
require_once(dirname(__FILE__) . '/Condition/UrlParams.class.php');
require_once(dirname(__FILE__) . '/Condition/UrlPath.class.php');

//! Base class for condition evaluators of Stupid system.
/**
 * @author sque
 *
 * This class will be used to extend Stupid system by creating new
 * condition evaluators. If you are looking to add rules in
 * stupid system check Stupid::add_rule()
 */
abstract class Stupid_Condition
{
	//! Internal array with all evaluators saved in associative array by their type.
	private static $evaluators = array();
	
	//! Called by derived classes to register them selfs
	public static function register()
	{	
	    $called_class = get_called_class();	
		self::$evaluators[eval("return $called_class::type();")] = $called_class;
	}

	//! Called to create a condition object based on parameters
	public static function create($cond_options)
	{
		// Check if there is an implementation of this condition type
		if (!isset($cond_options['type']))
		{	
		    trigger_error("Cannot create StupidCondition without defining its \"type\"");
			return false;
		}
		if (!isset(self::$evaluators[$cond_options['type']]))
		{	
		    trigger_error("There is no register condition evaluator that can understand " . $cond_options['type']);
			return false;
		}
		
		// Save condition options		
		return $evaluator =  new self::$evaluators[$cond_options['type']]($cond_options);
	}
	
	//! Back references exported by this condtion
	protected $back_references = array();
	
	//! Constructor of condition
	final public function __construct($options)
	{	
	    $this->options = $options;
	}

	//! Params for action (Return an array with the parameters)
	public function action_arguments()
	{
	    return $this->back_references;
    }
	
	//! Published interface for evaluation
	public function evaluate($previous_backrefs= array())
	{	
	    if ( (isset($this->options['not'])) && ($this->options['not'] === true ))
			return ! $this->evaluate_impl($previous_backrefs);	
		return $this->evaluate_impl($previous_backrefs);
	}
	
	//! Returns the unique type of evaluator
	public static function type()
	{   
	    throw new RuntimeException("Not Implemeneted type()");
    }
	
	//! @b ABSTRACT To be implemented by evaluator
	abstract protected function evaluate_impl($previous_arguments);
}
?>
