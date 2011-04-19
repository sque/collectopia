<?php 

/**
 * Form for editing and createing places.
 * @author sque
 *
 */
class PlaceEditForm extends JsonForm
{
	private $db_to_form_fields;
	
	private $form_to_db_fields;
	
	/**
	 * Create a new edit/create form.
	 * @param Image $place If this is an edit form, then supply the place,
	 * otherwise give NULL for new.
	 */
    public function __construct($place = null)
    {
    	$this->place = $place;
    	
    	// DB fields and their form equivalent
    	$this->db_to_form_fields = array(
    	    'short_name' => 'short_name',
    		'name' => 'name',
    		'description' => 'description',
    		'loc_lat' => 'loc_lat',
    		'loc_lng' => 'loc_lng',
    		'country' => 'pos_country',
    		'area_level_2' => 'pos_administrative_area_level_2',
    		'area_level_1' => 'pos_administrative_area_level_1',
    		'city' => 'pos_city',
    		'street' => 'pos_street',
    		'street_number' => 'pos_street_number',
    		'address' => 'pos_address',
    		'email' => 'email',
    		'web' => 'web',
    		'video' => 'video'
    	);
    	foreach($this->db_to_form_fields as $k => $v)
    		$this->form_to_db_fields[$v] = $k;
    	
    	// Prepare all fields
        $fields =  array(
        	'short_name' => array('display' => 'Marker Name', 'regcheck' => '/^.{1,12}$/',
        		'onerror' => 'Short name must be between 3 and 12 characters.',
        		'htmlattribs' => array('maxlength' => 12)),
        	'name' => array('display' => 'Official name', 'regcheck' => '/^.{3,}$/',
        		'onerror' => 'Give a title of the place.'),
			'email' => array('display' => 'E-mail'),
        	'web' => array('display' => 'Website'),
        	'tel' => array('display' => 'Phone'),
        	'video' => array('display' => 'Video URL (YouTube)',
        		/*'hint' => 'Give a vimeo or youtube url with the video you want.'*/),
			'description' => array('display' => 'Description', 'type' => 'textarea',
        		'regcheck' => '/^.{10,}/', 'onerror' => 'Description must be at least 10 letters.'),
        	'photos' => array('type' => 'hidden', 'value' => ''),
        	'loc_lat' => array('type' => 'hidden', 'regcheck' => '/^-?\d{1,3}\.?\d+$/',
        		'onerror' => 'This is not a valid location'),
        	'loc_lng' => array('type' => 'hidden', 'regcheck' => '/^-?\d{1,3}\.?\d+$/',
        		'onerror' => 'This is not a valid location'),
        	'pos_country' => array('type' => 'hidden'),        	
        	'pos_administrative_area_level_2' => array('type' => 'hidden'),
        	'pos_administrative_area_level_1' => array('type' => 'hidden'),
        	'pos_city' => array('type' => 'hidden'),
        	'pos_street' => array('type' => 'hidden'),
        	'pos_street_number' => array('type' => 'hidden'),
        	'pos_address' => array('type' => 'hidden'),
        	'categories' => array('type' => 'hidden', 'regcheck' => '/^[a-z\,]+$/',
        		'onerror' => 'Select at least one category.')
        );
        
        if($place) {
        	// In edit mode populate values
        	foreach($place->toArray() as $k => $v) {
        		if (!isset($fields[$k]))
        			continue;
        		
        		if ($k == 'video'){
        			$vid = explode(':', $v);
        			if ($vid[0] == 'youtube')
        				$fields[$k]['value'] = "http://www.youtube.com/watch?v={$vid[1]}";
        			continue; 
        		}
        		
        		$fields[$k]['value'] = $v;
        	}
        	
        	// Add place photos
        	$photos = array();
        	foreach($place->photos->all() as $photo) {
        		$photo->generate_thumb(48, 48);
        		$photos[] = $photo->id;
        	}
        	$fields['photos']['value'] = implode(',', $photos);
        	
	        // Add place photos
	        $cats = array();        	
        	foreach($place->categories->all() as $cat) {
        		$cats[] = $cat->tag;
        	}
        	$fields['categories']['value'] = implode(',', $cats);
        }
        
        // Call standard parent
        parent::__construct($fields, array());	        
    }    

    /**
     * Extra validations on post
     */
    public function on_post()
    { 
        if (!empty($this->fields['video']['value'])) {
    		if (preg_match_all('#https?://.+\.youtube\.com/((watch.*?v=)|v/)(?P<url>[\w\-]+)#',
    			$this->fields['video']['value'], $matches)) {
    				// Valid Youtube video
    				$this->fields['video']['value'] = 'youtube:' . $matches['url'][0];
    		} else {
    			$this->invalidate_field('video', 'Cannot recognize this video url!');
    		}
    	}
    }
    
    public function create_place($values)
    {
       	// Add the place
       	$place_data = array();
       	foreach($this->form_to_db_fields as $ffrm => $fdb)
       		$place_data[$fdb] = $values[$ffrm];
    	$place = Place::create($place_data);
    	
    	if (! $place ) {
    		echo json_encode(array('error' => 'Cannot create this place'));
    		return;
    	}
    	
    	// Add the categories
    	foreach(explode(',', $values['categories']) as $cat)
    		PlacesCats::create(array('cat_tag' => $cat, 'place_id' => $place->id));
    	
    	// Attach all temporary photos on this place.
    	foreach(explode(',', $values['photos']) as $photo) {
    		if ($photo == '')
    			continue;
    		if (substr($photo, 0, 2) == 's:')
    			$photo = substr($photo, 2);
    		if ($p = Photo::open_temporary($photo))
    			$p->attach_at_place($place);
    	}
    	
    	// generate markers for this place
    	$place->generate_markers();
    	
    	// Return the new place
    	return $place;    	
    }
    
    public function update_place($values) 
    {
    	// Update all fields of this place
    	foreach($this->form_to_db_fields as $ffrm => $fdb) {
    		if (!isset($this->place->$fdb)) {
    			error_log("Place does not have field ${k}");
    			continue;
    		}
    		
    		$this->place->$fdb = $values[$ffrm];
    	}
    	
    	// Update categories    	
    	$this->place->save();
    	
    	// Add the categories
    	$existing_cats = $this->place->categories->all();
    	$existing_cats = array_map(function($c){ return $c->tag; } , $existing_cats);
    	$setup_cats = explode(',', $values['categories']);
    	foreach($setup_cats as $cat) {
    		if (in_array($cat, $existing_cats))
    			continue;
    		PlacesCats::create(array('cat_tag' => $cat, 'place_id' => $this->place->id));
    	}
    	foreach(array_diff($existing_cats, $setup_cats) as $cat) {
    		$this->place->categories->remove(Category::open($cat));
    	}
    	
    	// Attach all temporary photos on this place.
    	$existing_photos = $this->place->photos->all();
    	$existing_photos = array_map(function($p) { return $p->id; }, $existing_photos);
    	$requested_photos = explode(',', $values['photos']);
    	foreach($requested_photos as $photo) {
    		if ($photo == '')
    			continue;
    		if (substr($photo, 0, 2) == 's:')
    			$photo = substr($photo, 2);
    		if ($p = Photo::open_temporary($photo))
    			$p->attach_at_place($this->place);
    	}
		foreach(array_diff($existing_photos, $requested_photos) as $pid) {
			if (!($p = Photo::open($pid)))
				continue;
			if ($p->place_id != $this->place->id)
				continue;
			$p->place_id = null;
			$p->secret = null;
			$p->save();
    	}
    	
    	// Remove the removed ones.
    	
    		
		// Regenerate markers for this place
    	$this->place->generate_markers();
    	
    	// Return the place
    	return $this->place;
    }
    
    /**
     * When all data are valid, lets process the output.
     * @param array $values Submitted data
     */
    public function on_valid($values)
    {
    	if (!$this->place)
    		$this->form_object = $this->create_place($values);
    	else 
    		$this->form_object = $this->update_place($values);

    }
    
    /**
     * Return the object that must be rendered on succes
     */
    public function on_show_object()
    {
    	//var_dump($this->form_object);
    	return $this->form_object->to_api();
    }
};
