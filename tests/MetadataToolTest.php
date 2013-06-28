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
require_once 'Dataface/MetadataTool.php';

class MetadataToolTest extends BaseTest {

	function MetadataToolTest($name="MetadataToolTest"){
		$this->BaseTest($name);
	}
	
	function setUp(){
		$app =& Dataface_Application::getInstance();
		parent::setUp();
		$sql = "create table `md_test1` (
				fname varchar(32) NOT NULL,
				lname varchar(32) NOT NULL,
				age int(11) default 10,
				primary key (`fname`,`lname`))";
		$res = mysql_query($sql, $app->db());
		if (!$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		
		$sql = "create table `md_test1__metadata` (
			   fname varchar(32) NOT NULL,
			   lname varchar(32) NOT NULL,
			   __translation_state int(5) default 0,
			   primary key (`fname`,`lname`))";
		$res = mysql_query($sql, $app->db());
		if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		
		
	}
	
	function tearDown(){
		$app=& Dataface_Application::getInstance();
		$sql = "drop table if exists `md_test1`";
		$res = mysql_query($sql, $app->db());
		if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		
		$sql = "drop table if exists `md_test1__metadata`";
		$res = mysql_query($sql, $app->db());
		if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		parent::tearDown();
	}
	
	function test_isMetadataTable(){
		$mt = new Dataface_MetadataTool(null);
		$this->assertTrue($mt->isMetadataTable('foo__metadata'));
		$this->assertTrue(!$mt->isMetadataTable('foo__metadata2'));
		$this->assertTrue($mt->isMetadataTable('bar_metadata__metadata'));
	}
	
	
	function test_getColumns(){
		$app =& Dataface_Application::getInstance();
		$mt = new Dataface_MetadataTool('md_test1');
		$cols = $mt->getColumns();
		$this->assertEquals(
			array('fname','lname','__translation_state'),
			array_keys($cols)
			);
			
		$sql = "alter table `md_test1__metadata` add column `__state` int(5) default 0";
		$res = mysql_query($sql, $app->db());
		if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		
		$cols = $mt->getColumns();
		$this->assertEquals(
			array('fname','lname','__translation_state'),
			array_keys($cols)
			);
		$cols = $mt->getColumns(null, false); // this time don't use cache.. extra column should show up.
		$this->assertEquals(
			array('fname','lname','__translation_state','__state'),
			array_keys($cols)
			);
	}
	
	function test_getKeyColumns(){
		$app =& Dataface_Application::getInstance();
		$mt = new Dataface_MetadataTool('md_test1');
		$cols = $mt->getKeyColumns();
		
		$this->assertEquals(
			array('fname','lname'),
			array_keys($cols)
			);
		
	}
	
	
	function test_loadMetadataFieldDefs(){
		$app =& Dataface_Application::getInstance();
		$mt = new Dataface_MetadataTool('md_test1');
		$fields = $mt->loadMetadataFieldDefs();
		$this->assertEquals(
			array('__translation_state','__published_state'),
			array_keys($fields)
			);
	}
	
	
	function test_createMetadataTable(){
		$app =& Dataface_Application::getInstance();
		$sql = "create table `md_test2` (
				fname varchar(32) NOT NULL,
				lname varchar(32) NOT NULL,
				age int(11) default 10,
				primary key (`fname`,`lname`))";
		$res = mysql_query($sql, $app->db());
		if (!$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		
		$mt = new Dataface_MetadataTool('md_test2');
		$this->assertTrue($mt->createMetadataTable());
		
		$this->assertEquals(1, mysql_num_rows(mysql_query("show tables like 'md_test2__metadata'", $app->db())));
		$cols = $mt->getColumns(null, false);
		$this->assertEquals(
			array('fname','lname','__translation_state','__published_state'),
			array_keys($cols)
			);
		
	}
	
	function test_refreshMetadataTable(){
		$app =& Dataface_Application::getInstance();
		$sql = "create table `md_test3` (
				fname varchar(32) NOT NULL,
				lname varchar(32) NOT NULL,
				age int(11) default 10,
				primary key (`fname`,`lname`))";
		$res = mysql_query($sql, $app->db());
		if (!$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		$mt = new Dataface_MetadataTool('md_test3');
		$this->assertTrue($mt->refreshMetadataTable());
		$this->assertEquals(1, mysql_num_rows(mysql_query("show tables like 'md_test3__metadata'", $app->db())));
		$cols = $mt->getColumns(null, false);
		$this->assertEquals(
			array('fname','lname','__translation_state','__published_state'),
			array_keys($cols)
			);
			
		$mt->fieldDefs['__test_col'] = array('Type'=>'varchar(32)','Default'=>'Null','Field'=>'__test_col');
		$this->assertTrue($mt->refreshMetadataTable());
		$cols = $mt->getColumns(null,false);
		$this->assertEquals(
			array('fname','lname','__translation_state','__published_state','__test_col'),
			array_keys($cols)
			);
	}
	
	
}
?>
	
