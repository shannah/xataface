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
 * @ingroup databaseAbstractionAPI
 */

/**
 * File: Dataface/Relationship.php
 * Author: Steve Hannah <shannah@sfu.ca>
 * Created: October 2005
 *
 * Description:
 * -------------
 * Encapsulates a relationship between two tables.
 *
 */
 
 
class Dataface_Relationship {

	/*
	 * The name of the relationship.
	 */
	var $_name;
	
	/*
	 * Reference to the source table of the relationship.
	 */
	var $_sourceTable;
	
	/*
	 * Arrayh of references to the destination tables of the relationship.
	 */
	var $_destinationTables;
	
	/*
	 * An associative array mapping field names to tables.
	 */
	var $_fieldTableLookup;
	
	/*
	 * A Descriptor array to describe the relationship.
	 */
	var $_schema;
	
	/*
	 * The key fields of the relationship.
	 */
	var $_keys;
	
	
	/**
	 * Flag to indicate whether or not the sql has been generated. (Used in particular
	 * by the getSQL() method.
	 *
	 * @type boolean
	 */
	var $_sql_generated = array();
	
	var $_permissions = array();
	
	var $app;
	
	/**
	 * @var array Stores cached method results.
	 * @since 0.6.1
	 */
	var $_cache=array();
	
	var $addNew;
	var $addExisting;
	
	
	
	/**
	 * 
	 * Constructor for the relationship.
	 *
	 * @param $tablename The name of the source table.
	 * @wparam $relationshipName The name of the relationship
	 * @param An array of initializing values.  Usually produced by parsing the relationships.ini
	 * 			file.
	 *
	 */
	function Dataface_Relationship($tablename, $relationshipName, &$values){
		$this->app =& Dataface_Application::getInstance();
		$this->_name = $relationshipName;
		$this->_sourceTable =& Dataface_Table::loadTable($tablename);
		$this->_schema = array();
		$res = $this->_init($values);
		if ( PEAR::isError($res) ){
			throw new Exception($res->getMessage());
		}
		
		if ( !isset($this->_schema['permissions']) ){
			$app =& Dataface_Application::getInstance();
			$this->_schema['permissions'] = Dataface_PermissionsTool::getRolePermissions($app->_conf['default_relationship_role']);
		}
		$this->_permissions =& $this->_schema['permissions'];
		
	}
	
	/**
	 * Returns an array of names of fields in this relationship.
	 * @param boolean $includeAll Whether to include all table fields
	 *		involved.  By default, only the specified columns are returned.
	 *		If this flag is set to true, then all fields from all destination 
	 *		tables will be returned - including grafted fields.
	 *
	 * @returns array
	 */
	function &fields($includeAll=false, $includeTransient=false){
		if ( !$includeAll ){
			
			return $this->_schema['columns'];
		} else {
			if ( !isset($this->_cache[__FUNCTION__][intval($includeAll)][intval($includeTransient)]) ){
				$tables =& $this->getDestinationTables();
				$out = array();
				$used_names = array();
				foreach ( array_keys($tables) as $i ){
					foreach ( $tables[$i]->fields(false,true, $includeTransient) as $fld ){
						if ( @$fld['grafted'] and @$used_names[$fld['Field']] ) continue;
							// We don't want grafted fields overwriting valid fields in
							// other tables.
						$out[] = $tables[$i]->tablename.'.'.$fld['Field'];
						$used_names[ $fld['Field'] ] = 1;
					}
				}
				$this->_cache[__FUNCTION__][intval($includeAll)][intval($includeTransient)] = $out;
			}
			return $this->_cache[__FUNCTION__][intval($includeAll)][intval($includeTransient)];
		}
	}
	
	
	function getCardinality(){
		if ( isset($this->_schema['__cardinality__']) ){
			return $this->_schema['__cardinality__'];
		} else {
			return '*';
		}
	}
	
	
	
	
	/**
	 *
	 * Checks to see if the relationship has a field named $fieldname.
	 *
	 * @param string $fieldname The name of the field that we are checking.  This may be an absolute
	 * 		name or a relative name.
	 * @param boolean $checkAll If this is true then the method will return true
	 *		if the field exists in the destination tables (even if the field is grafted).
	 *		Otherwise it will only return true if the field is explicitly part of the 
	 *		relationship.
	 *		e.g. 
	 *		__sql__ = "select personID, firstName from people where groupID='$groupID'"
	 * 
	 *		In this relationship the lastName field may be part of the people table,
	 *		but it is not explicitly selected in the relationship.
	 *		So if we issue:
	 *		<code>
	 *		$relationship->hasField('people.lastName'); // false
	 *		$relationship->hasField('people.lastName', true); //true
	 *		</code>
	 *	@returns boolean
	 *
	 */
	function hasField($fieldname, $checkAll=false, $includeTransient=false){
		if ( strpos($fieldname,'.') === false ){
			if (in_array($fieldname, $this->_schema['short_columns'] ) ) return true;
			if ( ($checkAll or $includeTransient) and preg_grep('/\.'.preg_quote($fieldname, '/').'$/', $this->fields($checkAll, $includeTransient))){
				return true;
			}
			return false;
		} else {
			return in_array( $fieldname, $this->fields($checkAll, $includeTransient) );
		}
	}
	
	
	
	/**
	 * 
	 * Initializes the relationship given an associative array of values that is produced
	 * by parsing the relationships.ini file.
	 *
	 * @param $rel_values Initializing parameters for relationship.  Possible keys include:
	 *			__sql__ : An sql query to define the relationship.
	 *			
	 *
	 */
	function _init(&$rel_values){
		$r =& $this->_schema;
		/*
		 * First we will check the array for parameters.  Parameters might include
		 * default values for new records in the relationship - or for existing records
		 * in the relationship.
		 */
		foreach ($rel_values as $key=>$value){
			if ( strpos($key,":") !== false ){
				$path = explode(":", $key);
				$len = count($path);
				
				$val =& $r;
				
				for ($i=0; $i<$len; $i++ ){
					//if (!isset($val[$path[$i]]) ){
						if ( $i == $len -1 ) $val[$path[$i]] = $value;
						else {
							if ( !isset($val[$path[$i]]) ) {
								$val[$path[$i]] = array();
							}
							$valTemp =& $val;
							unset($val);
							$val =& $valTemp[$path[$i]];
							unset($valTemp);
						}
					//}
				}		
			
			}
		}
		if ( isset($rel_values['__cardinality__']) ) $r['__cardinality__'] = $rel_values['__cardinality__'];
		
		if ( array_key_exists( '__sql__', $rel_values ) ){
			// The relationship was defined using an SQL statement
			$r['sql'] = $rel_values['__sql__'];
			$matches = array();
			/* MOD START 051021 - shannah@sfu.ca - Using PEAR SQL parser package instead of regexes. */
			$parser = new SQL_Parser();
			$struct = $parser->parse($r['sql']);
			if ( PEAR::isError($struct) ){
				error_log($struct->toString()."\n".implode("\n", $struct->getBacktrace()));
				throw new Exception("Failed to parse relationship SQ.  See error log for details.", E_USER_ERROR);
				
			}
			$parser_wrapper = new SQL_Parser_wrapper($struct);
			$parser_wrapper->fixColumns();
			$r['parsed_sql'] =& $struct;
			$r['tables'] = $struct['table_names'];
			$r['columns'] = $struct['column_names'];
			foreach ($struct['columns'] as $colstruct){
				if ( $colstruct['type'] == 'ident' and @$colstruct['alias'] ){
					$r['aliases'][$colstruct['value']] = $colstruct['alias'];
				}
			}
			$temp = array();
			foreach ( $r['columns'] as $column ){
				$col = $parser_wrapper->resolveColumnName($column);
				if (preg_match('/\.$/', $col) ){
					$col = $col.'*';
				}
				$temp[] = $col;
			}
			$r['columns'] = $temp;
			unset($struct);
			unset($temp);
			/* MOD END 051021 */
			

		} else {
			// The relationship was not defined using SQL.  It merely defines match columns
			// and select columns
			
			$select = '*';
				// Default selection to all columns
				
			if ( array_key_exists( '__select__', $rel_values ) ){
				// __select__ should be comma-delimited list of column names.
				$select = $rel_values['__select__'];
			}
			
			$tables = array();
				// stores list of table names involved in this relation
				
			// Let's generate an SQL query based on the information given
			//
			
			$from = 'from ';
				// from portion of generated sql query
			$where = 'where ';
				// where portion of generated sql query
				
			foreach ( $rel_values as $c1 => $c2 ){
				// Iterate through all of the match columns of the relationship
				
				if ( in_array( $c1, array('__sql__', '__select__', '__sort__','__domain__','__cardinality__') ) ) continue;
					// special flags like sql, select, and sort are not column matchings.. we skip them.
				if ( strpos( $c1, ":" ) !== false ) continue;
					// This is a parameter so we ignore it.
					
			
				
				// get the paths of the related columns
				// Match columns may be given as Table_name.Column_name dotted pairs... we need to separate
				// the tablenames from the column names.
				$p1 = explode( '.', $c1);
				$p2 = explode('.', $c2);
				
				if ( count( $p1 ) == 1 ){
					// Only column name is given.. we assume the tablename is the current table.
					array_unshift( $p1, $this->_sourceTable->tablename );
				}
				if ( count($p2) ==1 ){
					// Only the column name is given for rhs... assume current table name.
					array_unshift( $p2, $this->_sourceTable->tablename );
				}
				
				// add the tables to our table array... we omit the current table though.
				if ( !in_array( $p1[0], $tables ) && $p1[0] != $this->_sourceTable->tablename) $tables[] = $p1[0];
				if ( !in_array( $p2[0], $tables ) && $p2[0] != $this->_sourceTable->tablename) $tables[] = $p2[0];
				
				// Simplify references to current table to be replaced by variable value
				if( $p1[0] == $this->_sourceTable->tablename ){
					$lhs = "'\$$p1[1]'";
				} else {
					$lhs = "$p1[0].$p1[1]";
				}
				
				if ( $p2[0] == $this->_sourceTable->tablename ){
					if ( strpos($p2[1], '$')===0){
						$var = '';
					} else {
						$var = '$';
					}
					$rhs = "'".$var.$p2[1]."'";
				} else {
					$rhs = "$p2[0].$p2[1]";
				}
				
				// append condition to where clause
				$where .= strlen($where) > 6 ? ' and ' : '';
				$where .= "$lhs=$rhs";
			}
			
			
			
			foreach ($tables as $table){
				$from .= $table.', ';
			}
			
			$from = substr( $from, 0, strlen($from)-2);
			
			$r['sql'] = "select $select $from $where";
			
			
			
			/* MOD START 051021 - shannah@sfu.ca - Using PEAR SQL parser package instead of regexes. */
			$parser = new SQL_Parser(null, 'MySQL');
			
			$struct = $parser->parse($r['sql']);
			$parser_wrapper = new SQL_Parser_wrapper($struct, 'MySQL');
			$parser_wrapper->fixColumns();
			$r['parsed_sql'] =& $struct;
			$r['tables'] = $struct['table_names'];
			$r['columns'] = $struct['column_names'];
			$temp = array();
			foreach ( $r['columns'] as $column ){
				$col = $parser_wrapper->resolveColumnName($column);
				if (preg_match('/\.$/', $col) ){
					$col = $col.'*';
				}
				$temp[] = $col;
			}
			$r['columns'] = $temp;
			unset($struct);
			
		}
		
		$res = $this->_normalizeColumns();
		if ( PEAR::isError($res) ) return $res;
		$r['short_columns'] = array();
		foreach ($r['columns'] as $col ){
			list($table,$col) = explode('.', $col);
			$r['short_columns'][] = $col;
		}
	
	}
	
	/**
	 * Scans the columns of a relationship and resolves wildcards and unqualified 
	 * column names into fully qualified column names.
	 */
	function _normalizeColumns(){
	
		$rel =& $this->_schema;
		
		
		$tables =& $rel['tables'];
		$selected_tables = array();
		$rel['selected_tables'] =& $selected_tables;
			// contains a list of the tables that actually have values returned in the select statement
		
		$len = sizeof($rel['columns']);
		for ($i=0; $i<sizeof($rel['columns']); $i++){
			$matches = array();
			
			// Case 1: This column has a wildcard.  eg: Profiles.* or just simply *
			if ( preg_match('/^(\w+\.){0,1}\*$/', $rel['columns'][$i], $matches) ){
				// we are returning all columns from a particular table
				if ( isset( $matches[1]) ){
					$table = $matches[1];
					
					$temp_tables = array();
					$temp_tables[] = substr($table, 0, strlen($table)-1);
				} else {
					$temp_tables = $tables;
				}
				$temp_columns = array();
				
				// go through each table requested, and extract its columns
				foreach ($temp_tables as $table){
					
					$table_table =& Dataface_Table::loadTable($table, $this->_sourceTable->db);
					if ( PEAR::isError($table_table) ){
						$table_table->addUserInfo("Failed to load table for table '$table'");
						return $table_shema;
					}
					
					$fields = array_keys($table_table->fields());
					for ($j=0; $j<count($fields); $j++){
						$fields[$j] = $table.'.'.$fields[$j];	
					}
					
					$temp_columns = array_merge($temp_columns, $fields);
					if ( !in_array( $table, $selected_tables ) ){
						$selected_tables[] = $table;
					}
				}
				
				// We need to add all of the columns that we found to the persistent columns list for this relationship.
				// But we need to remove the entry with the '*' because it is meaningless from here on out.
				// Case A: We are at the first element
				if ( $i==0 ){
					$rel['columns'] = array_merge( $temp_columns, array_slice( $rel['columns'], 1, $len-1) );
					
				// Case B: We are at the last element
				} else if ( $i==$len-1 ){
					$rel['columns'] = array_merge( $rel['columns'], $temp_columns );
					$len = sizeof($rel['columns']);
					$i = $len-1;
						// increment the counter so that we don't repeat all of the ones we just created.
						
				// Case C: We are somewhere in the middle of the columns list
				} else {
					
					$rel['columns'] = array_merge( array_slice( $rel['columns'], 0, $i),
												$temp_columns,
												array_slice( $rel['columns'], $i+1, $len-$i-1) );
					$len = sizeof($rel['columns']);
					$i = $i + sizeof($temp_columns) -1;
						// increment the counter so that we don't repeat all of the ones we just created.
				}
				unset($table_table);
					// to keep us from doing damage
					
			
			// Case 2: This is a fully qualified column address.	
			} else if ( preg_match('/^(\w+)\.(\w+)$/', $rel['columns'][$i], $matches) ) {
				
				$table = $matches[1];
				$column = $matches[2];
				$table_table =& Dataface_Table::loadTable($table, $this->_sourceTable->db);
				if ( PEAR::isError($table_table) ){
					$table_table->addUserInfo("Failed to load table for table '$table'");
					error_log($table_table->toString()."\n".implode("\n", $table_table->getBacktrace()));
					throw new Exception("Failed to validate column ".$rel['columns'][$i].". See error log for details.", E_USER_ERROR);
					
				}

				$selected_tables[] = $table;
				// this column is ok and already absolute.
				
			// Case 3: This column is specified by only a column name - needs to be made absolute.
			} else {
				// it is just a single column declaration
				$name = Dataface_Table::absoluteFieldName($rel['columns'][$i], $tables, $this->_sourceTable->db);
				if ( PEAR::isError($name) ){
					$name->addUserInfo("Failed get absolute field name for '".$rel['columns'][$i]."'");
					
					return $name;
				}
				$rel['columns'][$i] = $name;
				
				$matches = array();
				if ( preg_match('/(\w+)\.(\w+)/', $name, $matches)){
					$selected_tables[] = $matches[1];
				} else {
					PEAR::raiseError(Dataface_SCHEMA_PARSE_ERROR,null,null,null,"Error parsing table name from '$name' ");
				}
			}
		}
		
		$this->_schema['selected_tables'] = array_unique($this->_schema['selected_tables']);

			
				
				
				
	
	}
	
	function getName(){
		return $this->_name;
	}
	
	/**
	 *
	 * Returns the SQL query that can be used to obtain the related records of this 
	 * relationship.  Note that the value returned from this method cannot be passed
	 * directly to mysql_query().  It may still have unresolved wildcards and must
	 * be passed through Dataface_Record::parseString() to replace all wildcards.
	 *
	 * @param getBlobs If true then Blob columns will also be returned.  Default is false.
	 * @type boolean
	 * 
	 * @returns SQL Query
	 * @type string
	 */
	function getSQL($getBlobs=false, $where=0, $sort=0, $preview=1){
		$start = microtime_float();
		import('SQL/Compiler.php');
		import( 'SQL/Parser/wrapper.php');
		$loadParserTime = microtime_float() - $start;
		if ( isset($this->_sql_generated[$where][$sort][$preview]) and $this->_sql_generated[$where][$sort][$preview] ){
			/*
			 * The SQL has already been generated and stored.  We can just return it.
			 */
			if ( $getBlobs ){
				// We will be returning blob columns as well
				return $this->_schema['sql_with_blobs'][$where][$sort][$preview];
			} else {
				// We will NOT be returning BLOB columns
				return $this->_schema['sql_without_blobs'][$where][$sort][$preview];
			}
		} else {
			/*
			 * The SQL has not been generated yet.  We will generate it.
			 */
			$this->_sql_generated[$where][$sort][$preview] = true;
			if ( !isset( $this->_schema['sql_without_blobs'] ) ){
				$this->_schema['sql_without_blobs'] = array();
			}
			if ( !isset($this->_schema['sql_with_blobs']) ){
				$this->_schema['sql_with_blobs'] = array();
			}
			
			if ( defined('DATAFACE_USE_CACHE') and DATAFACE_USE_CACHE ){
				$cache_key_blobs = 'tables/'.$this->_sourceTable->tablename.'/relationships/'.$this->_name.'/sql/withblobs';
				$cache_key_noblobs = 'tables/'.$this->_sourceTable->tablename.'/relationships/'.$this->_name.'/sql/withoutblobs';
				// we are using the APC cache
				import( 'Dataface/Cache.php');
				$cache =& Dataface_Cache::getInstance();
				$this->_schema['sql_with_blobs'] = $cache->get($cache_key_blobs);
				$this->_schema['sql_without_blobs'] = $cache->get($cache_key_noblobs);
			
			}


			
			if ( !isset($this->_schema['sql_without_blobs'][$where][$sort][$preview]) or !isset($this->_schema['sql_with_blobs'][$where][$sort][$preview])){
				//if ( !$this->_schema['sql_without_blobs'][$where][$sort] ) $this->_schema['sql_without_blobs'] = array();
				//if ( !$this->_schema['sql_with_blobs'] ) $this->_schema['sql_with_blobs'] = array();
				
			
				$parsed = unserialize(serialize($this->_schema['parsed_sql']));
				$parsed['column_names'] = array();
				$parsed['column_aliases'] = array();
				$parsed['columns'] = array();
				$wrapper = new SQL_Parser_wrapper($parsed, 'MySQL');
				$blobCols = array();
				
				$tableAliases = array();
				// For tables that have custom SQL defined we sub in its SQL
				// here.
				foreach ( array_keys($parsed['tables']) as $tkey ){
					if ( $parsed['tables'][$tkey]['type'] == 'ident' ){
						$table =& Dataface_Table::loadTable($parsed['tables'][$tkey]['value']);
						$proxyView = $table->getProxyView();
						$tsql = $table->sql();
						if ( isset($tsql) and !$proxyView){
							$parsed['tables'][$tkey]['type'] =  'compiled_subselect';
							$parsed['tables'][$tkey]['value'] = $tsql;
							if ( !$parsed['tables'][$tkey]['alias'] ) $parsed['tables'][$tkey]['alias'] = $table->tablename;
						} else if ( $proxyView ){
							$parsed['tables'][$tkey]['value'] = $proxyView;
							if ( !$parsed['tables'][$tkey]['alias'] ) $parsed['tables'][$tkey]['alias'] = $table->tablename;
						}
						$tableAliases[$table->tablename] = $parsed['tables'][$tkey]['alias'];
						unset($table);
						unset($tsql);
					}
				}
				$done = array();
				$dups = array();
				foreach ( $this->fields(true)  as $colname){
					// We go through each column in the query and add meta columns for length.
					
					//$table =& Dataface_Table::getTableTableForField($colname);
					list($tablename, $col) = explode('.',$colname);
					if ( $tablename != $this->getDomainTable() and Dataface_Table::loadTable($this->getDomainTable())->hasField($col) ){
						// If this is a duplicate field we take the domain table value.
						$dups[$col] = $this->getDomainTable();
						continue;
					}
					if ( isset($done[$col]) ) $dups[$col] = $tablename;
					$done[$col] = true;
					
					$table =& Dataface_Table::loadTable($tablename);
					$alias = $wrapper->getTableAlias($tablename);
					if ( !$alias ){
						$alias = $tablename;
					}
					$colname = $alias.'.'.$col;
					if ( isset($field) ) unset($field);
					$field =& $table->getField($col);
					if ( PEAR::isError($field) ) $field = array();
					if ( $table->isPassword($col) ){
						unset($table);
						continue;
					}
					
					if ( $table->isBlob($col) ){
						$blobCols[] = $colname;
					}
					if ( @$tableAliases[$tablename] ){
						$tableAlias = $tableAliases[$tablename];
					} else {
						$tableAlias = $tablename;
					}
					
					if ( $tableAlias ) {
						$colFull = '`'.$tableAlias.'`.`'.$col.'`';
						//echo "Full";
					}
					else {
						$colFull = '`'.$col.'`';
						
					}
					
					$rfieldProps = array();
					if ( isset($this->_schema['field']) and isset($this->_schema['field'][$col])){
						$rfieldProps = $this->_schema['field'][$col];
					}
					
					$maxlen = 255;
					if ( @$rfieldProps['max_length'] ){
						$maxlen = intval($rfieldProps['max_length']);
					}

					if ( in_array(strtolower($table->getType($col)), array('timestamp','datetime')) ){
						
						$parsed['columns'][] = array('type'=>'compiled_func', 'table'=>null, 'value'=>"ifnull(convert_tz(".$colFull.",'SYSTEM','".addslashes(df_tz_or_offset())."'), ".$colFull.")", 'alias'=>$col);
					} else if ( $preview and $table->isText($col) and !@$field['struct'] and !$table->isXML($col)){
						 $parsed['columns'][] = array('type'=>'compiled_func', 'table'=>null, 'value'=>"SUBSTRING($colFull, 1, $maxlen)", 'alias'=>$col);
					} else {
						
						$parsed['columns'][] = array('type'=>'ident', 'table'=>$tableAlias, 'value'=>$col, 'alias'=>null);
					}
					//$wrapper->addMetaDataColumn($colname);
					// Note:  Removed *length* metadata columns for now.. not hard to add
					// back.  Will wait to see if anyone screams!
					// Steve Hannah 071229
					unset($table);
					
				}

				
				if ( $where !== 0 ){
					$whereClause = $where;
					// Avoid ambiguous column error.  Any duplicate columns need to be specified.
					foreach ( $dups as $dcolname=>$dtablename ){
						$whereClause = preg_replace('/([^.]|^) *`'.preg_quote($dcolname).'`/','$1 `'.$dtablename.'`.`'.$dcolname.'`', $whereClause);
					}
					$wrapper->addWhereClause($whereClause);
				} 
				if ( $sort !==0){
					$sortClause = $sort;
					foreach ( $dups as $dcolname=>$dtablename ){
						$sortClause = preg_replace('/([^.]|^) *`'.preg_quote($dcolname).'`/','$1 `'.$dtablename.'`.`'.$dcolname.'`', $sortClause);
					}
					$wrapper->setSortClause($sortClause);
				}
				
				//$compiler = new SQL_Compiler(null, 'mysql');
				$compiler =& SQL_Compiler::newInstance('mysql');
				$compiler->version = 2;
				$this->_schema['sql_with_blobs'][$where][$sort][$preview] = $compiler->compile($parsed);
				
				
				foreach ($blobCols as $blobCol){
					$wrapper->removeColumn($blobCol);
				}
				$this->_schema['sql_without_blobs'][$where][$sort][$preview] = $compiler->compile($parsed);

				if ( defined('DATAFACE_USE_CACHE') and DATAFACE_USE_CACHE){
					$cache->set($cache_key_blobs, $this->_schema['sql_with_blobs']);
					$cache->set($cache_key_noblobs, $this->_schema['sql_without_blobs']);
				
				}
				

			}
			
			/*
			 * Now that the SQL is generated we can call ourselves and the first
			 * case will now be initiated (ie: the generated sql will be returned).
			 */
			 $timeToGenerate = microtime_float()-$start;
			 if ( DATAFACE_DEBUG ){
				$this->app->addDebugInfo("Time to generate sql for relationship {$this->name} : $timeToGenerate");
			}
			return $this->getSQL($getBlobs, $where, $sort);
		}
		
		
		
		
	}
	
	
	/**
	 * @brief Returns the label of this relationship.  If the action:label directive is
	 * set for the relationship in the relationships.ini file, then it will use this.  
	 * otherwise it will use the name of the relationship as a basis for determining the
	 * label.
	 *
	 * @return string
	 * @see getSingularLabel()
	 */
	function getLabel(){
		$action = $this->_sourceTable->getRelationshipsAsActions(array(), $this->_name);
		return $action['label'];
	}
	
	/**
	 * @brief Returns the label of this relationship as a singular term.  This uses the 
	 * action:singular_label directive from the relationships.ini file if available.  Otherwise
	 * it will attempt to singularize the label as recieved from getLabel().
	 *
	 * @return string
	 *
	 * @see df_singularize()
	 */
	function getSingularLabel(){
		$action = $this->_sourceTable->getRelationshipsAsActions(array(), $this->_name);
		if ( !isset($action['singular_label']) ){
		
			$label = $this->getLabel();
			$action['singular_label'] = df_singularize($label);
			
		
		}
		
		return $action['singular_label'];
		
		
		
	}
	
	
	/**
	 * Indicates whether new records can be added to this relationship.
	 *
	 * @return false if, in the relationships.ini file the actions:addnew directive is false or 0
	 * Otherwise this returns true.
	 */
	function supportsAddNew(){
		if ( !isset($this->addNew) ){
			$this->addNew = !( isset( $this->_schema['actions']['addnew'] ) and !$this->_schema['actions']['addnew'] );
		}
		return $this->addNew;
	}
	
	/**
	 * Indicates whether existing records can be added to this relationship.
	 *
	 * @return false if, in the relationships.ini file the actions:addexisting directive is false or 0.
	 * Otherwise returns true.
	 */
	function supportsAddExisting(){
		if ( !isset($this->addExisting) ){
			$this->addExisting=true;
			$fkeys = $this->getForeignKeyValues();
			if ( count($fkeys) == 1 ){
				$this->addExisting = false;
				// If the relationship only has a single destination table
				// then it probably won't support adding existing records
				// Unless the foreign key allows null values - then it is 
				// possible that records that aren't currently part of a 
				// relationship can be added.
				/*
				$table =& Dataface_Table::loadTable($this->getDomainTable());
				$keys = array_keys($fkeys[$this->getDomainTable()]);
				foreach ($keys as $key){
					$field =& $table->getField($key);
					if ( strtoupper($field['Null']) == 'YES' ){
						$this->addExisting=true;
						break;
					}
				}
				*/
			}
			if ( isset( $this->_schema['actions']['addexisting'] ) and !$this->_schema['actions']['addexisting']  ){
				$this->addExisting = false;
			}
			else if ( isset( $this->_schema['actions']['addexisting'] ) and $this->_schema['actions']['addexisting']  ) {
				$this->addExisting = true;
			}
		}
		return $this->addExisting;
	}
	
	/**
	 * Indicates whether records can be removed from this relationship.
	 *
	 * @return false if, in the relationships.ini file, the actions:remove directive is false or 0
	 * Otherwise returns true.
	 */
	function supportsRemove(){
		if (isset( $this->_schema['actions']['remove'] ) and !$this->_schema['actions']['remove'] ) return false;
		return true;
	
	}
	
	function showTabsForAddNew(){
		return ( @$this->_schema['prefs']['addnew']['show_tabs'] !== '0' );
	}
	
	
	
	/**
	 * @returns Dataface_Table
	 */
	function &getSourceTable(){
		return $this->_sourceTable;
	}
	
	/**
	 * Returns array of references to key fields in relationship.
	 */
	function &keys(){
		if ( !isset($this->_keys) ){
			$this->_keys = array();
			$destTables =& $this->getDestinationTables();
			foreach ( array_keys($destTables) as $x ){
				$table =& $destTables[$x];
				$tkeys = array_keys($table->keys());
				foreach ($tkeys as $tkey){
					$this->_keys[$tkey] = $table->getField($tkey);
				}
			}
		}
		return $this->_keys;	
	}
	
	/**
	 * Returns array of references to all of the destination tables in this relationship.
	 * @return array(Dataface_Table)
	 */
	function &getDestinationTables(){
		if ( !isset( $this->_destinationTables ) ){
			$this->_destinationTables = array();
			$this->_fieldTableLookup = array();
			$columns =& $this->_schema['columns'];
			$tables = array();
			foreach ($columns as $column){
				list($tablename, $fieldname) = explode('.', $column);
				//$table =& $this->_sourceTable->getTableTableForField($this->_name.'.'.$column);
				
				$table =& Dataface_Table::loadTable($tablename);
				$this->_fieldTableLookup[$fieldname] =& $table;
				$tables[] =& $table;
				unset($table);
			}
			//$this->_destinationTables = array_unique($tables);
			// For some reason array_unique does not seem to work with references in PHP 4
			$this->_destinationTables = array();
			$found = array();
			foreach ( array_keys($tables) as $tableIndex ){
				if ( @$found[$tables[$tableIndex]->tablename] ) continue;
				
				$found[$tables[$tableIndex]->tablename] = true;
				$this->_destinationTables[] =& $tables[$tableIndex];
			}
		}
		
		return $this->_destinationTables;
	}
	
	/**
	 * Returns reference to table that contains the given field.
	 * @param string $field The name of the field.
	 * @returns Dataface_Table
	 */
	function &getTable($field=null){
		if ( $field === null ) return $this->_sourceTable;
		if ( strpos($field, '.') !== false ){
			list($tablename, $field) = explode('.', $field);
			$table = Dataface_Table::loadTable($tablename);
			return $table;
		}
		$this->getDestinationTables();
		if ( isset($this->_fieldTableLookup[$field]) ) return $this->_fieldTableLookup[$field];
		else {
			$fields = preg_grep('/\.'.preg_quote($field,'/').'$/', $this->fields(true, true));
			if ( !$fields ){
				$null = null;
				return $null;
			} else {
				list($tablename) = explode('.', reset($fields));
				$this->_fieldTableLookup[$field] =& Dataface_Table::loadTable($tablename);
				//echo $tablename;
				return $this->_fieldTableLookup[$field];
			}
			
		}
	}
	
	/**
	 * Returns the alias for the given table according to the SQL used
	 * to define the relationship - or null.
	 */
	function getTableAlias($tableName){
		if ( !isset($this->_schema) || !isset($this->_schema['parsed_sql']) || !is_array($this->_schema['parsed_sql']['table_names']) ) return null;
		$idx = array_search($tableName, $this->_schema['parsed_sql']['table_names']);
		if ( $idx !== false ){
			return $this->_schema['parsed_sql']['table_aliases'][$idx];
		}
		return null;
		
	}
	
	
	/**
	 * Returns the field definition for a field in the relationship.
	 * @param string $fieldname The field name.  This can be absolute or
	 *		relative. (e.g. Absolute would be something like people.firstName,
	 *		whereas relative would be something like firstName).
	 * @returns array or null if field doesn't exist.
	 */
	function &getField($fieldname){
		if ( !isset($this->_cache[__FUNCTION__][$fieldname]) ){
			if ( strpos($fieldname, '.') !== false ){
				list($tablename, $sfieldname) = explode('.', $fieldname);
				$table =& Dataface_Table::loadTable($tablename);
				$field =& $table->getField($sfieldname);
				
			} else {
				// Check the domain table first
				$domainTable = Dataface_Table::loadTable($this->getDomainTable());
				$f =& $domainTable->getField($fieldname);
				if ( !PEAR::isError($f) ) return $field =& $f;
				else {
					
					// Domain table doesn't have a field by this name
					$fields = preg_grep('/\.'.preg_quote($fieldname,'/').'$/', $this->fields(true));
				
					if ( count($fields) > 0 ){
						$lfieldname = reset($fields);
						
						list($tablename, $sfieldname) = explode('.',$lfieldname);
						$table =& Dataface_Table::loadTable($tablename);
						$field =& $table->getField($sfieldname);
						
					} else {
						$field = null;
					}
				}
			}
			$this->_cache[__FUNCTION__][$fieldname] =& $field;
		}
		return $this->_cache[__FUNCTION__][$fieldname];
	}
	
	
	/**
	 * Returns an SQL query that will obtain the domain of this relationship.  The Domain
	 * is slightly different than the actual relationship, in that it returns all eligible
	 * rows that can be added to the relationship (and rows already in the relationship).
	 */
	function getDomainSQL(){
		
		$relationship =& $this->_schema;
			// obtain reference to the relationship in question
		
			
		// The 'domain_sql' attribute of a relationship defines the SQL select statement that
		// is used to obtain the set of candidates for a relationship.  This can be specified 
		// in the ini file using the __domain__ attribute of a relationship, or it can be parsed
		// from the existiing 'sql' attribute.
		if ( !isset( $relationship['domain_sql'] ) ){
			import( 'SQL/Compiler.php');
				// compiles SQL tree structure into query strings
			import( 'SQL/Parser/wrapper.php');
				// utility methods for dealing with SQL structures
			$compiler = new SQL_Compiler();
				// the compiler we will use to generate the eventual SQL
			$parsed_sql = unserialize(serialize($relationship['parsed_sql']));
				// we make a deep copy of the existing 'parsed_sql' structure that was 
				// created in the "readRelationshipsIniFile" method.  We deep copy, because
				// some of the methods in SQL_Parser_wrapper work directly on the 
				// datastructure - but we want to leave it unchanged.
			$wrapper = new SQL_Parser_wrapper($parsed_sql);
				// create a new wrapper to operate on the sql data structure.
			$wrapper->removeWhereClausesWithTable( $this->_sourceTable->tablename);
			$wrapper->removeJoinClausesWithTable( $this->_sourceTable->tablename);
				// We remove all Where and Join clauses that use columns from the current table.
				// This is because portions of the sql pertaining to the current table
				// likely represent specifications within the domain to mark that an 
				// element of the domain is related to the current table.
			$wrapper->removeWhereClausesWithPattern( '/\$\w+/' );
			$wrapper->removeJoinClausesWithPattern( '/\$\w+/' );
				// Similarly we need to remove any clauses containing variables which
				// get filled in by the current table.  The rationale is the same as
				// for removing clauses pertaining to the current table.
			$fkVals = $this->getForeignKeyValues();
				// We obtain the foreign key values for this relationship because they
				// will help us to decide which columns in the remaining query are 
				// helpful for obtaining the domain.
			$uselessTables = array();
				// will hold list of tables that we don't need
			$fkTables = array_keys($fkVals);
				// list of tables that are involved in foreign key relationships in this
				// relationship.
			foreach ($fkVals as $fkTable => $fkFields){
				$foundVal = 0;
				$foundLink = 0;
					// keep track of which tables actually have real values assigned.
				foreach ($fkFields as $fieldVal){
					//if ( !preg_match('/^__(\w+)_auto_increment__$/', $fieldVal) ){
					//	// A field with a value of the form __Tablename__auto_increment__ is a placeholder
					//	// for an auto generated id.  If the only values specified for a table are placeholders
					//	// then that table is pretty much useless as a domain query... it can be eliminated.
					//	$foundVal++;
					//	
					//	
					//}
					if ( is_scalar($fieldVal) and strpos($fieldVal, '$') === 0 ){
						// This table is linked directly to the current table... hence it is only a join
						// table.
						$foundLink++;
					}
				}
				if ( $foundLink){
					// no real valus found.. mark table as useless.
					$uselessTables[] = $fkTable;
				}
				
			}
			
			
			
			foreach ($uselessTables as $table_name){
				// Remove all useless tables from the query's where and join clauses.
				$wrapper->removeWhereClausesWithTable( $table_name );
				$wrapper->removeJoinClausesWithTable( $table_name );
				$wrapper->removeColumnsFromTable( $table_name );
			}
			
			$domain_tables = array_diff($relationship['selected_tables'], $uselessTables);
			if ( !$domain_tables ) $domain_tables = $relationship['selected_tables'];
			
			$table_ranks = array();
			foreach ($this->_schema['columns'] as $col){
				list($tname) = explode('.',$col);
				if ( !isset($table_ranks[$tname]) ) $table_ranks[$tname] = 0;
				$table_ranks[$tname]++;
			}

			$high = null;
			$high_score = 0;
			foreach ( $domain_tables as $dt ){
				if ( $table_ranks[$dt] > $high_score ){
					$high = $dt;
					$high_score = $table_ranks[$dt];
				}
			}
			$domain_tables = array($high);
				
			
			if ( count($domain_tables) !== 1 ){
				return PEAR::raiseError("Error calculating domain tables for relationship '".$this->_name."'.  Selected tables are {".implode(',',$relationship['selected_tables'])."} and Useless tables are {".implode(',', $uselessTables)."}.",null,null,null,1);
			}
			$relationship['domain_table'] = array_pop($domain_tables);
			
			
			$wrapper->packTables(/*$relationship['selected_tables']*/);
				// Previous steps have only eliminated useless tables with respect to query
				// parameters.  There may still be some tables listed in the query that don't
				// offer anything.  Notice that we pass the list of selected tables to this
				// method to indicate that tables whose columns are selected need to be there
				// and should be left intact.
			$relationship['domain_sql'] = $compiler->compile($parsed_sql);
		}
		return $relationship['domain_sql'];
		
	
	}
	
	/**
	 * Returns the name of the "domain table" for this relationship.  The domain table is the main
	 * table that comprises the data of a related record.  I.e., it is the destination table that is
	 * not a join table.
	 * The join tables serve to join the source table to the domain table.
	 * @returns string Name of the domain table.
	 */
	function getDomainTable(){
		if ( !isset($this->domainTable) ){
			$res = $this->getDomainSQL();
			if ( PEAR::isError($res) ){
				return $res;
			}
			$this->domainTable =  $this->_schema['domain_table'];
		}
		return $this->domainTable;
	}
	
	
	/**
	 * Gets the values of the foreign keys of a particular relationship.  This returns an associative
	 * array with the following structure:
	 *   Array(
	 *			"table1" => Array( "field1"=>"value1", "field2"=>"value2", ... ),
	 *			"table2" => Array( "field1"=>"value1", "field2"=>"value2", ... ),
	 *			...
	 *	);
	 * @param relationship_name The name of the relationship
	 * @param values Supplementary values passed as array with keys = absolute field names, values = serialized values.
	 * @param sql If provided, this will be used as the select statement that we dissect.
	 * @parseValues If true we parse out variables.  If false, we simply return the variables to be parsed later.
	 * @throws PEAR_Error if there is insufficient values supplied for the foreign key to work.
	 *
	 */	
	function getForeignKeyValues($values = null, $sql = null, $record = null){
		if ( is_object($record) ) $record_id = $record->getId();
		if ( !isset($values) and !isset($sql) and !isset($record) and isset($this->_cache[__FUNCTION__]) ){
			return $this->_cache[__FUNCTION__];
		}
		if ( !isset($values) and !isset($sql) and is_object($record) and isset($this->_cache[__FUNCTION__.$record_id]) ){
			return $this->_cache[__FUNCTION__.$record_id];
		}
		// Strategy:
		// ----------
		// 1. Label all fields involved in the foreign key so that fields that are equal have the
		//    same label.  Eg: In the query:
		// 		select * 
		//			from Students 
		//			inner join Student_Courses 
		//				on Students.id = Student_Courses.studentid
		//			inner join Courses
		//				on Student_Courses.courseid = Courses.id
		//		where
		//			Students.id = '$id'
		//
		//	In the above query Students.id and Student_Course.studentid would have the same label, and
		//  Student_Courses.courseid and Courses.id would have the same label.
		//  ie: Label(Students.id) = Label(Student_Courses.studentid) ^ Label(Student_Courses.courseid) = Label(Courses.id)
		//
		// 2. Assign values for each label.  All fields with a particular label have the same value.
		//	In the above query, we would have:
		//		Value(Label(Students.id)) = '$id'
		//		**Note from above that Label(Students.id)=Label(Student_Courses.studentid) so their values are also equal.
		//
		// 3. For labels without a value find out if one of the fields assuming that label is an auto-increment field. If so
		// 	  we assign the special value '__Tablename__auto_increment__' where "Tablename" is the name of the table  whose
		// 	  field is to be auto incremented.
		//
		// 4. Collect the the values in a structure so that we can lookup the values of any particular field in any particular
		//    table easily.  Return this structure.
		$relationship =& $this->_schema;
		
		if ( $sql !== null ){
			// An SQL query was specified as a parameter, we parse this and use the resulting data structure
			// for the rest of the computations.
			if ( is_string($sql) ) {
				$parser = new SQL_Parser(null,'MySQL');
				$sql = $parser->parse($sql);
			}
			$select =& $sql;
		} else {
			// We use the 'parsed_sql' entry in the relationship as the basis for our dissection.
			$select =& $relationship['parsed_sql'];
		}
		
		
		// Build equivalence classes for column names.
		$labels = array();
		$vals = array();
		$this->_makeEquivalenceLabels($labels, $vals, $select);
		
		// Fill in some default values
		
		if ( is_array($values) ){
			foreach ($values as $field_name => $field_value ){
				
				if ( !$field_value ) continue;
					// we don't want empty and null values to act as defaults because they 
					//  tend to screw things up when we are adding related records.
					
				if ( isset( $labels[$field_name] ) ) $label = $labels[$field_name];
				else {
					$label = $field_name;
					$labels[$field_name] = $label;
				}
				
		
				$vals[$label] = $field_value;
			
			}
		}
		
	
		
		// next we need to find 'circular links'.  Ie: There may be columns that are only specified to be equal to each other.  Most of the
		// time this means that one of the fields is an auto increment field that will be automatically filled in.  We need to insert
		// a special value (in this case), so that we know this is the case.
		foreach ( $labels as $field_name=>$label ){
			if ( !isset( $vals[$label] ) ){
				$field =& Dataface_Table::getTableField($field_name);
				$table_auto_increment = null;
				foreach ( $labels as $auto_field_name=>$auto_label ){
					if ( $auto_label == $label ){
						$auto_field =& Dataface_Table::getTableField($auto_field_name);
						if ( $auto_field['Extra'] == 'auto_increment' ){
							list($table_auto_increment) = explode('.', $auto_field_name);
							unset($auto_field);
							break;
						}
						unset($auto_field);
					}
				}
				if ( isset($table_auto_increment) ){
					//list($table) = explode('.', $field_name);
					$vals[$label] = "__".$table_auto_increment."__auto_increment__";
				} else {
					$vals[$label] = new Dataface_Relationship_ForeignKey($this, $labels, $label);
				}
				unset($field);
			}
		}
			
		$table_cols = array();
		foreach ( $labels as $field_name=>$label){
			$fieldArr =& Dataface_Table::getTableField($field_name);
			list( $table, $field ) = explode('.', $field_name);
			if ( !$table ) continue;
			if ( !isset( $table_cols[$table] ) ) $table_cols[$table] = array();
 			$table_cols[$table][$field] = ( is_scalar(@$vals[$label]) and $record !== null  and !preg_match('/(blob|binary)/', strtolower($fieldArr['Type'])) ) ? $record->parseString(@$vals[$label]) : @$vals[$label];
			unset($fieldArr);
		}
		
		// make sure that each table at least sets all of the mandatory fields.
		foreach ( $table_cols as $table=>$cols ){
			$tableObject =& Dataface_Table::loadTable($table);
			foreach ( array_keys($tableObject->mandatoryFields()) as $key ){
				if ( !isset( $cols[$key] ) ){
					$this->errors[] = PEAR::raiseError(DATAFACE_TABLE_RELATED_RECORD_REQUIRED_FIELD_MISSING_ERROR, null,null,null, "Could not generate SQL to add new record to relationship '".$this->_name."' because not all of the required fields have values.  In particular, the field '$key' of table '$table' is missing but is a key of the table.");
				}
			}
			unset($tableObject);
		}
		
		
		if ( !isset($values) and !isset($sql) and !isset($record) ){
			$this->_cache[__FUNCTION__] = $table_cols;
		}
		if ( !isset($values) and !isset($sql) and is_object($record) ){
			$this->_cache[__FUNCTION__.$record_id] = $table_cols;
		}

		return $table_cols;
	
	}
	
	function isNullForeignKey(&$val){
		return is_a($val, 'Dataface_Relationship_ForeignKey');
	}
	
	/**
	 * Returns the distance of a specified table from the current table in 
	 * a relationship.  Tables that are directly joined to the current table
	 * have a distance of 1.  
	 * Tables in the relationship but not directly joined to the current table
	 * have a distance of 2 (for simplicity sake), and tables not found in 
	 * the relationship have a distance of 999.
	 * @param string $table The name of a table to check.
	 */
	function getDistance($table){
		$fkeys = $this->getForeignKeyValues();
		if ( !isset($fkeys[$table]) ) return 999;
		foreach ( $fkeys[$table] as $key=>$value ){
			if ( is_scalar($value) and strlen($value) > 0 and $value{0} == '$' ) return 1;
			
		}
		return 2;
	}
	
	function getAddExistingFilters(){
		$this->_addExistingFilters = null;
		if ( !isset($this->_addExistingFilters) ){
			$this->_addExistingFilters = array();
			$fkeys = $this->getForeignKeyValues();
			if ( count($fkeys) == 1 ){
				foreach ( $fkeys[$this->getDomainTable()] as $fname=>$fval){
					$this->_addExistingFilters[$fname] = '=';
				}
			}
		}
		return $this->_addExistingFilters;
			
	}
	
	/**
	 * Returns a valuelist of the records that can be added to this relationship.
	 *
	 * @param Dataface_Record &$record The parent record.
	 * @param array $query A query to filter the possible records.
	 * @returns array Associative array where the keys are of the form
	 * 		key1=value1&key2=value2  and the values are the title of the record.
	 */
	function getAddableValues(&$record, $filter=array()){
		$filter = array_merge($this->getAddExistingFilters(), $filter);
		$t =& Dataface_Table::loadTable($this->getDomainTable());
		$r =& $this->_schema;
		$tkey_names = array_keys($t->keys());
		if ( !is_a($record, 'Dataface_Record') ){
			throw new Exception("Attempt to call getAddableValues() without providing a Dataface_Record as context.", E_USER_ERROR);
			
		}
		if ( ( $res = $record->callDelegateFunction($this->_name.'__'.__FUNCTION__) ) !== null ){
			return $res;
		}
	
		if ( isset( $this->_schema['vocabulary']['existing'] ) ){
			// A custom vocabulary has been specified in the relationships.ini
			// file for this relationship.
			$options_temp = $t->getValuelist($r['vocabulary']['existing']);
			$options = array();
			foreach (array_keys($options_temp) as $optkey){
				if ( strpos($optkey, '=') === false ){
					$options[$tkey_names[0].'='.urlencode($optkey)] = $options_temp[$optkey];
				} else {
					$options[$optkey] = $options_temp[$optkey];
				}
			}
		} else {
			// No custom vocabulary has been specified.  Let's do our best
			// to figure out what the vocabulary should be.
			//$fkeys = $this->getForeignKeyValues(null,null,$record);
			$table = $this->getDomainTable();
			if ( isset( $fkeys[$table] ) ){
				$query = $fkeys[$table];
				foreach ($query as $key=>$val){
					if ( $this->isNullForeignKey($val) or strpos($val,'$')===0 or $val == '__'.$table.'__auto_increment__'){
						unset($query[$key]);
					}
				}
			} else {
				$query = array();
			}
			$query = array_merge($filter, $query);
			$qt = new Dataface_QueryTool($table, $this->_sourceTable->db, $query);
			$options = $qt->getTitles(true,false,true/*Ignores 250 record limit*/);
		}
		
		return $options;
	
	}
	
	
	
	/**
	 * Creates labels fields involved in an sql query that such that fields that have the same
	 * value have the same labels. This works by taking 2 references to arrays as parameters.
	 * The first parameter, $labels, will map Column names to label names, and the second 
	 * parameter, $values, maps label names to values.
	 * @param labels Out parameter to map field names to labels.
	 * @param values Out parameter to map labels to values.
	 * @param sql_data In parameter - the sql data structure as returned by SQL_Parser::parse()
	 */
	 function _makeEquivalenceLabels(&$labels, &$values, &$sql_data){
		
		$roots = array();
		if ( isset( $sql_data['where_clause'] ) and is_array( $sql_data['where_clause'] )){
			$roots[] =& $sql_data['where_clause'];
		}
		if ( isset( $sql_data['table_join_clause'] ) and is_array( $sql_data['table_join_clause']) ){
			foreach ( $sql_data['table_join_clause'] as $clause ){
				if ( is_array($clause) ){
					$roots[] = $clause;
				}
			}
		}
		$parser_wrapper = new SQL_Parser_wrapper($sql_data);
		foreach ($roots as $root){
			$this->_makeEquivalenceLabels_rec($labels, $values, $root, $parser_wrapper);
		}
	
	
	}
	
	
	/**
	 * The recursive part of the algorithm to make equivalence labels.  See 
	 * Table::_makeEquivalenceLabels()
	 * @param labels Out param to map field names to labels.
	 * @param values Out param to map labels to values
	 * @param root Reference to a node of the parse tree that we are currently dealing with.
	 * @param parser_wrapper Reference to the parser wrapper that can be used to operate on and query
	 * 		  the parsed sql data structure (as returned by SQL_Parser::parse()
	 */
	function _makeEquivalenceLabels_rec( &$labels, &$values, &$root, &$parser_wrapper){
		
		if ( isset( $root['op'] ) ){
			if ( $root['op'] == '=' ){
				$label = '';
				$value = null;
				$fields = array();
				$existingLabels = 0;
				$oldLabel = null;
					// keep track of the number of existing labels.
				foreach ( array('arg_1','arg_2') as $arg ){
					switch ($root[$arg]['type']){
						case 'ident':
							$field_name = Dataface_Table::absoluteFieldName(
												$parser_wrapper->resolveColumnName($root[$arg]['value']), 
												$parser_wrapper->_data['table_names']
							
							);
							if ( !is_string($field_name) ){
								echo "Field name is not a string.";
								echo get_class($field_name);
								if ( is_a($field_name, 'PEAR_Error') ) echo $field_name->toString();
							}
							$fields[] = $field_name;
							
							// If this column already has a label, then we use it as the common label
							if ( isset( $labels[$field_name] ) ) {
								$existingLabels++;
								if ( $existingLabels > 1 ){
									// If the other column already had a label, then we keep track of it
									$oldLabel = $label;
								}
								$label = $labels[$field_name];
							}
									
							break;
						
						case 'text_val':
						case 'int_val':
						case 'real_val':
							$value = $root[$arg]['value'];
							break;
					}
							
				}
				// Assert (count($fields) == 1 or count($fields) == 2)
				// Assert (count($fields) == 1 => $value !== null )
				// Assert (count($fields) == 2 => $value === null )
				
				$label = ( $label ? $label : $fields[0] );
					// Obtain the label for these columns.  If there are 2 columns, they must have the same label
				foreach ( $fields as $field ){
					if ( !isset( $labels[$field] ) ) $labels[$field] = $label;
				}
				
				// Now we have to change labels of all fields that contained the old label.
				if ( $oldLabel !== null ){
					foreach ( $labels as $field_name=>$field_label ) {
						if ( $field_label == $oldLabel ){
							$labels[$field_name] = $label;
						}
					}
				}
				
				// Now we update the value for the label if there is a value.
				if ( $value !== null ){
					$values[$label] = $value;
				}
			}
		}
		
		foreach ( $root as $key=>$value ){
			if ( is_array($value) ){
				$this->_makeEquivalenceLabels_rec($labels, $values, $value, $parser_wrapper);
			}
		}
	}
	
	
	/**
	 * @brief Returns the name of the column on which this relationship is ordered.
	 *
	 * This depends on the metafields:order directive of the relationships.ini file
	 * to set the column upon which the relationship should be ordered.  If this is
	 * not set, then this method will return a PEAR_Error object.
	 *
	 * @returns string  The name of the column on which to sort this relationship by default.
	 *	  Note that if the metafields:order directive is not set in the relationships.ini
	 *		file this will return a PEAR_Error object.
	 * 		
	 *
	 * @since 0.8
	 *
	 * @see http://xataface.com/documentation/tutorial/getting_started/relationships
	 * @see http://www.xataface.com/wiki/relationships.ini_file
	 * @see Dataface_Record::moveUp()
	 * @see Dataface_Record::moveDown()
	 * @see Dataface_Record::sortRelationship()
	 */
	function getOrderColumn(){

		$order_col = ( ( isset( $this->_schema['metafields']['order']) and $this->_schema['metafields']['order'] ) ? $this->_schema['metafields']['order'] : null );
		if ( !isset($order_col) ){
			return PEAR::isError('Attempt to sort relationship "'.$this->_name.'" but no order column was defined.');
		}
		return $order_col;
	}
	
	
	/**
	 * Indicates whether this relationship is a parent relationship.  
	 * <p>A parent relationship generally implies that first record found in the
	 * relationship is the parent of the current record in the heirarchical
	 * structure.</p>
	 * <p>A relationship can be declared to be a parent relationship in the 
	 * relationships.ini file by adding "meta:class = parent".</p>
	 *
	 * @return boolean True if the relationship is a parent relationship.
	 */	
	function isParentRelationship(){
		return ( isset( $this->_schema['meta']['class']) and strtolower($this->_schema['meta']['class']) == 'parent');
	}
	
	/**
	 * indicates whether this relationship is a "children" relationship.
	 * <p>A children relationship generall implies that related records are 
	 * children of the current record in the heirarchical structure.  This
	 * is useful for data models where records have an inherent heirarchical
	 * structure (like a file system).</p>
	 * <p>A relationship cann be declared to be a "children" relationship in the
	 * relationships.ini file by adding "meta:class = children" </p>
	 *
	 * @return boolean True if the relationship is a children relationship.
	 */
	function isChildrenRelationship(){
		return ( isset($this->_schema['meta']['class']) and strtolower($this->_schema['meta']['class']) == 'children');
	}
	
	
	/**
	 * Indicates if this is a one-to-many relationship.  A one-to-many relationship
	 * must be handled differently when it comes to adding/deleting/cutting/pasting
	 * because it can't simply by linked to additional parent records.  Adding a 
	 * new parent record implies removing the old parent record.  This has permission
	 * implications because it would actually involve changing a field in the 
	 * related record just to add or remove it from a relationship.
	 *
	 * @return boolean True if the relationship is one-to-many.
	 * @since 0.6.1
	 */
	function isOneToMany(){
		if ( $this->getMaxCardinality() === 1 ) return false;
		if ( isset($this->_cache[__FUNCTION__]) ) return $this->_cache[__FUNCTION__];
		$this->_cache[__FUNCTION__] = (count($this->getForeignKeyValues()) == 1);
		return $this->_cache[__FUNCTION__];
	}
	
	/**
	 * Indicates if this is a many-to-many relationship.  A relationship must be
	 * either one-to-many or many-to-many.  Many-to-many relationships are more
	 * flexible when it comes to copying records because their records can live
	 * in multiple relationships concurrently by simply adding a record to the 
	 * join table.
	 * @return boolean True if the relationship is many-to-many.
	 * @since 0.6.1
	 */
	function isManyToMany(){
		if( $this->getMaxCardinality() === 1 ) return false;
		return !$this->isOneToMany();
	}
	
	function getMinCardinality(){
		$cardinality = $this->getCardinality();
		if ( $cardinality == '*' ) return 0;
		else if ( $cardinality == '+' ) return 1;
		else if ( strpos($cardinality, '..') !== false ){
			list($min,$max) = array_map('trim',explode('..', $cardinality));
			$min = intval($min);
			return $min;
			
		} else {
			return 0;
		}
	}
	
	function getMaxCardinality(){
		$cardinality = $this->getCardinality();
		if ( $cardinality == '*' ) return 0;
		else if ( $cardinality == '+' ) return 1;
		else if ( strpos($cardinality, '..') !== false ){
			list($min,$max) = array_map('trim',explode('..', $cardinality));
			if ( $max == '*' ) return 0;
			return intval($max);
			
		} else {
			return 0;
		}
	}
	
	
	function isOneToOne(){
		return ( $this->getMaxCardinality() ===1 and $this->getMinCardinality() === 1);
	}
	
	function isOneToZeroOrOne(){
		$max = $this->getMaxCardinality();
		$min = $this->getMinCardinality();
		
		return ($min === 0 and $max === 1);
	}
	
	
		
		
	
	function getPermissions($params=array(), $table=null){
	
		// 1. Get the permissions for the particular field
		if ( isset($params['field']) ){
			if ( strpos($params['field'],'.') !== false ){
				list($junk,$fieldname) = explode('.', $params['field']);
			} else {
				$fieldname = $params['field'];
			}
			$t =& $this->getTable($fieldname);
			//$rec = $this->toRecord($t->tablename);
			
			
			$perms = $t->getPermissions(array('field'=>$fieldname, 'nobubble'=>1));
			if ( !$perms ) $perms = array();
			
			
			
			
			$rfperms = $this->_sourceTable->getPermissions(array('relationship'=>$this->getName(), 'field'=>$fieldname, 'nobubble'=>1));
			//echo "RFPerms: ";print_r($rfperms);
			if ( $rfperms ){
				foreach ($rfperms as $k=>$v){
					$perms[$k] = $v;
				}
			}
			
			unset($params['field']);
			$recPerms = $this->getPermissions($params, $t->tablename);
			
			
			foreach ($perms as $k=>$v){
				$recPerms[$k] = $v;
			}
		
			//print_r($perms);
			return $recPerms;
		} else {
			$domainTable = $this->getDomainTable();
			$destinationTables = $this->getDestinationTables();
			$isManyToMany = $this->isManyToMany();
			$targetTable = $table;
			if ( !@$targetTable ){
				if ( $isManyToMany ){
					foreach ($destinationTables as $candidateTable){
						if ( strcmp($candidateTable->tablename, $domainTable) !== 0 ){
							$targetTable = $candidateTable->tablename;
							break;
						}
					}
				}
			}
			if ( !@$targetTable ){
				$targetTable = $domainTable;
			}
			
			$parentPerms = $this->_sourceTable->getPermissions(array('relationship'=>$this->getName()));
			$targetTableObj = Dataface_Table::loadTable($targetTable);
			//$domainRecord = $this->toRecord($targetTable);
			
			
			$isDomainTable = (strcmp($domainTable, $targetTable) === 0 );
			
			
			$perms = $targetTableObj->getPermissions();
			if ( $isManyToMany ){
				if ( @$parentPerms['add new related record'] ){
					$perms['new'] = 1;
				} else if ( @$parentPerms['add existing related record'] and !$isDomainTable ){
					$perms['new'] = 1;
				} else if ( $isDomainTable and isset($parentPerms['add new related record']) and !@$parentPerms['add new related record'] ){
					$perms['new'] = 0;
				} else if ( isset($parentPerms['add existing related record']) and !@$parentPerms['add existing related record'] ){
					$perms['new'] = 0;
				}
				
				if ( @$parentPerms['delete related record'] ){
					$perms['delete'] = 1;
				} else if ( $isDomainTable and isset($parentPerms['delete related record']) and !@$parentPerms['delete related record'] ){
					$perms['delete'] = 0;
				} else if ( !$isDomainTable and @$parentPerms['remove related record'] ){
					$perms['delete'] = 1;
				} else if ( !$isDomainTable and isset($parentPerms['remove related record']) and !@$parentPerms['remove related record'] ){
					$perms['delete'] = 0;
				}
				
				if ( !$isDomainTable ){
					if ( @$parentPerms['edit related records'] ){
						$perms['edit'] = 1;
					} else if ( isset($parentPerms['edit related records']) and !@$parentPerms['edit related records'] ){
						$perms['edit'] = 0;
					}
					
					if (  @$parentPerms['link related records'] ){
						$perms['link'] = 1;
					} else if ( isset($parentPerms['link related records']) and !@$parentPerms['link related records'] ){
						$perms['link'] = 0;
					}
				}
				
				
			} else {
				if ( $parentPerms['add new related record'] ){
					$perms['new'] = 1;
				} else if ( isset($parentPerms['add new related record']) and !@$parentPerms['add new related record'] ){
					$perms['new'] = 0;
				}
				
				if ( @$parentPerms['delete related record'] ){
					$perms['delete'] = 1;
				} else if ( isset($parentPerms['delete related record']) and !@$parentPerms['delete related record'] ){
					$perms['delete'] = 0;
				} 
				if ( @$parentPerms['edit related records'] ){
					$perms['edit'] = 1;
				} else if ( isset($parentPerms['edit related records']) and !@$parentPerms['edit related records'] ){
					$perms['edit'] = 0;
				}
				if ( @$parentPerms['link related records'] ){
					$perms['link'] = 1;
				} else if ( isset($parentPerms['link related records']) and !@$parentPerms['link related records'] ){
					$perms['link'] = 0;
				}
			}
			
			
			
			if ( @$parentPerms['view related records'] ){
				$perms['view'] = 1;
			} else if ( isset($parentPerms['view related records']) and !@$parentPerms['view related records'] ){
				$perms['view'] = 0;
			}
			if ( @$parentPerms['find related records'] ){
				$perms['find'] = 1;
			} else if ( isset($parentPerms['find related records']) and !@$parentPerms['find related records'] ){
				$perms['find'] = 0;
			}
			
			
			/*
			foreach ( $this->toRecords() as $record){
				$rperms = $record->getPermissions(array());
				if ( $perms ){
					$perms = array_intersect_assoc($perms, $rperms);
					
				} else {
					$perms = $rperms;
				}
				
			}
			*/
			return $perms;
			
		}
	}
	
	
	function checkPermission($perm, $params=array()){
		$perms = $this->getPermissions($params);
		return @$perms[$perm]?1:0;
		
	}
	
			

}

/**
 * A marker class to mark values in Dataface_Relationship::getForeignKeyValues()
 * that haven't been filled in.  We use a class so that we don't get it confused
 * with another valid scalar value.
 */
class Dataface_Relationship_ForeignKey {
	var $fields = array();
	var $relationship = null;
	
	/**
	 * Constructor.
	 * @param array $labels The map of field names to labels in the relationship.
	 * @param string $label The label that this foreign key refers to.
	 */
	function Dataface_Relationship_ForeignKey(&$relationship, $labels, $label){
		$this->relationship =& $relationship;
		foreach ( $labels as $field=>$l ){
			if ( $l==$label ) $this->fields[] = $field;
		}
	}
	
	/**
	 * Returns array of field names associated with this foreign key.
	 * The field names are absolute (i.e. in the form "TableName.FieldName"
	 * @return array(string)
	 */
	function getFields(){
		return $this->fields;
	}
	
	/**
	 * Returns the name of the field in the furthest table from the source
	 * table of the relationship.  This is a valuable field to know
	 * since usually it is the furthest table that is the domain table
	 * of the relationship, and hence since a foreign key specifies 2 fields
	 * we usually want to know which field is *more important* on forms - as
	 * we don't want to give the user two fields for the same value.
	 *
	 * @return string The field in this foreign key furthest from the source
	 *	table.  This is an absolute field name (e.g. tablename.fieldname).
	 */
	function getFurthestField(){
		$d = 0;
		$f = null;
		foreach ($this->fields as $field){
			list($table) = explode('.', $field);
			$td = $this->relationship->getDistance($table);
			if ( $td > $d ){
				$d = $td;
				$f = $field;
			}
		}
		return $f;
	}
	
	
}

