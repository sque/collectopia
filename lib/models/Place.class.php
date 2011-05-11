<?php

/**
 * This file was autogenerated by exporter.
 * @property integer id
 * @property string name
 * @property string short_name
 * @property float loc_long
 * @property float loc_lat
 * @property string markerpack_hash
 * @property integer markerpack_index
 * @property string country
 * @property string city
 * @property string street
 * @property string street_number
 * @property string address
 * @property string email
 * @property string web
 * @property string tel
 * @property string video
 * @property string description
 * @property float rate_current
 * @property integer rate_total
 * @property DateTime created_at
 * 
 * 
 * @property MarkersPack marker 

 * @method Place open() Find and open a record based on primary key.
 * @method Place create() Create a new record and return object.
 */
class Place extends DB_Record
{
	public static $table = "places";

	public static $fields = array(
	    'id' => array('pk' => true,'ai' => true,),
		'short_name',
		'slug',
	    'name' ,
	    'loc_lng' ,
	    'loc_lat' ,
		'markerpack_hash' => array('fk' => 'MarkerPack'),
		'markerpack_index',
	    'country' ,
	  	'area_level_1',
	  	'area_level_2',
	    'city' ,
	    'street' ,
	    'street_number' ,
	    'address' ,
	    'email' ,
	    'web' ,
	    'tel' ,
		'video',
	    'description' ,
	  	'rate_current',
	  	'rate_total',
	    'created_at' => array('type' => 'datetime',),	
	);
	
	/**
	 * Submit a new rating
	 */
	public function submit_rate($rate) {
		$new_rating = (($this->rate_current * $this->rate_total) + (int)$rate) / ($this->rate_total + 1);
		$this->rate_current = (float)$new_rating;
		$this->rate_total += (float)1;
		$this->save();	
	}
	
	/**
	 * Get an array formated in the collectopia API format.
	 * @return array Associative array with all place ready to be jsoned
	 */
	public function to_api() {
		/*$filtered = array_intersect_key($this->toArray(),
			array('id' => 0, 'name'  => 0, 'country'  => 0, 'area_level_1'  => 0,
				'area_level_2'  => 0, 'city'  => 0, 'street'  => 0, 'street_number'  => 0,
				'address'  => 0, 'email'  => 0, 'web'  => 0, 'tel'  => 0, 'video'  => 0,
				'description'  => 0, 'rate_current'  => 0, 'rate_total'  => 0, 'created_at'  => 0
			)
		);*/

		$api_data = array_merge($this->toArray(),
			array('categories' => array(), 'photos' => array())
		);
		// After update this fields transform to strings
		$api_data['rate_total'] = (int) $api_data['rate_total'];
		$api_data['rate_current'] = (float) $api_data['rate_current'];
		
		// Enrich with category info
		foreach(PlacesCats::raw_query()
			->select(array('cat_tag'))->where('place_id = ?')->execute($this->id) as $cat)
		$api_data['categories'][] = $cat['cat_tag'];

		// Enrich with photo info
		foreach(Photo::open_query()
			->where('place_id = ?')->execute($this->id) as $photo) {
			
			$photo->generate_thumb(154, 115);
			$api_data['photos'][] = $photo->to_api(); 
		}
		
		return $api_data;
	}
	
	/**
	 * Build the marker set for this place.
	 */
	public function build_marker_set() {
		
		// Create normal
		$normal = new Image(__DIR__ . '/../../static/images/marker.png');
		imagefttext(
			$normal->gd_handle(),	// resource
			6.4,					// font size
			0,						// angle
			4,						// position X
			20,						// position Y
			imagecolorexact($normal->gd_handle(), 255,255,255),
			Registry::get('markers.fonts.directory') . '/arial.ttf',
			$this->short_name
		);
		
		// Create focused
		$focused = new Image(__DIR__ . '/../../static/images/marker_focus.png');
		imagefttext(
			$focused->gd_handle(),	// resource
			12,					// font size
			0,						// angle
			8,						// position X
			37,						// position Y
			imagecolorexact($focused->gd_handle(), 255,255,255),
			Registry::get('markers.fonts.directory') . '/arial.ttf',
			$this->short_name
		);
		
		// Draw categories
		$draw_cat_square = function($img, $x, $y, $width, $height, $col_fill, $border_trans) {
			$col_black = imagecolorexactalpha($img, 0, 0, 0, $border_trans);

			// Fill
			imagefilledrectangle($img, $x, $y, $x + $width, $y + $height,$col_fill);

			// Border
			imagerectangle($img, $x, $y, $x + $width, $y + $height, $col_black);
			imageline($img, $x + $width +  1, $y + 1, $x + $width +  1, $y + $height -1, $col_black);
			imageline($img, $x - 1, $y + 1, $x -1, $y + $height - 1, $col_black);
			imageline($img, $x + 1, $y - 1, $x + $width -  1, $y - 1, $col_black);
			imageline($img, $x + 1, $y + $height + 1, $x + $width -  1, $y + $height + 1, $col_black);
		};
		
		$normal_x = 48; $normal_step = 9;
		$focus_x = 96; $focus_step = 16;
		foreach(array_reverse($this->categories->all()) as $cat) {
			$color = color_hex_to_dec($cat->color);
			$color = imagecolorexact($normal->gd_handle(), $color[0], $color[1], $color[2]);
			
			$draw_cat_square($normal->gd_handle(), $normal_x, 2, 6, 6, $color, 90);
			$draw_cat_square($focused->gd_handle(), $focus_x, 3, 11, 11, $color, 50);
			$normal_x -= $normal_step;
			$focus_x -= $focus_step;
		}
		
		// Return images
		return array(
			'normal' => $normal,
			'focused' => $focused
		);
	}
	
	/**
	 * Generate marker images for this place. If there is already allocated a marker
	 * pack slot, then it will be overwritten otherwise a new one will be allocated.
	 * 
	 */
	public function generate_markers() {
		// Create markers
		$markers = $this->build_marker_set();
		
		if (empty($this->markerpack_hash)) {
			// Allocate a new slot
			$pack = MarkerPack::find_free_one($this->loc_lat, $this->loc_lng);
			if (($pack_index = $pack->append_markers_set($markers['normal'], $markers['focused'])) !== false) {
				$this->markerpack_hash = $pack->hash;
				$this->markerpack_index = $pack_index;
				$this->save();
			}
		}else {
			// Update the current one
			$this->marker->update_markers_set(
				$this->markerpack_index,
				$markers['normal'], $markers['focused']);
		}
	}
	
	/**
	 * Slug generator for Place objetcs. It is used
	 * at create and update procedure.
	 */
	public static function generate_slug($name) {
		
		// Function to check if a slug is not used
		$is_slug_used = function($slug){
			return count(Place::raw_query()
				->select(array('id'))->where('slug = ?')->execute($slug)) != 0;
		};
		
		// Get the first one
		$base_slug = $slug = Inflector::urlize($name);
		
		if ($is_slug_used($slug)){
			$count = 1;
			do{
				$slug = $base_slug . '-' . $count;
				$count ++;
			}while($is_slug_used($slug));
		}
		
		// We found it
		return $slug;
	}
}

Place::events()->connect('op.pre.create', function($e) {
	$data = & $e->filtered_value;
	$data["created_at"] = new DateTime();
	$data['slug'] = Place::generate_slug($data['name']);
});

Place::events()->connect('op.pre.save', function(Event $e) {
	$r = $e->arguments['record'];

	// Update slug
	if (isset($e->arguments['old_values']['name'])) {
		$r->slug = Place::generate_slug($r->name);
	}
	
	SearchIndex::open()->updatePlace($r);
});

Place::events()->connect('op.post.create', function($e) {
	$p = $e->arguments["record"];
	
	// Append search index
	SearchIndex::open()->addPlace($p);
});

Place::events()->connect('op.pre.delete', function($e) {
	$p = $e->arguments["record"];
	
	// Delete all photos
	foreach($p->photos->all() as $photo)
		$photo->delete();
		
	// Remove categories
	PlacesCats::raw_query()->delete()
		->where('place_id = ?')
		->execute($p->id);
		
	// Remove from search index
	SearchIndex::open()->removePlace($p);	
});

require_once(__DIR__ . '/Photo.class.php');
require_once(__DIR__ . '/MarkerPack.class.php');
require_once(__DIR__ . '/Category.class.php');