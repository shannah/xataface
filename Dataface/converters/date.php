<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp (shannah@sfu.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */
/**
 * File: Dataface/converters/date.php
 * Author: Steve Hannah <shannah@sfu.ca>
 * Created: October 19, 2005
 * Description:
 * -------------
 * Utility functions for converting dates from one format to another.
 */
class Dataface_converters_date {

	/**
	 * Converts a quickform date field value as returned by $element->getValue()
	 * to a unix timestamp.
	 */
	static function qf2UnixTimestamp($value){
		$date = Dataface_converters_date::qf2Table($value);
		$timestamp = strtotime( Dataface_converters_date::datetime_to_string($date) );
		return $timestamp;
	}
	
	/**
	 * Specifically parses a value from a quickform date element to be in the format
	 * accepted by a Dataface_Table object date field.
	 */
	static function qf2Table($value){
		$out = array();
		foreach ($value as $key=>$val){
			if ( is_array($val) ) $out[$key] = $val[0];
			else $out[$key] = $val;
		}
		return Dataface_converters_date::parseDate($out);
	}

	
	/**
	 * Converts a quickform date field value as returned by $element->getValue()
	 * to a date array that can be stored in a Dataface_Table object.
	 */
	static function parseDate($value){
		if ( !isset($value) || !$value ) return null;
		if ( $value == '0000-00-00' || $value == '0000-00-00 00:00:00' ) return null;
		if ( is_array($value) and (isset( $value['year']) or isset($value['hours'])) ) return $value;
			// if it is already in the correct format, we don't need to parse it.
		
		if ( Dataface_converters_date::isTimeStamp($value) ) {
			$date = array();
			if ( strlen($value)>=4) $date['Y'] = substr($value,0,4);
			if ( strlen($value)>=6) $date['m'] = substr($value,4,2);
			if ( strlen($value)>=8) $date['d'] = substr($value,6,2);
			if ( strlen($value)>=10) $date['H'] = substr($value,8,2);
			if ( strlen($value)>=12) $date['i'] = substr($value,10,2);
			if ( strlen($value)>=14) $date['s'] = substr($value,12,2);
		}
		else if ( !is_array($value) ){
			if ( function_exists('date_parse') ){
				$out = date_parse($value);
				$out['hours'] = $out['hour'];
				unset($out['hour']);
				$out['minutes'] = $out['minute'];
				unset($out['minute']);
				$out['seconds'] = $out['second'];
				unset($out['second']);
				return $out;
			} else if ( Dataface_converters_date::inRange($value) ){
				// strtotime cannot seem to calculate the time properly on this
				// so we will manually parse it;
				if ( preg_match('/^(\d{4})(-(\d{2}))?(-(\d{2}))?( (\d{2}):(\d{2})(:(\d{2}))?)?$/', $value, $matches)){
					$date = array();
					$date['year'] = $matches[1];
					$date['month'] = @$matches[3];
					$date['day'] = @$matches[5];
					$date['hours'] = @$matches[7];
					$date['minutes'] = @$matches[8];
					$date['seconds'] = @$matches[10];
					return $date;
				}
				
			}
			$isNull = true;
			$units = explode(' ','Y m M F d h a A i s');
			$date = array();
			foreach ($units as $unit){
				if ( $value ){
					$date[$unit] = date($unit, strtotime($value));
					$isNull = false;
				} else {
					$date[$unit] = null; //date($unit);
				}
			}
			if ( $isNull ) return null;
			
		
		} else {
			$date = $value;
		}
		$params = array();
		$params['year'] = isset($date['Y']) ? $date['Y'] : date('Y');
		$params['month'] = isset($date['m']) ? $date['m'] : (
									isset($date['M']) ? $date['M'] : (
									isset($date['F']) ? $date['F'] : null//date('m')
									));
		$params['day'] = isset($date['d']) ? $date['d'] : null;//date('d');
		if ( isset($date['H'] ) ) $params['hours'] = $date['H'];
		else if (isset($date['h']) && isset($date['a']) ) $params['hours'] = date('H', strtotime($date['h'].":00".$date['a']));
		else if (isset($date['h']) && isset($date['A']) ) $params['hours'] = date('H', strtotime($date['h'].":00".$date['A']));
		else if (isset($date['h']) ) $params['hours'] = $date['h'];
		else $params['hours'] = null;//date('H');
		
		$params['minutes'] = isset( $date['i'] ) ? $date['i'] : null;//date('i');
		$params['seconds'] = isset( $date['s'] ) ? $date['s'] : null;//date('s');
		
		foreach ( array_keys($params) as $param){
			$params[$param] = intval($params[$param]);
		}
	
		
		return $params;
	
	}
	
	/**
	 * Returns true of the given value is a timestamp.
	 */
	static function isTimeStamp($value){
		if ( is_array($value) ) return false;
		
		return preg_match('/^\d{4,14}$/',$value);
	}
	
	
	/**
	 * Convert a date array to a string.
	 * Can be called statically.
	 */
	static function date_to_string($value){
		if ( !isset($value) or !is_array($value) or count($value) == 0 ) return '';
		return  str_pad($value['year'],4,"0",STR_PAD_LEFT).'-'.
				str_pad($value['month'],2,"0",STR_PAD_LEFT).'-'.
				str_pad($value['day'],2,"0",STR_PAD_LEFT);
	
	}
	
	/**
	 * Converts a datetime array to a string.
	 */
	static function datetime_to_string($value){ 
		if ( !isset($value) or !is_array($value) or count($value) == 0) return '';
		return 	str_pad($value['year'],4,"0",STR_PAD_LEFT).'-'.
				str_pad($value['month'],2,"0",STR_PAD_LEFT).'-'.
				str_pad($value['day'], 2,"0", STR_PAD_LEFT).' '.
				str_pad($value['hours'],2,"0",STR_PAD_LEFT).':'.
				str_pad($value['minutes'],2,"0",STR_PAD_LEFT).':'.
				str_pad($value['seconds'],2,"0", STR_PAD_LEFT);
	}
	
	/**
	 * Converts a time array to a string.
	 */
	static function time_to_string($value){ 
		if ( !isset($value) or !is_array($value) or count($value) == 0 ) return '';
		return 	str_pad($value['hours'],2,"0",STR_PAD_LEFT).':'.
				str_pad($value['minutes'],2,"0", STR_PAD_LEFT).':'.
				str_pad($value['seconds'],2,"0", STR_PAD_LEFT);
	}
	
	/**
	 * Converts a timestamp array to a string.
	 */
	static function timestamp_to_string($value){ 
		return self::datetime_to_string($value);
		/*
		// We removed this because timestamps should be displayed like normal dates
		// http://bugs.weblite.ca/view.php?id=1038
		if ( !isset($value) or !is_array($value) or count($value) == 0) return '';
		return 	str_pad($value['year'],4,"0", STR_PAD_LEFT).
				str_pad($value['month'],2,"0", STR_PAD_LEFT).
				str_pad($value['day'],2,"0",STR_PAD_LEFT).
				str_pad($value['hours'],2,"0", STR_PAD_LEFT).
				str_pad($value['minutes'],2,"0", STR_PAD_LEFT).
				str_pad($value['seconds'],2,"0", STR_PAD_LEFT);
		*/
	}
	
	static function inRange($date){
		if ( version_compare(PHP_VERSION, '5.1', '<') ){
			return (strtotime($date) == -1);
		} else {
			return (strtotime($date) === false);
		}
	}
	
	


}
