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


require_once dirname(__FILE__) . '/lib/vendor/phplibs/ClassLoader.class.php';
require_once dirname(__FILE__) . '/lib/tools.lib.php';
/**
 * Here you can write code that will be executed at the begining of each page instance
 */

// Autoloader for local and phplibs classes
$phplibs_loader = new ClassLoader(
    array(
    dirname(__FILE__) . '/lib/vendor/phplibs',
    dirname(__FILE__) . '/lib/local',
    dirname(__FILE__) . '/lib/models'
));
$phplibs_loader->set_file_extension('.class.php');
$phplibs_loader->register();

// Zend
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__) . '/lib/vendor');
$zend_loader = new ClassLoader(array(dirname(__FILE__) . '/lib/vendor'));
$zend_loader->register();

// Load static library for HTML
require_once dirname(__FILE__) . '/lib/vendor/phplibs/Output/html.lib.php';

// Load configuration file
require_once dirname(__FILE__) . '/config.inc.php';

// Database connection
DB_Conn::connect(Registry::get('db.host'), Registry::get('db.user'), Registry::get('db.pass'), Registry::get('db.schema'), true);
DB_Conn::query('SET NAMES utf8;');
DB_Conn::query("SET time_zone='+0:00';");
DB_Conn::events()->connect('error',
    create_function('$e', ' error_log( $e->arguments["message"]); '));

// PHP TimeZone
date_default_timezone_set(Registry::get('site.timezone'));

// PHP Session
session_start();
if (!isset($_SESSION['initialized']))
{
    // Prevent session fixation with invalid ids
    session_regenerate_id();
    $_SESSION['initialized'] = true;    
}

// Setup authentication
$auth = new Authn_Backend_DB(array(
    'query_user' => User::open_query()
        ->where('enabled = ?')->push_exec_param(1)
        ->where('username = ?'),
    'field_username' => 'username',
    'field_password' => 'password',
    'hash_function' => array('User', 'hash_function')
));
Authn_Realm::set_backend($auth);

