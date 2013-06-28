<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp (shannah@sfu.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */
/**
 * File: 	Dataface/AuthenticationTool.php
 * Author:	Steve Hannah <shannah@sfu.ca>
 * Created:	May 10, 2006
 *
 * Description:
 *	Handles authentication for Dataface application.
 */
import('Dataface/Table.php');
class Dataface_AuthenticationTool {

	var $authType = 'basic';

	var $conf;
	/**
	 * Delegate object that can override login functionality.
	 */
	var $delegate;
	
	/**
	 * Name of the table that contains the Users records.
	 */
	var $usersTable;
	
	/**
	 * Name of the column that contains the username
	 */
	var $usernameColumn;
	
	/**
	 * Name of the column that contains the password
	 */
	var $passwordColumn;
	
	/**
	 * Optional name of the column that contains the level of the user.
	 */
	var $userLevelColumn;
	
	private $emailColumn=null;
	
	/**
	 * A flag to indicate if authentication is enabled.
	 */
	var $authEnabled = true;
	
	public static function &getInstance($params=array()){
		static $instance = 0;
		if ( $instance === 0 ){
			$instance = new Dataface_AuthenticationTool($params);
			if ( !defined('DATAFACE_AUTHENTICATIONTOOL_LOADED') ){
				define('DATAFACE_AUTHENTICATIONTOOL_LOADED', true);
			}
		}
		
		return $instance;
	}
	
	function Dataface_AuthenticationTool($params=array()){
		$this->conf = $params;
		$this->usersTable = ( isset($params['users_table']) ? $params['users_table'] : null);
		$this->usernameColumn = ( isset($params['username_column']) ? $params['username_column'] : null);
		$this->passwordColumn = (isset( $params['password_column']) ? $params['password_column'] : null);
		$this->userLevelColumn = (isset( $params['user_level_column']) ? $params['user_level_column'] : null);
		
		$this->setAuthType(@$params['auth_type']); 
	}
	
	function setAuthType($type){
		if ( isset( $type ) and $type != $this->authType ){
			$this->authType = $type;
			$this->delegate = null;
			// It is possible to define a delegate to this tool by adding the
			// auth_type option to the conf.ini file _auth section.
			$module = basename($type);
			$module_path = array(
				DATAFACE_SITE_PATH.'/modules/Auth/'.$module.'/'.$module.'.php',
				DATAFACE_PATH.'/modules/Auth/'.$module.'/'.$module.'.php'
				);
			foreach ( $module_path as $path ){
				if ( is_readable($path) ){
					import($path);
					$classname = 'dataface_modules_'.$module;
					$this->delegate = new $classname;
					break;
				}
			}
			
		} 
	}
	
	function getCredentials(){
	
		if ( isset($this->delegate) and method_exists($this->delegate, 'getCredentials') ){
			return $this->delegate->getCredentials();
		} else {
			$username = (isset($_REQUEST['UserName']) ? $_REQUEST['UserName'] : null);
			$password = (isset($_REQUEST['Password']) ? $_REQUEST['Password'] : null);
			return array('UserName'=>$username, 'Password'=>$password);
		}
	}
	
	function checkCredentials(){
		$app =& Dataface_Application::getInstance();
		if ( !$this->authEnabled ) return true;
		if ( isset($this->delegate) and method_exists($this->delegate, 'checkCredentials') ){
			return $this->delegate->checkCredentials();
		} else {
			// The user is attempting to log in.
			$creds = $this->getCredentials();
			if ( !isset( $creds['UserName'] ) || !isset($creds['Password']) ){
				// The user did not submit a username of password for login.. trigger error.
				//throw new Exception("Username or Password Not specified", E_USER_ERROR);
				return false;
			}
			import('Dataface/Serializer.php');
			$serializer = new Dataface_Serializer($this->usersTable);
			//$res = mysql_query(
			$sql =	"SELECT `".$this->usernameColumn."` FROM `".$this->usersTable."`
				 WHERE `".$this->usernameColumn."`='".addslashes(
					$serializer->serialize($this->usernameColumn, $creds['UserName'])
					)."'
				 AND `".$this->passwordColumn."`=".
					$serializer->encrypt(
						$this->passwordColumn,
						"'".addslashes($serializer->serialize($this->passwordColumn, $creds['Password']))."'"
					);
			$res = mysql_query($sql, $app->db());
			if ( !$res ) throw new Exception(mysql_error($app->db()), E_USER_ERROR);
				
			if ( mysql_num_rows($res) === 0 ){
				return false;
			}
			$found = false;
			while ( $row = mysql_fetch_row($res) ){
				if ( strcmp($row[0], $creds['UserName'])===0 ){
					$found=true;
					break;
				}
			}
			@mysql_free_result($res);
			return $found;
		}
	
	}
	
	
	function setPassword($password){
		$app =& Dataface_Application::getInstance();
		if ( isset($this->delegate) and method_exists($this->delegate, 'setPassword') ){
			return $this->delegate->setPassword($username, $password);
		} else {
			
			$user = $this->getLoggedInUser();
			if ( !$user ){
			
				throw new Exception("Failed to set password because there is no logged in user.");
			}
			
			$user->setValue($this->passwordColumn, $password);
			$res = $user->save();
			if ( PEAR::isError($res) ){
				throw new Exception($res->getMessage());
			}
			return true;
		}
	}

	function authenticate(){
		$app =& Dataface_Application::getInstance();
		if ( !$this->authEnabled ) return true;
		
		
		if ( $app->sessionEnabled() or $app->autoSession ){
			$app->startSession($this->conf);
		}
		$appdel =& $app->getDelegate();
		
		// Fire a trigger before we authenticate
		if ( isset($appdel) and method_exists($appdel, 'before_authenticate') ){
			$appdel->before_authenticate();
		}
		
		if ( isset( $_REQUEST['-action'] ) and $_REQUEST['-action'] == 'logout' ){
			$app->startSession();
			// the user has invoked a logout request.
			
			if ( isset($appdel) and method_exists($appdel, 'before_action_logout' ) ){
				$res = $appdel->before_action_logout();
				if ( PEAR::isError($res) ) return $res;
			}
			$username = @$_SESSION['UserName'];
			session_destroy();
			
			import('Dataface/Utilities.php');
				
			Dataface_Utilities::fireEvent('after_action_logout', array('UserName'=>$username));
			
			
			if ( isset($this->delegate) and method_exists($this->delegate, 'logout') ){
				$this->delegate->logout();
			}
			if ( isset($_REQUEST['-redirect']) and !empty($_REQUEST['-redirect']) ){
				$app->redirect($_REQUEST['-redirect']);
			} else if ( isset($_SESSION['-redirect']) ){
				$redirect = $_SESSION['-redirect'];
				unset($_SESSION['-redirect']);
				$app->redirect($redirect);

			
			} else {
				$app->redirect(DATAFACE_SITE_HREF);
			}
			
		}
		
		if ( isset( $_REQUEST['-action'] ) and $_REQUEST['-action'] == 'login' ){
			$app->startSession();
			if ( $this->isLoggedIn() ){
				$app->redirect(DATAFACE_SITE_HREF.'?--msg='.urlencode("You are logged in"));

			}
			
			if ( $this->isLockedOut() ){
				$app->redirect(DATAFACE_SITE_HREF.'?--msg='.urlencode("Sorry, you are currently locked out of the site due to failed login attempts.  Please try again later, or contact a system administrator for help."));

			}
			// The user is attempting to log in.
			$creds = $this->getCredentials();
			$approved = $this->checkCredentials();
			
			if ( isset($creds['UserName']) and !$approved ){
				
				$this->flagFailedAttempt($creds);
				
				return PEAR::raiseError(
					df_translate('Incorrect Password',
							'Sorry, you have entered an incorrect username /password combination.  Please try again.'
							),
					DATAFACE_E_LOGIN_FAILURE
					);
			} else if ( !$approved ){
				
				$this->showLoginPrompt();
				exit;
			}
			
			$this->clearFailedAttempts();
			
			// If we are this far, then the login worked..  We will store the 
			// userid in the session.
			$_SESSION['UserName'] = $creds['UserName'];
			
			import('Dataface/Utilities.php');
				
			Dataface_Utilities::fireEvent('after_action_login', array('UserName'=>$_SESSION['UserName']));
			$msg = df_translate('You are now logged in','You are now logged in');
			if ( isset( $_REQUEST['-redirect'] ) and !empty($_REQUEST['-redirect']) ){
				
				$redirect = df_append_query($_REQUEST['-redirect'], array('--msg'=>$msg));
				//$app->redirect($redirect);

			} else if ( isset($_SESSION['-redirect']) ){
				$redirect = $_SESSION['-redirect'];
				unset($_SESSION['-redirect']);
				$redirect = df_append_query($redirect, array('--msg'=>$msg));
				//$app->redirect($redirect);

			} else {
			// Now we forward to the homepage:
				$redirect = $_SERVER['HOST_URI'].DATAFACE_SITE_HREF;
			}
			
			$redirect = preg_replace('/-action=login_prompt/', '', $redirect);
			$redirect = preg_replace('/-action=forgot_password/', '', $redirect);
			$app->redirect($redirect);

		}
		
		if ( isset($this->delegate) and method_exists($this->delegate, 'authenticate') ){
			$res = $this->delegate->authenticate();
			if ( PEAR::isError($res) and $res->getCode() == DATAFACE_E_REQUEST_NOT_HANDLED ){
				// we just pass the buck
			} else {
				return $res;
			}
		}
		
		if ( isset($this->conf['pre_auth_types']) ){
			$pauthtypes = explode(',',$this->conf['pre_auth_types']);
			if ( $pauthtypes ){
				$oldType = $this->authType;
				foreach ($pauthtypes as $pauthtype){
					$this->setAuthType($pauthtype);
					if ( isset($this->delegate) and method_exists($this->delegate, 'authenticate') ){
						$res = $this->delegate->authenticate();
						if ( PEAR::isError($res) and $res->getCode() == DATAFACE_E_REQUEST_NOT_HANDLED) {
							// pass the buck
						} else {
							return $res;
						}
					}
				}
				$this->setAuthType($oldType);
			}
		}
		
		
	}
	
	/**
	 * Indicates whether there is a user logged in or not.
	 */
	function isLoggedIn(){
		if ( !$this->authEnabled ) return true;
		if ( isset($this->delegate) and method_exists($this->delegate, 'isLoggedIn') ){
			return $this->delegate->isLoggedIn();
		}

		return (isset($_SESSION['UserName']) and $_SESSION['UserName']);
	}
	
	/**
	 * Displays the login prompt for an application.
	 * @param $msg Optional error message to display.  e.g. 'Incorrect password'
	 */
	function showLoginPrompt($msg=''){
		
		if ( !$this->authEnabled ) return true;
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		if ( @$query['--no-prompt'] ){
			header("HTTP/1.0 401 Please Log In");
			echo "<html><body>Please Log In</body></html>";
			exit;
		}
		
		if ( isset($this->delegate) and method_exists($this->delegate, 'showLoginPrompt') ){
			return $this->delegate->showLoginPrompt($msg);
		}
		header("HTTP/1.1 401 Please Log In");
		
		$url = $app->url('-action=login_prompt');
		
		if ( $msg ) $msgarray = array($msg);
		else $msgarray = array();
		if ( isset($query['--msg']) ){
			$msgarray[] = $query['--msg'];
		}
		$msg = trim(implode('<br>',$msgarray));
		if ( $msg ) $url .= '&--msg='.urlencode($msg);
		if ( $query['-action'] != 'login' and $query['-action'] != 'login_prompt' ) $_SESSION['-redirect'] = (isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI']:$app->url(''));
		else {
			$referer = @$_SERVER['HTTP_REFERER'];
			if ( !@$_SESSION['-redirect'] and $referer and strpos($referer, df_absolute_url(DATAFACE_SITE_URL)) === 0 ){
				$_SESSION['-redirect'] = $referer;
			}
		}
		header("Location: $url");
		exit;
		//df_display(array('msg'=>$msg, 'redirect'=>@$_REQUEST['-redirect']), 'Dataface_Login_Prompt.html');
	
	}
	
	/**
	 * Returns reference to a Dataface_Record object of the currently logged in
	 * user's record.
	 */
	function &getLoggedInUser(){
		$null = null;
		if ( !$this->authEnabled ) return $null;
		if ( isset($this->delegate) and method_exists($this->delegate, 'getLoggedInUser') ){
			$user =&  $this->delegate->getLoggedInUser();
			return $user;
		}
		if ( !$this->isLoggedIn() ) return $null;
		static $user = 0;
		if ( $user === 0 ){
			$user = df_get_record($this->usersTable, array($this->usernameColumn => '='.$_SESSION['UserName']));
			if ( !$user ){
				$user = new Dataface_Record($this->usersTable, array($this->usernameColumn => $_SESSION['UserName']));
			}
		}
		return $user;
		
	}
	
	function getLoggedInUsername(){
		$null = null;
		if ( !$this->authEnabled ) return $null;
		if ( isset($this->delegate) and method_exists($this->delegate, 'getLoggedInUsername') ){
			return $this->delegate->getLoggedInUsername();
		}
		
		$user =& $this->getLoggedInUser();
		if ( isset($user) ){
			return $user->strval($this->usernameColumn);
		}
		
		return $null;
		
	}
	function _createFailedLoginsTable(){
		$res = mysql_query("create table if not exists `dataface__failed_logins` (
			`attempt_id` int(11) not null auto_increment primary key,
			`ip_address` varchar(32) not null,
			`username` varchar(32) not null,
			`time_of_attempt` int(11) not null
			)", df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()), E_USER_ERROR);
	}
	
	function flagFailedAttempt($credentials){
		$app = Dataface_Application::getInstance();
		$del = $app->getDelegate();
		$method = 'loginFailed';
		if ( isset($del) and method_exists($del, $method) ){
			$del->$method($credentials['UserName'], $_SERVER['REMOTE_ADDR'], time() );
		}
		$this->_createFailedLoginsTable();
		$res = mysql_query("insert into `dataface__failed_logins` (ip_address,username,time_of_attempt) values (
			'".addslashes($_SERVER['REMOTE_ADDR'])."',
			'".addslashes($credentials['UserName'])."',
			'".addslashes(time())."'
			)", df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()), E_USER_ERROR);
		
		
	}
	
	function clearFailedAttempts(){
		$this->_createFailedLoginsTable();
		$res = mysql_query("delete from `dataface__failed_logins` where ip_address='".addslashes($_SERVER['REMOTE_ADDR'])."'", df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
	}
	
	function isLockedOut(){
		$this->_createFailedLoginsTable();
		$res = mysql_query("delete from `dataface__failed_logins` where `time_of_attempt` < ".(time()-(60*30)), df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()), E_USER_ERROR);
		$res = mysql_query("select count(*) from `dataface__failed_logins` where `ip_address`='".addslashes($_SERVER['REMOTE_ADDR'])."'", df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()), E_USER_ERROR);
		list($num) = mysql_fetch_row($res);
		@mysql_free_result($res);
		return ($num > 20);
	}
	
	function getEmailColumn(){
		if ( !isset($this->emailColumn) ){
			import('Dataface/Ontology.php');
			Dataface_Ontology::registerType('Person', 'Dataface/Ontology/Person.php', 'Dataface_Ontology_Person');
			$ontology = Dataface_Ontology::newOntology('Person', $this->usersTable);
			if ( isset($this->conf['email_column']) ) $this->emailColumn = $this->conf['email_column'];
			else $this->emailColumn = $ontology->getFieldname('email');
		}
		return $this->emailColumn;
	}
	
	function getUserGroupNames(){
	    return array("FOO","BAR");
	}


}

