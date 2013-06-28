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
 * File: Dataface/ImportFiter/xml.php
 * Author: Steve Hannah <shannah@sfu.ca>
 * Created: December 3, 2005
 *
 * Description:
 * -------------
 * Import filter that converts xml to Record objects.  The XML
 * must be of the form:
 *
 * <dataface>
 *	<Profile>
 *		<FirstName>John</FirstName>
 *		<LastName>Smith</LastName>
 *		<Phone>555-555-5555</Phone>
 *	</Profile>
 *	<Profile>
 *		<FirstName>Julia</FirstName>
 *		<LastName>Vaughn</LastName>
 *		<Phone>444-444-4444</Phone>
 *	</Profile>
 * </dataface>
 *
 * The above example xml would be converted into an array of 2 Dataface_Record objects
 * for the "Profile" table.
 */

require_once 'Dataface/ImportFilter.php';
require_once 'xml2array.php';

class Dataface_ImportFilter_xml extends Dataface_ImportFilter {

	function Dataface_ImportFilter_xml(){
		$this->name = 'xml';
		$this->label = 'XML';
	}
	
	
	function import(&$data){
		$arr = GetXMLTree ($data);
		$arr = $arr['dataface'];
		
		$records = array();
		foreach (array_keys($arr) as $tablename){
			foreach ( array_keys($arr[$tablename]) as $index){
				$records[] = new Dataface_Record($tablename, $arr[$tablename][$index]);
			}
		}
		
		return $records;
	}

}
