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


/* Photos Actions */
Stupid::add_rule('photo_create',
    array('type' => 'url_path', 'chunk[2]' => '/^photo$/', 'chunk[3]' => '/^\+new$/')
);
Stupid::add_rule('photo_delete_temp',
    array('type' => 'url_path', 'chunk[2]' => '/^photo$/', 'chunk[3]' => '/^(?P<secret>[\w]+)$/', 'chunk[4]' => '/^\+delete-temp$/'),
    array('type' => 'url_params', 'op' => 'isset', 'param' => 'secret', 'param_type' => 'post') 
);

/* Place actions */
Stupid::add_rule('place_new',
    array('type' => 'url_path', 'chunk[2]' => '/^place$/', 'chunk[3]' => '/^\+new$/')
);
Stupid::add_rule('place_id_edit',
    array('type' => 'url_path', 'chunk[2]' => '/^place$/', 'chunk[3]' => '/^@(\d+)$/', 'chunk[4]' => '/^\+edit$/')
);
Stupid::add_rule('place_id_rate',
    array('type' => 'url_path', 'chunk[2]' => '/^place$/', 'chunk[3]' => '/^@(\d+)$/', 'chunk[4]' => '/^\+rate$/'),
    array('type' => 'url_params', 'op' => 'isnumeric', 'param' => 'rate') 
);

Stupid::add_rule('place_all',
    array('type' => 'url_path', 'chunk[2]' => '/^place$/', 'chunk[3]' => '/^\+all$/') 
);

Stupid::add_rule('search',
    array('type' => 'url_path', 'chunk[2]' => '/^search$/'),
    array('type' => 'url_params', 'op' => 'isset', 'param' => 'query') 
);
Stupid::add_rule('category_all',
    array('type' => 'url_path', 'chunk[2]' => '/^category/') 
);
Stupid::set_default_action(function() { throw new Exception404('Unknown action.'); } );
Stupid::chain_reaction();

function place_new(){
	Layout::open('default')->deactivate();	
	$pef = new PlaceEditForm();
	$pef->json_render();
}

function place_id_edit($pid){
	if (!($p = Place::open($pid)))
		throw new Exception404("Unknown place.");
		
	Layout::open('default')->deactivate();
	$pef = new PlaceEditForm($p);
	$pef->json_render();
}

function place_id_rate($id) {
	if (!$p = Place::open(array('id' => $id)))
		throw new Exception404('Unknown place.');
	$vote_rate = Net_HTTP_RequestParam::get('rate', 'post');
	
	$p->submit_rate($vote_rate);	
	
	header('Content-type: application/json');
	echo json_encode($p->to_api());
}

function place_all(){
	ob_start("ob_gzhandler");
	header('Content-type: application/json');
	$places = Place::open_all();
	$data = array();
	foreach($places as $p)
		$data[$p->id] = $p->to_api();

	echo json_encode($data);
}

function category_all(){
	header('Content-type: application/json');
	echo json_encode(Category::open_all_to_array());
}

function search(){
	header('Content-type: application/json');

	$query = Net_HTTP_RequestParam::get('query');	
	$search_results = SearchIndex::open()->engine->find($query);
	$hits = array();
	foreach($search_results as $r) {
    	$hits[] = array(
    		'type' => 'place',
    		'score' => $r->score,
    		'id' => $r->getDocument()->id,
    		'name' => $r->getDocument()->name,    		
    		'country' => $r->getDocument()->country,
    		'city' => $r->getDocument()->city
    	);
	}
	
	echo json_encode($hits);
}

function photo_create() {
	header('Content-type: application/json');
	
	if ((!isset($_FILES['image'])) || ($_FILES['image']['error'] != 0))
		return;
		
	// Extension check
	$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
	if (!in_array($ext, array('jpg', 'png', 'gif'))) {
		echo json_encode(array(
			'error' => 'Non supported file type.',
			'name' => $_FILES['image']['name']
		));
		return;
	}
	
	// Create a temporary photo
	$photo = Photo::create_from_file($_FILES['image']['name'], $_FILES['image']['tmp_name'], $img);
	if (!$photo) {
		echo json_encode(array(
			'error' => 'Error reading image.',
			'name' => $_FILES['image']['name']
		));
		return;
	}
	
	// Create thumb
	$thumb = $img->resize(48, 48, false);
	
	// Clean up expired photos
	Photo::clean_up_expired();
	
	// Return API object
	$pobj = $photo->to_api();
	$pobj['thumb_url'] = 'data:image/jpeg;base64,' .base64_encode($thumb->data(array('format' => IMAGETYPE_JPEG, 'quality' => 91))); 
	echo json_encode($pobj);	
}

function photo_delete_temp($secret) {
	
	if (Net_HTTP_RequestParam::get('secret', 'post') != $secret)
		throw new Exception404();
	
	if (!($photo = Photo::open_temporary($secret)))
		throw new Exception404();

	$photo->delete();
}