<?php
/*******************************************************************************
 * File:	HTML/QuickForm/calendar.php
 * Author:	Steve Hannah <shannah@sfu.ca>
 * Created: March 10, 2006
 * Description:
 * 	HMTL Quickform calendar widget.  This is essentially a wrapper to use the 
 * DynArch jscalendar widget - a really cool calendar widget.
 *
 ******************************************************************************/


require_once 'HTML/QuickForm/select.php';


/**
 * HTML Class for a select list with times at a specified interval.
 * 
 * @author       Steve Hannah <shannah@sfu.ca>
 * @version      0.1.0
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_time extends HTML_QuickForm_select {

	function HTML_QuickForm_time($elementName=null, $elementLabel=null, $attributes=null, $properties=array())
    {
    	if ( isset($elementName) ){
			$start = (isset($properties['starttime']) ? $properties['starttime'] : '08:00');
			$end = (isset($properties['endtime']) ? $properties['endtime'] : '18:00');
			$interval = (isset($properties['interval']) ? $properties['interval'] : '30' );
			$format = (isset($properties['format']) ? $properties['format'] : 'H:i');
			
			
			
			$properties['starttime'] = $start;
			$properties['endtime'] = $end;
			$properties['interval'] = $interval;
			$properties['format'] = $format;
			
			if ( intval($properties['interval']) <= 0 ) $properties['interval'] = 30;
			
			$starttime = strtotime($properties['starttime']);
			$endtime = strtotime($properties['endtime']);
			$interval_seconds = intval($properties['interval'])*60;
			$opts = array(''=>'---');
			$j=0;
			for ( $i=$starttime; $i<=$endtime; $i+=$interval_seconds){
				$opts[date('H:i:s', $i)] = date($properties['format'], $i);
			
				$j++;
			}
			
		
			parent::HTML_QuickForm_select($elementName, $elementLabel, $opts, $attributes); 
		}
		
    } //end constructor
    
    
    function getValue(){
    	$val = parent::getValue();
    	if ( is_array($val) ){
    		return $val[0];
    	}
    	return $val;
    }
    
	
	

}

