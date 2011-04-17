<?php


/**
 * Specialization for JSON Forms.
 */
class JsonForm extends Output_HTML_Form
{
	/**
	 * Standard constructor
	 * @param unknown_type $fields The fields of this form
	 * @param unknown_type $options Additional options
	 */
	public function __construct($fields, $options) {
		parent::__construct($fields, array_merge($options, array('buttons' => array(), 'checkformid' => false)));
	}


	/**
	 * Process all fields and extract an error report.
	 */
	private function generate_error_form()
	{
		$error_fields = array();
		foreach($this->fields as $name => $field) {
			if ($field['valid'])
				continue;

			if (isset($field['error']))
				$error_fields[$name] = $field['error'];
		}
		return array(
    			'error' => 'There where errors in form!',
    			'fields' => $error_fields);
	}
	
	/**
	 * Process this form and generate the proper json output.
	 */
	public function json_render()
	{
		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            	// No post
            	$data = array('fields' => $this->fields);            	
        } else if(!$this->is_valid()) {
			$data = $this->generate_error_form();
		} else {
			$data = $this->on_show_object();
		}
		
		header('Content-Type: application/json');
		echo json_encode($data);
	}
}