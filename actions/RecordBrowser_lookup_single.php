<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2009 Web Lite Solutions Corp (shannah@sfu.ca)
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
 * Handler for the RecordBrowser_lookup_single action.  This action will display the
 * title of a single record given its id and table.  The id can be specified as a 
 * scalar value (if the table's primary key is a single field) a urlencoded  string
 * of keys and their associated values for tables with multiple fields in their 
 * primary key, or as a Xataface record id.
 *
 * @param $_GET['-table'] The name of the table
 * @param $_GET['id'] The id of the record that we want.
 * @return The title of the record.
 *
 * Example usage:
 *
 * index.php?-action=RecordBrowser_lookup_single&-table=people&-id=10
 *
 * This was created specifically to be used by the RecordBrowser jquery
 * component that is part of the lookup widget.
 *
 * Related files:
 * Dataface/FormTool/lookup.php
 * HTML/QuickForm/lookup.php
 * js/RecordBrowser
 *
 *
 * @created July 15, 2009
 * @author Steve Hannah <shannah@sfu.ca>
 */
class dataface_actions_RecordBrowser_lookup_single {
	function handle(&$params){
		session_write_close();
		header('Connection: close');
		$app =& Dataface_Application::getInstance();
		
		$query =& $app->getQuery();
		$table = $query['-table'];
		$ids = $query['-id'];
		$rec = null;
		if ( !is_array($ids) ) $ids = array($ids);
		$out = array();
		foreach ($ids as $id){
			if ( preg_match('/^'.preg_quote($table,'/').'\?/', $id) ){
				// This is a record id
				$rec = df_get_record_by_id($id);
				
			} else if ( strpos($id, '=') !== false ){
				parse_str($id, $q);
				$rec = df_get_record($table, $q);
			} else {
				$keys = array_keys(Dataface_Table::loadTable($table)->keys());
				$q = array($keys[0] =>'='. $id);
				$rec = df_get_record($table, $q);
				
			}
			
			if ( $rec ){
				header('Content-type: text/html; charset='.$app->_conf['oe']);
				if ( $rec->checkPermission('view') ){
					switch (strval(@$query['-text'])){
						case '':
						case '__title__':
					
							$out[] = $rec->getTitle();
							break;
						case '__json__':
							//header('Content-type: text/json; charset='.$app->_conf['oe']);
							$out[] = array_merge($rec->strvals(), array('__id__'=>$rec->getId()));
							break;
						default:
							$out[] = $rec->display($query['-text']);
							break;
					}
				} else {
					return Dataface_Error::permissionDenied('You require view permission to access this record');
				}
				
			}
		}
		
		if ( count($out) == 0 ) $out[] = "";
		
		if ( count($out) < 2 and !is_array($query['-id']) and @$query['-return-type'] != 'array' ){
			if ( @$query['-text'] == '__json__' ){
				header("Content-type: application/json; charset=".$app->_conf['oe']);
				echo json_encode($out[0]);
			} else {
				echo $out[0];
			}
		} else {
			header("Content-type: application/json; charset=".$app->_conf['oe']);
			echo json_encode($out);
		}
		exit;
	}
}
