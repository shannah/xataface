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
require_once 'Dataface/JavascriptTool.php';
define('DATAFACE_SITE_PATH', dirname(__FILE__));
define('DATAFACE_SITE_URL', 'http://example.com/site');
define('DATAFACE_SITE_HREF', DATAFACE_SITE_URL.'/index.php');
define('DATAFACE_PATH', '..');

class JavascriptToolTest extends PHPUnit_TestCase {
	function JavascriptToolTest($name = 'JavscriptToolTest'){
		$this->PHPUnit_TestCase($name);
		$cachedir = dirname(__FILE__).DIRECTORY_SEPARATOR.'templates_c';
		if ( !file_exists($cachedir) ) mkdir($cachedir);
	}
	
	function test_compile1(){
		$js = new Dataface_JavascriptTool();
		$js->clearCache();
		$js->addPath(
			dirname(__FILE__).DIRECTORY_SEPARATOR.'JavascriptToolTest'.DIRECTORY_SEPARATOR.'js1',
			'/js1');
		$js->import('scriptA.js');
		$actual = trim($js->getContents());
		$expected = "if(typeof(window.__xatajax_included__)!='object'){window.__xatajax_included__={};};if(typeof(window.__xatajax_included__['scriptA.js'])=='undefined'){window.__xatajax_included__['scriptA.js']=true;alert('hello A');if(typeof(window.__xatajax_included__['scriptB.js'])=='undefined'){window.__xatajax_included__['scriptB.js']=true;if(typeof(window.__xatajax_included__['scriptC.js'])=='undefined'){window.__xatajax_included__['scriptC.js']=true;alert('Hello C');}
alert('hello B');}}";
		$this->assertEquals($expected, $actual) ;
		
		
	}
	
	function test_getHtml(){
		$js = new Dataface_JavascriptTool();
		$js->clearCache();
		$js->addPath(
			dirname(__FILE__).DIRECTORY_SEPARATOR.'JavascriptToolTest'.DIRECTORY_SEPARATOR.'js1',
			'/js1');
			
		$js->addPath(
			DATAFACE_PATH.'/js',
			'/dataface/js'
		);
		$js->import('scriptD.js');
		$actual = trim($js->getContents());
		$expected = "if(typeof(window.__xatajax_included__)!='object'){window.__xatajax_included__={};};if(typeof(window.__xatajax_included__['scriptD.js'])=='undefined'){window.__xatajax_included__['scriptD.js']=true;if(typeof(window.__xatajax_included__['scriptA.js'])=='undefined'){window.__xatajax_included__['scriptA.js']=true;alert('hello A');if(typeof(window.__xatajax_included__['scriptB.js'])=='undefined'){window.__xatajax_included__['scriptB.js']=true;if(typeof(window.__xatajax_included__['scriptC.js'])=='undefined'){window.__xatajax_included__['scriptC.js']=true;alert('Hello C');}
alert('hello B');}}
alert('hello D');}";
		$this->assertEquals($expected, $actual) ;
		
		$actual = trim($js->getHtml());
		$expected = trim('<script "http://example.com/site/index.php?-action=js&amp;--id=jquery.pac-8a2474b08ccf6380e458d3dcb3a1a909"></script>'."\r\n".'<script "http://example.com/site/index.php?-action=js&amp;--id=scriptD.js-a9e7eb818ca1a4aedb7d35609433bc6e"></script>');
		$this->assertEquals($expected, $actual);
		
		$js->import('jquery.packed.js');
		$actual = trim($js->getContents());
		$expected = "if(typeof(window.__xatajax_included__)!='object'){window.__xatajax_included__={};};if(typeof(window.__xatajax_included__['scriptD.js'])=='undefined'){window.__xatajax_included__['scriptD.js']=true;if(typeof(window.__xatajax_included__['scriptA.js'])=='undefined'){window.__xatajax_included__['scriptA.js']=true;alert('hello A');if(typeof(window.__xatajax_included__['scriptB.js'])=='undefined'){window.__xatajax_included__['scriptB.js']=true;if(typeof(window.__xatajax_included__['scriptC.js'])=='undefined'){window.__xatajax_included__['scriptC.js']=true;alert('Hello C');}
alert('hello B');}}
alert('hello D');}";
		$this->assertEquals($expected, $actual) ;
		
		$actual = trim($js->getHtml());
		$expected = trim('<script "http://example.com/site/index.php?-action=js&amp;--id=jquery.pac-8a2474b08ccf6380e458d3dcb3a1a909"></script>'."\r\n".'<script "http://example.com/site/index.php?-action=js&amp;--id=scriptD.js-f84b98111a897eca60344ccfb41101c1"></script>');
		$this->assertEquals($expected, $actual);
		
		
		
	}
	
	
	function test_css(){
		$css = Dataface_CSSTool::getInstance();
		$css->addPath(
			dirname(__FILE__).DIRECTORY_SEPARATOR.'JavascriptToolTest'.DIRECTORY_SEPARATOR.'css1',
			'/css1');
		$js = new Dataface_JavascriptTool();
		$js->clearCache();
		$js->addPath(
			dirname(__FILE__).DIRECTORY_SEPARATOR.'JavascriptToolTest'.DIRECTORY_SEPARATOR.'js1',
			'/js1');
			
		$js->addPath(
			DATAFACE_PATH.'/js',
			'/dataface/js'
		);
		$js->import('scriptE.js');
		$actual = trim($js->getContents());
		$expected = <<<END
if(typeof(window.__xatajax_included__)!='object'){window.__xatajax_included__={};};(function(){var headtg=document.getElementsByTagName("head")[0];if(!headtg)return;var linktg=document.createElement("link");linktg.type="text/css";linktg.rel="stylesheet";linktg.href="http://example.com/site/index.php?-action=css&--id=styleA.css-efd492ea792872aeb8bf19070f7154fa";linktg.title="Styles";headtg.appendChild(linktg);})();if(typeof(window.__xatajax_included__['scriptE.js'])=='undefined'){window.__xatajax_included__['scriptE.js']=true;alert('script E');}
END;
		$expected = trim($expected);
		$this->assertEquals($expected, $actual);
		
		$expected = '';
		$actual = $js->getHtml();
		$this->assertEquals($expected, $actual);
		
	
	}
}

if ( @$argv){
	$test = new PHPUnit_TestSuite('JavascriptToolTest');
	$result = new PHPUnit_TestResult;
	//$result->addListener(new Benchmarker());
	$test->run($result);
	
	print $result->toString();
}
