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


//! Object to register handlers on destruction of this object
class OnDestruct
{
    //! Handlers
    private $handlers = array();

    //! Implement on destruction
    public function __destruct()
    {
        foreach($this->handlers as $handle)
        call_user_func($handle);
    }

    //! Register a new handler
    public function register_handler($callable)
    {
        $this->handlers[] = $callable;
    }

    //! Unregister handler
    public function unregister_handler($callable)
    {
        if (($key = array_search($callable, $this->handlers, true)) !== false)
        unset($this->handlers[$key]);
    }
}

?>
