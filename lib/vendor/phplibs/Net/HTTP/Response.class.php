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


require_once dirname(__FILE__) . '/Cookie.class.php';

//! Manage the native HTTP response
class Net_HTTP_Response
{
    //! Ask user-agnet to redirect in a new url
    /**
     * @param $url The absolute or relative url to redirect.
     * @param $auto_exit If @b true the program will terminate immediatly.
     */
    static public function redirect($url, $auto_exit = true)
    {   
        header('Location: '. $url);
        if ($auto_exit)
            exit;
    }

    //! Define the content type of this response
    /**
     * @param $mime The mime of the content.
     */
    static public function set_content_type($mime)
    {   
        header('Content-type: ' . $mime);
    }

    //! Set the error code and message of response
    /**
     * @param $code 3-digits error code.
     * @param $message A small description of this error code.
     */
    static public function set_error_code($code, $message)
    {   
        header("HTTP/1.1 {$code} {$message}");
    }
}
