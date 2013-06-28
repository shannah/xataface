<?php
class dataface_actions_ajax_get_permissions {

	function handle($params){
	
		session_write_close();
		header('Connection:close');
		
		$app = Dataface_Application::getInstance();
		$query = $app->getQuery();
		
		if ( @$query['--id'] ){
			$table = Dataface_Table::loadTable($query['-table']);
			$keys = array_keys($table->keys());
			if ( count($keys) > 1 ){
				throw new Exception("Table has compound key so its permissions cannot be retrieved with the --id parameter.");
			}
			$query[$keys[0]] = '='.$query['--id'];
			$record = df_get_record($query['-table'], $query);
		} else {
		
			$record = $app->getRecord();
		}
		$perms = array();
		if ( $record ) $perms = $record->getPermissions();
		
		header('Content-type: application/json; charset="'.$app->_conf['oe'].'"');
		$out = json_encode($perms);
		header('Content-Length: '.strlen($out));
		echo $out;
		flush();
	}
}
