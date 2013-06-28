<?php
set_include_path(
	dirname(__FILE__)
	.DIRECTORY_SEPARATOR
	.'..'
	.DIRECTORY_SEPARATOR
	.'..'
	.DIRECTORY_SEPARATOR
	.'..'
	.PATH_SEPARATOR
	.dirname(__FILE__)
	.DIRECTORY_SEPARATOR
	.'..'
	.DIRECTORY_SEPARATOR
	.'..'
	.DIRECTORY_SEPARATOR
	.'..'
	.DIRECTORY_SEPARATOR
	.'lib'
	.PATH_SEPARATOR
	.dirname(__FILE__)
	.DIRECTORY_SEPARATOR
	.'..'
	.DIRECTORY_SEPARATOR
	.'classes'
	.PATH_SEPARATOR
	.get_include_path()
);
require_once 'PHPUnit.php';
require_once 'JavascriptDocumentor.php';
define('DATAFACE_SITE_PATH', dirname(__FILE__));
define('DATAFACE_SITE_URL', 'http://example.com/site');
define('DATAFACE_SITE_HREF', DATAFACE_SITE_URL.'/index.php');
define('DATAFACE_PATH', '..');

class JavascriptDocumentorTest extends PHPUnit_TestCase {
	function JavascriptToolTest($name = 'JavscriptDocumentorTest'){
		$this->PHPUnit_TestCase($name);
		$cachedir = dirname(__FILE__).DIRECTORY_SEPARATOR.'templates_c';
		if ( !file_exists($cachedir) ) mkdir($cachedir);
	}
	
	function untest_compile1(){
		$js = new JavascriptDocumentor();
		$js->clearCache();
		$js->addPath(
			dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'js',
			'/js2'
		);
		$js->addPath(
			dirname(__FILE__).DIRECTORY_SEPARATOR.'JavascriptDocumentorTest'.DIRECTORY_SEPARATOR.'js1',
			'/js1');
		$js->import('scriptA.js');
		$actual = trim($js->getContents());
		echo $actual;
		
		//$this->assertEquals($expected, $actual) ;
		
		
	}
	
	function test_function(){
		$js = new JavascriptDocumentor();
		$js->clearCache();
		$js->addPath(
			dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'js',
			'/js2'
		);
		
		$js->addPath(
			dirname(__FILE__).DIRECTORY_SEPARATOR.'JavascriptDocumentorTest'.DIRECTORY_SEPARATOR.'js1',
			'/js1');
			
		$str = <<<END
/**
 * This is a simple function.
 */
 function mySimpleFunc(){}
END;
		$result = $js->process($str);
		echo $result;
	}
	
	function untest_function2(){
		$js = new JavascriptDocumentor();
		$str = <<<END
/**
 * This is a simple function with one parameter.
 * @param {int} o The input integer.
 */
 function mySimpleFunc(){}
END;
		echo $js->process($str);
	}
	
	function untest_function3(){
		$js = new JavascriptDocumentor();
		$str = <<<END
/**
 * This is a simple function with one parameter.
 * @param {int} 
 */
 function mySimpleFunc(){}
END;
		echo $js->process($str);
	}
	
	function test_var1(){
		$js = new JavascriptDocumentor();
		$str = <<<END
/**
 * This variable stores some important info.
 * @type {int}
 */
 var myvar=null;
END;
		echo $js->process($str);
	}
	
}

if ( @$argv){
	$test = new PHPUnit_TestSuite('JavascriptDocumentorTest');
	$result = new PHPUnit_TestResult;
	//$result->addListener(new Benchmarker());
	$test->run($result);
	
	print $result->toString();
}
