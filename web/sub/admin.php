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

Stupid::add_rule('admin_tools',
	array('type' => 'url_path', 'chunk[2]' => '/^tools$/')
);

Stupid::add_rule('admin_user_update',
	array('type' => 'url_path', 'chunk[2]' => '/^user$/', 'chunk[3]' => '/^([a-zA-Z0-9]+)$/', 'chunk[4]' => '/^\+update$/')
);

Stupid::set_default_action('admin_show_default');
Stupid::chain_reaction();

function admin_show_default() {
	
	$total_photos = Photo::raw_query()->select(array('COUNT(*)'))
		->where('place_id IS NOT NULL')->execute();
	$total_size = DB_Conn::query_fetch_all('SELECT SUM(size) FROM photos WHERE place_id IS NOT NULL');
	$total_size = $total_size[0][0];
	
	$total_temp_photos = Photo::raw_query()->select(array('COUNT(*)'))
		->where('place_id IS NULL')
		->where('secret IS NOT NULL')->execute();
	$total_temp_size = DB_Conn::query_fetch_all('SELECT SUM(size) FROM photos WHERE secret IS NOT NULL AND place_id IS NULL');
	$total_temp_size = $total_temp_size[0][0]; 
	
	$total_del_photos = Photo::raw_query()->select(array('COUNT(*)'))
		->where('place_id IS NULL')
		->where('secret IS NULL')->execute();
	$total_del_size = DB_Conn::query_fetch_all('SELECT SUM(size) FROM photos WHERE secret IS NULL AND place_id IS NULL');
	$total_del_size = $total_del_size[0][0]; 
	etag('h3' , 'Statistics');
	etag('ul class="statistics',
		tag('li', tag('strong', 'Total places: '), (string)Place::count()),
		tag('li', tag('strong', 'Place photos: '), (string)$total_photos[0][0], tag('em', " - " . html_human_fsize($total_size))),
		tag('li', tag('strong', 'Temporary photos: '),
			(string)$total_temp_photos[0][0], tag('em', " - " . html_human_fsize($total_temp_size))),
		tag('li', tag('strong', 'Photos of deleted places: '),
			(string)$total_del_photos[0][0], tag('em', " - " . html_human_fsize($total_del_size)))
	);
}

function admin_search_rebuild() {
	
	etag('h3', 'Rebuilding index...');
	$SI = SearchIndex::rebuild();
	etag('h3', 'OK');
}

function admin_tools() {
	etag('a', 'Rebuild index', tag('em', 'This action will delete and recreate the ' .
		'whole database index that is used for searching. It can take some time...'))->attr('href', url('/admin/search/+rebuild'));
}

function admin_user_update($user) {
	if (!($u = User::open($user)))
		return;
		
	$frm = new UI_UpdateUserForm($u);
	Output_HTMLTag::get_current_parent()->append($frm->render());
}