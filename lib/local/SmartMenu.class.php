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


require_once dirname(__FILE__) . '/../tools.lib.php';
require_once dirname(__FILE__) . '/SmartMenuEntry.class.php';

//! Demo class of a smart menu
class SmartMenu
{
    //! Internal first level menu entry
    private $main_menu = array();

    //! Attributes to add on menu
    private $menu_attribs = array();

    //! Construct the smart menu
    /**
     * @param $attribs Custom attributes to add on menu UL element.
     */
    public function __construct($attribs = array())
    {
        $this->main_menu = new SmartMenuEntry();
        $this->menu_attribs = $attribs;
    }

    //! Create a new text entry in main menu
    /**
     * @param $display The text to be displayed.
     * @param $id A unique id for this menu entry that can be used to reference it.
     */
    public function create_entry($display, $id = null)
    {   
        return $this->main_menu->create_entry($display, $id);
    }

    //! Create a new link entry in main menu
    /**
     * @param $display The text to be displayed on link.
     * @param $link The actual link of entry.
     * @param $id A unique id for this menu entry that can be used to reference it.
     */    
    public function create_link($display, $link, $id = null)
    {   
        return $this->main_menu->create_link($display, $link, $id);
    }
    
    //! Render menu and return html tree.
    public function render()
    {	
        $ul = $this->main_menu->render_childs();
        $ul->attributes = array_merge($ul->attributes, $this->menu_attribs);
        return $ul;
    }
}
