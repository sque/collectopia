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

Layout::open('admin')->activate();

Stupid::add_rule(function(){Net_HTTP_Response::redirect(url($_SERVER['PATH_INFO'] . '/+login'));},
    array('type' => 'authn', 'op' => 'isanon')
);

Stupid::add_rule('admin_search_rebuild',
	array('type' => 'url_path', 'chunk[2]' => '/^search$/', 'chunk[3]' => '/^\+rebuild$/')
);

Stupid::add_rule('admin_user_update',
	array('type' => 'url_path', 'chunk[2]' => '/^user$/', 'chunk[3]' => '/^([a-zA-Z0-9]+)$/', 'chunk[4]' => '/^\+update$/')
);

Stupid::set_default_action('admin_show_default');
Stupid::chain_reaction();

function admin_show_default() {
	
	etag('h1', 'Administrator interface');
	etag('em', 'No css, I know...');
	
	etag('a', tag('h2', 'Rebuild index'))->attr('href', url('/admin/search/+rebuild'));
}

function admin_search_rebuild() {
	
	etag('h3', 'Rebuilding index...');
	$SI = SearchIndex::rebuild();
	etag('h3', 'OK');
}

function admin_user_update($user) {
	if (!($u = User::open($user)))
		return;
		
	$frm = new UI_UpdateUserForm($u);
	Output_HTMLTag::get_current_parent()->append($frm->render());
}