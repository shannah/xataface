<?php

class dataface_actions_translate {

	function handle(&$params){
		import('Dataface/TranslationForm.php');
				
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$resultSet =& $app->getResultSet();
		
		$source = ( isset($_REQUEST['-sourceLanguage']) ? $_REQUEST['-sourceLanguage'] : $app->_conf['default_language']);
		$dest = ( isset($_REQUEST['-destinationLanguage']) ? $_REQUEST['-destinationLanguage'] : null);
		
		
		if ( $resultSet->found()>0){
			$form = new Dataface_TranslationForm($query['-table'], $source, $dest);
			/*
			 * There is either a result to edit, or we are creating a new record.
			 *
			 */
			 
			$res = $form->_build();
			if ( PEAR::isError($res) ){
				throw new Exception($res->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
			
			}
			
			/*
			 *
			 * We need to add the current GET parameter flags (the GET vars starting with '-') so
			 * that the controller knows to pass control to this method again upon form submission.
			 *
			 */
			foreach ( $query as $key=>$value){
				if ( strpos($key,'-')===0 ){
					$form->addElement('hidden', $key);
					$form->setDefaults( array( $key=>$value) );
					
				}
			}
			
			/*
			 * Store the current query string (the portion after the '?') in the form, so we 
			 * can retrieve it after and redirect back to our original location.
			 */
			$form->addElement('hidden', '-query');
			$form->setDefaults( array( '-action'=>$query['-action'],'-query'=>$_SERVER['QUERY_STRING']) );
			
			
			/*
			 * 
			 * We have to deal with 3 cases.
			 * 	1) The form has not been submitted.
			 *	2) The form was submitted but didn't validate (ie: it had some bad input)
			 * 	3) The form was submitted and was validated.
			 *
			 * We deal with Case 3 first...
			 *
			 */
		
			if ( $form->validate() ){
				/*
				 *
				 * The form was submitted and it validated ok.  We now process it (ie: save its contents).
				 *
				 */
				$app->clearMessages();
				$result = $form->process( array( &$form, 'save') );
				$success = true;
				$response =& Dataface_Application::getResponse();
				
				if ( !$result ){
					error_log("Error occurred in save: ".mysql_error( $app->db()).Dataface_Error::printStackTrace());
					throw new Exception("Error occurred in save.  See error log for details.");
					
					
				} else if ( PEAR::isError($result) && !Dataface_Error::isNotice($result) ){
					//echo "Error..";
					if ( Dataface_Error::isDuplicateEntry($result) ){
						return $result;
						
					} else {
						//echo "not dup entry"; exit;
						throw new Exception($result->toString(), E_USER_ERROR);
						
					}
				} else if ( Dataface_Error::isNotice($result) ){
					$app->addError($result);

					//$response['--msg'] = @$response['--msg'] ."\n".$result->getMessage();
					$success = false;
				}
				
				
				if ( $success ){
					/*
					 *
					 * The original query string will have the -new flag set.  We need to remove this 
					 * flag so that we don't redirect the user to create another new record.
					 *
					 */
					$vals = $form->exportValues();
					$vals['-query'] = preg_replace('/[&\?]-new=[^&]+/i', '', $vals['-query']);
					
					$msg = implode("\n", $app->getMessages());
					//$msg =@$response['--msg'];
					$msg = urlencode(
						Dataface_LanguageTool::translate(
							/* i18n id */
							'Record successfully translated',
							/* Default success message */
							"Record successfully translated.<br>"
						).$msg
					);
					$link = $_SERVER['HOST_URI'].DATAFACE_SITE_HREF.'?'.$vals['-query'].'&--msg='.$msg;
					
					
					/*
					 *
					 * Redirect the user to the appropriate record.
					 *
					 */
					$app->redirect($link);
					
				}
			}
			
			ob_start();
			$form->display();
			$out = ob_get_contents();
			ob_end_clean();
			
			
			$context = array('form'=>$out, 'formObj'=>$form);
			
				 
		} else {
			// no records were found
			$context = array('form'=>'', 'formObj'=>$form);
			$app->addMessage(
				Dataface_LanguageTool::translate(
					'No records matched request',
					'No records matched your request'
					)
				);
		}
		
		
		if ( isset($query['-template']) ) $template = $query['-template'];
		else if ( isset( $params['action']['template']) ) $template = $params['action']['template'];
		else $template = 'Dataface_Translate_Record.html';
		df_display($context, $template, true);
		
	}

}
