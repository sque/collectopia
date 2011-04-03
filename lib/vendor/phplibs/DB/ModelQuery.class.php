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


require_once(dirname(__FILE__) . '/./Conn.class.php');
require_once(dirname(__FILE__) . '/./Model.class.php');
require_once(dirname(__FILE__) . '/./ModelQueryCache.class.php');
require_once(dirname(__FILE__) . '/../functions.lib.php');

//! Execute SQL queries on models
/**
 * This is an sql-like interface to query on models.
 * You can insert,update,select,delete with any user-defined option
 * but only on the same model.
 * @author sque
 *
 */
class DB_ModelQuery
{
	//! Query type
	protected $query_type = NULL;
	
	//! Pointer to model
	protected $model = NULL;
	
	//! SELECT retrieve fields
	protected $select_fields = array();
	
	//! UPDATE set fields
	protected $set_fields = array();
	
	//! INSERT fields
	protected $insert_fields = array();
	
	//! All the insert values
	protected $insert_values = array();
	
	//! Limit of affected records
	protected $limit = NULL;
	
	//! Order of affected records
	protected $order_by = array();

	//! Group rules for retrieving data
	protected $group_by = array();

    //! Left join table
    protected $ljoin = NULL;
	
	//! WHERE conditions
	protected $conditions = array();
	
	//! Hash populated by the user instructions
	protected $sql_hash = NULL;
	
	//! The final sql string
	protected $sql_query = NULL;
	
	//! Data wrapper callback
	protected $data_wrapper_callback = NULL;
	
	//! Query cache hints
	protected $cache_hints = NULL;
	
	//! Query cache
	protected $query_cache;
	
	//! Execute parameters
	protected $exec_params = array();
	
	//! Use DB_Record::open_query() factory to create DB_ModelQuery objects
	/**
	 * @see DB_Record::open_query() on how to create objects of this class.
	 * @param $model Pass model object
	 * @param $data_wrapper_callback A callback to wrap data after execution
	 */
	final public function __construct(DB_Model $model, $data_wrapper_callback = NULL)
	{	
		// Save pointer of the model
		$this->model = & $model;
		$this->data_wrapper_callback = $data_wrapper_callback;
		$this->query_cache = DB_ModelQueryCache::open($model);
		$this->reset();		
	}
	
	//! Reset query so that it can be used again
	public function & reset()
	{	
	    // Reset all values to default
		$this->query_type = NULL;
		$this->select_fields = array();
		$this->set_fields = array();
		$this->insert_fields = array();
		$this->insert_values = array();
		$this->limit = NULL;
		$this->order_by = array();
		$this->ljoin = NULL;
		$this->conditions = array();
		$this->sql_hash = 'HASH:' . $this->model->table() .':';
		$this->sql_query = NULL;
		$this->cache_hints = NULL;

		return $this; 
	}
	
	//! Check if statement is alterable
	/**
	 * Alterable means that there can be more options on the query. 
	 * @return
	 *  - @b true if query is alterable
	 *  - @b false if the query is closed for changes. 
	 */
	public function is_alterable()
	{
	    return ($this->sql_query === NULL);
    }
	
	//! Check if it i alterable otherwise throw exception
	private function assure_alterable()
	{
	    if (!$this->is_alterable())
			throw new RuntimeException('This DB_ModelQuery instance is no longer alterable!');
	}
	
	//! Start a deletion on model
	public function & delete()
	{	
	    $this->assure_alterable();
	
		// Check if there is already a type command
		if ($this->query_type !== NULL)
			throw new RuntimeException('This DB_ModelQuery instance has already defined its type "' . $this->query_type . '"!');

		$this->query_type = 'delete';
		$this->sql_hash .= ':delete:';
		return $this; 
	}
	
	//! Start an update on model
	public function & update()
	{	
	    $this->assure_alterable();
	
		// Check if there is already a type command
		if ($this->query_type !== NULL)
			throw new RuntimeException('This DB_ModelQuery instance has already defined its type "' . $this->query_type . '"!');

		$this->query_type = 'update';
		$this->sql_hash .= ':update:';
		return $this; 
	}
	
	//! Start a selection query on model
	/*
	 * @param $fields @b Array of field names that you want to fetch values from.
	 */
	public function & select($fields)
	{	
	    $this->assure_alterable();
	
		// Check if there is already a type command
		if ($this->query_type !== NULL)
			throw new RuntimeException('This DB_ModelQuery instance has already defined its type "' . $this->query_type . '"!');
		
		$this->query_type = 'select';
		$this->select_fields = $fields;
		$this->sql_hash .= ':select:' . implode(':', $fields);
		return $this;
	}
	
	//! Start an insertation query on model
	/**
	 * @param $fields @b Array of field names that you will provide values for.
	 */
	public function & insert($fields)
	{	
	    $this->assure_alterable();
	
		// Check if there is already a type command
		if ($this->query_type !== NULL)
			throw new RuntimeException('This DB_ModelQuery instance has already defined its type "' . $this->query_type . '"!');
		
		$this->query_type = 'insert';
		$this->insert_fields = $fields;
		$this->sql_hash .= ':insert:' . implode(':', $fields);
		return $this;
	}
	
	//! Define values of insert command as an array
	/**
	 * @param $values_array An array of values for adding one record. The values must be
	 *  in the same order as the fields where declared in insert().
	 */
	public function & values_array($values)
	{	
	    $this->assure_alterable();

	    // Check if there is already a type command
		if ($this->query_type !== 'insert')
			throw new RuntimeException('You cannot add values in a non-insert query!');
			
		if (count($values) != count($this->insert_fields))
			throw new InvalidArgumentException('The quantity of values, must be exactly ' .
				'the same with the fields defined with insert()');
				
		$this->insert_values[] = $values;
        $this->push_exec_params($values);
        
		$this->sql_hash .= ':v' . count($values);
		return $this;
	}
	
	//! Define values of insert command as arguments
	/**
	 * Same as values_array(), only this one you pass the values as function arguments
	 */
	public function & values()
	{	
	    $args = func_get_args();
		return $this->values_array($args);
	}
	
	//! Set a field value
	/**
	 * @param $field The field to set its value to a new one
	 * @param $value [Default = false] Optional literal value to push in dynamic parameters.
	 */
	public function & set($field, $value = false)
	{	
	    $this->assure_alterable();
		$this->set_fields[] = array(
			'field' => $field,
			'value' => $value
		);
		if ($value !== false)
		    $this->push_exec_param($value);
		$this->sql_hash .= ':set:' . $field;
		return $this;
	}

	//! Add a general conditional expresion on query
	/**
	 * @param $exp A single operand expression between fields and dynamic parameters (exclamation mark).
	 *  If you want to pass a literal value, use combination of dynamic (?) and push_exec_param().\n
     *  @b Examples:
     *  - @code 'title = ?' @endcode
     *  - @code '? = ?' @endcode
     *  - @code 'title LIKE ?' @endcode
     *  - @code 'title NOT LIKE ?' @endcode
     *  - @code 'title IS NULL' @endcode
     *  - @code 'title IS NOT FALSE' @endcode
     *  - @code 'title IS UNKNOWN' @endcode
     *  .
     * @param $bool_op [Default = "AND"]: <strong> [AND|OR|XOR] [NOT] </strong>
     *  - @b 'AND' Use boolean @b AND operator between this expression and the previous one.
     *  - @b 'OR' Use boolean @b NOT operator between this expression and the previous one.
     *  - @b 'XOR' Use boolean @b XOR operator between this expression and the previous one.
     *  .
     *  @b Examples:
     *  - @code 'AND' @endcode
     *  - @code 'NOT' @endcode
     *  - @code 'OR NOT' @endcode
     *  - @code 'XOR NOT' @endcode
     *  .
     */
	public function & where($exp, $bool_op = 'AND')
	{	
	    $this->assure_alterable();
		$this->conditions[] = $cond = array(
			'expression' => $exp,
			'bool_op' => strtoupper($bool_op),
			'op' => NULL,
			'lvalue' => NULL,
			'rvalue' => NULL,
			'require_argument' => false,
		);

		$this->sql_hash .= ':where:' . $cond['bool_op'] . ':' . $exp;
		return $this;
	}
	
	//! Add an "in" conditional expression on query
	/**
	 * @param $field_name The name of the field to be checked for beeing equal with an array entity.
     * @param $values
     *  - @b integer The number of dynamic values
     *  - @b array An static values to pass on where clause.
     *  .
     * @param $bool_op [Default = "AND"]: <strong> [AND|OR|XOR] [NOT] </strong>
     *  - @b 'AND' Use boolean @b AND operator between this expression and the previous one.
     *  - @b 'OR' Use boolean @b NOT operator between this expression and the previous one.
     *  - @b 'XOR' Use boolean @b XOR operator between this expression and the previous one.
     *  .
     *  @b Examples:
     *  - @code 'AND' @endcode
     *  - @code 'NOT' @endcode
     *  - @code 'OR NOT' @endcode
     *  - @code 'XOR NOT' @endcode
     *  .
     */
    public function & where_in($field_name, $values, $bool_op = 'AND')
    {
	    $this->assure_alterable();
		$this->conditions[] = $cond = array(
			'bool_op' => strtoupper($bool_op),
			'op' => 'IN',
			'lvalue' => $field_name,
			'rvalue' => is_array($values)?count($values):$values,
			'require_argument' => false,
		);
		
		// Push execute parameters
		if (is_array($values))
		    $this->push_exec_params($values);

		$this->sql_hash .= ':where:' . $cond['bool_op'] . ':' . (is_array($values)?count($values):$values);
		return $this;
    }

	//! Declare left join table (for extra criteria only)
	/**
	 * After declaring left join you can use it in criteria by refering to it with "l" shortcut.
	 * Example l.title = ?
	 * @param $model_name The left joined model.
	 * @param $primary_field [Default: null] The local field of the join.
	 * @param $joined_field [Default: null] The foreing field of the join.
	 * @note If there is a declared relationship between this model and the left join, you can
	 *  ommit $primary_field and $joined_field as it can implicitly join on the declared reference key.
	 */
	public function & left_join($model_name, $primary_field = null, $joined_field = null)
	{   
	    $this->assure_alterable();

	    // Check if there is already a type command
		if ($this->query_type !== 'select')
			throw new RuntimeException('You cannot declare left_join on DB_ModelQuery that is not of SELECT type!');

		$this->ljoin = array(
		    'model_name' => $model_name,
		    'join_local_field' => $primary_field,
		    'join_foreign_field' => $joined_field
		);
		$this->sql_hash .= ':ljoin:' . $model_name . ':' . $primary_field . ':' . $joined_field;
	    return $this;
	}

	//! Limit the records affected by this query
	/**
	 *  @param $length The number of records to be retrieved or affected
	 *  @param $offset The offset of records that query will start to retrive or affect.
	 */
	public function & limit($length, $offset = NULL)
	{	
	    $this->assure_alterable();
		$this->limit = array('length' => $length, 'offset' => $offset);
		$this->sql_hash .= ':limit:' . $length . ':' . $offset;
		return $this;
	}
	
	//! Add an order by rule in query
	/**
	 * @param $expression A field name, column reference or an expression to be evaluated for each row.
	 * @param $direction The direction of ordering.
     */
	public function & order_by($expression, $direction = 'ASC')
	{	
	    $this->assure_alterable();
		$this->order_by[] = array(
		    'expression' => $expression,
		    'direction' => $direction
        );
		$this->sql_hash .= ':order:' . $expression . ':' . $direction;
		return $this;
	}

	//! Add a group by by rule in query
	/**
	 * @param $expression A field name, column reference or an expression to be evaluated for each row.
	 * @param $direction The direction of ordering prior grouping.
     */
	public function & group_by($expression, $direction = 'ASC')
	{	
	    $this->assure_alterable();
		$this->group_by[] = array(
		    'expression' => $expression,
		    'direction' => $direction
        );
		$this->sql_hash .= ':group:' . $expression . ':' . $direction;
		return $this;
	}


	//! Set the callback wrapper function
	public function & set_data_wrapper($callback)
	{   
	    $this->assure_alterable();
	    $this->data_wrapper_callback = $callback;
	    return $this;
	}
	
	//! Push an execute parameter
	public function & push_exec_param($value)
	{	    
	    $this->exec_params[] = $value;
		return $this;
	}
	
	//! Push an array of execute parameters
	public function & push_exec_params($values)
	{
	    foreach($values as $v)
    	    $this->exec_params[] = $v;
	    return $this;
	}
	
	//! Get the type of query
	public function type()
	{   
	    return $this->query_type;
	} 
	
	//! Get query hash
	public function hash()
	{
	    return $this->sql_hash;
    }

	//! Analayze column reference
	/**
     * Analyze an already parsed column reference.
	 *  @param $table_shorthand The table shorthand of the column ("p" or "l").
     *  @param $column The column friendly name as parsed.
	 */
	private function analyze_column_reference($table_shorthand, $column)
	{
	    $result = array(
	        'table_short' => (empty($table_shorthand)?'p':$table_shorthand),
	        'column' => $column,
	        'column_sqlfield' => null,
	        'query' => ''
	    );
	    
        if ($result['table_short'] === 'p')
    	        $result['column_sqlfield'] = $this->model->field_info($column, 'sqlfield');
    	    else if ($result['table_short'] === 'l')
    	    {   
    	        if ($this->ljoin === NULL)
    	            throw new InvalidArgumentException("You cannot use \"l\" shorthand in EXPRESION when there is no LEFT JOIN!");
    	        $result['column_sqlfield'] = $this->ljoin['model']->field_info($column, 'sqlfield');
    	    }
    	        	    
            if ($result['column_sqlfield'] === NULL)
			    throw new InvalidArgumentException(
			        "There is no field with name \"{$column}\" in model \"{$this->model->name()}\"");
			        
	    //! Construct valid sql query
        $result['query'] = (($this->ljoin !== NULL)?$result['table_short'] . '.':'') . '`' . $result['column_sqlfield'] . '`';
        return $result;
	}
	
	
	//! Analyze single expresison side value
	private function analyze_exp_side_value(& $cond, $side, $string)
	{
	    $matched = preg_match_all(
	        '/^[\s]*' . // Pre-field space
	        '(' .
	            '(?P<wildcard>\?)' .                        // prepared statement wildcard
	            '|((?P<table>p|l)\.)?(?P<column>[\w\-]+)' .  // column reference
	        ')' . 
	        '[\s]*/', // Post-field space
	        $string,
	        $matches
	    );

	    if ($matched != 1)
		    throw new InvalidArgumentException("Invalid EXPRESSION '{$cond['expression']}' was given.");

	    if ($matches['wildcard'][0] === '?')
	    {   
	        $cond['require_argument'] = true;
	        $cond[$side] = '?';
	    }
	    else
	    {   
            $anl = $this->analyze_column_reference($matches['table'][0], $matches['column'][0]);
            $cond[$side] = $anl['query'];
	    }
	}
	
	//! Analyze single expression of the form l-Value OP r-Value
	private function analyze_single_expression(& $cond, $expression)
	{
        $matched = 
		    preg_match_all('/^[\s]*(?<lvalue>([\w\.\?])+)[\s]*' .
		        '(?P<not_pre_op>not\s)?[\s]*' .
			    '(?P<op>[=<>]+|like|is)[\s]*' .
		        '(?P<not_post_op>\snot)?[\s]*' .
			    '(?P<rvalue>([\w\.\?])+)[\s]*$/i',
			    $expression, $matches);

	    if ($matched != 1)
		    throw new InvalidArgumentException("Invalid EXPRESSION '{$expression}' was given.");

        // Operator
	    $cond['op'] = strtoupper($matches['not_pre_op']['0']) . 
	        strtoupper($matches['op'][0]) . strtoupper($matches['not_post_op'][0]);
        $cond['require_argument'] = false;
        
        // Check operator
        if (! in_array($cond['op'], array('=', '>', '>=', '<', '<=', '<>', 'LIKE', 'NOT LIKE', 'IS', 'IS NOT')))
            throw new InvalidArgumentException("Invalid EXPRESSION operand '{$cond['op']}' was given.");
		
        // L-value
        $this->analyze_exp_side_value($cond, 'lvalue', $matches['lvalue'][0]);
        
        // R-value
        if (substr($cond['op'], 0, 2) == 'IS')
        {
            $cond['rvalue'] = strtoupper($matches['rvalue'][0]);
            if (! in_array($cond['rvalue'], array('NULL', 'TRUE', 'FALSE', 'UNKNOWN')))
                throw new InvalidArgumentException("Invalid IS r-value '{$cond['rvalue']}' was given.");
        }
        else
            $this->analyze_exp_side_value($cond, 'rvalue', $matches['rvalue'][0]);
        
        // Generated condition
        $cond['query'] = "{$cond['lvalue']} {$cond['op']} {$cond['rvalue']}";
	}
	
	//! Analyze WHERE conditions and return query
	private function generate_where_conditions()
	{	
	    $query = '';
		if (count($this->conditions) > 0)
		{	
		    $query = ' WHERE';
			$first = true;
			foreach($this->conditions as & $cond)
			{
			    // Check boolean operation
			    $matched = 
		            preg_match_all('/^[\s]*(?<op>\bAND|OR|XOR\b)?[\s]*(?<not>\bNOT\b)?[\s]*$/',
	                $cond['bool_op'], $matches);
	            if ($matched != 1)
			        throw new InvalidArgumentException("The boolean operator \"{$cond['bool_op']}\" is invalid");
                $cond['bool_op'] = array('op' => (empty($matches['op'][0])?'AND':$matches['op'][0]));
                $cond['bool_op']['not'] = ($matches['not'][0] == 'NOT');
                
			    if ($cond['op'] === null)
			        $this->analyze_single_expression($cond, $cond['expression']);
                else if($cond['op'] === 'IN')
                {
                    // L-value
                    $this->analyze_exp_side_value($cond, 'lvalue', $cond['lvalue']);

                    $array_size = (integer) $cond['rvalue'];
                    $cond['rvalue'] = '(' . implode(', ', array_fill(0, $array_size, '?')) . ')';
                    $cond['query'] = "{$cond['lvalue']} {$cond['op']} {$cond['rvalue']}";
                }
                
				if ($first)
					$first = false;
				else
					$query .= ' ' . $cond['bool_op']['op'];
				$query .= ($cond['bool_op']['not']?' NOT':'') . ' ' . $cond['query'];
					
			}
			unset($cond);
		}
		return $query;
	}
	
	//! Generate LIMIT clause
	private function generate_limit()
	{   
	    // No limit
	    if ($this->limit === NULL)
	        return '';
		
		// Limit
        if (($this->limit['offset'] !== NULL) && ($this->query_type === 'select'))
			return " LIMIT {$this->limit['offset']},{$this->limit['length']}";

        return " LIMIT {$this->limit['length']}";
    }
    
    //! Analyze * BY clause
    private function analyze_by_rules($by_rules)
    {
        if (empty($by_rules))
            return '';

	    $gen_rules = array();
        foreach($by_rules as $rule)
        {
            // Check direction string
            $rule['direction'] = (strtoupper($rule['direction']) === 'ASC'?'ASC':'DESC');
        
            // Check for field name and column name
            $matched = preg_match_all(
	            '/^[\s]*' . // Pre space
	            '(' .
	                '(?P<wildcard>\?)' .                        // prepared statement wildcard
	                '|(?P<num_ref>[\d]+)' .                     // numeric column reference
	                '|((?P<table>p|l)\.)?(?P<column>[\w\-]+)' .  // named column reference,
	            ')' . 
	            '[\s]*$/', // Post space
	        $rule['expression'],
	        $matches);


	        if ($matched != 1)
	        {
	            // Not found lets try single expression analysis
	            $exp_params = array();
	            $this->analyze_single_expression($exp_params, $rule['expression']);
	            $gen_rules[] = $exp_params['query'] . ' ' . $rule['direction'];
	            continue;
	        }
    
	        if ($matches['wildcard'][0] === '?')
	        {   
	            $cond['require_argument'] = true;
	            $cond[$side] = '?';
	        }
	        else if ($matches['num_ref'][0] !== '')
	        {
	            $col_ref = $matches['num_ref'][0];
	            $total_cols = count($this->select_fields);
	            if (($col_ref > $total_cols) or ($col_ref < 1))
	                throw new InvalidArgumentException("The column numerical reference \"$col_ref\" " .
	                    "exceeded the boundries of retrieved fields");
	                    
                $gen_rules[] = (string)$col_ref . ' ' . $rule['direction'];
	        }
	        else
	        {
                $anl = $this->analyze_column_reference($matches['table'][0], $matches['column'][0]);
                $gen_rules[] = $anl['query'] . ' ' . $rule['direction'];
	        }
        }
        
        return implode(', ', $gen_rules);
    }
    
    //! Generate ORDER BY clause
    private function generate_order_by()
    {
        $rules = $this->analyze_by_rules($this->order_by);
        if ($rules == '')
            return '';
        return ' ORDER BY ' . $rules;
    }
    
    // Generate GROUP BY
    private function generate_group_by()
    {
        $rules = $this->analyze_by_rules($this->group_by);
        if ($rules == '')
            return '';
        return ' GROUP BY ' . $rules;
    }
    
    private function generate_left_join()
    {
        if ($this->ljoin === NULL)
            return '';

        // Add foreign model name
        if (! isset($this->ljoin['model']))
        {
            $lmodel_name = $this->ljoin['model_name'];
            if ( !($this->ljoin['model'] = DB_Model::open($lmodel_name)))
                throw new InvalidArgumentException("Cannot find model with name \"{$lmodel_name}\".");
        }
        
        // Add explicit relationship
        if (($this->ljoin['join_foreign_field'] !== null) && ($this->ljoin['join_local_field'] !== null))
        {
            $lfield = $this->ljoin['model']->field_info($this->ljoin['join_foreign_field'], 'sqlfield');
            if (!$lfield)
                throw new InvalidArgumentException(
                    "There is no field with name \"{$this->ljoin['join_foreign_field']}\" on model \"{$lmodel_name}\".");
            $pfield = $this->model->field_info($this->ljoin['join_local_field'], 'sqlfield');
            if (!$pfield)
                throw new InvalidArgumentException(
                    "There is no field with name \"{$this->ljoin['join_local_field']}\" on model \"{$this->model->name()}\".");
        }
        else
        {
            // Add implicit relationship
            if (($pfield = $this->model->fk_field_for($lmodel_name, true)))
            {   
                $pfield = $pfield['sqlfield'];
                list($lfield) = $this->ljoin['model']->pk_fields();
                $lfield = $this->ljoin['model']->field_info($lfield, 'sqlfield');
            }
            else if (($lfield = $this->ljoin['model']->fk_field_for($this->model->name(), true)))
            {
                $lfield = $lfield['sqlfield'];
                list($pfield) = $this->model->pk_fields(false);
                $pfield = $this->model->field_info($pfield, 'sqlfield');
            }
            else
            {
                // No relationship found
                throw new InvalidArgumentException(
                    "You cannot declare a left join of \"{$this->model->name()}\" ".
                     "with \"{$lmodel_name}\" without explicitly defining join fields.");
            }
        }
        return " LEFT JOIN `{$this->ljoin['model']->table()}` l ON l.`{$lfield}` = p.`{$pfield}`";
    }

	//! Generate SELECT query
	private function generate_select_query()
	{
	    $query = 'SELECT';
		foreach($this->select_fields as $field)
		{	if (strcasecmp($field, 'count(*)') === 0)
			{	
			    $fields[] = 'count(*)';
				continue;
			}
			$fields[] = (($this->ljoin !== NULL)?'p.':'') . "`" . $this->model->field_info($field, 'sqlfield') . "`";
		}

		$query .= ' ' . implode(', ', $fields);
		$query .= ' FROM `' . $this->model->table() . '`' . (($this->ljoin !== NULL)?' p':'');

        // Left join
        $query .= $this->generate_left_join();
        
		// Conditions
		$query .= $this->generate_where_conditions();
		
		// Group by
		$query .= $this->generate_group_by();
		
        // Order by
        $query .= $this->generate_order_by();

		// Limit
		$query .= $this->generate_limit();
		
		return $query;
	}
	
	//! Generate UPDATE query
	private function generate_update_query()
	{	
	    $query = 'UPDATE `' . $this->model->table() . '` SET';
	
		if (count($this->set_fields) === 0)
			throw new InvalidArgumentException("Cannot execute update() command without using set()");
			
		foreach($this->set_fields as $params)
		{
		    if (!($sqlfield = $this->model->field_info($params['field'], 'sqlfield')))
    			throw new InvalidArgumentException("Unknown field {$params['field']} in update() command.");
		        
			$set_query = "`" . $sqlfield . "` = ?";
            $fields[] = $set_query;
		}
		$query .= ' ' . implode(', ', $fields);
		$query .= $this->generate_where_conditions();
		
        // Order by
        $query .= $this->generate_order_by();
        
		// Limit
		$query .= $this->generate_limit();

		return $query;
	}
	
	//! Generate INSERT query
	private function generate_insert_query()
	{
	    $query = 'INSERT INTO `' . $this->model->table() . '`';
	
		if (count($this->insert_fields) === 0)
			throw new InvalidArgumentException("Cannot execute insert() with no fields!");
			
		foreach($this->insert_fields as $field)
			$fields[] = "`" . $this->model->field_info($field, 'sqlfield') . "`";

		$query .= ' (' . implode(', ', $fields) . ') VALUES';
		if (count($this->insert_values) === 0)
			throw new InvalidArgumentException("Cannot insert() with no values, use values() to define them.");

        $query .= str_repeat(
            ' (' . implode(', ', array_fill(0, count($this->insert_fields), '?')) . ')',
            count($this->insert_values)
        );

		return $query;
	}
	
	//! Analyze DELETE query
	private function generate_delete_query()
	{	
	    $query = 'DELETE FROM `' . $this->model->table() . '`';
		$query .= $this->generate_where_conditions();
		
        // Order by
        $query .= $this->generate_order_by();
		
		// Limit
		$query .= $this->generate_limit();
		
		return $query;
	}


	//! Get cache hint for caching query results
	public function cache_hints()
	{   
	    // Return if it is already generated
	    if ($this->cache_hints !== NULL)
	        return $this->cache_hints;

        // Check that it is no longer altera ble
	    if ($this->sql_query === NULL)
	        return NULL;
	        
	    // Initialize array
	    $this->cache_hints = array(
	        'cachable' => ($this->query_type === 'select'),
	        'invalidate_on' => array()
	    );

        // Left joins are not cachable
	    if ($this->ljoin !== NULL)
	        $this->cache_hints['cachable'] = false;

	    return $this->cache_hints;
	}
	
	//! Create the sql command for this query
	/**
	 * Executing sql() will make query non-alterable and fixed,
	 * however you can use execute() multiple times.
	 * @return The string with SQL command.
	 */
	public function sql()
	{	
	    // Check if sql has been already crafted
		if ($this->sql_query !== NULL)
			return $this->sql_query;
		
		// Check model cache
		$cache = $this->model->fetch_cache($this->sql_hash, $succ);
		if ($succ)
		{	$this->sql_query = $cache['query'];
		    $this->cache_hints = $cache['cache_hints'];
			return $this->sql_query;
		}
		
		if ($this->query_type === 'select')
			$this->sql_query = $this->generate_select_query();
		else if ($this->query_type === 'update')
			$this->sql_query = $this->generate_update_query();
		else if ($this->query_type === 'delete')
			$this->sql_query = $this->generate_delete_query();
		else if ($this->query_type === 'insert')
			$this->sql_query = $this->generate_insert_query();
		else
			throw new RuntimeException('Query is not finished to be exported.' .
				' You have to use at least one of the main commands insert()/update()/delete()/select(). ');

        // Cache hints
        $this->cache_hints();
        
		// Save in cache
		$this->model->push_cache($this->sql_hash, 
		    array(
		        'query' => $this->sql_query,
		        'cache_hints' => $this->cache_hints
		    )
		);
        
		return $this->sql_query;
	}
	
	//! Force preparation of statement
	/**
	 * Prepare this statement if it is not yet. Otherwise don't do nothing.
	 * @note Statements are prepared automatically at execution time.
	 * @return NULL
	 */
	public function prepare()
	{	
	    if (!DB_Conn::is_key_used($this->sql_hash))
			return DB_Conn::prepare($this->sql_hash, $this->sql());
	}
	
	//! Execute statement and return the results
	public function execute()
	{	
	    // Merge pushed parameters with functions
		$params = func_get_args();		
		$params = array_merge($this->exec_params, $params);
		
		// Prepare query
		$this->prepare();

		// Check cache if select
		if ($this->query_type === 'select')
		{
			$data = $this->query_cache->fetch_results($this, $params, $succ);
			if ($succ)
				return $data;
		}

		// Execute query
		if ($this->query_type === 'select')
			$data = DB_Conn::execute_fetch_all($this->sql_hash, $params);
		else
			$data = DB_Conn::execute($this->sql_hash, $params);

		// User wrapper
		if ($this->data_wrapper_callback !== NULL)
		    $data = call_user_func($this->data_wrapper_callback, $data, $this->model);

		// Cache it
		$this->query_cache->process_query($this, $params, $data);
		
		// Return data
		return $data;
	}
}

?>
