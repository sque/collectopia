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


require_once dirname(__FILE__) . '/ACL.class.php';
require_once dirname(__FILE__) . '/Role/Feeder.class.php';

//! Representation of resource.
class Authz_Resource
{
    //! The name of the resource
    protected $name;
    
    //! The parent of this resource.
    protected $parent = null;
    
    //! The Authz_ACL of this resource.
    protected $acl;

    //! Construct a new resource
    /**
     * @param $name The name of this resource.
     * @param $parent 
     * - @b Authz_Resource The parent of this resource.
     * - @b null If this resource has no parent.
     */
    public function __construct($name, $parent = null)
    {
        $this->acl = new Authz_ACL();
        
        $this->name = $name;
        
        if (is_object($parent))
            $this->parent = $parent;
    }
    
    //! Get the name of this resource.
    public function get_name()
    {
        return $this->name;
    }

    //! Get the parent of this resource.
    public function get_parent()
    {
        return $this->parent;
    }
    
    //! Check if this resource has parent.
    public function has_parent()
    {
        return $this->parent !== null;
    }
    
    //! Get the access control list of this resource.
    public function get_acl()
    {
        return $this->acl;
    }
    
    //! Search through role inheritance and resource inheritance for effective ACE
    /**
     * @param $role The name of the role to search for effective ACE.
     * @param $action The action to search for.
     * @param $roles The roles feeder that describes roles inheritance.
     * @param $depth A return value of the ACE's depth.
     *  This value is relative to implementation but it can be used to compare weight of ACEs.
     * @return
     *  - @b Authz_ACE The effective ACE that was found.
     *  - @b null If no ACE was found for criteria.
     *  .
     */
    public function effective_ace($role, $action, Authz_Role_Feeder $roles, & $depth)
    {   $matched = array('ace' => null, 'depth' => -1);    
        
        // Search local acl
        if ($ace = $this->acl->effective_ace($role, $action))
        {   
            $matched = array('ace' => $ace, 'depth' => ($ace->is_role_null()?500:0));

            if (! $ace->is_role_null())
            {
                $depth = $matched['depth'];
                return $ace;
            }
        }

        // Search for role inheritance
        if ($roles->has_role($role))
        {
            foreach($roles->get_role($role)->get_parents() as $prole)
            {   $pdepth = -1;
                
                if (($ace = $this->effective_ace($prole->get_name(), $action, $roles, $pdepth)) === null)
                    continue;

                if ($ace->is_role_null())
                    continue;
                    
                if (($matched['depth'] == -1) || ($pdepth < $matched['depth']))
                {
                    $matched['depth'] = 1 + $pdepth;
                    $matched['ace'] = $ace;
                }
            }
        }
        
        // Resolved using role inheritance
        if ($matched['depth'] >= 0)
        {
            $depth = $matched['depth'];
            return $matched['ace'];
        }
        
        // Search for resource inheritance
        if ($this->has_parent())
            if (($ace = $this->get_parent()->effective_ace($role, $action, $roles, $pdepth)) !== null)
            {
                $depth = $pdepth + 10000;
                return $ace;
            }

        return null;
    }
}

?>
