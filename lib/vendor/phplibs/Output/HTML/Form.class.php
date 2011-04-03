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


require_once dirname(__FILE__) . '/../html.lib.php';

//! An abstract web form constructor
/**
 * @todo Clean-up \@p from this documentation (@p is not the same as \@par!)
 * @todo Clean-up todos lol
 *   Form provides a fast way to create input forms with server-side validation. It
 *   supports multiple types of input and an abstract way to create your own custom
 *   types. At the same time it provides form validation from mandatory fields to
 *   regular expressions checks for text boxes. If form is properly validated,
 *   developper can add custom code for finally processing data using special functions
 *   in the derived class.
 *   
 *   @par Special Functions
 *   Special functions are function that can be declared in the derived class, and get
 *   executed in special cases. There is no explicit dependency on these functions and
 *   Form will work too without declaring any of them, however you should probably define
 *   at least one to add some "real" functionality on the Form.
 *   \n\n
 *   - @b on_post():\n
 *       Called when form received data from the user. It does not guarantee that the form
 *       is properly validated.
 *   - @b on_valid($values):\n
 *       It is called when form received data from user and all the fields are valid. This
 *       function is called after on_post(). Form passes as an argument the values of all
 *       fields in the form of associative array.
 *   - @b on_nopost():\n
 *       Called when the form was requested using GET and no data where posted from the user.
 *       (When user see the form for the first time)
 *   - @b on_postrender():\n
 *       Called after the generation of the form. The first argument of the function is the
 *       DOM tree of the generated html code.
 *   .
 *
 *   @par Example
 *   To create a form object you must create a derived class that will initialize Form
 *   and populate any special function that it needs.
 *   @code
 *   class NewUserForm extends Output_HTML_Form
 *   {
 *       public function __construct()
 *       {    Output_HTML_Form::__construct(
 *               array(
 *                   'username' => array('display' => 'Username'),
 *                   'password1' => array('display' => 'Password', 'type' => 'password'),
 *                   'password2' => array('display' => 'Retype password', 'type' =>'password')
 *               ),
 *               array('title' => 'New user', 'buttons' => array('create' => array()))
 *            );
 *       }
 *       
 *       public function on_valid()
 *       {
 *           // Add your code here
 *       }
 *   }
 *   
 *   // Display form
 *   $nufrm = new NewUserForm();
 *   @endcode
 *   
 *   @par Flow Chart
 *   Form using the same object, it displays the form, accepts user input, validates
 *   data and executes user defined code for form events. I will try to visualize
 *   the order of events and data processing.\n\n    
 *   @b Life-Cycle: The form's life-cycle limits in the constructor and only there.
 *   @code
 *   
 *   $nufrm = new NewUserForm();    // < Here, any input data is processed, is validated,
 *                                  ///  user events are executed and finally the form is rendered.
 *   @endcode
 *   \n
 *   A detailed flow chart is followed, displaying what happens inside the constructor of Form.
 *   @verbatim
 *  ( Output_HTML_Form Constructor Start )
 *           |
 *           V
 *          / \
 *       /       \         +------------------+
 *     / User Post \ ----->| Call on_nopost() |
 *     \   Data    /  NO   +------------------+
 *       \       /                  V
 *          \ /                     |
 *           |                      |
 *           V                      |
 * +---------------------+          |
 * |  Process User Data  |          |
 * | (validate regexp,   |          |
 * |  validate mandatory |          |
 * |  data, save values) |          |
 * +---------------------+          |
 *           |                      |
 *           V                      |
 * +---------------------+          |
 * |    Call on_post()   |          |
 * | (Here user can do   |          |
 * |  extra validations  |          |
 * |  and invalidate any |          |
 * |  fields)            |          |
 * +---------------------+          |
 *           |                      |
 *           V                      |
 *          / \                     |
 *       /       \                  V
 *     /  Is Form  \ -------------->+
 *     \   VALID?  /  NO            |
 *       \       /                  |
 *          \ /                     |
 *           |                      |
 *           V                      |
 * +---------------------+          |
 * |   Call on_valid()   |          |
 * +---------------------+          |
 *           |                      V
 *           +<---------------------+
 *           |                      
 *           V                      
 *          / \                     
 *       /       \                  
 *     /  Is Form  \ -------->+
 *     \  Visible? /  NO      |
 *       \       /            |
 *          \ /               |
 *           |                |
 *           V                |
 *   +-------------------+    |
 *   |  Render Form      |    |
 *   +-------------------+    |
 *           |                V
 *           +<---------------+
 *           V
 * ( Output_HTML_Form Constructor End )
 *   @endverbatim
 *
 */
class Output_HTML_Form
{
    //! The id of the form
    private $form_id;

    //! Encoding type of form
    private $enctype;

    //! An array with all fields
    protected $fields;

    //! The options of the form
    protected $options;

    //! Internal increment for creating unique form ids.
    private static $last_autoid = 0;

    //! Construct the form object
    /**
    @param $fields An associative array with all fields of the form, fields must be given in the same
    order that will be rendered too. The key of each of record defines the unique id of the field
    and the value is another associative array with the parameters of the field.\n
    <b> The supported field parameters are: </b>
        - display: The text that will be displayed at the left of the input
        - type: [Default=text] The type of input control. Currently implemented are
            ('text', 'textarea', 'password', 'dropbox', 'radio', 'checkbox', 'line', 'file', 'hidden', 'custom')
        - optionlist: [Default=array()]
            An array with all the value options that will be displayed at this control.
            This is only needed for types that have mandatory options like (dropbox, radio).
            The array is given in format array(key1 => text1, key2 => text2)
            - key: The key name of this option. The result of the field is the @b key value of the selected option.
            - text: [Default: key] The text to be displayed for this option.
            .
        - htmlattribs: [Default=array()]
            An array with extra attributes that you want to add at the input html element. For example you may
            want to define a custom maxlength of an input box this can be done by defining array('maxlength' => '20')
            in htmlattribs.\n htmlattribs is an associative array that the key is the html attribute name and
            value is the html attribute value.
        - value: [Optional] A predefined value for the input that will be displayed, or the key of the selection.
        - mustselect: [Default: true] If the type of input has options, it force you to set an option
        - usepost: [Default=true, Exception type=password] If true it will assign value the posted one from user.
        - hint: [Optional] A hint message for this field.
        - regcheck: [Optional] A regular expression that field must pass to be valid.
        - onerror: [Optional] The error that will be displayed if field is not valid (either by regchek or by manually
        using invalidate_field() function ).
        .\n\n
    A small example for $fields is the following
    @code
    new Form(
        array(
            'name' => array('display' => 'Name', type='text'),
            'sex' => array('display' => 'Sex', type='radio', 'optionlist' = array('m' => 'Male', 'f' => 'Female'))
        )
    );
    @endcode

    @param $options An associative array with the options of the form.\n
    Valid array keys are:
        - title The title of the form.
        - buttons [Default = array('submit' => array())\n
            An associative array of all form buttons. Each item of array has a unique key and an array with parameters
            of the button. Valid parameters are:
            - display [Default same as button id]: The text on the buttom.
            - type [Default=submit] Three types are valid "submit", "reset" and "button". Submit and reset are
                self-explained types. Button is a general type that does nothing, but you can enchanch it
                with "onclick" parameter of buttons
            - onclick [Default=""] Custom user defined javascript that will be executed when user clicks
                on this button.
            - htmlattribs: [Default=array()]
                An array with extra attributes that you want to add at the input html element.\n
                htmlattribs is given as an associative array where the key is the html attribute name and
                value is the html attribute value.
            .
        - css [Default = array()] An array with extra classes
        - renderonconstruct [Default = false] The form is render immediatly at the constructor of the Form. If
            you set it false you can render the form using render() function of the created object at any
            place in your page.
        .\n\n
    @par Example:
    @code
    Output_HTML_Form::__construct(
        array(... fields ...),
        array('title' => 'My Duper Form', 'buttons' => array('ok' => array('display' => 'Ok'))
    );
    @endcode \n\n
    @par Another example with @b renderonconstruct set to @b false:
    @code

    class MyForm extends Form
    {
        public __construct()
        {   
            Output_HTML_Form::__construct(
            array(... fields ...),
            array('title' => 'My Duper Form', 'renderonconstruct' = false, 'buttons' => array('ok' => array('display' => 'Ok'))
        }

        public function on_valid()
        {
            // Add your code here
        }
    }

    // Create process and process input
    $nufrm = new MyForm();

    echo 'this will be displayed before the form';

    // Now render the form here
    $nufrm->render();
    @endcode

    @remarks Form now supports declaring fields and options using class properties for example
    @code
    class MyForm extends Form
    {
        protected $fields = array(...fields...);
        protected $options = array(...options);

        public function on_valid()
        {	// Add your code here	}
    };
    new MyForm();
    @endcode
    */
    public function __construct($fields = NULL, $options = NULL)
    {
        if ($fields !== NULL)
            $this->fields = $fields;
        if ($options !== NULL)
            $this->options = $options;
        $this->form_id = 'form_gen_' . (self::$last_autoid ++);
        $this->enctype = 'application/x-www-form-urlencoded';

        // Initialize default values for options
        $default_options = array(
        	'display' => '',
        	'css' => array('ui-form'),
        	'hideform' => false,
        	'renderonconstruct' => false,
        	'buttons' => array('submit' => array())
        );
        $this->options = array_merge($default_options, $this->options);
            
        // Initialize default values for fields
        $default_field_values = array(
        	'type' => 'text',
        	'optionlist' => array(),
        	'htmlattribs' => array(),
        	'mustselect' => true,
        );
        foreach($this->fields as $field_key => $field)
        	$this->fields[$field_key] = array_merge($default_field_values, $field);
        
        // Extra custom options
        foreach($this->fields as & $field)
        {   
            // Usepost
            if (!isset($field['usepost']))
                $field['usepost'] = ($field['type'] == 'password')?false:true;
            // Rows and Cols for textarea
            if (($field['type'] == 'textarea') && (!isset($field['htmlattribs']['rows'])))
            	$field['htmlattribs']['rows'] = 8;
            if (($field['type'] == 'textarea') && (!isset($field['htmlattribs']['cols'])))
            	$field['htmlattribs']['cols'] = 70;
            	
            // Check for file field
            if ($field['type'] == 'file')
            	$this->enctype = 'multipart/form-data';
        }
        unset($field);
        
        // Initialize default values for buttons
        foreach($this->options['buttons'] as $but_id => & $button)
        {
        	// Type
        	if (!isset($button['type']))
        		$button['type'] = 'submit';

			// Display
        	if (!isset($button['display']))
        		$button['display'] = $but_id;

			// Onclick event
        	if (!isset($button['onclick']))
        		$button['onclick'] = '';
        	
        	// Onclick event
        	if (!isset($button['htmlattribs']))
        		$button['htmlattribs'] = array();
        	
        }
        unset($button);
        
        // Process post
        $this->process_post();
        
        // Render the form
        if ($this->options['renderonconstruct'])
	        echo $this->render();
    }
    
    //! Process the posted data
    private function process_post()
    {   
        // Check if the form is posted
        if ((!isset($_POST['submited_form_id'])) ||
            ($_POST['submited_form_id'] != $this->form_id))
        {
            // Call user function when there is no post
            if (method_exists($this, 'on_nopost'))
                $this->on_nopost();
            return false;
        }

        // Store values and check if they are valid
        foreach($this->fields as $k => & $field)
        {   
			// Files
			if ($field['type'] == 'file')
			{
			    if ($_FILES[$k]['error'] == UPLOAD_ERR_NO_FILE)
                {
                    $field['value'] = null;
                }
				else if ($_FILES[$k]['error'] > 0)
				{
					$field['valid'] = false;
                    if (isset($field['onerror']))
                        $field['error'] = $field['onerror'];
					continue;
				}
			    else
			    {
				    if (isset($field['regcheck']))
		            {
		            	$field['valid'] = true;
		                if (preg_match($field['regcheck'], $_FILES[$k]['name']) == 0)
		                {   
		                	$field['valid'] = false;
		                    if (isset($field['onerror']))
		                        $field['error'] = $field['onerror'];
		                }
		            }
			    	
				    // Get file data
				    $fdata = file_get_contents($_FILES[$k]['tmp_name']);
				
				    $field['value'] = array(
					    'orig_name' => $_FILES[$k]['name'],
					    'size' => $_FILES[$k]['size'],
					    'data' => $fdata
				    );
				    
				    continue;
				}
			}
			else if ($field['type'] == 'checkbox')
			{
			    // Checkboxes
			    $field['value'] = (isset($_POST[$k]) && ($_POST[$k] == 'on'));
            }
			else if ($field['type'] == 'checklist')
			{
			    // Normalize values of checklist
			    $field['value'] = array();
			    if (isset($_POST[$k]))
			    {
			        foreach($_POST[$k] as $key => $on)
			        {
			            if ( isset($field['optionlist'][$key]) && ($on == 'on'))
			                $field['value'][$key] = true;
			        }
			    }
			}			
			else if (isset($_POST[$k]))
			{
			    // Store values for classic elements
                $field['value'] = $_POST[$k];
			}
			
            // Regcheck
            $field['valid'] = true;
            if (isset($field['regcheck']))
            {
                if (preg_match($field['regcheck'], $field['value']) == 0)
                {   $field['valid'] = false;
                    if (isset($field['onerror']))
                        $field['error'] = $field['onerror'];
                }
            }
            
            // Mustselect check
            if (($field['valid']) &&
                (($field['type'] == 'dropbox') || ($field['type'] == 'radio'))
                && ($field['mustselect']))
            {
                if ((!isset($field['value'])) || ($field['value'] == ''))
                {   
                    $field['valid'] = false;
                    if (isset($field['onerror']))
                        $field['error'] = $field['onerror'];
                }
            }
        }
        unset($field);

        // Call user function for post processing
        if (method_exists($this, 'on_post'))
            $this->on_post();
            
        // Call on_valid if form is valid
        if ($this->is_valid() && method_exists($this, 'on_valid'))
            $this->on_valid($this->field_values());
    }

    //! Get the user given value of a field
    /** 
        If a this is the first time viewing the firm, the
        function will return the predefined value of this field. (if any)
    */
    public function get_field_value($fname)
    {
        if (isset($this->fields[$fname]) && (isset($this->fields[$fname]['value'])) )
            return $this->fields[$fname]['value'];
    }
    
    //! Get a reference to the field
    public function & get_field($fname)
    {	
        return $this->fields[$fname];
    }
    
    //! Get all the values of fields
    /**
    	@return An associative array with all values of form fields.
    */
    public function field_values()
    {	$values = array();
		foreach($this->fields as $fname => $field)
			$values[$fname] = (isset($field['value'])?$field['value']:null);
		return $values;
    }
    
    //! Check if a field is valid
    public function is_field_valid($fname)
    {
        if (isset($this->fields[$fname]) &&
            isset($this->fields[$fname]['valid']))
                return $this->fields[$fname]['valid'];

        return false;
    }
    
    //! Invalidate a field and set an error message
    public function invalidate_field($fname, $error_msg)
    {
        if (isset($this->fields[$fname]))
        {
            $this->fields[$fname]['valid'] = false;
            $this->fields[$fname]['error'] = $error_msg;
        }
    }
    
    //! Check if form is valid
    /** 
        It will check if all fields are valid, and if they are,
        it will return true.
    */
    public function is_valid()
    {   foreach($this->fields as $k => $field)
            if(!$this->is_field_valid($k))
                return false;
        return true;
    }
    
    //! Set the error message of a field
    /** 
        This does not invalidates fields, it just changes
        the error message.
    */
    protected function set_field_error($fname, $error)
    {
        if(!isset($this->fields[$fname]))
            return false;
        $this->fields[$fname]['error'] = $error;
    }
    
    //! Get a refernece to the internal field object
    /** 
       The reference returned will be an array
       with the parameters of the fields, for 
       the parameterse of the field you can see
       __construct().
   */
    public function field($fname)
    {   
        if(!isset($this->fields[$fname]))
            return false;
        return $this->fields[$fname];
    }
    
    //! Change the display text of a field
    /** 
        Display text is the text on the left of the field
        that describes it.
    */
    public function set_field_display($fname, $display)
    {   
        if(!isset($this->fields[$fname]))
            return false;
        $this->fields[$fname]['display'] = $display;
    }
    
    //! Render the form
    public function render()
    {   
        // Check if it should be hidden
    	if ($this->options['hideform'])
    		return false;
   
    	$div = tag('div');
    	foreach($this->options['css'] as $cls)
    		$div->add_class($cls); 
    	
    	$form = tag('form action="" method="post"', array('enctype' => $this->enctype))->appendTo($div)->push_parent();
        etag('input type="hidden" name="submited_form_id"' , array('value' => $this->form_id));

        if (isset($this->options['title']))
        	etag('span class="title"',  $this->options['title']);
        
        
        // Render all fields
        etag('ul class="fields"')->push_parent();
        foreach($this->fields as $id => $field)
        {
            etag('li')->add_class('type-' . $field['type'])->push_parent();

            // Line type
            if ($field['type'] == 'line')
            {
                etag('hr');
            	Output_HTMLTag::pop_parent(); 	
            	continue;
            }

            if (isset($field['display']))
            	etag('label', $field['display']);
            

            // Show input pertype
            switch($field['type'])
            {
            case 'text':
            case 'hidden':
            case 'password':
                $attrs = array_merge($field['htmlattribs'], array('name' => $id, 'type' => $field['type']));
                if (($field['usepost']) && isset($field['value'])) 
                	$attrs['value'] = $field['value'];
                etag('input', $attrs);                
                break;
            case 'textarea':
            	etag('textarea', $field['htmlattribs'],
            		array('name'=>$id),
            		(($field['usepost']) && isset($field['value']))?$field['value']:''
            	);
                break;
            case 'radio':
                foreach($field['optionlist'] as $opt_key => $opt_text)
                {
                    etag('span html_escape_off',
                        tag('input type="radio"', $field['htmlattribs'],
                    		array('name'=>$id, 'value'=>$opt_key),
                		    (($field['usepost']) && isset($field['value']) && ($opt_key == $field['value']))
                		        ?array('checked'=>'checked')
                		        :array()
                	    ),
                		esc_sp(esc_html((string)$opt_text))
                	);
                }
                break;
            case 'dropbox':
            	$select = etag('select', array('name' => $id), $field['htmlattribs']);
                foreach($field['optionlist'] as $opt_key => $opt_text)
                {   
                    tag('option html_escape_off',
                		array('value'=>$opt_key),
                		(($field['usepost']) &&
                		    isset($field['value']) &&
                		    ($opt_key == $field['value']))?array('selected'=>'selected'):array(),
                		esc_sp(esc_html((string)$opt_text))
                	)->appendTo($select);
                }
                break;
            case 'checkbox':
            	etag('input type="checkbox"', array('name'=>$id),
            		$field['htmlattribs'],
            		(($field['usepost']) && isset($field['value']) && ($field['value']))?array('checked'=>'checked'):array()
            	);
                break;
            case 'checklist':
                $ul = etag('ul class="ui-form-checklist');
                foreach($field['optionlist'] as $key => $text)
                {
                    $ul->append(
                        tag('li', $check = tag('input type="checkbox"')->attr('name', $id .'[' . $key .']'), $text)
                    );
                    if (($field['usepost']) && isset($field['value'][$key]) && ($field['value'][$key]))
                        $check->attr('checked', 'checked');
                }
                break;
            case 'file':
            	etag('input type="file"', array('name' => $id), $field['htmlattribs']);
                break;            
            case 'custom':
            	etag('span html_escape_off', $field['value']);
                break;
            }
            
            if (isset($field['error']))
            	etag('span class="ui-form-error"', $field['error']);
            else if (isset($field['hint']))
            	etag('span class="ui-form-hint"', $field['hint']);
            Output_HTMLTag::pop_parent();
        }
        Output_HTMLTag::pop_parent();
        
        // Render buttons
        etag('div class="buttons"')->push_parent();
        foreach($this->options['buttons'] as $but_id => $but_parm)
        {
            $but_parm['htmlattribs']['name'] = $but_id;
        	$but_parm['htmlattribs']['value'] = $but_parm['display'];
        	        	
        	// Type
			if ($but_parm['type'] == 'submit')
				$but_parm['htmlattribs']['type'] = 'submit';
			else if ($but_parm['type'] == 'reset')
				$but_parm['htmlattribs']['type'] = 'reset';
			else
				$but_parm['htmlattribs']['type'] = 'button';
			
			// Onclick
			if ($but_parm['onclick'] != '')
				$but_parm['htmlattribs']['onclick'] = $but_parm['onclick'];

			etag('input', $but_parm['htmlattribs']);
        }
        Output_HTMLTag::pop_parent(2);
        
        // Call user function for post-render changes
        if (method_exists($this, 'on_postrender'))
            $this->on_postrender($div);

		return $div;
    }
    
    //! Don't display the form
    /**
        Makes the form hidden and will not render. You can use
        this function from any special function to prevent
        form rendering.
    */
    public function hide()
    {
        $this->options['hideform'] = true;
    }
}

?>
