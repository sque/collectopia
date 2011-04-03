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


require_once dirname(__FILE__) . '/../html.lib.php';

//! Create an interactive grid of data
/** 
	Grid is an HTML element that can read data from a source and display
	them on a grid control. It also supports custom actions for each data.

	Special functions are functions that can be declared at childern class
	and are called by grid at special occusions to alter data or interact with grid.
	Currently the following special functions are supported:
		- @b on_custom_data([column-id], [row-id], [data-record]) Called for each cell that belongs to
			a column with option 'customdata' set true. The function must return the string with data
			that will displayed in cell.
			@note The returned data are NOT ESCAPED before displaying them !! You must escape your data 
			using esc_html() if they don't include any html code.
		- @b on_mangle_data([column-id], [row-id], [data]) Called for each cell that belong to a column
			with option 'mangle' set true. This function is used for further data mangling. It is called
			after feeding data from data array and just before displaying data.
			@note It will not be called if data are fed using the @b on_data_request()
			@note The returned data are NOT ESCAPED before displaying them !! You must escape your data 
			using esc_html() if they don't include any html code.
		- @b on_click([column-id], [row-id], [data-record]) If this function is declared, it will be called
			when a user clicks on a cell that belongs to column which has the option 'clickable' set true.			
		- @b on_header_click([column-id]) Trigered when user clicks on a header.
 */
class Output_HTML_Grid
{
	//! All the columns of the grid
	public $columns;
	
	//! Data of the grid
	public $data;
	
	//! Options of the grid
	public $options;
	
	//! Grid id
	public $grid_id;
	
	//! Last auto grid id
	private static $last_autoid = 0;
	
	//! Constructor of a grid
	/** 
		@param $columns An associative array with a description of each column. Each item of this array
			must have another array with the options of the column. This array must be associative and the options
			are passed as key => value
			- caption: [Default=(column-id)] The caption of this column
			- htmlattribs: [Default=array()] Attributes of HTML TD element for all rows.
			- datakey: [Default=(column-id)] If the feed is done by data parameter then key of the records associative array/object
				that data will be read from. This can work only if customdata option is false.
			- customdata: [Default=false] Don't write data to cells of this column from $data but execute special
				function on_custom_data().
			- mangle: [Default=false] Call on_mangle_data() with data and display the result of this function.
			- clickable: [Default=false] If set true the on_click will be executed when a user clicks any cell of this
				column.
			- headerclickable: [Default=false] Make this header clickable and when they are clicked, the on_header_click() special
				function is executed.
		@param $options An associative array with all options of the grid.
			- css: [Default=array('ui-grid')] array of extra class names
			- caption: [Default=''] The caption of the table.
			- httpmethod: [Default='post'] The method to use when user interacts with grid.
			- headerpos: [Default='top'] The position that headers will be rendered, possible values are
				@b 'top', @b 'bottom', @b 'both' or @b 'none'.
			- pagecontrolpos: [Default='top'] The position that page controls will be rendered, possible values are
				@b 'top', @b 'bottom', @b 'both' or @b 'none'.
			- maxperpage: [Default=false] If this option is set to false, grid will not enter in non-paged mode.
				If this value is bigger than 0, then each page will have the size set by this value.
			- startrow: [Default=1] You can change the starting page of a grid, by setting a different value.
				Make sure that 'maxperpage' is set to non-zero value.
		@param $data = null An array with data for each row. Each item of the array can be another array with all info of records
				or an object with properties.
	*/
	public function __construct($columns = NULL, $options = NULL, $data = null)
	{
		if ($columns !== NULL)
			$this->columns = $columns;
		if ($options !== NULL)
		    $this->options = $options;
		$this->data = $data;
    
        $this->grid_id = 'grid_gen_' . (self::$last_autoid ++);
        
        // Initialize default values for options
        if (!isset($this->options['css']))
            $this->options['css'] = array('ui-grid');
        if (!isset($this->options['caption']))
            $this->options['caption'] = '';
		if (!isset($this->options['httpmethod']))
		    $this->options['httpmethod'] = 'post';
		if (!isset($this->options['headerpos']))
		    $this->options['headerpos'] = 'top';
		if (!isset($this->options['pagecontrolpos']))
		    $this->options['pagecontrolpos'] = 'top';
		if (!isset($this->options['maxperpage']))
		    $this->options['maxperpage'] = false;
		if (!isset($this->options['startrow']))
		    $this->options['startrow'] = 1;

        // Initialize default values for columns
        foreach($this->columns as $k => & $c)
        {   // Data key
            if (!isset($c['datakey']))
                $c['datakey'] = $k;
                
            // Caption
            if (!isset($c['caption']))
                $c['caption'] = $k;
                
            // Mangle
            if (!isset($c['mangle']))
                $c['mangle'] = false;

			// HTML Attribs
            if (!isset($c['htmlattribs']))
                $c['htmlattribs'] = array();

            // Customdata
            if (!isset($c['customdata']))
            	$c['customdata'] = false;
            
            // Observe click event
            if (!isset($c['clickable']))
            	$c['clickable'] = false;

            // Observe click event
            if (!isset($c['headerclickable']))
            	$c['headerclickable'] = false;            	
        }
        unset($c);
        
        // Process post
        $this->process_post();
	}
	
	//! Process the posted data
    private function process_post()
    {   // Check if this grid is posted
        if ((!isset($_POST['submited_grid_id'])) ||
            ($_POST['submited_grid_id'] != $this->grid_id))
        {
            // Call user function when there is no post
            if (method_exists($this, 'on_nopost'))
                $this->on_nopost();
            return false;
        }

        if ($_POST['libgrid_backend_action'] == 'click')
        {
            // Call user function when there is click event
            if (method_exists($this, 'on_click'))
                $this->on_click($_POST['libgrid_backend_colid'], $_POST['libgrid_backend_rowid'], $this->data[$_POST['libgrid_backend_rowid']]);
            return true;
        }
        else if ($_POST['libgrid_backend_action'] == 'headerclick')
        {
            // Call user function when there is no post
            if (method_exists($this, 'on_header_click'))
                $this->on_header_click($_POST['libgrid_backend_colid']);
            return true;
        }
        else if ($_POST['libgrid_backend_action'] == 'changepage')
        {
        	$this->options['startrow'] = (is_numeric($_POST['libgrid_backend_startrow'])?$_POST['libgrid_backend_startrow']:1);
        }
        
    }
    
    // Render column captions only
    private function render_column_captions()
    {	// Render Headers
		$thead = tag('thead', $tr = tag('tr class="ui-headers"'));
		foreach($this->columns as $col_id => $c)
		{	$tr->append($th = tag('th', $c['caption']));
		
			if ($c['headerclickable'])
			{	$th->add_class('ui-clickable');
				$th->attr('onclick',
					'$(\'form#' . $this->grid_id . 	' input[name=libgrid_backend_action]\').val(\'headerclick\'); ' .
					'$(\'form#' . $this->grid_id . ' input[name=libgrid_backend_colid]\').val(\'' . $col_id . '\');' .
					' $(\'form#' . $this->grid_id . '\').submit();'
				);
			}
			foreach($c['htmlattribs'] as $n => $v)
				$th->attr($n, $v);
		}
		return $thead;
    }
    
    // Render page controls
    private function render_page_controls()
    {	if ($this->options['maxperpage'] == false)
    		return;
    	
    	// Calculate view parameters
    	$totalrows = count($this->data);
    	$pagesize = $this->options['maxperpage'];
    	$startrow = $this->options['startrow'];
    	$endrow = (($startrow + $pagesize) <= $totalrows)?$startrow + $pagesize -1 : $totalrows;
    	$nextpage = ($endrow == $totalrows)?false:$endrow + 1;
    	$firstpage = ($startrow == 1)?false:1;
    	if ($startrow == 1)
    		$previouspage = false;
    	else 
	    	$previouspage = ($startrow > $pagesize)?$startrow - $pagesize:1;
	    if (($endrow == $totalrows) || (($totalrows - $startrow) < $pagesize))
			$lastpage = false;
		else
			$lastpage = floor($totalrows / $pagesize) * $pagesize + 1;

		// Render Headers
		tag('table class="ui-grid-page-controls"')->push_parent();
		etag('tr', tag('td html_escape_off align="left"',
			 $startrow . ' &rarr; ' . $endrow . '&nbsp;&nbsp;of&nbsp;&nbsp;' . $totalrows . ' results'),
			 ($td = tag('td width="250px" align="right"'))
		);
		$td->push_parent();
				
		// First button
		$span = etag('span class="ui-grid-first"', 'First');		
		if ($firstpage != false)
			$span->attr('onclick', '$(\'form#' . $this->grid_id . ' input[name=libgrid_backend_action]\').val(\'changepage\'); ' .
				' $(\'form#' . $this->grid_id . ' input[name=libgrid_backend_startrow]\').val(\'1\');' .
				' $(\'form#' . $this->grid_id . '\').submit();')
		    ->add_class('ui-grid-active');
		else
			$span->add_class('ui-grid-inactive');
		etag('span html_escape_off', ' &#8226; ');
		
		$span = etag('span class="ui-grid-previous"', 'Previous');
		if ($previouspage != false)
			$span->attr('onclick', '$(\'form#' . $this->grid_id . ' input[name=libgrid_backend_action]\').val(\'changepage\'); ' .
				' $(\'form#' . $this->grid_id . ' input[name=libgrid_backend_startrow]\').val(\'' . $previouspage .'\');' .
				' $(\'form#' . $this->grid_id . '\').submit();')
		    ->add_class('ui-grid-active');
		else
			$span->add_class('ui-grid-inactive');
		etag('span html_escape_off', ' &#8226; ');
		
		// Next button
		$span = etag('span class="ui-grid-next"', 'Next');
		if ($nextpage != false)
			$span->attr('onclick','$(\'form#' . $this->grid_id . ' input[name=libgrid_backend_action]\').val(\'changepage\'); ' .
				' $(\'form#' . $this->grid_id . ' input[name=libgrid_backend_startrow]\').val(\'' . $nextpage . '\');' .
				' $(\'form#' . $this->grid_id . '\').submit();')
		    ->add_class('ui-grid-active');				
		else
			$span->add_class('ui-grid-inactive');
		etag('span html_escape_off', ' &#8226; ');
				
		$span = etag('span class="ui-grid-last"', 'Last');
		if ($lastpage != false)
			$span->attr('onclick','$(\'form#' . $this->grid_id . 	' input[name=libgrid_backend_action]\').val(\'changepage\'); ' .
				' $(\'form#' . $this->grid_id . ' input[name=libgrid_backend_startrow]\').val(\'' . $lastpage . '\');' .
				' $(\'form#' . $this->grid_id . '\').submit();')
		    ->add_class('ui-grid-active');
		else
			$span->add_class('ui-grid-inactive');		
		
		return Output_HTMLTag::pop_parent(2);
    }
    
	//! Render grid
	public function render()
	{	$div = tag('div')->push_parent();
		foreach($this->options['css'] as $cls)
            $div->add_class($cls);
        
        // Form hidden event
        etag('form', array('action' => '', 'method' => 'post', 'id' => $this->grid_id))->push_parent();
        etag('input type="hidden" name="submited_grid_id"', array('value' => $this->grid_id));
        etag('input type="hidden" name="libgrid_backend_action"');
        etag('input type="hidden" name="libgrid_backend_colid"');
        etag('input type="hidden" name="libgrid_backend_rowid"');
        etag('input type="hidden" name="libgrid_backend_startrow"');
        Output_HTMLTag::pop_parent();        
        
        
		// Caption
		if (!empty($this->options['caption']))
			etag('div class="ui-grid-title"', $this->options['caption']);
		
        // Page controls
        if (($this->options['pagecontrolpos'] == 'top') || ($this->options['pagecontrolpos'] == 'both'))
	        Output_HTMLTag::get_current_parent()->append($this->render_page_controls());

        // Grid list
        etag('table class="ui-grid-list"')->push_parent();
        
        // Render column captions again
        if (($this->options['headerpos'] == 'top') || ($this->options['headerpos'] == 'both'))
    		Output_HTMLTag::get_current_parent()->append($this->render_column_captions());
			
		// Render data
		$count_rows = 0;
		foreach($this->data as $recid => $rec)
		{	$count_rows += 1;
		
			// Pagenation
			if ($this->options['maxperpage'])
			{ 	if (($count_rows -$this->options['startrow']) > $this->options['maxperpage'])
					break;
				if ($count_rows < $this->options['startrow'])
					continue;
			}
				
			// Draw new line
			etag('tr')->push_parent()->add_class(($count_rows % 2)?'ui-even':'');
			
			foreach($this->columns as $col_id => $c)
			{	
				// Get cell data
				if (($c['customdata']) && method_exists($this, 'on_custom_data'))
					$data = $this->on_custom_data($col_id, $recid, $rec);
				else
				{
					if (is_object($rec))
						$cell_data = $rec->$c['datakey'];
					else if (is_array($rec))
						$cell_data = $rec[$c['datakey']];
					else
						$cell_data = (string)$rec;
					if (($c['mangle']) && (method_exists($this, 'on_mangle_data')))
						$data = $this->on_mangle_data($col_id, $recid, $cell_data);
					else
						$data = (empty($cell_data))?'&nbsp;':esc_html($cell_data);
				}

				// Display cell
				$td = etag('td html_escape_off', (string)$data);
				if ($c['clickable'])
				{	$td->add_class('ui-clickable')->
						attr('onclick',
						'$(\'form#' . $this->grid_id . 	' input[name=libgrid_backend_action]\').val(\'click\'); ' .
						'$(\'form#' . $this->grid_id . ' input[name=libgrid_backend_colid]\').val(\'' . $col_id . '\');' .
						' $(\'form#' . $this->grid_id .	' input[name=libgrid_backend_rowid]\').val(\'' . $recid . '\');' .
						' $(\'form#' . $this->grid_id . '\').submit();'
					);
				}
			}
			Output_HTMLTag::pop_parent();	// TR
		}
		
		// Render column captions again
        if (($this->options['headerpos'] == 'bottom') || ($this->options['headerpos'] == 'both'))
			$this->render_column_captions();

		Output_HTMLTag::pop_parent();	// TABLE

        // Page controls
        if (($this->options['pagecontrolpos'] == 'bottom') || ($this->options['pagecontrolpos'] == 'both'))
        	Output_HTMLTag::get_current_parent()->append($this->render_page_controls());

		Output_HTMLTag::pop_parent();	// DIV
		return $div;
	}
}

?>
