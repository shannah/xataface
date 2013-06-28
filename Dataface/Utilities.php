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
 * Contains Utility functions.
 */
class Dataface_Utilities {
	
	/**
	 * Groups an array of records by a field.
	 *
	 * @param string $fieldname The name of the field on which to group the records.
	 * @param array $records The array of records.  This may be an array of associative
	 *					arrays, an array of Dataface_Record objects, or an array
	 *					of Dataface_RelatedRecord objects.
	 *
	 * @return Array( [Grouped Field value] -> Array(Records) )
	 */
	public static function groupBy($fieldname, $records, $order=array(), $titles=array()){
		import( 'PEAR.php');
		if (!is_array($records) ){
			return PEAR::raiseError("In Dataface_Utilities::groupBy() expected 2nd parameter to be an array, but received "+$records);
		}
		
		
		$out = array();
		$unordered = array();
		$ordered = array();
		
		foreach ($order as $orderKey){
			$ordered[$orderKey] = array();
		}
		
		
		foreach (array_keys($records) as $i){
			if ( is_a($records[$i], 'Dataface_Record') || is_a($records[$i], 'Dataface_RelatedRecord') ){
				$key = $records[$i]->qq($fieldname);
				
			} else if (is_array($records[$i]) ) {
				$key = $records[$i][$fieldname];
			} else {
				return PEAR::raiseError("In Dataface_Utilities::groupBy() each of the elements in the list of records should be either an array or a Dataface_Record object (or a Dataface_RelatedRecord object), but received: ".$records[$i]);
			}
			if ( !$key ){
				continue;
			}
			if ( isset( $ordered[$key] ) ) $ordered[$key][] =& $records[$i];
			else {
				if ( !isset($unordered[$key]) ){
					$unordered[$key] = array();
				}
			
				$unordered[$key][] =& $records[$i];
			}
		}
		$out = array_merge($ordered, $unordered);
		$out2 = array();
		foreach (array_keys($out) as $key){
			if ( isset($titles[$key]) ){
				$out2[$titles[$key]] =& $out[$key];
			} else {
				$out2[$key] =& $out[$key];
			}
		}
		
		return $out2;
	
	}
	
	
	/**
	 * Converts an query array into a string with a bunch of HTML hidden fields
	 * to be placed on an HTML form.
	 *
	 * <p>E.g.:
	 *	<code>
	 *	$html = Dataface_Utilities::query2Html(
	 *		array('FirstName'=>'Steve', 'LastName'=>'Hannah', 
	 *		'Address'=>array('Country'=>'Canada', 'Province'=>'BC')
	 *		)
	 *	);
	 *	
	 *	echo $html;
	 *	</code>
	 * Would output:
	 * <code>
	 * <input type="hidden" name="FirstName" value="Steve" />
	 * <input type="hidden" name="LastName" value="Hannah" />
	 * <input type="hidden" name="Address[Country]" value="Canada" />
	 * <input type="hidden" name="Address[Province]" value="BC" />
	 * </code>
	 *
	 * @param array $query Array of request query parameters.  e.g. $_REQUEST
	 * @param array $keyFilter Optional array of keys that should be omitted.
	 *
	 * @return string
	 */
	public static function query2html($query, $keyFilter=array()){
		foreach ( $keyFilter as $bad ){
			if ( isset($query[$bad]) ) unset($query[$bad]);
		}
		$qt = array();
		//call_user_func(array(__CLASS__, 'flattenQuery'), $query, $qt);
		Dataface_Utilities::flattenQuery($query, $qt);
		
		ob_start();
		foreach ($qt as $key=>$value){
			echo "<input type=\"hidden\" name=\"$key\" value=\"$value\" />\n";
		}	
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}
	
	/**
	 * Flattens the variables of a query so that they can be written safely
	 * to an HTML form.
	 *
	 * <p>E.g.:
	 * <code> [
	 *			'userid'=>10,
	 *			'FirstName'=>'Steve',
	 *			'LastName'=>'Hannah',
	 *			'Address'=> [
	 *				'Country'=>'Canada',
	 *				'Province'=>'BC'
	 *			]
	 *		  ]
	 * </code>
	 * would be converted to:
	 * <code>
	 *			[
	 *				'userid'=>10,
	 *				'FirstName'=>'Steve',
	 *				'LastName'=>'Hannah',
	 *				'Address[Country]'=>'Canada',
	 *				'Address[Province]'=>'BC'
	 *			]
	 *	</code>
	 *
	 * @param array $in The input query array.
	 * @param array &$out The output query array.
	 * @param $path $path An array representing the path of the current element
	 *		since this method uses itself recursively.
	 *
	 * @return void
	 */
	public static function flattenQuery($in,&$out, $path=array()){
		$origPath = $path;
		if ( !empty($path) ){
			$prefix = array_shift($path);
			if ( !empty($path) ){
				$prefix .= '['.implode('][', $path).']';
			}
		} else {
			$prefix = '';
		}
		$hasprefix = !empty($prefix);
		foreach ($in as $key=>$value){
			//if ( substr($key,0,2) == '--' ) continue;
			if ( is_array($value) ){
				$origPath[] = $key;
				Dataface_Utilities::flattenQuery($value, $out, $origPath);
			} else {
				if ( $hasprefix ){
					$out[$prefix.'['.$key.']'] = $value;
				} else {
					$out[$key] = $value;
				}
			}
		}
	}
	
	
	/**
	 * Fires an event on the delegate classes.  In effect, this calls a method
	 * called $name on the current table's delegate class if it exists - otherwise
	 * it will call the function on the application's delegate class - if it exists.
	 * @param string $name The name of the event.  Must be valid method name in
	 *		a delegate class.
	 * @return void
	 */
	public static function fireEvent($name, $params=array()){
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		if ( isset($query['-table']) ){
			$table =& Dataface_Table::loadTable($query['-table']);
			$delegate =& $table->getDelegate();
			if ( isset($delegate) && method_exists($delegate, $name) ){
				//$res = call_user_func(array(&$delegate, $name));
				$res = $delegate->$name($params);
				return $res;
			}
		}
		
		$appDelegate =& $app->getDelegate();
		if ( isset($appDelegate) && method_exists($appDelegate, $name) ){
			//$res = call_user_func(array(&$appDelegate, $name));
			$res = $appDelegate->$name($params);
			return $res;
		}
	}
	
	
	/**
	 * Quotes identifiers for use in SQL queries.
	 */
	public static function quoteIdent($ident){
		return str_replace('`','', $ident);
	}
	
	
	/**
	 * Redirects the user to a new page based on the query attributes:
	 * --redirect, --error_page, and --success_page.
	 * @param string $msg The message to be sent as the --msg attribute.
	 * @param PEAR_Error The error object in case we should be sending back an error code.
	 *
	 * This also respects the --prefix GET parameter that will be prepended to the
	 * names of the --msg, --error_code, and --error_message variables that are
	 * passed to the redirected pages.
	 */
	public static function redirect($msg=null, $error=null){
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$prefix = @$query['--prefix'];
		if ( isset($query['--error_page']) and isset($error) ){
			$page = $query['--error_page'];
		} else if ( isset($query['--success_page']) and !isset($error) ){
			$page = $query['--success_page'];
		} else if ( isset($query['--redirect']) ){
			$page = $query['--redirect'];
		}
		
		if ( !isset($page) ) return;
		if ( isset($error) ){
			if ( strpos($page,'?') === false ) $page .= '?';
			else $page .= '&';
				
			$page .= $prefix.'--error_message='.urlencode($res->getMessage()).'&'.$prefix.'--error_code='.urlencode($res->getCode());
			
		}
			
		if ( isset($msg) ){
			if ( strpos($page,'?') === false ) $page .= '?';
			$page .= $prefix.'--msg='.urlencode($msg);
		}
		
		$app->redirect("$page");
	}

}
 
