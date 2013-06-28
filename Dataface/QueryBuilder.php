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
 * File: 		Dataface/QueryBuilder.php
 * Author:		Steve Hannah <shannah@sfu.ca>
 * Created:	Sept. 2, 2005
 * Description:
 * 	Builds SQL queries based on key-value pair queries.
 * 	
 ******************************************************************************/
 
import( 'PEAR.php'); 
import( 'Dataface/Table.php');
import( 'Dataface/Error.php');
import( 'Dataface/Serializer.php');
import('Dataface/DB.php'); // for Blob registry.

define('QUERYBUILDER_ERROR_EMPTY_SELECT', 1);
 
class Dataface_QueryBuilder {

	/**
	 * Associative array containing the query.  
	 * Keys are column names to match.
	 * Values are column values.
	 * Keys beginning with hyphen are ignored in query and treated as special.
	 * Special keys.
	 * -skip : Skip the first N records.
	 * -limit : The max records to return.
	 */
	var $_query;
	
	/**
	 * The name of the table to query.
	 */
	var $_tablename;
	
	/**
	 * @type Dataface_Table .  The table of the table to query.
	 */
	var $_table;
	
	/**
	 * Convenience array.. holds references to $_table->fields()
	 */
	var $_fields;
	
	var $_mutableFields;
	
	/**
	 * If true then queries will use '=' instead of 'LIKE' for matching.
	 */
	var $_exactMatches = false;
	
	/**
	 * If true we will omit blob columns from the result.
	 */
	var $_omitBlobs = true;
	
	/**
	 * We usually don't want to be loading password fields.
	 */
	var $_omitPasswords = true;
	
	/**
	 * Key-value pairs of column name - column value to be used as security
	 * limitations on select queries.  These constraints are added automatically added
	 * to queries to hide rows from the user that he doesn't have access to.  This 
	 * should be seamless.
	 */
	var $_security = array();
	
	
	var $_serializer;
	
	var $errors = array();
	
	/*
	 * Whether or not meta data should be selected along with select statements.
	 * Meta data includes calculations of the "lengths" of the data in particular
	 * fields.  This is particularly handy for blob fields where we are interested
	 * in the size of the blob.
	 */
	var $selectMetaData = false;
	
	/**
	 * Boolean flag indicating whether we should include metadatas in the queries.
	 */
	var $metadata = false;
	
	
	/**
	 * @var string Stores the current action ('select','update','insert','delete','select_num_rows') 
	 * so that submethods know the whole context of what is going on.  In particular this is 
	 * helpful for the _from() method so that it knows to left join onto the metadata table
	 * on selects but not on deletes.
	 */
	var $action = null;
	
	
		

	
	/**
	 * Creates a new Dataface_QueryBuilder object.
	 * @param tablename The name of the table to query.
	 * @param table Reference to the table for the table.
	 * @param query Associative array with keys = column names and values equal
	 *  query values for that column.
	 */
	function Dataface_QueryBuilder($tablename, $query=''){
		$this->_tablename = $tablename;
		$this->_table =& Dataface_Table::loadTable($tablename);
		$this->_query = is_array($query) ? $query : array();
		$this->_fields =& $this->_table->fields(false, true);
		$this->_mutableFields =& $this->_table->fields();
		$this->_serializer = new Dataface_Serializer($tablename);
		$this->action = null;
		
		$app =& Dataface_Application::getInstance();
		if ( @$app->_conf['metadata_enabled'] ){
			$this->metadata = true;
		}
		
		$keys = array_keys( $this->_query );
		foreach ($keys as $key){
			if ( $this->_query[$key] === ''){
				unset( $this->_query[$key] );
			}
		}
		
		
		if ( isset( $GLOBALS['DATAFACE_QUERYBUILDER_SECURITY_CONSTRAINTS'][$tablename]) ){
			$this->_security = $GLOBALS['DATAFACE_QUERYBUILDER_SECURITY_CONSTRAINTS'][$tablename];
		}
		
		
	}
	
	function _opt($code, $isText=true){
		switch ( $code ){
			case '=':
			case '>':
			case '<':
			case '>=':
			case '<=':
				return $code;
			default:
				return 'LIKE';
		}
	}
	
	
	
	/**
	 * Generates SQL to perform a full text search.
	 *
	 * @param $queryStr A query string that can be used in an AGAINST clause of 
	 *                  a MySQL full text search.  Eg: 'foo bar', '+foo -bar', '"foo bar"'
	 * @type string
	 *
	 * @param $columns Optional array of column names to select.
	 * @type array(string)
	 *
	 * @param $queryParams An array of query parameters.
	 * @type array([Paramname]->[Paramval])
	 *
	 * @param $nolimit Indicate whether there should be no limit.
	 * @param boolean
	 *
	 * @returns string SQL query.
	 */
	function search($queryStr, $columns='', $queryParams=array(), $nolimit=false){
		$this->action='search';
		$ret = $this->_select($columns);
		$from = trim($this->_from($this->_table->tablename));
		$indexOnly = ( (isset($queryParams['-ignoreIndex']) and $queryParams['-useIndex']) ? false : true );
		$where = "WHERE ".$this->_match($queryStr, $indexOnly);
		$from = trim( $this->_from($this->_table->tablename));
		$order = trim($this->_orderby($queryParams));
		$limit = trim($this->_limit($queryParams, $nolimit));
		if ( strlen($from)>0 ) $ret .= ' '.$from;
		if ( strlen($where)>0 ) $ret .= ' '.$where;
		if ( strlen($order)>0 ) $ret .= ' '.$order;
		if ( strlen($limit)>0 ) $ret .= ' '.$limit;
		$this->action = null;
		return $ret;
		
	
	}
	/**
	 * Returns the select sql query as a string.
	 * eg: SELECT foo,bar from table1 where bar='1'
	 * @param columns An optional list of columns to select.
	 */
	function select($columns='', $query=array(), $nolimit=false, $tablename=null, $preview=false){
		$this->action="select";
		if ( !is_array($query) ){
			$query = array();
		}
		$query = array_merge( $this->_query, $query);
		
		$ret = $this->_select($columns, array(), $preview);
		if ( PEAR::isError($ret) ){
			$ret->addUserInfo("Failed to select columns in select() ");
			
			return $ret;
		}
		$from = trim($this->_from($tablename));
		$where = trim($this->_where($query));
		$where = $this->_secure($where);
		//$having = $this->_having($query);
		
		$order = trim($this->_orderby($query));
		$limit = trim($this->_limit($query, $nolimit));
		
		if ( strlen($from)>0 ) $ret .= ' '.$from;
		if ( strlen($where)>0 ) $ret .= ' '.$where;
		//if ( strlen($having)>0 ) $ret .= ' '.$having;
		if ( strlen($order)>0 ) $ret .= ' '.$order;
		if ( strlen($limit)>0 ) $ret .= ' '.$limit;
		$this->action = null;
		//echo $ret;
		return $ret;
	}
	
	/**
	 * Returns the sql query to find the number of rows of the select query.
	 * eg: SELECT COUNT(*) from foo where bar='1'
	 */
	
	
	function select_num_rows($query=array(), $tablename=null){
		
		$this->action='select_num_rows';
		$query = array_merge( $this->_query, $query);
		$ret = 'SELECT COUNT(*) as num';
		$from = $this->_from($this->tablename($tablename));
		$where = $this->_where($query);
		$where = $this->_secure($where);
		
		if ( strlen($from)>0 ) $ret .= ' '.$from;
		if ( strlen($where)>0 ) $ret .= ' '.$where;
		$this->action = null;
		return trim($ret);
	
	
	}
	
	
	/**
	 * <p>Generates a string SQL statement to update the given record.</p>
	 * @param $record Reference to the Dataface_Record object to be updated.
	 * @param $key_vals Optional override of key values to be used in the where clause.  Alternatively, this method will use the key values from 
	 * 			the record's snapshot in the where clause if this parameter is left out (or is null).
	 */
	function update(&$record, $key_vals=null, $tablename=null){
		$app =& Dataface_Application::getInstance();
		$this->action='update';
		// Step 1:  Make sure that the input is valid
		if ( !is_a($record, "Dataface_Record") ){
			throw new Exception("Attempt to use QueryBuilder::update() where something other than a Dataface_Record object is defined.", E_USER_ERROR);
		}
		
		// Step 2: Start building the sql query.  We use an array to build up the query, but 
		// string concatenation would work just as well.  We switched to arrays in an attempt to 
		// fix a bug related to updating BLOB fields, but it turned out that the problem was different.
		$tableObj =& Dataface_Table::loadTable($this->tablename($tablename));
		$dbObj =& Dataface_DB::getInstance();
		$sql = array();
		$sql[] = "UPDATE `".$this->tablename($tablename)."` SET ";
		$fieldnames = array_keys($this->_mutableFields);
			// Get all of the field names in this table.
		$keys = array_keys($this->_table->keys());
			// Get the names of the key fields in this table
		$changed = false;
			// A flag to indicate whether the record has been changed.
		foreach ($fieldnames as $fieldname){
			if ( isset($fieldArr) ) unset($fieldArr);
			$fieldArr =& $tableObj->getField($fieldname);
			if ( @$fieldArr['ignore'] ) continue;
			if ( !$record->valueChanged($fieldname)  and !(isset($fieldArr['timestamp']) and strtolower($fieldArr['timestamp']) == 'update') ) {
				// If this field has not been changed then we don't need to update it.
				// Note that in order for valueChanged() to work properly, the Dataface_Record::setSnapshot()
				// method must be called at somepoint to indicate that that snapshot represents the last unchanged
				// state of the record.
				continue;
			}
			if ( $tableObj->isVersioned() and $tableObj->getVersionField() === $fieldname ){
				continue;
			}
			//echo "$fieldname changed\n";
			
			// If we made it this far, then the current field has indeed changed
			$changed = true;
			
			$sval = $this->_serializer->serialize($fieldname, $record->getValue($fieldname));
				// Serialize the field's value to prepare it for the database
			if ( !isset($sval) and @$fieldArr['timestamp'] != 'update' ){
				$sql[] = "`$fieldname` = NULL, ";
			} else if ( $tableObj->isBlob($fieldname) and @$app->_conf['multilingual_content']){
				// This is a blob column... we don't place the data directly in the String because it would take
				// too long to parse when Dataface_DB needs to parse it.
				// Instead we register the BLOB and store its id number.
				$blobID = $dbObj->registerBlob($sval);
				$sql[] = "`$fieldname` = '-=-=B".$blobID."=-=-', ";
			} else if ( $tableObj->isDate($fieldname)  and 
					isset($fieldArr['timestamp']) and 
					strtolower($fieldArr['timestamp']) == 'update'){
				$sql[] = "`$fieldname` = NOW(), ";
				
			} else {
				$sql[] = "`$fieldname` = ".$this->prepareValue($fieldname, $sval).", ";
			}		
		}
		if ( !$changed ) return '';
			// If no fields have changed, then we will just return an empty string for the query.
		if ( $tableObj->isVersioned()  ){
			$versionField = $tableObj->getVersionField();
			$sql[] = "`$versionField` = ifnull(`$versionField`,0)+1, ";
		}
		
		$sql[count($sql)-1] = substr($sql[count($sql)-1], 0, strlen($sql[count($sql)-1])-2);
			// chop off the trailing comma from the update clause
		$vals = $record->snapshotExists() ? $record->snapshotKeys() : $record->getValues( array_keys($this->_table->keys()));
			// If a snapshot has been set we will use its key values in the where clause
		
		if ( $key_vals === null ){
			$query = unserialize(serialize($vals));
			foreach ( array_keys($query) as $qkey){
				$query[$qkey] = "=".$this->_serializer->serialize($qkey, $query[$qkey]);
			}
		} else {
			$query = $key_vals;
			foreach (array_keys($query) as $qkey){
				$query[$qkey] = "=".$query[$qkey];
			}
		}
		
		$sql[] = " ".$this->_where($query);
		$sql[] = " LIMIT 1";
		
		
		
		$sql = implode($sql);
		//echo $sql;
		$this->action = null;
		return $sql;
	
	}
	
	
	
	function insert(&$record, $tablename=null){
		$app =& Dataface_Application::getInstance();
		$this->action = 'insert';
		if ( !is_a($record, "Dataface_Record") ){
			throw new Exception("First argument to QueryBuilder::insert() must be of type Dataface_Record, but received ".get_class($record), E_USER_ERROR);
		}
		// the keys are not complete... so this item does not exist.. create new record.
		$tableObj =& Dataface_Table::loadTable($this->tablename($tablename));
		$dbObj =& Dataface_DB::getInstance();
		$fields = array_keys($this->_mutableFields);
		$keys =& $this->_table->keys();
		$insertedKeys = array();
		$insertedValues = array();
		
		foreach ($this->_mutableFields as $key=>$field){
			if ( @$field['ignore'] ) continue;
			if ( $tableObj->isDate($key) ){
				// We must take special care for dates.
				if (isset($fieldArr)) unset($fieldArr);
				$fieldArr =& $tableObj->getField($key);
				if ( isset($fieldArr['timestamp']) and in_array(strtolower($fieldArr['timestamp']), array('insert','update')) ){
					$insertedKeys[] = '`'.$key.'`';
					$insertedValues[] = 'NOW()';
					
					continue;
				}
			}
			if ( !$record->hasValue($key) ) continue;
			$val = $record->getValue($key);
			if ( strtolower($this->_mutableFields[$key]['Extra']) == 'auto_increment' && !$val ){
				// This is a MySQL 5 fix.  In MySQL 5 it doesn't like it when you put blank values into
				// auto increment fields.
				continue;
			}
			if ( !isset($val)) continue;
			$sval = $this->_serializer->serialize($key, $record->getValue($key) );
			//if ( !$field['value'] && in_array($key, array_keys($keys)) ) continue;
			if ( $tableObj->isBlob($key) and @$app->_conf['multilingual_content'] ){
				$blobID = $dbObj->registerBlob($sval);
				$sval2 = "-=-=B".$blobID."=-=-";
			} else {
				$sval2 = $sval;
			}
			
			if ( strlen(strval($sval2)) == 0 and strtolower($this->_mutableFields[$key]['Null']) == 'yes' ){
				$insertedKeys[] = '`'.$key.'`';
				$insertedValues[] = 'NULL';
				//$sql .= 'NULL,';
			} else {
				$insertedKeys[] = '`'.$key.'`';
				
				$insertedValues[] = $this->prepareValue($key,$sval2);
				//$sql .= "'".addslashes($sval2)."',";
			}
		}
		$sql = "INSERT INTO `".$this->tablename($tablename)."` (".
			implode(',', $insertedKeys).') VALUES ('.
			implode(',', $insertedValues).')';
		$this->action = null;
		return $sql;
	}
	
	
	function prepareValue($fieldname, $value,$serialize=false){
		$quotes = true;
		if ( $serialize ) $value = $this->_serializer->serialize($fieldname, $value);
		if ( in_array( strtolower($this->_table->getType($fieldname)), array('timestamp','datetime')) ){
			$value = "ifnull(convert_tz('".addslashes($value)."','".addslashes(df_tz_or_offset())."','SYSTEM'),'".addslashes($value)."')";
			$quotes = false;
		}
		if ( $quotes ) $value = "'".addslashes($value)."'";
		$value = $this->_serializer->encrypt($fieldname,$value);
		return $value;
		
	}
	
	
	
	function delete($query=array(), $nolimit=false, $tablename=null){
		$this->action = 'delete';
		if ( !isset($tablename) ) $tablename = $this->_table->tablename;
		$table =& Dataface_Table::loadTable($tablename);
		
		$query = array_merge($this->_query, $query);
		$tsql=$table->sql();
		$parent =& $table->getParent();
		if ( isset($tsql) or isset($parent)){
			$talias = $tablename.'__dforiginal__';
			$joinclause = array();
			foreach ( array_keys($table->keys() ) as $tkey){
				$joinclause[] = "`$talias`.`$tkey`=`$tablename`.`$tkey`";
			}
			$joinclause = implode(' AND ', $joinclause);
			$from = "FROM `{$talias}` USING `{$tablename}` as `{$talias}` left join ".substr($this->_from($tablename), 5)." on ($joinclause)";
			
		} else {
			$from = $this->_from($tablename);
					
		}
		$where = $this->_where($query);
		$limit = $this->_limit($query, $nolimit);
		$ret = "DELETE ".$from;
		
		if ( strlen($where)>0 )  $ret .= ' '.$where;
		if ( strlen($limit)>0 )  $ret .= ' '.$limit;
		$this->action = null;
		return trim($ret);
		
		
	}
	
	function wc($tablename, $colname){
		if ( in_array($this->action, array('select','delete', 'select_num_rows')) ){
			return "`{$tablename}`.`{$colname}`";
		} else {
			return "`{$colname}`";
		}
	}
	
	function _fieldWhereClause(&$field, $value, &$use_where, $tableAlias=null){
		$key = $field['Field'];
		$where = '';
		$table =& Dataface_Table::loadTable($field['tablename']);
		$changeTable = false;
		if ( $this->_table->tablename != $table->tablename ){
			$changeTable = true;
			$oldTable =& $this->_table;
			unset($this->_table);
			$this->_table =& $table;
			$oldSerializer =& $this->_serializer;
			unset($this->_serializer);
			$this->_serializer = new Dataface_Serializer($table->tablename);
		}
		if ( !isset($tableAlias) ) $tableAlias = $table->tablename;
		if ( is_array($value) ){
			throw new Exception("Attempt to use array in query clause");
		}
		$words = explode(' OR ', $value);
		if ( count($words) > 1){
			$where .= '(';
			$conj = 'OR';
		} else {
			$conj = 'AND';
		}
		
		// A value with a prefix of '<>' indicates we are searching for values NOT equal to...
		if ( isset($field['repeat']) and $field['repeat']){
			$repeat = true;
			
		} else {
			$repeat = false;
		}
		foreach ($words as $value){
			if ( $value === '' ) continue;
			// A value with a prefix of '=' indicates that this is an exact match
			if ( $value{0}=='=' ){
				$exact = true;
				$value = substr($value,1);
			} else {
				$exact = false;
			}
			$factors = explode(' AND ', $value);
			if ( count($factors) > 1 ){
				$where .= '(';
			}
			foreach ($factors as $value){
				if ( !$exact and (strpos($value, '!=')===0 or strpos($value, '<>') === 0)){
                                        $value = substr($value, 2);
                                        $oldval = trim($value);
					$value = $this->prepareValue( $key, $table->parse($key, $value), true );
					if ( $repeat ){
						$where .= $this->wc($tableAlias, $key)." NOT RLIKE CONCAT('[[:<:]]',$value,'[[:>:]]') AND ";
					} else {
                                                $oper = '<>';
                                                if (  strlen($oldval) === 0 ){
                                                        $where .= $this->wc($tableAlias,$key)." $oper $value AND ";
                                                } else {
                                                        $where .= '('.$this->wc($tableAlias,$key)." $oper $value OR ".$this->wc($tableAlias,$key)." IS NULL) AND ";
                                                }
					}
				
				// A value with a prefix of '<' indicates that we are searching for values less than
				// a field.
				} else if ( !$exact and strpos($value,'<')===0){
					if ( strpos($value,'=') === 1 ){
						$value = substr($value,2);
						$op = '<=';
					} else {
						$value = substr($value, 1);
						$op = '<';
					}
					$value = $this->prepareValue( $key, $table->parse($key, $value), true );
					$where .= $this->wc($tableAlias, $key)." $op $value AND ";
					
				// A value with a prefix of '>' indicates a greater than search
				} else if ( !$exact and strpos($value, '>')===0 ) {
					if ( strpos($value,'=') === 1 ){
						$value = substr($value,2);
						$op = '>=';
					} else {
						$value = substr($value, 1);
						$op = '>';
					}
					$value = $this->prepareValue( $key, $table->parse($key, $value), true );
					$where .= $this->wc($tableAlias, $key)." $op $value AND ";
					
					
				// If the query term has '..' any where it is interpreted as a range search
				} else if ( !$exact and strpos($value, '..')> 0 ){
					list($low,$high) = explode('..',$value);
					$low = trim($low); $high = trim($high);
					$low = $this->prepareValue( $key, $table->parse($key, $low), true);
					$high = $this->prepareValue( $key, $table->parse($key, $high), true);
					$where .= $this->wc($tableAlias, $key)." >= $low AND ".$this->wc($tableAlias, $key)." <= $high AND ";
				} else if ( !$exact and strpos($value, '~') === 0 ){
					$value = substr($value,1);
					$oldval = $value;
					$oper = 'LIKE';
					$value = $this->prepareValue( $key, $table->parse($key, $value), true);
					if (  strlen($oldval) > 0 ){
						$where .= $this->wc($tableAlias,$key)." $oper $value AND ";
					} else {
						$where .= '('.$this->wc($tableAlias,$key)." $oper '' OR ".$this->wc($tableAlias,$key)." IS NULL) AND ";
					}
				
				
				} else if ( $repeat ){
					$value = $this->prepareValue( $key, $table->parse($key, $value), true); 
					$where .= $this->wc($tableAlias, $key)." RLIKE CONCAT('[[:<:]]',$value,'[[:>:]]') AND ";
				}
				
				else if ( $this->_exactMatches || preg_match( '/int/i', $field['Type']) || $exact ){
					$oldval = $value;
					$oper = '=';
					$value = $this->prepareValue( $key, $table->parse($key, $value), true);
					if (  strlen($oldval) > 0 ){
						$where .= $this->wc($tableAlias,$key)." $oper $value AND ";
					} else {
						$where .= '('.$this->wc($tableAlias,$key)." $oper '' OR ".$this->wc($tableAlias,$key)." IS NULL) AND ";
					}
				} else {
					$value = $this->prepareValue( $key, $table->parse($key, $value), true); 
					$where .= $this->wc($tableAlias, $key)." LIKE CONCAT('%',$value,'%') AND ";
				}
				$use_where = true;
			}
			$where = substr($where, 0, strlen($where)-5);
			if (count($factors) > 1){
				
				$where .= ')';
			}
			$where .= ' OR ';

			
		}
		$where = substr($where, 0, strlen($where)-4);
		if ( count($words) > 1){
			
			$where .= ')';
		}
		
		if ($changeTable){
			unset($this->_table);
			$this->_table =& $oldTable;
			unset($this->_serializer);
			$this->_serializer =& $oldSerializer;
		}
		return $where;
	
	}
	
	/**
         * @brief A wrapper around the _where() method that optionally takes
         * a Dataface_Record object as a parameter.  This produces just the
         * where clause of an SQL query.
         * 
         * @param mixed $query Either an array of parameters or a Dataface_Record object
         * which will be used for its keys.
         * @param Boolean $merge Whether to merge the criteria with the object's 
         * current query.
         * @return string The where clause e.g. "WHERE name='Steve' and age=29"
         * @since 2.0.3
         */
        public function where($query = null, $merge=true){
            if ( $query instanceof Dataface_Record ){
                $record = $query;
                $keys = array_keys($record->table()->keys());
                $query = array();
                foreach ($keys as $key){
                    $query[$key] =  "=".$this->_serializer->serialize($key, $record->val($key));
                }
            } else if ( !isset($query) ){
                $query = array();
            }
            return $this->_where($query, $merge);
        }
        
	/**
	 * Returns the where clause for the sql query.
	 * eg: WHERE foo = 'bar' and moo = 'cow'
	 * @param fields Table fields that are used in the where clause
	 */
	function _where($query=array(), $merge=true){
                
		if ( $merge ){
			$query = array_merge( $this->_query, $query);
			
		}
		foreach ($query as $key=>$value) {
			if ( $value === null or $value === '' ){
				unset($query[$key]);
			}
		}
		
		if ( isset($query['__id__']) ){
			$keys = array_keys($this->_table->keys());
			if ( $keys ){
				$query[$keys[0]] = $query['__id__'];
				unset($query['__id__']);
			}
		}
		
		
		
		$where  = "WHERE ";
		$missing_key = false;
		$limit = '';
		$use_where = false;
			
		$fields = array();
		//print_r($query);
		foreach ($query as $key=>$value){
			if ( strpos($key,'-') !== 0 ) $fields[$key] = $value;
		}
		foreach ($fields as $key=>$value){
			if ( isset($this->_fields[$key]) ){
				$field =& $this->_fields[$key];
				if ( !@$field['not_findable'] ){
					$where .= $this->_fieldWhereClause($field, $value, $use_where, $this->_tablename).' AND ';
				}
				unset($field);
			}
				
		}
		$charFields = $this->_table->getCharFields(true, true);
		if ( isset( $query['-search'] ) and strlen($query['-search']) and count($charFields)>0 ){
			$status = $this->_table->getStatus();
			//if ( $status['Engine'] == 'MyISAM' ){
			//	// MyISAM has a match clause. that works quite well.
			//	$where .= $this->_match($query['-search'])." AND ";
			//} else {
			//	// If the table type is not MyISAM, then we need to manually do the multi-field search.
				$words = explode(' ', $query['-search']);
				foreach ( $words as $word ){
					$where .= '(`'.implode('` LIKE \'%'.addslashes($word).'%\' OR `', $charFields).'` LIKE \'%'.addslashes($word).'%\') AND ';
			//	}
			}
						
			$use_where = true;
		}
		
		if ( $this->metadata ){
			$wfkeys = preg_grep('/^_metadata::/', array_keys($query));
			$clause = array();
			foreach ($wfkeys as $wfkey){
				$wf_col = substr($wfkey,11);
				if ( !$this->_table->fieldExists($wf_col) ) continue;
				$wf_col = $this->_tablename."__metadata.__{$wf_col}";
				$clause[] = "`{$wf_col}`='".addslashes($query[$wfkey])."'";
			}
			if ( count($clause)>0 ){
				$use_where = true;
				$where .= implode(' AND ', $clause).' AND ';
			}
		}
		
		// Now we will search related fields
		$rkeys = preg_grep('/^[^\-].*\/.*$/', array_keys($query));
		
		$rquery = array();
		foreach ($rkeys as $rkey ){
			list($relationship, $rfield) = explode('/', $rkey);
			$rquery[$relationship][] = $rfield;
			
		}
		
		foreach ( $rquery as $rname=>$rfields){
			$r =& $this->_table->getRelationship($rname);
			if ( PEAR::isError($r) ){
				unset($r);
				continue;
			}
			
			
			$pairs=array();
			foreach ( $rfields as $rfield ){
				$rfieldDef =& $r->getField($rfield);
				$q = $query[$rname.'/'.$rfield];
				$ralias = $r->getTableAlias($rfieldDef['tablename']);
				if ( !$ralias ) $ralias = null;
				$pairs[] = $this->_fieldWhereClause($rfieldDef, $q, $use_where, $ralias );
				unset($rfieldDef);
				
				//$pairs[] = '`'.str_replace('`','',$rfield).'` LIKE \'%'.addslashes($query[$rname.'/'.$rfield]).'%\'';
			}
			if ( $pairs ){
				$subwhere = ' AND '.implode(' AND ',$pairs);
			}
			
			$sql = $r->getSQL();
			
			$fkeys = $r->getForeignKeyValues();
			foreach ( $fkeys as $tname=>$tfields ){
				foreach ( $tfields as $tval ){
					if ( !is_scalar($tval) ) continue;
					if ( strlen($tval) > 0 ) $tval = substr($tval,1);
					$sql = preg_replace('/[\'"]?\$('.preg_quote($tval).')[\'"]?/', '`'.str_replace('`','',$this->_table->tablename).'`.`\1`', $sql);
				}
			}
			$where .= 'EXISTS ('.$sql.$subwhere.') AND ';
			$use_where = true;
			unset($r);
			unset($fkeys);
		}
		
		
		
		if ( $use_where ){
			
			$where = substr($where,0, strlen($where)-5);
		} else {
			$where = '';
		}
		
		return $where;
	}
	
	
	
	/**
	 * Returns the from clause for the SQL query.
	 * eg: FROM users
	 */
	function _from($tablename=null){
		$app =& Dataface_Application::getInstance();
		if ( !isset($tablename) ) $tablename = $this->_table->tablename;
		
		$table =& Dataface_Table::loadTable($tablename);
		$proxyView = $table->getProxyView();
		$tsql = $table->sql();
		$fromq = '';
		if ( $proxyView ){
			$fromq = "`".$proxyView."`";
		} else if ( isset($tsql) ){
			$fromq = "(".$tsql.")";
		} else {
			$fromq = "`".$this->tablename($tablename)."`";
		}
		
		$parent =& $table->getParent();
		if ( isset($parent) ){
			$qb2 = new Dataface_QueryBuilder($parent->tablename);
			$pfrom = $qb2->_from();
			$as_pos = ( ( strpos(strtolower($pfrom), ' as ') !== false ) ? (strlen($pfrom) - strpos(strrev(strtolower($pfrom)), ' sa ' )-3) : false);
			if ( $as_pos !== false ){
				$pfrom = substr($pfrom, strlen('FROM '), $as_pos-strlen('FROM '));
			} else {
				$pfrom = substr($pfrom, strlen('FROM '));
			}
			$pkeys = array_keys($parent->keys());
			$ckeys = array_keys($table->keys());
			$joinq = array();
			for ($i=0; $i<count($pkeys); $i++){
				$joinq[] = '`t___child`.`'.$ckeys[$i].'`=`t___parent`.`'.$pkeys[$i].'`';
			}
			$joinq = implode(' and ', $joinq);
			
			
			$out = "FROM (select * from ".$fromq." as `t___child` left join ".$pfrom." as `t___parent` on ($joinq)) as `".$this->tablename($tablename)."`" ;
		
			
		} else if ( isset($tsql) or isset($proxyView) ){
			$out = "FROM ".$fromq." as `".$this->tablename($tablename)."`";
		} else {
			$out = "FROM ".$fromq;
		}
		
		
		
		
		if ( $this->metadata and $this->action == 'select') {
			$out .= " LEFT JOIN `{$tablename}__metadata` ON ";
			$keys = array_keys($table->keys());
			if ( count($keys) == 0 ) throw new Exception("The table '".$tablename."' has no primary key.", E_USER_ERROR);
			
			$clauses = array();
			foreach ( $keys as $key ){
				$clauses[] = "`{$tablename}`.`{$key}`=`{$tablename}__metadata`.`{$key}`";
			}
			$out .= "(".implode(' and ', $clauses).")";
		}
		return $out;
	}
	
	
	/**
	 * Returns the select portion of the sql query.
	 * eg: SELECT foo, bar
	 * @param columns An option list of columns to select.
	 */
	function _select($columns='', $query=array(), $preview=false, $previewLen=null){
		if ( !isset($previewLen) and defined('XATAFACE_DEFAULT_PREVIEW_LENGTH') and is_int(XATAFACE_DEFAULT_PREVIEW_LENGTH) ){
			$previewLen = XATAFACE_DEFAULT_PREVIEW_LENGTH;
		}
		if ( !is_int($previewLen) ) $previewLen = 255;
		$app =& Dataface_Application::getInstance();
		$query = array_merge( $this->_query, $query);
		foreach ($query as $key=>$value) {
			if ( $value === null ){
				unset($query[$key]);
			}
		}
		$select = "SELECT ";
		$colcount = 0;
		foreach ($this->_fields as $key=>$field){
			if ( $this->selectMetaData ){
				$select .= "length(`{$this->_tablename}`.`".$key."`) as `__".$key."_length`,";
				
			}
			if ( is_array($columns) and !in_array($key, $columns) ) continue;
				// if the columns array is set then we only return the columns listed in that array.
				
			
		
			if ( $this->_omitBlobs and $this->_table->isBlob($field['name']) ) continue;
				// if the omitBlobs flag is set then we don't select blob columns
			if ( $this->_omitPasswords and $this->_table->isPassword($field['name']) ) continue;
				// if the omitPasswords flag is set then we don't select password columns
			if ( $preview and $this->_table->isText($field['name']) and !@$field['struct'] and !$this->_table->isXML($field['name'])) 
				$select .= "SUBSTRING(`{$this->_tablename}`.`$key`, 1, ".$previewLen.") as `$key`,";
			else if ( in_array(strtolower($this->_table->getType($key)),array('datetime','timestamp')) )
				$select .= "ifnull(convert_tz(`".$this->_tablename."`.`".$key."`, 'SYSTEM', '".df_tz_or_offset()."'), `".$this->_tablename."`.`".$key."`) as `$key`,";
			else 
				$select .= "`{$this->_tablename}`.`$key`,";
			$colcount++;

		}
		if ( $this->metadata) {
			$clauses = array();
			foreach ( $this->_table->getMetadataColumns() as $mdc ){
				$clauses[] = "`{$this->_tablename}__metadata`.`{$mdc}`";
			}
			$select .= implode(',',$clauses).',';
			
		}
		
		if ( $colcount == 0 ){
			return PEAR::raiseError(QUERYBUILDER_ERROR_EMPTY_SELECT, null,null,null, "No columns were selected in select statement.  Make sure that _omitBlobs property is disabled in QueryBuilder object if you are only wanting to return Blob columns.");
		}
		$select = substr($select, 0, strlen($select) -1);
		return $select;
	}
	
	/**
	 * Returns the limit portion of the sql query.
	 * eg: LIMIT 1,2
	 */
	function _limit($query=array(), $nolimit=false){
		if ( $nolimit ) return '';
		$query = array_merge( $this->_query, $query);
		foreach ($query as $key=>$value) {
			if ( $value === null ){
				unset($query[$key]);
			}
		}
		
		$limit = '';
		if ( isset( $query['-limit']) && isset($query['-skip'] ) ){
			if ( preg_match('/^[0-9]+$/',$query['-limit']) &&
				 preg_match('/^[0-9]+$/',$query['-skip']) ){
				$limit = "LIMIT ".$query['-skip'].",".$query['-limit'];
			}
		} else if ( isset( $query['-limit'] ) ){
			if ( preg_match('/^[0-9]+$/', $query['-limit']) ){
				$limit = "LIMIT ".$query['-limit'];
			}
		} else if ( isset( $query['-skip']) ){
			if ( preg_match('/^[0-9]+$/', $query['-skip']) ){
				$limit = "LIMIT ".$query['-skip'].", 100";
			}
		}
		return $limit;
	}
	
	
	/**
	 * Returns the ORDER BY clause of the SQL query.
	 */
	function _orderby($query = array()){
		$query = array_merge( $this->_query, $query);
		foreach ($query as $key=>$value) {
			if ( $value === null ){
				unset($query[$key]);
			}
		}
		
		if ( isset($query['-sort']) ){
			
			return 'ORDER BY '.preg_replace_callback('/\b(\w+?)\b/',array(&$this, '_mysql_quote_idents'), $query['-sort']);
		}
		return '';
	
	}
	
	function _mysql_quote_idents($matches){
		if (!in_array(strtolower($matches[1]), array('asc','desc') ) ){
			return '`'.((strpos($matches[1],'.') === false) ?"{$this->_tablename}`.`":'').$matches[1].'`';
		} else {
			return $matches[1];
		}
	}
	
	/**
	 * Generates a MATCH() clause to match against a full-text index.
	 *
	 * @param $queryStr A boolean search string to search for.  See MySQL full-text
	 *                  boolean searches for the syntax of this string.
	 * @type string
	 *
	 * @param $tables Optional array of table names from which to match
	 * @type array([Table name] => [Table alias])
	 *
	 * @param $useIndexOnly Optional parameter specifying if we should only match
	 * 						against columns with a full text index.  If false then
	 *						all char and text colums will be used in the search.
	 * @type boolean
	 * 
	 * @returns string Match clause for an SQL query.
	 */
	function _match($queryStr){
		$version = mysql_get_server_info();
		$matches = array();
		preg_match('/(\d+)\.(\d)+\.(\d)+/', $version, $matches);
		$majorVersion = intval($matches[1]);
		
		// We want to escape illegal characters, but in a boolean search
		// double  quotes are allowed so we much unescape them.
		$queryStr = addslashes($queryStr);
		$queryStr = str_replace('\"', '"', $queryStr);		
		
		$out = 'MATCH (';

		// We have at least version 4 so we can do boolean searches
		$indexedFields =& $this->_table->getFullTextIndexedFields();
		if ( count($indexedFields)>0){
			$fields =& $indexedFields;
		} else {
			// There are no indexed fields so we will just do a search on all character fields.
			$fields =& $this->_table->getCharFields();
		}
		
		
		$empty = true;
			// flag to indicate if the query will be empty
		foreach ($fields as $field){
			$out .= "`{$this->_tablename}`.`$field`,";
			$empty = false;
				// the query is NOT empty
		}
		
		if ( $empty ){
			throw new Exception("Query attempted when no queryable columns are available in table '".$this->_table->tablename."'.  Only tables with a full-text search defined on at least one column are eligiblle to be searched in this way.", E_USER_ERROR);
		}
		
		$out = substr($out, 0, strlen($out)-1);
		
		$out .= ") AGAINST ('$queryStr'";
		if ( $majorVersion >= 4 ) {
			$out .= " IN BOOLEAN MODE";
		}
		$out .= ")";
			
		return $out;
	
	}
	
	
	function omitBlobs(){
	
		$this->_omitBlobs = true;
	}
	
	function includeBlobs(){
		$this->_omitBlobs = false;
	}
	
	
	function addSecurityConstraint($key, $value){
	
		$this->_security[$key] = $value;
	
	}
	
	function addSecurityConstraints($constraints){
	
		$this->_security = array_merge($this->_security, $constraints);
	}
	
	
	function removeSecurityConstraint($key){
		unset( $this->_security[$key] );
	}
	
	function setSecurityConstraints( $constraints ){
		$this->_security = $constraints;
	}
	
	function _secure($where){
		
		$swhere = $this->_where($this->_table->getSecurityFilter($this->_security), false);
		// get rid of the leading "where"
		$swhere = trim(substr($swhere, 5, strlen($swhere)-5));
		
		$where = trim($where);
		if ( strlen($where)>0 ){
			if (strlen($swhere)>0) {
				$where .= " AND ".$swhere;
			}
		} else if ( strlen($swhere)>0){
			$where = "WHERE $swhere";
		}
		return $where;
		
		
	
	}
	
	
		/**
	 * Returns an array of SQL statements that should be executed sequentially to add a related record.
	 */
	function addRelatedRecord(&$relatedRecord, $sql=null){
		if ( !is_a($relatedRecord, 'Dataface_RelatedRecord') ){
			throw new Exception("In QueryBuilder::addRelatedRecord() expecting first argument to be type 'Dataface_RelatedRecord' but received '".get_class($relatedRecord)."'\n<br>", E_USER_ERROR);
		}
		$relationship =& $relatedRecord->_relationship;
		$table_cols = $relatedRecord->getForeignKeyValues( $sql);
		if ( count($this->errors) > 0 ){
			$error = array_pop($this->errors);
			$error->addUserInfo("Error getting foreign key values for relationship '$relationship_name'");
			throw new Exception($error->toString());
		}
		
		
		$sql = array();
		
		// now generate the sql
		// We will go through each table and insert the record for that 
		// table separately.
		foreach ( $table_cols as $table=>$cols ){
			if ( isset($recordObj) ) unset($recordObj);
			$recordObj = new Dataface_Record($table, $cols);
			$recordVals =& $recordObj->vals();
			if ( isset( $recordVals[ $recordObj->_table->getAutoIncrementField() ] ) ){
				// We don't want the auto-increment field to be inserted - though it may
				// have a placeholder value.
				$recordObj->setValue($recordObj->_table->getAutoIncrementField(), null);
			}
			$qb = new Dataface_QueryBuilder($table);
			$sql[$table] = $qb->insert($recordObj);
			
		}
		
		return $sql;
			
	}
	
	
	function addExistingRelatedRecord(&$relatedRecord){
		$record =& $relatedRecord->_record;
		$relationshipName =& $relatedRecord->_relationshipName;
		$values = $relatedRecord->getAbsoluteValues(true);
		if ( !is_a($record, 'Dataface_Record') ){
			throw new Exception("In Dataface_QueryBuilder::addExistingRelatedRecord() expected first argument to be of type 'Dataface_Record' but received '".get_class($record)."'.\n<br>", E_USER_ERROR);
		}
		if ( !is_array($values) ){
			throw new Exception("In Dataface_QueryBuilder::addExistingRelatedRecord() expected third argument to be an array but received a scalar.", E_USER_ERROR);
		}
		$relationship =& $record->_table->getRelationship($relationshipName);
		$foreignKeys = $relationship->getForeignKeyValues();
		$foreignKeys_withValues = $relatedRecord->getForeignKeyValues();
		
		if ( count($this->errors) > 0 ){
			$error = array_pop($this->errors);
			$error->addUserInfo("Error getting foreign key values for relationship '$relationship_name'");
			throw new Exception($error->toString());
		}
		
		$sql = array();
		foreach ($foreignKeys as $table=>$cols){
			$skip = true;
			foreach ($cols as $field_name=>$field_value){
				if ( $field_value != "__".$table."__auto_increment__" ) {
					$skip = false;
					break;
				}
			}
			if ( $skip ) continue;
			$cols = $foreignKeys_withValues[$table];
			if ( isset($recordObj) ) unset($recordObj);
			$recordObj = new Dataface_Record($table, $cols);
			$recordVals =& $recordObj->vals();
			if ( isset( $recordVals[ $recordObj->_table->getAutoIncrementField() ] ) ){
				// We don't want the auto-increment field to be inserted - though it may
				// have a placeholder value.
				$recordObj->setValue($recordObj->_table->getAutoIncrementField(), null);
			}
			$qb = new Dataface_QueryBuilder($table);
			$sql[$table] = $qb->insert($recordObj);
			/*
			$skip = true;
				// indicator to say whether or not to skip this table
				// we skip the table if it contains an unresolved autoincrement value
				
			foreach ($cols as $field_name=>$field_value){
				if ( $field_value != "__".$table."__auto_increment__" ) {
					$skip = false;
					break;
				}
			}
			
			if ( $skip == true ) continue;
				
			
			$cols = $foreignKeys_withValues[$table];
			
			
			$query = "INSERT INTO `$table`";
			$colnames = "";
			$colvals = "";
			
			foreach ( $cols as $colname=>$colval){
				$colnames .= $colname.',';
				$colvals .= "'".addslashes($colval)."',";
			}
			
			$colnames = substr($colnames, 0, strlen($colnames)-1);
			$colvals = substr($colvals, 0, strlen($colvals)-1);
			
			$query .= " ($colnames) VALUES ($colvals)";
			
			$sql[$table] = $query;
			*/
		
		}
		
		return $sql;
		
		
	
	}
	
	function tablename($tablename=null){
		if ( $tablename === null ) return $this->_tablename;
		return $tablename;
	
	}
	
	
	


}
