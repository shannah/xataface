<?php
import('Dataface/XMLTool.php');
class dataface_actions_export_xml {
	function handle(&$params){
		import('Dataface/XMLTool/default.php');
		$xml = new Dataface_XMLTool_default();
		$xml->expanded = true;
		
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		$input = array();
		
		if ( isset($query['--single-record-only']) ){
			$record =& $app->getRecord();
			if ( $record->checkPermission('view xml') ){
				$input[] = $record;
			}
			
		} else if ( @$query['-relationship'] ) {
			$query['-related:limit'] = 9999;
			$query['-related:start'] = 0;
			$record =& $app->getRecord();
			
			$rrecords =& df_get_related_records($query); // $record->getRelatedRecordObjects( $query['-relationship'], 'all' );
			foreach ($rrecords as $rrecord){
				$drecord =& $rrecord->toRecord();
				if ( $drecord->checkPermission('view xml') ){
					$input[] = $drecord;
				}
				unset($drecord);
				unset($rrecord);
			}
		
		} else {
			$records = df_get_records_array($query['-table'], $query,null,null,false);
			foreach ($records as $record){
				if ( $record->checkPermission('view xml') ){
					$input[] = $record;
				}
			}	
		}
		
		echo $xml->header();
		echo $xml->toXML($input);
		echo $xml->footer();
		exit;
		
	}

}

