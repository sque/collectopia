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


Layout::open('default')->activate();
Layout::open('default')->get_document()->add_meta('noindex', array('name' => 'robots'));


// Get the reference url to redirect back
function reference_url()
{
    $path_chunks = explode('/', $_SERVER['PATH_INFO']);
    $path_chunks =  array_filter($path_chunks,
    create_function('$c',
        'return (($c != "+login") && ($c != "+logout"));')
    );
    $cleaned_path = implode('/', $path_chunks);
    return url($cleaned_path?$cleaned_path:'/');
}


// Logout user if there is someone logged on
Stupid::add_rule(create_function('', 'Authn_Realm::clear_identity(); Net_HTTP_Response::redirect(reference_url());'),
array('type' => 'url_path', 'chunk[-1]' => '/\+logout/'));
Stupid::chain_reaction();

// Login form
if (! Authn_Realm::has_identity())
{
    $form = new UI_LoginForm(reference_url());
    etag('div', $form->render());
}
else
Net_HTTP_Response::redirect(reference_url());

?>
