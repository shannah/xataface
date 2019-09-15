<?php
import('PHPUnit.php');

class ModelsTest extends PHPUnit_TestCase {
	function __construct($name){
		$this->PHPUnit_TestCase($name);
	}
	
	
}

$jt = Dataface_JavascriptTool::getInstance();
$jt->import('xataface/model/tests/ModelTest.js');
$jt->import('xataface/model/tests/ListModelTest.js');
$jt->import('xataface/model/tests/MasterDetailModelTest.js');
$jt->import('xataface/store/tests/DocumentTest.js');
$jt->import('xataface/store/tests/ResultSetTest.js');
$jt->import('xataface/store/tests/MasterDetailStoreTest.js');
$jt->import('xataface/form/tests/FormElementTest.js');
$jt->import('xataface/form/tests/FormTest.js');
$jt->import('xataface/tests/ClassLoaderTest.js');
