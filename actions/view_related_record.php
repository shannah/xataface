<?php
class dataface_actions_view_related_record {
	function handle($params){
		$app = Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		$related_record = df_get_record_by_id($query['-related-record-id']);
		if ( !$related_record || PEAR::isError($related_record) ){
			$this->out_404();
		}
		$app->_conf['orig_permissions'] = $related_record->_record->getPermissions();
		
		Dataface_PermissionsTool::addContextMask($related_record);
		$perms = $related_record->getPermissions();
		//print_r($perms);exit;
		if ( !@$perms['view'] ){
			return Dataface_Error::permissionDenied('You don\'t have permission to view this record.');
		}
		
		$query['-relationship'] = $related_record->_relationship->getName();
		Dataface_JavascriptTool::getInstance()->import('xataface/actions/view_related_record.js');
		df_display(
			array('related_record' => $related_record ), 
			'xataface/actions/view_related_record.html'
		);
		
	}
	
	function out_404(){
		echo "Not found";
	}
}