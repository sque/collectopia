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


require_once dirname(__FILE__) . '/Identity.class.php';
require_once dirname(__FILE__) . '/Backend.class.php';
require_once dirname(__FILE__) . '/Session.class.php';
require_once dirname(__FILE__) . '/Session/Native.class.php';

//! Static singleton authentication realm
class Authn_Realm
{
    //! The authentication backend that will be used
    static private $backend = null;

    //! The session storage that will be used
    static private $session = null;

    //! The event dispatcher for events
    static private $event_dispatcher = null;

    //! Set the authentication backend of the realm
    /**
     * @param $backend Any valid Authn_Backend implementation.
     */
    static public function set_backend(Authn_Backend $backend)
    {   
        self::$backend = $backend;
    }

    //! Get the current authentication backend.
    static public function get_backend()
    {   
        return self::$backend;
    }

    //! Set the current session storage engine.
    /**
     * @param $session Any valid Authn_Session implementation.
     */
    static public function set_session(Authn_Session $session)
    {   
        self::$session = $session;
    }

    //! Get the current storage session.
    static public function get_session()
    {
        return self::$session;
    }

    //! Get the EventDispatcher of Authn_Realm
    /**
	 * Events are announced through an EventDispatcher object. The following
	 * events are valid:
	 *  - @b auth.successful: A successful authentication took place.
	 *  - @b auth.error: An identity authentatication failed.
	 *  - @b ident.clear: The current authenticated identity was cleared.
	 * .
     */
    static public function events()
    {   if (self::$event_dispatcher === null)
            self::$event_dispatcher = new EventDispatcher(array(
                'auth.successful',
                'auth.error',
                'ident.clear'
            ));
        return self::$event_dispatcher;
    }

    //! Check if it has an authenticated identity
    /**
     * @return
     *  - @b true if there is an authenticated identity on this realm.
     *  - @b false if the current user is anonymous.
     */
    static public function has_identity()
    {   
        return (self::$session->get_identity() != false);
    }

    //! Get current authenticated identity
    /**
     * @return
     *  - @b Authn_Identity object of the authenticated identity.
     *  - @b false If there is no authenticated identity.
     */
    static public function get_identity()
    {   
        return self::$session->get_identity();
    }

    //! Clear current authenticated identity
    static public function clear_identity()
    {
        $identity = self::has_identity();
        if (!$identity)
            return false;

        self::events()->notify('ident.clear', array('identity' => $identity));
        
        return self::$session->clear_identity();
    }

    //! Authenticate a (new) identity on this realm
    /**
     * @param $username The username of the identity
     * @param $password The password of identity
     * @param $ttl
     *  - An explicit declaration of expiration time for this authentication.
     *  - @b null if you want to follow the Authn_Session default policy.
     */
    static public function authenticate($username, $password, $ttl = null)
    {
        if (!self::$backend)
            return false;

        // Clear previous one
        if (self::has_identity())
            self::clear_identity();

        $id = self::$backend->authenticate($username, $password);
        if (! ($id instanceof Authn_Identity))
        {   
            self::events()->notify('auth.error', array('username' => $username, 'password' => $password));
            return false;
        }
        self::events()->notify('auth.successful', array('username' => $username, 'password' => $password));

        // Save session
        self::$session->set_identity($id, $ttl);
        return $id;
    }
}

// Default session storage is set to native
Authn_Realm::set_session(new Authn_Session_Native());

?>
