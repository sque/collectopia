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


//! Interface to implement roles feeder
interface Authz_Role_Feeder
{
    //! Check if there is a role in feeder.
    /**
     * @param $name The name of the feeder.
     * @return
     *  - @b true if the role was found.
     *  - @b false if there is no role with that name.
     */
    public function has_role($name);
    
    //! Get a role from the feeder
    /**
     * @param $name The name of the role we are looking for.
     * @return
     *  - @b Authz_Role the role object.
     *  - @b false if no role was found with that name.
     *  .
     */
    public function get_role($name);
}

?>
