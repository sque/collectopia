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


Stupid::add_rule('photo_new',
    array('type' => 'url_path', 'chunk[2]' => '/^photo$/', 'chunk[3]' => '/^new$/')
);

Stupid::add_rule('photo_drop_temp',
    array('type' => 'url_path', 'chunk[2]' => '/^photo$/', 'chunk[3]' => '/^(?P<key>[\w]+)$/', 'chunk[4]' => '/^\+drop$/')
);

Stupid::add_rule('place_new',
    array('type' => 'url_path', 'chunk[2]' => '/^place$/', 'chunk[3]' => '/^new$/') 
);
Stupid::add_rule('place_image',
    array('type' => 'url_path', 'chunk[2]' => '/^place$/', 'chunk[3]' => '/^(\d+)$/', 'chunk[4]' => '/^image$/') 
);
Stupid::add_rule('place_rate',
    array('type' => 'url_path', 'chunk[2]' => '/^place$/', 'chunk[3]' => '/^(\d+)$/', 'chunk[4]' => '/^rate$/'),
    array('type' => 'url_params', 'op' => 'isnumeric', 'param' => 'rate') 
);
Stupid::add_rule('place_all',
    array('type' => 'url_path', 'chunk[2]' => '/^place$/') 
);

Stupid::add_rule('search',
    array('type' => 'url_path', 'chunk[2]' => '/^search$/'),
    array('type' => 'url_params', 'op' => 'isset', 'param' => 'query') 
);
Stupid::add_rule('category_all',
    array('type' => 'url_path', 'chunk[2]' => '/^category/') 
);
Stupid::set_default_action('data_default');
Stupid::chain_reaction();

function color_hex_to_dec($hex){
	return array(
		hexdec(substr($hex, 0, 2)),
		hexdec(substr($hex, 2, 2)),
		hexdec(substr($hex, 4, 2))
	);
}

function data_default(){
	Layout::open('default')->activate();
	$frm = new UI_PlaceNewForm();
	etag('div class="panel"',
		tag('div',
			tag('span class="title"', 'create'),
			tag('span class="close"', 'close'),
			$frm->render(),
			tag('ul class="actions"',
				tag('li', tag('a href="#"', 'send')),
				tag('li', tag('a href="#"', 'print'))
			)
		)		
	);
}

function place_new(){
	sleep(1.5);
	Layout::open('default')->deactivate();
	$frm = new UI_PlaceNewForm();
	echo $frm->render();
}

function place_image($id) {
	if (!$p = Place::open(array('id' => $id)))
		throw new Exception404();

	$image = new Image(dirname(__FILE__) . '/../../static/images/marker_dark.png');
	imagefttext(
		$image->gd_handle(),	// resource
		6.4,						// font size
		0,						// angle
		3,						// position X
		20,						// position Y
		imagecolorexact($image->gd_handle(), 255,255,255),
		dirname(__FILE__) . '/../../static/fonts/arial.ttf',
		$p->name);
		
	// Put categories
	$x = 55;
	foreach($p->categories->all() as $cat) {
		$color = color_hex_to_dec($cat->color);
		imagefilledellipse(
			$image->gd_handle(),
			$x,
			5,
			9,
			9,
			imagecolorexact($image->gd_handle(), $color[0], $color[1], $color[2])
		);		
		$x -= 12;
	}
	
	// Dump image
	$image->dump();	
}

function place_rate($id) {
	if (!$p = Place::open(array('id' => $id)))
		throw new Exception404();
	$vote_rate = Net_HTTP_RequestParam::get('rate');
	
	$new_rating = (($p->rate_current * $p->rate_total) + $vote_rate) / ($p->rate_total + 1);
	$p->rate_current = $new_rating;
	$p->rate_total += 1;
	$p->save();
	
	header('Content-type: application/json');
	echo json_encode($p->toArray());
}

function place_all(){
	header('Content-type: application/json');
	$places = Place::open_all();
	$data = array();
	foreach($places as $p)
		$data[$p->id] = $p->all_data();

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
    		'id' => $r->id,
    		'name' => $r->name,
    		'country' => $r->country,
    		'city' => $r->city
    	);
	}
	
	echo json_encode($hits);
}

function photo_new() {
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
	try {
		$img = @new Image($_FILES['image']['tmp_name']);
	} catch(Exception $e) {
		echo json_encode(array(
			'error' => 'Error reading image.',
			'name' => $_FILES['image']['name']
		));
		return;
	}
	
	$img_data = $img->data(array('format' => IMAGETYPE_JPEG, 'quality' => 93));
	$data_hash = md5($img_data);

	$tmp_photo = TmpPhoto::create(array(
		'key' => md5($data_hash . rand(1, getrandmax())),
		'name' => $_FILES['image']['name'],
		'data_hash' => $data_hash
	));
	file_put_contents($tmp_photo->fpath(), $img_data);
	$thumb = $img->resize(48, 48, false);
	
	// Clean up expired photos
	TmpPhoto::cleanUpExpired();
	
	
	echo json_encode(array(
		'name' => $tmp_photo->name,
		'key' => $tmp_photo->key,
		'type' => 'image/jpeg',
		'size' => count($img_data),
		'thumb' => 'data:image/jpeg;base64,' .base64_encode($thumb->data(array('format' => IMAGETYPE_JPEG, 'quality' => 91)))
	));	
}

function photo_drop_temp($key) {
	if (!($photo = TmpPhoto::open($key))){
		include __DIR__ . '/../not_found.php';
		exit;
	}

	$photo->delete();
}