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
define('SQL_PARSER_WRAPPER_ERROR', 101);
require_once 'SQL/Parser.php';

class SQL_Parser_wrapper {
	
	var $_data;
	var $_tableLookup;
	var $_parser;
	
	function SQL_Parser_wrapper(&$data, $dialect='MySQL'){
		$this->_data =& $data;
		$this->_tableLookup = array();
		$this->_parser = new SQL_Parser(null, $dialect);
	}
	
	/**
	 * Extracts the tablename from a requested column name.  This will resolve aliases.
	 * @param columnname The name of a column as it appears in a select list.  Eg: a.b, Student.profileid etc..
	 */
	function getTableName($columnname){
		if ( !isset( $this->_tableLookup[$columnname] ) ){
			if ( strpos($columnname, '.') === false ) $this->_tableLookup[$columnname] = null;
			else {
				$data =& $this->_data;
				list($table, $column) = explode('.', $columnname);
				if ( isset( $data['table_aliases'] ) ){
					for ($i=0; $i<count($data['table_aliases']); $i++){
						if ( $data['table_aliases'][$i] == $table ){
							$table = $data['table_names'][$i];
							break;
						}
					}
				}
				$this->_tableLookup[$columnname] = $table;
			}
			
		}
		return $this->_tableLookup[$columnname];
	}
	
	
	/**
	 * Gets the alias for a particular tablename.  If no alias is found then the tablename itself
	 * is returned.   If the specified tablename does not exists at all then a PEAR_Error is thrown.
	 */
	function getTableAlias($tablename){
		$index = array_search($tablename, $this->_data['table_names']);
		if ( $index === false ){
			return PEAR::raiseError("Table not found in query", SQL_PARSER_WRAPPER_ERROR, E_USER_WARNING, null, "The table '$tablename' was requested in SQL_Parser_wrapper::getTableAlias() and not found in the sql query");
		 
		}
		if ( isset( $this->_data['table_aliases']) and isset( $this->_data['table_aliases'][$index]) and
				$this->_data['table_aliases'][$index] ) {
			return $this->_data['table_aliases'][$index];
		} else {
			return $tablename;
		}
	
	}
	
	
	
	/**
	 * Given a column name without its associated table or using its table's alias
	 * this will returnt he absolute column name in the form 'Tablename.Columnname'
	 */
	function resolveColumnName($columnname){
	
		$table = $this->getTableName($columnname);
		if ( $table === null ){
			return $columnname;
		} else {
			if ( strpos($columnname, ".") !== false ){
				list($junk, $col) = explode('.', $columnname);
				return $table.'.'.$col;
			} else {
				return $col;
			}
		}
			
	}
	
	
	/**
	 * Given an absolute column name of the form 'Tablename.Columnname' this will
	 * return the column name as it should appear in the select list.  This will 
	 * replace the table name with the table alias if one exists.
	 *
	 * For example, in the query "select * from Courses as c",
	 *
	 * $this->unresolveColumnName('Courses.id') === 'c.id'
	 */
	function unresolveColumnName($columnname){
		if ( strpos($columnname, '.') !== false ){
			list($table,$column) = explode('.', $columnname);
			$tablename = $this->getTableAlias($table);
			if ( PEAR::isError($tablename) ){
				/*
				 * There is no table by this name.  Check to see if it is already
				 * an alias.
				 */
				$index = array_search($table, $this->_data['table_aliases']);
				if ( $index !== false ){
					/*
					 * The tablename is an alias so we can leave it unchanged.
					 */
					$tablename = $table;
				} else {
					/*
					 * The tablename is not an alias nor is it a valid table...
					 * propogate the error upwards.
					 */
					$tablename->addUserInfo("In SQL_Parser_wrapper attempted to unresolve column '$columnname' but the table does not exist as either an alias or a column name.");
					return $tablename;
				}
			}
			return $tablename.'.'.$column;
		} else {
			$index = $this->array_ereg_search('/\.'.$columnname.'$/', $this->_data['column_names']);
			if ( $index !== false ){
				return $this->_data['column_names'][$index];
			} else {
				return $columnname;
			}
		}
	
	}
	
	/**
	 * like array_search, except the needle is a regular expression so that matches can be
	 * done for things other than equality.
	 */
	function array_ereg_search($needle, $haystack){
		foreach ( array_keys($haystack) as $index ){
			if ( preg_match($needle, $haystack[$index]) ){
				return $index;
			}
		}
		return false;
	}
	
	function unresolveWhereClauseColumns(&$clause){
		if ( !is_array($clause) ) return;
		if ( isset($clause['type']) and $clause['type'] === 'ident' ){
			$clause['value'] = $this->unresolveColumnName($clause['value']);
		}
		foreach ( array_keys($clause) as $key){
			$this->unresolveWhereClauseColumns($clause[$key]);
		}
			
	
	}
	
	
	/**
	 * Removes the specified column from the select clause.
	 * @return True if the column is removed, false otherwise.
	 */
	function removeColumn($columnname){
		$columnNames =& $this->_data['column_names'];
		$index = array_search($columnname, $columnNames);
		if ( $index !== false ){
			array_splice($columnNames, $index, 1);
			if ( isset( $this->_data['column_aliases'] ) ){
				array_splice($this->_data['column_aliases'], $index, 1);
			}
			return true;
		} else {
			return false;
		}
	}
	
	
	/**
	 * Removes all columns from the query belonging to the given tablename.
	 * returns The number of columns removed.
	 */
	function removeColumnsFromTable($tablename){
		$columnNames =& $this->_data['column_names'];
		$count = 0;
		foreach ( $columnNames as $name ){
			if ( $this->getTableName($name) == $tablename){
				$res = $this->removeColumn($name);
				if ( $res ) $count++;
			}
		}
		return $count;
	}
	
	/**
	 * Adds a column to the select list.
	 */
	function addColumn($columnname, $columnalias){
		$this->_data['column_names'][] = $columnname;
		$this->_data['column_aliases'][] = $columnalias;
	
	}
	
	/**
	 * Appends a clause to the where clause.
	 */
	function &appendClause($clause, $op='or'){
		$data =& $this->_data;
		if ( isset( $data['where_clause']) and $data['where_clause'] ) {
			
			if ( (isset( $data['where_clause']['type']) and $data['where_clause']['type'] == 'subclause') 
					or count($data['where_clause']) ===1 or 
					(isset($data['where_clause']['op']) and !in_array($data['where_clause']['op'], array('and','or')) )
				){
				$arg1 = $data['where_clause'];
			} else {
				$arg1 = array("value"=>$data['where_clause'], "type"=>"subclause");
			}
			
			if ( (isset($clause['type']) and $clause['type'] == 'subclause') or
				count($clause) === 1 or
				(isset($clause['op']) and !in_array($clause['op'], array('and','or')))
				){
				$arg2 = $clause;
			} else {
				$arg2 = array("value"=>$clause, "type"=>"subclause");
			}
			
			$data['where_clause'] = array( "arg_1"=> $arg1, 'op'=>$op, "arg_2"=> $arg2);
		} else {
			$data['where_clause'] = $clause;
		}
		return $data;
		
	}
	
	
	function &addWhereClause($whereStr, $op='and'){
		$sql = "SELECT * FROM foo WHERE $whereStr";
		$parsed = $this->_parser->parse($sql);

		
		$this->unresolveWhereClauseColumns($parsed['where_clause']);
		
		$this->appendClause($parsed['where_clause'], $op);
		return $this->_data;
	
	}
	
	
	function &setSortClause($sortStr){
	
		$sql = "SELECT * FROM foo ORDER BY $sortStr";
		$parsed = $this->_parser->parse($sql);
		
		$sort_order = array();
		foreach (array_keys($parsed['sort_order']) as $sort_col){
			$this->unresolveWhereClauseColumns($parsed['sort_order'][$sort_col]);
			$sort_order[] =& $parsed['sort_order'][$sort_col];
			
		}
		
		$this->_data['sort_order'] = $sort_order;
		return $this->_data;
	
	}
	
	
	function &addSortClause($sortStr){
	
		$sql = "SELECT * FROM foo ORDER BY $sortStr";
		$parsed = $this->_parser->parse($sql);
		
		$sort_order =& $this->_data['sort_order'];
		foreach (array_keys($parsed['sort_order']) as $sort_col){
			$this->unresolveWhereClauseColumns($parsed['sort_order'][$sort_col]);
			$sort_order[] =& $parsed['sort_order'][$sort_col];
			
		}
		
		//$this->_data['sort_order'] = $sort_order;
		return $this->_data;
	
	}
	
	
	
	
	
	function &removeWhereClause($clause){
		$null = null;
		$this->_data['where_clause'] = $this->_removeClause_rec($clause, $this->_data['where_clause']);
		if ( $this->_data['where_clause'] == null ) {
			unset($this->_data['where_clause']);
			return $null;
		}
		return $this->_data['where_clause'];
	}
	
	function removeJoinClause($clause){
		if ( is_array($this->_data['table_join_clause']) ){
			$new_clauses = array();
			$new_joins = array();
			
			foreach ( $this->_data['table_join_clause'] as $index=>$jc){
				$new_clause = $this->_removeClause_rec($clause, $jc);
				if ( $new_clause == null ) $new_clause = '';
				$new_clauses[] = $new_clause;
				if ( sizeof($new_clauses) > 1 ){
					if ( $new_clause == '' )  $new_joins[] = ',';
					else $new_joins[] = $this->_data['table_join'][$index-1];
				}
			}
			$this->_data['table_join_clause'] = $new_clauses;
			$this->_data['table_join'] = $new_joins;
		}
		
	}
	
	
	function _removeClause_rec($clause, $root){

		// Case 1: The current Node has "arg_1" and "arg_2" params
		if ( isset( $root['arg_1'] ) and isset( $root['arg_2']) ){

			if ( $clause == $root ){
				return null;
			} else {
				$root['arg_1'] = $this->_removeClause_rec($clause, $root['arg_1']);
				$root['arg_2'] = $this->_removeClause_rec($clause, $root['arg_2']);
				
				if ( $root['arg_1'] == null and $root['arg_2'] == null ) return null;
				else if ( $root['arg_1'] != null and $root['arg_2'] == null ) return $root['arg_1'];
				else if ( $root['arg_2'] != null and $root['arg_1'] == null ) return $root['arg_2'];
				else return $root;
			}
		} 
			
		// There is only a single argument... this is kind of a lame case, but it exists.
		else if ( isset( $root['arg_1'] ) ){

			$root['arg_1'] = $this->_removeClause_rec($clause, $root['arg_1']);
			return $root['arg_1'];
		}
		
		// Case 2: The current Node has a "type" param
		else if ( isset( $root['type']) and $root['type'] == 'subclause' ){

			$root['value'] =  $this->_removeClause_rec($clause, $root['value']);
			if ( $root['value'] == null ) return null;
			return $root;
		}
		
		// Case 3: Anything else... return the root unchanged
		else {

			return $root;
		}
		
	
	}
	
	function findWhereClausesWithTable($table){
		
		$clauses = array();
		if ( isset($this->_data['where_clause']) and is_array($this->_data['where_clause']) ){
			$this->_findClausesWithTable_rec($table, $this->_data['where_clause'], $clauses);
		}
		return $clauses;
		
	}
	
	function findJoinClausesWithTable($table){
		$clauses = array();
		
		if ( is_array($this->_data['table_join_clause']) ){
			foreach ( $this->_data['table_join_clause'] as $index=>$jc){
				$this->_findClausesWithTable_rec($table, $jc, $clauses);
				
			}
		}
		
		return $clauses;
	
	}
	
	
	/**
	 * Adds columns to describe a particular column.  Currently only columns
	 * to describe the size of the data in a field are added.
	 *
	 * @param $columnName The name of the column for which we want meta data.
	 * @type string
	 *
	 * @param $fullColumnNames whether to make the meta data column names such that
	 *			they specify both the table and the column of the referenced columns.
	 * @type boolean
	 *
	 * For example, suppose we have the query "select blurb from Profiles".
	 *
	 * We do the following:
	 *
	 * <code>
	 * $parser = new SQL_Parser(null,'MySQL');
	 * $sql = "select blurb from Profiles";
	 * $parsed = $parser->parse($sql);
	 * $wrapper = new SQL_Parser_wrapper($parsed);
	 * $wrapper->addMetaDataColumn('blurb');
	 * $compiler = new SQL_Compiler();
	 * $sql = $compiler->compile($parsed);
	 * echo $sql;
	 *	// should output: select blurb, LENGTH(blurb) as __blurb_length from Profiles
	 *
	 * </code>
	 * In other words it will add another column named __ColumnName_length that contains
	 * the length in bytes of the column.
	 */
	function addMetaDataColumn($columnName, $fullColumnNames=false){
		if ( strpos($columnName, '.') !== false ){
			list( $table, $shortName) = explode('.', $columnName);
		} else {
			$shortName = $columnName;
		}
		
		$aliasName = str_replace('.','_',$columnName);
		
		// at this point $alias should hold the valid name of the column for which we want info.
		$aliasColumnName = $this->unresolveColumnName($columnName);
		if ( PEAR::isError($aliasColumnName) ){
			return $aliasColumnName;
		}
		$func = array('name'=>'length', 'args'=>array(array('type'=>'ident', 'value'=>$aliasColumnName)), 'alias'=>'__'.($fullColumnNames ? $aliasName : $shortName).'_length');
		
		if ( !isset( $this->_data['set_function'] ) ){
			$this->_data['set_function'] = array();
		}
		
		/*
		 * Let's see if this function has already been added.
		 */
		$index = array_search($func, $this->_data['set_function']);
		if ( $index === false ){
			$this->_data['set_function'][] = $func;
		}
		
	
	}
	
	/**
	 * Adds meta data columns for all columns in this query.
	 * A meta data column is one that describes the column data, such as its length.
	 */
	function addMetaDataColumns($fullColumnNames = false){
		if ( !isset( $this->_data['column_names']) ) return;
		foreach ( $this->_data['column_names'] as $columnName){
			$this->addMetaDataColumn($columnName, $fullColumnNames);
		}
	}
	 
	 
	function _findClausesWithTable_rec($table, &$root, &$clauses){
		
		foreach ( array('arg_1','arg_2') as $arg){
			if ( !isset( $root[$arg]) ) continue;
			$type = (isset( $root[$arg]['type'] ) ? $root[$arg]['type'] : null);
			switch ($type){
				case 'subclause':
					$this->_findClausesWithTable_rec($table, $root[$arg]['value'], $clauses);
					break;
					
				case 'ident':
					if ( $this->getTableName($root[$arg]['value']) == $table ) array_push($clauses, $root);
					break;
					
				default:
					$this->_findClausesWithTable_rec($table, $root[$arg], $clauses);
					
					
			}
		}	
	}
	
	function findWhereClausesWithPattern($regex){
		$clauses = array();
		if ( isset($this->_data['where_clause']) and is_array($this->_data['where_clause']) ){
			$this->_findClausesWithPattern_rec($regex, $this->_data['where_clause'], $clauses);
		}
		return $clauses;
	}
	
	function findJoinClausesWithPattern($regex){
		$clauses = array();
		if ( is_array($this->_data['table_join_clause']) ){
			foreach ( $this->_data['table_join_clause'] as $jc ){
				$this->_findClausesWithPattern_rec($regex, $jc, $clauses);
			}
		}
		
		return $clauses;
	
	}
	
	function _findClausesWithPattern_rec( $regex, &$root, &$clauses){

		foreach ( array('arg_1','arg_2') as $arg){

			if ( !isset( $root[$arg]) ) continue;
			$type = (isset( $root[$arg]['type'] ) ? $root[$arg]['type'] : null);

			switch ($type){
				case 'subclause':

					$this->_findClausesWithPattern_rec($regex, $root[$arg]['value'], $clauses);
					break;
					
				case 'text_val':
				case 'int_val':
				case 'real_val':

					if ( preg_match($regex, $root[$arg]['value']) ) array_push($clauses, $root);
					break;
					
				default:

					$this->_findClausesWithPattern_rec($regex, $root[$arg], $clauses);
					
					
			}
		}
	}
	
	function removeWhereClausesWithTable($table){
	
		$clauses = $this->findWhereClausesWithTable($table);
		foreach ($clauses as $clause){
			$this->removeWhereClause($clause);
		}
	
	}
	
	
	function removeJoinClausesWithTable($table){
		$clauses = $this->findJoinClausesWithTable($table);
		foreach ($clauses as $clause){
			$this->removeJoinClause($clause);
		}
	
	}
	
	function removeWhereClausesWithPattern($regex){
		$clauses = $this->findWhereClausesWithPattern($regex);
		foreach ($clauses as $clause){
			$this->removeWhereClause($clause);
		}
	
	}
	
	function removeJoinClausesWithPattern($regex){
		$clauses = $this->findJoinClausesWithPattern($regex);
		foreach ($clauses as $clause){
			$this->removeJoinClause($clause);
		}
	}
	
	/**
	 * Removes unneccessary tables from the query.  A table is deemed unnecessary
	 * if it doesn't have any columns in the select list and it does not appear
	 * in the where clause, and it is not involved with any non-trivial joins.
	 */
	function packTables($exempt=array()){
		$selected_tables = array();
		
		// Find the tables that are selected -- these are absolutely necessary
		foreach ($this->_data['column_names'] as $column){
			$selected_tables[] = $this->getTableName($column);
			$selected_tables = array_unique($selected_tables);
		}
		
		$removed_tables = array();
		foreach ( $this->_data['table_names'] as $index=>$table_name){
			
			// If this table is in the "exempt" list, we leave it alone
			if ( in_array($table_name, $exempt) ) continue;

			
			// If this table is selected, it is needed -- so skip it
			if ( in_array($table_name, $selected_tables) ) continue;
			
			
			
			// IF  this table is involved in a nontrivial join, it is exempt
			$found = $this->findJoinClausesWithTable($table_name);
			if ( count($found) > 0 ) continue;
			
			// If this table is involved in any where clauses, then it is needed
			$found = $this->findWhereClausesWithTable( $table_name);
			if ( count($found) > 0 ) continue;
			
			
			
			// At this point, the table appears to have no purpose in the query
			$removed_tables[] = $table_name;
		}

		$table_names = array();
		$table_join = array();
		$table_join_clause = array();
		$table_aliases = array();
		$newIndex = 0;
		foreach ( $this->_data['table_names'] as $index=>$table_name){
			if ( !in_array($table_name, $removed_tables) ){
				$table_names[] = $table_name;
				if ( $index > 0 and $newIndex > 0 ){
					$table_join[] = $this->_data['table_join'][$index-1];
				}
				$table_join_clause[] = $this->_data['table_join_clause'][$index];
				$table_aliases[] = $this->_data['table_aliases'][$index];
				$newIndex++;
			}
		}
		
		$this->_data['table_names'] = $table_names;
		$this->_data['table_aliases'] = $table_aliases;
		$this->_data['table_join'] = $table_join;
		$this->_data['table_join_clause'] = $table_join_clause;
					
	}
	
	
	function fixColumns(){
		if ( PEAR::isError($this->_data) ){
			throw new Exception($this->_data->toString(), E_USER_ERROR);
		}
		for ($i=0; $i<count($this->_data['column_names']); $i++){
			$name =& $this->_data['column_names'][$i];
			if ( strpos($name, '.') === strlen($name)-1 ) $name .= '*';
			unset($name);
		}
	
	}
	
	function makeEquivalenceLabels(&$labels, &$values){
		
		$roots = array();
		if ( isset( $this->_data['where_clause'] ) and is_array( $this->_data['where_clause'] )){
			$roots[] =& $this->_data['where_clause'];
		}
		if ( isset( $this->_data['table_join_clause'] ) and is_array( $this->_data['table_join_clause']) ){
			foreach ( $this->_data['table_join_clause'] as $clause ){
				if ( is_array($clause) ){
					$roots[] = $clause;
				}
			}
		}
		foreach ($roots as $root){
			$this->_makeEquivalenceLabels($labels, $values, $root);
		}
	
	
	}
	
	
	function _makeEquivalenceLabels_rec( &$labels, &$values, &$root){
	
	
	}
	
	
	function translate_select_query(){
	
	}
	
	function getTableNames(){
		$tables = array();
		$this->getTableNames_rec($this->_data, $tables);
		return array_unique($tables);
	}
	
	function getTableNames_rec(&$root, &$tables){
		if ( isset($root['table_names']) ){
			foreach ($root['table_names'] as $table){
				$tables[] = $table;
			}
		}
		foreach ( $root as $key=>$val ){
			if ( is_array($val) ){
				$this->getTableNames_rec($val, $tables);
			}
		}
		return true;
	}
	
	
	
	

}
