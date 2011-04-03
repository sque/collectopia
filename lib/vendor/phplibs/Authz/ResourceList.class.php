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


require_once dirname(__FILE__) . '/Role/Feeder.class.php';
require_once dirname(__FILE__) . '/ResourceClass.class.php';

//! A containers for resources
class Authz_ResourceList
{
    //! The array that hold all resource classes
    private $resources = array();
    
    //! Add a new resource class in container
    /**
     * @param $name The name of the new resource class.
     * @param $parent
     *  - The name of the parent of this new class.
     *  - @b null if this resource has no parent.
     *  .
     * @return Authz_ResourceClass holding the new resource.
     * @throws InvalidArgumentException If there is already a resource with this name.
     * @throws InvalidArgumentException If the parent is unknwon.
     */
    public function add_resource($name, $parent = null)
    {
        // Check for duplication
        if (isset($this->resources[$name]))
            throw new InvalidArgumentException("There is already resource with name \"{$name}\"");

        // Check for broken dependency
        if ($parent !== null)
            if (!$this->has_resource($parent))
                throw new InvalidArgumentException(
                    "Cannot add resource that depends on unknown resource \"{$parent}\"");

        $this->resources[$name] = new Authz_ResourceClass(
            $name,
            ($parent?$this->get_resource($parent):null)
        );
        
        return $this->resources[$name];
    }
    
    //! Remove a resource from this container
    /**
     * @param $name The name of the resource class to remove.
     * @return
     *  - @b true If the resource was removed succesfully.
     *  - @b false On any kind of error.
     *  .
    * @throws RuntimeException if there is another resource that depends on this one.
     */
    public function remove_resource($name)
    {
        if (!isset($this->resources[$name]))
            return false;
            
        foreach($this->resources as $res)
            if ($res->has_parent())
                if ($res->get_parent()->get_name() == $name)
                    throw new RuntimeException(
                        "Cannot remove resource \"{$name}\" because \"{$res->get_name()}\" depends on it.");

        unset($this->resources[$name]);
        return true;
    }
    
    //! Get a resource class or instance from this container
    /**
     * @param $name The name of the resource class.
     * @param $instance The id of the resource class instance.
     * @return
     *  - @b Authz_ResourceClass if you asked for resource without instance.
     *  - @b Authz_Resource if you asked for resource instance.
     *  - @b false if it was not found.
     *  .
     */
    public function get_resource($name, $instance = null)
    {
        if (!isset($this->resources[$name]))
            return false;
            
        if ($instance === null)
            return $this->resources[$name];

        return $this->resources[$name]->get_instance($instance);
    }
    
    //! Check that there is a resource class in this container.
    /**
     * @param $name The name of the resource class.
     */
    public function has_resource($name)
    {
        return isset($this->resources[$name]);
    }
}

?>
