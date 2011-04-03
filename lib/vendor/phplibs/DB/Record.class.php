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


require_once(dirname(__FILE__) . '/Conn.class.php');
require_once(dirname(__FILE__) . '/ModelQuery.class.php');
require_once(dirname(__FILE__) . '/Model.class.php');
require_once(dirname(__FILE__) . '/Record/RelationshipMany.class.php');
require_once(dirname(__FILE__) . '/Record/RelationshipBridge.class.php');
require_once(dirname(__FILE__) . '/../functions.lib.php');

//! Class implementating Record concept
class DB_Record
{
	//! Array with record constructors
	static protected $model_constr = array();

	//! Array with dynamic relationships
	static protected $dynamic_relationships = array();

	//! Array with events dispatchers of DB Records
	static protected $event_dispatchers = array();
	
	//! Initialize model based on the structure of derived class
	static private function init_model($model_name)
	{
		// Create model constructor
		if (!isset(self::$model_constr[$model_name]))
			self::$model_constr[$model_name] = create_function('$sql_data, $model', 
				'$records = array();
				$model_name = $model->name();
				foreach($sql_data as $key => $rec)
					$records[] =  new $model_name($model, $rec);
				return $records;');
		
		// Open model if it exists
		if (($md = DB_Model::open($model_name)) !== NULL)
			return $md;

		$fields = get_static_var($model_name, 'fields');
		$table = (property_exists($model_name, 'table')?get_static_var($model_name, 'table'):
		    call_user_func(array($model_name,'get_table')));
		$rels = (isset_static_var($model_name, 'relationships')
			?get_static_var($model_name, 'relationships')
			:array()
		);
		if (isset(self::$dynamic_relationships[$model_name]))
		    $rels = array_merge($rels, self::$dynamic_relationships[$model_name]);
					
		// Check if fields are defined
		if (!is_array($fields))
			throw new InvalidArgumentException('DB_Record::$fields is not defined in derived class');

		// Check if table is defined
		if (!is_string($table))
			throw new InvalidArgumentException('DB_Record::$table is not defined in derived class');
		
		return DB_Model::create($model_name, $table, $fields, $rels);
	}
	
	//! Perform arbitary query on model and get raw sql results
	/**
	 * Get a raw query object for this model, whose results will
	 * be in the form of raw data structured in arrays.
	 * @return @b DB_ModelQuery instance for the model of 
	 * this class.
     */
	static public function raw_query($model_name = NULL)
	{
	    if ($model_name === NULL)
			$model_name = get_called_class();
		
		$model = self::init_model($model_name);
		
		return new DB_ModelQuery($model);
	}
	
	//! Perform a query and return model objects of this query
	/**
	 * Perfrom a @b select query on this model and get an
	 * of objects of the caller model.
	 * @return DB_ModelQuery instance for the caller model
	 *  initialized in select mode that will return caller objects.
	 */
	static public function open_query($model_name = NULL)
	{
	    if ($model_name === NULL)
			$model_name = get_called_class();
		
		$model = self::init_model($model_name);
		
		$query = new DB_ModelQuery($model, self::$model_constr[$model_name]);
		return $query->select($model->fields());
	}

	//! Get the model of this record
	/**
	 * @return DB_Model informational object.
	 */
	static public function model($model_name = NULL)
	{	
		if ($model_name === NULL)
			$model_name = get_called_class();

		return self::init_model($model_name);
	}

	//! Get the model event handler
	/**
	 * Events are announced through an EventDispatcher object per model.
	 * The following events are valid:
	 *  - @b op.pre.open: Filter before execution of open().
	 *  - @b op.post.open: Notify after execution of open().
	 *  - @b op.pre.create: Filter before execution of create().
	 *  - @b op.post.create: Notify after execution of create().
	 *  - @b op.pre.delete: Filter before execution of delete().
	 *  - @b op.post.delete: Notify after execution of delete().
	 *  - @b op.pre.save: Filter before execution of save().
	 *  - @b op.post.save: Notify after executeion of save().
	 * .
	 * @return EventDispatcher for this model
	 */
    static public function events($model_name = NULL)
    {
        if ($model_name === NULL)
            $model_name = get_called_class();

        if (!isset(self::$event_dispatchers[$model_name]))
            self::$event_dispatchers[$model_name] = new EventDispatcher(
                array(
                    'op.post.open',
                    'op.post.create',
                    'op.post.delete',
                    'op.post.save',
                    'op.pre.open',
                    'op.pre.create',
                    'op.pre.delete',
                    'op.pre.save'
                )
            );

        return self::$event_dispatchers[$model_name];
    }

    //! Notify an event listener
    static private function notify_event($model_name, $event_name, $args)
    {
        if (!isset(self::$event_dispatchers[$model_name]))
            return false;
        return self::$event_dispatchers[$model_name]->notify($event_name, $args);
    }

    //! Filter through an event listener
    static private function filter_event($model_name, $event_name, & $value, $args)
    {
        if (!isset(self::$event_dispatchers[$model_name]))
            return false;
        return self::$event_dispatchers[$model_name]->filter($event_name, $value, $args);
    }

	//! Declare 1-to-many relationship
	static public function one_to_many($many_model_name, $one_rel_name, $many_rel_name)
	{
	    $model_name = get_called_class();
	    self::$dynamic_relationships[$model_name][$many_rel_name] = 
	        array('type' => 'many', 'foreign_model' => $many_model_name);


	    self::$dynamic_relationships[$many_model_name][$one_rel_name] =
	        array('type' => 'one', 'foreign_model' => $model_name);
	}

	//! Declare 1-to-many relationship
	static public function many_to_many($foreign_model_name, $bridge_model_name, $foreign_rel_name, $local_rel_name)
	{
	    $model_name = get_called_class();
	    self::$dynamic_relationships[$model_name][$local_rel_name] = array(
	        'type' => 'bridge',
	        'foreign_model' => $foreign_model_name,
	        'bridge_model' => $bridge_model_name
	    );

	    self::$dynamic_relationships[$foreign_model_name][$foreign_rel_name] = array(
	        'type' => 'bridge',
	        'foreign_model' => $model_name,
	        'bridge_model' => $bridge_model_name
	    );
	}
	
	//! Open the record based on its primary key
	/**
	 * 
	 * It will query database table for a record with the supplied primary key. It will
	 * read the data and return an DB_Record object for this record.
	 * 
	 * @param $primary_keys It can be a string or associative array
	 * 	- @b string The value of PK column if the PK is single-column.
	 *  - @b array The values of all PK columns in associative array if the PK is multi-column.
	 *  .
	 * @param $called_class This parameter must be @b ALWAYS NULL. It would be better
	 * 	if you never used it all, as it is a reserved one for internal use to simulate
	 * 	"Late static binding" on PHP version earlier than PHP5.3
	 * @return 
	 * 	- @b NULL If the record could not be found.
	 * 	- A DB_Record derived class instance specialized for this record.
	 * 	.
	 * 
	 * @code
	 * // Example reading a news from database with id 14
	 * $n = News::open(14);
	 * @endcode
	*/
	public static function open($primary_keys, $model_name = NULL)
	{
		if ($model_name === NULL)
			$model_name = get_called_class();

		// Initialize model
		$model = self::init_model($model_name);

        // Event notification
        self::filter_event(
            $model_name,
            'op.pre.open',
            $primary_keys,
            array('model' => $model_name));
        if ($primary_keys === false)
            return false;
            
		// Check parameters
		$pk_fields = $model->pk_fields(false);

		// 1 value to array
		if (!is_array($primary_keys))
			$primary_keys = array($pk_fields[0] => $primary_keys);
				
		// Check for given quantity
		if (count($pk_fields) != count($primary_keys))
			return false;

		// Execute query and check return value
		$q = self::open_query($model_name);
		$select_args = array();
		foreach($pk_fields as $pk_name)
		{
			$q->where('? = p.' .$pk_name);
			$select_args[] = $primary_keys[$pk_name];
		}

		// Check return value
		if (count($records = call_user_func_array(array($q, 'execute'), $select_args)) !== 1)
			return false;

        // Event notification
        self::notify_event(
            $model_name,
            'op.post.open',
            array('records' => $records, 'model' => $model_name));
        
		return $records[0];
	}
	
	//! Open all records of this table
	/**
	 * It will query database table and return all the records of the table.
	 * 
	 * @param $called_class This parameter must be @b ALWAYS NULL. It would be better
	 * 	if you never used it all, as it is a reserved one for internal use to emulate
	 * 	"Late static binding" on PHP version earlier than PHP5.3
	 * @return 
	 * 	- @b false If any error occurs
	 * 	- An @b DBRecordCollection for all database records.
	 * 	.	
	 * 
	 * @code
	 * // Example reading a news from database with id 14
	 * $all_news = News::open_all();
	 * @endcode
	 */
	public static function open_all($model_name = NULL)
	{
	    if ($model_name === NULL)
			$model_name = get_called_class();

		// Initialize model
		$model = self::init_model($model_name);
		
		// Execute query and check return value
		$records = self::open_query($model_name)
			->execute();

        // Event notification
        self::notify_event(
            $model_name,
            'op.post.open',
            array('records' => $records, 'model' => $model_name));

        return $records;
	}
	
	//! Count records of model
	static public function count($model_name = NULL)
	{
	    if ($model_name === NULL)
			$model_name = get_called_class();

		// Initialize model
		$model = self::init_model($model_name);
		
		// Execute query and check return value
		$res = self::raw_query($model_name)
			->select(array('count(*)'))
			->execute();
		
		// Return results from database
		return $res[0][0];
	}	
	
	//! Create a new record in database of this model
	/**
	 * Insert a new record in database and get the reference objetc.
	 * @param $args Associative array with new records parameters. Key is the
	 *  is the field name and value the desired value. Any missing field is
	 *  set the "default" value that was defined on the module otherwise is not defined.
	 * @return - @b Object of the new model record.
	 *  - @b false on any kind of error.
	 */
	static public function create($args = array(), $model_name = NULL)
	{
	    if ($model_name === NULL)
			$model_name = get_called_class();

	    // Initialize model
		$model = self::init_model($model_name);

		// Event notification
        self::filter_event(
            $model_name,
            'op.pre.create',
            $args,
            array('model' => $model_name));
        if ($args === false)
            return false;

		// Prepare values
		$insert_args = array();
		$values = array();
		foreach($model->fields(true) as $field_name => $field)
		{	
			if (isset($args[$field_name]))
				$values[$field_name] = $model->db_field_data($field_name, $args[$field_name]);
		    else if ($field['ai'])
				continue;	// There is no default values for AI fields
			else if ($field['default'] != FALSE)
				$values[$field_name] = $model->db_field_data($field_name, $field['default']);
			else if ($field['pk'])
				throw new RuntimeException("You cannot create a {$model_name} object  without defining ". 
					"non auto increment primary key '{$field['name']}'");
			else
				continue;	// No user input and no default values
				
			$insert_args[] = $values[$field_name]; 
		}
		
		// Prepare query
		$q = self::raw_query($model_name)
			->insert(array_keys($values))
			->values_array($insert_args);
		
		if (($ret = $q->execute()) === FALSE)
			return false;
	
		// Fill autoincrement fields
		if (count($model->ai_fields()) > 0)
		{
		    $ai = $model->ai_fields(false);
			$values[$ai[0]] = DB_Conn::last_insert_id();
		}
		
		// If we have all the attributes of model, directly create object,
		// otherwise open object from database.
		if (count($values) === count($model->fields()))
		{	// Translate data to sql based key
			$sql_fields = array();
			foreach($values as $field_name => $value)
				$sql_fields[$model->field_info($field_name, 'sqlfield')] = $value;			

			$new_object = new $model_name($model, $sql_fields);
		}
		else
		{
		    // Open data based on primary key.
		    foreach($model->pk_fields() as $pk_name)
			    $pk_values[$pk_name] = $values[$pk_name];
			    
            $new_object = DB_Record::open($pk_values, $model_name);
        }

        // Event notification
        self::notify_event(
            $model_name,
            'op.post.create',
            array('record' => $new_object, 'model' => $model_name));

        return $new_object;
	}
	
	//! Data values of this instance
	protected $fields_data = array();
	
	//! Cache used for cachings casts
	protected $data_cast_cache = array();
	
	//! Track dirty fields for delta updates
	protected $dirty_fields = array();
	
	//! Model meta data pointer
	protected $model = NULL;
	
	//! Final constructor of DB_Record 
	/**
	 * Constructor is declared final to prohibit direct instantiantion
	 * of this class.
	 * @remarks
	 * You DON'T use @b new to create objects manually instead use create()
	 * and open() functions that will create objects for you.
	 * 
	 * @param $model_meta The meta data of the model that the instance is build from.
	 * @param $sql_data Data to fill the $fields_data given in assoc array using @i sqlfield as key
	 */
	final public function __construct(& $model, $sql_data = NULL)
	{
	    $this->model = & $model;
	
		// Populate fields data
		foreach($model->fields(true) as $field_name => $field)
		{	
		    $this->fields_data[$field_name] = (isset($sql_data[$field['sqlfield']]))?$sql_data[$field['sqlfield']]:NULL;
			$this->data_cast_cache[$field_name] = NULL;			
		}
	}
	
	//! Save changes in database
	/**
	 * Dump all changes of this object in the database. DB_Record
	 * will update only @i dirty fields.
	 * @return - @b true If the object had dirty fields and the database
	 *      was updated succesfully.
	 *  - @b false If no update in database was performed.
	 */
	public function save()
	{	
		if(count($this->dirty_fields) === 0)
			return false;	// No changes

		// Event notification
		$cancel = false;
        self::filter_event(
            $this->model->name(),
            'op.pre.save',
            $cancel,
            array('model' => $this->model->name(), 'record' => $this, 'old_values' => $this->dirty_fields));
        if ($cancel)
            return false;            

		// Create update query
		$update_args = array();
		$q = self::raw_query($this->model->name())
			->update()
			->limit(1);

		// Add delta fields
		foreach($this->dirty_fields as $field_name => $old_value)
		{
		    $q->set($field_name);
			$update_args[] = $this->fields_data[$field_name];
		}

		// Add Where clause based on primary keys.
		// Note: We must use old values if pk are changed 
		// otherwise we will write over a wrong record.
		foreach($this->model->pk_fields() as $field_name => $pk)
		{
		    $q->where("{$pk} = ?");
		    if (isset($this->dirty_fields[$pk]))
		        $update_args[] = $this->dirty_fields[$pk];
		    else
    			$update_args[] = $this->fields_data[$pk];
		}

		// Execute query
		$res = call_user_func_array(array($q, 'execute'), $update_args);
		if ((!$res) || ($res->affected_rows !== 1))
            return false;

        // Clear dirty fields
        $this->dirty_fields = array();

        // Event notification
        self::notify_event(
            $this->model->name(),
            'op.post.save',
            array('record' => $this, 'model' => $this->model->name()));
            
		return true;
	}
	
	//! Delete this record
	/**
	 * It will delete the record from database. However the object
	 * will not be destroyed so be carefull to dump it after deletion.
     * @return - @b true If the record was succesfully deleted.
     *  - @b false On any kind of error.
	 */
	public function delete()
	{	
        // Event notification
		$cancel = false;
        self::filter_event(
            $this->model->name(),
            'op.pre.delete',
            $cancel,
            array('model' => $this->model->name(), 'record' => $this)
        );
        if ($cancel)
            return false;
            
		// Create delete query
		$delete_args = array();
		$q = self::raw_query($this->model->name())
			->delete()
			->limit(1);
		
		// Add Where clause based on primary keys
		foreach($this->key(true) as $pk => $value)
		{	$q->where("{$pk} = ?");
			$delete_args[] = $value;
		}
		
		// Execute query
		$res = call_user_func_array(array($q, 'execute'), $delete_args);
		if ((!$res) || ($res->affected_rows !== 1))
		    return false;

        // Post-Event notification
        self::notify_event(
            $this->model->name(),
            'op.post.delete',
            array('record' => $this, 'model' => $this->model->name()));

		return true;
	}

	//! Get the key of this record
	public function key($assoc = false)
	{	
		$values = array();

		if ($assoc)
			foreach($this->model->pk_fields() as $pk)
				$values[$pk] = $this->fields_data[$pk];
		else
			foreach($this->model->pk_fields() as $pk)
				$values[] = $this->fields_data[$pk];
		return $values;
	}
	
	//! Get the value of a field
	/**
	 * It will return data of any field that you request. Data will be 
	 * converted from sql format to user format before returned. This means
	 * that fields of type "datetime" will be converted to php native DateTime object,
	 * "serialized" fields will be unserialized before returned to user.
	 * 
	 * @param $name
	 * @return 
	 * 	- The data of the field converted in user format.
	 * 	- @b NULL if there is no field with that name. In that case a php error will be triggered too.
	 *	.
	 *
	 * @note __get() and __set() are php magic methods and can be declare to overload the 
	 *  standard procedure of accesing object properties. It is @b not not nessecary to
	 *  use them as function @code echo $record->__get('myfield'); @endcode but use them as
	 *  object properties @code echo $record->myfield; @endcode
	 * 
	 * @see __set()
	 */
	public function __get($name)
	{
		if ($this->model->has_field($name))
		{	
			// Check for data
			return $this->model->user_field_data(
				$name,
				$this->fields_data[$name]
			);
		}
		
		if ($this->model->has_relationship($name))
		{	
			$rel = $this->model->relationship_info($name);
			
			if ($rel['type'] === 'one')
			{
				return DB_Record::open(
					$this->__get($this->model->fk_field_for($rel['foreign_model'])),
					$rel['foreign_model']
				);
			}
			if ($rel['type'] === 'many')
			{	
				$pks = $this->key();
				return new DB_Record_RelationshipMany(
			        $this->model,
					$rel['foreign_model'],
					$pks[0]);
			}

			if ($rel['type'] === 'bridge')
			{ 
				$pks = $this->key();
			    return new DB_Record_RelationshipBridge(
			        $this->model,
			        $rel['bridge_model'],
			        $rel['foreign_model'],
			        $pks[0]
			    );
			}
			
			throw new RuntimeException("Unknown DB_Record relation type '{$rel['type']}'");
		}
		
		// Oops!
		$trace = debug_backtrace();
		throw new InvalidArgumentException("{$this->model->name()}(DB_Record)->{$name}" . 
			" is not valid field of model {$this->model->name()}, requested at {$trace[0]['file']} ".
			" on line {$trace[0]['line']}");
	}
	
	//! Set the value of a field
	public function __set($name, $value)
	{
		if ($this->model->has_field($name))
		{
			// Mark it as dirty and save old value
			$this->dirty_fields[$name] = $this->fields_data[$name];
			
			// Set data
			return $this->fields_data[$name] = 
				$this->model->db_field_data(
					$name,
					$value
				);
		}
		
		if ($this->model->has_relationship($name))
		{	$rel = $this->model->relationship_info($name);
			
			if ($rel['type'] == 'one')
			{	if (is_object($value))
				{	$fm = DB_Model::open($rel['foreign_model']);
					$pks = $fm->pk_fields();
					$this->__set(
					    $this->model->fk_field_for($rel['foreign_model']),
					    $value->__get($pks[0]));
				}
				else
					$this->__set(
					    $this->model->fk_field_for($rel['foreign_model']),
					    $value
					);

				return $value;
			}
			
			if ($rel['type'] == 'many')
				return false;
			
			throw new RuntimeException("Unknown DB_Record relation type '{$rel['type']}'");
		}
		
		// Oops!
	    $trace = debug_backtrace();
		throw new InvalidArgumentException("{$this->model->name()}(DB_Record)->{$name}" . 
			" is not valid field of model {$this->model->name()}, requested at {$trace[0]['file']} ".
			" on line {$trace[0]['line']}");
	}

	//! Validate if a field is set
	public function __isset($name)
	{   
		if (($this->model->has_field($name))
		    ||  ($this->model->has_relationship($name)))
		    return true;
		return false;
    }
	
	//! Serialization implementation
	public function __sleep()
	{
	    return array('fields_data', 'dirty_fields');
	}
	
	//! Unserilization implementation
	public function __wakeup()
	{	
		// Initialize static
		$this->model = self::init_model(get_class($this));
	}
}
?>
