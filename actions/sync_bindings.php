<?php
error_reporting(E_ALL);
ini_set('display_errors','on');
class dataface_actions_sync_bindings {
	function handle(&$params){
		import(XFROOT.'xf/db/Binding.php');
		xf\db\Binding::updateAllBindings();
		echo "All bindings have been synchronized";
	}

}
