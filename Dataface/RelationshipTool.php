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
 *  File: Dataface/RelationshipTool.php
 *  Author: Steve Hannah <shannah@sfu.ca>
 *  Created: October 21, 2005
 *  Description:
 *  ------------
 *  Utility methods to manage relationships.  In particular, writing relationships.
 */

require_once 'SQL/Parser.php';

class Dataface_RelationshipTool {


	var $_table;
	var $_parser;
	
	function Dataface_RelationshipTool($tablename){
		$this->_table =& Dataface_Table::loadTable($tablename);
		$this->_parser = new Sql_parser();
	}
	
	
	public static function getInstance($tablename){
		static $instances = array();
		if (!isset($instances[$tablename]) ){
			$instances[$tablename] = new Dataface_RelationshipTool($tablename);
		}
		return $instances[$tablename];
	}
	
	

	function isRemoveable($relationship_name){}
	function isLinkable($relationship_name){}
	function isUnlinkable($relationship_name){}
	
	function isAddable($relationship_name){
		$r =& $this->_table->getRelationship($relationship_name);
		$sql = $this->_table->parseString($r['sql']);
		echo $sql;
		$struct = $this->_parser->parse($sql);
		print_r($struct);
		
		$where_clause = $struct['where_clause'];
		
		
	}
			
			
			
		



}
