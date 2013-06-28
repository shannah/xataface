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
 * File: 	Dataface/QueryTranslator.php
 * Author:	Steve Hannah <shannah@sfu.ca>
 * Created:	May 1, 2006
 * Description:	Class to convert SQL queries into multlingual queries based
 * on table naming conventions.
 */


class Dataface_QueryTranslator {
	var $app;
	var $_tableNames = array(); // [Alias -> Name]
	var $_tableAliases = array();// [Name -> Alias]
	var $_tables = array();		 // [Dataface_Table]
	
	var $_tableNames_tr = array(); // translated table names : [Alias -> Name]
	var $_tableAliases_tr = array(); // translated table aliases : [Name -> Alias]
	var $_tableAliasTranslationMap = array(); // maps translated table alias to original table alias
	
	var $_columnTranslationMap = array();
	
	var $_tableTranslations = null;
	
	var $_lang = null;
	var $_data;	// the parsed data after SQL parser has parsed the query.
	var $_data_translated; // the translated data.
	
	var $_parser;
	var $_compiler;
	var $_query;
	
	var $parentContext;
	
	
	/**
	Functions defined in this class:
	
	function translateQuery($query, $lang=null){}
	function translateSelectQuery($query, $lang=null){}
	function translateUpdateQuery($query, $lang=null){}
	function translateInsertQuery($query, $lang=null){}
	function translateIdent($ident, $lang=null){}
	function translateFunction($func, $lang=null){}
	function translateJoinClause($func, $lang=null){}
	function translateWhereClause($func, $lang=null){}
	*/
	
	function Dataface_QueryTranslator($lang=null){
		$this->app =& Dataface_Application::getInstance();
		if ( !isset($lang) ) $lang = $this->app->_conf['lang'];
		$this->_lang = $lang;
		
		//if ( !@$this->app->_conf['default_language_no_fallback'] or $this->app->_conf['default_language'] != $lang ){
			// In Dataface 0.6.10 the default behavior of the query translator was
			// changed so that default language queries are not changed.  This 
			// behavior can be reversed by adding the default_language_no_fallback=1
			// flag to the conf.ini file.
			
			import('SQL/Parser.php');
			$this->_parser = new SQL_Parser( null, 'MySQL');
			import('SQL/Compiler.php');
			$this->_compiler =& SQL_Compiler::newInstance('mysql');
			$this->_compiler->version = 2;
		//}
		
	}
	
	/**
	 * IF this translator is meant to work within the context of another translator,
	 * this this method can be used to set the parent context to the parent translator.
	 *
	 * The tables and columns of the parent context should be accessible from the 
	 * child context.
	 *
	 * @param $translator A Dataface_QueryTranslator object that is the parent of 
	 * the current object.
	 *
	 */
	function setParentContext(&$translator){
		$this->parentContext =& $translator;
	}
	
	function translateQuery($query, $lang=null, $compile = true){
		//echo "Translating query: ".$query;
		if ( !is_array($query) ){
				
			
			$query = trim($query);
			$this->_query = $query;
			$command = strtolower(substr($query, 0, strpos($query, ' ')));
		} else {
			$command = $query['command'];
		}
		
		// Reset all of the private variables that are used for temp storage
		// while parsing.
		unset($this->_tableNames);
		unset($this->_tableAliases);
		unset($this->_tableAliasTranslationMap);
		unset($this->_columnTranslationMap);
		unset($this->_data);
		unset($this->_talbeNames_tr);
		unset($this->_tableAliases_tr);
		$this->_tableNames = array();
		$this->_tableAliases = array();
		$this->_tableAliasTranslationMap = array();
		$this->_columnTranslationMap = array();
		if ( isset($lang) ) $this->_lang = $lang;
		
		// In Dataface 0.6.10 we changed the default behavior of the translations so 
		// that the translation table is not used for the default language.
		// This can be reversed by adding the default_language_no_fallback=1 flag
		// to the conf.ini file.
		// If the flag has not been added, then queries in the default language
		// will be returned unchanged.
		if ( !@$this->app->_conf['default_language_no_fallback'] and ($this->_lang == $this->app->_conf['default_language']) ){

			return array($query);
		} 
		if ( is_array($query) ){
			$this->_data = $query;
		} else {
			$this->_data = $this->_parser->parse($query);
		}
		if ( PEAR::isError($this->_data) ) return $this->_data;
		$this->_tableNames_tr = array();
		$this->_tableAliases_tr = array();

		
		
		
		
		switch ($command){
			case 'select':	return $this->translateSelectQuery($query,$lang, $compile);
			case 'update':	return $this->translateUpdateQuery($query,$lang, $compile);
			case 'insert':	return $this->translateInsertQuery($query,$lang, $compile);
			case 'delete': 	return $this->translateDeleteQuery($query,$lang, $compile);
			default:		return PEAR::raiseError(
									df_translate(
										'scripts.Dataface.QueryTranslator.translateQuery.ERROR_INVALID_QUERY',
										"Invalid query attempted to be translated.  Expected select, update, or insert query but received: ".$query,
										array('query'=>$query)
										)
									);
		}
	}
	
	
	
	function translateSelectQuery($query, $lang = null, $compile = true){
		//echo "Translating $query";
		// Make a short ref to the parsed data structure of the query
		$d =& $this->_data;
		// fill in the tableNames:
		$numTables = count($d['tables']);	// number of tables in the query
		for ($i=0; $i<$numTables; $i++){
			if ( $d['tables'][$i]['type'] == 'ident' ){
				$tname = $d['tables'][$i]['value'];
				$talias = $d['tables'][$i]['alias'];
				if ( !$talias ) $talias = $tname;
			} else {
				// This table is a subselect - we won't keep this in the tables array
				$translator = new Dataface_QueryTranslator($this->_lang);
				$d['tables'][$i]['value'] = $translator->translateQuery($d['tables'][$i]['value'], null, false);
				continue;
			}
			
			$this->_tableNames[$talias] = $tname;
			$this->_tableAliases[$tname] = $talias;
			if (!isset( $this->_tables[$tname] ) ){
				$this->_tables[$tname] =& Dataface_Table::loadTable($tname);
			}
		}
		
		if ( isset( $this->parentContext) ){	
			foreach ( array_keys($this->parentContext->_tables) as $tablename ){
				if ( !isset($this->_tables[$tablename]) ){
					$this->_tables[$tablename] =& $this->parentContext->_tables[$tablename];
					$this->_tableAliases[$tablename] = $this->parentContext->_tablesAliases[$tablename];
					$this->_tableNames[$this->_tableAliases[$tablename]] = $tablename;
				}
			}
		}
		
		
		
		// Prepare the translated data array:
		$this->_data_translated = $d;
			// Placeholder for the data structure for the translated query
		$this->_data_translated['columns'] = array();
		if ( !isset( $d['table_join'] ) ) $this->_data_translated['table_join'] = array();
		foreach ($this->_data_translated['table_join'] as $k=>$v){
			if ( $v == "," ){
				// For some reason the comma causes problems when we add joins at the end
				$this->_data_translated['table_join'][$k] = "inner join";
			}
		}
			// If there were no joins, we initialize it.
			
		// Translate the column names
		if ( isset( $d['columns'] ) and is_array($d['columns']) ) {
			$numCols = count($d['columns']);
			for ($i=0; $i<$numCols; $i++){
				$currColumn  = $d['columns'][$i];
				//$this->_data_translated['columns'][] =& $currColumn;
				$this->translateColumn($currColumn);
				unset($currColumn);
			}
			
		}
		
		
		// Translate the where clause
		if ( isset($d['where_clause']) ){
			$this->translateClause($this->_data_translated['where_clause']);
		}
		
		// Translate the join clause
		if ( isset($d['table_join_clause']) ){
			$numClauses = count($d['table_join_clause']);
			for ($i=0; $i<$numClauses; $i++){
				
				$this->translateClause($this->_data_translated['table_join_clause'][$i]);
			}
		}
		
		// Translate order by clause
		if ( isset($d['sort_order']) ){
			$numClauses = count($d['sort_order']);
			for ( $i=0; $i<$numClauses;$i++){
				$this->translateClause($this->_data_translated['sort_order'][$i]);
			}
		}
		
		if ( $compile ){
			$out = array($this->_compiler->compile( $this->_data_translated));
			//echo "Translated as: ";print_r($out);
			return $out;
		} else {
			return $this->_data_translated;
		}
		
		
	}
	

	
	/**
	 * Translates a column.
	 * @param $col The name of the column as it appears in the select list.
	 * @param $column_alias The column's alias.
	 * @param $updateTables If true (default) this will cause the tables list to be
	 *        updated.
	 * @param $updateColumns If true (default) this will cause the columnTranslationMap
	 *			to be updated.
	 */
	function translateColumn(&$col, $column_alias=null, $update=true){
		if ( is_array($col) ){
			// For backwards compatability it is possible for $col to simply
			// be the name of a column.  Fron now on, however, $col will be 
			// an associative array of column information of the form:
			// 'type' => ident|func
			// 'table' => ..
			// 'value' => ..
			// 'alias' => ..
			$columnInfo =& $col;
			unset($col);
			switch ( $columnInfo['type'] ){
				case 'glob':
				case 'ident':
					$col = $columnInfo['value'];
					if ( $columnInfo['table'] ) $col = $columnInfo['table'].'.'.$col;
					$column_alias = $columnInfo['alias'];
					break;
				
				case 'func':
					//print_r($columnInfo['value']);
					$this->translateFunction($columnInfo['value'], $update);
					if ( $update ){
						$this->_data_translated['columns'][] =& $columnInfo;
						return true;
					} else {
						// This shouldn't happen.
					}
					break;
			}
		}
		if ( $update && preg_match('/\.{0,1}\*$/', $col) ){
			// this is a glob column.
			$expandedGlob = $this->expandGlob($col);
			foreach ( $expandedGlob as $globCol){
				$currColumnTable = null;
				$currColumnName = null;
				$currColumnArr = explode('.', $globCol);
				if ( count($currColumnArr) > 1){
					$currColumnName = $currColumnArr[1];
					$currColumnTable = $currColumnArr[0];
				} else {
					$currColumnName = $currColumnArr[0];
					$currColumnTable = '';
				}
				$currColumn = array('type'=>'ident','table'=>$currColumnTable,'value'=>$currColumnName, 'alias'=>'');
				//$this->_data_translated['columns'][] =& $currColumn;
				$this->translateColumn($currColumn);
				unset($currColumn);
			}
			return true;
		} 
		
		$originalColumnName = $col;
		$tablename = null;
		if ( strpos($col, '.') !== false ) {
			// this column has table portion
			list($alias,$col) = explode('.', $col);
			
			if ( isset($this->_tableNames[$alias]) ){
				$tablename = $this->_tableNames[$alias];
			} 
		} else {
			$alias = '';
			foreach ( array_keys($this->_tables) as $tableKey){
				if ( isset($this->_tables[$tableKey]->_fields[$col]) ){
					$tablename = $this->_tables[$tableKey]->tablename;
					break;
				}
			}

		}

		if ( !isset($tablename) ){
			
			// This column is not associated with a table.  This means
			// that it could be referencing a subselect - which would 
			// already have been translated, so we don't need to do any
			// translations - or it could be that the column doesn't exist.
			// In either case we just leave it alone and MySQL can return
			// an error in the latter case.
			if ( !$update ){
				
				return array('column_names'=>array(($alias ? $alias.'.' : '').$col), 'column_aliases'=>array($column_alias));
			} else {
				if ( isset( $columnInfo ) ){
					$this->_data_translated['columns'][] = $columnInfo;
				} 
				return false;
			}
		} else if ( isset( $this->_tableAliases[$tablename]) and !$alias  ){
			$alias = $this->_tableAliases[$tablename];
		}
		
		$table =& $this->_tables[$tablename];
		$translation = $table->getTranslation($this->_lang);
		if ( !isset($translation) or !in_array($col, $translation) or in_array($col, array_keys($table->keys()) ) ){
			// there is no translation for this field
			// so we do nothing here
			if ( $update ){
				//$this->_data_translated['column_names'][] = ($alias ? $alias : $tablename).'.'. $col;
				//$this->_data_translated['column_aliases'][] = $column_alias;
				if ( isset($columnInfo) ){
					if ( !$columnInfo['table'] ) $columnInfo['table'] = $tablename;
					if ( $alias ) $columnInfo['table'] = $alias;
					$this->_data_translated['columns'][] = $columnInfo;
				}
				return false;
			} else {
				return array('column_names'=>array(($alias ? $alias : $tablename).'.'.$col), 'column_aliases'=>array($column_alias));
			}
		} else {
			// the table has a translation
		
			// the column has a translation
			$old_alias = $alias;
			if ( !$alias ){
				$alias = $tablename.'__'.$this->_lang;
				
			} else {
				$alias = $alias.'__'.$this->_lang;
			}
			if ( !isset($this->_tableNames_tr[$alias] ) ){
				// If the translation table hasn't been recorded yet, let's do that now
				$this->_tableNames_tr[$alias] = $tablename.'_'.$this->_lang;
			}
			if ( !isset($this->_tableAliases_tr[$tablename.'_'.$this->_lang]) ){
				$this->_tableAliases_tr[$tablename.'_'.$this->_lang] = $tablename;
			}
			if ( !isset($this->_tableAliasTranslationMap[$alias]) ){
				$this->_tableAliasTranslationMap[$alias] = $old_alias;
			}
			
			// Now we add the join clause for the translation table (if necessary)
			if ( !in_array( $alias, $this->_data_translated['table_aliases'] ) ){
				$this->_data_translated['table_names'][] = $tablename.'_'.$this->_lang;
				$this->_data_translated['table_aliases'][] = $alias;
				$this->_data_translated['tables'][] = array('type'=>'ident','value'=>$tablename.'_'.$this->_lang, 'alias'=>$alias);
				$this->_data_translated['table_join'][] = 'left join';
				$join_clause = null;
				foreach ( array_keys($this->_tables[$tablename]->keys()) as $keyName){
					$temp = array(
						'arg_1'=>array(
							'value'=> ( $old_alias ? $old_alias : $tablename).'.'.$keyName,
							'type'=> 'ident'
							),
						'op'=>'=',
						'arg_2'=>array(
							'value'=> $alias.'.'.$keyName,
							'type'=>'ident'
							)
						);
					if ( !isset($join_clause) ){
						$join_clause =& $temp;
					} else {
						$temp2 =& $join_clause;
						unset($join_clause);
						$join_clause = array(
							'arg_1'=>&$temp2,
							'op'=>'and',
							'arg_2'=>&$temp
							);
					}
					
					unset($temp);
					unset($temp2);
					
						
				}
				$this->_data_translated['table_join_clause'][] = $join_clause;
			}
			
			
			
			
			// Now adjust the column name
		
			$func_struct = 
				array(
					'name'=>'ifnull',
					'args'=>array(
						array(
							'type'=>'ident',
							'value'=>$alias.'.'.$col
							),
						array(
							'type'=>'ident',
							'value'=>($old_alias ? $old_alias : $tablename).'.'.$col
							),
						),
					'alias'=>($column_alias ? $column_alias : $col)
					);
			if ( $update && !isset( $this->_columnTranslationMap[$originalColumnName] ) ) {
				$this->_columnTranslationMap[$originalColumnName] = $func_struct['alias'];
			}
			if ( $update){
				if ( isset($columnInfo) ){
					$columnInfo['type'] = 'func';
					$columnInfo['table'] = '';
					$columnInfo['value'] = $func_struct;
					$columnInfo['alias'] = $func_struct['alias'];
					$this->_data_translated['columns'][] = $columnInfo;
					return true;
					//print_r($columnInfo);
				} else {
					$this->_data_translated['set_function'][] = $func_struct;
					return true;
				}
			} else {
				return array('set_function'=>array($func_struct));
			}	
			
		}
		return true;
	}
	
	/**
	 * Translates a function in place.
	 * Alternatively can  be made to return a copy of the function with the modifications
	 * made, without modifying the original function, by setting the second parameter to 
	 * false.
	 */
	function translateFunction(&$func, $update=true){
		if ( !isset( $func['args'] ) ) return false;
		if ( !$update ) $new_func = $func;
		foreach ( array_keys($func['args']) as $key){
			$arg =& $func['args'][$key];
			switch( $arg['type'] ){
				case 'ident':
						$new_value = $this->translateColumn($func['args'][$key]['value'], null, false);
						
						if ( isset($new_value['set_function']) ) {
							$new_arg = array('type'=>'function', 'value'=>$new_value['set_function'][0]);
						} else if ( isset( $new_value['column_names'] ) ){
							$new_arg = array('type'=>'ident', 'value'=>$new_value['column_names'][0]);
						}
						if ( !$update ){
							$new_func['args'][$key] = $new_arg;
						} else {
							$func['args'][$key] = $new_arg;
						}
						break;
				
				case 'function':
						if ( $update ) {
							$this->translateFunction($func['args'][$key]['value'], true);
						} else {
							$this->translateFunction($new_func['args'][$key]['value'], true);
						}
						break;
						
			}
			unset($arg);
			
		}
		
		if ( !$update ){
			
			return $new_func;
		} else {
			return true;
		}
			
	}
	
	/**
	 * Translates a clause such as a where clause or a join clause in place.
	 * Does not return anything.. only modifies the clause in place.
	 */
	function translateClause(&$clause){
		if ( isset($clause['type']) ) $this->translateUnaryClause($clause);
		else $this->translateBinaryClause($clause);
				
	}
	
	function translateBinaryClause(&$clause){
		if ( !is_array($clause) ) return;
		foreach ( array('arg_1','arg_2') as $arg ){
			if ( !isset($clause[$arg]) ) continue;
			$this->translateUnaryClause($clause[$arg]);
		}
	}
	
	function translateUnaryClause(&$clause){
		if ( !isset($clause['type']) ){
			$this->translateClause($clause);
		} else {
			switch( $clause['type'] ){
				case 'ident':
					$new_value = $this->translateColumn($clause['value'], null, false);
					if ( is_array($new_value) and isset($new_value['set_function']) ) {
						// the translation is a function
						$clause['type'] = 'function';
						$clause['value'] = $new_value['set_function'][0];
					} else {
						$clause['value'] = $new_value['column_names'][0];
					}
					
					break;
				
				case 'function':
					$this->translateFunction($clause['value']);
					break;
					
				case 'subclause':
					$this->translateClause($clause['value']);
					break;
					
				case 'command':
				case 'subquery':
					$translator = new Dataface_QueryTranslator($this->_lang);
					$translator->setParentContext($this);
					$clause['value'] = $translator->translateQuery($clause['value'], $this->_lang, false);
					
					break;
				
				//case 'match':
				//	if ( isset($clause['value']) and is_array($clause['value']) ){
				//		$numClauses = count($clause['value']);
				//		for ($i=0; $i<$numClauses; $i++){
				//			
				//			
				//			
				//		}
				//	}
				//	break;
					
					
					
			}
		}
		
		
	}
	

	
	/**
	 * Expands a glob into its component columns.  e.g., '*' is transformed to an array of all columns in the table.
	 * Also accepts input of the form <Tablename>.* and <alias>.*.
	 */
	function expandGlob($glob){
		$numTables = count($this->_tableNames);
		if ( strpos($glob,'.') !== false ){
			// This is a glob of only a single table or alias
			list($alias, $glob) = explode('.', $glob);
			if ( isset( $this->_tableNames[$alias]) ){
				$out = array_keys($this->_tables[ $this->_tableNames[$alias] ]->fields() );
				$out2=array();
				foreach ($out as $col){
					$out2[] = $alias.'.'.$col;
				}
				return $out2;
			} else {
				throw new Exception(
					df_translate(
						'scripts.Dataface.QueryTranslator.expandGlob.ERROR_NONEXISTENT_TABLE',
						"Attempt to expand glob for non-existent table '$alias'",
						array('table'=>$alias)
						), E_USER_ERROR);
			}
		} else {
			$fields = array();
			foreach ( array_keys($this->_tableNames) as $alias ){
				$newfields = array_keys($this->_tables[ $this->_tableNames[ $alias ] ]->fields());
				foreach ( $newfields as $newfield ){
					//if ( $numTables > 1 ){
						$fields[] = $alias.'.'.$newfield;
					//} else {
					//	$fields[] = $newfield;
					//}
				}
			}
			return $fields;
		}
			
	}
	
	/**
	 * Translates the given update query into a multilingual update.  This will cause 
	 * columns with a translation to be updated in the translated table only (not the base table)
	 * and key columns are updated in both the translation table and the base table.
	 * This will return an array of SQL queries to perform the update.
	 *
	 */
	function translateUpdateQuery($query){
		/*
		 * This method translates an update query to be multilingual.
		 * It tries to be non-obtrusive in that only column names in the 
		 * update list are converted.  The where clause is left alone.
		 * As a consequence, this does not support multi-table updates.
		 */
		
		$d =& $this->_data;
		$tableName = $d['table_names'][0];
		if ( count($d['table_names']) > 1 ){
			return PEAR::raiseError(
				df_translate(
					'scripts.Dataface.QueryTranslator.translateUpdateQuery.ERROR_MULTI_TABLE_UPDATE',
					'Failed to translate update query because the translator does not support multiple-table update syntax.'
					), E_USER_ERROR);
		}
		
		$table = Dataface_Table::loadTable($tableName);
		$keys = array_keys($table->keys());
			// Array of the names of fields the form the primary key of this table.
		
		$translation = $table->getTranslation($this->_lang);
			// Array of column names that have a translation in this language.
		
		
		if ( !isset( $translation ) ) $translation = array();
			// If there is no translation we will just set the translation to 
			// and empty array.  Even if there is no translation for the current
			// language, we should still go through and parse the query in case 
			// a key is being changed and other translations will need to be modified
			// to maintaing foreign key integrity.
			
		// We initialize the data structure to store the update to the translation
		// table.
		$this->_data_translated = $d;	// to store update query to translation table
		$this->_data_translated['column_names'] = array();
		$this->_data_translated['values'] = array();
		$this->_data_translated['table_names'] = array($tableName.($translation?'_'.$this->_lang:''));
		
		// Initialize the data structure to store the update to the original table
		// after translated columns are removed.
		$new_data = $d;	// to store update query to base table
		$new_data['column_names'] = array();
		$new_data['values'] = array();
		
		// Initialize the data structure to store the update to the other translation
		// tables in case the keys are changed.  This update is just to synchronize 
		// the keys.
		$keyChange_data = $d;
		$keyChange_data['column_names'] = array();
		$keyChange_data['values'] = array();
			// if keys are updated, then they should be updated in all translation tables
			// to remain consistent.
		
		$numCols = count($d['column_names']);
		$translationRequired = false;
		$originalRequired = false;
		$keysChanged = false;
		
		for ($i=0; $i<$numCols; $i++ ){
			$col = $d['column_names'][$i];
			$value = $d['values'][$i];
			if ( in_array($col, $keys) ){
				$originalRequired = true;
				$keysChanged = true;
				$this->_data_translated['column_names'][] = $col;
				$this->_data_translated['values'][] = $value;
				$new_data['column_names'][] = $col;
				$new_data['values'][] = $value;
				$keyChange_data['column_names'][] = $col;
				$keyChange_data['values'][] = $value;
			} else if ( in_array($col, $translation)  ){
				$translationRequired = true;
				$this->_data_translated['column_names'][] = $col;
				$this->_data_translated['values'][] = $value;
			} else {
				$originalRequired = true;
				$new_data['column_names'][] = $col;
				$new_data['values'][] = $value;
			}
		}
		
		if (!$translationRequired and !$keysChanged){
			return array($query);
		} else {
			$queryKeys = $this->extractQuery($d);
			if ( $translationRequired ){
				$out = array('insert ignore into `'.$tableName.'_'.$this->_lang.'` (`'.implode('`,`', array_keys($queryKeys)).'`) values (\''.implode('\',\'', array_values($queryKeys)).'\')');
			} else {
				$out = array();
			}
			if ( $originalRequired ) $out[] = $this->_compiler->compile($new_data);
			$out[] =  $this->_compiler->compile($this->_data_translated);
			
			if ( $keysChanged ){
				$translations = array_keys($table->getTranslations());
				foreach ( $translations as $tr ){
					if ( $tr == $this->_lang ) continue;
					$keyChange_data['table_names'] = array($tableName.'_'.$tr);
					$out[] = $this->_compiler->compile($keyChange_data);
				}
			}

			return $out;
		}
		
		
			
		
		
	}
	
	/**
	 * Extracts a dataface query array from a data structure for an SQL query.  The 
	 * dataface query array is of the form [Column] -> [Value].  
	 */
	function extractQuery(&$data){
		$w =& $data['where_clause'];
		$out = array();
		$this->extractQuery_rec($w, $out);
		return $out;
	}
	
	function extractQuery_rec($clause, &$out){
		if ( !is_array($clause)) return;
		
		if ( isset($clause['arg_1']	) ){
			switch ($clause['arg_1']['type']){
				case 'subclause':
					$this->extractQuery_rec($clause['arg_1'], $out);
					break;
				
				case 'ident':
					if ( in_array($clause['arg_2']['type'], array('int_val','real_val','text_val','null') )  and $clause['op'] == '='){
						$out[$clause['arg_1']['value']] = $clause['arg_2']['value'];
					}
					break;
					
			}
		}
		
		
	}
	
	/**
	 * Translates a given insert query into a multilingual insert.  I.e. it places
	 * translated field values into the translation table.  All values are placed
	 * into the base table, but the translated values are duplicated in the translation
	 * table.  This is the preferred functionality because new records should be 
	 * available to all languages by default - when a translation is made, it will
	 * override the default.
	 *
	 */
	function translateInsertQuery($query){

		/*
		 * This method translates an update query to be multilingual.
		 * It tries to be non-obtrusive in that only column names in the 
		 * update list are converted.  The where clause is left alone.
		 * As a consequence, this does not support multi-table updates.
		 */
		$d =& $this->_data;
		$tableName = $d['table_names'][0];
		if ( count($d['table_names']) > 1 ){
			return PEAR::raiseError(
				df_translate(
					'scripts.Dataface.QueryTranslator.translateUpdateQuery.ERROR_MULTI_TABLE_UPDATE',
					'Failed to translate update query because the translator does not support multiple-table update syntax.'
					), E_USER_ERROR);
		}
		
		$table = Dataface_Table::loadTable($tableName, null, false, true);
		if ( PEAR::isError($table) ){
			return array($query);
		}
		
		$translation = $table->getTranslation($this->_lang);
			// Array of column names that have a translation in this language.
		
		if ( !isset( $translation ) ) return array($query);
			// there are no translations for this table, so we just return the query.
			
		// We initialize the data structure to store the update to the translation
		// table.
		$this->_data_translated = $d;	// to store update query to translation table
		$this->_data_translated['column_names'] = array();
		$this->_data_translated['values'] = array();
		$this->_data_translated['table_names'] = array($tableName.'_'.$this->_lang);
		
		// Initialize the data structure to store the update to the original table
		// after translated columns are removed.
		$new_data = $d;	// to store update query to base table
		$new_data['column_names'] = array();
		$new_data['values'] = array();
		
		
		
		$numCols = count($d['column_names']);
		$translationRequired = false;
		$originalRequired = true;	// for inserts the original is always required!
		
		if ( ($aif = $table->getAutoIncrementField())  ){
			$new_data['column_names'][] = $this->_data_translated['column_names'][] = $aif;
			$new_data['values'][] = $this->_data_translated['values'][] = array('type'=>'text_val', 'value'=>'%%%%%__MYSQL_INSERT_ID__%%%%%');
			
		}
		
		for ($i=0; $i<$numCols; $i++ ){
			$col = $d['column_names'][$i];
			$value = $d['values'][$i];
			if ( in_array($col, $translation)){
				$translationRequired = true;
				$this->_data_translated['column_names'][] = $col;
				$this->_data_translated['values'][] = $value;
			} 
				
			$new_data['column_names'][] = $col;
			$new_data['values'][] = $value;
			
		}

		if (!$translationRequired){
			$out = array($query);
		} else {
			$out = array();
			
			if ( $originalRequired ) $out[] = $this->_compiler->compile($new_data);
			$out[] =  $this->_compiler->compile($this->_data_translated);
			
		}

		return $out;
		
	}
	
	/**
	 * Translates a delete query.  Essentially, this just needs to delete all of 
	 * the translations for the deleted record.  This only works if the delete command
	 * is deleting a single record using the primary keys in the where clause.
	 */
	function translateDeleteQuery($query){
		$d =& $this->_data;
		$out = array($this->_compiler->compile($d));
		$table = Dataface_Table::loadTable($d['table_names'][0]);
		foreach ( array_keys($table->getTranslations()) as $lang){
			$d['table_names'][0] = $table->tablename.'_'.$lang;
			$out[] = $this->_compiler->compile($d);
		}
		return $out;
	}
}
