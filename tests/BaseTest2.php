<?php
require 'testconfig.php';
require_once 'PHPUnit.php';
require_once 'Dataface/Table.php';
require_once 'Dataface/QueryBuilder.php';
require_once 'mysql_functions.php';
require_once 'Dataface/QueryTool.php';
require_once 'Dataface/QuickForm.php';
class BaseTest2 extends PHPUnit_TestCase {

	var $db;
	var $table1;
	var $fieldnames_control;
	var $types_control;
	
	function BaseTest2( $name = 'BaseTest'){
		$this->PHPUnit_TestCase($name);
	}

	function setUp(){
		startTimer();
		$this->db = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("Could not connect to db");
		endTimer("Connect to database");
		
		startTimer();
		mysql_select_db("mysql");
			
	
	}
	
	function tearDown(){
		
		
	}
	
		


}

function startTimer(){
	global $timer;
	
	$timer = microtime(true);
}

function endTimer($msg){
	global $timer, $showBenchmarks;
	$time = microtime(true) - $timer;
	if ( isset($showBenchmarks) and $showBenchmarks ){
		echo "$msg : $time\n";
	}
}


?>
