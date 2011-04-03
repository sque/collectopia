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


require_once(dirname(__FILE__) . '/../Session.class.php');

//! Track identity inside the instance of this object
class Authn_Session_Instance implements Authn_Session
{
    //! The session identity
    private $online_identity;
    
    public function __construct()
    {
        $this->online_identity = false;
    }
        
    public function set_identity(Authn_Identity $identity, $ttl = null)
    {
        $this->online_identity = $identity;
    }

    public function get_identity()
    {
        return $this->online_identity;
    }
    
    public function clear_identity()
    {
        $this->online_identity = false;
    }
}

?>
