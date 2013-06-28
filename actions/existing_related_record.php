<?php
import('Dataface/LinkTool.php');
class dataface_actions_existing_related_record {
	function handle(&$params){
		import( 'Dataface/ExistingRelatedRecordForm.php');
		
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$resultSet =& $app->getResultSet();
		
		//$record =& $app->getRecord();	// loads the current record 
		
		if ( !isset( $query['-relationship'] ) ){
			return PEAR::raiseError(
				Dataface_LanguageTool::translate(
					'Error: No relationship specified',
					'Error.  No relationship was specified when trying to add existing related record.'
					),
					DATAFACE_E_NOTICE
				);
			
		}
		$record = null;
		$form = new Dataface_ExistingRelatedRecordForm($record, $query['-relationship']);
		$res = $form->_build();
		if ( PEAR::isError($res) ) return Dataface_Error::permissionDenied($res->getMessage());
		
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
		

		if ( !$form->_record || !is_a($form->_record, 'Dataface_Record') ){
			throw new Exception(
				Dataface_LanguageTool::translate(
					'Fatal Error',
					'Fatal Error: Form should have loaded record but the record was null. '.Dataface_Error::printStackTrace(),
					array('stack_trace'=>Dataface_Error::printStackTrace(), 'msg'=>'Form should have loaded record but the record was null.')
					),
				E_USER_ERROR
				);
		}
		
		if ( !$form->_record->checkPermission('add existing related record', array('relationship'=>$query['-relationship']))){
		//if ( !Dataface_PermissionsTool::checkPermission('add existing related record',$form->_record) ) {
			return Dataface_Error::permissionDenied(
				Dataface_LanguageTool::translate(
					'Error: Permission denied adding existing related record',
					'Permission Denied.  You do not have sufficient permissions to add an existing related record.  Required permission: "add existing related record", but you have only been granted permissions: "'.implode(',',$form->_record->getPermissions()).'".',
					array('required_permission'=>'add existing related record', 'granted_permissions'=>implode(',', $form->_record->getPermissions()) )
					)
				);
			
		}
		
		if ( $form->validate() ){
			$res = $form->process(array(&$form, 'save'), true);
			$response =& Dataface_Application::getResponse();
			
			if ( PEAR::isError($res) && !Dataface_Error::isNotice($res) ){
				return $res;
			} else if ( Dataface_Error::isNotice($res) ){
				//$response['--msg'] = @$response['--msg'] . "\n".$res->getMessage();
				$app->addError(PEAR::raiseError(
					df_translate(
						'Failed to add record because of errors',
						'Failed to add record to relationship because of the following errors:'
						), 
					DATAFACE_E_NOTICE)
				);
				$app->addError($res);
				$success = false;
			} else {
				$success = true;
			}
			if ( $success ){
				import('Dataface/Utilities.php');
				Dataface_Utilities::fireEvent('after_action_existing_related_record');
				$fquery = array('-action'=>'browse');
				$msg = Dataface_LanguageTool::translate(
					'Record successfully added to relationship',
					"The record has been successfully added to the ".$query['-relationship']." relationship.\n" ,
					array('relationship'=>$query['-relationship'])
					);
				$msg = urlencode(trim(($success ? $msg :'').@$response['--msg']));
				
				
				$vals = $form->exportValues();
				if ( isset($vals['--redirect']) ){
					$qmark = (strpos($vals['--redirect'],'?') !== false) ? '&':'?';
					$app->redirect($vals['--redirect'].$qmark.'--msg='.$msg);
				}
				foreach ($vals['__keys__'] as $key=>$value){
					$fquery[$key] = "=".$value;
				}
				$link = Dataface_LinkTool::buildLink($fquery);
				$app->redirect("$link"."&--msg=".$msg);

			}
		}
		
		
		ob_start();
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		
		
		$context = array('form'=>$out);
		if ( isset($query['-template']) ) $template = $query['-template'];
		else if ( isset( $params['action']['template']) ) $template = $params['action']['template'];
		else $template = 'Dataface_Add_Existing_Related_Record.html';
		df_display($context, $template, true);
	}



}
?>
