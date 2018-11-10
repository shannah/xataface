<?php
class dataface_actions_clear_templates_c {
    function handle($params) {
        $liveCache = DATAFACE_SITE_PATH . DIRECTORY_SEPARATOR . "templates_c";
        //echo $liveCache;exit;
        $app = Dataface_Application::getInstance();
        $query = $app->getQuery();
        //print_r($query);exit;
        $table = Dataface_Table::loadTable($query['-table']);
        //print_r($table);exit;
        $perms = Dataface_PermissionsTool::getPermissions($table);
        if (!Dataface_PermissionsTool::checkPermission('clear views', $perms)) {
            die("Only admins allowed to perform this action.");
        }
        
        self::rrmdir($liveCache, array('.htaccess'), false);
        header('Content-type: application/json; charset=UTF-8');
        echo json_encode(array('code' => 200, 'message' => 'success'));
        
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