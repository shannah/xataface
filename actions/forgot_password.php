<?php
class dataface_actions_forgot_password {
	public static $TABLE_RESET_PASSWORD = 'dataface__reset_password';
	public static $EX_MULTIPLE_USERS_WITH_SAME_EMAIL = 500;
	public static $EX_NO_USERS_WITH_EMAIL = 501;
	public static $EX_NO_EMAIL_COLUMN_FOUND = 502;
	public static $EX_NO_USERNAME_COLUMN_FOUND = 504;
	public static $EX_NO_USERNAME_FOR_USER = 503;
	public static $EX_NO_SUCH_UUID = 505;
	public static $EX_USER_NOT_FOUND = 506;
	public static $EX_NO_EMAIL_FOR_USER = 507;
	public static $EX_NO_USERS_FOUND_WITH_USERNAME = 508;
	public static $EX_MULTIPLE_USERS_WITH_SAME_USERNAME = 509;
	
	function handle($params){
		
		
		$app = Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$jt = Dataface_JavascriptTool::getInstance();
		$jt->import('forgot_password.js');
				
		try {
			if ( isset($query['--uuid']) ){
				// A uuid was supplied, 
				$res = $this->reset_password_with_uuid($query['--uuid']);
				if ( $res ){
					df_display(array(), 'xataface/forgot_password/password_has_been_reset.html');
					exit;
				} else {
					throw new Exception(df_translate('actions.forgot_password.failed_reset_for_uuid',"Failed to reset password for uuid").' '.$query['--uuid']);
				}
				
			}  else if ( isset($query['--email']) ){
				
				$this->send_reset_email_for_email($query['--email']);
				if ( @$query['--format'] == 'json' ){
					$this->response(array(
						'code'=>200,
						'message'=>df_translate('actions.forgot_password.email_sent_to_email','An email has been sent to the provided email address with instructions for resetting your password.')
					));
					exit;
				} else {
					df_display(array(), 'xataface/forgot_password/sent_email.html');
					exit;
				}
				
			
			} else if ( isset($query['--username']) ){
				
				$this->send_reset_email_for_username($query['--username']);
				if ( @$query['--format'] == 'json' ){
					$this->response(array(	
					'code'=>200,
					'message'=> df_translate('actions.forgot_password.email_sent_to_email_for_username', 'An email has been sent to the email on file for this user account with instructions for resetting the password.')
					));
					exit;
				} else {
					df_display(array(), 'xataface/forgot_password/sent_email.html');
					exit;
				}
				
				
			} else {
			
				
				df_display(array(), 'xataface/forgot_password/form.html');
				exit;
			}
		} catch ( Exception $ex ){
		
			if ( @$query['--format'] == 'json' ){
				$this->response(array(
					'code'=>$ex->getCode(),
					'message'=>$ex->getMessage()
				));
				exit;
			} else {
				df_display(array('error'=>$ex->getMessage()), 'xataface/forgot_password/form.html');
			}
		
		}
		
		
		
	}
	
	
	function response($p){
		header('Content-type: application/json; charset="'.Dataface_Application::getInstance()->_conf['oe'].'"');
		echo json_encode($p);
		
	}
	
	/**
	 * Creates th reset password table that keeps track of reset password requests.
	 */
	function create_reset_password_table(){
		$table = self::$TABLE_RESET_PASSWORD;
		$res = mysql_query("create table if not exists `{$table}` (
			request_id int(11) auto_increment primary key,
			request_uuid binary(32),
			username varchar(255),
			request_ip int(11),
			date_created datetime,
			expires int(11),
			key (request_uuid) )", df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
		
	}
	
	
	/**
	 * Deletes expired reset password requests from the table.
	 */
	function clear_expired(){
		$table = self::$TABLE_RESET_PASSWORD;
		$res = mysql_query("delete from `{$table}` where expires < ".time(), df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
		
		
	
	}
	
	
	function send_reset_email_for_username($username){
		$auth = Dataface_AuthenticationTool::getInstance();
		$usernameCol = $auth->usernameColumn;
		if ( !$usernameCol ) throw new Exception(df_translate('actions.forgot_password.no_username_column_found', "No username Column found in the users table.  Please specify one using the username_column directive in the [_auth] section of the conf.ini file."), self::$EX_NO_EMAIL_COLUMN_FOUND);
		
		$people = df_get_records_array($auth->usersTable, array($usernameCol => '='.$username));
		if ( !$people ) throw new Exception(df_translate('actions.forgot_password.username_not_found', "No account found with that username"), self::$EX_NO_USERS_FOUND_WITH_USERNAME);
		if ( count($people) > 1 ){
			throw new Exception(df_translate('actions.forgot_password.multiple_users_with_same_username', "Multiple users found with same username"), self::$EX_MULTIPLE_USERS_WITH_SAME_USERNAME);
			
		} else {
		
			$this->send_reset_email_for_user($people[0]);
			
		}
	
	}
	
	
	
	
	/**
	 * Sends reset email to a particular email address.  This first checks to see if
	 * the email address belongs to a valid user account.
	 *
	 * @param string $email The email address to send to.
	 * @return void
	 *
	 * @throws Exception Code: self::$EX_MULTIPLE_USERS_WITH_SAME_EMAIL
	 * @throws Exception Code: self::$EX_NO_USERS_WITH_EMAIL
	 * @throws Exception Code: self::$EX_NO_EMAIL_COLUMN_FOUND
	 */
	function send_reset_email_for_email($email){
		$auth = Dataface_AuthenticationTool::getInstance();
		$emailCol = $auth->getEmailColumn();
		if ( !$emailCol ) throw new Exception(df_translate('actions.forgot_password.no_email_column_found',"No Email Column found in the users table.  Please specify one using the email_column directive in the [_auth] section of the conf.ini file."), self::$EX_NO_EMAIL_COLUMN_FOUND);
		
		$people = df_get_records_array($auth->usersTable, array($emailCol => '='.$email));
		if ( !$people ) throw new Exception(df_translate('actions.forgot_password.no_account_for_email',"No account found with that email address"), self::$EX_NO_USERS_WITH_EMAIL);
		if ( count($people) > 1 ){
			throw new Exception(df_translate('actions.forgot_password.multiple_users_for_email',"Multiple users found with same email address"), self::$EX_MULTIPLE_USERS_WITH_SAME_EMAIL);
			
		} else {
			$this->send_reset_email_for_user($people[0]);
			
		}
		
		
	}
	
	/**
	 * Sends the reset email to a particular user.
	 * 
	 * @param Dataface_Record $user The user record.
	 * @return true on success
	 *
	 * @throws Exception code:  self::$EX_NO_USERNAME_FOR_USER If username is blank
	 * @throws Exception code: self::$EX_NO_EMAIL_COLUMN_FOUND No email column was found in the users table.
	 * @throws Exception code: self::$EX_NO_USERS_FOUND_WITH_EMAIL If the user record doesn't have an email address.
	 */
	public function send_reset_email_for_user(Dataface_Record $user){
		$app = Dataface_Application::getInstance();
		$auth = Dataface_AuthenticationTool::getInstance();
		$emailCol = $auth->getEmailColumn();
		$usernameCol = $auth->usernameColumn;
		
		if ( !$emailCol ) throw new Exception(df_translate('actions.forgot_password.no_email_column_found',"No Email Column found in the users table.  Please specify one using the email_column directive in the [_auth] section of the conf.ini file."), self::$EX_NO_EMAIL_COLUMN_FOUND);
		if ( !$usernameCol ) throw new Exception(df_translate('actions.forgot_password.no_username_column_found',"No username column found in the users table. Please specify one using the username_column directive in the [_auth] section of the conf.ini file."), self::$EX_NO_USERNAME_COLUMN_FOUND);
		if ( !$user ) throw new Exception(df_translate('actions.forgot_password.null_user',"Cannot send email for null user"), self::$EX_NO_USERS_FOUND_WITH_EMAIL);
		
		
		$username = $user->val($usernameCol);
		if ( !$username ){
			throw new Exception(df_translate('actions.forgot_password.user_without_name',"Cannot reset password for user without a username"), self::$EX_NO_USERNAME_FOR_USER);	
		}
		
		$email = $user->val($emailCol);
		if ( !$email ) throw new Exception(df_translate('actions.forgot_password.user_without_email',"User has not email address on file"), $EX_NO_EMAIL_FOR_USER);
		
		
		$ip = null;
		$val = ip2long($_SERVER['REMOTE_ADDR']);
		if ( $val !== false ){
			$ip = sprintf('%u', $val );
		}
		
		// Insert the entry
		$this->create_reset_password_table();
		$table = self::$TABLE_RESET_PASSWORD;
		$sql = "insert into `{$table}`
			(`request_uuid`, `username`, `request_ip`, `date_created`, `expires`)
			values
			(UUID(),'".addslashes($username)."','".addslashes($ip)."', NOW(), ".(time()+600).")";
		$res = mysql_query($sql, df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
		$id = mysql_insert_id(df_db());
		
		$res = mysql_query("select * from `{$table}` where request_id='".addslashes($id)."'", df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
		
		$row = mysql_fetch_assoc($res);
		if ( !$row ) throw new Exception(df_translate('actions.forgot_password.failed_fetch_password_row',"Failed to fetch reset password request row from database after it has been inserted.  This should never happen ... must be a bug"));
		
		$uuid = $row['request_uuid'];
		if ( !$uuid ) throw new Exception(df_translate('actions.forgot_password.blank_uuid_for_reset_request',"Blank uuid for the reset request.  This should never happen.  Must be a bug."));
		
		$url = df_absolute_url(DATAFACE_SITE_HREF.'?-action=forgot_password&--uuid='.$uuid);
		$site_url = df_absolute_url(DATAFACE_SITE_URL);
		
		$msg = df_translate('actions.forgot_password.reset_password_request_email_body',
		<<<END
You have requested to reset the password for the user '$username'.
Please go to the URL below in order to proceed with resetting your password:
<$url>

If you did not make this request, please disregard this email.
END
, array('username'=>$username, 'url'=>$url));

		$subject = df_translate('actions.forgot_password.password_reset',"Password Reset");
		
		
		$del = $app->getDelegate();
		$info = array();
		if ( isset($del) and method_exists($del, 'getResetPasswordEmailInfo') ){
			$info = $del->getResetPasswordEmailInfo($user, $url);
		}
		
		if ( isset($info['subject']) ) $subject = $info['subject'];
		if ( isset($info['message']) ) $msg = $info['message'];
		$parameters = null;
		if ( isset($info['parameters']) ) $parameters = $info['parameters'];
		
		
		
		$site_title = $app->getSiteTitle();
		$support_email = $_SERVER['SERVER_ADMIN'];
		if ( isset($app->_conf['admin_email']) ) $support_email = $app->_conf['admin_email'];
		if ( isset($app->_conf['support_email']) ) $support_email = $app->_conf['support_email'];
		
		$from_email = $support_email;
		if ( strpos($support_email, '>') === false ){
		    $from_email = $site_title.' <'.$support_email.'>';
		}
		
		$headers = 'From: '.$from_email."\r\nReply-to: ".$from_email
                        ."\r\nContent-type: text/plain; charset=".$app->_conf['oe'];
		if ( isset($info['headers']) ) $headers = $info['headers'];
		//echo "Subject: $subject \nEmail: $email \n$msg \nHeaders: $headers";exit;
		if ( @$app->_conf['_mail']['func'] ) $func = $app->_conf['_mail']['func'];
		else $func = 'mail';
		$res = $func($email,
					$subject,
					$msg,
					$headers,
					$parameters);
		if ( !$res ){
			throw new Exception(df_translate('actions.forgot_password.failed_send_activation',"Failed to send activation email.  Please try again later."), DATAFACE_E_ERROR);
		} else {
			//echo "Successfully sent mail to $email";exit;
			return true;
		}
		
		
		
	}
	
	
	
	public function reset_password_with_uuid($uuid){
		$auth = Dataface_AuthenticationTool::getInstance();
		$app = Dataface_Application::getInstance();
                $del = $app->getDelegate();
		$this->create_reset_password_table();
		$this->clear_expired();
		$table = self::$TABLE_RESET_PASSWORD;
		$res = mysql_query("select * from `{$table}` where request_uuid='".addslashes($uuid)."' limit 1", df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
		$row = mysql_fetch_assoc($res);
		if ( !$row ) throw new Exception(df_translate('actions.forgot_password.no_such_reset_request_found',"No such reset request could be found"), self::$EX_NO_SUCH_UUID);
		
		if ( !$row['username'] ){
			throw new Exception(df_translate('actions.forgot_password.attempt_to_reset_for_null_username',"Attempt to reset password for user with null username"), self::$EX_NO_USERNAME_FOR_USER);
			
		}
		$username = $row['username'];
		
		
		
		
		@mysql_free_result($res);
		
		
		
		// now that we have the username, let's reset the password.
		//$rand = strval(rand())."".$uuid;
		$rand = md5($uuid);
		error_log("Rand is ".$rand);
		$pw = '';
		for ( $i=0; $i<=16; $i+=2 ){
			$pw .= $rand{$i};
		}
		$password = $pw;
                if ( isset($del) and method_exists($del, 'generateTemporaryPassword')){
                    $pw = $del->generateTemporaryPassword();
                    if ( $pw ){
                        $password = $pw;
                    }
                }
		//error_log("Password is $password");
		$user = df_get_record($auth->usersTable, array($auth->usernameColumn => '='.$username));
		if ( !$user ){
			throw new Exception(df_translate('actions.forgot_password.no_account_for_username',"No user account found with that username"), self::$EX_USER_NOT_FOUND);
			
		}
		$emailColumn = $auth->getEmailColumn();
		if ( !$emailColumn ) throw new Exception(df_translate('actions.forgot_password.no_email_column_found_short',"No email column found in the users table"), self::$EX_NO_EMAIL_COLUMN_FOUND);
		$email = $user->val($emailColumn);
		
		if ( !$email ){
			throw new Exception(df_translate('actions.forgot_password.user_without_email_long',"User has account has no email address on record.  Please contact support to reset the password"), self::$EX_NO_EMAIL_FOR_USER);
			
		}
		
		
		$user->setValue($auth->passwordColumn, $password);
		$res = $user->save();
		if ( PEAR::isError($res) ){
			throw new Exception($res->getMessage());
		}
		
		
		// Let's delete this request from the password reset requests.
		$this->delete_request_with_uuid($uuid);
		
		// Now let's send the email.
		$del = $app->getDelegate();
		$info = array();
		if ( isset($del) and method_exists($del, 'getPasswordChangedEmailInfo') ){
			$info = $del->getPasswordChangedEmailInfo($user, $password);
		}
		
		$subject = df_translate('actions.forgot_password.password_changed',"Password Changed");
		if ( isset($info['subject']) ) $subject = $info['subject'];
		
		
		$site_url = df_absolute_url(DATAFACE_SITE_HREF);
		
		$msg = df_translate('actions.forgot_password.new_temporary_password_email_body',
		<<<END
Your new temporary password is
$password

You can change your password as follows:

1. Log in with your temporary password at <$site_url?-action=login>
2. Click on the "My Profile" link in the upper right of the page
3. Click on the "Edit" tab.
4. Change your password in the edit form and click "Save" when done.
END
, array('password'=>$password, 'site_url'=>$site_url));

		if ( isset($info['message']) ) $msg = $info['message'];
		
		$parameters = null;
		if ( isset($info['parameters']) ) $parameters = $info['parameters'];
		
		
		
		$site_title = $app->getSiteTitle();
		$support_email = $_SERVER['SERVER_ADMIN'];
		if ( isset($app->_conf['admin_email']) ) $support_email = $app->_conf['admin_email'];
		if ( isset($app->_conf['support_email']) ) $support_email = $app->_conf['support_email'];
		
		$headers = 'From: '.$site_title.' <'.$support_email.'>'."\r\nReply-to: ".$site_title." <".$support_email.">"
                        ."\r\nContent-type: text/plain; charset=".$app->_conf['oe'];
		if ( isset($info['headers']) ) $headers = $info['headers'];
		
		
		if ( @$app->_conf['_mail']['func'] ) $func = $app->_conf['_mail']['func'];
		else $func = 'mail';
		$res = $func($email,
					$subject,
					$msg,
					$headers,
					$parameters);
		if ( !$res ){
			return PEAR::raiseError(df_translate('actions.forgot_password.failed_send_activation',"Failed to send activation email.  Please try again later."), DATAFACE_E_ERROR);
		} else {
			return true;
		}
		
		
		
		
	}
	
	function delete_request_with_uuid($uuid){
		$table = self::$TABLE_RESET_PASSWORD;
		$res = mysql_query("delete from `{$table}` where request_uuid='".addslashes($uuid)."' limit 1", df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
		
	}
}
