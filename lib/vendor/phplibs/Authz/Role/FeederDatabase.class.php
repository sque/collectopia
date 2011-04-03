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


require_once dirname(__FILE__) . '/Feeder.class.php';
require_once dirname(__FILE__) . '/Database.class.php';


//! Implementation of Database Role Feeder
class Authz_Role_FeederDatabase implements Authz_Role_Feeder
{
    //! Options of database connection
    protected $options;
    
    
    //! Construct database role feeder
    /**
     * @param $options An associative array with options
     *  - @b role_query [@b Mandatory]: A DB_ModelQuery object for role querying
     *  - @b role_name_field [@b Mandatory]: The field that holds the role name.
     *  - @b parents_query [Default = null]: The DB_ModelQuery object for role's parent querying.
     *  - @b parent_name_field [Default = null]: The field that holds the parent name.
     *  - @b parent_name_filter_func [Default = null]: A filter function to pass the parents name.
     *  - @b role_class [Default = Authz_Role_Database]: The class for creating role objects.
     *  .
     */
    public function __construct($options)
    {
        $def_options = array(
            'role_query' => null,
            'role_name_field' => null,
            'parents_query' => null,
            'parent_name_field' => null,
            'parent_name_filter_func' => null,
            'role_class' => 'Authz_Role_Database'
        );

        $this->options = array_merge($def_options, $options);
        
        if (!$this->options['role_query'])
            throw new InvalidArgumentException('Missing mandatory option "role_query".');
            
        if (!$this->options['role_name_field'])
            throw new InvalidArgumentException('Missing mandatory option "role_name_field".');
    }
    
    //! Get the options of this instance.
    public function get_options()
    {
        return $this->options;
    }
    
    public function has_role($name)
    {   
        $result = $this->options['role_query']->execute($name);
        if (count($result) === 0)
            return false;
        return true;
    }
    
    public function get_role($name)
    {
        if (!$this->has_role($name))
            return false;
        
        return new $this->options['role_class']($name, $this->options);
    }
}
?>
