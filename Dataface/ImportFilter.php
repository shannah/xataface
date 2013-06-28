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
 * File:	Dataface/ImportFilter.php
 * Author:	Steve Hannah <shannah@sfu.ca>
 * Created:	December 1, 2005
 *
 * Description:
 * ------------
 *
 * Takes raw data and converts it to a set of Record objects.  Makes use of the table
 * delegate files for specific functionality.
 *
 */
 
require_once 'Dataface/Table.php';

class Dataface_ImportFilter {
	
	/**
	 * Reference to table object.
	 * @type Dataface_Table
	 */
	var $_table;
	
	/**
	 * Label for the filter.
	 */
	var $label;
	
	/**
	 * Name of the filter.
	 */
	var $name;
	
	
	/**
	 * Constructor
	 * @param $tablename Name of the table that this imports data into.
	 * @param $name The name of the filter.
	 * @param $label The label of the filter.
	 */
	function Dataface_ImportFilter( $tablename, $name, $label ){
		$this->_table =& Dataface_Table::loadTable($tablename);
		$this->label = $label;
		$this->name = $name;
	}
	
	
	/**
	 * Imports data into the table.  This works by calling the delegate function __import__<filtername>
	 * where you replace '<filtername>' with the name of this filter.
	 *
	 * @param $data  @type raw data The raw data that is being imported.
	 */
	function import(&$data, $defaultValues=array()){
	
		$delegate =& $this->_table->getDelegate();
		if ( $delegate !== null and method_exists($delegate, '__import__'.$this->name) ){
			return call_user_func(array(&$delegate,'__import__'.$this->name), $data, $defaultValues);
		}
	
	}
	
	


}



?>
