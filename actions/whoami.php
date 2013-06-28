<?php
class dataface_actions_whoami {
	function handle($params){
		$app = Dataface_Application::getInstance();
		$user = Dataface_AuthenticationTool::getInstance()
			->getLoggedInUser();
		$username = Dataface_AuthenticationTool::getInstance()
			->getLoggedInUserName();
			
		if ( !isset($user) ){
			header('HTTP/1.0 401 Please Login');
			header('Content-type: text/json; charset="'.$app->_conf['oe'].'"');
			echo json_encode(array(
				'code' => 401,
				'message' => 'You are not logged in'
			));
			exit;
		} else {
			 header('Content-type: text/json; charset="'.$app->_conf['oe'].'"');
			echo json_encode(array(
				'code' => 200,
				'message' => 'You are not logged in as '.$username,
				'username' => $username
			));
			exit;
		}
	}
}
