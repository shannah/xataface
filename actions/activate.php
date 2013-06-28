<?php
/**
 * An activate action.  This is the 2nd half of the registration action
 * when email activation is turned on.  It accepts a parameter 'code' 
 * that contains a 32 character code which is used to verify that 
 * a user is who he says he is.
 */
class dataface_actions_activate {
	function handle(&$params){
		$app = Dataface_Application::getInstance();
		
		if ( !isset($_GET['code']) ){
			// We need this parameter or we can do nothing.
			return PEAR::raiseError(
				df_translate('actions.activate.MESSAGE_MISSING_CODE_PARAMETER',
					'The code parameter is missing from your request.  Validation cannot take place.  Please check your url and try again.'
					),
				DATAFACE_E_ERROR
				);
		}
		
		// Step 0:  Find out what the redirect URL will be
		// We accept --redirect markers to specify which page to redirect
		// to after we're done.  This will usually be the page that the
		// user was on before they went to the login page.
		if ( isset($_SESSION['--redirect']) ) $url = $_SESSION['--redirect'];
		else if ( isset($_SESSION['-redirect']) ) $url = $_SESSION['-redirect'];
		else if ( isset($_REQUEST['--redirect']) ) $url = $_REQUEST['--redirect'];
		else if ( isset($_REQUEST['-redirect']) ) $url = $_REQUEST['-redirect'];
		else $url = $app->url('-action='.$app->_conf['default_action']);
		if ( strpos($url, '?') === false ){
            $url .= '?';
        }
		
		// Step 1: Delete all registrations older than time limit
		$time_limit = 24*60*60; // 1 day
		if ( isset($params['time_limit']) ){
			$time_limit = intval($params['time_limit']);
		}
		
		$res = mysql_query(
			"delete from dataface__registrations 
				where registration_date < '".addslashes(date('Y-m-d H:i:s', time()-$time_limit))."'",
			df_db()
			);
		if ( !$res ){
			error_log(mysql_error(df_db()));
			throw new Exception("Failed to delete registrations due to an SQL error.  See error log for details.", E_USER_ERROR);
			
		}
		
		// Step 2: Load the specified registration information
		
		$res = mysql_query(
			"select registration_data from dataface__registrations
				where registration_code = '".addslashes($_GET['code'])."'",
			df_db()
			);
		
		if ( !$res ){
			error_log(mysql_error(df_db()));
			throw new Exception("Failed to load registration information due to an SQL error.  See error log for details.", E_USER_ERROR);
			
		}
		
		if ( mysql_num_rows($res) == 0 ){
			// We didn't find any records matching the prescribed code, so
			// we redirect the user to their desired page and inform them
			// that the registration didn't work.
			$msg = df_translate(
				'actions.activate.MESSAGE_REGISTRATION_NOT_FOUND',
				'No registration information could be found to match this code.  Please try registering again.'
				);
			$app->redirect($url.'&--msg='.urlencode($msg));

		}
		
		// Step 3: Check to make sure that there are no other users with the
		// same name.
		
		list($raw_data) = mysql_fetch_row($res);
		$values = unserialize($raw_data);
		$appdel = $app->getDelegate();
		if ( isset($appdel) and method_exists($appdel, 'validateRegistrationForm') ){
			$res = $appdel->validateRegistrationForm($values);
			if ( PEAR::isError($res) ){
				$msg = $res->getMessage();
				$app->redirect($url.'&--msg='.urlencode($msg));
			}
		} else {
			$res = mysql_query("select count(*) from 
				`".str_replace('`','',$app->_conf['_auth']['users_table'])."` 
				where `".str_replace('`','',$app->_conf['_auth']['username_column'])."` = '".addslashes($values[$app->_conf['_auth']['username_column']])."'
				", df_db());
			if ( !$res ){
				error_log(mysql_error(df_db()));
				throw new Exception("Failed to find user records due to an SQL error.  See error log for details.", E_USER_ERROR);
				
			}
			list($num) = mysql_fetch_row($res);
			if ( $num > 0 ){
				$msg = df_translate(
					'actions.activate.MESSAGE_DUPLICATE_USER',
					'Registration failed because a user already exists by that name.  Try registering again with a different name.'
					);
				$app->redirect($url.'&--msg='.urlencode($msg));
			}
		}
		
		
		// Step 4: Save the registration data and log the user in.
		$record = new Dataface_Record($app->_conf['_auth']['users_table'], array());
		$record->setValues($values);
		$res = $record->save();
		if ( PEAR::isError($res) ){
			$app->redirect($url.'&--msg='.urlencode($res->getMessage()));
		} else {
			$res = mysql_query(
				"delete from dataface__registrations
					where registration_code = '".addslashes($_GET['code'])."'",
				df_db()
				);
			
			if ( !$res ){
				error_log(mysql_error(df_db()));
				throw new Exception("Failed to clean up old registrations due to an SQL error.  See error log for details.", E_USER_ERROR);
				
			}
			$msg = df_translate(
				'actions.activate.MESSAGE_REGISTRATION_COMPLETE',
				'Registration complete.  You are now logged in.');
			$_SESSION['UserName'] = $record->strval($app->_conf['_auth']['username_column']);
			
			
			import('Dataface/Utilities.php');
				
			Dataface_Utilities::fireEvent('after_action_activate', array('record'=>$record));

			$app->redirect($url.'&--msg='.urlencode($msg));
			
		}
		
		
	}
}
?>
