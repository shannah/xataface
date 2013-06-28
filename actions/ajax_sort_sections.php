<?php
class dataface_actions_ajax_sort_sections {

	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$tablename = $query['-table'];
		$record =& $app->getRecord();
		
		//print_r($_POST);
		$section_keys = array('--dataface-sections-left', '--dataface-sections-main');
		foreach ($section_keys as $section_key){
			
			if ( !isset($_POST[$section_key]) ) continue;
			
			$sections = explode(',',$_POST[$section_key]);
			
			import('Dataface/PreferencesTool.php');
			$pt =& Dataface_PreferencesTool::getInstance();
			
			$prefs =& $pt->getPreferences($record->getId());
			
			$sectionOrders = array();
			$lastOrder = 0;
			foreach ( $sections as $section){
				if ( isset($prefs['tables.'.$tablename.'.sections.'.$section.'.order']) ){
					$order = intval($prefs['tables.'.$tablename.'.sections.'.$section.'.order']);
					$lastOrder = $order;
				} else {
					$order = ++$lastOrder;
				}
				$sectionOrders[$section] = $order;
			}
			
			$orderVals = array_values($sectionOrders);
			sort($orderVals);
			
			$i=0;
			foreach ( array_keys($sectionOrders) as $section ){
				$sectionOrders[$section] = $orderVals[$i++];
			}
			
			
			
			$record_id = $record->getId();
			$last_order=0;
			foreach ($sectionOrders as $section=>$order){
				$order = max($last_order+1, $order);
				$pt->savePreference('*', 'tables.'.$tablename.'.sections.'.$section.'.order', $order);
				$last_order=$order;
			}
		}
			
			
		
		//$pt->savePreference($record->getId(), 'foo','bar','user1');
		//print_r($_SESSION['dataface__preferences']);
		//echo "here";
		/*
		if ( $record->checkPermission('manage_sort_sections') ){
			$prefUser = null;
		} else if ( class_exists('Dataface_AuthenticationTool') ){
			$auth =& Dataface_AuthenticationTool::getInstance();
			if ( $auth->isLoggedIn() ){
				$prefUser = $auth->getLoggedInUsername();
			} else {
				$prefUser = null;
			}
		} else {
			$prefUser = null;
		}
		
		
		$configFile = 'tables/'.$tablename.'/fields.ini';
		
		
		$sections = $_POST['--left-sections'];
		
		$ct =& Dataface_ConfigTool::getInstance();
		for ( $i=0; $i<count($sections); $i++){
			$ct->setConfigParams($configFile, 'section:'.$sections[$i], 'order', $i, $prefUser);
		}
		
		echo "Sections sorted";*/
	}
}
