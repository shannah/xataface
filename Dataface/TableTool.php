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
 *  File: Dataface/TableTool.php
 *  Author: Steve Hannah <shannah@sfu.ca>
 *  Created: Oct. 20, 2005
 *  Description:
 *  ------------
 *  
 *  A tool provide application level integration to Table objects.  This class allows the
 *  Table object to be decoupled from the rest of the Dataface package.  The Table class
 *  has no knowledge of the Application object and associated Tools.  This tool augments
 *  the capabilties of the Table object by opening access to the Application object and
 *  associated tools.
 *  
 *  The goal is to make the Table object as self contained as possible.  The Table object
 *  currently has access only to the Dataface_converters package and configuration information.
 */

require_once 'Dataface/Application.php';
require_once 'Dataface/Table.php';

class Dataface_TableTool {
	
	var $_table;
	var $_app;
	
	function Dataface_TableTool($tablename){
		$this->_table =& Dataface_Table::loadTable($tablename);
		$this->_app =& Dataface_Application::getInstance();
	}
	
	public static function &getInstance($tablename){
		static $instances = array();
		if ( !isset($instances[$tablename]) ){
			$instances[$tablename] = new Dataface_TableTool($tablename);
		}
		return $instances[$tablename];
	}
	
	
	/**
	 * If there is a link associated with a field of the table, this method
	 * returns a full and proper url for the link.
	 * @param fieldname The name of the field in the table.
	 * @param $values 
	 */
	function resolveLink($fieldname, &$record){
		if ( !is_a($record, 'Dataface_Record') ){
			throw new Exception("Dataface_TableTool::resolveLink() expects an object of type 'Dataface_Record' as the second argument, but received '".get_class($record));
		}
		$link = $record->getLink($fieldname);
	
		if ( is_array($link) ){
			return Dataface_LinkTool::buildLink($link);
		} else if ( $link ){
			return $this->_app->filterUrl($link);
		} else {
			return null;
		}
	
	}


}
