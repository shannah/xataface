<?php
class dataface_actions_change_password {
	function handle($params){
	
		$app = Dataface_Application::getInstance();
		$auth = Dataface_AuthenticationTool::getInstance();
		$user = $auth->getLoggedInUser();
		$username = $auth->getLoggedInUsername();
		
		if ( !$user or !$username ){
			return Dataface_Error::permissionDenied('You must be logged in to change your password');
		}
		
		if ( $_POST ){
		
			try {
			
				if ( !@$_POST['--password1'] || !@$_POST['--password2'] ){
					throw new Exception("Please enter your new password in both fields provided.");
				}
				
				if ( !@$_POST['--current-password'] ){
					throw new Exception("Please enter your current password in the field provided.");
					
				}
				
				$_REQUEST['UserName'] = $username;
				$_REQUEST['Password'] = $_POST['--current-password'];
				
				if ( !$auth->checkCredentials() ){
					throw new Exception("The password you entered is incorrect.  Please try again.");
				}
				
				if ( strcmp($_POST['--password1'], $_POST['--password2'])!==0 ){
					throw new Exception("Your new passwords don't match.  Please ensure that you retype your new password correctly.");
					
				}
				
				$res = $auth->setPassword($_POST['--password1']);
				
				$this->out(array(
					'code'=>200,
					'message'=>'Your password has been successfully changed'
				));
				exit;
			} catch (Exception $ex){
				$this->out(array(
					'code'=> $ex->getCode(),
					'message'=>$ex->getMessage()
				));
				exit;
			}
		
		} else {
		
			$jt = Dataface_JavascriptTool::getInstance();
			$jt->import('change_password.js');
			
			df_display(array(), 'change_password.html');
		}
		
		
		
	}
	
	
	function out($params){
		header('Content-type: application/json; charset="'.Dataface_Application::getInstance()->_conf['oe'].'"');
		echo json_encode($params);
	}
}
