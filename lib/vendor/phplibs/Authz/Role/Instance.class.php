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


//! Implementation of Authz_Role for Authz_Role_FeederInstance
class Authz_Role_Instance implements Authz_Role
{
    //! The name of the role
    private $name;
    
    //! Array of Authz_Role_Instance parents.
    private $parents = array();
    
    //! Construct a new role
    /**
     * @param $name The name of the role.
     * @param $parents An array of parent objects.
     */
    public function __construct($name, $parents = array())
    {
        $this->name = $name;
        
        foreach($parents as $p)
            $this->parents[$p->get_name()] = $p;
    }

    public function get_name()
    {
        return $this->name;
    }
    
    public function get_parents()
    {
        return $this->parents;
    }
    
    public function has_parent($name)
    {
        return array_key_exists($name, $this->parents);
    }
    
    public function get_parent($name)
    {   
        if (!$this->has_parent($name))
            return false;

        return $this->parents[$name];
    }
}

?>
