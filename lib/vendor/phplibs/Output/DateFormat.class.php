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

require_once dirname(__FILE__) . '/html.lib.php';

//! Date textual formater class
class Output_DateFormat
{
    //! The date object that will be formated
    private $date_obj;

    //! Create format object
    public function __construct($date_obj)
    {   
        $this->date_obj = $date_obj;
    }

    //! Human representation of time span.
    /**
    * Humans usually prefer the time in lectical representation,
    * e.g. '10 mins ago', 'after an hour'. This function will
    * return a human diff of the formated time and a relative one.
    * @param $rel_time The time to calculate the difference to.
    * @param $html Return html formated time.
    * @return A string with lectical time representation that
    *  depending on $html may be encapsulated in a html \<span\> tag.
    */
    function human_diff($rel_time = null, $html = true)
    {	
        if ($rel_time === null)
            $rel_time = date_create();
        $full_date = $this->date_obj->format('D, j M, Y \a\t H:i:s');
        $sec_diff = abs($this->date_obj->format('U') - $rel_time->format('U'));

        $span = tag('span', array('title' =>  $full_date));
    
        $ret = '';
        if ($sec_diff <= 60)	// Same minute
            $ret .= 'some moments ago';
        else if ($sec_diff <= 3600)	// Same hour
            $ret .= floor($sec_diff / 60) . ' minutes ago';
        else if ($sec_diff <= 86400)	// Same day
            $ret .= floor($sec_diff / 3600) . ' hours ago';
        else /*if ($sec_diff <= (86400 * 14))	// Same last 2 weeks
        	$ret .= $dt->format('M j') . '(' . floor($sec_diff / 86400) . ' days ago)';*/
        {	
            $cur_date = getdate();
            $that_date = getdate($this->date_obj->format('U'));
    
            if ($cur_date['year'] == $that_date['year'])
                $ret .= $this->date_obj->format('M d, H:i');
            else
                $ret .= $this->date_obj->format('d/m/Y');
        }
    
        if ($html)
            return $span->append($ret);
        else
            return $ret;
    }

    //! Return as less as possible details about time
    /**
    * This smart format will omit details that are the same with
    * presence. E.g. if you are showing a date in the same year,
    * the year will be ommited, the same will happen for month and day.
    */
    function smart_details()
    {	
        $currentTime = time();
        $currentTimeDay = date('d m Y', $currentTime);
        $ndateDay = $this->date_obj->format('d m Y');
        if ($currentTimeDay == $ndateDay)
            return $this->date_obj->format('h:i a');
        if (date('Y', $currentTime) == $this->date_obj->format('Y'))
            return substr($this->date_obj->format('F'), 0, 3) . $this->date_obj->format(' d,  h:i a');

        return substr($this->date_obj->format('F'), 0, 3) . $this->date_obj->format(' d, Y');
    }
}
?>
