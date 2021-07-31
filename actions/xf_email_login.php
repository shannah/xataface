<?php

class dataface_actions_xf_email_login {
    function handle($params) {
        $app = Dataface_Application::getInstance();
        $query = $app->getQuery();
        import(XFROOT.'Dataface/AuthenticationTool.php');
        $auth = Dataface_AuthenticationTool::getInstance();
        
        
        
        if ($_POST) {
            if (!$auth->isEmailLoginAllowed()) {
                $this->out(['code' => 500, 'message' => 'Email login is currently disabled.']);
                exit;
            }
            
            $email = @$_POST['--email'];
            $redirectUrl = @$_POST['--redirectUrl'];
            if ($auth->isLoggedIn()) {
                $this->out(['code' => 501, 'message' => 'You are already logged in.']);
                exit;
            }
            
            if (!$email) {
                $this->out(['code' => 500, 'message' => 'Invalid request']);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->out(['code' => 599, 'message' => 'Invalid Email Address']);
                exit;
            }
            
    		$site_title = $app->getSiteTitle();
    		$support_email = $_SERVER['SERVER_ADMIN'];
    		if ( isset($app->_conf['admin_email']) ) $support_email = $app->_conf['admin_email'];
    		if ( isset($app->_conf['support_email']) ) $support_email = $app->_conf['support_email'];
		
    		$from_email = $support_email;
    		if ( strpos($support_email, '>') === false ){
    		    $from_email = $site_title.' <'.$support_email.'>';
    		}
		
    		$headers = 'From: '.$from_email."\r\nReply-to: ".$from_email
                            ."\r\nContent-type: text/html; charset=".$app->_conf['oe'];
    		
        
            
            $tok = $auth->createLoginToken($email, $redirectUrl);
            $shortTok = null;
            if (!empty($tok) and !empty($query['--request-login-code']) and ($query['--request-login-code'] == '1' or $query['--request-login-code'] == 'true') and !empty($auth->conf['short_token_length'])) {
                $shortTok = substr(md5($tok), 0, intval($auth->conf['short_token_length']));
            }
            
            
            if (!$tok) {
                // No token was created for this email address, meaning that likely there is no
                // matching email address on record.
                
                // For security reasons we should not disclose this, but the email we send should 
                // link to the registration form instead.
                
                if (@$app->_conf['_auth']['allow_register']) {
                    if (@$app->_conf['_auth']['auto_register']) {
                        // We will automatically register this user
                        $values = [];
                        $values[$auth->getEmailColumn()] = $email;
                        $values[$auth->usernameColumn] = $email;
                        
                        $existingUser = df_get_record($auth->usersTable, [$auth->getEmailColumn() => '='.$email]);
                        if ($existingUser) {
                            $logId = df_error_log("Trying to auto register user with email $email but account already exists");
                            $this->out(['code' => 200, 'message' => 'An login link has been sent to '.$email, 'logId' => $logId]);
                            exit;
                        }
                        $existingUser = df_get_record($auth->usersTable, [$auth->usernameColumn => '='.$email]);
                        if ($existingUser) {
                            $logId = df_error_log("Trying to auto register user with email $email but account already exists with this username");
                            $this->out(['code' => 200, 'message' => 'An login link has been sent to '.$email, 'logId' => $logId]);
                            exit;
                        }
                        
                        import(XFROOT.'xf/registration/createActivationLink.func.php');
                        $url = df_absolute_url(xf\registration\createActivationLink($values));
                        $loginMessage = 'Click here to create an account';
                        $registerMessage = '<p style="color:#1a1a1a;font-size:16px;line-height:26px;margin:0 0 1em 0;text-align:center">Someone requested a login link for <a href="'.htmlspecialchars(df_absolute_url(DATAFACE_SITE_HREF)).'">'.htmlspecialchars($site_title).'</a> at this email address, but we couldn\'t find an account.</p>';
                        
                    } else {
                        $url = df_absolute_url(DATAFACE_SITE_HREF.'?-action=register&email='.urlencode($email));
                        $loginMessage = 'Click here to create an account';
                        $registerMessage = '<p style="color:#1a1a1a;font-size:16px;line-height:26px;margin:0 0 1em 0;text-align:center">Someone requested a login link for <a href="'.htmlspecialchars(df_absolute_url(DATAFACE_SITE_HREF)).'">'.htmlspecialchars($site_title).'</a> at this email address, but we couldn\'t find an account.</p>';
                    }
                    
                } else {
                    // Registration is disallowed, but for security reasons we don't want to confirm nor deny that 
                    // the server has an account with this email address so we'll report that a login link was sent
                    // even though no login link was sent.
                    $logId = df_error_log("Login link requested for non-existent account ".$email." not sent because allow_register is off");
                    $this->out(['code' => 200, 'message' => 'An login link has been sent to '.$email, 'logId' => $logId]);
                    exit;
                }
                
                
            } else {
                $url = df_absolute_url(DATAFACE_SITE_HREF.'?-action=login&--token='.urlencode($tok));
                $loginMessage = 'Click here to log in';
                $registerMessage = '';
            }
            
            $subject = 'Log in to '.$app->getSiteTitle();
            
            $shortTokMessage = '';
            
            if (!empty($shortTok)) {
                $msg = '<html><body>'.$registerMessage.'<p style="color:#1a1a1a;font-size:16px;line-height:26px;margin:0 0 1em 0;text-align:center"><span style="background-color:#000080;border:solid #000080;border-radius:4px;border-width:12px 20px;box-sizing:content-box;color:#ffffff;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif,\'Apple Color Emoji\',\'Segoe UI Emoji\',\'Segoe UI Symbol\';font-size:16px;height:auto;line-height:1em;margin:0;opacity:1;outline:none;padding:0;text-decoration:none!important" >'.htmlspecialchars('Your login code is '.$shortTok).'</span></p></body></html>';
            } else {
                $msg = '<html><body>'.$registerMessage.'<p style="color:#1a1a1a;font-size:16px;line-height:26px;margin:0 0 1em 0;text-align:center"><a href="'.htmlspecialchars($url).'" style="background-color:#000080;border:solid #000080;border-radius:4px;border-width:12px 20px;box-sizing:content-box;color:#ffffff;display:inline-block;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif,\'Apple Color Emoji\',\'Segoe UI Emoji\',\'Segoe UI Symbol\';font-size:16px;height:auto;line-height:1em;margin:0;opacity:1;outline:none;padding:0;text-decoration:none!important" >'.htmlspecialchars($loginMessage).'</a></p></body></html>';
            }
            
            
            
    		$event = new StdClass;
    		$event->email = $email;
            $event->subject = $subject;
            $event->message = array('text/html' => $msg);
            $event->headers = $headers;
            $event->parameters = [];
    		$event->consumed = false;
            $app->fireEvent('mail', $event);
        
            if ($event->consumed) {
                $res = @$event->out;
            } else {
        		if ( @$app->_conf['_mail']['func'] ) $func = $app->_conf['_mail']['func'];
        		else $func = 'mail';
        		$res = $func($email,
        					$subject,
        					$msg,
        					$headers,
                            '');
            }
		
        
		
    		if ( !$res ){
    			$this->out(['code' => 503, 'message' => 'Failed to send email.']);
    		} else {
    			//echo "Successfully sent mail to $email";exit;
                $this->out(['code' => 200, 'message' => 'An login link has been sent to '.$email]);
    		}
            
            
        } else {
            if ($auth->isLoggedIn()) {
                $app->redirect(DATAFACE_SITE_HREF.'?--msg='.urlencode('You are already logged in'));
                exit;
            }
            
        }
    }
    
    function out($data) {
        $app = Dataface_Application::getInstance();
        header('Content-type: application/json; charset="'.$app->_conf['oe'].'"');
        echo json_encode($data);
        exit;
    }
}
?>