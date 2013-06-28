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
require_once 'Dataface/TranslationTool.php';

class TranslationToolTest extends BaseTest {

	function TranslationToolTest($name="TranslationToolTest"){
		$this->BaseTest($name);
	}
	
	function setUp(){
		parent::setUp();
		$app =& Dataface_Application::getInstance();
		$sql = "drop table if exists `dataface__translations`";
		$res = mysql_query($sql, $app->db());
		if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		
	}
	
	function test_createTranslationsTable(){
		$app =& Dataface_Application::getInstance();
		$tt = new Dataface_TranslationTool();
		
		
		$tt->createTranslationsTable();
		$sql = "show columns from `dataface__translations`";
		$res = mysql_query($sql, $app->db());
		if ( !$res ) {
			trigger_error(mysql_error($app->db()), E_USER_ERROR);
		}
		
		$cols = array();
		while ( $row = mysql_fetch_assoc($res) ) $cols[$row['Field']] = $row;
		$this->assertEquals(
			array_keys($tt->schema),
			array_keys($cols)
			);
	
	}
	
	function test_updateTranslationsTable(){
		$app =& Dataface_Application::getInstance();
		$tt = new Dataface_TranslationTool();
		
		
		$tt->updateTranslationsTable();
		$sql = "show columns from `dataface__translations`";
		$res = mysql_query($sql, $app->db());
		if ( !$res ) {
			trigger_error(mysql_error($app->db()), E_USER_ERROR);
		}
		
		$cols = array();
		while ( $row = mysql_fetch_assoc($res) ) $cols[$row['Field']] = $row;
		$this->assertEquals(
			array_keys($tt->schema),
			array_keys($cols)
			);
			
		$tt->schema['test_col'] = array('Field'=>'test_col', 'Type'=>"int(11)", 'Extra'=>'', 'Null'=>'');
		$tt->updateTranslationsTable();
		$sql = "show columns from `dataface__translations`";
		$res = mysql_query($sql, $app->db());
		if ( !$res ) {
			trigger_error(mysql_error($app->db()), E_USER_ERROR);
		}
		
		$cols = array();
		while ( $row = mysql_fetch_assoc($res) ) $cols[$row['Field']] = $row;
		$this->assertEquals(
			array_keys($tt->schema),
			array_keys($cols)
			);
		
		
	}
	
	function test_getRecordId(){
		$app =& Dataface_Application::getInstance();
		$record = df_get_record('Profiles', array('id'=>10));
		$tt = new Dataface_TranslationTool();
		$this->assertEquals(
			'id=10',
			$tt->getRecordId($record)
			);
	}
	
	function test_getTranslationId(){
		$app =& Dataface_Application::getInstance();
		$record = df_get_record('Profiles', array('id'=>10));
		$tt = new Dataface_TranslationTool();
		$enid  = $tt->getTranslationId($record, 'en');
		$frid = $tt->getTranslationId($record, 'fr');
		$this->assertEquals(1,$enid);
		$this->assertEquals(2,$frid);
	}
	
	function test_getTranslationRecord(){
		$app =& Dataface_Application::getInstance();
		$record = df_get_record('Profiles', array('id'=>10));
		$tt = new Dataface_TranslationTool();
		$enrec  = $tt->getTranslationRecord($record, 'en');
		$frrec = $tt->getTranslationRecord($record, 'fr');
		
		$this->assertEquals('id=10', $enrec->val('record_id'));
		$this->assertEquals(1, $enrec->val('id'));
	}
	
	function test_getCanonicalVersion(){
		$record = df_get_record('Profiles', array('id'=>10));
		$tt = new Dataface_TranslationTool();
		$this->assertEquals('1.00', $tt->getCanonicalVersion($record,'en'));
	}
	
	function test_newCanonicalVersion(){
		$record = df_get_record('Profiles', array('id'=>10));
		$tt = new Dataface_TranslationTool();
		$this->assertEquals('2.00', $tt->markNewCanonicalVersion($record, 'en'));
		$this->assertEquals('3.00', $tt->markNewCanonicalVersion($record, 'en'));
		$this->assertEquals('2.00', $tt->markNewCanonicalVersion($record, 'fr'));
	}
	
	function test_migrateDefaultLanguage(){
		$app =& Dataface_Application::getInstance();
		$vals = mysql_fetch_assoc(mysql_query("select * from PeopleIntl where PersonID=1", $app->db()));
		$this->assertEquals(
			"Default Position",
			$vals['Position']
			);
		
		$vals2 = mysql_fetch_assoc(mysql_query("select * from PeopleIntl where PersonID=2", $app->db()));
		$this->assertEquals(
			"Default Position 2",
			$vals2['Position']
			);
		
		$tt = new Dataface_TranslationTool();
		$tt->migrateDefaultLanguage('en', array('PeopleIntl'));
		
		$vals = mysql_fetch_assoc(mysql_query("select * from PeopleIntl where PersonID=1", $app->db()));
		$this->assertEquals(
			"My English Position",
			$vals['Position']
			);
		
		$vals2 = mysql_fetch_assoc(mysql_query("select * from PeopleIntl where PersonID=2", $app->db()));
		$this->assertEquals(
			"Default Position 2",
			$vals2['Position']
			);
	}
}
?>
	
