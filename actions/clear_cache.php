<?php
if ( !function_exists('scandir') ){
	function scandir($dir, $sortorder = 0 ){
		if ( is_dir($dir) && $dirlist = @opendir($dir)) {
			while (($file = readdir($dirlist)) !== false ){
				$files[] = $file;
			}
			closedir($dirlist);
			($sortorder == 0) ? asort($files) : rsort($files);
			return $files;
		} else return false;
	}
}

class dataface_actions_clear_cache {
	function handle(&$params){
		$templates_dirs = array(
			DATAFACE_SITE_PATH.'/templates_c',
			DATAFACE_PATH.'/templates_c'
			);
		foreach ( $templates_dirs as $f ){
			if ( is_dir($f) ){
				foreach ( scandir($f) as $dir ){
					if ( $dir == '.' or $dir == '..' ) continue;
					$this->deltree($f.'/'.$dir);
				}
			}
		}
	}
	
	function deltree( $f ){
		if ( is_dir($f) ){
			foreach (scandir($f) as $item){
				if ( $item == '.' or $item == '..') continue;
				$this->deltree($f.'/'.$item);
			}
			rmdir($f);
		} else {
			unlink($f);
		}
	
	}
}
