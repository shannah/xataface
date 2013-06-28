<?php
class dataface_actions_related_records_checkboxes {
	
	function handle($params){
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$record =& $app->getRecord();
		if ( !$record ){
			return PEAR::raiseError("No record found.", DATAFACE_E_NOTICE);
		}
		if ( !isset($query['-relationship']) ){
			return PEAR::raiseError("No relationship specified.");
		}
		
		$table =& Dataface_Table::loadTable($query['-table']);

		$action = $table->getRelationshipsAsActions(array(), $query['-relationship']);
	
		if ( @$action['permission'] and !$record->checkPermission($action['permission']) ){
			return Dataface_Error::permissionDenied();
		}
		
		ob_start();
		import('Dataface/RelationshipCheckboxForm.php');
		$form = new Dataface_RelationshipCheckboxForm($record, $query['-relationship']);
		$out = ob_get_contents();
		ob_end_clean();
		
		if ( isset($query['-template']) ){
			df_display(array('form'=>$out), $query['-template']);
		} else if ( isset($action['template']) ){
			df_display(array('form'=>$out), $action['template']);
		} else {
			df_display(array('form'=>$out), 'Dataface_related_records_checkboxes.html');
		}	
		
		
	}

}

?>
