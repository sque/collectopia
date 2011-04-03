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


//! Simple profiling suite
/**
 * To use profiling suite, you add checkpoints in your code
 * with unique names and at the end you can calculate the elapsed time between
 * passes at checkpoints.
 * @remarks This class acts as a namespace , it does not provide an instantiation interface,
 * it provides only static interface.
 * @author sque
 *
 */
class Profile
{
	//! Prohibit the creation of Profile objects
	final private function __construct(){}

	//! The list with all passes through checkpoints
	/**
	* The order of the list is, first older and last newer.
	*/
	private static $passes = array();

	//! Declare a checkpoint in your code
	/**
	* By adding checkpoint() at any position in your code, you
	* declare a checkpoint, and everytime checkpoint() is executed various
	* profiling statistics are saved for later examination. You can also pass
	* a custom variable at function to examine its value later on your code.
	* @param $name The name of the checkpoint, this must be a unique name to identify
	* the checkpoint in your code. This name will be used from other functions to
	* retrieve statistical data from the passes through this checkpoint.
	* @param $variable_data Any data that you may want to store when the code
	* passes through this checkpoint. Data will be saved for each pass so you can
	* use it to save the counter of a loop or anything else. If you need to save more
	* data at each pass, you can use an array of data.
	* @return This function always returns NULL.
	*
	* @code
	* // Example of adding checkpoints in a function
	* function my_slow_function($big_number)
	* {	Profile::checkpoint('my_slow_function start', $big_number);
	*
	* 		for($i = 0; $i < $big_number;$i++)
	* 		{	Profile::checkpoint('my_slow_function iter', $i)
	* 			$d = ($i * $i) + ($d/$i);
	* 		}
	*
	* 		Profile::checkpoint('my_slow_function end', array($big_number, $d));
	* 		return $d;
	* }
	* @endcode
	*/
	public static function checkpoint($name, $variable_data = NULL)
	{	self::$passes[] = array('checkpoint' => $name,
			'time_real' =>  microtime(true),
			'mem_total' => memory_get_usage(), 
			'data' => $variable_data); 
	}

	//! Calculate elapsed time between two checkpoints.
	/**
	* It will return the time elapsed between runtime passes through those checkpoints.
	* If those two checkpoints were passed multiple times it will return an array
	* of elapsed time, in order of that they were actually passed.
	* @param $from The name of the first checkpoint.
	* @param $to The name of the second checkpoint.
	* @param $index
	* 	- -1 Will return an array with all elapsed times.
	* 	- >= 0 Will return the item of this zero-based indexed array.
	* 	.
	*
	* @return An array of floating point numbers with the elapsed times between those
	* 	two checkpoints.
	*
	* @b Example
	* @code
	* for($i = 0;$i < 5;$i++)
	* {
	* 		Profile::checkpoint('loop-start', $a);
	* 		sleep(1.31);
	* 		Profile::checkpoint('loop-end');
	* }
	* @endcode
	*
	* @b Output
	* @code
	* array(5) {
	*   [0]=>
	*   float(1.00015091896)
	*   [1]=>
	*   float(1.00019788742)
	*   [2]=>
	*   float(1.00021100044)
	*   [3]=>
	*   float(1.00020098686)
	*   [4]=>
	*   float(1.00019907951)
	* }
	* @endcode
	*/
	public static function elapsed($from, $to, $index = -1)
	{	$result = array();

	    $searching = $from;
	    $from_time = 0;
	    foreach(self::$passes as $pass)
	    {	if ($pass['checkpoint'] != $searching)
	        continue;

	        if ($searching == $from)
	        {	$from_time = $pass['time_real'];
	            $searching = $to;
	        }
	        else
	        {	$result[] = ($pass['time_real'] - $from_time);
	            $searching = $from;
	        }
	    }
	    if ($index === -1)
	        return $result;

	    return $result[$index];
	}

	//! Get statistics of runtime passes through checkpoints.
	/**
	* It will return an array with passes through checkpoints till now. The order of the entries
	* is in the order that they were actually passed, first is the oldest. You can filter the passes
	* by checkpoint names usign $show_only and $exclude filter control arguments.
	* @param $show_only An array with checkpoint names that will be included (the rest will be excluded).
	* 		NULL will return all of them.
	* @param $exclude An array with checkpoint names that will be excluded from the final list. If
	* 		this is combined with $show_only then $exclude filter will be applied on the list produced
	* 		by $show_only filter.
	* @return An array with passes through checkpoints. Each item in this list is an associative array
	* 		with statistical info about each pass.
	*  	- @b checkpoint : The name of the checkpoint that runtime passed through.
	*  	- @b time_real  : The real time that runtime passed through (Unix timestamp in float-point seconds)
	*  	- @b time_delta : The time elapsed between this and the previous pass occured in any checkpoint.
	*  	- @b mem_total  : The current usage of memory php application at pass time.
	*  	- @b mem_delta	: The delta of memory usage between this and any previous pass occured in any checkpoint.
	*  	.
	* @see csv_dump()
	* @see html_dump()
	*
	* <b> Simple usage example </b>
	* @code
	* function myadd($a, $b)
	* {	
	*       Profile::checkpoint('func_add', array($a, $b));
	* 		$ret = $a + $b;
	*		Profile::checkpoint('func_end', $ret);
	* }
	*
	* echo myadd(1,2);
	* var_dump(Profile::passes());
	* @endcode
	*
	* @b Output
	* @code
	* 3
	* array(2) {
	*   [0]=>
	*   array(8) {
	*     ["checkpoint"]=>
	*     string(8) "func_add"
	*     ["time_real"]=>
	*     float(1258569169.49)
	*     ["mem_total"]=>
	*     int(542404)
	*     ["data"]=>
	*     array(2) {
	*       [0]=>
	*       int(1)
	*       [1]=>
	*       int(2)
	*     }
	*     ["time_prog"]=>
	*     float(0.0142240524292)
	*     ["time_delta"]=>
	*     float(0)
	*     ["mem_delta"]=>
	*     int(542404)
	*     ["time_delta_peak"]=>
	*     int(0)
	*   }
	*   [1]=>
	*   array(8) {
	*     ["checkpoint"]=>
	*     string(8) "func_end"
	*     ["time_real"]=>
	*     float(1258569169.49)
	*     ["mem_total"]=>
	*     int(543164)
	*     ["data"]=>
	*     int(3)
	*     ["time_prog"]=>
	*     float(0.0142409801483)
	*     ["time_delta"]=>
	*     float(1.69277191162E-5)
	*     ["mem_delta"]=>
	*     int(760)
	*     ["time_delta_peak"]=>
	*     float(1)
	*   }
	* }
	* @endcode
	*/
	public static function passes($show_only = NULL, $exclude = NULL)
	{
        // Calculate show_only filter
        if (is_array($show_only))
        {	$filtered = array();
            foreach(self::$passes as $pass)
                if (in_array($pass['checkpoint'], $show_only))
                    $filtered[]= $pass;
                    $passes = $filtered;
        }
        else
            $passes = self::$passes;

        // Calculate exclude filter
        if (is_array($exclude))
        {	$filtered = array();
            foreach($passes as $pass)
                if (in_array($pass['checkpoint'], $exclude))
                    $filtered[]= $pass;
                    $passes = $filtered;
        }

        if (! empty($passes))
        {	$start_time = self::$passes[0]['time_real'];
            $previous_time = $passes[0]['time_real'];
            $previous_memory = $passes[0]['mem_total'];
            $average = 0;
            $maximum = 0;
        }
        else
            return;
			
		// Calculate time delta and memory delta
		$previous_mem = 0;
		foreach($passes as & $pass)
		{	$pass['time_prog'] =  $pass['time_real'] - $start_time;
		    $pass['time_delta'] =  $pass['time_real'] - $previous_time;
		    $previous_time = $pass['time_real'];
		    $average += $pass['time_delta'];
		    if ($pass['time_delta'] > $maximum)
		        $maximum = $pass['time_delta'];

		    $pass['mem_delta'] = $pass['mem_total'] - $previous_mem;
		    $previous_mem = $pass['mem_total'];
		}
		unset($pass);
		$average /= count($passes);

		// Calculate statistics
		foreach($passes as & $pass)
		{	$pass['time_delta_peak'] =  $pass['time_delta'] > $average?($pass['time_delta'] - $average) / ($maximum - $average):0;
		}
		unset($pass);
		return $passes;
	}

	//! HTML dump of all measurments
	/**
	* This function execute passes() and displays results in an HTML formated table.
	* @param $show_only An array with checkpoint names that will be included (the rest will be excluded).
	* 		NULL will return all of them.
	* @param $exclude An array with checkpoint names that will be excluded from the final list. If
	* 		this is combined with $show_only then $exclude filter will be applied on the list produced
	* 		by $show_only filter.
	* @return NULL
	* @see passes()
	* @see csv_dump()
	*/
	public static function html_dump($show_only = NULL, $exclude = NULL)
	{	$passes = self::passes($show_only, $exclude);

	    // Render html table
	    echo '<table class="profile_dump_table" style="color: black; background: white;" border="1"><tr><th>Checkpoint <th>Variable <th>Program Time <th> Time Delta <th> Total Memory <th> Memory Delta' . "\n";
	    foreach($passes as $pass)
	    {	if ($pass['time_delta_peak'] > 0.9)
	            $color = '#ff0000';
	        else if ($pass['time_delta_peak'] > 0.8)
	            $color = '#dd3300';
	        else if ($pass['time_delta_peak'] > 0.6)
	            $color = '#aa6600';
	        else if ($pass['time_delta_peak'] > 0.4)
	            $color = '#999900';
	        else if ($pass['time_delta_peak'] > 0.2)
	            $color = '#999999';
	        else
	            $color = '#000000';

	        printf("<tr><td>%s<td>&nbsp;%s<td><pre>%12.5f</pre><td style=\"color: %s;\"><pre>%12.5f</pre><td><pre>%9s B</pre><td<pre>%9s B</pre>\n",
	            $pass['checkpoint'],
	            print_r($pass['data'], true),
                $pass['time_prog'],
                $color,
                $pass['time_delta'],
                $pass['mem_total'],
                $pass['mem_delta']
	        );
	    }
	    echo "</table>\n";
	}

	//! Dump all measurements in comma-seperated values (CSV) format
	/**
	* This function execute passes() and displays results in CSV format.
	* @param $delimiter The delimiter to use for CSV format.
	* @param $show_only An array with checkpoint names that will be included (the rest will be excluded).
	* 		NULL will return all of them.
	* @param $exclude An array with checkpoint names that will be excluded from the final list. If
	* 		this is combined with $show_only then $exclude filter will be applied on the list produced
	* 		by $show_only filter.
	* @return NULL
	* @see passes()
	* @see html_dump()
	*/
	public static function csv_dump($delimiter = ",", $show_only = NULL, $exclude = NULL)
	{	$passes = self::passes($show_only, $exclude);

	    // Render dump
	    echo 'Checkpoint' . $delimiter . 'Variable' . $delimiter . 'Program Time ' . $delimiter . ' Time Delta'
	    . $delimiter . ' Program Memory' . $delimiter . ' Memory Delta' ."\n";
	    foreach($passes as $pass)
	    {	printf("%s%s%s%s%5.5f%s%5.5f%s%s B%s%s B\n",
	        $pass['checkpoint'],
	        $delimiter,
	        print_r($pass['data'], true),
	        $delimiter,
	        $pass['time_prog'],
	        $delimiter,
	        $pass['time_delta'],
	        $delimiter,
	        $pass['mem_total'],
	        $delimiter,
	        $pass['mem_delta']
	        );
	    }
	    echo "\n";
	}
}
?>
