<?php
class dataface_actions_delete {

	function handle(&$params){
		import( 'Dataface/DeleteForm.php');
		import( 'Dataface/LanguageTool.php');
		import( 'Dataface/Record.php');
		
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$record = new Dataface_Record($query['-table'], @$_REQUEST['--__keys__']);

// 		if ( !Dataface_PermissionsTool::delete($record) ) {
// 			return Dataface_Error::permissionDenied(
// 				Dataface_LanguageTool::translate(
// 					/* i18n id */
// 					'No delete permissions',
// 					/* Default error message */
// 					'Insufficient Permissions to delete this record',
// 					/* i18n parameters */
// 					array('record'=>$record->getTitle())
// 				)
// 			);
// 			
// 			
// 		}
		
	
		
		$form = new Dataface_DeleteForm($query['-table'], $app->db(), $query);
		
		$form->_build();
		$form->addElement('hidden','-table');
		$form->setDefaults(array('-table'=>$query['-table']));
		$msg = '';
		
		if ( $form->validate() ){
			$res = $form->process( array(&$form, 'delete'), true);
			$response =& Dataface_Application::getResponse();
			if ( !isset($response['--msg']) ) $response['--msg'] = '';
			$failed = false;
			if ( PEAR::isError($res) && !Dataface_Error::isNotice($res) ){
			
			    $errno = xf_db_errno(df_db());
			    if (@$query['-response'] == 'json') {
			        df_write_json(array(
			            'code' => 500,
			            'message' => $errno == 1451 ?
			                "Failed to delete record due to a foreign key constraint" :
			                $res->getMessage(),
			            'errno' => $errno
			        ));
			        exit;
			    }
				return $res;
				//$error = $res->getMessage();
				//$msg .= "\n". $res->getUserInfo();
			} else if ( Dataface_Error::isNotice($res) ){
				$app->addError($res);
				//$response['--msg'] = @$response['--msg'] ."\n".$res->getMessage();
				$failed = true;
				
			} else if ( is_array($res) ){
				$msg = df_translate(
					'Some errors occurred while deleting records',
					'Some errors occurred while deleting records'
					);
				foreach ($res as $warning){
					$response['--msg'] .= "\n".$warning->getMessage();
				}
				
			} else  {
				$msg = Dataface_LanguageTool::translate(
					/* i18n id */
					'Records successfully deleted',
					/* default message */
					'Records successfully deleted.'
				);
			}
			$msg = urlencode(trim($msg."\n".$response['--msg']));
			if ( !$failed ){
			
			    if (@$query['-response'] == 'json') {
			        df_write_json(array(
			            'code' => 200,
			            'message' => 'Record successfully deleted'
			        ));
			        exit;
			    }
			
				import('Dataface/Utilities.php');
				Dataface_Utilities::fireEvent('after_action_delete', array('record'=>&$record));
				$append = '';
				$append .= '&--master-detail-delete-row=1';
				header('Location: '.$_SERVER['HOST_URI'].DATAFACE_SITE_HREF.'?-table='.$query['-table'].$append.'&--msg='.$msg);
				exit;
			} else {
			    if (@$query['-response'] == 'json') {
			        df_write_json(array(
			            'code' => 500,
			            'message' => urldecode($msg)
			        ));
			        exit;
			    }
			}
		}
		
		ob_start();
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		
		
		$context = array('form'=>$out);
		if ( isset($query['-template']) ) $template = $query['-template'];
		else if ( isset( $params['action']['template']) ) $template = $params['action']['template'];
		else $template = 'Dataface_Delete_Record.html';
		df_display($context, $template, true);
	
	}
}
