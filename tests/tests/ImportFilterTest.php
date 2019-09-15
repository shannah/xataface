<?php
/********************************************************************************
 *
 *  Xataface Web Application Framework for PHP and MySQL
 *  Copyright (C) 2005  Steve Hannah <shannah@sfu.ca>
 *  
 *  This library is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU Lesser General Public
 *  License as published by the Free Software Foundation; either
 *  version 2.1 of the License, or (at your option) any later version.
 *  
 *  This library is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *  Lesser General Public License for more details.
 *  
 *  You should have received a copy of the GNU Lesser General Public
 *  License along with this library; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *===============================================================================
 */

require_once 'BaseTest.php';
require_once 'Dataface/ImportFilter/xml.php';
require_once 'Dataface/Record.php';

class ImportFilterTest extends BaseTest {

	function ImportFilterTest($name = 'ImportFilterTest'){
		$this->BaseTest($name);
		//parent::BaseTest();
	}
	
	function test_xml_import_filter(){
		$data = '<?xml version="1.0"?>
			<dataface>
				<Profiles>
					<fname>John</fname>
					<lname>Smith</lname>
					<title>Professor</title>
				</Profiles>
				<Profiles>
					<fname>Julia</fname>
					<lname>Vaughn</lname>
					<title>Assistant</title>
				</Profiles>
			
			
			</dataface>';
		
		$filter = new Dataface_ImportFilter_xml();
		$records = $filter->import($data);
		
		$this->assertEquals( 2, count($records) );
		$this->assertEquals('dataface_record', strtolower(get_class($records[0])));
		
		$this->assertEquals('Profiles', $records[0]->_table->tablename);
		$this->assertEquals('Profiles', $records[1]->_table->tablename);
		$this->assertEquals('John', $records[0]->val('fname'));
		$this->assertEquals('Smith', $records[0]->val('lname'));
		$this->assertEquals('Professor', $records[0]->val('title'));
		$this->assertEquals('Julia', $records[1]->val('fname'));
	
	}
}


?>
