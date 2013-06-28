<?php
class dataface_actions_view_history_record_details {
	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		if ( !@$_GET['history__id'] ){
			return PEAR::raiseError('No history id supplied', DATAFACE_E_ERROR);
		}
		$historyid = $_GET['history__id'];
		$query =& $app->getQuery();
		$table = $query['-table'];
		$r = $app->getRecord();
		
		import('Dataface/HistoryTool.php');
		$ht = new Dataface_HistoryTool();
		if ( @$_GET['-fromcurrent'] ){
			$record = $ht->getDiffs($table, $historyid);
			$record->escapeOutput = false;
		
		} else if ( @$_GET['-show_changes'] ){
			$thisVersion = $ht->getRecordById($table, $historyid);
			if ( PEAR::isError($thisVersion) ){
				return $thisVersion;
			} else if ( !$thisVersion ){
				return PEAR::raiseError('No history record found', DATAFACE_E_ERROR);
			}
			$mdate = $thisVersion->strval("history__modified");
			//echo "mdate: ".$mdate;
			$prevDate = date('Y-m-d H:i:s', strtotime('-1 second', strtotime($mdate)));
			//echo " prevdate: ".$prevDate.' ';
			$prevVersionId = $ht->getPreviousVersion($r, $prevDate, $thisVersion->val('history__language'), null, true);
			//echo "Prev: $prevVersionId";
			if ( !$prevVersionId ){
				$record = new Dataface_Record($table.'__history', array());
			} else {
				$record = $ht->getDiffs($table, $prevVersionId, $historyid);
				
			}
			$record->escapeOutput = false;
		} else {
			$record = $ht->getRecordById($table, $historyid);
		}
		if ( !$record ) return PEAR::raiseError("No history record for table {$table} with history id {$historyid} could be found", DATAFACE_E_ERROR);
		if ( PEAR::isError($record) ) return $record;
		
		$record->secureDisplay = false;
		$context = array('history_record'=>&$record);
		$context['source_record'] = $app->getRecord();
		
		
		$t =& Dataface_Table::loadTable($table);
		$numfields = count($t->fields());
		$pts = 0;
		$ppf = array();
		
		$fields = $t->fields();
		$tmp = array();
		foreach ($fields as $k=>$f){
			if ( $r->checkPermission('view', array('field'=>$k)) ){
				$tmp[$k] = $fields[$k];
			}
		}
		$fields = $tmp;
		
		$context['fields'] = $fields;
		
		foreach ($fields as $field){
			if ( $t->isText($field['name']) ){
				$pts+=5;
				$ppf[$field['name']] = $pts;
			} else {
				$pts++;
				$ppf[$field['name']] = $pts;
			}
		}
		
		$firstField = null;
		$threshold = floatval(floatval($pts)/floatval(2));
		foreach ( $fields  as $field){
			if ( $ppf[$field['name']] >= $threshold ){
				$firstField = $field['name'];
				break;
			}
		}
		
		$context['first_field_second_col'] = $firstField;
		$context['changes'] = @$_GET['-show_changes'];
		$context['table'] =& $t;
		df_display($context, 'Dataface_HistoryRecordDetails.html');
		
	}

}
?>
