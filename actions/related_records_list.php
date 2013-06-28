<?php
import('Dataface/Table.php');
class dataface_actions_related_records_list {
	function handle($params){
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		if ( !isset($query['-relationship']) ){
			return PEAR::raiseError("No relationship specified.");
		}
		
		$table =& Dataface_Table::loadTable($query['-table']);
		$record =& $app->getRecord();
		if ( !$record ){
			return Dataface_Error::permissionDenied("No record found");
		}
		$perms = $record->getPermissions(array('relationship'=>$query['-relationship']));
		
		if ( !@$perms['view related records'] ) return Dataface_Error::permissionDenied('You don\'t have permission to view this relationship.');

		$action = $table->getRelationshipsAsActions(array(), $query['-relationship']);
	
		
		if ( isset($query['-template']) ){
			df_display(array('record'=>$record), $query['-template']);
		} else if ( isset($action['template']) ){
			df_display(array('record'=>$record), $action['template']);
		} else {
			df_display(array('record'=>$record), 'Dataface_Related_Records_List.html');
		}	
		
		
	}
}

?>
