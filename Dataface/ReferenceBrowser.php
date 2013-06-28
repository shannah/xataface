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
require_once 'HTML/QuickForm.php';

class ReferenceBrowser {


	var $_table;
	var $_relationshipName;
	var $_relationship;
	
	function ReferenceBrowser( $tablename, $relationshipName){
		$this->_table =& Dataface_Table::loadTable($tablename);
		$this->_relationshipName = $relationshipName;
		$this->_relationship =& $this->_table->getRelationship($relationshipName);
		$this->HTML_QuickForm('Reference Browser');
	}
	
	
	function _build(){
		
		$type = $this->getRelationshipType($this->_relationshipName);
		if ( $type != "many_to_many" ){
			trigger_error("Attempt to build reference browser widget for relationship '".$this->_relationshipName."' in tablle '".$this->_table->tablename."' where the type is not 'many_to_many'.  The type found was '$type'.", E_USER_ERROR);
		}
		
		$domainSQL = $this->_table->getRelationshipDomainSQL($this->_relationshipName);
		$res = mysql_query($domainSQL, $this->_table->db);
		
	
	}


}
