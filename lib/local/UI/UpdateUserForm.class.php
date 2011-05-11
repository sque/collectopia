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


class UI_UpdateUserForm extends Output_HTML_Form
{
    public function __construct($user)
    {
        $this->user = $user;

        parent::__construct(array(			
			'password1' => array('display' => 'Password', 'type' => 'password'),
        	'password2' => array('display' => 'Password', 'type' => 'password'),
        ),
        array('title' => "Update user '{$user->username}'",
            'css' => array('ui-form','ui-login'),
		    'buttons' => array(
		        'login' => array('display' =>'Upadte')
                )
            )
        );
    }

    public function on_post()
    {
    	
        $pass1 = $this->get_field_value('password1');
        $pass2 = $this->get_field_value('password2');
        
        if ($pass1 != $pass2)
        	return $this->invalidate_field('password2', 'Passwords do not match.');
        
        if (empty($pass1))
        	return $this->invalidate_field('password2', 'Password cannot be empty.');
    }
    
    public function on_valid($values)
    {
    	$this->user->password = User::hash_function($values['password1'], $this->user);
    	$this->user->save();
    	Net_HTTP_Response::redirect(url('/admin'));
    }
};
