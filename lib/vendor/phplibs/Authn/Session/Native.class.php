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

//! Use native php sessions to track identity
class Authn_Session_Native implements Authn_Session
{
    //! The index to use in $_SESSION array for storing identity.
    private $session_index;
    
    //! Construct a php native authentication session
    /**
     * @param $session_index The index to use inside $_SESSION
     */
    public function __construct($session_index = 'PHPLIBS_AUTHN_SESSION')
    {
        $this->session_index = $session_index;
    }
    
    
    public function set_identity(Authn_Identity $identity, $ttl = null)
    {
        session_regenerate_id();
        $_SESSION[$this->session_index] = $identity;
    }

    public function get_identity()
    {
        if (!isset($_SESSION[$this->session_index]))
            return false;
        if ($_SESSION[$this->session_index] === null)
            return false;
        return $_SESSION[$this->session_index];
    }


    public function clear_identity()
    {
        $_SESSION[$this->session_index] = null;
    }
}

?>
