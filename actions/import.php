<?php
import('Dataface/QueryTool.php');
/**
 * 
 *
 */
class dataface_actions_import {
	function handle($params){
		ini_set('memory_limit', '256M');
		set_time_limit(99999);
		import( 'Dataface/ImportForm.php');
		$app =& Dataface_Application::getInstance();
		$app->startSession();
		$query =& $app->getQuery();
		
		$form = new Dataface_ImportForm($query['-table']);
		$record =& $form->_record;
		
		if ( is_object($record) ){
			if ( !$record->checkPermission('import') ){

				return Dataface_Error::permissionDenied();
			}
		} else {
			if ( !Dataface_PermissionsTool::checkPermission('import',Dataface_Table::loadTable($query['-table']) ) ){
				return Dataface_Error::permissionDenied();
			}
		}
		$form->_build();

		
		if ( $form->validate() ){
			//echo "validated";
			$querystr = $form->exportValue('-query');
			$returnPage = $form->exportValue('--redirect');
			
			if ( intval($form->_step) === 1 ){
				
				if ( preg_match('/--step=1/',$querystr) ){
					$querystr = preg_replace('/--step=1/', '--step=2', $querystr);
				} else {
					$querystr .= '&--step=2';
				}
				$importTablename = $form->process(array(&$form, 'import'));
				$app->redirect($_SERVER['PHP_SELF'].'?'.$querystr.'&--importTablename='.$importTablename.'&--redirect='.urlencode($returnPage));
			} else {
				$records = $form->process(array(&$form, 'import'));
				
				$returnPage = $form->exportValue('--redirect');
				//$keys  = $form->exportValue('__keys__');
				//$keys['-action'] = 'browse';
				//$keys['-step'] = null;
				//$keys['-query'] = null;
			
				//$link = Dataface_LinkTool::buildLink($keys);
				$link = $returnPage;
				$response =& Dataface_Application::getResponse();
				$msg = urlencode(trim("Records imported successfully.\n".@$response['--msg']));
				if ( strpos($link,'?') === false ) $link .= '?';
				$app->redirect($link.'&--msg='.$msg);

				
			
			} 
				
				
		
		
		}
				
		ob_start();
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		$context['form'] = $out;
		$context['filters'] = $form->_filterNames;
		$context['step'] = $form->_step;
		
		if ( isset($query['-template']) ) $template = $query['-template'];
		else if ( isset( $params['action']['template']) ) $template = $params['action']['template'];
		else {
			if ( isset( $query['-relationship'] ) ){
				$template = 'Dataface_Import_RelatedRecords.html';
			
			} else {
				$template = 'Dataface_Import_RelatedRecords.html';
			}
		
		}

		df_display($context, $template, true);
	
	}
}


?>
