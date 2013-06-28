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
require_once 'Dataface/Table/builder.php';

class Table_builderTest extends BaseTest {

	var $mytable = 'my_table';
	
	function Table_builderTest($name = 'Table_builderTest'){
		//$this->PHPUnit_TestCase($name);
		//parent::BaseTest();
		$this->BaseTest($name);
	}
	
	function setUp(){
		$app =& Dataface_Application::getInstance();
		$path = DATAFACE_SITE_PATH.'/tables/'.$this->mytable;
		if ( file_exists($path) ) @rmdir($path);
		mysql_query('drop table if exists `'.$this->mytable.'`', $app->db()) or die(mysql_error().' on line '.__LINE__.' of file '.__FILE__);
		parent::setUp();
	}
	
	function test_save(){
		$app =& Dataface_Application::getInstance();
		$builder = new Dataface_Table_builder($this->mytable);
		$this->assertTrue(!isset($builder->table));
		$this->assertEquals(
			0,
			mysql_num_rows(mysql_query("show tables like '".$this->mytable."'", $app->db()))
			);
		
		$builder->addField(
			array(
				'Field'=>'name',
				'Type'=>'varchar(32)'
				)
			);
		$res = $builder->save();
		
		if ( PEAR::isError($res) ) trigger_error($res->toString(), E_USER_ERROR);
		
		$this->assertEquals(
			array('name','id'),
			array_keys($builder->table->fields())
			);
		
		$this->assertEquals(
			1,
			mysql_num_rows(mysql_query("show tables like '".$this->mytable."'", $app->db()))
			);
		
		
		$builder->addField(
			array(
				'Field'=>'email',
				'Type'=>'varchar(28)'
				)
			);
		
		$res = $builder->save();
		if ( PEAR::isError($res) ) trigger_error($res->toString(), E_USER_ERROR);
		
		$this->assertEquals(
			array('name','id','email'),
			array_keys($builder->table->fields())
			);
		
		$this->assertEquals(
			1,
			mysql_num_rows(mysql_query("show tables like '".$this->mytable."'", $app->db()))
			);
		
		
	
	}
}





?>
