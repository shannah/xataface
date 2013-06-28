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
 * File:	Dataface/RelatedRecord.php
 * Author:	Steve Hannah <shannah@sfu.ca>
 * Created:	October 2005
 *
 * Description:
 * Represents a record that is part of a relationship.
 */
/**
 * @brief Encapsulates a row in a relationship.  This may contain fields that span across 
 * multiple tables.  It implements many of the same methods as the Dataface_Record.
 *
 * @section synopsis Synopsis
 * 
 * Related records will be most often encountered as the output of Dataface_Record::getRelatedRecordObjects()
 * However you can construct your own objects and use the Dataface_IO::addRelatedRecord() or Dataface_IO::addExistingRelatedRecord()
 * methods for adding your record to the relationship.
 *
 *
 * @section examples Example Usage
 *
 * Getting related records from a Dataface_Record object.
 * @code
 * $author = df_get_record('people', array('person_id'=>10));
 * $books = $author->getRelatedRecordObjects();
 * foreach ($books as $book){
 *     echo "Book Title: ".$book->val('title')."\n";
 * }
 * @endcode
 * 
 * Getting the Dataface_Record object that encapsulates the domain
 * table of this relationship:
 * @code
 * $bookRec = $book->toRecord();
 * @endcode
 *
 */ 
class Dataface_RelatedRecord {

	/**
	 * @brief Flag to indicate whether display methods like display()
	 * 	and htmlValue should be constrained by permissions.  Default is
	 *  true.
	 * @type boolean
	 * @see Dataface_Record::secureDisplay
	 */
	var $secureDisplay = true;

	/**
	 * @private
	 */
	var $vetoSecurity;
	/**
	 * @brief The base record of the relationship.
	 * @type Dataface_Record
	 */
	var $_record;
	
	/**
	 * @brief The name of the relationship.
	 * @type string
	 */
	var $_relationshipName;
	
	/**
	 * @brief Reference to the relationship.
	 * @type Dataface_Relationship
	 */
	var $_relationship;
	
	/**
	 * @brief Array of values for this related record.
	 * @type array
	 * @private
	 */
	var $_values;
	
	/**
	 * @brief Array of meta data values for this related record.
	 * A Metadata value is a value that describes a data in a field of the 
	 * related record.  Currently there is only one meta value: __Tablename_Fieldname_length.
	 * @private 
	 */
	var $_metaDataValues;
	
	/**
	 * Maps field names to the absolute column name for that field.
	 * For example if the field profileid is located in the Profiles table, then 
	 * $this->_absoluteColumnNames['profileid'] == 'Profiles.profileid'.
	 * @type array([Field name] -> [Absolute column name])
	 *
	 * @private
	 */
	var $_absoluteColumnNames;
	
	/**
	 * @private
	 */
	var $_lockedFields=array();
	/**
	 * ???
	 * @private
	 */
	var $_records;
	/**
	 * @private
	 */
	var $_dirtyFlags=array();
	
	/**
	 * @private 
	 */
	var $cache=array();
	
	
	/**
	 * @brief Creates  new blank related record whith the specified base record and relationship name.
	 *
	 * @param Dataface_Record $record Reference to Dataface_Record object to which this record is related.
	 * @param string $relationshipName The name of the relationship of which this related record is a member.
	 * @param array $values Associative array of values for this related record.
	 */
	function Dataface_RelatedRecord($record, $relationshipName, $values=null){
		
		if ( !is_a($record, 'Dataface_Record') ){
			throw new Exception("Error in Dataface_RelatedRecord constructor.  Expected first argument to be of type 'Dataface_Record' but received '".get_class($record)."'.", E_USER_ERROR);
		}
		$this->_record =& $record;
		$this->_relationshipName = $relationshipName;
		$this->_relationship =& $record->_table->getRelationship($relationshipName);
		if ( is_array($values) ){
			$this->setValues($values);
			$this->clearFlags();
		}
	}
	
	
	/**
	 * @brief Initializes the values array for this related record.
	 * @private
	 */
	function _initValues(){
		if ( !isset( $this->_values ) ){
			$fkeys = $this->_relationship->getForeignKeyValues();
			$this->_values = array();
			$this->_absoluteColumnNames = array();
			//$cols = $this->_relationship->_schema['columns'];
			$cols = $this->_relationship->fields(true); // we will get all fields - even grafted ones.
			foreach ($cols as $col){
				list($table, $field) = explode('.', $col);
				$this->_values[$field] = null;
				if ( isset($this->_absoluteColumnNames[$field]) and
					Dataface_Table::loadTable($this->_relationship->getDomainTable())->hasField($field) ){
						$this->_absoluteColumnNames[$field] = $this->_relationship->getDomainTable().'.'.$field;
				} else {
					$this->_absoluteColumnNames[$field] = $col;
				}
				
				
				/*
				 * We want to check for locked fields. Locked fields are fields that *must* have a particular
				 * value for the relationship to remain valid.
				 */
				if ( isset( $fkeys[$table][$field]) and is_scalar($fkeys[$table][$field]) and strpos($fkeys[$table][$field],'$') ===0 ){
					$this->_lockedFields[$field] = $fkeys[$table][$field];
					$this->_values[$field] = $this->_record->parseString($fkeys[$table][$field]);
				}
			}
		}
	}
	
	//---------------------------------------------------------------------------------
	//{@
	
	/**
	 * @name Utility Methods
	 *
	 * Miscellaneous housekeeping functions.
	 *
	 */
	
	/**
	 * @brief Clears the cached values.  This is used internally to clear calculated values
	 * when something is changed.
	 * @return Dataface_RelatedRecord Self for chaining.
	 */
	function clearCache(){
		$this->cache = array();
		return $this;
	}
	
	
	/**
	 * @brief Produces a Dataface_Record object representing the portion of this related record that is stored in a 
	 * particular table.
	 * @param string $tablename The name of the table for which we wich to have a Dataface_Record object returned.
	 * @return Dataface_Record A record covering the domain table of this related record.  The domain table
	 *  is generally the table that is the target of the relationship.  (I.e. not the join table and not the source table).
	 * @since 0.6
	 */
	function &toRecord($tablename=null){
		if ( isset($this->cache[__FUNCTION__][$tablename]) ){
			return $this->cache[__FUNCTION__][$tablename];
		}
		if ( !isset($tablename) ){
			$tablename =  $this->_relationship->getDomainTable();
			
			
		} 
		
		$table =& Dataface_Table::loadTable($tablename);
		
		
		$values = array();
		
		
		$absVals = $this->getAbsoluteValues();
		$fieldnames = $this->_relationship->fields(true);
		//foreach ( array_keys($absVals) as $key ){
		foreach ( $fieldnames as $key ){
			list($currTablename, $columnName) = explode('.', $key);
			if ( ($currTablename == $tablename or $table->hasField($columnName)) and array_key_exists($key, $absVals)){
								
				$values[$columnName] = $absVals[$key];
				
			} else if ( isset($this->_relationship->_schema['aliases'][$columnName]) /*and 
				/*$table->hasField($this->_relationship->_schema['aliases'][$columnName])*/ ){
				$values[$this->_relationship->_schema['aliases'][$columnName]] = $absVals[$key];
			}
		}
		
		foreach ( $this->_values as $key=>$val ){
			if ( !isset($values[$key]) and $table->hasField($key) ) $values[$key] = $this->_values[$key];
		}
		
		$record = new Dataface_Record($tablename, $values);
		$record->secureDisplay = $this->secureDisplay;
		foreach (array_keys($values) as $key){

			if ( $this->isDirty($key) ) $record->setFlag($key);

		}
		$this->cache[__FUNCTION__][$tablename] =& $record;

		return $record;
	}
	
	
	
	/**
	 * @brief Returns an array of Dataface_Record objects that represent collectively this
	 * related record.
	 * @return array(Dataface_Record) An array of dataface record objects comprising
	 * the values in this related record.
	 *
	 *
	 */
	function toRecords(){
		$tables =& $this->_relationship->getDestinationTables();
		$out = array();
		foreach ( array_keys($tables) as $index ){
			$out[] =& $this->toRecord($tables[$index]->tablename);
		}
		return $out;
	}
	
	
	/**
	 * @brief Gets reference to the parent record (base record).
	 *
	 * The difference between this method and the toRecord() method is that this returns
	 * the parent record (or the source record of the relationship) - a record that generally
	 * is not part of this related record.   The toRecord() method returns a record that
	 * comprises a portion of this related record (or at least the columns in one of
	 * the tables that is spanned by this related record).
	 *
	 * For example:
	 * @code
	 * $person = df_get_record('people', array('person_id'=>10));
	 * $books = $person->getRelatedRecordObjects('books');
	 *
	 * $firstBook = $books[0];
	 * $parent = $firstBook->getParent();
	 * echo ($parent === $person)? "True":"False"; // outputs "True"
	 *
	 * echo $parent->_table->tablename;  // 'people'
	 * echo $books->toRecord()->_table->tablename;  // 'books'
	 *
	 * @endcode
	 * @return Dataface_Record The parent/base record of the relationship.
	 *
	 */
	function &getParent(){
		return $this->_record;
	}
	
	
	/**
	 * @brief Takes a boolean expression resembling an SQL where clause and evaluates it
	 * based on the values in this record.
	 *
	 * Example:
	 * @code
	 * $record->setValue('first_name', 'Steve');
	 * $record->setValue('last_name', 'Hannah');
	 * $record->checkCondition('$first_name=="Steve"');  // true
	 * $record->checkCondition('$last_name=="foo"');  // false
	 * @endcode
	 *
	 * @param string $condition A PHP expression that evaluates to a boolean.
	 *
	 * @return boolean True if the condition evaluates to true.
	 */
	function testCondition($condition){
		extract($this->strvals());
		return eval('return ('.$condition.');');
	}
	
	
	/**
	 * @brief Returns actions associated with this record.
	 * @param array $params An associative array of parameters to filter the actions.
	 * 			Possible keys include:
	 *				category => the name of a category of actions to return.
	 * @return array Associative array of action definitions.
	 *
	 * @see Dataface_ActionTool::getActions()
	 * @see Dataface_Table::getActions()
	 */
	function getActions($params=array()){
		$params['record'] =& $this;
		return $this->_record->_table->tablename->getActions($params);
	}
	
	
	// @}
	// END OF Utility Methods
	//-------------------------------------------------------------------------------------
	
	
	//-------------------------------------------------------------------------------------
	// @{
	/**
	 * @name Transactions
	 *
	 * Methods to deal with transactions and change tracking (so that we know what 
	 * has changed since we loaded the data from the db).
	 */
	
	/**
	 * @brief Clears the dirty flags to indicate the current state of the
	 * record is consistent with the database - or at least nothing needs to 
	 * be saved.
	 * @return Dataface_RelatedRecord Self for chaining.
	 */
	function clearFlags(){
		$this->_dirtyFlags = array();
		return $this;
	}
	
	/**
	 * @brief Sets a flag on a field to indicate that it has changed and should be saved.
	 * @param string $fieldname The name of the field to mark "dirty".
	 * @return Dataface_RelatedRecord Self for chaining.
	 */
	function setFlag($fieldname){
		$this->_dirtyFlags[$fieldname] = true;
		return $this;
	}
	
	/**
	 * @brief Clears a flag on a field to indicate that it has no changes since loading.
	 * @param string $fieldname The name of the field to mark "clean".
	 * @return Dataface_RelatedRecord Self for chaining.
	 */
	function clearFlag($fieldname){
		unset($this->_dirtyFlags[$fieldname]);
		return $this;
	}
	
	/**
	 * @brief Checks if a particular field has changed since we loaded it (i.e. it is dirty).
	 * @param string $fieldname The name of the field that we want to know about.
	 * @return boolean True if the field has changed.
	 */
	function isDirty($fieldname){
		return ( isset($this->_dirtyFlags[$fieldname]) );
	}
	
	// @}
	// End Transactions
	//---------------------------------------------------------------------------------------
	
	//----------------------------------------------------------------------------------------
	// @{
	
	/**
	 * @name Accessing Field Data
	 *
	 * Methods for getting and setting field/column values in the record.
	 */
	
	/**
	 * @brief Sets a meta data value for a field.
	 *
	 * @param string $key The string metadata name.
	 * @param mixed $value The value.
	 * @return Dataface_RelatedRecord Self for chaining.
	 *
	 */
	function setMetaDataValue($key, $value){
		if ( !isset( $this->_metaDataValues ) ) $this->_metaDataValues = array();
		$this->_metaDataValues[$key] = $value;
		return $this;
		
	}
	
	
	/**
	 * @brief Gets the length of a field's value (in bytes or characters).
	 *
	 * This will work for Blob fields as well as regular fields, as it is calculated
	 * using the MySQL LENGTH() function.
	 *
	 * @param string $fieldname The name of the field whose length we want to check.
	 * @return int The length of the field's value in bytes.
	 */
	function getLength($fieldname){
		if ( strpos($fieldname, '.') !== false ){
			list($tablename, $fieldname) = explode('.', $fieldname);
			return $this->getLength($fieldname);
		}
		$key = '__'.$fieldname.'_length';
		if ( isset( $this->_metaDataValues[$key] ) ){
			return $this->_metaDataValues[$key];
		} else {
			return strlen($this->getValueAsString($fieldname));
		}	
	}
	
	/**
	 * @brief Sets the value for a field of this related record.
	 *
	 * @param string $fieldname The name of the field to set.  This may be a relative name or an absolute column name.
	 * @param mixed $value The value to set this field to.
	 *
	 * @see Dataface_Record::setValue() 
	 */
	function setValue($fieldname, $value){
		$this->_initValues();
		
		
		if ( strpos($fieldname,'.') === false ){
			if ( strpos($fieldname, '__') === 0 ){
				return $this->setMetaDataValue($fieldname, $value);
			}
		
			if ( isset( $this->_lockedFields[$fieldname]) ) return;
			$val = $this->_record->_table->parse($this->_relationshipName.".".$fieldname, $value);
			if ( $val != @$this->_values[$fieldname] ){
				$this->_values[$fieldname] = $val;
				$this->clearCache();
				$this->setFlag($fieldname);
			}
		
		} else {
			
			list ( $table, $field )  = explode('.', $fieldname);
			if ( $table != $this->_relationship->getDomainTable() and Dataface_Table::loadTable($this->_relationship->getDomainTable())->hasField($field) ){
				return PEAR::raiseError("Cannot set duplicate value in relationship.   The field $fieldname is part of the domain table and not part of $table in this relationship.");
			}
			return $this->setValue($field, $value);
		}
			
	}
	
	/**
	 * @brief Sets multiple values at once.
	 *
	 * @param array $values Associative array of values to set.
	 * @type array([Field name] -> [Field value])
	 *
	 * @see Dataface_Record::setValues()
	 */
	function setValues($values){
		if ( !is_array($values) ){
			throw new Exception( "setValues() expects 1st parameter to be an array but received a '".get_class($values)."' ",E_USER_WARNING);
		}
		foreach ( $values as $key=>$value){
			
			$this->setValue($key, $value);
		}
	
	}
	
	
	
		
		
	/**
	 * @brief Gets the value for a field.
	 *
	 * @param string $fieldname The name of the field whose value we are retrieving.  This may be either a relative
	 *					 fieldname or an absolute column name.
	 * @return mixed The field value.
	 * @see Dataface_Record::getValue()
	 */
	function getValue($fieldname){
		$this->_initValues();
		if ( strpos($fieldname,'.') === false ){
			
			if ( !array_key_exists( $fieldname,  $this->_values ) ){
				// The key does not exist as a normal field -- so check if it is a calculated field.
				$tables = $this->_relationship->getDestinationTables();
				$dt =& Dataface_Table::loadTable($this->_relationship->getDomainTable());
				
				if ( $dt->hasField($fieldname) ){
					$tables = array($dt);
				} else {
					//echo "Domain table doesn't have $fieldname";
					throw new Exception("Domain table doesn't have field $fieldname");
				}
				foreach ( array_keys($tables) as $tkey){
					if ( $tables[$tkey]->hasField($fieldname) ){
						$tempRecord = new Dataface_Record($tables[$tkey]->tablename,$this->getValues());
						return $tempRecord->getValue($fieldname);
					}
				}
				throw new Exception("Attempt to get value for fieldname '$fieldname' that does not exist in related record of relationship '".$this->_relationshipName."'.  Acceptable values include {".implode(', ', array_keys($this->_values))."}.\n<br>", E_USER_ERROR);
			}

			return $this->_values[$fieldname];
			
		} else {
			list ( $table, $field )  = explode('.', $fieldname);
			return $this->getValue($field);
		}
	}
	
	/**
	 * @brief Gets the string value of a given field.
	 *
	 * @param string $fieldname The name of the field whose value we are retrieving.
	 *
	 * @return string The field value as a string.
	 *
	 * @see Dataface_Record::getValueAsString()
	 *
	 */
	function getValueAsString($fieldname){
		$value = $this->getValue($fieldname);

		$parent =& $this->getParent();
		$table =& $parent->_table->getTableTableForField($this->_relationshipName.'.'.$fieldname);
		if ( PEAR::isError($table) ){
			throw new Exception($table->toString(), E_USER_ERROR);
		}
		$delegate =& $table->getDelegate();
		$rel_fieldname = $fieldname; //$table->relativeFieldName($fieldname);
		if ( $delegate !== null and method_exists( $delegate, $rel_fieldname.'__toString') ){
			$value = call_user_func( array(&$delegate, $rel_fieldname.'__toString'), $value);
		} else 
		
		
		if ( is_array($value) ){
			if ( method_exists( $table, $table->getType($fieldname)."_to_string") ){
				$value = call_user_func( array( &$table, $table->getType($fieldname)."_to_string"), $value );
			} else {
				$value = implode(', ', $value);
			}
		}
		
		
		$evt = new stdClass;
		$evt->table = $table;
		$evt->field =& $table->getField($rel_fieldname);
		$evt->value = $value;
		$evt->type = $table->getType($rel_fieldname);
		$table->app->fireEvent('after_getValueAsString', $evt);
		$value = $evt->value;
		
		return $value;
	
	}
	
	/**
	 * @brief Gets a field value as HTML.
	 * @param string $fieldname The name of the field whose value we wish to retrieve.
	 * @return string The field's value as HTML.
	 *
	 * @see Dataface_Record::htmlValue()
	 */
	function htmlValue($fieldname, $params=array()){
		$value = $this->getValue($fieldname);
		$parent =& $this->getParent();
		$table =& $parent->_table->getTableTableForField($this->_relationshipName.'.'.$fieldname);
		if ( PEAR::isError($table) ){
			throw new Exception($table->toString(), E_USER_ERROR);
		}
		$record =& $this->toRecord($table->tablename);
		
		if ( $this->secureDisplay and !$this->checkPermission('view') ){
			$val = $this->display($fieldname);
			$field = $record->table()->getField($fieldname);
			if ( !@$field['passthru'] and $record->escapeOutput) $val = nl2br(df_escape($val));
			
			$del =& $record->table()->getDelegate();
			if ( $del and method_exists($del, 'no_access_link') ){
				$link = $del->no_access_link($record, array('field'=>$fieldname));
				return '<a href="'.df_escape($link).'">'.$val.'</a>';
			} else {
				return $val;
			}
			
		} else {
			$oldSecure = $record->secureDisplay;
			$record->secureDisplay = false;
			$htmlval = $record->htmlValue($fieldname, 0,0,0,$params);
			$record->secureDisplay = $oldSecure;
			return $htmlval;
		}
	}
	
	/**
	 * @brief Gets the values stored in this table as an associative array.  The values
	 * are all returned as strings.</p>
	 * @param array fields An optional array of field names to retrieve.
	 * @return array Associative array of field values where the keys are the field names
	 * and the values are their corresponding values.
	 *
	 * @see Dataface_Record::getValuesAsStrings()
	 *
	 */
	function getValuesAsStrings($fields=''){
		$keys = is_array($fields) ? $fields : array_keys($this->_values);
		$values = array();
		foreach ($keys as $key){
			$values[$key] = $this->getValueAsString($key);
		}
		return $values;
	}
	
	
	/**
	 * @alias getValuesAsStrings()
	 * @see getValuesAsStrings()
	 */
	function strvals($fields=''){
		return $this->getValuesAsStrings($fields);
	}
	
	
	/**
	 * @alias getValueAsString()
	 * @see getValueAsString()
	 */
	function strval($fieldname, $index=0){
		return $this->getValueAsString($fieldname, $index);
	}
	
	
	/**
	* @alias getValueAsString()
	* @see getValueAsString()
	*/
	function stringValue($fieldname){
		return $this->getValueAsString($fieldname);
	}
	
	/**
	 * @brief Gets the values of this related record.
	 * 
	 * @param array(string) $columns An optional array of columns to get.
	 *
	 * @param boolean $excludeNulls If this is true, then columns with 'null' values will not be included.  Defaults to 'false'
	 * @return array Associative array of values.
	 *
	 * @see Dataface_Record::getValues()
	 */
	function &getValues($columns=null, $excludeNulls=false){
		$this->_initValues();
		return $this->_values;
	}

	/**
	 * @alias getValues()
	 * @see getValues()
	 */
	function &values($fields = null){
		return $this->getValues($fields);
	}
	
	/**
	 * @alias getValues()
	 * @see getValues()
	 */
	function &vals($fields = null){
		return $this->getValues($fields);
	}
	
	
	/**
	 * Alias getValue()
	 * @see getValue()
	 */
	function val($fieldname){
		return $this->getValue($fieldname);
	}
	
	
	
	/**
	 * @brief Gets the values of this related record except that the keys of the returned associative array
	 * are absolute column names rather than relative names as are returned in getValues().
	 *
	 * @param boolean $excludeNulls If true then 'null' values are not included in returned associative array.
	 * @return array Associative array of key/value pairs.
	 */
	function getAbsoluteValues($excludeNulls=false, $includeAll=false){
		$absVals = array();
		foreach ( $this->getValues() as $key=>$value){
			if ( !isset($this->_absoluteColumnNames[$key]) ){
				// Tough call here.  In this case the most likely scenario
				// is that the value is a transient field.
				// In the past (due to a bug or not), transient fields have not been
				// included in the output of this method.  For consistency, we'll
				// keep it that way by just skipping this field.
				continue;
				$tablename =  $this->_relationship->getTable($key)->tablename;
				$this->_absoluteColumnNames[$key] = $tablename.'.'.$key;
			}
			$absVals[ $this->_absoluteColumnNames[$key] ] = $value;
		}
		return $absVals;
		
	
	}
	
	/**
	 * @brief Returns 2-Dimensional associative array of the values in this related record and in any join table.
	 * The output of this method is used to add and remove related records.
	 *
	 * @param string $sql Optional SQL query that is used for getting the related records.
	 * @return array 2-Dimensional array... 
	 * @see Dataface_Relationship::getForeignKeyValues()
	 * @see Dataface_QueryBuilder::addRelatedRecord()
	 * @see Dataface_QueryBuilder::addExistingRelatedRecord()
	 */
	function getForeignKeyValues($sql = null){
		if ( !isset($sql) ) $sql_index = 0;
		else $sql_index = $sql;
		if ( isset($this->cache[__FUNCTION__][$sql_index]) ){
			return $this->cache[__FUNCTION__][$sql_index];
		}
		$fkeys = $this->_relationship->getForeignKeyValues();
		$absVals = $this->getAbsoluteValues(true);
	
		$out = $this->_relationship->getForeignKeyValues($absVals, $sql, $this->getParent());
		$this->cache[__FUNCTION__][$sql_index] = $out;
		return $out;
		
	}
	
	/**
	 * @brief Gets a list of fields that are unconstrained (i.e. can be
	 * edited.  This is helpful when building forms for this record.
	 *
	 * @returns array($fieldname:string)
	 * @since 2.0
	 */
	function getUnconstrainedFields($sql = null){
	
		//$fkCols = $this->getForeignKeyValues($sql);
		$tmp = new Dataface_RelatedRecord($this->_record, $this->_relationshipName, array());
		$fkCols = $tmp->getForeignKeyValues($sql);
		
		if ( PEAR::isError($fkCols) ){
			throw new Exception($fkCols->getMessage(), $fkCols->getCode());
			
		}
		
		$unconstrainedFields = array();
		$cols = $this->_relationship->fields();
		
		foreach ($cols as $col){
			$field = $this->_relationship->getField($col);
			//print_r($field);
			$tablename = $field['tablename'];
			$fieldname = $field['name'];
			//echo $absFieldname;
			if ( array_key_exists($tablename, $fkCols) and array_key_exists($fieldname, $fkCols[$tablename]) ){
				// This column is already specified by the foreign key relationship so we don't need to pass
				// this information using the form.
				// Actually - this isn't entirely true.  If there is no auto-incrementing field
				// associated with this foreign key, then 
				if ( $this->_relationship->isNullForeignKey($fkCols[$tablename][$fieldname]) ){
					$furthestField = $fkCols[$tablename][$fieldname]->getFurthestField();
					if ( $furthestField != $absFieldname ){
						// We only display this field if it is the furthest field of the key
						continue;
					}
					
				} else {
					continue;
				}
			}
			
			if ( @$field['grafted'] && !@$field['transient'] ) continue;
			$unconstrainedFields[] = $col;
			
		}
		
		return $unconstrainedFields;
		
	
	}
	
	/**
	 * @brief Returns a list of fields in this related record that are constrained
	 * (i.e. cannot be changed.
	 *
	 * @returns array($fieldname:string)
	 * @since 2.0
	 */
	function getConstrainedFields($sql=null){
	
		return array_diff($this->_relationship->fields(), $this->getUnconstrainedFields($sql));
		
	}
	
	/**
	 * @brief Gets a list of the tables in this related record that are 
	 * unconstrained.  Unconstrained tables are ones that contain fields
	 * that are unconstrained.
	 * @returns array($tablename:string)
	 * @since 2.0
	 */
	function getUnconstrainedTables($sql=null){
	
		$tables = array();
		$cols = $this->getUnconstrainedFields($sql);
		foreach ($cols as $col){
			$field = $this->_relationship->getField($col);
			$tables[$field['tablename']] = 1;
		}
		return array_keys($tables);
	}
	
	
	
	
	/**
	 * @brief Returns a the value of a field in a meaningful state so that it can be displayed.
	 *
	 * This method is similar to getValueAsString() except that this goes a step further and resolves
	 * references. For example, some fields may store an integer that represents the id for a related 
	 * record in another table.  If a vocabulary is assigned to that field that defines the meanings for 
	 * the integers, then this method will return the resolved vocabulary rather than the integer itself.</p>
	 *
	 * @param string $fieldname The name of the field whose value we wish to retrieve.
	 * @return string The field value as a string.
	 *
	 * @code
	 * // Column definitions:
	 * // Table Unit_plans (id INT(11), name VARCHR(255) )
	 * // Table Lessons ( unit_id INT(11) )
	 * // Lessons.unit_id.vocabulary = "select id,name from Unit_plans"
	 * $record = new Dataface_Record('Lessons', array('unit_id'=>3));
	 * $record->getValueAsString('unit_id'); // returns 3
	 * $record->display('unit_id'); // returns "Good Unit Plan"
	 * @endcode
	 *
	 * @see Dataface_Record::display()
	 */
	 
	function display($fieldname){
		if ( isset($this->cache[__FUNCTION__][$fieldname]) ){
			return $this->cache[__FUNCTION__][$fieldname];
		}
		$parent =& $this->getParent();
		$table =& $parent->_table->getTableTableForField($this->_relationshipName.'.'.$fieldname);
		if (PEAR::isError($table) ){
			throw new Exception("Error loading table while displaying $fieldname because ".$table->getMessage(), E_USER_ERROR);
			
		}
		
		
		if ( !$table->isBlob($fieldname) ){
			$record =& $this->toRecord($table->tablename);
			if ( $this->secureDisplay and $this->checkPermission('view', array('field'=>$fieldname)) ){
				//echo "HERE";
				$oldSecure = $record->secureDisplay;
				$record->secureDisplay = false;
				$out = $record->display($fieldname);
				$record->secureDisplay = $oldSecure;
			} else {
				$out = $record->display($fieldname);
			}
			
			$this->cache[__FUNCTION__][$fieldname] = $out;
			return $out;
			
		} else {
			$keys = array_keys($table->keys());
			$qstr = '';
			foreach ($keys as $key){
				$qstr .= "&$key"."=".$this->strval($key);
			}
			$out = DATAFACE_SITE_HREF."?-action=getBlob&-table=".$table->tablename."&-field=$fieldname$qstr";
			$this->cache[__FUNCTION__][$fieldname] = $out;
			return $out;
		}
				
				
	}
	
	/**
	 * @brief Shows a short preview of field contents.  Useful for text fields when we just want to 
	 * see the first bit of the field.  This will also strip all html tags out of the content.
	 * 
	 * @param string $fieldname The name of the field to preview.
	 * @param int $index In case of a related record which index in the relationship to get the record from.
	 * @param int $maxlength The maximum length of the preview (in characters).
	 * @return string The preview of the string version.
	 * 
	 * @see Dataface_Record::preview()
	 */
	function preview($fieldname, $index=0, $maxlength=255){
		if ( isset($this->cache[__FUNCTION__][$fieldname][$index][$maxlength]) ){
			return $this->cache[__FUNCTION__][$fieldname][$index][$maxlength];
		}
		$strval = strip_tags($this->display($fieldname,$index));
		$out = substr($strval, 0, $maxlength);
		if ( strlen($strval)>$maxlength) {
			$out .= '...';
		}
		$this->cache[__FUNCTION__][$fieldname][$index][$maxlength] = $out;
		return $out;
	}
	
	/**
	 * @alias display()
	 * @deprecated
	 */
	function printValue($fieldname){
		return $this->display($fieldname);
	}
	
	/**
	 * @alias display()
	 * @deprecated
	 */
	function printval($fieldname){
		return $this->display($fieldname);
	}
	
	/**
	 * @alias display()
	 * @deprecated
	 */
	function q($fieldname){
		return $this->display($fieldname);
	}
	
	/**
	 * Displays field contents and converts html special characters to entities.
	 *
	 * @param string $fieldname The name of the field to display.
	 * @return string
	 *
	 */
	function qq($fieldname){
		$parent =& $this->getParent();
		$table =& $parent->_table->getTableTableForField($this->_relationshipName.'.'.$fieldname);
		if ( PEAR::isError($table) ){
			throw new Exception($table->toString(), E_USER_ERROR);
		}
		if ( !$table->isBlob($fieldname) ){
			return df_escape($this->q($fieldname, $index));
		} else {
			return $this->display($fieldname, $index);
		}
	}
	
	
	// @}
	// End Accessing Field Data
	//-------------------------------------------------------------------------------------------
	
	
	//-------------------------------------------------------------------------------------------
	// @{
	/** 
	 * @name Metadata
	 *
	 * Methods to get descriptive information about this record.
	 */
	 
	 /**
	 * @brief Returns the Id of this related record object.  The id is a string in a 
	 * url format to uniquely identify this related record.  The format is:
	 * tablename/relationshipname?parentkey1=val1&parentkey2=val2&relationshipname::key1=val2&relationshipname::key2=val3
	 *
	 * @return string
	 * @see Dataface_Record::getId()
	 * @see df_get_record_by_id()
	 * @see Dataface_IO::getById()
	 *
	 */
	function getId(){
		if ( isset($this->cache[__FUNCTION__]) ){
			return $this->cache[__FUNCTION__];
		}
		$parentid = $this->_record->getId();
		list($tablename, $querystr) = explode('?',$parentid);
		$id = $tablename.'/'.$this->_relationshipName.'?'.$querystr;
		$keys = array_keys($this->_relationship->keys());
		$params = array();
		foreach ($keys as $key){
			$params[] = urlencode($this->_relationshipName.'::'.$key).'='.urlencode($this->strval($key));
		}
		$out = $id.'&'.implode('&',$params);
		$this->cache[__FUNCTION__] = $out;
		return $out;
	}
	
	
	/**
	 * @brief Gets the record title.  This wraps the domain record's getTitle()
	 * method.
	 * 
	 * @return string The record's title.
	 * 
	 * @see Dataface_Record::getTitle()
	 */
	function getTitle(){
		$method = 'rel_'.$this->_relationshipName.'__getTitle';
		$del = $this->_record->table()->getDelegate();
		
		if ( isset($del) and method_exists($del, $method) ){
			return $del->$method($this);
		}
		
		
		$record =& $this->toRecord();
		if ( $this->checkPermission('view') ){
			$oldSecureDisplay = $record->secureDisplay;
			$record->secureDisplay = false;
			$out = $record->getTitle();
			$record->secureDisplay = $oldSecureDisplay;
			return $out;
		} else {
			return $record->getTitle();
		}
	}
	 
	 
	 
	// @}
	// END Metadata
	//--------------------------------------------------------------------------------------------	
	
	// @{
	/**
	 * @name Form Handling
	 */
	
	/**
	 * @brief Validates a value against a field name.  Returns true if the value is a valid 
	 * value to be stored in the field.
	 *
	 * This method will always return true.  The Delegate class can be used to override
	 * this method.  Use <fieldname>__validate(&$record, $value, &$message) to override
	 * this functionality.
	 *
	 * @param string $fieldname The name of the field that we are validating for.
	 * @param mixed $value The value that we are checking.
	 * @param array &$params An out parameter to store the validation message.
	 * @return boolean True if validation succeeds.  False otherwise.
	 * @see Dataface_Record::validate()
	 *
	 */
	function validate( $fieldname, $value, &$params){
		if ( strpos($fieldname, '.') !== false ){
			list($relname, $fieldname) = explode('.', $fieldname);
			return $this->validate($fieldname, $value, $params);
		}
		
		if ( !is_array($params) ){
			$params = array('message'=> &$params);
		}
		$table =& $this->_relationship->getTable($fieldname);
		if (PEAR::isError($table) ){
			error_log($table->toString().implode("\n", $table->getBacktrace()));
			throw new Exception("Failed to get table for field $fieldname.  See error log for details", E_USER_ERROR);
			
		} else if (!$table ){
			throw new Exception("Could not load table for field $fieldname .", E_USER_ERROR);
		}
		$field =& $table->getField($fieldname);
		
		if ( $field['widget']['type'] == 'file' and @$field['validators']['required'] and is_array($value) and $this->getLength($fieldname) == 0 and !is_uploaded_file(@$value['tmp_name'])){
			// This bit of validation operates on the upload values assuming the file was just uploaded as a form.  It assumes
			// that $value is of the form
			//// eg: array('tmp_name'=>'/path/to/uploaded/file', 'name'=>'filename.txt', 'type'=>'image/gif').
			$params['message'] = "$fieldname is a required field.";
			return false;
		}
	
		$res = $table->validate($fieldname, $value, $params);
		if ( $res ){
			$delegate =& $table->getDelegate();
			if ( $delegate !== null and method_exists($delegate, $fieldname."__validate") ){
				/*
				 *
				 * The delegate defines a custom validation method for this field.  Use it.
				 *
				 */
				$methodname = $fieldname."__validate";
				$res = $delegate->$methodname($this,$value,$params);
				//$res = call_user_func(array(&$delegate, $fieldname."__validate"), $this, $value, $params);
			}
		}
		return $res;
		
	}
	
	// @}
	// END Form Validation
	//--------------------------------------------------------------------------------------------
	
	// @{
	/**
	 * @name Saving 
	 *
	 * Methods for saving data to the database.
	 */
	
	function save($lang=null, $secure=false){
		$recs = $this->toRecords();
		
		foreach (array_keys($recs) as $i){

			$res = $recs[$i]->save($lang, $secure);
			if ( PEAR::isError($res) ) return $res;
		}
	}
	
	// @}
	// End Saving
	//----------------------------------------------------------------------------------------------
	
	//----------------------------------------------------------------------------------------------
	// @{
	/**
	 * @name Permissions
	 *
	 * Methods for checking permissions for this record.
	 */
	
	
	function getPermissions($params=array(), $table=null){
	
		// 1. Get the permissions for the particular field
		if ( isset($params['field']) ){
			if ( strpos($params['field'],'.') !== false ){
				list($junk,$fieldname) = explode('.', $params['field']);
			} else {
				$fieldname = $params['field'];
			}
			$t =& $this->_relationship->getTable($fieldname);
			$rec = $this->toRecord($t->tablename);
			
			
			$perms = $rec->getPermissions(array('field'=>$fieldname, 'nobubble'=>1));
			if ( !$perms ) $perms = array();
			
			
			
			
			$rfperms = $this->_record->getPermissions(array('relationship'=>$this->_relationshipName, 'field'=>$fieldname, 'nobubble'=>1));
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
			$domainTable = $this->_relationship->getDomainTable();
			$destinationTables = $this->_relationship->getDestinationTables();
			$isManyToMany = $this->_relationship->isManyToMany();
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
			
			$parentPerms = $this->_record->getPermissions(array('relationship'=>$this->_relationshipName));
			$domainRecord = $this->toRecord($targetTable);
			
			
			$isDomainTable = (strcmp($domainTable, $targetTable) === 0 );
			
			
			$perms = $domainRecord->getPermissions();
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
				if ( @$parentPerms['add new related record'] ){
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
	
	/**
	 * @brief Checks to see if the current user is granted the specified permission on 
	 * this record.
	 *
	 * This is essentially a wrapper for the domain record's checkPermission() method.
	 *
	 * @param string $perm The name of the permission to check.
	 * @param array $params Optional parameters.  See Dataface_Record::checkPermission() for
	 *  details of what can be included in this parameter.
	 *
	 * @return boolean True if the user is granted the permission.
	 *
	 * @see Dataface_Record::checkPermission()
	 * @see Dataface_Table::getPermissions()
	 * @see Dataface_PermissionsTool::getPermissions()
	 */
	function checkPermission($perm, $params=array()){
		$perms = $this->getPermissions($params);
		return @$perms[$perm]?1:0;
		
	}
	
	
	// @}
	// End Permissions
	//----------------------------------------------------------------------------------------------
	
	
	
	
	
	
	



}
