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
import('HTML/QuickForm.php');
class Dataface_ValuelistEditorForm extends HTML_QuickForm {
	var $table;
	var $valuelistName;
	var $values;
	var $widgetID;
	var $_built = false;
	
	function Dataface_ValuelistEditorForm($tablename, $valuelistName, $widgetID=null){
		$this->table =& Dataface_Table::loadTable($tablename);
		$this->valuelistName = $valuelistName;
		$this->values = $this->table->getValuelist($this->valuelistName);
		$this->widgetID = $widgetID;
	}
	
	function _build(){
		if ( $this->_built ) return;
		$this->_built = true;
		$this->addElement('text', 'add_value', 'Add Value');
		$this->addElement('submit','save','Save');
	}
	
	function display(){
		$this->_build();
		parent::display();
	}
	
	function save($values){
		
	}
}
