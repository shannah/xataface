<?php
$_SERVER['PHP_SELF'] = '/~shannah/lesson_plans/index.php';
require_once 'BaseTest.php';
require_once 'Dataface/LinkTool.php';

class LinkToolTest extends BaseTest {

	
	function LinkToolTest($name = 'LinkToolTest'){
		$this->BaseTest($name);
		//parent::BaseTest();
	}
	
	
	function test_build_links(){
	
	
		$_GET = array('-table'=>'Profiles');
		$_POST = array();
		$_REQUEST = array('-table'=>'Profiles');
		
		$link = Dataface_LinkTool::buildLink(
			array("fname"=>"John", "lname"=>"Thomas")
		);
		$this->assertEquals($_SERVER['HOST_URI'].$_SERVER['PHP_SELF'].'?fname=John&lname=Thomas&-table=Profiles',$link);
		
		// try to use the context a bit
		$_GET['-table'] = 'Addresses';
		$_REQUEST = $_GET;
		$link = Dataface_LinkTool::buildLink(
			array("fname"=>"John", "lname"=>"Thomas")
		);
		$this->assertEquals( $_SERVER['HOST_URI'].$_SERVER['PHP_SELF'].'?fname=John&lname=Thomas&-table=Profiles',$link);
		
		$link = Dataface_LinkTool::buildLink(
			array("fname"=>"John", "lname"=>"Thomas"), false
		);
		$this->assertEquals($_SERVER['HOST_URI'].$_SERVER['PHP_SELF'].'?fname=John&lname=Thomas&-table=Profiles',$link);
		
		$link = Dataface_LinkTool::buildLink(
			array("fname"=>"John", "lname"=>"Thomas", "-table"=>null)
		);
		$this->assertEquals( $_SERVER['HOST_URI'].$_SERVER['PHP_SELF'].'?fname=John&lname=Thomas&-table=Profiles',$link);
		
	
	
	}


}






?>
