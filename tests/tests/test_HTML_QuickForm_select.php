<?php
import('PHPUnit.php');
import('HTML/QuickForm/select.php');

class xataface_HTML_QuickForm_select extends PHPUnit_TestCase {

	function xataface_Dataface_RecordTest( $name = 'xataface_HTML_QuickForm_select'){
		$this->PHPUnit_TestCase($name);
		
	}

	function setUp(){

	}
	
	
	
	
	
	
	
	function tearDown(){
		
		

	}
	
	/**
	 * This test was created to test the change to preg_split() in setValues() due
	 * to the PHP 5.3 deprecation of the split() function.
	 * http://bugs.weblite.ca/view.php?id=809
	 */
	function testPregSplit(){
		
		$select = new HTML_QuickForm_select('test','Test', array('a1'=>'Tom', 'b2'=>'Dick', 'c3'=>'Harry'));
		$select->setMultiple(true);
		$select->setSelected('a1');
		$this->assertEquals(array(0=>'a1'), $select->getSelected(), 'setSelected failed to select value.');
		$select->setSelected('a1, c3');
		$this->assertEquals(array(0=>'a1',1=>'c3'), $select->getSelected(), 'setSelectedFailed to select double value.');
	}
		


}


// Add this test to the suite of tests to be run by the testrunner
Dataface_ModuleTool::getInstance()->loadModule('modules_testrunner')
		->addTest('xataface_HTML_QuickForm_select');
