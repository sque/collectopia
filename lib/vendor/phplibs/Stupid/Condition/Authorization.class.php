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

//! Implementation of auth Stupid_Condition
/**
 * A condition evaluator that can perform checks on the Authz.\n
 * This evaluator implements the <b> type = "authz"</b> 
 *
 * @par Acceptable condition options
 * - @b resource [@b Mandatory]: The class name of the resource.
 * - @b instance [Default = null]: The instance of the resource to check for.
 * - @b backref_instance [Default = false]: If you want to pass the instance from
 *  a backreference, set this to the index key in backref array.
 * - @b action [@b Mandatory]: The action to check for.
 * .
 * 
 * @par Examples
 * @code
 * // This action is accesible only for those authorized for "view" action on "news" resource.
 * Stupid::add_rule('create_news',
 *     array('type' => 'url_path', 'chunk[0]' => '/\/news\/', 'chunk[1]' => '/(\d+)/'),
 *     array('type' => 'authz', 'resource' => 'news', 'backref_instance' => 0, 'action' => 'view'));
 * @endcode
 */
class Stupid_Condition_Authorization extends Stupid_Condition
{
	public static function type()
	{	
	    return 'authz';
    }

	public function evaluate_impl($previous_backrefs)
	{
		// Default condition values
		$defcond = array(
		    'instance' => null,
		    'backref_instance' => false
		);
		$options = array_merge($defcond, $this->options);
		
		// Check mandatory options
		if ((!isset($options['resource'])) || (!isset($options['action'])))
		    throw new InvalidArgumentException('Stupid_Condition[Authz]: Undefined mandatory options!');

        // Get instance
        if ($options['backref_instance'] !== false)
        {   if (!isset($previous_backrefs[$options['backref_instance']]))
                throw new InvalidArgumentException(
                    'Stupid_Condition[Authz] there is no backref with index key "' . $options['backref_instance'] . '"!');
                    
            $options['instance'] = $previous_backrefs[$options['backref_instance']];
        }
        return Authz::is_allowed(array($options['resource'], $options['instance']), $options['action']);
	}
}
Stupid_Condition_Authorization::register();

?>
