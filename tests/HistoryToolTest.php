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
require_once 'Dataface/HistoryTool.php';

class HistoryToolTest extends BaseTest {

	function HistoryToolTest($name="HistoryToolTest"){
		$this->BaseTest($name);
	}
	
	function setUp(){
		$app =& Dataface_Application::getInstance();
		parent::setUp();
		$sql = " CREATE TABLE `HistoryToolTest` (
			`id` int(11) not null auto_increment,
			`name` varchar(32) not null default 'John',
			`age` int(5) default 10,
			`date_created` timestamp,
			`container_field` varchar(32),
			`container_field_mimetype` varchar(32),
			PRIMARY KEY (`id`))";
		$res = mysql_query($sql, $app->db());
		if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		$sql = array();
		$sql[] = "insert into `HistoryToolTest` (`name`,`container_field`,`container_field_mimetype`) values ('Johnny','john.gif','image/gif')";
		$sql[] = "insert into `HistoryToolTest` (`name`,`container_field`,`container_field_mimetype`) values ('Betty','betty.jpg','image/jpg')";
		$sql[] = "insert into `HistoryToolTest` (`name`) values ('Joseph')";
		foreach ($sql as $q){
			$res = mysql_query($q, $app->db());
			if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		}
		
		$savepath = dirname(__FILE__).'/tables/HistoryToolTest/container_field/';
		if ( file_exists($savepath) and $fh = opendir($savepath) ) {
			while ( false !== ($file = readdir($fh)) ){
				if ( $file{0} != '.' ) unlink($savepath.$file);
			}
			closedir($fh);
		}
		
		$historypath = $savepath.'.dataface_history/';
		if ( file_exists($historypath) and $fh = opendir($historypath) ){
			while ( false !== ($file = readdir($fh) ) ){
				if ($file{0} != '.' ) unlink($historypath.$file);
			}
			closedir($fh);
		}
		
		if ( file_exists($historypath) ) rmdir($historypath);
		
		touch($savepath.'john.gif');
		touch($savepath.'betty.jpg');
		
		
	}
	
	function test_create(){
	
		$profile =& df_get_record('Profiles', array('id'=>10));
		$ht = new Dataface_HistoryTool();
		$ht->createHistoryTable($profile->_table->tablename);
		$this->assertTrue( mysql_num_rows(mysql_query("show tables like '".$ht->logTableName($profile->_table->tablename)."'")) > 0 );
		

	}
	
	function test_log_record(){
		$profile =& df_get_record('Profiles', array('id'=>10));
		$ht = new Dataface_HistoryTool();
		$ht->logRecord($profile);
		$history =& df_get_record($ht->logTableName('Profiles'), array('id'=>10));
		$profile2 = new Dataface_Record('Profiles', $history->vals());
		$this->assertEquals(
			$profile->strvals(),
			$profile2->strvals()
			);
		//print_r($history->strvals());
	
	}
	
	function test_diff(){
		$this->test_log_record();
		$profile =& df_get_record('Profiles', array('id'=>10));
		$profile->setValue('description',"Head of this household\nAnd more\nHe is also a great fisherman");
		$profile->save();
		$ht = new Dataface_HistoryTool();
		$ht->logRecord($profile);
		
		$diff = $ht->getDiffs('Profiles',1,2);
		$this->assertEquals("Head of <del>the</del><ins>this</ins> household<ins>
And more
He is also a great fisherman</ins>
",
			$diff->val('description')
			);
		
		$this->assertEquals('',$diff->val('title'));
		$this->assertEquals('<del>1</del><ins>2</ins>
',
			$diff->val('history__id')
			);
		//print_r($diff->strvals());
		
		$profile->setValue('description',"Head of my household\nAnd more\nHe is also a greater fisherman");
		$profile->save();
		$ht->logRecord($profile);
		$diff = $ht->getDiffs('Profiles',2,3);
		
		$this->assertEquals(
			'Head of <del>this</del><ins>my</ins> household
And more
He is also a <del>great</del><ins>greater</ins> fisherman
',
			$diff->val('description')
			);
			
		$diff = $ht->getDiffs('Profiles',2);
		$this->assertEquals(
			'Head of <del>this</del><ins>my</ins> household
And more
He is also a <del>great</del><ins>greater</ins> fisherman
',
			$diff->val('description')
			);
		//print_r($diff->strvals());
		//print_r($diff->strvals());
		//$res =mysql_query("select  * from `Profiles__history`");
		//while ( $row = mysql_fetch_assoc($res)){
		//	print_r($row);
		//}
	}
	
	
	function test_diffs_by_date(){
		$this->test_diff();
		$app =& Dataface_Application::getInstance();
		$profile =& df_get_record('Profiles', array('id'=>10));
		$profile->setValue('description', 'Head of your household');
		$profile->save();
		$ht = new Dataface_HistoryTool();
		$ht->logRecord($profile);
		
		$sql[] = "update `Profiles__history` set `history__modified`='2005-12-10 12:23:00' where `history__id`=1";
		$sql[] = "update `Profiles__history` set `history__modified`='2006-05-04 12:22:00' where `history__id`=2";
		$sql[] = "update `Profiles__history` set `history__modified`='2006-05-05 12:21:00' where `history__id`=3";
		foreach ($sql as $query){
			$res = mysql_query($query, $app->db());
			if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		}
		
		$diff = $ht->getDiffsByDate($profile, '2006-01-01');
		$this->assertEquals(
			'Head of <del>the</del><ins>your</ins> household
',
			$diff->val('description')
			);
			
		$diff = $ht->getDiffsByDate($profile, '2006-05-04 12:22:00');
		//print_r($diff->strvals());
		$this->assertEquals(
			'Head of <del>this</del><ins>your</ins> household<del>
And more
He is also a great fisherman</del>
',
			$diff->val('description')
			);
			
		$diff = $ht->getDiffsByDate($profile, '2006-05-04', '2006-05-06');
		$this->assertEquals(
			'Head of <del>the</del><ins>my</ins> household<ins>
And more
He is also a greater fisherman</ins>
',
			$diff->val('description')
			);
		//print_r($diff->strvals());
	}
	
	function test_restore_field(){
		$app =& Dataface_Application::getInstance();
		$record = df_get_record('HistoryToolTest', array('name'=>'Johnny'));
		$record->setValue('container_field', 'john2.gif');
		$savepath = dirname(__FILE__).'/tables/HistoryToolTest/container_field/';
		unlink($savepath.'john.gif') or trigger_error("Failed to unlink john.gif", E_USER_ERROR);
		$res = touch($savepath.'john2.gif') or trigger_error("Failed to touch john2.gif", E_USER_ERROR);
	
		$record->save();
		$historyTool = new Dataface_HistoryTool();
		$hid = $historyTool->logRecord($record);
		//$savepath = dirname(__FILE__).'/tables/HistoryToolTest/container_field/';
		$historypath = $savepath.'.dataface_history/';
		//echo "History id: $hid";
		//echo "Checking for ".$historypath.$hid." on line ".__LINE__;
		$this->assertTrue(file_exists($historypath.$hid));
		
		$original_record = new Dataface_Record('HIstoryToolTest', $record->getValues());
		$record->setValue('container_field', 'john3.gif');
		unlink($savepath.'john2.gif');
		touch($savepath.'john3.gif');
		$record->save();
		$hid2 = $historyTool->logRecord($record);
		$this->assertTrue(file_exists($historypath.$hid2));
		$historyTool->restore($record, $hid);
		$this->assertTrue(file_exists($savepath.'john2.gif'));
		$this->assertTrue(!file_exists($savepath.'john3.gif'));
		
		
		
	}
	
	function test_restore(){
		$app =& Dataface_Application::getInstance();
		$record = df_get_record('HistoryToolTest', array('name'=>'Johnny'));
		$this->assertEquals('john.gif', $record->val('container_field'));
		$record->setValue('container_field', 'john2.gif');
		$record->save();
		$ht = new Dataface_HistoryTool();
		$hid = $ht->logRecord($record);
		$history1 = $ht->getRecordById('HistoryToolTest',$hid);
		$this->assertEquals(array('name'=>'Johnny','container_field'=> 'john2.gif'), $history1->strvals(array('name','container_field')));
		
		$record->setValue('container_field', 'john3.gif');
		$record->save();
		$hid2 = $ht->logRecord($record);
		$history2 = $ht->getRecordById('HistoryToolTest',$hid2);
		$this->assertEquals(array('name'=>'Johnny','container_field'=>'john3.gif'), $history2->strvals(array('name','container_field')));
		
		$record2 = df_get_record('HistoryToolTest', array('name'=>'Johnny'));
		$this->assertEquals($record2->strvals(array('name','container_field')), $history2->strvals(array('name','container_field')));
		
		$ht->restore($record, $hid);
		$record3 = df_get_record('HistoryToolTest', array('name'=>'Johnny'));
		$this->assertEquals(array('name'=>'Johnny','container_field'=>'john2.gif'), $record3->strvals(array('name','container_field')));
		
		
	
	}
	
	
	function test_restore_to_date(){
		$app =& Dataface_Application::getInstance();
		$record = df_get_record('HistoryToolTest', array('name'=>'Johnny'));
		$this->assertEquals('john.gif', $record->val('container_field'));
		$record->setValue('container_field', 'john2.gif');
		$record->save();
		$ht = new Dataface_HistoryTool();
		$hid = $ht->logRecord($record);
		$history1 = $ht->getRecordById('HistoryToolTest',$hid);
		$this->assertEquals(array('name'=>'Johnny','container_field'=> 'john2.gif'), $history1->strvals(array('name','container_field')));
		
		$record->setValue('container_field', 'john3.gif');
		$record->save();
		$hid2 = $ht->logRecord($record);
		$history2 = $ht->getRecordById('HistoryToolTest',$hid2);
		$this->assertEquals(array('name'=>'Johnny','container_field'=>'john3.gif'), $history2->strvals(array('name','container_field')));
		
		$record2 = df_get_record('HistoryToolTest', array('name'=>'Johnny'));
		$this->assertEquals($record2->strvals(array('name','container_field')), $history2->strvals(array('name','container_field')));
		
		$sql = array();
		$sql[] = "update `HistoryToolTest__history` set `history__modified` = '2004-01-02' where `history__id` = '$hid'";
		foreach ($sql as $q){
			$res = mysql_query($q, $app->db());
			if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		}
		
		$ht->restoreToDate($record, '2004-02-02');
		$record3 = df_get_record('HistoryToolTest', array('name'=>'Johnny'));
		$this->assertEquals(array('name'=>'Johnny','container_field'=>'john2.gif'), $record3->strvals(array('name','container_field')));
		
		
	}
}
?>
	
