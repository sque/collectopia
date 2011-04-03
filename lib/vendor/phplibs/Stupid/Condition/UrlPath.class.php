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


require_once(dirname(__FILE__) . '/../Condition.class.php');

//! Implementation of url_path Stupid_Condition
/**
 * A condition evaluator that can perform checks on the
 * given uri path.\n
 * This evaluator implements the <b> type = "url_path"</b> 
 *
 * The evaluator can work with chunks or full path. First you need
 * to determine on which path to look for, and them you add one or more
 * rules that must be checked.
 * 
 * @par Acceptable condition options
 * - @b path_type [Default = path_info]: request_uri, path_info
 * - @b delimiter [Default=/] : The delimiter that will be used to tokenize
 *      selected path in chunks.
 * - @b path: A regular expresion to check if path is equal to this.
 * - @b chunk[X]: A regular expression to check that this chunk conforms to.\n
 * 		@b X: A zero-based index of chunk. Negative values are accepted where -1 is the last, -2 pre-last.
 * .
 * 
 * @par Examples
 * @code
 * // Using chunks
 * Stupid::add_rule('create_news',
 *     array('type' => 'url_path', 'chunk[0]' => '/^news$/' , chunk[1] => '/([\d]+)/'));
 * // Using full path
 * Stupid::add_rule('create_news',
 *     array('type' => 'url_path', 'path' => '/^news/\+create$/'));
 * @endcode
 * @author sque
 */
class Stupid_Condition_UrlPath extends Stupid_Condition
{
	public static function type()
	{	return 'url_path';	}
 
	public function evaluate_impl($previous_backrefs)
	{
		// Default condition values
		$defcond = array(
			'path_type' => 'path_info',
			'delimiter' => '/',
			'bref' => array()						
		);

		// Merge default with user supplied options
		$options = array_merge($defcond, $this->options);

		// Type of path
		switch($options['path_type']){
		case 'request_uri':
			$subject_path = $_SERVER['REQUEST_URI'];
			break;
		case 'path_info':
			if (!isset($_SERVER['PATH_INFO']))	
				$subject_path = '';	// Non-existing path_info means empty one
			else
				$subject_path = $_SERVER['PATH_INFO'];
			break;
		}

		// Check if there is a path constrain
		if (isset($options['path']))
		{	if (preg_match($options['path'], $subject_path, $matches) != 1)
				return false;
				
			// Push back references
            if (count($matches) > 1)
				$this->back_references = array_merge($this->back_references, array_slice($matches, 1));
		}
		
		// Pre-process chunk[X] options
		$chunk_checks = array();
		foreach($options as $key => $value)
		{	
			if (preg_match('/chunk\[([-]?[\d]+)\]/', $key, $matches) == 1)
				$chunk_checks[$matches[1]] = $value;
		}
		
		// Post-process chunk[X] options
		if (count($chunk_checks) > 0)
		{
			// Explode path
			$chunks = $subject_path;
			$chunks = explode($options['delimiter'], $chunks);
		
			foreach($chunk_checks as $chunk_index => $regex)
			{	// Check out of boundries
				if (($chunk_index >= 0) && ($chunk_index >= count($chunks)))
					return false;
				if (($chunk_index < 0) && ((abs($chunk_index)-1) >= count($chunks)))
					return false;
					
				// Normalize oposite
				if ($chunk_index < 0) $chunk_index = count($chunks) + $chunk_index;
				
				// Back-references
				if (preg_match($regex, $chunks[$chunk_index], $matches) != 1)
					return false;
				
				// Push back references
				$matches = preg_matches_remove_unamed($matches);
				if (count($matches) > 1)
					$this->back_references = array_merge($this->back_references, array_slice($matches, 1));
			}
		}
		return true;
	}
};
Stupid_Condition_UrlPath::register();
?>
