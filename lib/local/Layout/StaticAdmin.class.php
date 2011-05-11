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


class Layout_StaticAdmin extends Layout
{
    
    protected function __init_layout()
    {   
        $this->activate();
        $doc = $this->get_document();    
        
        $doc->add_favicon(surl('/static/images/favicon_32.png'));

        $doc->add_ref_css(surl('/static/css/login.css'));   
        
        etag('div id="wrapper"')->push_parent();
        $header = etag('div class="header"');
        
    	if (Authn_Realm::has_identity()) {
        		$header->prepend(tag('ul class="menu"',
        			tag('li', tag('a', 'Control Panel')->attr('href', url('/admin'))),
        			tag('li', tag('a', 'Update my profile')->attr('href', url('/admin/user/' . Authn_Realm::get_identity()->get_record()->username . '/+update')))
        		));
        	}
        
        $def_content = etag('div id="content');
		
        $this->set_default_container($def_content);
        
    
        $this->deactivate();
    }
}
