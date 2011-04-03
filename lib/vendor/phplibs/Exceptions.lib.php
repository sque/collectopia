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


//! An operation was done without an established connection
class NotConnectedException extends RuntimeException {};
class WebActionException extends RuntimeException {
	public function __construct($message = null, $code = null)
	{
		error_log("[Error $code] $message");
		parent::__construct($message, $code);	
	}
};
class Exception404 extends WebActionException{
	public function __construct($message = 'Not Found', $code = '404'){
		parent::__construct($message, $code, $previous);
	}
};
class Exception500 extends WebActionException {
	public function __construct($message = 'Internal server error', $code = '500'){
		parent::__construct($message, $code);
	}
};
