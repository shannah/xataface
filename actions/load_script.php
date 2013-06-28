<?php
class dataface_actions_load_script {


	function handle($params){
		session_write_close();
		$app = Dataface_Application::getInstance();
		$expires = 60*60*72;
		try {
		
			
			
			$query = $app->getQuery();
			
			$script = @$query['--script'];
			if ( !$script ){
				throw new Exception("Script could not be found", 404);
			}
			
			$scripts = explode(',', $script);
			
			$jt = Dataface_JavascriptTool::getInstance();
			
			$jt->clearScripts();
			$app->fireEvent('beforeLoadScript');
			foreach ($scripts as $script){
				$script = trim($script);
	
				//echo '['.$script.']';exit;
				$script = $this->sanitizePath($script);
				
				$jt->import($script);
			}
			
			header('Connection:close');
			$conf = Dataface_Application::getInstance()->_conf;
			$conf = @$conf['Dataface_JavascriptTool'];
			if ( !$conf ) $conf = array();
			if ( !@$conf['debug'] ){
				header("Pragma: public", true);
				header("Cache-Control:max-age=".$expires.', public, s-maxage='.$expires, true);
				header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT', true);
			}
			header('Content-type: text/javascript; charset="'.$app->_conf['oe'].'"');
			
			$out = $jt->getContents();
			header('Content-Length: '.strlen($out));
			echo $out;
			flush();
			
		} catch (Exception $ex){
			
			
			header('Content-type: text/javascript; charset="'.$app->_conf['oe'].'"');
			$out = 'console.log('.json_encode($ex->getMessage()).');';
			header('Content-Length: '.strlen($out));
			echo $out;
			flush();
			
		}
		
		
		
		
	}
	
	
	function sanitizePath($path){
		
		$parts = explode('/', $path);
		foreach ($parts as $part){
			if ( strpos($part, '\\') !== false ) throw new Exception("Illegal backslash in path.");
			if ( preg_match('/\s/', $part) ) throw new Exception("Illegal white space in path.");
			if ( $part == '..' ) throw new Exception("Illegal .. in path");
			
		}
		$path = implode('/', $parts);
		if ( $path{0} == '/' ) throw new Exception("Absolute paths not supported");
		return $path;
	
	}
}
