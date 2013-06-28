<?php
import('Dataface/JavascriptTool.php');
class actions_xatadoc {
	function handle($params){
		$app = Dataface_Application::getInstance();
		$conf =& $app->_conf;
		$js = Dataface_JavascriptTool::getInstance();
		$js->setMinify(false);
		$js->setUseCache(false);
		
		if ( isset($conf['_xatadoc']) ){
			foreach ($conf['_xatadoc_path'] as $f=>$enabled ){
				if ( $enabled ){
					$js->addPath($f);
				}
			}
		}
		
		//print_r($this->getPaths());exit;
		foreach ($this->getPaths() as $p){
			$this->crawlPath($p);
		}
		
		$js->import('actions/xatadoc.js');
		df_register_skin('xatajax', XATAJAX_PATH.DIRECTORY_SEPARATOR.'templates');
		df_display(array(), 'xatadoc.html');
		
	}
	
	
	function getPaths(){
		$js = Dataface_JavascriptTool::getInstance();
		return array_keys($paths = $js->getPaths());
		
	}
	
	
	function crawlPath($root, $path=null){
		$js = Dataface_JavascriptTool::getInstance();
		if ( isset($path) ){
			$dirpath = $root.DIRECTORY_SEPARATOR.$path;
			$docpath = $path.DIRECTORY_SEPARATOR.'__doc__.js';
			$fullpath = $root.DIRECTORY_SEPARATOR.$docpath;
		} else {
			$dirpath = $root;
			$docpath = '__doc__.js';
			$fullpath = $root.DIRECTORY_SEPARATOR.$docpath;
		}
		
		if ( !is_readable($dirpath) ){
			return;
		}
		
		if ( is_readable($fullpath) ){
			$js->import($docpath);
		}
		
		$files = scandir($dirpath);
		foreach ($files as $f){
			if ( strpos($f, '.') === 0 ) continue;
			$abspath = $dirpath.DIRECTORY_SEPARATOR.$f;
			if ( is_dir($abspath) ){
				if (isset($path) ){
					$this->crawlPath($root, $path.DIRECTORY_SEPARATOR.$f);
				} else {
					$this->crawlPath($root, $f);
				}
			}
			
		}
		
	}
}
