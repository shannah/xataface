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
    
    
    function handle($params) {
        $this->clear_cache($params);
        header('Content-type: application/json; charset=UTF-8');
        echo json_encode(array('code' => 200, 'message' => 'success'));
    }
    
	function clear_cache(&$params){
        $liveCache = XFTEMPLATES_C;
        if ($liveCache[strlen($liveCache)-1] == DIRECTORY_SEPARATOR) {
            $liveCache = substr($liveCache, 0, strlen($liveCache)-1);
        }
        
        //echo $liveCache;exit;
        $app = Dataface_Application::getInstance();
        $query = $app->getQuery();
        //print_r($query);exit;
        import(XFROOT.'Dataface/Table.php');
        $table = Dataface_Table::loadTable($query['-table']);
        //print_r($table);exit;
        
        self::rrmdir($liveCache, array('.htaccess'), false);
        
        import(XFROOT.'actions/clear_views.php');
        $clearViews = new dataface_actions_clear_views();
        $clearViews->clear_views();
        
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        if ( function_exists('apc_clear_cache') ){
            apc_clear_cache('user');
        }
        
        // Output Cache
        @xf_db_query("truncate table `__output_cache`", df_db());
        
        
	}
	
    static function rrmdir($dir, $excludes=array(), $deleteRootDir=false) { 
        $sep = DIRECTORY_SEPARATOR;
       if (is_dir($dir)) { 
         $objects = scandir($dir); 
         foreach ($objects as $object) { 
            if (in_array($object, $excludes)) {
                continue;
            }
           if ($object != "." && $object != "..") { 
             if (is_dir($dir.$sep.$object))
               self::rrmdir($dir.$sep.$object, $excludes, true);
             else
               unlink($dir.$sep.$object); 
           } 
         }
         if ($deleteRootDir) {
            rmdir($dir); 
         }
       } 
    }
}
