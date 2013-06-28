<?php
class dataface_actions_ajax_get_event_details {

	function handle(&$params){
	
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		$record_id = $query['--record_id'];
		if ( !$record_id ) trigger_error("No record id provided", E_USER_ERROR);
		
		$record =& df_get_record_by_id($record_id);
		
		$fields =& $record->_table->fields(false, true);
		
		header('Content-type: application/json; charset='.$app->_conf['oe']);
		
		//$out = '';
		
		//$out .= '<table class="record-view-table"><tbody>';
		$dl = array();
		foreach ( $fields as $field ){
			//if ( !$record->val($field['name']) ) continue;
			
			if ( !$record->checkPermission('view', array('field'=>$field['name'])) ) continue;
			if ( $field['visibility']['browse'] == 'hidden' ) continue;
			$val = $record->htmlValue($field['name']);
			if ( @$app->_conf['_prefs']['calendar.edit.inline'] and $record->checkPermission('edit', array('field'=>$field['name'])) and in_array($field['name'], array_keys($record->_table->fields())) ){
				$class = 'df__editable_wrapper';
			} else {
				$class = '';
			}
			$dl[] = array('fielddef'=>&$field, 'tdid'=> 'td-'.rand(), 'value'=>$val, 'tdclass'=>$class);
			//$out .= '<tr><th>'.df_escape($field['widget']['label']).'</th><td id="td-'.rand().'" class="'.$class.'">'.$val.'</td></tr>';
			unset($field);
		}
		//$out .= '</tbody></table>';
		//import('Dataface/Ontology.php');
		
		//Dataface_Ontology::registerType('Event', 'Dataface/Ontology/Event.php', 'Dataface_Ontology_Event');
		//$ontology =& Dataface_Ontology::newOntology('Event', $query['-table']);
		
		//$event =& $ontology->newIndividual($record);
		
		ob_start();
		df_display(array('fields'=>&$dl, 'event'=>&$record), 'Dataface_AjaxEventDetails.html');
		$out = ob_get_contents();
		ob_end_clean();
		
		$response = array('record_id'=>$record_id, 'details'=>$out);
		
		import('Services/JSON.php');
		$json = new Services_JSON;
		echo $json->encode($response);
		exit;
		
		
	}
	
}
