<?php 

class UI_PlaceNewForm extends Output_HTML_Form
{
    public function __construct()
    {
        parent::__construct(array(
			'name' => array('display' => 'Name', 'regcheck' => '/^.{3,}$/',
        		'onerror' => 'Give a title of the place.'),
			'email' => array('display' => 'E-mail'),
        	'web' => array('display' => 'Website'),
        	'tel' => array('display' => 'Phone'),
			'description' => array('display' => 'Description', 'type' => 'textarea',
        		'regcheck' => '/^.{10,}$/', 'onerror' => 'Give a description of the place.'),        	
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
        ),
        array(
            'css' => array('ui-form','place-new'),
        	'buttons' => array()
            )
        );
    }    

    public function on_post()
    { 
		header('Content-type: application/json');
    	$this->hide();
    	
    	if(!$this->is_valid()) {
    			$error_fields = array();
    		foreach($this->fields as $name => $field)
    			if (isset($field['error']))
    				$error_fields[$name] = $field['error'];
    		echo json_encode(array('error' => 'There where errors in form!', 'fields' => $error_fields));
    	}
    }
    
    public function on_valid($values)
    {
    	
    	// Add the place
    	$place = Place::create(array(
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
    	));
    	    	
    	if (! $place ) {
    	
    		echo json_encode(array('error' => 'Cannot create this place'));
    		return;
    	}
    	
    	// Add the categories
    	foreach(explode(',', $values['categories']) as $cat)
    		PlacesCats::create(array('cat_tag' => $cat, 'place_id' => $place->id));
    	
    	echo json_encode($place->toArray());    	
    }
};
