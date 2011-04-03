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


//! Manage http cookies
class Net_HTTP_Cookie
{
    //! The name of te cookie
    private $name;

    //! Value of cookie
    private $value = '';

    //! Domain of cookie
    private $domain = '';

    //! Path of cookie
    private $path = '/';

    //! Time when cookie expires
    private $expiration_time = 0;

    //! Flag if the cookie is httponly
    private $httponly = false;

    //! Flag if cookie is secure
    private $secure = false;

    //! Construct a cookie
    /**
     * @param $name The name of the cookie.
     * @param $value The value of the cookie
     * @param $domain The effective domain of the cookie.
     * @param $path The effective path of the cookie.
     * @param $expiration_time The unix time stamp when cookie expires or 0 for session cookie.
     * @param $httponly Set the "httponly" flag of the cookie.
     * @param $secure Set the "secure" flag of the cookie.
     */
    public function __construct($name, $value, $expiration_time = 0, $path = '/', $domain = '', $secure = false, $httponly = false)
    {
        $this->name = $name;
        $this->value = $value;
        $this->domain = $domain;
        $this->path = $path;
        $this->expiration_time = $expiration_time;
        $this->httponly = $httponly;
        $this->secure = $secure;
    }

    //! Get the name of the cookie
    public function get_name()
    {
        return $this->name;
    }

    //! Get the value of the cookie
    public function get_value()
    {   
        return $this->value;
    }

    //! Get the effective domain of the cookie
    public function get_domain()
    {   
        return $this->domain;
    }

    //! Get the effective path of the cookie
    public function get_path()
    {   
        return $this->path;
    }

    //! Get the time this cookie expires
    /**
     * @return Unix timestamp of expiration time or 0 if
     *      it is session cookie.
     */
    public function get_expiration_time()
    {
        return $this->expiration_time;
    }

    //! Check if cookie is session cookie based on expiration time
    public function is_session_cookie()
    {   
        return ($this->expiration_time == 0);
    }    
    
    //! Check "httponly" flag of the cookie
    public function is_httponly()
    {
        return $this->httponly;
    }

    //! Check "seucre" flag of the cookie
    public function is_secure()
    {
        return $this->secure;
    }

    //! Set the name of the cookie
    /**
     * @param $name The new name.
     */
    public function set_name($name)
    {
        $this->name = $name;
    }
    
    //! Set the value of the cookie
    /**
     * @param $value The new value.
     */
    public function set_value($value)
    {
        $this->value = $value;
    }

    //! Set the effective domain of the cookie
    /**
     * @param $domain The new effective domain.
     */
    public function set_domain($domain)
    {
        $this->domain = $domain;
    }

    //! Set the effective path of the cookie
    /**
     * @param $path The new effective path.
     */
    public function set_path($path)
    {
        $this->path = $path;
    }

    //! Set the "secure" flag of the cookie
    /**
     * @param $enabled @b boolean The new state of "secure" flag.
     */
    public function set_secure($enabled)
    {
        return $this->secure = $enabled;
    }
    
    //! Set the "httponly" flag of the cookie
    /**
     * @param $enabled @b boolean The new state of "httponly" flag.
     */
    public function set_httponly($enabled)
    {
        return $this->httponly = $enabled;
    }

    //! Set the expiration time of the cookie.
    /**
     * @param $time
     *  - The unix time stamp of the expiration date.
     *  - @b 0 if the cookie is a session cookie.
     *  .
     */
    public function set_expiration_time($time)
    {
        return $this->expiration_time = $time;
    }

    //! Open a cookie received through web server
    public static function open($name)
    {
        if (!isset($_COOKIE[$name]))
            return false;

        $cookie = new Net_HTTP_Cookie($name, $_COOKIE[$name]);
        return $cookie;
    }

    //! Send cookie to the underlying web server
    public function send()
    {
        setcookie($this->name,
            $this->value,
            ($this->is_session_cookie()?0:$this->expiration_time),
            $this->path,
            $this->domain,
            $this->secure,
            $this->httponly
        );
    }
}
