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


require_once dirname(__FILE__) . '/OnDestruct.class.php';

//! Layouts handler and manager
class Layout
{
    //! Document of this instance
    private $document;

    //! The element that is the default container (by default body)
    private $default_container;

    //! Event dispatcher
    private $events;

    //! Create a new persistent layout with unique name
    /**
    * To create a new layout use factory function create()
    */
    final public function __construct($name)
    {
        // Check if there is already a layout with that name
        if (self::open($name) !== null)
            throw new RuntimeException("There is already a layout with name {$name}");

        // Register myself
        self::$instances[$name] = $this;
        
        $this->document = new Output_HTMLDoc();
        $this->default_container = $this->document->get_body();
        $this->events = new EventDispatcher(array(
            'pre-flush',
            'post-flush'
            ));
            
        // Call initialize method
        if (method_exists($this, '__init_layout'))
            $this->__init_layout();
    }

    //! Get the event dispatcher for this layout
    public function events()
    {
        return $this->events;
    }

    //! Get Output_HTMLDoc of this layout
    public function get_document()
    {
        return $this->document;
    }

    //! Active layout listener
    static private $active = null;

    //! Register this layout as the active one
    public function activate()
    {
        if (self::$active !== null)
            self::$active->deactivate();

        // Set output buffer
        self::$active = $this;
        ob_start(array(self::$active->default_container, 'append_text'));
        self::$active->default_container->push_parent();
        
        // Register autorender on destruct
        if (!isset($GLOBALS['auto_render']))
            $GLOBALS['auto_render'] = new OnDestruct();

        $GLOBALS['auto_render']->register_handler(array($this, 'deactivate_flush'));

        return $this;
    }

    //! Get the activated layout
    static public function activated()
    {
        return self::$active;
    }

    //! Deactivate layout and flush layout on output
    public function deactivate_flush()
    {
        if (self::$active === $this)
        {
            $this->events()->notify('pre-flush', array('layout' => $this));

            // Unregister output gatherers
            Output_HTMLTag::pop_parent();
            ob_end_clean();

            //$this->events()->notify('post-flush', array('layout' => $this));

            echo $this->document->render();

            self::$active = null;
        }
    }

    //! Unregister this layout from active one
    public function deactivate()
    {
        if (self::$active === $this)
        {
            // Unregister output gatherers
            Output_HTMLTag::pop_parent();
            ob_end_clean();

            // Unregister auto renderer
            $GLOBALS['auto_render']->unregister_handler(array(self::$active, 'flush'));
            self::$active = null;
        }
    }

    public function set_default_container($container)
    {
        if (self::$active === $this)
        {
            $this->deactivate();
             
            $this->default_container = $container;
            $this->activate();
        }
        else
            $this->default_container = $container;
    }

    //! Internal holder of instances
    static private $instances;

    //! Open an already opened instance
    static public function open($name)
    {
        if (isset(self::$instances[$name]))
            return self::$instances[$name];

        return null;
    }
}

?>
