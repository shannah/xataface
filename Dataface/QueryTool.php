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
/*******************************************************************************
 * File:		Dataface/QueryTool.php
 * Author:		Steve Hannah <shannah@sfu.ca>
 * Created:	September 4, 2005
 * Description:
 * 	Encapsulates query results from a table.
 ******************************************************************************/
import( 'Dataface/QueryBuilder.php');
import( 'Dataface/Table.php');
import( 'Dataface/Record.php');
import( 'Dataface/DB.php');


$GLOBALS['Dataface_QueryTool_limit'] = 30;
$GLOBALS['Dataface_QueryTool_skip'] = 0;
class Dataface_QueryTool {
    /**
     * @var Dataface_QueryTool
     */
    static $lastIterated = null;

	var $_table;
	var $_db;
	var $_tablename;
	
	var $_query;
	
	var $_data;
	
	var $_currentRecord = null;
	var $_titles;
	
	var $dbObj = null;
	
	function &staticCache(){
		static $cache = 0;
		if ( $cache === 0 ){
			$cache = array();
		}
		return $cache;
	}
	
	
	/**
	 * Creates a new query tool.
	 * @param $tablename The name of the table on which this query is based.
	 * @param $db The database handle.
	 * @param $query Associative array of query parameters.
	 */
	function Dataface_QueryTool($tablename, $db=null, $query=null){
		$this->dbObj =& Dataface_DB::getInstance();
		$this->_tablename = $tablename;
		if ( !is_array($query) ) $query= array();
		if ( $db === null ){
			$db = DATAFACE_DB_HANDLE;
		}
		$this->_db = $db;
		$this->_query = $query;
		
		
		$this->_table =& Dataface_Table::loadTable($tablename);
		
		$this->_data = array();
		if ( isset( $query['-cursor'] ) ){
			$this->_data['cursor'] = $query['-cursor'];
		} else {
			$this->_data['cursor'] = 0;
		}
		
		if ( isset( $query['-skip'] ) ){
			$this->_data['start'] = $query['-skip'];
		} else {
			$this->_data['start'] = 0;
		}
		
		if ( isset( $query['-limit'] ) ){
			$this->_data['end'] = $this->_data['start'] + $query['-limit']-1;
			$this->_data['limit'] = $query['-limit'];
		} else {
			$this->_data['end'] = $this->_data['start'] + $GLOBALS['Dataface_QueryTool_limit']-1;
			$this->_data['limit'] = $GLOBALS['Dataface_QueryTool_limit'];
			
		}
		
		$tableKeyNames = array_keys($this->_table->keys());
		if ( count($tableKeyNames) <= 0 ) throw new Exception("The table '$tablename' has no primary key.  Please add one.", E_USER_ERROR);
		
		$firstKeyName = $tableKeyNames[0];
		
		$cache =& $this->staticCache();
		$sql = "select count(`$firstKeyName`) from `$tablename`";
		
		if ( isset($cache[$sql]) ) $this->_data['cardinality'] = $cache[$sql];
		else {
			$res = $this->dbObj->query( $sql, $this->_db,null, true /*as array*/);
			if ( !$res and !is_array($res) ) throw new Exception("We had a problem with the query $sql.", E_USER_ERROR);

			$this->_data['cardinality'] = reset($res[0]);
			$cache[$sql] = $this->_data['cardinality'];
		}
		
		$builder = new Dataface_QueryBuilder( $tablename, $this->_query);
		$builder->selectMetaData = true;
		$sql = $builder->select_num_rows();
		if ( isset($cache[$sql]) ){
			$this->_data['found'] = $cache[$sql];
		} else {
		
			$res = $this->dbObj->query( $sql, $this->_db,null, true /*as array*/);
			if ( !$res and !is_array($res) ){
				throw new Exception(mysql_error($this->_db).$sql, E_USER_ERROR);
			}
			$this->_data['found'] = array_shift($res[0]);//mysql_fetch_row( $res );
			$cache[$sql] = $this->_data['found'];
		}
		
		if ( $this->_data['end'] > $this->_data['found']-1 ){
			$this->_data['end'] = $this->_data['found']-1;
		}
		if ( $this->_data['start'] > $this->_data['found'] ){
			$this->_data['start'] = $this->_data['found'];
		}
		
		
	}
	
	function getTitles($ordered=true, $genericKeys = false, $ignoreLimit=false){
		$app =& Dataface_Application::getInstance();
		if ( !isset($this->_titles[$ordered][$genericKeys][$ignoreLimit]) ){
			$titleColumn = $this->_table->titleColumn();

			$keys = array_keys($this->_table->keys());
			if ( !is_array($keys) || count($keys) == 0 ){
				throw new Exception(
					df_translate(
						'No primary key defined',
						'There is no primary key defined on table "'.$this->_table->tablename.'". Please define a primary key.',
						array('table'=>$this->_table->tablename, 'stack_trace'=>'')
						),
					E_USER_ERROR
					);
			}
			$len = strlen($titleColumn);
			if ( $titleColumn{$len-1} != ')' and $titleColumn{$len-1} != '`') $titleColumn = '`'.$titleColumn.'`';
			
			$builder = new Dataface_QueryBuilder( $this->_tablename, $this->_query);
			$builder->action = 'select';
			$from = $builder->_from();
			$sql = "SELECT `".implode('`,`',$keys)."`,$titleColumn as `__titleColumn__` $from";
			$where = $builder->_where();
			$where = $builder->_secure($where);
			$limit = $builder->_limit();
			if ( strlen($where)>0 ){
				$sql .= " $where";
			}
			if ( $ordered ){
				$sql .= " ORDER BY `__titleColumn__`";
			} else {
				$sql .= $builder->_orderby();
			}
			if ( strlen($limit)>0 and !$ignoreLimit ){
				$sql .= " $limit";
			} else if ( !$ignoreLimit) {
				$sql .= " LIMIT 250";
			}
			$res = $this->dbObj->query($sql, $this->_table->db, null,true /* as array */);
			if ( !$res and !is_array($res) ){
				$app->refreshSchemas($this->_table->tablename);
					// updates meta tables such as workflow tables to make sure that they
					// are up to date.
				$res = $this->dbObj->query($sql, $this->_table->db,null, true /* as array */);
				if ( !$res and !is_array($res) )
					throw new Exception(
						df_translate(
							'scripts.Dataface.QueryTool.getTitles.ERROR_ERROR_RETRIEVING_TITLES',
							"Error retrieving title from database in Dataface_QueryTool::getTitles(): "
							)
						.$sql.mysql_error($this->_table->db), E_USER_ERROR);
			}
			$titles = array();
			//while ( $row = mysql_fetch_row($res) ){
			foreach ( $res as $row ){
				$title = array_pop($row); 
				if ( !$genericKeys) {
					$keyvals = array();
					reset($keys);
					while ( sizeof($row)>0 ){
						$keyvals[current($keys)] = array_shift($row);
						next($keys);
					}
					
					$keystr = '';
					foreach ($keyvals as $keykey=>$keyval){
						$keystr .= urlencode($keykey)."=".urlencode($keyval)."&";
					}
					$keystr = substr($keystr, 0, strlen($keystr)-1);
					$titles[$keystr] = $title;
				} else {
					$titles[] = $title;
				}
				
			}
			//@mysql_free_result($res);

			$this->_titles[$ordered][$genericKeys][$ignoreLimit] =& $titles;
		}

		return $this->_titles[$ordered][$genericKeys][$ignoreLimit];
	}
	
	/**
	 * Loads the results into an array.
	 * Array keys are concatenated values of primary key fields.
	 * @param $columns Array of column names to return.
	 * @param $loadText Defaults to false.  If true, returns text fields as well.
	 * @param $loadBlobs Defaults to false. If true, returns blob fields as well.
	 */
	 
	function loadSet($columns='', $loadText=false, $loadBlobs=false, $preview=true){
		$app =& Dataface_Application::getInstance();
		//It turns out that QueryBuilder handles whether or not blobs should be loaded so we won't worry about that here.
		$loadText=true;
		$loadBlobs=true;
		
		$fields = $this->_table->fields(false, true);
		$fieldnames = array_keys($fields);
		$builder = new Dataface_QueryBuilder($this->_tablename, $this->_query);
		$builder->selectMetaData = true;
			// We set selectMetaData true so that the field lengths will be loaded as well.
			// This is especially useful for blob fields, since we don't load blobs - but
			// we still want to know th size of the blob.
			
		// initialize the loaded mask
		if ( !isset( $this->_data['loaded'] ) ){
			$this->_data['loaded'] = array();
			foreach ($fieldnames as $fieldname){
				$this->_data['loaded'][$fieldname] = false;
			}
		}
		$loaded =& $this->_data['loaded'];
		
		// figure out which columns still need to be loaded.
		$cols = array();
		if ( is_array($columns) ){
			$cols = $columns;
		} else {
			foreach ($fieldnames as $col){
				if ( $loaded[$col] ) continue;
				$cols[] = $col;
			}
		}
		
		if ( sizeof( $cols ) > 0   ){
			// we need to load a couple of columns
			$tablekeys = array_keys($this->_table->keys());
			$select_cols = array_merge($cols, $tablekeys);
			$sql = $builder->select($select_cols, array(), false, null, $preview);
			

			$res = $this->dbObj->query( $sql, $this->_db, null, true/*as array*/);
			if ( !$res and !is_array($res) ){
				$app->refreshSchemas($this->_table->tablename);
				$res = $this->dbObj->query( $sql, $this->_db, null, true/*as array*/);
				if ( !$res and !is_array($res) )
					
					throw new Exception(
						df_translate(
							'scripts.Dataface.QueryTool.loadSet.ERROR_LOADING_RECORDS',
							"Error loading records in Dataface_QueryTool::loadSet(): "
							)
						.mysql_error($this->_db)."\n<br>".$sql, E_USER_ERROR);
			}
			if ( !isset( $this->_data['start'] ) )
				$this->_data['start'] = $this->_query['-skip'];
			if ( !isset( $this->_data['end'] ) )
				$this->_data['end'] = $this->_query['-skip'] + count($res);//mysql_num_rows($res);
			
			if ( !isset( $this->_data['data'] ) ){
				$this->_data['data'] = array();
				$this->_data['indexedData'] = array();
			}
			
			$fieldnames = array_keys( $this->_table->fields(false, true) );
			
			foreach ( $res as $row){
				$key='';
				foreach ($tablekeys as $name){
					$key .= $row[$name];
				}
				
				foreach ($row as $att=>$attval){
					if ( !in_array($att, $fieldnames) and strpos($att,'__')!== 0 ){
						unset( $row[$att] );
					}
				}
				
				if ( !isset( $this->_data['data'][$key] ) ){
					$this->_data['data'][$key] = $row;
					$this->_data['indexedData'][] =& $this->_data['data'][$key];
				} else {
					foreach ($cols as $col){
						$this->_data['data'][$key][$col] = $row[$col];
					}
				}
			}
			//@mysql_free_result($res);
			
			foreach ($cols as $col){
				$loaded[$col] = true;
			}
				
			
		}
		$cache =& $this->staticCache();
		if (!isset( $this->_data['found'] ) ){
			
			$sql = $builder->select_num_rows();
			
			if ( isset($cache[$sql]) ){
				$this->_data['found'] = $cache[$sql];
			} else {
				$res = $this->dbObj->query( $sql, $this->_db,null, true /*as array*/);
				$this->_data['found'] = array_shift($res[0]);
				$cache[$sql] = $this->_data['found'];
			}
			
		} 
		
		if ( !isset( $this->_data['cardinality'] ) ){
			$tableKeyNames = array_keys($this->_table->keys());
			if ( count($tableKeyNames) <= 0 ) throw new Exception("The table '$tablename' has no primary key.  Please add one.", E_USER_ERROR);
			
			$firstKeyName = $tableKeyNames[0];
			$sql = "select count(`$firstKeyName`) from `".$this->_tablename.'`';
			
			if ( isset($cache[$sql]) ) $this->_data['cardinality'] = $cache[$sql];
			else {
				$res = $this->dbObj->query( $sql, $this->_db,null, true /*as array*/);
				$this->_data['cardinality'] = array_shift($res[0]);
				$cache[$sql] = $this->_data['cardinality'];
			}
		
			
		}	
		
		return true;
		
				
	}
	
	function &loadCurrent($columns=null, $loadText=true, $loadBlobs=false, $loadPasswords=false){
		$app =& Dataface_Application::getInstance();
		$false = false; // boolean placeholders for values needing to be returned by reference
		$true = true;
		
		if ( $this->_currentRecord === null ){
			//require_once 'Dataface/IO.php';
			//$io = new Dataface_IO($this->_table->tablename);
			//$query = array_merge( $this->_query, array('-skip'=>$this->_data['cursor'], '-limit'=>1) );
			$this->_currentRecord = new Dataface_Record($this->_table->tablename, array());
			//$io->read($query, $this->_currentRecord);
			
		}
		//return $this->_currentRecord;
		
		
		$unloaded =  array();
		$fields =& $this->_table->fields(false, true);
		if ( $columns === null ) {
			$names = array_keys($fields);
		} else {
			$names = $columns;
		}
		
		foreach ($names as $name){
			if ( !$this->_currentRecord->isLoaded($name) ){
				if ( !$loadText and $this->_table->isText($name) ) continue;
				if ( !$loadBlobs and $this->_table->isBlob($name) ) continue;
				if ( !$loadPasswords and $this->_table->isPassword($name) ) continue;
				$unloaded[] = $name;
			}
		}
		
		if ( sizeof( $unloaded ) > 0 ){
			
			$query = array_merge( $this->_query, array('-skip'=>$this->_data['cursor'], '-limit'=>1) );
			$builder = new Dataface_QueryBuilder( $this->_tablename, $query);
			$builder->selectMetaData = true;
			$builder->_omitBlobs = false;
		
			$sql = $builder->select($unloaded);
			//echo $sql;
			if ( PEAR::isError($sql) ){
				throw new Exception($sql->toString(), E_USER_ERROR);
			}
			
			//echo $sql;
			$res = $this->dbObj->query($sql, $this->_db,null, true /* as array */);
			if ( !$res and !is_array($res) ){
				$app->refreshSchemas($this->_table->tablename);
				$res = $this->dbObj->query($sql, $this->_db, null,true /* as array */);
				if ( !$res and !is_array($res) ){
					error_log(df_translate('scripts.Dataface.QueryTool.loadCurrent.ERROR_COULD_NOT_LOAD_CURRENT_RECORD',"Error: Could not load current record: ").mysql_error( $this->_db)."\n$sql");
					throw new Exception("Failed to load current record due to an SQL error");
					
				}
			}
			if (count($res) <= 0 ){
				return $false;
			}
			$row = $res[0]; //mysql_fetch_assoc($res);
			//@mysql_free_result($row);
			$this->_currentRecord = new Dataface_Record($this->_table->tablename, $row);
			//$this->_table->setValues($row);
			//$this->_table->setSnapshot();
			//$this->_table->deserialize();
		} 
		
		return $this->_currentRecord;
		
	
	}
	
	
	function found(){
		if (!isset( $this->_data['found'] ) ){
			$cache =& $this->staticCache();
			$builder = new Dataface_QueryBuilder($this->_tablename,$this->_query);
			$sql = $builder->select_num_rows();
			if ( isset($cache[$sql]) ) $this->_data['found'] = $cache[$sql];
			else {
				$res = $this->dbObj->query( $sql, $this->_db, null,true /*as array*/);
				$this->_data['found']  = array_shift($res[0]);
				$cache[$sql] = $this->_data['found'];
			}
			
		} 
		return $this->_data['found'];
	
	}
	
	
	function cardinality(){
		if ( !isset( $this->_data['cardinality'] ) ){
			$cache =& $this->staticCache();
			$tableKeyNames = array_keys($this->_table->keys());
			if ( count($tableKeyNames) <= 0 ) throw new Exception("The table '$tablename' has no primary key.  Please add one.", E_USER_ERROR);
			
			$firstKeyName = $tableKeyNames[0];
			$sql = "select count(`$firstKeyName`) from ".$this->_tablename;
			if ( isset($cache[$sql]) ) $this->_data['cardinality'] = $cache[$sql];
			else {
				$res = $this->dbObj->query( $sql, $this->_db,null, true /*as array*/); 
				$this->_data['cardinality'] = array_shift($res[0]);
				$cache[$sql]  = $this->_data['cardinality'];
			}
		
			
		}	
		return $this->_data['cardinality'];
	}
	
	function start(){
		return $this->_data['start'];
	}
	function end(){
		return $this->_data['end'];
	}
	
	function &data(){
		return $this->_data['data'];
	}
	
	function &iterator(){
		$it = new Dataface_RecordIterator($this->_tablename, $this->data());
		return $it;
	}
	
	function getRecordsArray(){
	
		$records = array();
		$it = $this->iterator();
		if ( PEAR::isError($it) )return $it;
		while ($it->hasNext()){
			$records[] = $it->next();
		}
		return $records;
	}
	
	function limit(){
		return $this->_data['limit'];
	}
	

	function cursor(){
		return $this->_data['cursor'];
	}
	
	function &indexedData(){
		return $this->_data['indexedData'];
	}
	
	public static function &loadResult($tablename, $db=null, $query=''){
		if ( $db === null and defined('DATAFACE_DB_HANDLE') ) $db = DATAFACE_DB_HANDLE;
		if ( !isset( $resultDescriptors ) ){
			static $resultDescriptors = array();
		}
		
		if ( is_array($query) and @$query['--no-query'] ){
			$out = new Dataface_QueryTool_Null($tablename, $db, $query);
			return $out;
		}
		
		if ( !isset( $resultDescriptors[$tablename] ) ){
			$resultDescriptors[$tablename] = new Dataface_QueryTool($tablename, $db , $query);
		}
		return $resultDescriptors[$tablename];
	}
	
}


class Dataface_QueryTool_Null extends Dataface_QueryTool {

	function &staticCache(){
		static $cache = 0;
		if ( $cache === 0 ){
			$cache = array();
		}
		return $cache;
	}
	
	
	/**
	 * Creates a new query tool.
	 * @param $tablename The name of the table on which this query is based.
	 * @param $db The database handle.
	 * @param $query Associative array of query parameters.
	 */
	function __construct($tablename, $db=null, $query=null){
		
		
		
	}
	
	function getTitles($ordered=true, $genericKeys = false, $ignoreLimit=false){
		return array();
	}
	
	/**
	 * Loads the results into an array.
	 * Array keys are concatenated values of primary key fields.
	 * @param $columns Array of column names to return.
	 * @param $loadText Defaults to false.  If true, returns text fields as well.
	 * @param $loadBlobs Defaults to false. If true, returns blob fields as well.
	 */
	 
	function loadSet($columns='', $loadText=false, $loadBlobs=false, $preview=true){
		return true;
		
				
	}
	
	function &loadCurrent($columns=null, $loadText=true, $loadBlobs=false, $loadPasswords=false){
		return null;
		
	
	}
	
	
	function found(){
		return 0;
	
	}
	
	
	function cardinality(){
		return 0;
	}
	
	function start(){
		return 0;
	}
	function end(){
		return 0;
	}
	
	function &data(){
		return array();
	}
	
	function &iterator(){
	    self::$lastIterated = $this;
		$it = new Dataface_RecordIterator($this->_tablename, $this->data());
		return $it;
	}
	
	function getRecordsArray(){
		return array();
	}
	
	function limit(){
		return 0;
	}
	

	function cursor(){
		return 0;
	}
	
	function &indexedData(){
		return array();
	}
	
	
}
