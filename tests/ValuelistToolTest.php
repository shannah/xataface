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

$_SERVER['PHP_SELF'] = __FILE__;
require_once 'BaseTest.php';
require_once 'Dataface/ValuelistTool.php';


class ValuelistToolTest extends BaseTest {

	
	
	function ValuelistToolTest($name = 'ValuelistToolTest'){
		$this->BaseTest($name);
		//parent::BaseTest();
	}
	
	function test_add_value(){
		$vt = Dataface_ValuelistTool::getInstance();
		$people = Dataface_Table::loadTable('People');
		$vt->addValueToValuelist($people, 'Publications', 'My Test Publication');
		$res = mysql_query("select * from Publications where `BiblioString` = 'My Test Publication'");
		$this->assertTrue( mysql_num_rows($res) === 1);
		
	}
}
	
?>
