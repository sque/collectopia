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

//! An menu entry of the SmartMenu
class SmartMenuEntry
{
    //! Acceptable types
    static private $types = array('link', 'text', 'custom');

    //! Acceptablte modes
    static private $modes = array('prefix', 'equal', false);

    //! Display text of this entry
    private $display = '';

    //! Type of this entry
    private $type = 'text';

    //! Link of this entry
    private $link = '';

    //! Autoselect mode for links
    private $autoselect_mode = 'prefix';

    //! Childs of this entry
    private $childs = array();

    //! Extra custom html attributes entry's LI element.
    public $extra_attr = array();

    //! Render this entry and all its childs;
    public function render()
    {   $REQUEST_URL = (isset($_SERVER['PATH_INFO'])?$_SERVER['PATH_INFO']:$_SERVER['REQUEST_URI']);

        if ($this->type === 'link')
        {
            $li = tag('li', tag('a', array('href' => url($this->link)), $this->display), $this->extra_attr);
            if ($this->autoselect_mode !== FALSE)
            {
                if ($this->autoselect_mode === 'prefix')
                {
                    if( $this->link === substr($REQUEST_URL, 0, strlen($this->link)))
                        $li->add_class('selected');
                }
                else if ($this->autoselect_mode === 'equal')
                    if( $this->link === $REQUEST_URL)
                        $li->add_class('selected');
            }
        }
        else if ($this->type === 'custom')
            $li = tag('li html_escape_off', $this->display, $this->extra_attr);
        else if ($this->type == 'text')
            $li = tag('li', $this->display, $this->extra_attr);

        // Add childs if any
        if (!empty($this->childs))
            $li->append($this->render_childs());
            
        return $li;
    }

    //! Render only the childs of this entry.
    public function render_childs()
    {
        $ul = tag('ul');
        
        foreach($this->childs as $entry)
            $entry->render($entry)->appendTo($ul);

        return $ul;
    }

    //! Create a new sub text entry
    /**
     * @param $display The text to be displayed.
     * @param $id A unique id for this menu entry that can be used to reference it.
     */
    public function create_entry($display, $id = null)
    {   
        $entry = new SmartMenuEntry();
        $entry->set_display($display);
        if ($id !== null)
            $this->childs[$id] = $entry;
        else
            $this->childs[] = $entry;
            
        return $entry;
    }

    //! Create a new sub entry
    /**
     * @param $display The text to be displayed on link.
     * @param $link The actual link of entry.
     * @param $id A unique id for this menu entry that can be used to reference it.
     */    
    public function create_link($display, $link, $id = null)
    {   
        $entry = $this->create_entry($display, $id);
        $entry->set_type('link');
        $entry->set_link($link);
        return $entry;
    }

    //! Get a child based on its id
    public function get_child($id)
    {
        if (isset($this->childs[$id]))
            return $this->childs[$id];
        return NULL;
    }

    //! Set the display of this entry
    public function & set_display($display)
    {
        $this->display = $display;
        return $this;
    }

    //! Set the type of this entry   
    public function & set_type($type)
    {
        if (in_array($type, self::$types, true))
            $this->type = $type;
        return $this;
    }

    //! Set the link of this entry
    public function & set_link($link)
    {
        $this->link = $link;
        return $this;
    }

    //! Set the autoselect mode of this entry
    public function & set_autoselect_mode($mode)
    {
        if (in_array($mode, self::$modes, true))
            $this->autoselect_mode = $mode;
        return $this;
    }

    //! Get the display of this entry
    public function get_display()
    {
        return $this->display;
    }

    //! Get the type of this entry
    public function get_type()
    {
        return $this->type;
    }

    //! Get the link of this entry
    public function get_link()
    {
        return $this->link;
    }

    //! Get the autoselect mode of this entry
    public function get_autoselect_mode()
    {
        return $this->autoselect_mode;
    }
}

?>
