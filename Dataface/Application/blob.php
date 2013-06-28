<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp (shannah@sfu.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */
 
/**
 * File: Dataface/Application/blob.php
 * Created: March 25, 2006
 * Author: Steve Hannah <shannah@sfu.ca>
 *
 * Description: Created for the sole purpose of factoring blob handling out of Dataface_Application.
 * Despite the factoring out, this class is only to be accessed via Dataface_Application.
 */
 
class Dataface_Application_blob {

	
	static function _parseRelatedBlobRequest($request){
		$app = Dataface_Application::getInstance();
		import('dataface-public-api.php');
		if ( !isset( $request['-field'] ) ) die("Could not complete request.  No field name specified.");
		if ( !isset( $request['-table'] ) ) die("Could not complete request.  No table specified.");
		
		$record =& df_get_record($request['-table'], $request);
		if ( strpos($request['-field'], '.') === false ){
			die("ParseRelatedBlobRequest only works for -field parameters refering to related fields.");
		}
		list($relationship, $relativeField) = explode('.', $request['-field']);
		if ( @$request['-where'] ) $where = stripslashes($request['-where']);
		else $where = 0;
		$rrecords =& $record->getRelatedRecordObjects($relationship, 0, 1, $where);
		if (count($rrecords) == 0 ){
			die("No records found");
		}
		$rrecord =& $rrecords[0];
		
		$relationshipRef =& $rrecord->_relationship;
		$domainTable =& $relationshipRef->getDomainTable();
		if ( !$domainTable || PEAR::isError($domainTable) ){
			unset($domainTable);
			$destinationTables = $relationshipRef->destinationTables();
			$domainTable = reset($destinationTables);
		}
		$out = array('-table'=>$domainTable, '-field'=>$relativeField);
		
		$domainTableRef =& Dataface_Table::loadTable($domainTable);
		foreach ( array_keys($domainTableRef->keys()) as $key){
			$out[$key] = $rrecord->strval($key);
		}
		
		return $out;
		
	
	}
	
	
	/**
	 *
	 * Blob requests are ones that only want the content of a blob field in the database.
	 * These requests are special in that they will not generally return a content-type of
	 * text/html.  These are often images.
	 *
	 * @param $request  A reference to the global $_REQUEST variable generally.
	 *
	 */
	static function _handleGetBlob($request){
		$app = Dataface_Application::getInstance();
		import( 'Dataface/Table.php');
		import('Dataface/QueryTool.php');
		
		if ( strpos(@$request['-field'], '.') !== false ){
			$request = self::_parseRelatedBlobRequest($request);
		}
			
		if ( !isset( $request['-field'] ) ) die("Could not complete request.  No field name specified.");
		if ( !isset( $request['-table'] ) ) die("Could not complete request.  No table specified.");
		$fieldname = $request['-field'];
		$tablename = $request['-table'];
		
		$table =& Dataface_Table::loadTable($tablename);
		$keys = array_keys($table->keys());
		
		
		$lastTableUpdate = $table->getUpdateTime();
		$lastTableUpdate = strtotime($lastTableUpdate);
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$rec =& df_get_record($table->tablename, $query);
		if ( !$rec ) throw new Exception("No record found to match the request.", E_USER_ERROR);
			
		$field =& $table->getField($fieldname);
		if ( PEAR::isError($field) ){
			header('HTTP/1.0 500 Internal Server Error');
			echo '<h1>Internal Server Error</h1>';
			error_log($field->getMessage());
			exit;
		}
		
		if ( !$rec->checkPermission('view', array('field'=>$fieldname)) ){
			header('HTTP/1.1 403 Forbidden');
			echo '<h1>Access Forbidden</h1>';
			exit;
		}
		
		if ( $table->isContainer($fieldname) ){
			
			$savepath = $field['savepath'];
			
			
			if ( !$rec->val($fieldname) ){
				header('HTTP/1.0 404 Not Found');
				echo '<h1>404 File Not Found</h1>';
				exit;
			}
			
			
			header('Content-type: '.$rec->getMimetype($fieldname));
			header('Content-disposition: attachment; filename="'.basename($rec->val($fieldname)).'"');
			echo file_get_contents($savepath.'/'.basename($rec->val($fieldname)));
			exit;
			
		}
		if ( !$table->isBlob($fieldname) ) die("blob.php can only be used to load BLOB or Binary columns.  The requested field '$fieldname' is not a blob");
		//$field =& $table->getField($fieldname);

		if ( isset($request['-index']) ) $index = $request['-index'];
		else $index = 0;
		
		$cachePath = $app->_conf['cache_dir'].'/'.basename($app->_conf['_database']['name']).'-'.basename($tablename).'-'.basename($fieldname).'-'.basename($index).'?';
		foreach ($keys as $key){
			$cachePath .= urlencode($key).'='.urlencode($_REQUEST[$key]).'&';
		}
		
		$queryTool =& Dataface_QueryTool::loadResult($tablename, null, $request);

		// No mimetype was recorded.  Use the PECL Fileinto extension if it is available.
		
		$files = glob($cachePath.'-*');
		$found = false;
			
		if ( is_array($files) ){
			foreach ($files as $file){
				$matches = array();
				if ( preg_match('/.*-([^\-]+)$/', $file, $matches) ){
					$time = $matches[1];
					if ( intval($time)>$lastTableUpdate){
						$found = $file;
						break;
					} else {
						@unlink($file);
					}
				}
			}
		}
		
		if ( $found !== false ){
			$contents = file_get_contents($found);
		} else {
			$columns = array($fieldname);
			
			if ( isset($field['mimetype']) and $field['mimetype']){
				$columns[] = $field['mimetype'];
			}
			if ( isset($field['filename']) and $field['filename']){
				$columns[] = $field['filename'];
			}
			$record =& $queryTool->loadCurrent($columns, true, true);
			$record->loadBlobs = true;
			$contents = $record->getValue($fieldname, $index);
			$found = $cachePath.'-'.time();
			$found=str_replace("?","-",$found);
			if ( $fh = fopen($found, "w") ){
				fwrite($fh, $contents);
				fclose($fh);
			} else {
				$found = false;
			}
		}
	
		if ( !isset( $record ) ){
			$columns = array();
			if ( isset($field['mimetype']) and $field['mimetype']){
				$columns[] = $field['mimetype'];
			}
			if ( isset($field['filename']) and $field['filename']){
				$columns[] = $field['filename'];
			}

			$record =& $queryTool->loadCurrent($columns);
		}
		
		if ( isset($field['mimetype']) and $field['mimetype']){
			$mimetype = $record->getValue($field['mimetype'], $index);
		}
		if ( isset($field['filename']) and $field['filename']){
			$filename = $record->getValue($field['filename'], $index);
		}
		//$mimetype = $record->getValue($field['mimetype'], $index);
			//echo $mimetype; exit;
		 
			
		if ( (!isset($mimetype) or !$mimetype) and $found !== false ){
			
			if(extension_loaded('fileinfo')) {
				$res = finfo_open(FILEINFO_MIME_TYPE); /* return mime type ala mimetype extension */
				$mimetype = finfo_file($res, $found);
			} else if (function_exists('mime_content_type')) {
				
			
				$mimetype = mime_content_type($found);
				
			} else {
				throw new Exception("Could not find mimetype for field '$fieldname'", E_USER_ERROR);
			}
		}
		
		if ( !isset($filename) ){
			$filename = $request['-table'].'_'.$request['-field'].'_'.date('Y_m_d_H_i_s');
		}
		//echo "here"; 	
		//echo "here: $mimetype"; 
		//echo $contents;
		//echo $mimetype; exit;
		header('Content-type: '.$mimetype);
		header('Content-disposition: attachment; filename="'.$filename.'"');
		echo $contents;
		exit;
		
			
		
	}

}
