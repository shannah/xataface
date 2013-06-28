<?php
$_SERVER['PHP_SELF'] = __FILE__;
require_once 'BaseTest.php';

require_once 'dataface-public-api.php';


class DB_Sync_Test extends BaseTest {

	
	
	function DB_Sync_Test($name = 'DB_Sync_Test'){
		$this->BaseTest($name);
		//parent::BaseTest();
	}
	
	function setUp(){
		
		parent::setUp();
		require_once 'DB/Sync.php';
		require_once 'Dataface/Application.php';
		$app =& Dataface_Application::getInstance();
		
		$res = mysql_query("create table a (
			id int(11) not null auto_increment,
			a varchar(32) default 'b',
			b datetime not null,
			primary key (`id`))", $app->db());
			
		if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		
		
		$res = mysql_query("create table b (
			id int(12) not null auto_increment,
			b datetime not null,
			primary key (`id`))", $app->db());
		if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);	
	}
	
	function testSync1(){
		$app =& Dataface_Application::getInstance();
		$s = new DB_Sync($app->db(), $app->db(), 'a','b');
		$s->syncTables();
		
		$res = mysql_query("show create table b", $app->db());
		if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		$row = mysql_fetch_assoc($res);
		@mysql_free_result($res);
		
		$this->assertEquals(
			"CREATE TABLE `b` (
  `id` int(11) NOT NULL auto_increment,
  `a` varchar(32) default 'b',
  `b` datetime default '0000-00-00 00:00:00',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1",
			$row['Create Table']
			);
		
	}
	
	
	
	
	
	

	
	
	
	
	
	
	
}

?>
