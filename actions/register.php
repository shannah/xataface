<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2007  Steve Hannah (shannah@sfu.ca)
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
 * An action that allows users to register for the application.
 *
 * This action is only enabled if all of the following conditions hold:
 *		1. Authentication is enabled via the [_auth] section of the conf.ini file.
 *		2. The allow_register option in the [_auth] section of the conf.ini file
 *			is set to 1.
 *
 * @created June 4, 2007
 * @author Steve Hannah <shannah@sfu.ca>
 */
class dataface_actions_register {

	/**
	 * Reference to hold the HTML_QuickForm object that is used as the registration
	 * form.
	 */
	var $form;
	var $params;
	var $ontology;
	

	function handle(&$params){
		$this->params =& $params['action'];
		unset($params);
		$params =& $this->params;

		Dataface_PermissionsTool::getInstance()->setDelegate(new dataface_actions_register_permissions_delegate());
		
		
		$app =& Dataface_Application::getInstance();
		$auth =& Dataface_AuthenticationTool::getInstance();

		
		import('Dataface/Ontology.php');
		Dataface_Ontology::registerType('Person', 'Dataface/Ontology/Person.php', 'Dataface_Ontology_Person');
		$this->ontology =& Dataface_Ontology::newOntology('Person', $app->_conf['_auth']['users_table']);
		
		$atts =& $this->ontology->getAttributes();
		
		$query =& $app->getQuery();
		if ( !is_array(@$app->_conf['_auth']) ){
			return PEAR::raiseError("Cannot register when authentication is not enabled.", DATAFACE_E_ERROR);
		}
		
		if ( isset($app->_conf['_auth']['email_column']) ){
			
			
			$atts['email'] =& $this->ontology->table->getField( $app->_conf['_auth']['email_column'] );
			$this->fieldnames['email'] = $app->_conf['_auth']['email_column'];
		} 
		
			
		if ( $auth->isLoggedIn() ){
			return Dataface_Error::permissionDenied("Sorry you cannot register once you are logged in.  If you want to register, you must first log out.");
		}
		
		
		
		if ( !@$app->_conf['_auth']['allow_register'] ){
			return PEAR::raiseError("Sorry, registration is not allowed.  Please contact the administrator for an account.", DATAFACE_E_ERROR);
		}
		
		
		$pt =& Dataface_PermissionsTool::getInstance();
		
		
		// Create a new record form on the users table
		$this->form =& df_create_new_record_form($app->_conf['_auth']['users_table']);
		
		// add the -action element so that the form will direct us back here.
		$this->form->addElement('hidden','-action');
		$this->form->setDefaults(array('-action'=>$query['-action']));
		
		// Check to make sure that there isn't another user with the same 
		// username already.
		$validationResults = $this->validateRegistrationForm($_POST);
		if ( count($_POST) > 0 and PEAR::isError($validationResults) ){
			$app->addMessage($validationResults->getMessage());
			$this->form->_errors[$app->_conf['_auth']['username_column']] = $validationResults->getMessage();
		}
		if ( !PEAR::isError($validationResults) and $this->form->validate() ){
			// The form input seems OK.  Let's process the form
			
			// Since we will be using our own form processing for this action, 
			// we need to manually push the field inputs into the Dataface_Record
			// object.
			$this->form->push();
			
			// Now we obtain the Dataface_Record object that is to be added.
			$rec =& $this->form->_record;
			$delegate =& $rec->_table->getDelegate();
			
			
			// Give the delegate classes an opportunity to have some fun
			if ( isset($delegate) and method_exists($delegate, 'beforeRegister') ){
				$res = $delegate->beforeRegister($rec);
				if ( PEAR::isError($res) ){
					return $res;
				}
			}
			
			$appdel = & $app->getDelegate();
			if ( isset($appdel) and method_exists($appdel, 'beforeRegister') ){
				$res = $appdel->beforeRegister($rec);
				if ( PEAR::isError($res) ) return $res;
			}
			
			// This is where we actually do the processing.  This passes control
			// to the processRegistrationForm method in this class.
			$res = $this->form->process(array(&$this, 'processRegistrationForm'), true);
			
			// If there was an error in processing mark the error, and show the
			// form again.  Otherwise we just redirect to the next page and
			// let the user know that he was successful.
			if ( PEAR::isError($res) ){
				$app->addError($res);
				
			} else {
			
				// Let the delegate classes perform their victory lap..
				if ( isset($delegate) and method_exists($delegate, 'afterRegister') ){
					$res  = $delegate->afterRegister($rec);
					if ( PEAR::isError($res) ) return $res;
				}
				
				if ( isset($appdel) and method_exists($appdel, 'afterRegister') ){
					$res = $appdel->afterRegister($rec);
					if ( PEAR::isError($res) ) return $res;
				}
			
			
				// We accept --redirect markers to specify which page to redirect
				// to after we're done.  This will usually be the page that the
				// user was on before they went to the login page.
				if ( isset($_SESSION['--redirect']) ) $url = $_SESSION['--redirect'];
				else if ( isset($_SESSION['-redirect']) ) $url = $_SESSION['-redirect'];
				else if ( isset($_REQUEST['--redirect']) ) $url = $_REQUEST['--redirect'];
				else if ( isset($_REQUEST['-redirect']) ) $url = $_REQUEST['-redirect'];
				else $url = $app->url('-action='.$app->_conf['default_action']);
				
				if ( @$params['email_validation'] ){
					$individual = $this->ontology->newIndividual($this->form->_record);
					$msg = df_translate('actions.register.MESSAGE_THANKYOU_PLEASE_VALIDATE', 
						'Thank you. An email has been sent to '.$individual->strval('email').' with instructions on how to complete the registration process.',
						array('email'=>$individual->strval('email'))
						);
				} else {
					// To save the user from having to log in after he has just filled
					// in the registration form, we will just log him in right here.
					$_SESSION['UserName'] = $this->form->exportValue($app->_conf['_auth']['username_column']);
					$msg =  df_translate('actions.register.MESSAGE_REGISTRATION_SUCCESSFUL',
						"Registration successful.  You are now logged in."
						);
				}
				// Now we actually forward to the success page along with a success message
				if ( strpos($url, '?') === false ) $url .= '?';
				$app->redirect($url.'&--msg='.urlencode($msg));
			}
		}
		
		// We want to display the form, but not yet so we will use an output buffer
		// to store the form HTML in a variable and pass it to our template.
		ob_start();
		$this->form->display();
		$out = ob_get_contents();
		ob_end_clean();
		
		$context = array('registration_form'=>$out);
		
		// We don't want to keep the registration page in history, because we want to
		// be able to redirect the user back to where he came from before registering.
		$app->prefs['no_history'] = true;
		df_display($context, 'Dataface_Registration.html');
	
	}
	
	/**
	 * Creates a table to hold the temporary user registrations.
	 */
	function createRegistrationTable(){
		if ( !Dataface_Table::tableExists('dataface__registrations', false) ){
			$sql = "create table `dataface__registrations` (
				registration_code varchar(32) not null,
				registration_date timestamp not null,
				registration_data longtext not null,
				primary key (registration_code))";
				// registration_code stores an md5 code used to identify the registration
				// registration_date is the date that the registration was made
				// registration_data is a serialized array of the data from getValues()
				// on the record.
				
				
			$res = mysql_query($sql, df_db());
			if ( !$res ) throw new Exception(mysql_error(df_db()), E_USER_ERROR);
		}
		return true;
	
	}
	
	/**
	 * Validates the registration form to make sure that it is OK for input.
	 * Mainly this just checks for duplicate user names.
	 * @param array $values Value map.  Usually from $_POST
	 * @return mixed PEAR_Error if there is a problem.  True otherwise.
	 */
	function validateRegistrationForm($values){

		$app =& Dataface_Application::getInstance();
		$del =& $app->getDelegate();
		if ( $del and method_exists($del,'validateRegistrationForm') ){
			$res = $del->validateRegistrationForm($values);
			if ( PEAR::isError($res) ) return $res;
			else if ( is_int($res) and $res === 2 ) return true;
		}
		$conf =& $app->_conf['_auth'];
		
		// Make sure username is supplied
		if ( !@$values[$conf['username_column']] ) 
			return PEAR::raiseError(
				df_translate('actions.register.MESSAGE_USERNAME_REQUIRED', 'Please enter a username')
				);
		
		
		// Check for a duplicate username
		$res = mysql_query("select count(*) from `".$conf['users_table']."` where `".$conf['username_column']."` = '".addslashes($values[$conf['username_column']])."'", df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()), E_USER_ERROR);
		
		list($num) = mysql_fetch_row($res);
		if ( $num>0 ){
			return PEAR::raiseError(
				df_translate('actions.register.MESSAGE_USERNAME_ALREADY_TAKEN', 'Sorry, that username is already in use by another user.')
				);
		}
		
		// Make sure that the user supplied a password
		if ( !@$values[$conf['password_column']] )
			return PEAR::raiseError(
				df_translate('actions.register.MESSAGE_PASSWORD_REQUIRED', 'Please enter a password')
				);
				
		// Make sure that the user supplied an email address - and that the email address is valid
		$emailField = $this->ontology->getFieldname('email');
		if ( !@$values[$emailField] or !$this->ontology->validate('email', @$values[$emailField], false /*No blanks*/))
			return PEAR::raiseError(
				df_translate('actions.register.MESSAGE_EMAIL_REQUIRED', 'Please enter a valid email address in order to register.  A valid email address is required because an email will be sent to the address with information on how to activate this account.')
				);
				
		return true;
	}
	
	
	function _fireDelegateMethod($name, &$record, $params=null){
		$app =& Dataface_Application::getInstance();
		$table = & Dataface_Table::loadTable($app->_conf['_auth']['users_table']);
		
		$appdel =& $app->getDelegate();
		$tdel =& $table->getDelegate();
		
		if ( isset($tdel) and method_exists($tdel, $name) ){
			$res = $tdel->$name($record, $params);
			if ( !PEAR::isError($res) or ($res->getCode() != DATAFACE_E_REQUEST_NOT_HANDLED) ){
				return $res;
			}
		}	
		
		if ( isset($appdel) and method_exists($appdel, $name) ){
			$res = $appdel->$name($record, $params);
			if ( !PEAR::isError($res) or ($res->getCode() != DATAFACE_E_REQUEST_NOT_HANDLED) ){
				return $res;
			}
		}
		return PEAR::raiseError("No delegate method found named '$name'.", DATAFACE_E_REQUEST_NOT_HANDLED);
	}

	
	
	function processRegistrationForm($values){
		
		$app =& Dataface_Application::getInstance();
		$conf =& $app->_conf['_auth'];
		$appConf =& $app->conf();
		$table =& Dataface_Table::loadTable($conf['users_table']);
		
		if ( @$this->params['email_validation'] ){
			
			// Let's try to create the registration table if it doesn't already
			// exist
			$this->createRegistrationTable();
			
			// Now we will store the registration attempt
			
			// A unique code to be used as an id
			$code = null;
			do {
				$code = md5(rand());
			} while ( 
				mysql_num_rows(
					mysql_query(
						"select registration_code 
						from dataface__registrations 
						where registration_code='".addslashes($code)."'", 
						df_db()
						)
					) 
				);
			
			// Now that we have a unique id, we can insert the value
			
			$sql = "insert into dataface__registrations 
					(registration_code, registration_data) values
					('".addslashes($code)."',
					'".addslashes(
						serialize(
							$this->form->_record->getValues()
							)
						)."')";
			$res = mysql_query($sql, df_db());
			if ( !$res ) throw new Exception(mysql_error(df_db()), E_USER_ERROR);
			
			$activation_url = $_SERVER['HOST_URI'].DATAFACE_SITE_HREF.'?-action=activate&code='.urlencode($code);
			
			// Now that the registration information has been inserted, we need
			// to send the confirmation email
			// Let's try to send the email if possible.
			$res = $this->_fireDelegateMethod('sendRegistrationActivationEmail', $this->form->_record, $activation_url );
			if ( !PEAR::isError($res) or ( $res->getCode() != DATAFACE_E_REQUEST_NOT_HANDLED) ){
				return $res;
			}
			
			// If we got this far, that means that we haven't sent the email yet... Rather
			// let's send it outselves.
			// We use the Person Ontology to work with the users table record in a more
			// generic way.
			$registrant =& $this->ontology->newIndividual($this->form->_record);
			// We now have the user's email address
			$email = $registrant->strval('email');
			
			// Let's get the email info. This will return an associative array
			// of the parameters involved in the registration email.  The keys
			// are:
			// 1. subject
			// 2. message
			// 3. headers
			// 4. parameters
			// These are such that they can be passed directly to the mail function
			$info = $this->_fireDelegateMethod('getRegistrationActivationEmailInfo', $this->form->_record, $activation_url);
			if ( PEAR::isError($info) ) $info = array();
			$info['to'] = $email;
			
			// Override specific parts of the message if delegate class wants it.
			$subject = $this->_fireDelegateMethod('getRegistrationActivationEmailSubject', $this->form->_record, $activation_url);
			if ( !PEAR::isError($subject) ) $info['subject'] = $subject;
			
			
			$message = $this->_fireDelegateMethod('getRegistrationActivationEmailMessage', $this->form->_record, $activation_url);
			if ( !PEAR::isError($message) ) $info['message'] = $message;
			
			$parameters = $this->_fireDelegateMethod('getRegistrationActivationEmailParameters', $this->form->_record, $activation_url);
			if ( !PEAR::isError($parameters) ) $info['parameters'] = $parameters;
			
			$headers = $this->_fireDelegateMethod('getRegistrationActivationEmailHeaders', $this->form->_record, $activation_url);
			if ( !PEAR::isError($headers) ) $info['headers'] = $headers;
			
			
			// Now we fill in the missing information with defaults
			if ( !@$info['subject'] ) 
				$info['subject'] = df_translate(
					'actions.register.MESSAGE_REGISTRATION_ACTIVATION_EMAIL_SUBJECT',
					$app->getSiteTitle().': Activate your account',
					array('site_title'=>$app->getSiteTitle())
					);
			
			if ( !@$info['message'] ){
				$site_title = $app->getSiteTitle();
				if ( isset($appConf['abuse_email']) ){
					$admin_email = $appConf['abuse_email'];
				} else if ( isset($appConf['admin_email']) ){
					$admin_email = $appConf['admin_email'];
				} else {
					$admin_email = $_SERVER['SERVER_ADMIN'];
				}
				
				if ( isset( $appConf['application_name'] ) ){
					$application_name = $appConf['application_name'];
				} else {
					$application_name = df_translate('actions.register.LABEL_A_DATAFACE_APPLICATION','a Dataface Application');
				}
				
				if ( file_exists('version.txt') ){
					$application_version = trim(file_get_contents('version.txt'));
				} else {
					$application_version = '0.1';
				}
				
				if ( file_exists(DATAFACE_PATH.'/version.txt') ){
					$dataface_version = trim(file_get_contents(DATAFACE_PATH.'/version.txt'));
				} else {
					$dataface_version = 'unknown';
				}
				
				
				
				
				$msg = <<<END
Thank you for registering for an account on $site_title .  In order to complete your registration,
please visit $activation_url .

If you have not registered for an account on this web site and believe that you have received
this email eroneously, please report this to $admin_email .
-----------------------------------------------------------
This message was sent by $site_title which is powered by $application_name version $application_version
$application_name built using Dataface version $dataface_version (http://fas.sfu.ca/dataface).
END;

				$info['message'] = df_translate(
					'actions.register.MESSAGE_REGISTRATION_ACTIVATION_EMAIL_MESSAGE',
					$msg,
					array(
						'site_title'=>$site_title,
						'activation_url'=>$activation_url,
						'admin_email'=>$admin_email,
						'application_name'=>$application_name,
						'application_version'=>$application_version,
						'dataface_version'=>$dataface_version
						)
					);
			
			
			}
			
			// Now that we have all of the information ready to send.  Let's send
			// the email message.
			
			if ( @$conf['_mail']['func'] ) $func = $conf['_mail']['func'];
			else $func = 'mail';
			$res = $func($info['to'],
						$info['subject'],
						$info['message'],
						@$info['headers'],
						@$info['parameters']);
			if ( !$res ){
				return PEAR::raiseError('Failed to send activation email.  Please try again later.', DATAFACE_E_ERROR);
			} else {
				return true;
			}
			
		} else {
			// We aren't using email validation.. let's just pass it to the 
			// form's standard processing function.
			return $this->form->process(array(&$this->form, 'save'), true);
		}
		
	}
}

class dataface_actions_register_permissions_delegate {
	function filterPermissions(&$obj, &$perms){
		if ( @$perms['register'] ) $perms['new'] = 1;
	}
}

?>
