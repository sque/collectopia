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


require_once( dirname(__FILE__) . '/../Backend.class.php');
require_once( dirname(__FILE__) . '/../Identity/LDAP.class.php');

class Authn_Backend_LDAP implements Authn_Backend
{
    //! The normalized options of this instance.
    private $options = array();

    //! Get the options of this instance.
    public function get_options()
    {
        return $this->options;
    }

    //! Create an instance of ldap backend
    /**
     * @param $options An associative array of options.
     *  - @b url [@b *] The url to connect on server.
     *  - @b baseDN [@b *] The baseDN to search for users.
     *  - @b id_attribute [Default: userprincipalname] The attribute of user object that represents its unique id.
     *  - @b force_protocol_version [Default: null] Force a specfic version of communication protocol.
     *  - @b default_domain [Default: null] A domain to be postfixed in username before validating username.
     *  [@b *] mandatory field.
     * @throws InvalidArgumentException If one of the mandatory fields is missing.
     */
    public function __construct($options = array())
    {
        if (! isset(
            $options['url'],
            $options['baseDN'])
        )   throw new InvalidArgumentException('Missing mandatory options for Authn_Backend_LDAP!');

        // Merge with default options and save
        $this->options = array_merge(array(
            'username' => false,
            'password' => false,
            'id_attribute' => 'userprincipalname',
            'force_protocol_version' => null,
            'default_domain' => null),
        $options);
    }

    public function authenticate($username, $password)
    {   
        if (($conn = ldap_connect($this->options['url'])) == false)
            return false;

        // Prefix default domain to username if not there
        if ($this->options['default_domain'])
            if (strpos($username, '@') === false)
                $username .= '@' . $this->options['default_domain'];
            
        if ($this->options['force_protocol_version'])
            if (!ldap_set_option($conn, LDAP_OPT_PROTOCOL_VERSION, $this->options['force_protocol_version']))
                return false;

        if (! @ldap_bind($conn, $username, $password))
            return false;
            
        //! Fetch the user object
        if (!($search = ldap_search($conn, $this->options['baseDN'], '(userPrincipalName=' . $username . ')')))
            return false;

        $users = ldap_get_entries($conn, $search);
        if ((!$users) || ($users['count'] != 1))
            return false;

        return new Authn_Identity_LDAP($users[0], $this->options['id_attribute']);
    }
}
