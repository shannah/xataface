<?php
class dataface_actions_xf_my_profile {
    function handle($params) {
        import('xf/core/XFException.php');
		$app = Dataface_Application::getInstance();
		$user = Dataface_AuthenticationTool::getInstance()
			->getLoggedInUser();
        

        
		$username = Dataface_AuthenticationTool::getInstance()
			->getLoggedInUserName();
        
        
        if (!$user) {
            return Dataface_Error::permissionDenied("You must be logged in");
        }
        
        $url = $user->getPublicLink();
        $app->redirect("$url");
        exit;
        
    }
}
?>