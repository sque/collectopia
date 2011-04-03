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


require_once dirname(__FILE__) . '/Resource.class.php';

//! Representation of resource class.
/**
 * Resource class is an extension of basic resource to support instances.
 */
class Authz_ResourceClass extends Authz_Resource
{
    //! An array with all instances of the class
    protected $instances = array();

    //! Get an instance based on its id
    /**
     * If the instance is known the previous handle is returned,
     * otherwise a new one inheriting the class is returned.
     */
    public function get_instance($id)
    {
        if (isset($this->instances[$id]))
            return $this->instances[$id];
        
        return $this->instances[$id] =
            new Authz_Resource(
                (string)$id,
                $this);
    }
    
}

?>
