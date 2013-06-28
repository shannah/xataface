<?php
/**
 * Displays the history of the current record.
 */
class dataface_actions_history {

	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		$record =& $app->getRecord();
		$context = array();
		if ( !$record ) return PEAR::raiseError("No record is currently selected", DATAFACE_E_ERROR);
		
		$history_tablename = $record->_table->tablename.'__history';
		if ( !Dataface_Table::tableExists($history_tablename) )
			$context['error'] = PEAR::raiseError("This record has no history yet recorded.", DATAFACE_E_NOTICE);
		else {
			import('Dataface/HistoryTool.php');
			$history_tool = new Dataface_HistoryTool();
			$history_log = $history_tool->getHistoryLog($record);
			$context['log'] =& $history_log;
			
			
			// let's make a query string for the current record
			//current_record_qstr
			
			$keys = array_keys($record->_table->keys());
			$qstr = array();
			foreach ( $keys as $key){
				$qstr[] = urlencode('--__keys__['.$key.']').'='.urlencode($record->strval($key));
			}
			$context['current_record_qstr'] = implode('&',$qstr);
		}
		
		df_display($context, 'Dataface_RecordHistory.html');
	}

}

?>
