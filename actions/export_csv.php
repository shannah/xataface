<?php
if ( !function_exists('prepare_csv') ){
	/**
	 * This function is necessary to prepare data for inclusion in a 
	 * CSV cell.  Originally I thought we could just use 'addslashes',
	 * but apparently quotes should just be doubled rather than 
	 * escpaed (e.g. "").
	 */
	function prepare_csv($str){
		return str_replace('"','""',$str);
	}
}

if ( !function_exists('fputcsv') ){
	/**
	 * putcsv was not included in PHP until version 5.  Provide alternative 
	 * implementation here.
	 * Taken from http://ca3.php.net/manual/en/function.fputcsv.php#56827
	 */
	 function fputcsv($filePointer,$dataArray,$delimiter=',',$enclosure='"')
	{
		// Write a line to a file
		// $filePointer = the file resource to write to
		// $dataArray = the data to write out
		// $delimeter = the field separator
		
		
		// Build the string
		$dataArray = array_map('prepare_csv', $dataArray);
		$string = $enclosure.implode($enclosure.$delimiter.$enclosure, $dataArray).$enclosure;
		
		
		// Append new line
		$string .= "\n";
		
		// Write the string to the file
		fwrite($filePointer,$string);
	}
}



class dataface_actions_export_csv {
	
	function handle(&$params){
		set_time_limit(0);
		import('Dataface/RecordReader.php');
		$app =& Dataface_Application::getInstance();
		$query = $app->getQuery();
		$query['-limit'] = 9999999;
		$table =& Dataface_Table::loadTable($query['-table']);
		if ( isset($query['-relationship']) and @$query['--related'] ){
			$query['-related:start'] = 0;
			$query['-related:limit'] = 9999999;
			$record =& $app->getRecord();
			$relationship =& $table->getRelationship($query['-relationship']);
			
			$records =& df_get_related_records($query); //$record->getRelatedRecordObjects($query['-relationship']);
			
			$data = array(/*$relationship->_schema['short_columns']*/);
			$headings = array();
			foreach ( $relationship->_schema['short_columns'] as $colhead ){
				$f =& $relationship->getField($colhead);
				if ( @$f['visibility']['csv']  == 'hidden' ){
					unset($f);
					continue;
				}
				$headings[] = $colhead;
				unset($f);
			}
			$data[] = $headings;
			foreach ($records as $record){
				if ( !$record->checkPermission('view') ) continue;
				$data[] = $this->related_rec2data($record);
			}
			$temp = tmpfile();
			foreach ($data as $row){
				fputcsv($temp, $row,",",'"');
			}
		} else {
			$temp = tmpfile();
			$query['-skip'] = 0;
			$query['-limit'] = null;
			$records = new Dataface_RecordReader($query);
			//$records =& df_get_records_array($query['-table'], $query,null,null,false);
			//$data = array();
			$headings = array();
			foreach (array_merge(array_keys($table->fields()), array_keys($table->graftedFields())) as $colhead){
				$f =& $table->getField($colhead);
				if ( @$f['visibility']['csv'] == 'hidden' ){
					unset($f);
					continue;
				}
				$headings[] = $colhead;
				unset($f);
			
			}
			//$data[] = $headings;
			fputcsv($temp, $headings,",",'"');
			foreach ($records as $record){
				if ( !$record->checkPermission('view') ) continue;
				$data = $this->rec2data($record);
				fputcsv($temp, $data,",",'"');
			}
		}
		
		
		fseek($temp,0);
		header("Content-type: text/csv; charset={$app->_conf['oe']}");
		header('Content-disposition: attachment; filename="'.$query['-table'].'_results_'.date('Y_m_d_H_i_s').'.csv"');
		
		$fstats = fstat($temp);
		while ( ob_end_clean() );
		//echo fread($temp, $fstats['size']);
		fpassthru($temp);
		fclose($temp);
		exit;
		
		
		
	}
	
	function rec2data(&$record){
		$out = array();
		$columns = array_merge(array_keys($record->_table->fields()), array_keys($record->_table->graftedFields()));
		
		foreach ($columns as $key){
			$f =& $record->_table->getField($key);
			if ( @$f['visibility']['csv'] == 'hidden' ){
				unset($f);
				continue;
			}
			$out[] = $record->display($key);
			unset($f);
		}
		return $out;
	}
	
	function related_rec2data(&$record){
		$out = array();
		$r =& $record->_relationship;
		foreach ($r->_schema['short_columns'] as $col){
			$f =& $r->getField($col);
			if ( @$f['visibility']['csv'] == 'hidden' ){
				unset($f);
				continue;
			}
			$out[] = $record->display($col);
			unset($f);
		}
		return $out;
		
	}

}

?>
