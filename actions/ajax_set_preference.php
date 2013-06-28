<?php
class dataface_actions_ajax_set_preference {
	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		$record =& $app->getRecord();
		$out = array();
		if ( !isset($_POST['--name']) ){
			$out['error'] = 'No name specified';
			$this->respond($out);
			exit;
		}
		
		if ( !isset($_POST['--value']) ){
			$out['error'] = 'No value specified';
			$this->respond($out);
			exit;
		}
		
		if ( isset($_POST['--record_id']) ){
			$recordid = $_POST['--record_id'];
		} else {
			$recordid = $recordd->getId();
		}
		
		import('Dataface/PreferencesTool.php');
		$pt =& Dataface_PreferencesTool::getInstance();
		
		$pt->savePreference($recordid, $_POST['--name'], $_POST['--value']);
		$out['message'] = 'Successfully saved preference '.$_POST['--name'].' to '.$_POST['--value'].' for '.$recordid;
		$this->respond($out);
		
	}
	
	function respond($out){
		import('Services/JSON.php');
		$json = new Services_JSON;
		
		header('Content-type: application/json');
		echo $json->encode($out);
		exit;
		
	}
}
