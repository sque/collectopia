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


class UI_LoginForm extends Output_HTML_Form
{
    public function __construct($redirect_url)
    {
        $this->redirect_url = $redirect_url;

        parent::__construct(array(
			'login-user' => array('display' => 'Username'),
			'login-pass' => array('display' => 'Password', 'type' => 'password'),
        ),
        array('title' => Config::get('site.title') . ' Login',
            'css' => array('ui-form','ui-login'),
		    'buttons' => array(
		        'login' => array('display' =>'Login'),
		        'back' => array('display' => 'Back', 'type' => 'button', 'onclick' => "window.location='{$redirect_url}'")
                )
            )
        );
    }

    public function on_post()
    {
        $user = $this->get_field_value('login-user');
        $pass = $this->get_field_value('login-pass');
        if (Authn_Realm::authenticate($user, $pass))
        {
            Net_HTTP_Response::redirect($this->redirect_url);
        }
        else
        {
            $this->invalidate_field('login-pass', 'The username or password you entered is incorrect.');
        }
    }
};

?>
