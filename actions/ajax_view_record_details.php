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
class dataface_actions_ajax_view_record_details {
	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		
		$query =& $app->getQuery();
		$record =& $app->getRecord();
		
		if ( !$record ) return PEAR::raiseError("No record could be found that matches the query.", DATAFACE_E_ERROR);
		if ( PEAR::isError($record) ) return $record;
		
		$context = array('record'=>&$record);
		
		$t =& $record->_table;
		$fields = array();
		foreach ( $t->fields(false,true) as $field){
			if ( $record->checkPermission('view', array('field'=>$field['name']))){
				$fields[$field['name']] = $field;
			}
		}
		$numfields = count($fields);
		$pts = 0;
		$ppf = array();
		foreach (array_keys($fields) as $field){
			if ( $t->isText($field) ){
				$pts+=5;
				$ppf[$field] = $pts;
			} else {
				$pts++;
				$ppf[$field] = $pts;
			}
		}
		
		$firstField = null;
		$threshold = floatval(floatval($pts)/floatval(2));
		foreach ( array_keys($fields)  as $field){
			if ( $ppf[$field] >= $threshold ){
				$firstField = $field;
				break;
			}
		}
		
		$context['first_field_second_col'] = $firstField;
		$context['table'] =& $t;
		$context['fields'] =& $fields;
		header('Content-type: text/html; charset='.$app->_conf['oe']);
		df_display($context, 'Dataface_AjaxRecordDetails.html');
		
	}

}
?>
