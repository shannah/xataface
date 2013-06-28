<?php
class dataface_actions_history_restore_record {

	function handle(&$params){
	
		if ( !@$_POST['history__id'] ) return PEAR::raiseError("No history id specified", DATAFACE_E_ERROR);
		$historyid = $_POST['history__id'];
		if ( !preg_match('/\d+/', $historyid) ) return PEAR::raiseError("Invalid history id provided.", DATAFACE_E_ERROR);
		
		$app =& Dataface_Application::getInstance();
		$record =& $app->getRecord();
		if ( !$record ) return PEAR::raiseError("No record was specified", DATAFACE_E_ERROR);
		
		import("Dataface/HistoryTool.php");
		$ht = new Dataface_HistoryTool();
		$hrecord = $ht->getRecordById($record->_table->tablename, $historyid);
		
		// make sure that this history record matches the current record.
		$keys = array_keys($record->_table->keys());
		if ( $record->strvals($keys) != $hrecord->strvals($keys) ) 
			return PEAR::raiseError("Attempt to restore record history from unmatching history record.", DATAFACE_E_ERROR);
			
		
		// Now that we are convinced that we have the correct record, we can restore it.
		if ( @$_POST['-fieldname'] ) $fieldname = $_POST['-fieldname'];
		else $fieldname = null;
		$res = $ht->restore($record, $historyid, $fieldname, true);
		
		if ( PEAR::isError($res) ) return $res;
		
		$url = false; //$app->getPreviousUrl(true);
		if ( @$_POST['-locationid'] ) $url = DATAFACE_SITE_HREF.'?'.$app->decodeLocation($_POST['-locationid']);

		if ( !$url ){
			// If the url is not specified we will just create a url to return
			// to the specified record's history listing.
			$url = $record->getURL('-action=history');
		
		}
		
		if ( $fieldname ){
			$msg = "Field '$fieldname' successfully restored to its value from '".$hrecord->strval('history__modified')."'.";
		} else {
			$msg = "Record successfully restored to its value from '".$hrecord->strval('history__modified')."'.";
		}
		$url .= "&--msg=".urlencode($msg);
		
		$app->redirect($url);
		
	}

}

?>
