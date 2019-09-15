<?php
$_SERVER['PHP_SELF'] = __FILE__;
require_once 'BaseTest.php';
require_once 'Dataface/Record.php';
require_once 'dataface-public-api.php';
require_once 'Dataface/Utilities.php';

class UtilitiesTest extends BaseTest {

	
	
	function UtilitiesTest($name = 'UtilitiesTest'){
		$this->BaseTest($name);
		//parent::BaseTest();
	}
	
	
	function test_groupBy(){
		$record =& df_get_record('People', array('PersonID'=>1));
		
		$pubs = $record->getRelatedRecords('Publications','all');
		
		$categories = Dataface_Utilities::groupBy('PubType', $pubs);
		
		$this->assertEquals(array('Refereed Journal','Book Chapter','Conference'), array_keys($categories));
		
		$this->assertEquals(64, sizeof($categories['Refereed Journal']));
		$this->assertEquals(64, sizeof($categories['Book Chapter']));
		$this->assertEquals(63, sizeof($categories['Conference']));
		
		
	
	}
}

?>
