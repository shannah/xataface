<?php
class dataface_actions_getBlob {

	function handle(&$params){
	
		$app =& Dataface_Application::getInstance();
		if (!@$app->_conf['blob_keep_sessions_open']){
			@session_write_close();
		}
		$query =& $app->getQuery();
		$app->_handleGetBlob($query);
		exit;
	}
}

?>
