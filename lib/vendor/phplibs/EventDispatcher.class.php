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


//! The event object transmitted by EventDispatcher
class Event
{
    //! The name of the event
    public $name;

    //! User arguments passed to event
    public $arguments = array();

    //! Value to be filtered by the event
    public $filtered_value = NULL;

    //! Type of notification
    public $type;

    //! Flag if event has been processed
    public $processed = false;

    //! Construct event object
    public function __construct($name, $type, $arguments = array())
    {   $this->name = $name;
    $this->arguments = $arguments;
    $this->type = $type;
    }
}

//! Dispatch events to their listeners
/**
 EventDispatcher holds an array with all events. Events can
 be declared at the dispatcher using declare_event() function. The
 concept is that an object raises_events and the registered listeners
 get informed using the callback function that they previously defined.

 @b Example \n
 To understand the concept of EventDispatcher we will demonstrate it with Cat,
 Bob and Alice. Let's say Bob wants to listen TheirCat if it is hungry to feed it,
 and Alice wants to listen TheirCat to see if it is bored so that she entertains it.

 First we define the Cat class and PetHolder class and we create our actors.
 @code
 class Cat
 {
 	public $events;

     public function __construct()
     {
     	$this->events = new EventDispatcher(array('hungry', 'bored'));
     }

     public function random_mood()
     {
     	if (my_random())
     		$this->events->notify('hungry', $this);
     	else
     		$this->events->notify('bored', $this);
     }
 }

 class PetHolder
 {
 	public function feed_pet($pet)
 	{}

 	public function entertain_pet($pet)
 	{}
 }

 $TheirCat = new Cat();
 $Bob = new PetHolder();
 $Alice = new PetHolder();
 @endcode

 Now that we have all our actors we need to declare who wants to be informed for.
 @code
 $TheirCat->events->connect('hungry', array($Bob, 'feed_pet'));
 $TheirCat->events->connect('bored', array($Alice, 'entertain_pet'));
 @endcode

 When ever $TheirCat->random_mood() the appropriate callback of Bob or Alice will be called
 to handle the event.
 */
class EventDispatcher
{
    //! An array with all events and their listeners.
    private $event_listeners = array();

    //! An array with global listeners
    private $global_listeners = array();
    
    //! Create an EventDispatcher object and declare the events.
    /**
     * @param $event_names An array with all events that will be declared
     */
    public function __construct($event_names = array())
    {   
        foreach($event_names as $e)
            self::declare_event($e);
    }
    
    //! Declare an event on this dispatcher
    /**
     * @param $event_name The name of the event to declare
     * @return @b true if it was declared otherwise @b false
     */
    public function declare_event($event_name)
    {   
        // Must be a valid value
        if (empty($event_name))
            return false;
            
        // Must not exist
        if ($this->has_event($event_name))
             return false;

        // Create listeners pool for this event
        $this->event_listeners[$event_name] = array();
        return true;
    }
    
    //! Check if an event is already declared
    /**
     * @param $event_name The name of the event
     * @return @b true if exists otherwise @b false
     */
    public function has_event($event_name)
    {   return array_key_exists($event_name, $this->event_listeners);    }

    //! Get all events
    /**
     * @return An array with all events declared at this dispatcher.
     */
    public function get_events()
    {   return array_keys($this->event_listeners);   }

    //! Check if event has a specific listener
    /**
     * @param $event_name The name of the event or @b NULL for global listeners.
     * @param $callable The callable of the listener.
     * @return @b true if it has listener otherwise @b false
     */
    public function has_listener($event_name, $callable)
    {   
        // Check global listeners
        if ($event_name === NULL)
            return (array_search($callable, $this->global_listeners, true) !== false);

        // Must exist
        if (! $this->has_event($event_name))
             return false;

        return (array_search($callable, $this->event_listeners[$event_name], true) !== false);
    }
    
    //! Get all listeners of an event
    /**
     * @param $event_name The name of the event or @b NULL for global listeners.
     * @return @b Array with callbacks or @b NULL if event is unknown.
     */
    public function get_listeners($event_name)
    {   
        // Check for global listeners
        if ($event_name === NULL)
            return $this->global_listeners;

        // Event must not exist
        if (! $this->has_event($event_name))
             return NULL;
        return $this->event_listeners[$event_name];
    }
    
    //! Connect on event
    /** 
     * @param $event_name The name of the event or @b NULL for @e any event.
     * @param $callable The callable object to be called when the event is raised.
     * @return @b true if it was connected succesfully or @b false on any error.
     */
    public function connect($event_name, $callable)
    {   
        // Check if it wants to connect to global listeners
        if ($event_name === NULL)
        {   if (array_search($callable, $this->global_listeners, true) === false)
            {   
                $this->global_listeners[] = $callable;
                return true;
            }
            return false;
        }
        
        // Check that the event exists
        if (! $this->has_event($event_name))
            return false;
            
        // Check if this callable has been registered again
        if (array_search($callable, $this->event_listeners[$event_name], true) !== false)
            return false;

        $this->event_listeners[$event_name][] = $callable;
        return true;
    }

    //! Disconnect from event
    /** 
     * @param $event_name The name of the event or @b NULL for @e any event.
     * @param $callable The callable object that was passed on connection.
     * @return @b true if it was disconnected succesfully or @b false on any error.
     */
    public function disconnect($event_name, $callable)
    {   
        // Check if it wants to disconnect from global listeners
        if ($event_name === NULL)
        {   $cb_key = array_search($callable, $this->global_listeners, true);

            if ($cb_key !== false)
            {   
                unset($this->global_listeners[$cb_key]);
                $this->global_listeners = array_values($this->global_listeners);
                return true;
            }
            return false;
        } 

        // Check if it is a known event
        if (! $this->has_event($event_name))
            return false;
            
        // Check if this listener exists
        if (($cb_key = array_search($callable, $this->event_listeners[$event_name], true)) === false)
            return false;
        
        // Remove listener
        unset($this->event_listeners[$event_name][$cb_key]);
        $this->event_listeners[$event_name] = array_values($this->event_listeners[$event_name]);
        return true;
    }
        
    //! Notify all listeners for this event
    /** 
     * @param $event_name The name of the event that notification belongs to.
     * @param $arguments Array with user defined arguments for the listeners.
     * @return @b Event object with the details of the event.
     * @throws InvalidArgumentException if the $event_name is not valid
     */
    public function notify($event_name, $arguments = array())
    {   
        if (! $this->has_event($event_name))
            throw new InvalidArgumentException("Cannot notify unknown ${event_name}");

        // Create event object
        $e = new Event($event_name, 'notify', $arguments);
        
        // Call event listeners
        foreach($this->event_listeners[$event_name] as $callback)
        {
            call_user_func($callback, $e);
            $e->processed = true;   // Mark it as processed
        }
        
        // Call global listeners
        foreach($this->global_listeners as $callback)
        {
            call_user_func($callback, $e);
            $e->processed = true;   // Mark it as processed
        }

        return $e;
    }

    //! Notify all listeners for this event until one returns non null value
    /** 
     * @param $event_name The name of the event that notification belongs to.
     * @param $arguments Array with user defined arguments for the listeners.
     * @return @b Event object with the details of the event.
     * @throws InvalidArgumentException if the $event_name is not valid
     */
    public function notify_until($event_name, $arguments = array())
    {   
        if (! $this->has_event($event_name))
            throw new InvalidArgumentException("Cannot notify_until unknown ${event_name}");

        // Create event object
        $e = new Event($event_name, 'notify_until', $arguments);
        
        // Call event listeners
        foreach($this->event_listeners[$event_name] as $callback)
        	if (call_user_func($callback, $e) !== NULL)
            {	
                $e->processed = true;   // Mark it as processed
				return $e;
			}
        
        // Call global listeners
        foreach($this->global_listeners as $callback)
			if (call_user_func($callback, $e) !== NULL)
            {	
                $e->processed = true;   // Mark it as processed
				return $e;
			}

        return $e;
    }

    //! Filter value through listeners
    /** 
     * @param $event_name The name of the event that notification belongs to.
     * @param $value The value that must be filtered by listeners.
     * @param $arguments Array with user defined arguments for the listeners.
     * @return @b Event object with the details of the event.
     * @throws InvalidArgumentException if the $event_name is not valid
     */
    public function filter($event_name, & $value, $arguments = array())
    {   
        if (! $this->has_event($event_name))
            throw new InvalidArgumentException("Cannot filter unknown ${event_name}");

        // Create event object
        $e = new Event($event_name, 'filter', $arguments);
		$e->filtered_value = & $value;
        
        // Call event listeners
        foreach($this->event_listeners[$event_name] as $callback)
        {   
            call_user_func($callback, $e);
            $e->processed = true;   // Mark it as processed
        }
        
        // Call global listeners
        foreach($this->global_listeners as $callback)
        {   
            call_user_func($callback, $e);
            $e->processed = true;   // Mark it as processed
        }

        return $e;
    }
}

?>
