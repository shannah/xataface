<?php

class dataface_actions_sync_bindings {
	function handle(&$params){
		
		if ($_POST) {
			error_reporting(E_ALL);
			ini_set('display_errors','on');
			import(XFROOT.'xf/db/Binding.php');
			xf\db\Binding::updateAllBindings();
			echo "All bindings have been synchronized";
		} else {
			Dataface_JavascriptTool::getInstance()->import('xataface/actions/sync_bindings.js');
			df_display(array(), 'xataface/actions/sync_bindings.html');
		}
	
		
	}

}
