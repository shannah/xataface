<?php
ini_set('include_path', dirname(__FILE__).'/../../..:'.dirname(__FILE__).'/../../../lib:'.ini_get('include_path'));

require_once 'PHPUnit.php';

require_once 'HTML/QuickForm/table.php';
require_once 'HTML/QuickForm/text.php';


class QuickForm_table_test extends PHPUnit_TestCase {

	var $el;
	
	function QuickForm_table_test( $name = 'QuickForm_table_test'){
		$this->PHPUnit_TestCase($name);
	}
	
	function setUp(){
		$this->el = new HTML_QuickForm_table('test_table','Testing');
		$this->el->addField( new HTML_QuickForm_text('fname','First Name'));
		$this->el->addField( new HTML_QuickForm_text('lname','Last Name'));
		
	
	}
	
	function tearDown(){
	
	
	}
	
	function test_Html(){
		$this->assertEquals($this->el->toHtml(), '');
	
	}
	
	
	function test_Html_with_values(){
		$el = unserialize(serialize($this->el));
		$el->setName('test_table_2');
		
		$el->setValue(
			array(
				array('fname'=>'John','lname'=>'Stamos'),
				array('fname'=>'Sally','lname'=>'Field'),
				array('fname'=>'Steve','lname'=>'Field')
			)
		);
		$this->assertEquals($el->toHtml(), '');
	}
	


}

$test = new PHPUnit_TestSuite('QuickForm_table_test');

$result = PHPUnit::run($test);

print $result->toString();


?>
