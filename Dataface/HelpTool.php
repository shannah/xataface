<?php

class Dataface_HelpTool {


	var $contents;
	
	public static function &getInstance(){
		static $instance = 0;
		if ( !is_object($instance) ) $instance = new Dataface_HelpTool();
		return $instance;
	}
	
	function getContents($path=null){
		if ( !isset($this->contents) ){
			$this->contents = array('Users'=>array(),'Administrators'=>array(),'Developers'=>array());

		}
		
	}
	
	function createSection($name, $url, $path, $description='', $target='User'){
		return array('name'=>$name,'url'=>$url,'path'=>$path,'description'=>$description,'target'=>$target);
	}
	
	function buildSection($path, $url){
		if ( is_readable($path) and is_file($path) ){
			$name = ucwords(str_replace('_',' ',basename($path)));
			$description = '';
			$target = 'User';
			
			$fh = fopen($path,'r');
			$beginning = fread($fh, 1024);
			fclose($fh);

			
			
			if ( preg_match('/<title>(.*?)</title>/i', $beginning, $matches) ){
				$name = $matches[1];
			}
			if ( preg_match('/<meta name="description" content="([^"]+)".*?>/is', $beginning, $matches) ){
				$description = $matches[1];
			}
			if ( preg_match('/<meta name="target-audience" content="([^"]+)".*?>/is', $beginning, $matches) ){
				$target = $matches[1];
			}
				
			return $this->createSection($name, $url, $path, $description, $target); 
			
		} else {
			return null;
		}
		
	}
	
	function getDocRootForLanguage($docRoot, $lang=null){
		if ( !isset($lang) ){
			$app =& Dataface_Application::getInstance();
			if ( isset($app->_conf['lang']) ) $lang = $app->_conf['lang'];
		}
		if (is_dir($docRoot.'/'.$lang) and is_readable($docRoot.'/'.$lang) ){
			return $docRoot.'/'.$lang;
		}
		return $docRoot;
	}
	
	function getModuleDocRoot($moduleName, $lang=null){
		if ( !isset($lang) ){
			$app =& Dataface_Application::getInstance();
			if ( isset($app->_conf['lang']) ) $lang = $app->_conf['lang'];
		}
		$moduleName2 = preg_replace('/^modules_/','',$moduleName);
		$docsDir = DATAFACE_PATH.'modules/'.$moduleName2.'/docs';
		return $this->getDocRootForLanguage($docsDir, $lang);
	}
	
	function getTableDocRoot($tablename, $lang=null){
		if ( !isset($lang) ){
			$app =& Dataface_Application::getInstance();
			if ( isset($app->_conf['lang']) ) $lang = $app->_conf['lang'];
		}
		$docsDir = DATAFACE_SITE_PATH.'tables/'.$tablename.'/docs';
		return $this->getDocRootForLanguage($docsDir, $lang);
	}
	
	function getApplicationDocRoot($lang=null){
		if ( !isset($lang) ){
			$app =& Dataface_Application::getInstance();
			if ( isset($app->_conf['lang']) ) $lang = $app->_conf['lang'];
		}
		$docsDir = DATAFACE_SITE_PATH.'/docs';
		return $this->getDocRootForLanguage($docsDir, $lang);
	}
	
	
	function getDatafaceDocRoot($lang=null){
		if ( !isset($lang) ){
			$app =& Dataface_Application::getInstance();
			if ( isset($app->_conf['lang']) ) $lang = $app->_conf['lang'];
		}
		$docsDir = DATAFACE_PATH.'/docs';
		return $this->getDocRootForLanguage($docsDir, $lang);
	}
	
	
	function getContents($path){
		
	}
	
	
	
	
	
	

}
