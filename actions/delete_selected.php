<?php
/**
 * This action approves a set of selected webpages.
 *
 * @created Dec. 1, 2008
 * @author Steve Hannah <steve@weblite.ca>
 */
class dataface_actions_delete_selected {
	function handle(&$params){
		if ( !$_POST ) return PEAR::raiseError("This method is only available via POST");
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$records = df_get_selected_records($query);
		
		$updated = 0;
		$errs = array();
		foreach ($records as $rec){
			if ( !$rec->checkPermission('delete') ){
                                $errStr = df_translate("actions.delete_selected.permission_denied", "You do not have permission to delete '%s' because you do not have the 'delete' permission." );
                                $errs[] = sprintf($errStr, $rec->getTitle());
				
				continue;
			}
			$res = $rec->delete(true /*secure*/);
			if ( PEAR::isError($res) ) $errs[] = $res->getMessage();
			else $updated++;
			
		}
		
		if ( $errs ){
			$_SESSION['--msg'] = df_translate('Errors Occurred', 'Errors Occurred').':<br/> '.implode('<br/> ', $errs);
		} else {
			$_SESSION['--msg'] = df_translate('No errors occurred', "No errors occurred" );
		}
		
		$url = $app->url('-action=list');
		if ( @$_POST['--redirect'] ) $url = base64_decode($_POST['--redirect']);
                $msgStr = df_translate('x records were deleted', '%d records were deleted.');
                $msgStr = sprintf($msgStr, $updated);
		$url .= '&--msg='.urlencode($msgStr);
		$app->redirect($url);
	}
}
