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


require_once(dirname(__FILE__) . '/../functions.lib.php');

//! HTML Document constructor
/**
 This is an HTML Document constructor, it will create a valid HTML doc
 based on user suplied data.

 @par Example
 @code
 $mypage = new HTMLDoc();
 $mypage->title = 'My Super Duper WebSite';
 $mypage->add_ref_js('/js/jquery.js');
 $mypage->add_ref_css('/themes/fantastic.css');

 // Add data to body
 $mypage->get_body()->append_text('Hello World');

 // Render and display page
 echo $mypage->render();
 @endcode

 @par Capture from Output Buffer
 It is very difficult and ugly to write a webpage and use append_data(). In that case
 it is better to capture output buffer and append directly to body.
 \n
 An easy way to do it is:
 @code
 $mypage = new HTMLDoc();

 // Auto append data to html content
 ob_start(array($mypage->get_body(), 'append_text'));

 // Everything echoed here will be appended to html
 echo 'Hello world';

 // Stop capturing and echo final page
 ob_end_clean();
 echo $mypage->render();
 @endcode

 @par Auto render page "trick"
 There is a trick to autorender html page at the end of each page. The example
 assumes that you have a file named layout.php where you create the basic layout
 of the site and it is included from any page.
 \n
 @b layout.php
 @code
 $mypage = new HTMLDoc();
 $mypage->title = 'My Super Duper WebSite';
 $mypage->add_ref_js('/js/jquery.js');
 $mypage->add_ref_css('/themes/fantastic.css');

 // Auto append data to html content
 ob_start(array($mypage->get_body(), 'append_text'));

 // Create a guard object that on destruction it will render the mypage
 class auto_render_html
 {   
 	public function __destruct()
 	{   global $mypage;
 		ob_end_clean();
 		echo $mypage->render();
 	}
 }
 $auto_render = new auto_render_html();
 @endcode
 \n
 @b index.php
 @code
 require_once('layout.php');

 // Everything written here will be appended to body
 echo 'Hello World';

 // At the end of the script all the objects will be destroyed, $auto_render too which
 // will render the HTML page "magically"
 @endcode
 */
class Output_HTMLDoc
{
    //! Contents of body
    private $body;
    
    //! Contents of head
    private $head;

    //! Character set of body content
    public $char_set = 'utf-8';

    //! Title of html page
    public $title = '';
    
    //! Namespaces of this document
    public $ns = array();

    /**
     * Construct an empty Document
     */
    public function __construct()
    {
        $this->body = new Output_HTMLTag('body');
        $this->head = new Output_HTMLTag('head');
        $this->ns[''] = 'http://www.w3.org/1999/xhtml';
    }

    //! Get body Output_HTMLTag
    public function get_body()
    {
        return $this->body;
    }
    
    //! Get head Output_HTMLTag
    public function get_head()
    {
        return $this->head;
    }
    
    //! Add a external reference entry
    /** 
     * @param $href The position of this external reference
     * @param $type Specifies the MIME type of the linked document
     * @param $rel Specifies the relationship between the current document and the linked document
     * @param $extra_html_attribs An array with extra attributes that you want to set at this link element.\n
     *		Attributes are given as an associative array where key is the attribute name and value is the 
     *		attribute value.\n
     */
    public function add_link_ref($href, $type, $rel, $extra_html_attribs = array())
    {	
        $link_el = $extra_html_attribs;
    	$link_el['href'] = $href;
    	$link_el['type'] = $type;
    	$link_el['rel'] = $rel;
    	$this->head->append(tag('link', $link_el));
    }	
    
    //! Add a new meta data entry
    /** 
     *	@param $content The value of meta element's content attribute
	 * 	@param $extra_html_attribs An array with extra attributes that you want to set at this meta element.\n
     *		Attributes are given as an associative array where key is the attribute name and value is the 
     *  	attribute value.\n
     *	Example:\n
     *	@code
     *		$myhtml->add_meta('text/html;charset=ISO-8859-1', array('http-equiv' => 'Content-Type'));
     *	@endcode
     */
    public function add_meta($content, $extra_html_attribs = array())
    {
    	$meta_el = $extra_html_attribs;
    	$meta_el['content'] = $content;
        $this->head->append(tag('meta', $meta_el));
    }
    
    //! Add a favicon of this webpage
    public function add_favicon($icon, $type = NULL)
    {	if ($type === NULL)
    	{	$ext = pathinfo($icon, PATHINFO_EXTENSION);
    	
    		if ($ext == 'gif')
    			$type = 'image/gif';
    		else if ($ext == 'png')
    			$type = 'image/png';
    		else if ($ext == 'ico')
    			$type = 'image/vnd.microsoft.icon';
    		else
    			return false;
    	}
    	
    	return $this->add_link_ref($icon, $type, 'icon');
    }
    
	//! Add a javascript reference
	public function add_ref_js($script)
	{
        $this->head->append(tag('script type="text/javascript"',  array('src' => $script)));
	}
	
	//! Add a style sheet reference
	public function add_ref_css($script)
	{
	    return $this->add_link_ref($script, "text/css", "stylesheet");
	}

    //! Append data in the body content
    public function append_data($str)
    {
        $this->body_data .= $str;
    }

    //! Render html code and return a string with the whole page
    public function render()
    {   $is_xhtml = (Output_HTMLTag::$default_render_mode == 'xhtml');
    	
    	// DocType
    	if ($is_xhtml) {
	        $r = '<!DOCTYPE html>' .
        		'<html xml:lang="en" ';
	        foreach($this->ns as $ns => $url) {
	        	if (empty($ns))
	        		$r .= 'xmlns="' . $url . '" ';
	        	else
	        		$r .= 'xmlns:' . $ns . '="' . $url . '" ';
	        }
        	$r .= 'lang="en" >';
    	} else {
	        $r = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"><html>';
    	}
        // HEAD
        $this->head->append(tag('title', $this->title));
        
	        // Character set
	    if ($is_xhtml)
	    	$this->head->append(tag('meta', array('http-equiv' => 'Content-type',
	    	    'content' => 'application/xhtml+xml;charset=' . $this->char_set)));
	    else
	    	$this->head->append(tag('meta', array('http-equiv' => 'Content-type',
	    	    'content' => 'text/html;charset=' . $this->char_set)));

	    $r .= (string) $this->head;
  
        // Title
        $r .= (string)$this->body;
        $r .= '</html>';
        return $r;
    }
};

?>
