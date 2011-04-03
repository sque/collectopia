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

//! Use a cache engine to store tracked identities
class Authn_Session_Cache implements Authn_Session
{
    //! Cache Engine
    private $cache;

    //! Current session id
    private $session_id = null;

    //! The cookie that will be used to save id.
    private $cookie;

    //! Cache storage constructor
    /**
     * @param $cache The cache engine that will be used.
     * @param $cookie Cookie to be used for saving identity.
     *  All the parameters of cookie will be used except of value which will
     *  be changed to the appropriate one.
     */
    public function __construct(Cache $cache, Net_HTTP_Cookie $cookie)
    {
        $this->cache = $cache;
        $this->cookie = $cookie;

        // Check if there is already a cookie
        $received_cookie = Net_HTTP_Cookie::open($cookie->get_name());
        if ($received_cookie)
            $this->session_id = $received_cookie->get_value();
    }

    public function set_identity(Authn_Identity $identity, $ttl = null)
    {   
        // Clear identity
        $this->clear_identity();

        // Create a new sessionid
        // Uniqid() without $entropy = true is just an alias for mircoseconds.
        // rand() is an direct call to system's libc rand implementation preseeded.
        // mt_rand() is a better random generator that will be prefixed to uniqid
        // sha1() just hides clues about returned values of mt_rand() and uniquid()
        // however it does not protect you if mt_rand() and uniqid() are time dependant.
        $this->session_id = hash('sha512', sha1(uniqid((string)mt_rand(), true)) .  sha1(rand()));

        // Send cookie
        if ($ttl)
            $this->cookie->set_expiration_time(time() + $ttl);
        $this->cookie->set_value($this->session_id);
        $this->cookie->send();

        // Save in cache
        $this->cache->set(
            $this->session_id,
            $identity,
            ($this->cookie->is_session_cookie()?0:$this->cookie->get_expiration_time() - time())
        );
    }

    public function get_identity()
    {
        if ($this->session_id === null)
            return false;

        $identity = $this->cache->get($this->session_id, $succ);
        if (!$succ)
        {   
            $this->clear_identity();
            return false;
        }

        return $identity;
    }

    public function clear_identity()
    {   
        // Remove data from cache
        if ($this->session_id)
            $this->cache->delete($this->session_id);

        // Reset session_id
        $this->session_id = null;

        // Delete cookie
        $this->cookie->set_value('');
        $this->cookie->send();
    }
}

?>
