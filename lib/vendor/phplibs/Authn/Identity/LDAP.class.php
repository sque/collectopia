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


require_once( dirname(__FILE__) . '/../Identity.class.php');

//! Implementation of Authn_Identity for Authn_Backend_LDAP
class Authn_Identity_LDAP implements Authn_Identity
{
    //! An associative array with all user attributes
    private $user_attribs;

    //! The name of the attribute that will be used as idenity's id.
    private $id_attribute;
    
    //! The object is constructed by Authn_Backend_LDAP
    /**
     * @param $user_attribs Associative array with all attributes of user in LDAP catalog.
     * @param $id_attribute The name of the attribute that will be used as idenity's id.
     */
    public function __construct($user_attribs, $id_attribute)
    {
        $this->user_attribs = $user_attribs;
        $this->id_attribute = $id_attribute;
        
        // Check that there is an id attribute
        if (! $this->get_attribute($this->id_attribute))
            throw new RuntimeException("There is no attirubute with name \"{$this->id_attribute}\"!");
    }

    //! Get the Distinguished Name of this identity
    public function dn()
    {   
        return $this->get_attribute('distinguishedname');
    }

    //! Get the principalName of this identity
    public function principalName()
    {   
        return $this->get_attribute('userprincipalname');
    }
    
    //! Get the SAM Account Name of this identity
    public function sam_account_name()
    {
        return $this->get_attribute('samaccountname');
    }
    
    //! Get an attribute from users attributes
    /**
     * @param $name The name of the attribute
     * @return
     *  - The value of attribute.
     *  - @b false on any kind of error.
     */
    public function get_attribute($name)
    {
        if (!isset($this->user_attribs[$name]))
            return false;
        if ($this->user_attribs[$name]['count'] == 0)
            return false;
        return $this->user_attribs[$name][0];
    }
    
    public function id()
    {
        return $this->get_attribute($this->id_attribute);
    }
}

?>
