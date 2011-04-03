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


//! Object handling collection from N-to-M relationship
/**
 * This object is constructed when requesting a relationship from a DB_Record.
 * Check DB_Record for more information on how to construct it.
 */
class DB_Record_RelationshipBridge
{
    //! Relationship options
    private $rel_params;

    //! Query object
    private $query;
    
    //! Construct relationship
    public function __construct($local_model, $bridge_model_name, $foreign_model_name, $local_value)
    {   
        // Construct relationship array
        $bridge_model = DB_Record::model($bridge_model_name);
        $foreign_model = DB_Record::model($foreign_model_name);

        $rel = array();
		$rel['local_model_name'] = $local_model->name();
		$rel['bridge_model_name'] = $bridge_model_name;    		
		$rel['foreign_model_name'] = $foreign_model_name;
		    $pks = $local_model->pk_fields();
	    $rel['local2bridge_field'] = $pks[0];
	    $rel['bridge2local_field'] = $bridge_model->fk_field_for($local_model->name());
	    $rel['bridge2foreign_field'] = $bridge_model->fk_field_for($foreign_model_name);
	        $pks = $foreign_model->pk_fields();
	    $rel['foreign2bridge_field'] = $pks[0];
	    $rel['local_bridge_value'] = $local_value;
        
		// Construct joined query
		$this->query = DB_Record::open_query($rel['foreign_model_name'])
            ->left_join($rel['bridge_model_name'], $rel['foreign2bridge_field'], $rel['bridge2foreign_field'])
            ->where('? = l.' . $rel['bridge2local_field'])
            ->push_exec_param($rel['local_bridge_value']);

        // Save relationship
        $this->rel_params = $rel;
    }

    public function add($record)
    {   
    	$keys = $record->key();
        $params = array(
            $this->rel_params['bridge2local_field'] => $this->rel_params['local_bridge_value'],
            $this->rel_params['bridge2foreign_field'] => $keys[0]
        );
        return DB_Record::create($params, $this->rel_params['bridge_model_name']);
    }

    public function remove($record)
    {   
    	$keys = $record->key();
        $params = array(
            $this->rel_params['bridge2local_field'] => $this->rel_params['local_bridge_value'],
            $this->rel_params['bridge2foreign_field'] => $keys[0]
        );
        if (($bridge_record = DB_Record::open($params, $this->rel_params['bridge_model_name'])) === FALSE)
            return false;

        return $bridge_record->delete();
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
}

?>
