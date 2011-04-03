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


//! Object handling collection from 1-to-M relationship
/**
 * This object is constructed when requesting a relationship from a DB_Record.
 * Check DBRecord for more information on how to construct it.
 */
class DB_Record_RelationshipMany
{
	//! The constructed query
	private $query;

    //! Relationship info
    private $rel_params = array();
    
    //! Construct relationship handler
	public function __construct($local_model, $foreign_model_name, $field_value)
	{	
		// Construct query object
	    $foreign_model = DB_Record::model($foreign_model_name);

	    // Save parameters
	    $this->rel_params['local_model'] = $local_model;
	    $this->rel_params['foreign_model'] = $foreign_model;
	    $this->rel_params['field_value'] = $field_value;
	    
		$this->query = DB_Record::open_query($foreign_model_name)
			->where($foreign_model->fk_field_for($local_model->name()) . ' = ?')
			->push_exec_param($field_value);
	}

	//! Get all records of this relationship
	public function all()
	{	
		return $this->query->execute();
	}

	//! Perform a subquery on this relationship
	public function subquery()
	{
		return $this->query;
	}

	//! Get one only member with a specific primary key
	public function get($primary_key)
	{
		$pks = $this->rel_params['foreign_model']->pk_fields();
	    $res = $this->subquery()->where("{$pks[0]} = ?")->execute($primary_key);
	    if (count($res) > 0)
	        return $res[0];
	    return NULL;
    }
}

?>
