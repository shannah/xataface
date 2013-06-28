<?php
class dataface_actions_my_profile {
	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		$auth =& Dataface_AuthenticationTool::getInstance();
		
		if ( $auth->isLoggedIn() ){
			// forward to the user's profile
			$user =& $auth->getLoggedInUser();
			$app->redirect($user->getURL());
			exit;
		} else {
			$app->redirect($app->url('-action=login_prompt').'&--msg='.urlencode('Sorry, this action is only available to logged in users'));
		}
	}
}
