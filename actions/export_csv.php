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
	
        function writeRow($fh, $data, $query){
            fputcsv($fh, $data,",",'"');
        }
        
        function startFile($fh, $query){
            
        }
        
        function endFile($fh, $query){
            
        }
        
        function writeOutput($fh, $query){
            $app = Dataface_Application::getInstance();
            if ( @$app->_conf['export_csv'] and @$app->_conf['export_csv']['format'] == 'excel' ){
                    
                $this->outputExcelcsv($fh, $query, $app);
            } else {
                $this->outputStandardCsv($fh, $query, $app);
            }
        }
    
	function handle(&$params){
		set_time_limit(0);
		import('Dataface/RecordReader.php');
		$app =& Dataface_Application::getInstance();
		$query = $app->getQuery();
		$query['-limit'] = 9999999;
		if (isset($query['--limit'])) {
		    $query['-limit'] = intval($query['--limit']);
		}
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
				
				// WIDGET LABEL INSTEAD OF DB COLUMN NAME - CGD
				if (@$app->_conf ['export_csv'] and @$app->_conf ['export_csv'] ['heading'] == 'label')
					$headings [] = @$f ['widget'] ['label'];
				else
					$headings [] = $colhead;
		
				unset($f);
			}
			$data[] = $headings;
			foreach ($records as $record){
				if ( !$record->checkPermission('view') ) continue;
				$data[] = $this->related_rec2data($record);
			}
			$temp = tmpfile();
                        $this->startFile($temp, $query);
			foreach ($data as $row){
				$this->writeRow($temp, $row, $query);//, $recordfputcsv($temp, $row,",",'"');
			}
		} else {
			$temp = tmpfile();
                        
                        $query['-skip'] = 0;
			$query['-limit'] = null;
			$records = new Dataface_RecordReader($query, 30, false);
			//$records =& df_get_records_array($query['-table'], $query,null,null,false);
			//$data = array();
			$headings = array();
			//foreach (array_merge(array_keys($table->fields()), array_keys($table->graftedFields())) as $colhead){
			foreach (array_keys($table->fields(false, true)) as $colhead){
				$f =& $table->getField($colhead);
				if ( @$f['visibility']['csv'] == 'hidden' ){
					unset($f);
					continue;
				}
				
				// WIDGET LABEL INSTEAD OF DB COLUMN NAME - CGD
				if (@$app->_conf ['export_csv'] and @$app->_conf ['export_csv'] ['heading'] == 'label')
					$headings [] = @$f ['widget'] ['label'];
				else
					$headings [] = $colhead;
				
				unset($f);
			
			}
			//$data[] = $headings;
                        $this->startFile($temp, $query);
                        $this->writeRow($temp, $headings, $query);
			//fputcsv($temp, $headings,",",'"');
			foreach ($records as $record){
				if ( !$record->checkPermission('view') ) continue;
				$data = $this->rec2data($record);
				//fputcsv($temp, $data,",",'"');
                                $this->writeRow($temp, $data, $query);
			}
		}
		$this->endFile($temp, $query);
		
		fseek($temp,0);
		$this->writeOutput($temp, $query);
                    
		exit;
		
		
		
	}
        
        function outputStandardCsv($fh, $query, $app){
            header("Content-type: text/csv; charset={$app->_conf['oe']}");
            header('Content-disposition: attachment; filename="'.$query['-table'].'_results_'.date('Y_m_d_H_i_s').'.csv"');

            //$fstats = fstat($fh);
            while ( @ob_end_clean() );
            //echo fread($temp, $fstats['size']);
            fpassthru($fh);
            fclose($fh);
        }
	
        function outputExcelCsv($fh, $query, $app){
            $sep  = "\t";
            $eol  = "\n";
            $stdout = fopen('php://output', 'w');
            header('Content-Description: File Transfer');
            header('Content-Type: application/vnd.ms-excel');
            header('Content-disposition: attachment; filename="'.$query['-table'].'_results_'.date('Y_m_d_H_i_s').'.csv"');
            header('Content-Transfer-Encoding: binary');
            fwrite($stdout, chr(255) . chr(254));
            while ( ($row = fgetcsv($fh, 0, ',', '"')) !== false ){
                //print_r($row);
                ob_start();
                fputcsv($stdout, $row, $sep);
                $rowText = ob_get_contents();
                //echo $rowText.'now';exit;
                ob_end_clean();
                
                $rowText =  mb_convert_encoding($rowText, 'UTF-16LE', 'UTF-8');
                fwrite($stdout, $rowText);
            }
            fclose($fh);
            fclose($stdout);
        }
        
	function rec2data(&$record){
		$out = array();
		//$columns = array_merge(array_keys($record->_table->fields()), array_keys($record->_table->graftedFields()));
		$columns = array_keys($record->_table->fields(false, true));
		
		foreach ($columns as $key){
			$f =& $record->_table->getField($key);
                        $del = $record->_table->getDelegate();
			if ( @$f['visibility']['csv'] == 'hidden' ){
				unset($f);
				continue;
			}
                        $csvMethod = $key.'__csvValue';
                        if ( isset($del) and method_exists($del, $csvMethod)){
                            $out[] = $del->$csvMethod($record);
                        } else {
                            $out[] = $record->display($key);
                        }
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
                        $del = $r->getTable($col)->getDelegate();
			$csvMethod = $col.'__csvValue';
                        if ( isset($del) and method_exists($del, $csvMethod) ){
                            $out[] = $del->csvMethod($record->toRecord($col));
                        } else {
                            $out[] = $record->display($col);
                        }
			unset($f);
		}
		return $out;
		
	}

}

?>
