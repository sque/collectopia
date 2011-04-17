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


class Layout_Default extends Layout
{
    private $mainmenu = null;
    
    public function get_mainmenu()
    {
        return $this->mainmenu;
    }


    private function init_menu()
    {
        $this->mainmenu = new SmartMenu(array('class' => 'menu'));
        $this->events()->connect('pre-flush', create_function('$event',
        '
            $layout = $event->arguments["layout"];
           
            $layout->get_document()->get_body()->getElementById("main-menu")
                ->append($layout->get_mainmenu()->render());
        '));

        $this->mainmenu->create_link('Home', '/')->set_autoselect_mode('equal');
        $this->mainmenu->create_link('Section 1', '/section1');
        $this->mainmenu->create_link('Section 2', '/section2');
    }
    
    protected function __init_layout()
    {   
        $this->activate();
        $doc = $this->get_document();    
        $this->get_document()->add_favicon(surl('/static/images/favicon_32.png'));
        $this->get_document()->title = Registry::get('site.title');
        $this->get_document()->add_ref_css(surl('/static/css/default.css'));
        $this->get_document()->add_ref_css(surl('/static/css/ui-lightness/jquery-ui.css'));
        $this->get_document()->add_ref_css(surl('/static/fileupload/jquery.fileupload-ui.css'));
        $this->get_document()->add_ref_css(surl('/static/fancybox/jquery.fancybox-1.3.4.css'));
        
        
        $this->get_document()->add_ref_js(surl('/static/js/jquery-1.4.4.min.js'));
        $this->get_document()->add_ref_js(surl('/static/js/jquery-ui-1.8.6.custom.min.js'));
		$this->get_document()->add_ref_js(surl('/static/fileupload/jquery.fileupload.js'));
		$this->get_document()->add_ref_js(surl('/static/fileupload/jquery.fileupload-ui.js'));
        $this->get_document()->add_ref_js(surl('/static/js/jquery-createEl.js'));
        $this->get_document()->add_ref_js('http://maps.google.com/maps/api/js?sensor=false');
        $this->get_document()->add_ref_js(surl('/static/js/collectopia/api.js'));
		$this->get_document()->add_ref_js(surl('/static/js/collectopia/ui.js'));
        $this->get_document()->add_ref_js(surl('/static/js/collectopia/map.js'));
        $this->get_document()->add_ref_js(surl('/static/js/collectopia/panels.js'));
        $this->get_document()->add_ref_js(surl('/static/js/collectopia/panel/addplace.js'));
        $this->get_document()->add_ref_js(surl('/static/js/collectopia/panel/info.js'));
        
        // Jquery cycle
        $this->get_document()->add_ref_js(surl('/static/jcycle/jquery.cycle.all.min.js'));
        
        // Jquery Fancy
        $this->get_document()->add_ref_js(surl('/static/fancybox/jquery.easing-1.3.pack.js'));
        $this->get_document()->add_ref_js(surl('/static/fancybox/jquery.mousewheel-3.0.4.pack.js'));        
        $this->get_document()->add_ref_js(surl('/static/fancybox/jquery.fancybox-1.3.4.pack.js'));
       
    	// Extra css
    	$style = '';
    	foreach($categories = Category::open_all_to_array() as $cat)
	    	$style .= "
	    	.place-editor ul.categories li.{$cat['tag']} .checkbox{	border-color: #{$cat['color']}; }
			.place-editor ul.categories li.{$cat['tag']}.enabled .checkbox{	background-color: #{$cat['color']};	}
	    	";
    	$doc->get_head()->append(tag('style', $style));
        
        etag('div id="wrapper"')->push_parent();
        etag('div id="map-canvas"');
        $def_content = etag('div id="content',
        	tag('div class="logo"', tag('img', array('src' => surl('/static/images/logo.png')))));
        
        etag('div id="panels"',
        	tag('div class="info"')
        );		
          		
        $this->set_default_container($def_content);
        
        etag('script type="text/javascript" html_escape_off', "
        $(document).ready(function() {
        	collectopia.api.init('" . surl('/') . "');
			map = new collectopia.Map($('#content'), " . json_encode($categories) . ");
		});
        ");

        // Search widget        
        $this->deactivate();
    }
}
