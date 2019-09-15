<?php
set_include_path(
	dirname(__FILE__)
	.DIRECTORY_SEPARATOR
	.'..'
	.PATH_SEPARATOR
	.dirname(__FILE__)
	.DIRECTORY_SEPARATOR
	.'..'
	.DIRECTORY_SEPARATOR
	.'lib'
	.PATH_SEPARATOR
	.get_include_path()
);
require_once 'PHPUnit.php';
require_once 'Dataface/CSSTool.php';
define('DATAFACE_SITE_PATH', dirname(__FILE__));
define('DATAFACE_SITE_URL', 'http://example.com/site');

class CSSToolTest extends PHPUnit_TestCase {
	function CSSToolTest($name = 'CSSToolTest'){
		$this->PHPUnit_TestCase($name);
		$cachedir = dirname(__FILE__).DIRECTORY_SEPARATOR.'templates_c';
		if ( !file_exists($cachedir) ) mkdir($cachedir);
	}
	
	function test_compile1(){
		$js = Dataface_CSSTool::getInstance();
		$js->clearCache();
		$js->addPath(
			dirname(__FILE__).DIRECTORY_SEPARATOR.'CSSToolTest'.DIRECTORY_SEPARATOR.'css1',
			'/css1');
		$js->import('stylesheetA.css');
		$js->import('stylesheetB.css');
		$js->import('stylesheetC.css');
		$actual = trim($js->getContents());
		
		$expected = "body{background-color:black;font-size:10px;padding-left:10px;padding-right:20px}table{border:1px solid black}.divB{font-weight:bold}.divC{color:red;padding:20px 10px;margin-top:1px;margin-left:30px}";
		$this->assertEquals($actual, $expected);
		
		
	}
}

if ( @$argv){
	$test = new PHPUnit_TestSuite('CSSToolTest');
	$result = new PHPUnit_TestResult;
	//$result->addListener(new Benchmarker());
	$test->run($result);
	
	print $result->toHtml();
}
