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

//! Interface to implement authorization roles
interface Authz_Role
{
    //! Get the name of this role
    public function get_name();

    //! Get an array with parents of this role
    /**
     * Parents must also be implementations of Authz_Role interface.
     */
    public function get_parents();

    //! Check if this role has a specific parent 
    /**
     * @param $name The name of the parent to look for.
     * @return
     *  - @b true If the parent was found.
     *  - @b false If this parent was unknown.
     *  .
     */
    public function has_parent($name);

    //! Get a specific parent
    /**
     * @param $name The name of the parent to look for.
     * @return
     *  - @b Authz_Role The object of the parent
     *  - @b false If this parent was not found.
     *  .
     */
    public function get_parent($name);
}

?>
