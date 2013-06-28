<?php
define('XATAJAX_URL', DATAFACE_URL.'/modules/XataJax');
define('XATAJAX_PATH', DATAFACE_PATH.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR.'XataJax');
function xj_json_response($data){
	header('Content-type: text/json; charset="'.Dataface_Application::getInstance()->_conf['oe'].'"');
	echo json_encode($data);
}

import('Dataface/JavascriptTool.php');
import('Dataface/CSSTool.php');
class modules_XataJax {
	
	public function __construct(){
	
	
		
	
		$type = null;
		if ( @$_GET['-xatadoc'] ){
			import('modules/XataJax/classes/JavascriptDocumentor.php');
			$type = 'JavascriptDocumentor';
		}
		$js = Dataface_JavascriptTool::getInstance($type);
		
		$conf = Dataface_Application::getInstance()->_conf;
		$conf = @$conf['Dataface_JavascriptTool'];
		if ( !$conf ) $conf = array();
		if ( @$conf['debug'] ){
			$js->setMinify(false);
			$js->setUseCache(false);
		}
		
		$js->addPath(dirname(__FILE__).DIRECTORY_SEPARATOR.'js', XATAJAX_URL.'/js');
		$js->addPath(DATAFACE_SITE_PATH.DIRECTORY_SEPARATOR.'js', DATAFACE_SITE_URL.'/js');
		$js->addPath(DATAFACE_PATH.DIRECTORY_SEPARATOR.'js', DATAFACE_URL.'/js');
		
		$css = Dataface_CSSTool::getInstance();
		$css->addPath(dirname(__FILE__).DIRECTORY_SEPARATOR.'css', XATAJAX_URL.'/css');
		$css->addPath(DATAFACE_SITE_PATH.DIRECTORY_SEPARATOR.'css', DATAFACE_SITE_URL.'/css');
		$css->addPath(DATAFACE_PATH.DIRECTORY_SEPARATOR.'css', DATAFACE_URL.'/css');
		
		
	}
	
	

	public function block__after_global_footer(){
		
		$js = Dataface_JavascriptTool::getInstance();
		$used = false;
		if ( $js->getScripts() ){
			echo $js->getHtml();
			$used = true;
		}
		
		
		
		return $used;
	}
	
	public function block__javascript_tool_includes(){
		return $this->block__after_global_footer();
	}
}
