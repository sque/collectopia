<?php 

/**
 * Form for editing and createing places.
 * @author sque
 *
 */
class PlaceEditForm extends JsonForm
{
	/**
	 * Create a new edit/create form.
	 * @param Image $place If this is an edit form, then supply the place,
	 * otherwise give NULL for new.
	 */
    public function __construct($place = null)
    {
    	$this->place = $place;
    	
    	// Prepare all fields
        $fields =  array(
        	'short_name' => array('display' => 'Short Name', 'regcheck' => '/^.{1,12}$/',
        		'hint' => 'A short name less than 12 characters.',
        		'onerror' => 'Short name must be between 3 and 12 characters.',
        		'htmlattribs' => array('maxlength' => 12)),
        	'name' => array('display' => 'Long name', 'regcheck' => '/^.{3,}$/',
        		'onerror' => 'Give a title of the place.'),
			'email' => array('display' => 'E-mail'),
        	'web' => array('display' => 'Website'),
        	'tel' => array('display' => 'Phone'),
        	'video' => array('display' => 'Video URL', 'hint' => 'Give a vimeo or youtube url with the video you want.'),
			'description' => array('display' => 'Description', 'type' => 'textarea',
        		'regcheck' => '/^.{10,}/', 'onerror' => 'Give a description of the place.'),
        	'photos' => array('type' => 'hidden', 'value' => ''),
        	'loc_lat' => array('type' => 'hidden', 'regcheck' => '/^-?\d{1,3}\.?\d+$/'),
        	'loc_lng' => array('type' => 'hidden', 'regcheck' => '/^-?\d{1,3}\.?\d+$/'),
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
        	$fields['photos']['value'] = array();
        	foreach($place->photos->all() as $photo) {
        		$photo->generate_thumb(48, 48);
        		$fields['photos']['value'][] = $photo->toArray();
        	}
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
    	$place = Place::create(array(
    		'short_name' => $values['short_name'],
    		'name' => $values['name'],
    		'description' => $values['description'],
    		'loc_lat' => $values['loc_lat'],
    		'loc_lng' => $values['loc_lng'],
    		'country' => $values['pos_country'],
    		'area_level_2' => $values['pos_administrative_area_level_2'],
    		'area_level_1' => $values['pos_administrative_area_level_1'],
    		'city' => $values['pos_city'],
    		'street' => $values['pos_street'],
    		'street_number' => $values['pos_street_number'],
    		'address' => $values['pos_address'],
    		'email' => $values['email'],
    		'web' => $values['web'],
    		'video' => $values['video']
    	));
    	
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
    	foreach($values as $k => $v) {
    		if (!isset($this->place->$k)) {
    			error_log("Place does not have field ${k}");
    			continue;
    		}
    		
    		$this->place->$k = $v;
    	}
    	$this->place->save();
    	
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
