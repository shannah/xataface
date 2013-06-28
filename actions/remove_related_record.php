<?php
/**
 * Action to remove a related record.
 *
 * @param array --__keys__ The keys for the parent record.
 * @param string -relationship The name of the relationship from which the
 *		records should be removed.
 * @param string --remkeys The keys of the records to be removed encoded as follows
 *		key1val-key2val-key3val&key1val-key2val-key3val&..
 */
class dataface_actions_remove_related_record {
	function handle(&$params){
		import( 'Dataface/RemoveRelatedRecordForm.php');
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		$record = null; //& new Dataface_Record($this->_tablename, $_REQUEST['--__keys__']);
			// let the form handle the loading of the record
		
		
		$form = new Dataface_RemoveRelatedRecordForm($record, $query['-relationship']);
		
	
		if ( !$form->_record ){
			// the record could not be loaded
			return PEAR::raiseError(
				Dataface_LanguageTool::translate(
					'Specified record could not be loaded',
					'The specified record could not be loaded'
					),
					DATAFACE_E_NOTICE
				);
		}
		
		unset($app->currentRecord);
		$app->currentRecord =& $form->_record;
		
		if ( !Dataface_PermissionsTool::checkPermission('remove related record', $form->_record, array('relationship'=>$query['-relationship']) ) ) {
			return Dataface_Error::permissionDenied(
				Dataface_LanguageTool::translate(
					'Insufficient permissions to delete record',
					'Permission Denied.  You do not have permissions to remove related records from the relationship "'.
					$query['-relationship'].
					'" for this record.  
					Requires permission "remove related record" but you only have the following permissions: "'.
					df_permission_names_as_string(
						$form->_record->getPermissions(
							array('relationship'=>$query['-relationship'])
							)
						).
					'"',
					array('relationship'=>$query['-relationship'],
						'required_permission'=>'remove related record',
						'granted_permissions'=>df_permission_names_as_string($form->_record->getPermissions(array('relationship'=>$query['-relationship'])))
						)
					)
				);
			//$this->_vars['error'] =  "<div class=\"error\">Error.  Permission Denied.<!-- At line ".__LINE__." of file ".__FILE__." --></div>";
			//return;
		}
		if ( @$_POST['-confirm_delete_hidden'] and $form->validate() ){
		
			$res = $form->process(array(&$form, 'delete'), true);
			$response =& Dataface_Application::getResponse();
			
			if ( PEAR::isError($res) && !Dataface_Error::isNotice($res) ){
				return $res;
				//$this->_vars['error'] = "<div class=\"error\">Error.  ".$res->toString()."<!-- At line ".__LINE__." of file ".__FILE__." --></div>";
				//return;
			} else if ( count($res['warnings']) > 0 ){ //Dataface_Error::isNotice($res) ){
				foreach ($res['warnings'] as $warning){
					$app->addError($warning);
					$response['--msg'] = 'Errors occurred trying to remove records';
				}
				
			} else {
				$response['--msg'] = df_translate(
					'Records successfully deleted from relationship',
					' Records successfully removed from relationship'
					)."<br>".@$response['--msg'];
			}
			
			if ( count($res['warnings'])>0){
				foreach (array_merge($res['confirmations'], $res['warnings']) as $confirmation){
					$response['--msg'] .= "<br>".$confirmation;
				}
			}
			
			$msg = urlencode(trim(@$response['--msg']));
			$app->redirect($form->_record->getURL(array('-action'=>'related_records_list', '-relationship'=>$query['-relationship']) ).'&--msg='.$msg);
			//header("Location: ".$_SERVER['HOST_URI'].$_SERVER['PHP_SELF'].'?'.$_COOKIE['dataface_lastpage'].'&--msg='.$msg);
			
				
			
		}
		
		
		
		ob_start();
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		
		
		$context = array('form'=>$out);
		if ( isset($query['-template']) ) $template = $query['-template'];
		else if ( isset( $params['action']['template']) ) $template = $params['action']['template'];
		else $template = 'Dataface_Remove_Related_Record.html';
		df_display($context, $template, true);
	}
}

?>
