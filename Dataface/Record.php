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
 

import( 'Dataface/Table.php');
import( 'Dataface/RelatedRecord.php');
import('Dataface/LinkTool.php');


/**
 * Set the number of related records that are to be loaded in each block.
 * Related Records are loaded in blocks so that records that have large amounts
 * of related records don't clog the system.
 */
define('DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE', 30);


/**
 * @ingroup databaseAbstractionAPI
 */

/**
 * @brief Represents a single record from a table.
 *
 * @section synopsis Synopsis
 *
 * The Dataface_Record class a core class as it encapsulates a single row of a table.  Most
 * interactions with the database will go through this class in some shape or form.  It 
 * provides access to configuration, triggers, delegate classes, permissions, and just about
 * every other facet of the framework.
 *
 * @section examples Example Usage
 *
 * Generally this class is used to view and edit records in the database.  It is frequently used 
 * delegate classes for responding to events.
 *
 * Sample loading a record and reading some values:
 * @code
 * $record = df_get_record('people', array('person_id'=>10));
 * if ( !$record ){
 *     echo "No record found with person_id=10.";
 * } else {
 *     echo sprintf("We have loaded a record with First Name: %s, Last Name: %s.",
 *         $record->val('first_name'),
 *         $record->val('last_name')
 *     );
 * }
 * @endcode
 *
 * Sample editing some values and saving them:
 * @code
 * $record->setValue('first_name', 'Steve');
 * $res = $record->save();
 * if ( PEAR::isError($res) ){
 *     echo "An error occurred: ".$res->getMessage();
 * } else {
 *     echo "Sucessfully saved record.";
 * }
 * @endcode
 * 
 * Sample creating a new record and saving it to the database.
 * @code
 * $record = new Dataface_Record('people', array());
 * $record->setValues(array(
 *     'first_name'=> 'Steve',
 *     'last_name'=> 'Hannah'
 * ));
 * $res = $record->save();
 * if ( !PEAR::isError($res) ){
 *     echo "Record saved successfully with person_id ".$record->val('person_id');
 * } else {
 *     echo "Error occurred trying to save record: ".$res->getMessage();
 * }
 * @endcode
 *
 *
 * @see Dataface_Table : A class that represents a database table.
 * @see Dataface_Relationship: A class that represents a relationship between tables.
 * @see Dataface_RelatedRecord : A class the represents a record in a relationship.
 *
 */
class Dataface_Record {

	/**
	 * @ingroup Permissions
	 * @brief Flag to indicate whether display and htmlValue should be subject to permissions
	 *
	 * @var boolean Whether calls to display and htmlValue should be subject
	 *				to permissions.  By default this is true.
	 */
	var $secureDisplay = true;


	/**
	 * A unique ID for this record (for making object comparisons in PHP 4)
	 * sinc the comparison operators don't work properly until PHP 5.
	 * @private 
	 */
	var $_id;
	
	/**
	 * @brief Generates the next unique record ID for the system.
	 *
	 * @return int The next id
	 * @private
	 */
	static function _nextId(){
		static $id = 0;
		return $id++;
	}

	/**
	 * This will hold a reference to the parent record if this table is 
	 * an extension of another table.  A table can be "extended" or "inherit"
	 * from another table by defining the __isa__ parameter to the fields.ini
	 * file. 
	 * @see getParentRecord()
	 * @private
	 */
	var $_parentRecord;
	
	
	/**
	 * Handles the property change listeners for this record.
	 *
	 * @private
	 */
	var $propertyChangeListeners=array();
	
	/**
	 * Associative array of values of this record.  [Column Names] -> [Column Values]
	 *
	 * @private
	 */
	var $_values;
	
	
	/**
	 * Reference to the Dataface_Table object that owns this Record.
	 *
	 * @type Dataface_Table
	 */
	var $_table;
	
	
	/**
	 * The name of the table that owns this record.
	 *
	 * @type string
	 */
	var $_tablename;
	
	
	/**
	 * Associative array of values of related records to this record.
	 *			[Relationship name] ->[
	 *				[0 .. num records] -> [
	 *					[Column Names] -> [Column Values]
	 *				]
	 *			]
	 * @private
	 */
	var $_relatedValues = array();
	
	/**
	 * A boolean array indicating whether or not a block of related records is loaded.
	 * The block size is defined in the DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE constant.
	 * @private
	 */
	var $_relatedValuesLoaded = array();
	
	/**
	 * @private
	 */
	var $_numRelatedRecords = array();
	/**
	 * @private
	 */
	var $_lastRelatedRecordStart = 0;
	
	/**
	 * @private
	 */
	var $_lastRelatedRecordLimit = 30;
	
	/**
	 * @private
	 */
	var $_relationshipRanges;
	
	
	/**
	 * @private 
	 * The title of the record
	 */
	var $_title;
	
	
	/**
	 * Associative array of snapshot values.  It is possible to take a snapshot of a record
	 * so that it can be compared when updating (to only update those fields that have been
	 * changed.
	 * @private
	 */
	var $_oldValues;
	
	/**
	 * @private
	 */
	var $_transientValues=array();
	
	
	/**
	 * @brief Flag indicating if we are using meta data fields.  Meta data fields (if this flag is set)
	 * are signalled by '__' preceeding the field name.
	 */
	var $useMetaData = true;
	
	
	/**
	 * Flags to indicate if a field has been changed.
	 * Associative array. [Field name] -> [boolean]
	 *
	 * @private
	 */
	var $_dirtyFlags = array();
	
	/**
	 * Flags to indicate if values of a related field have been changed.
	 * Associative array. [Relationship name] -> [  [Field name] -> [Field value]  ]
	 * @private
	 */
	var $_relatedDirtyFlags = array();
	
	/**
	 * @private
	 */
	var $_isLoaded = array();
	
	/**
	 * @private
	 */
	var $_metaDataValues=array();
	
	
	/**
	 * @brief Indicator to say whether blob columns should be loaded.  This is useful for the blob
	 * columns of related records.
	 * @type boolean
	 */
	var $loadBlobs = false;
	
	/**
	 * Stores metadata for related records. Index keys are identical to relatedValues array.
	 * @private
	 */
	var $_relatedMetaValues=array();
	
	
	/**
	 * @brief Reference to the delegate class object.
	 * @private
	 */
	var $_delegate = null;
	
	/**
	 * @private 
	 */
	var $cache=array();
	
	/**
	 * This flag is used to veto security settings of changes.
	 * If this flag is set when setValue() is called, then it records this 
	 * as a veto change, which means that the normal security checks won't
	 * be in effect.  All changes made to a record inside the beforeSave()
	 * beforeInsert() and beforeUpdate() triggers are performed in 
	 * veto mode so that the changes will be exempt from security checks.
	 *
	 * @type boolean
	 * @private
	 */
	var $vetoSecurity = false;
	
	/**
	 * This array tracks any fields that should be exempt from security
	 * checks.
	 *
	 * @private
	 */
	var $vetoFields = array();
	
	
	/**
	 * @brief This is a multi-purpose pouch that allows triggers to attach data to a record
	 * and retrieve it later.
	 */
	var $pouch = array();
	
	
	/**
	 * @brief The language code of this record.  This is automatically set to the language
	 * of content that was loaded when the record was loaded.
	 *
	 * @type string
	 */
	var $lang = null;
	
	
	var $escapeOutput = true;
	
	//--------------------------------------------------------------------------------
	// @{
	/**
	 * @name Initialization
	 */
	
	/**
	 * @param $tablename The name of the table that owns this record.
	 * @param $values An associative array of values to populate the record.
	 */
	function Dataface_Record($tablename, $values=null){
		$app =& Dataface_Application::getInstance();
		$this->_id = Dataface_Record::_nextId();
		$this->_tablename = $tablename;
		$this->_table =& Dataface_Table::loadTable($tablename);
		$this->_delegate =& $this->_table->getDelegate();
		$this->lang = $app->_conf['lang'];
		
		if ( $values !== null ){
			if ( is_a($values, 'StdClass') ){
				$values = get_object_vars($values);
			}
			if ( is_array($values) ){
				foreach ($values as $k=>$v){
					$this->setValue($k, $v);
				}
				$this->setSnapshot();
			}
			//$this->setValues($values);
			
		}
	}
	
	// @}
	// END Initialization 
	//---------------------------------------------------------------------------------
	
	/**
	 * This was necessary to fix a memory leak with records that have a parent record.
	 *  Thanks to http://bugs.php.net/bug.php?id=33595 for the details of this
	 * workaround.
	 *
	 * When looping through and discarding records, it is a good idea to 
	 * explicitly call __destruct.
	 *
	 */
	function __destruct(){
		unset($this->propertyChangeListeners);
		unset($this->cache);
		unset($this->pouch);
		unset($this->vetoFields);
		unset($this->_delegate);
		unset($this->_metaDataValues);
		unset($this->_transientValues);
		unset($this->_oldValues);
	
		if ( isset($this->_parentRecord) ){
			$this->_parentRecord->__destruct();
			unset($this->_parentRecord);
		}
		
		
	}
	
	//------------------------------------------------------------------------------------
	// @{
	/**
	 * @name Utility Methods
	 */
	
	/**
	 * @brief Calls a delegate class method with no parameters (if it exists) and returns 
	 *  the result.
	 *
	 * @param string $function The name of the method to try to call.
	 * @param mixed $fallback The value to return if the method could not be found
	 * @return mixed The result of the delegate method.
	 *
	 * @since 0.8
	 *
	 * @see http://xataface.com/documentation/tutorial/getting_started/delegate_classes
	 * @see http://www.xataface.com/wiki/Delegate_class_methods
	 */
	function callDelegateFunction($function, $fallback=null){
		$del =& $this->_table->getDelegate();
		$parent =& $this->getParentRecord();
		if ( isset($del) && method_exists($del, $function) ){
			return $del->$function($this);
			//return call_user_func(array(&$del, $function), $this);
		} else if ( isset($parent) ){
		
			return $parent->callDelegateFunction($function, $fallback);
		} else {
			return $fallback;
		}
	
	}
	
	

	
	
	/**
	 * @brief Returns actions associated with this record.
	 *
	 * @param array $params An associative array of parameters for the actions to be retrieved.
	 *			Possible keys include:
	 *				category => the name of the category for the actions.
	 *
	 * @return array Associative array of action definitions.
	 * @since 0.6
	 * 
	 * @see Dataface_Table::getActions()
	 *
	 */
	function getActions($params=array()){
		$params['record'] =& $this;
		$actions = $this->_table->getActions($params);
		
		$parent =& $this->getParentRecord();
		if ( isset($parent) ){
			$actions = array_merge_recursive_unique($parent->getActions($params), $actions);
		}
		return $actions;
	}
	
	
	/**
	 * @brief Returns a reference to the Dataface_Table object.
	 * @return Dataface_Table
	 * @since 0.5
	 */
	function &table(){
		return $this->_table;
	}
	
	
	/**
	 * @brief Clears all of the various caches in this record.  
	 *
	 * This is called generally when values are changed.
	 *
	 * @return void
	 * @since 0.5
	 */
	function clearCache(){
		
		unset($this->cache);
		
		$this->cache=array();
		
		$this->_relatedValuesLoaded = array();
		
		$this->_relatedValues = array();
		$this->_relatedMetaValues = array();
		
		
	}
	
	
	
	
		
	/**
	 * @brief Parses the string to replace column name variables with the corresponding
	 *	column value.
	 *
	 * @param string $str The string to be parsed.
	 * @return string The parsed string
	 * @since 0.5
	 * 
	 * <p>Parses a string, resolving any variables to the values in this record.  A variable is denoted by
	 * a dollar sign preceeding the name of a field in the table.  This method replaces the variable
	 * with its corresponding value from this record.</p>
	 *
	 * <p>Examples of variables include:
	 *		<ul>
	 *			<li>$id : This would be replaced by the value in the 'id' field of the record.</li>
	 *			<li>$address.city : This would be replaced by the value in the 'city' field in the 'address' relationship.</li>
	 *		</ul>
	 *	</p>
	 * <p>Related records can be parsed, but currently indexes are not supported.  For example, if there are records in a relationship
	 *	there is no way to specify the third record in a variable.  Variables refering to related fields are automatically replaced
	 *	with the value found in the first related record.</p>
	 *
	 * 
	 */
	function parseString( $str){
		if ( !is_string($str) ) return $str;
		$matches = array();
		$blackString = $str;
		while ( preg_match( '/(?<!\\\)\$([0-9a-zA-Z\._\-]+)/', $blackString, $matches ) ){
			if ( $this->_table->hasField($matches[1]) ){
				$replacement = $this->strval($matches[1]);
				
				
			} else {
				$replacement = $matches[1];
			}
			$str = preg_replace( '/(?<!\\\)\$'.$matches[1].'/', $replacement, $str);
			$blackString = preg_replace( '/(?<!\\\)\$'.$matches[1].'/', "", $blackString);
			
		}
		return $str;
	}
	
	// @}
	// END Utility Methods
	//-------------------------------------------------------------------------------
	
	
	//---------------------------------------------------------------------------------
	//{@
	
	/**
	 * @name Relationships
	 *
	 * Methods for working with related records.
	 *
	 */
	
	
	
	/**
	 * @brief Gets the range of records that should be loaded for related records.
	 * @param string $relationshipName The name of the relationship
	 * @return array A 2-element array with integers [lower, upper] marking the lower and 
	 *	upper bounds.
	 * @since 0.5
	 *
	 * @see http://xataface.com/documentation/tutorial/getting_started/relationships
	 * @see http://www.xataface.com/wiki/relationships.ini_file
	 *
	 */
	function getRelationshipRange($relationshipName){
		if ( isset( $this->_relationshipRanges[$relationshipName] ) ){
			return $this->_relationshipRanges[$relationshipName];
		} else {
			return $this->_table->getRelationshipRange($relationshipName);
		}
	}
	
	/**
	 * @brief Sets the range that should be included for a given relationship.
	 *
	 * @param string $relationshipName The name of the relationship.
	 * @param int $lower The start index to be returned.
	 * @param int $upper The upper index to be returned.
	 * @return void
	 * @since 0.5
	 *
	 * @see http://xataface.com/documentation/tutorial/getting_started/relationships
	 * @see http://www.xataface.com/wiki/relationships.ini_file
	 */
	function setRelationshipRange($relationshipName, $lower, $upper){
		if ( !isset( $this->_relationshipRanges ) ) $this->_relationshipRanges = array();
		$this->_relationshipRanges[$relationshipName] = array($lower, $upper);
		
	}

	
	
	
	
	
	
	
	/**
	 * @brief Indicates whether a paricular related record has been loaded yet.
	 * @param string $relname The relationship name.
	 * @param int $index The integer index of the record that we are checking to see if it is loaded.
	 * @param string $where 
	 *  (optional) A string SQL clause to be used to filter the results.
	 * @param mixed $sort 
	 *  (optional) A comma-delimited list of columns to sort on.
	 * @return boolean True of the specified related record has already been loaded.
	 *
	 * @private
	 * @since 0.5
	 */
	function _relatedRecordLoaded($relname, $index, $where=0, $sort=0){
	
		$blockNumber = floor($index / DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE);
		return ( isset( $this->_relatedValuesLoaded[$relname][$where][$sort][$blockNumber] ) and $this->_relatedValuesLoaded[$relname][$where][$sort][$blockNumber] );
	}
	
	
	/**
	 * @brief Converts an index range to a block range.  
	 * Related records are loaded in blocks where 
	 * a single block contains a number of records (as defined in the DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE
	 * constant.
	 *
	 * @param int $lower The lower index range.
	 * @param int $upper The upper index range
	 * @return array 2-element array with lower and upper bounds of blocks.
	 * @since 0.5
	 * @private 
	 */
	function _translateRangeToBlocks($lower, $upper){
	
		$lowerBlock = floor($lower / DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE);
		$upperBlock = floor($upper / DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE);
		return array(intval($lowerBlock), intval($upperBlock));
	}
	
	/**
	 * @brief Boolean indicator to see if a block has already been loaded.
	 *
	 * @param string $relname The name of the relationship whose record blocks we are inquiring about.
	 * @param int $block The block number to check.
	 * @param string $where
	 *  (Optional) Where clause to use to filter the related records.
	 * @param string $sort
	 *  (Optional) Comma-delimited list of columns on which to sort the related records.
	 * @return boolean True if the specified block has already been loaded.
	 *
	 * @since 0.5
	 * @private 
	 *
	 */
	function _relatedRecordBlockLoaded($relname, $block, $where=0, $sort=0){
		return ( isset( $this->_relatedValuesLoaded[$relname][$where][$sort][$block] ) and $this->_relatedValuesLoaded[$relname][$where][$sort][$block] );
	}
	
	/**
	 * @brief Loads a block of related records into memory.  
	 * Records are loaded in as blocks so that we don't load too much more than 
	 * neccessary (imaging a relationship with a million related records.  We couldn't 
	 * possibly want to load more than a few  hundred at a time.
	 *
	 * @param string $relname The name of the relationship from which to return records.
	 * @param int $block The block number to load.  (From 0 to ??)
	 * @param string $where
	 *	(optional) Where clause that can be used to filter the related records.
	 * @param string $sort
	 * 	(optional) Comma-delimited list of columns on which to sort the related records.
	 *
	 * @return boolean value indicating whether the loading worked.
	 *
	 * @since 0.5
	 * @private
	 */
	function _loadRelatedRecordBlock($relname, $block, $where=0, $sort=0){
		if ( $this->_relatedRecordBlockLoaded($relname, $block, $where, $sort) ) return true;

		$relationship =& $this->_table->getRelationship($relname);
		if ( !is_object($relationship) ){
			throw new Exception(
				df_translate(
					'scripts.Dataface.Record._loadRelatedRecordBlock.ERROR_GETTING_RELATIONSHIP',
					"Error getting relationship '$relname'.  The value returned by getRelationship() was '$relationship'.",
					array('relationship'=>$relname, 'retval'=>$relationship)
					), E_USER_ERROR);
		}
		
		$start = $block * DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE;
		$limit = DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE;
		
		if ( $start >= $this->numRelatedRecords($relname, $where) ){


			return false;
		}
		
		//$sql = $this->parseString($relationship->_schema['sql']);
		$sql = $this->parseString($relationship->getSQL($this->loadBlobs, $where, $sort));

		// TODO We need to change this so that it is compatible with relationships that already specify a limit.
		$sql .= " LIMIT ".addslashes($start).",".addslashes($limit);
		

		//$res = mysql_query($sql, $this->_table->db);
		$db =& Dataface_DB::getInstance();
		$res = $db->query($sql, $this->_table->db, null, true);
		if ( !$res and !is_array($res) ){
			
			throw new Exception( mysql_error($this->_table->db).
				df_translate(
					'scripts.Dataface.Record._loadRelatedRecordBlock.ERROR_LOADING_RELATED_RECORDS',
					"Error loading related records for relationship '$relname' in table '".$this->_table->tablename."'.  There was a problem performing the sql query '$sql'. The Mysql error returned was '".mysql_error($this->_table->db),
					array('relationship'=>$relname,'table'=>$this->_table->tablename, 'mysql_error'=>mysql_error($this->_table->db), 'sql'=>$sql)
					)
				,E_USER_ERROR);
		}
		$index = $start;
		//while ( $row = mysql_fetch_assoc($res) ){
		foreach ($res as $row){
			$record_row = array();
			$meta_row = array();
			foreach ($row as $key=>$value){
				
				if (  strpos($key, '__') === 0  ){
					$meta_row[$key] = $value;
				} else {
					$record_row[$key] = $this->_table->parse($relname.'.'.$key, $value);
				}
				unset($value);
			}
			$this->_relatedValues[$relname][$where][$sort][$index++] =& $record_row;
			$this->_relatedMetaValues[$relname][$where][$sort][$index-1] =& $meta_row;
			unset($record_row);
			unset($meta_row);
			
		}

		$this->_relatedValuesLoaded[$relname][$where][$sort][$block] = true;
		
		return true;
	}
	
	
	/**
	 * @brief Returns the total number of related records for a given relationship.
	 *
	 * @section Examples
	 * @subsection default_usage Default Usage
	 * @code
	 * if ( $record->numRelatedRecords('books') > 0 ){
	 *     echo "There are ".$record->numRelatedRecords('books')." books.";
	 * } else {
	 *     echo "There are no books.";
	 * }
	 * @endcode
	 *
	 * @subsection where_clause Using 'Where' Clause
	 * 
	 * The following example counts the number of books in the relationship that 
	 *	where published in 1986.
	 * @code
	 * $numBooksIn1986 = $record->numRelatedRecords('books', "year='1986'");
	 * @endcode
	 *
	 * @param string $relname The relationship name.
	 * @param mixed $where
	 * (optional) String where clause that can be used to filter the records.
	 *
	 * @return Integer number of records in this relationship.
	 *
	 * @since 0.5
	 *
	 * @see http://xataface.com/documentation/tutorial/getting_started/relationships
	 * @see http://www.xataface.com/wiki/relationships.ini_file
	 */
	function numRelatedRecords($relname, $where=0){
	
		if ( !isset( $this->_numRelatedRecords[$relname][$where]) ){
			$relationship =& $this->_table->getRelationship($relname);
			
			//if ( $where !== 0 ){
				$sql = $this->parseString($relationship->getSQL($this->loadBlobs, $where));
			//} else {
			//	$sql = $this->parseString($relationship->_schema['sql']);
			//}
			$sql = stristr($sql, ' FROM ');
			$sql = "SELECT COUNT(*) as num".$sql;
			//$dbObj = 
			//$res = mysql_query($sql, $this->_table->db);
			//$res = mysql_query($sql, $this->_table->db);
			$db =& Dataface_DB::getInstance();
			$res = $db->query($sql, $this->_table->db, null, true);
			if ( !$res and !is_array($res) ){
			//if ( !$res ){
				throw new Exception(
					df_translate(
						'scripts.Dataface.Record.numRelatedRecords.ERROR_CALCULATING_NUM_RELATED_RECORDS',
						"Error calculating the number of related records there are for the relationship '$relname' in the table '".$this->_table->tablename."'.  There was a problem performing the sql query '$sql'.  The MYSQL error returned was '".mysql_error($this->_table->db)."'.\n<br>",
						array('relationship'=>$relname,'table'=>$this->_table->tablename,'mysql_error'=>mysql_error($this->_table->db),'sql'=>$sql)
						), E_USER_ERROR);
			}
			
			$this->_numRelatedRecords[$relname][$where] = $res[0]['num'];
		}
		return $this->_numRelatedRecords[$relname][$where];
		
	}
	
	/**
	 * @brief Returns an array of all of the records returned by a specified relation.
	 * 
	 * Each record is an associative array where the values are in raw format as returned by the database.
	 *
	 * @section Examples
	 * @subsection default_usage Default Usage
	 * @code
	 * $relatedRecords = $record->getRelatedRecords('books');
	 * foreach ($relatedRecords as $book){
	 *     echo 'Name: '.$book['name'].' Subject: '.$book['subject']."\n";
	 * }
	 * @endcode
	 *
	 * @subsection by_range Getting first 5 records in Relationship
	 * @code
	 * $relatedRecords = $record->getRelatedRecords('books', 0, 5);
	 * foreach ($relatedRecords as $book){
	 *     echo 'Name: '.$book['name'].' Subject: '.$book['subject']."\n";
	 * }
	 * @endcode
	 *
	 * @subsection filtering_results Getting Filtering Results
	 * @code
	 * $relatedRecords = $record->getRelatedRecords('books', 0, 5, "name LIKE 'Tale of %'");
	 * foreach ($relatedRecords as $book){
	 *     echo 'Name: '.$book['name'].' Subject: '.$book['subject']."\n";
	 * }
	 * @endcode
	 *
	 * The preceding example should return the first 5 books whose name starts with "Tale of "
	 *
	 * @subsection sorting_results Sorting the Results
	 * The following example shows how to retrieve the first 5 related records when sorting
	 * on book name then author.
	 *
	 * @code
	 * $relatedRecords = $record->getRelatedRecords('books', 0, 5, null, "name asc, author asc");
	 * foreach ($relatedRecords as $book){
	 *     echo 'Name: '.$book['name'].' Subject: '.$book['subject']."\n";
	 * }
	 * @endcode
	 *
	 * @param string $relname The name of the relationship whose records we are retrieveing.
	 * @param boolean $multipleRows 
	 *	(optional) If true, this will return an array of records.  If it is false it only returns the first record.
	 * @param integer $start The start position from this relationship to return records from.
	 * @param integer $limit The number of records to return
	 * @param string $where A short where clause to filter the results.
	 * @param string $sort A comma-delimited list of fields to sort on. e.g. 'Name asc, Weight desc'.
	 * @return array A 2-dimensional array of records.  Each record is represented by an associative array.
	 *
	 * @see http://www.xataface.com/wiki/relationships.ini_file
	 * @see http://xataface.com/documentation/tutorial/getting_started/relationships 
	 * @see docs/examples/getRelatedRecords.example.php Getting a list of courses that a student record is enrolled in.
	 * @see docs/examples/getRelatedRecords.example2.php A more complex example using sorting and filtering of results.
	 * @see Dataface_Record::getRelatedRecordObjects()
	 * @see Dataface_Record::getRelationshipIterator()
	 * @see Dataface_Record::numRelatedRecords() 
	 *
	 * @since 0.5
	 */
	function &getRelatedRecords( $relname, $multipleRows=true , $start = null, $limit=null, $where=0, $sort=0){
		if ( !is_bool($multipleRows) and intval($multipleRows) > 0  ){
			/*
			 * Give caller the option of omitting the "MultipleRows" option.
			 */
			$sort = $where;
			$where = $limit;
			$limit = $start;
			$start = $multipleRows;
			$multipleRows = true;
		} else if ( $multipleRows === 'all'){
			// the second argument is the 'all' specifier - meaning that all records should be returned.
			$where = ($start === null ? 0:$start);
			$sort = $limit;
			$start = 0;
			$limit = $this->numRelatedRecords($relname, $where) + 1;
			$multipleRows = true;

		
		
		} else if ( is_string($multipleRows) and intval($multipleRows) === 0 and $multipleRows !== "0"){

			if ( is_string($start) and intval($start) === 0 and $start !== "0" ){
				// $start actually contains the sort parameter
				$sort = $start;
				$start = $limit;
				$limit = $where;
			} else {
				$sort = $where;
			
			}
			$where = $multipleRows;
			$multipleRows = 'all';
			return $this->getRelatedRecords($relname, $multipleRows, $where, $sort);
		}
			
		
		if ( $where === null ) $where = 0;
		if ( $sort === null ) $sort = 0;
		list($defaultStart, $defaultEnd) = $this->getRelationshipRange($relname);
		if ( $start === null){
			//$start = $this->_lastRelatedRecordStart;
			$start = $defaultStart;
		} else {
			$this->_lastRelatedRecordStart = $start;
		}
		
		if ( $limit === null ){
			//$limit = $this->_lastRelatedRecordLimit;
			$limit = $defaultEnd-$defaultStart;
		} else {
			$this->_lastRelatedRecordLimit = $limit;
		}
		
		
		$range = $this->_translateRangeToBlocks($start,$start+$limit-1);
		if ( $where === null ) $where = 0;
		if ( $sort === null ) $sort = 0;
		if ( !$sort ){
			$relationship =& $this->_table->getRelationship($relname);
			$order_column = $relationship->getOrderColumn();
			if ( !PEAR::isError($order_column) and $order_column){
				$sort = $order_column;
			}
		}
		// [0]->startblock as int , [1]->endblock as int
		for ( $i=$range[0]; $i<=$range[1]; $i++){
			$res = $this->_loadRelatedRecordBlock($relname, $i, $where, $sort);
			
			// If the above returned false, that means that we have reached the end of the result set.
			if (!$res ) break;
		}
		
		
		if ( $multipleRows === true ){
		
		
			$out = array();
			for ( $i=$start; $i<$start+$limit; $i++){
				if ( !isset( $this->_relatedValues[$relname][$where][$sort][$i] ) ) continue;
				$out[$i] =& $this->_relatedValues[$relname][$where][$sort][$i];
			}
			//return $this->_relatedValues[$relname][$where][$sort];
			return $out;
		} else if (is_array($multipleRows) ){
			throw new Exception("Unsupported feature: using array query for multiple rows in getRelatedRecords", E_USER_ERROR);
			// we are searching using a query
			foreach ( array_keys($this->_relatedValues[$relname][$where][$sort]) as $rowIndex ){
				$row =& $this->_relatedValues[$relname][$where][$sort][$rowIndex];
				$match = true;
				foreach ( $multipleRows as $key=>$value ){
					if ( strpos($key,'.')!== false ){
						// if the query specifies an absolute path, just parse it
						list($dummy, $key) = explode('.', $key);
						if ( trim($dummy) != trim($relname) ){
							// make sure that this query is for this relationship
							continue;
						}
					}
					$fullpath = $relname.'.'.$key;
					$nvalue = $this->_table->normalize($fullpath, $value);
					if ( $nvalue != $this->_table->normalize($fullpath, $rowIndex) ){
						// see if this column matches
						$match = false;
					}
				}
				if ( $match ) return $row;
				unset($row);
			}
			
		
		} else {
			if (@count($this->_relatedValues[$relname][$where][$sort])>0){
				if ( is_int( $start ) ){
					return $this->_relatedValues[$relname][$where][$sort][$start];
				} else {
					return reset($this->_relatedValues[$relname][$where][$sort]);
				}
				//$first =& array_shift($this->_relatedValues[$relname]);
				//array_unshift($this->_relatedValues[$relname], $first);
				//return $first;
			} else {
				$null = null;
				return $null;
			}
		}
				
	}
	
	/**
	 * @brief Returns the "children" of this record. 
	 * 
	 * <p>A record's children can be defined by two means:
	 * <ol><li>Adding &quot;meta:class = children&quot; to a relationship
	 * in the <em>relationships.ini</em> file to indicate the the records in 
	 * that relationship are considered &quot;child&quot; records of the parent
	 * record.</li>
	 * <li>Defining a method named <em>getChildren()</em> in the delegate class
	 * that returns an array of Dataface_Record objects (not Dataface_RelatedRecord
	 * objects) which are deemed to be the children of a particular record.</li>
	 * </ol></p>
	 *
	 * @section Examples
	 * @code
	 * //Getting sub pages of a webpage record.
	 * $subpages = $page->getChildren();
	 * foreach ($subpages as $pg){
	 *     echo $pg->val('path');
	 * }
	 * @endcode
	 * Note that the above example relies on the fact that either the 
	 * getChildren method has been defined in the delegate class or 
	 * a relationship has the meta:class=children designator for this
	 * table.
	 *
	 * @param int $start
	 *	(optional) The start index from which to return children.
	 * @param int $limit
	 *  (optional) The upper limit on the nubmer of records to be returned.
	 * @return array Array of Dataface_Record objects that are the children of
	 * 		this record.
	 *
	 * @since 0.8
	 *
	 * @see http://xataface.com/wiki/getChildren
	 * @see http://xataface.com/documentation/tutorial/getting_started/relationships
	 * @see http://www.xataface.com/wiki/relationships.ini_file
	 * @see getChild()
	 * @see http://xataface.com/wiki/list%3Atype
	 * @see http://xataface.com/wiki/meta%3Aclass
	 */
	function getChildren($start=null, $limit=null){
		$delegate =& $this->_table->getDelegate();
		if ( isset($delegate) and method_exists($delegate, 'getChildren')){
			$children =& $delegate->getChildren($this);
			return $children;
		} else if ( ( $rel =& $this->_table->getChildrenRelationship() ) !== null ){
			$it = $this->getRelationshipIterator($rel->getName(), $start, $limit);
			$out = array();
			while ( $it->hasNext() ){
				$child = $it->next();
				$out[] = $child->toRecord();
			}
			return $out;
		} else {
			
			return null;
		}
	}
	
	
	/**
	 * @brief Gets a particular child at the specified index.  
	 * 
	 * <p>If only one child is needed, then this method is preferred to 
	 * getChildren() because it avoids loading the unneeded records from the 
	 * database.</p>
	 *
	 * @param int $index The zero-based index of the child to retrieve.
	 * @return Dataface_Record The child record at that index (or null if none exists).
	 *
	 * @see getChildren()
	 * @see http://xataface.com/wiki/getChildren
	 * @see http://xataface.com/documentation/tutorial/getting_started/relationships
	 * @see http://www.xataface.com/wiki/relationships.ini_file
	 *
	 * @since 0.8
	 */
	function getChild($index){
		$children =& $this->getChildren($index,1);
		if ( !isset($children) || count($children) == 0 ) return null;
		return $children[0];
	}
	
	/**
	 * @brief Returns the "parent" record of this record.
	 *
	 * @attention
	 *		DO NOT CONFUSE THIS WITH getParentRecord().
	 *		getParentRecord() returns this record's parent in terms of the 
	 *		table heirarchy.  This method obtains the parent record in terms of the content 
	 *		heirarchy.
	 *
	 * <p>A record's parent can be defined in two ways:
	 * <ol>
	 * <li>Adding &quot;meta:class = parent&quot; to a relationship in the 
	 *     <em>relationships.ini</em> file to indicate that the first record
	 *     in the relationship is the &quot;parent&quot; record of the source record.</li>
	 * <li>Defining a method named <em>getParent()</em> in the delegate class that
	 *     returns a Dataface_Record object (not a Dataface_RelatedRecord object)
	 *     that is deemed to be the record's parent.</li>
	 * </ol>
	 * </p>
	 *
	 * <h3>Changes for 2.0</h3>
	 * <p>This method has been modified for Xataface 2.0 to return the parent 
	 * record provided by the new -portal-context parameter.  This is a last
	 * option that is only used if the other standard options for defining a record
	 * parent have not been implemented.</p>
	 *
	 * @param Dataface_Record The parent record of this record.
	 * @return Dataface_Record The parent record of this record (or null if none is defined).
	 *
	 * @see getChildren()
	 * @see http://xataface.com/wiki/getChildren
	 * @see http://xataface.com/documentation/tutorial/getting_started/relationships
	 * @see http://www.xataface.com/wiki/relationships.ini_file
	 *
	 * @since 0.8
	 */
	function &getParent(){
		$delegate =& $this->_table->getDelegate();
		if ( isset($delegate) and method_exists($delegate, 'getParent')){
			$parent =  $delegate->getParent($this);
			return $parent;
		} else if ( ( $rel =& $this->_table->getParentRelationship() ) !== null ){
			$it = $this->getRelationshipIterator($rel->getName());
			
			if ( $it->hasNext() ){
				$parent = $it->next();
				$out = $parent->toRecord();
				return $out;
			}
			return null;
		} else {
			$app = Dataface_Application::getInstance();
			$contextRecord = $app->getRecordContext($this->getId());
			if ( $contextRecord ){
				$parent = $contextRecord->getParent();
				return $parent;
			}
			return null;
		}
	}
	
	
	
	
	
	
	/**
	 * @brief Obtains an iterator to iterate through the related records for a specified
	 * relationship.
	 *
	 * @param string $relationshipName The name of the relationship.
	 * @param int $start The start index (zero based). Use null for default.
	 * @param int $limit The number of records to return. Use null for default.
	 * @param string $where A string where clause to limit the results.  e.g. 'Name="Fred" and Size="large"'.
	 *				Use 0 for default.
	 * @param string $sort A comma-delimited list of columns to sort on with optional 'asc' or 'desc'
	 *			indicators.  e.g. 'FirstName, SortID desc, LastName asc'.
	 * @return Dataface_RelationshipIterator
	 *
	 * @see getRelatedRecords()
	 * @see getRelatedRecordObjects()
	 * @see http://xataface.com/documentation/tutorial/getting_started/relationships
	 * @see http://www.xataface.com/wiki/relationships.ini_file
	 *
	 * @since 0.5
	 */
	function getRelationshipIterator($relationshipName, $start=null, $limit=null,$where=0, $sort=0){
		if ( !$sort ){
			$relationship =& $this->_table->getRelationship($relationshipName);
			if ( PEAR::isError($relationship) ){
				throw new Exception("Relationship $relationship could not be found: ".$relationship->getMessage());
			}
			$order_column = $relationship->getOrderColumn();
			if ( !PEAR::isError($order_column) and $order_column){
				$sort = $order_column;
			}
		}
		return new Dataface_RelationshipIterator($this, $relationshipName, $start, $limit, $where, $sort);
	}
	
	/**
	 * @brief Gets an array of Dataface_RelatedRecords 
	 *
	 * This is basically a wrapper around the getRelatedRecords method that returns 
	 * an array of Dataface_RelatedRecord objects instead of just an array of associative
	 * arrays.
	 *
	 * @param string $relationshipName The name of the relationship.
	 * @param int $start The start index (zero-based).  Use null for default.
	 * @param int $end The limit parameter.  Use null for default.
	 * @param string $where A where clause to limit the results. e.g. 'Name="Fred" and Size='large'".
	 *			Use 0 for default.
	 * @param string $sort A comma delimited list of columns to sort on.  e.g. 'OrderField, LastName desc, FirstName asc'
	 * @return array Array of Dataface_RelatedRecord objects.
	 *
	 * @since 0.5
	 * 
	 * @see http://xataface.com/documentation/tutorial/getting_started/relationships
	 * @see http://www.xataface.com/wiki/relationships.ini_file
	 * @see Dataface_Record::getRelatedRecords()
	 * @see Dataface_Record::getRelationshipIterator()
	 * @see Dataface_RelationshipIterator()
	 * @see Dataface_Record::getRelatedRecord()
	 */
	function &getRelatedRecordObjects($relationshipName, $start=null, $end=null,$where=0, $sort=0){
		$out = array();
			
		$it = $this->getRelationshipIterator($relationshipName, $start, $end,$where,$sort);
		while ($it->hasNext() ){
			$out[] =& $it->next();
		}
		return $out;
	}
	
	
	/**
	 * @brief Returns a single Dataface_RelatedRecord object from the relationship 
	 * specified by $relationshipName .
	 *
	 * @param string $relationshipName The name of the relationship.
	 * @param integer $index The position of the record in the relationship.
	 * @param string $where A where clause to filter the base result set.
	 *				Use 0 for default.
	 * @param string $sort A comma-delimited list of columns to sort on.
	 * @return Dataface_RelatedRecord
	 *
	 * @see http://xataface.com/documentation/tutorial/getting_started/relationships
	 * @see http://www.xataface.com/wiki/relationships.ini_file
	 * @see getRelatedRecordObjects()
	 * @see getRelatedRecords()
	 *
	 * @since 0.5
	 */
	function &getRelatedRecord($relationshipName, $index=0, $where=0, $sort=0){
		if ( isset($this->cache[__FUNCTION__][$relationshipName][$index][$where][$sort]) ){
			return $this->cache[__FUNCTION__][$relationshipName][$index][$where][$sort];
		}
		$it = $this->getRelationshipIterator($relationshipName, $index, 1, $where, $sort);
		if ( $it->hasNext() ){
			$rec =& $it->next();
			$this->cache[__FUNCTION__][$relationshipName][$index][$where][$sort] =& $rec;
			return $rec;
		} else {
			$null = null;	// stupid hack because literal 'null' can't be returned by ref.
			return $null;
		}
	}
	
	
	
	
	/**
	 * @brief Moves a related record up one in the list.
	 *
	 * This depends on the metafields:order directive of the relationships.ini file
	 * to set the column upon which the relationship should be ordered.  If this is
	 * not set, then this method will return a PEAR_Error object.
	 *
	 * @param string $relationship The name of the relationship.
	 * @param int $index The index of the record to move up.
	 * @return mixed
	 *		Will return true if it is successful.  It may return a PEAR_Error object
	 *		if it fails.
	 *
	 * @since 0.8
	 * @see http://xataface.com/documentation/tutorial/getting_started/relationships
	 * @see http://www.xataface.com/wiki/relationships.ini_file
	 * @see Dataface_Relationship::getOrderColumn()
	 * @see sortRelationship()
	 */
	function moveUp($relationship, $index){
		
		$r =& $this->_table->getRelationship($relationship);
		$order_col = $r->getOrderColumn();
		if ( PEAR::isError($order_col) ) return $order_col;
		$order_table =& $r->getTable($order_col);
		if ( PEAR::isError($order_table) ) return $order_table;
		
		if ( $index == 0 ) return PEAR::raiseError("Cannot move up index 0");
		$it =& $this->getRelationshipIterator($relationship, $index-1, 2);
		if ( PEAR::isError($it) ) return $it;
		if ( $it->hasNext() ){
			$prev_record =& $it->next();
			if ( $it->hasNext() ){
				$curr_record =& $it->next();
			}
		}
		
		if ( !isset($prev_record) || !isset($curr_record) ){
			return PEAR::raiseError('Attempt to move record up in "'.$relationship.'" but the index "'.$index.'" did not exist.');
		}
		
		if ( intval($prev_record->val($order_col)) == intval($curr_record->val($order_col)) ){
			// The relationship's records are not ordered yet (consecutive records should have distinct order values.
			$res = $this->sortRelationship($relationship);
			if ( PEAR::isError($res) ) return $res;
			return $this->moveUp($relationship, $index);
		}
		
		
		$prev = $prev_record->toRecord($order_table->tablename);
		$curr = $curr_record->toRecord($order_table->tablename);
		$temp = $prev->val($order_col);
		$res = $prev->setValue($order_col, $curr->val($order_col));
		if (PEAR::isError($res) ) return $res;
		$res = $prev->save();
		if ( PEAR::isError($res) ) return $res;
		$res = $curr->setValue($order_col, $temp);
		if ( PEAR::isError($res) ) return $res;
		$res = $curr->save();
		if ( PEAR::isError($res) ) return $res;
		
		return true;
		
		
	}
	
	/**
	 * @brief Moves a related record down one in the list.
	 *
	 * This depends on the metafields:order directive of the relationships.ini file
	 * to set the column upon which the relationship should be ordered.  If this is
	 * not set, then this method will return a PEAR_Error object.
	 *
	 * @param string $relationship The name of the relationship.
	 * @param int $index The index of the record to move down.
	 * @return boolean True if successful.  PEAR_Error object on failure.
	 *
	 * @since 0.8
	 * @see http://xataface.com/documentation/tutorial/getting_started/relationships
	 * @see http://www.xataface.com/wiki/relationships.ini_file
	 * @see Dataface_Relationship::getOrderColumn()
	 * @see moveUp()
	 * @see sortRelationship()
	 */
	function moveDown($relationship, $index){
		return $this->moveUp($relationship, $index+1);
	}
	
	
	
	/**
	 * Sorts the records of this relationship (or just a subset of the
	 * relationship.
	 * @param string $relationship The name of the relationship to sort.
	 * @param int $start The start position of the sorting (optional).
	 * @param array $subset An array of Dataface_RelatedRecord objects representing the new sort order.
	 */
	function sortRelationship($relationship, $start=null, $subset=null){
		$r =& $this->_table->getRelationship($relationship);
		$order_col = $r->getOrderColumn();
		if ( PEAR::isError($order_col) ) return $order_col;
		$order_table =& $r->getTable($order_col);
		if ( PEAR::isError($order_table) ) return $order_table;
		
		// Our strategy for sorting only a subset.
		// Let R be the list of records in the relationship ordered
		// using the default order.
		//
		// Let A be the list of records in our subset of R using the 
		// default order.
		// 
		// Let b = A[0]
		// Let a be the predecessor of b.
		// Let c be the last record in A.
		// Let d be the successor of c.
		// Let B = A union {a,d}
		// For any record x in R, let ord(x) be the value of the order column
		// in x.
		//
		//
		//  The algorithm we will use to sort our subset is as follows:
		// 	if ( !exists(a) or ord(a) < ord(b) )
		//		and 
		//	( !exists(d) or ord(c) < ord(d) )
		//		and
		//	( ord(c) - ord(b) >= count(A) ){
		//		sort(A)
		//  } else {
		//		sort(R)
		//	}
		
		
		if ( isset($start) ){
			// We are dealing with a subset, so let's go through our algorithm
			// to see if we can get away with only sorting the subset
			//$countR = $this->numRelatedRecords($relationship);
			$aExists = ($start > 0);
			$countA = count($subset);
			$B =& $this->getRelatedRecordObjects($relationship, max($start-1,0), $countA+2);
			$countB = count($B);
		
			
			if ( $aExists ){
				$dExists = ( $countB-$countA >=2 );
			} else {
				$dExists = ($countB-$countA >= 1);
			}
			
			
			$AOffset = 0;
			if ( $aExists ) $AOffset++; 
			
			
			if ( (!$aExists or $B[0 + $AOffset]->val($order_col) > $B[0]->val($order_col) )
					and
				 (!$dExists or $B[$countA-1+$AOffset]->val($order_col) < $B[$countB-1]->val($order_col) )
				 	and
				 ( ($B[$countA-1+$AOffset]->val($order_col) - $B[0+$AOffset]->val($order_col)) >= ($countA - 1) ) ){
				 
				
				 $sortIndex = array();
				 $i = $B[0+$AOffset]->val($order_col);
				 foreach ($subset as $record){
				 	$sortIndex[$record->getId()] = $i++;
				 }
				 
				 
				 $i0 = $AOffset;
				 $i1 = $countA+$AOffset;
				 for ( $i = $i0; $i<$i1; $i++ ){
				 	$B[$i]->setValue($order_col, $sortIndex[ $B[$i]->getId() ] );
				 	$res = $B[$i]->save();
				 	if ( PEAR::isError($res) ) echo $res->getMessage();
				 }
				 $this->clearCache();
				 return true;
			}
			
			
			
		}
		
		$it =& $this->getRelationshipIterator($relationship, 'all');
		$i = 1;
		
		while ( $it->hasNext() ){
			$rec =& $it->next();
			//$rec->setValue($order_col, $i++);
			$orderRecord =& $rec->toRecord($order_table->tablename);
			$orderRecord->setValue($order_col, $i++);
			$res = $orderRecord->save();
			if ( PEAR::isError($res) ) return $res;
			unset($rec);
			unset($orderRecord);
			
		}
		$this->clearCache();
		
	}
	
	// @}
	// End Relationships
	//--------------------------------------------------------------------------------------
	
	// @{
	
	/**
	 * @name Field Values
	 * Methods for working with field values.
	 */
	
	
	/**
	 * @brief Sets the value of a field.
	 *
	 * @param string $key The name of the field to set.  This can be a simple name (eg: 'id') or a related name (eg: 'addresses.city').
	 * @param string $value The value to set the field to.
	 * @param integer $index The index of the record to change (if this is a related record).
	 *
	 * @since 0.5
	 *
	 * @section Synopsis
	 * 
	 * This is meant to accept the value in a number of different formats as it will
	 * first try to parse and normalize the value before storing it.  It normalizes
	 * it using the Dataface_Table::parse() method.
	 *
	 * Calling this method will also cause the clearCache() method to be called
	 *
	 * @section Examples
	 * @code
	 * $record->setValue('name', 'Benjamin');
	 * echo $record->val('name'); // Benjamin
	 * @endcode
	 *
	 * @section PropertyChangeEvents PropertyChange Events
	 *
	 * You can set up a listener to be informed when field values are changed on a particular
	 * record using property change events.  See addPropertyChangeListener() for more information.
	 *
	 * @see getValue()
	 * @see firePropertyChangeEvent()
	 * @see addPropertyChangeListener()
	 * @see removePropertyChangeListener()
	 */
	function setValue($key, $value, $index=0){
		
		$oldValue = $this->getValue($key, $index);
		
		
		if ( strpos($key, '.')!== false ){
			throw new Exception("Unsupported operation: setting value on related record.", E_USER_ERROR);
		
		}
			
		// This is a local field
		else {
			if ( strpos($key, "__") === 0 && $this->useMetaData ){
				/*
				 * This is a meta value..
				 */
				return $this->setMetaDataValue($key, $value);
			}
			
			$add=true;
			
			 
			if ( !array_key_exists($key, $this->_table->fields() ) ){
				
				if ( array_key_exists($key, $this->_table->transientFields()) ){
					
					$this->_transientValues[$key] = $value;
					$add=false;
					//return;
				}
			
				else if ( !array_key_exists($key, $this->_table->graftedFields(false)) ){
					$parent =& $this->getParentRecord();
					
					if ( isset($parent) and $parent->_table->hasField($key) ){
						
						$parent->setValue($key, $value, $index);
				
					}
					$add=false;
				} else {
					
					
					
					$add=true;
				}
				
			} 
			if ( $add ){
				$this->_values[$key] = $this->_table->parse($key, $value);
				
				$this->_isLoaded[$key] = true;
			}
		}
		
		
		// now set the flag to indicate that the value has been changed.
		
		$this->clearCache();
		
		if ($oldValue != $this->getValue($key, $index) ){
			
			$this->setFlag($key, $index);
			if ( $this->vetoSecurity ){
				$this->vetoFields[$key] = true;
			}
			$this->clearCache();
			
			$this->firePropertyChangeEvent($key, $oldValue, $this->getValue($key,$index));
			
			// Now we should notify the parent record if this was a key field
			if ( array_key_exists($key, $this->_table->keys() ) ){
				if ( !isset($parent) ) $parent =& $this->getParentRecord();
				if ( isset($parent) ){
					$keys = array_keys($this->_table->keys());
					$pkeys = array_keys($parent->_table->keys());
					$key_index = array_search($key, $keys);
					
					$parent->setValue($pkeys[$key_index], $this->getValue($key, $index));
				}
			}
		}
		
		
	}
	
	
	
	
	
	/**
	 * @brief Sets muliple values at once.
	 *
	 * @param array $values Associative array. [Field names] -> [Values]
	 *
	 * @section Examples
	 * @code
	 * $record->setValues(array(
	 *		'name' => 'Benjamin',
	 *		'phone'=> '555-555-5555',
	 *		'date_added' => '2008-12-23 10:00:12'
	 * ));
	 * echo $record->val('name'); // Benjamin
	 * echo $record->strval('date_added'); // 2008-12-23 10:00:12
	 * @endcode
	 */
	function setValues($values){
		$fields = $this->_table->fields(false, true);
		foreach ($values as $key=>$value){
			if ( isset( $fields[$key] ) ){
				$this->setValue($key, $value);
			} else if ( strpos($key,'__')===0){
				$this->setMetaDataValue($key,$value);
			}
		}
	}
	
	
	function getVersion(){
		$versionField = $this->_table->getVersionField();
		if ( !isset($versionField) ){
			return 0;
		} else {
			return intval($this->val($versionField));
		}
	}
	
	/**
	 * @brief Gets the value of a field in this record.
	 *
	 * @param string $fieldname The name of the field whose value we wish to obtain.   Could be simple name (eg: 'id') or related name (eg: 'addresses.city').
	 * @param int $index The index of the value.  This is primarily used when retrieving the value of a related field that has more than one record.
	 * @param string $where Optional where clause that can be used to filter related record if the fieldname refers to a related field.
	 * @param string $sort Optional sort clause that can be used to sort results when obtaining a field from a related record.
	 * @param boolean $debug Optional parameter to echo some debugging information to help diagnose problems.
	 *
	 * @return mixed The value for the specified field.
	 *
	 * @section Synopsis
	 *
	 * This method returns the raw data structure of the stored data which could be 
	 * an array, an object, a string, or just about anything.  For varchar fields
	 * this will generally be a string, but for other field types (e.g. dates) it may
	 * return an actual data structure.
	 *
	 * Dates are stored as an associative array of key values with keys such as
	 *   - year
	 *   - month
	 *   - day
	 *   - hours
	 *   - minutes
	 *   - seconds
	 *
	 * @attention If you wish to retrieve dates as a string, you should use the 
	 *	getValueAsString() method instead of this one.
	 *
	 * @section Rendering The Field Rendering Pipeline
	 *
	 * Dataface_Record provides a number of methods for returning the value of a 
	 * field and each is a little bit different.  The difference between these methods
	 * is based on the amount of processing that is performed on the value before it
	 * is returned.
	 *
	 * The following table summarizes the differences between these methods.
	 *
	 * <table>
	 *		
	 *			<tr>
	 *				<th>Method</th>
	 *				<th>Output Type</th>
	 *				<th>Respects Permissions</th>
	 *				<th>Valuelist Replacement</th>
	 *			</tr>
	 *		
	 *		
	 *			<tr>
	 *				<td>getValue()</th>
	 *				<td>Mixed.  (May be a string, number, or data structure).</td>
	 *				<td>No</td>
	 *				<td>No</td>
	 *			</tr>
	 *			<tr>
	 *				<td>getValueAsString()</td>
	 *				<td>String</td>
	 *				<td>No</td>
	 *				<td>No</td>
	 *			</tr>
	 *			<tr>
	 *				<td>display()</td>
	 *				<td>String</td>
	 *				<td>Yes</td>
	 *				<td>Yes</td>
	 *			</tr>
	 *			<tr>
	 *				<td>htmlValue()</td>
	 *				<td>String.  This string is made HTML friendly by converting special characters to their corresponding HTML entities (except for htmlarea fields).</td>
	 *				<td>Yes</td>
	 *				<td>Yes</td>
	 *			</tr>
	 *		
	 *	</table>
	 *
	 * @subsection permissions "Respects Permissions"
	 *
	 * The "Respects Permissions" column in the above table indicates whether the method will first check the current
	 * users' permissions before returning the field value.  Notice that the display() method
	 * respects permissions while the getValueAsString() method does not.  This means that if the current 
	 * user doesn't have the <em>view</em> permission for the given field it will just return
	 * "NO ACCESS" (or some other pre-defined value to indicate that the user doesn't have access
	 * to view the field content.
	 *
	 * @subsection valuelists "Valuelist Replacement"
	 *
	 * The "Valuelist Replacement" column in the above table only pertains to fields with the
	 * <em>vocabulary</em> directive in the <a href="http://xataface.com/wiki/fields.ini_file">fields.ini file</a>.
	 * Methods without <em>Valuelist Replacement</em> will return the field's value as it is stored
	 * in the database (more or less), while methods with valuelist replacement will return the
	 * field's corresponding value from the <a href="http://www.xataface.com/wiki/valuelists.ini_file">valuelist</a>
	 * specified by the <em>vocabulary</em> directive.
	 *
	 * For example, a table "books" might have an "author_id" field that stores the ID of an
	 * author record.  The <a href="http://xataface.com/wiki/fields.ini_file">fields.ini file</a> directives for this 
	 * field might look something like:
	 *
	 * @code
	 * [author_id]
	 *    widget:type=select
	 *    vocabulary=people
	 * @endcode
	 *
	 * Then we can contrast the output of getValueAsString() to display() with the following example:
	 * @code
	 * $record->getValueAsString('author_id');  // Would return something like "10"
	 * $record->display('author_id'); // Would return something like "Charles Dickens"
	 * @endcode
	 *
	 * @see display() For more information about how to configure respect of permissions and valuelist replacement.
	 *
	 * @section containerFields Container Fields
	 *
	 * getValue() will simply return the name of the file that is stored in <em>container</em> fields.  It doesn't
	 * actually load the value of the field.  
	 *
	 * @subsection containerFieldsURL Getting the URL for a File in a <em>container</em> Field
	 * 
	 * The display() method will return the URL to the file in a <em>container</em> field.
	 *
	 * @subsection containerFieldsPath Getting the Path to a File in a <em>container</em> Field
	 * 
	 * The getContainerSource() method will return the path to the file in a <em>container</em> field.
	 *
	 * @subsection moreOnContainers More Information about Container Fields
	 * 
	 * See <a href="http://xataface.com/documentation/how-to/how-to-handle-file-uploads">How to Handle File Uploads</a> (Tutorial)
	 * 
	 * @section blobFields Blob Fields
	 * 
	 * getValue() will return an empty string for blob fields as Xataface knows not to 
	 * load blob fields into memory.  The only exception to this rule is when you want to
	 * save data to a blob field and have previously performed a setValue() call on the
	 * blob field with the blob data.
	 *
	 * @subsection blobFieldURLs Getting the URL for the File in a <em>blob</em> Field
	 * 
	 * The display() method will return the URL to download the file that is stored
	 * in a blob field.
	 *
	 *
	 * @section dateFields Date Fields
	 *
	 * getValue() will return a data structure for date, datetime, time, and timestamp fields.
	 * This can often be more difficult to deal with when performing PHP date and time 
	 * operations, so you may want to use getValueAsString() instead when working with
	 * date fields.  getValueAsString() will return a string representation of the date
	 * in MySQL format (e.g. YYYY-mm-dd HH:MM:SS)
	 *
	 *
	 * @section Examples
	 *
	 * @code
	 * // Get the value from the 'name' column
	 * $record->getValue('name');
	 *
	 * // Get the title of the first book in the record's 'books' relationship.
	 * $record->getValue('books.title');
	 *
	 * // Get the title of the second book in the record's 'books' relationship.
	 * $record->getValue('books.title',1);
	 * 
	 * // Get the title of the first book in the record's 'books' relationship
	 * // that was published in 1988
	 * $record->getValue('books.title', 0, "year=1988");
	 *
	 * // Get the title of the first book in the record's 'books' relationship
	 * // when the books are sorted on 'year'
	 * $record->getValue('books.title', 0, 0, 'year asc');
	 *
	 * @endcode
	 * 
	 * @see val()
	 * @see value()
	 * @see getValueAsString()
	 * @see strval()
	 * @see display()
	 * @see q()
	 * @see htmlValue()
	 * @see printValue()
	 * @see print()
	 *
	 */
	function &getValue($fieldname, $index=0, $where=0, $sort=0, $debug=false){
		static $callcount=0;
		$callcount++;
		if ( $debug ) echo "Num calls to getValue(): $callcount";
		if ( isset($this->cache[__FUNCTION__][$fieldname][$index][$where][$sort]) ){
			return $this->cache[__FUNCTION__][$fieldname][$index][$where][$sort];
		}
		
		
		
		if ( is_array($index) ){
			throw new Exception(
				df_translate(
					'scripts.Dataface.Record.getValue.ERROR_PARAMETER_2',
					"In Dataface_Record.getValue() expected 2nd parameter to be integer but received array."
					), E_USER_ERROR);
		}
		if ( is_array($where) ){
			throw new Exception(
				df_translate(
					'scripts.Dataface.Record.getValue.ERROR_PARAMETER_3',
					"In Dataface_Record.getValue() expected 3rd parameter to be a string, but received array."
					), E_USER_ERROR);
		}
		if ( is_array($sort) ){
			throw new Exception(
				df_translate(
					'scripts.Dataface.Record.getValue.ERROR_PARAMETER_4',
					"In Dataface_Record.getValue() expected 4th parameter to be a string but received array."
					), E_USER_ERROR);
		}
		
		$out = null;
		if ( strpos($fieldname,'.') === false ){
			$delegate =& $this->_delegate;
			
			if ( !isset( $this->_values[$fieldname] ) ) {
				// The field is not set... check if there is a calculated field we can use.
				if ( $delegate !== null and method_exists($delegate, "field__$fieldname")){
					$methodname = "field__$fieldname";
					$out = $delegate->$methodname($this,$index);
					//$out =& call_user_func( array(&$delegate, "field__$fieldname"), $this, $index);
				//} else if ( array_key_exists($fieldname, $this->_transientValues) ){
				} else if ( array_key_exists($fieldname, $this->_table->transientFields()) ){
					$transientFields =& $this->_table->transientFields();
					if ( array_key_exists( $fieldname, $this->_transientValues) ){
						$out = $this->_transientValues[$fieldname];
					} else if ( isset($transientFields[$fieldname]['relationship']) and $transientFields[$fieldname]['widget']['type'] == 'grid'){
						$out= array();
						$rrecords =& $this->getRelatedRecordObjects($transientFields[$fieldname]['relationship'], 'all');
						$currRelationship =& $this->_table->getRelationship($transientFields[$fieldname]['relationship']);
						$relKeys =& $currRelationship->keys();
						//print_r(array_keys($currRelationship->keys()));
						foreach ($rrecords as $rrecord){
							$row = $rrecord->strvals();
							
							foreach ( array_keys($row) as $row_field ){
								$ptable =& $rrecord->_relationship->getTable($row_field);
								$precord =& $rrecord->toRecord($ptable->tablename);
								if ( !$precord or PEAR::isError($precord) ) continue;
								$row['__permissions__'][$row_field] = $precord->getPermissions(array('field'=>$row_field));
								if ( isset($relKeys[$row_field]) ) unset($row['__permissions__'][$row_field]['edit']);
								unset($precord);
								unset($ptable);
							}
							$row['__id__'] = $rrecord->getId();
							
							$out[] = $row;
							unset($rrecord);
							unset($row);
						}
						unset($relKeys);
						unset($currRelationship);
						unset($rrecords);
						$this->_transientValues[$fieldname] = $out;
					} else if ( isset($transientFields[$fieldname]['relationship']) and $transientFields[$fieldname]['widget']['type'] == 'checkbox'){
						$out= array();
						$rrecords =& $this->getRelatedRecordObjects($transientFields[$fieldname]['relationship'], 'all');
						$currRelationship =& $this->_table->getRelationship($transientFields[$fieldname]['relationship']);
						foreach ($rrecords as $rrecord){
							$row = $rrecord->strvals();
							$domRec = $rrecord->toRecord();
							
							$rowstr = array();
							foreach (array_keys($domRec->_table->keys()) as $relKey){
								$rowStr[] = urlencode($relKey).'='.urlencode($row[$relKey]);
							}
							$out[] = implode('&',$rowStr);
							
							unset($rowStr, $domRec);
							unset($rrecord);
							unset($row);
						}
						unset($relKeys);
						unset($currRelationship);
						unset($rrecords);
						$this->_transientValues[$fieldname] = $out;
					} else {
						
						if ( isset($delegate) and  method_exists($delegate, $fieldname.'__init') ){
							$methodname = $fieldname.'__init';
							$out = $delegate->$methodname($this);
							if ( isset($out) ){
								$this->_transientValues[$fieldname] = $out;
							}
						}
						
						if ( !isset($out) ){
							$methodname = 'initTransientField';
							$app = Dataface_Application::getInstance();
							$appdel = $app->getDelegate();
							if ( isset($appdel) and method_exists($appdel, $methodname) ){
								$out = $appdel->$methodname($this, $transientFields[$fieldname]);
								if ( isset($out) ){
									$this->_transientValues[$fieldname] = $out;
								}
							}
							
						
						}
						
						if ( !isset($out) ){
							$event = new StdClass;
							$event->record = $this;
							$event->field = $transientFields[$fieldname];
							$out = null;
							
							
							$app = Dataface_Application::getInstance();
							$app->fireEvent('initTransientField', $event);
							
							
							$out = @$event->out;
							if ( isset($out) ){
								$this->_transientValues[$fieldname] = $out;
							}
						}
							
						
						
						$out = null;
					}
					
				} else if ( ( $parent =& $this->getParentRecord() ) and $parent->_table->hasField($fieldname) ){
				
					return $parent->getValue($fieldname,$index,$where,$sort,$debug);
				} else {
					$this->_values[$fieldname] = null;
					$out = null;
				}
			} else {
				$out = $this->_values[$fieldname];
			}
			if ( isset($out) ){
				// We only store non-null values in cache.  We were having problems 
				// with segfaulting in PHP5 when groups are used.
				// This seems to fix the issue, but let's revisit it later.
				$this->cache[strval(__FUNCTION__)][strval($fieldname)][$index][$where][$sort] = $out;
			}
			return $out;
		} else {
			list($relationship, $fieldname) = explode('.', $fieldname);
			
			$rec =& $this->getRelatedRecords($relationship, false, $index, 1, $where, $sort);
			$this->cache[__FUNCTION__][$relationship.'.'.$fieldname][$index][$where][$sort] =& $rec[$fieldname];
			return $rec[$fieldname];
			
			
		}
	}
	
	
	
	
	/**
	 * @brief Alias for getValue()
	 * 
	 * @see getValue()
	 */
	function &value($fieldname, $index=0, $where=0, $sort=0){
		$val =& $this->getValue($fieldname, $index,$where, $sort);
		return $val;
	}
	
	/**
	 * @brief Alias for getValue()
	 * @see getValue()
	 */
	function &val($fieldname, $index=0, $where=0, $sort=0){
		$val =& $this->getValue($fieldname, $index, $where, $sort);
		return $val;
	}
	
	/**
	 * @brief Gets the values of this Record in an associative array. [Field names] -> [Field values].
	 * 
	 * @section Examples
	 *
	 * @subsection example1 Example 1: All Fields
	 *
	 * @code
	 * // Getting all of the field values including grafted fields
	 * // but excluding transient fields
	 * $vals = $record->getValues();
	 * foreach ($vals as $key=>$val ){
	 *     echo "Field name: ".$key." : Field value: ".$val."\n";
	 * }
	 * @endcode
	 * 
	 * Output would be something like:
	 * @code
	 * Field name: name : Field value: Steve
	 * Field name: age : Field value: 27
	 * Field name: weight: Field value: 154
	 * ...
	 * etc...
	 * @endcode
	 *
	 * You could yield the same results as the above code by first obtaining a list
	 * of the fields in the table and looping through the fields, calling the getValue()
	 * method on each field as follows:
	 * @code
	 * foreach ( array_keys($record->_table->fields(false, true)) as $field){
	 *     echo "Field name: ".$field." : Field value: ".$record->getValue($field)."\n";
	 * }
	 * @endcode
	 *
	 *
	 * @subsection example2 Example 2: Only Some Fields
	 * 
	 * @code
	 * // Only retrieve the name and age fields.
	 * $vals = $record->getValues(array('name','age'));
	 * foreach ($vals as $key=>$val ){
	 *     echo "Field name: ".$key." : Field value: ".$val."\n";
	 * }
	 * @endcode
	 *
	 * Output would be something like:
	 * @code
	 * Field name: name : Field value: Steve
	 * Field name: age : Field value: 27
	 * @endcode
	 *
	 * 
	 * 
	 * @param array $fields Array of column names that we wish to retrieve.  If this parameter is omitted, all of the fields are returned.
	 * @param int $index If we are returning related fields, then this is the index of the record
	 *		whose values should be returned.
	 * @param string $where For related fields, this is the where clause that can be used to filter
	 *		the related record list.
	 * @param string $sort For related fields, this is the sort clause that can be used to sort the
	 *		related records.
	 *
	 * @return array Associative array of fieldnames and their associated values.
	 *
	 * @see getValue()
	 * @see vals()
	 * @see strvals()
	 *
	 */
	function &getValues($fields = null, $index=0, $where=0, $sort=0){
		if ( !isset( $this->_values ) ) $this->_values = array();
		$values = array();
		$fields = ( $fields === null ) ? array_keys($this->_table->fields(false,true)) : $fields;
		foreach ($fields as $field){
			$values[$field] =& $this->getValue($field, $index, $where, $sort);
		}
			
		return $values;
	}
	
	/**
	 * @brief Alias for getValues()
	 * 
	 * @see getValues()
	 */
	function &values($fields = null, $index=0, $where=0, $sort=0){
		$vals =& $this->getValues($fields, $index, $where, $sort);
		return $vals;
	}
	
	/**
	 * @brief Alias for getValues()
	 * @see getValues()
	 */
	function &vals($fields = null, $index=0, $where=0, $sort=0){
		$vals =& $this->getValues($fields, $index, $where, $sort);
		return $vals;
	}
	
	/**
	 * @brief Gets the values of a field as a string.  
	 * 
	 * @param string $fieldname The name of the field whose value we want to retrieve.
	 * @param int $index For related fields, the index of the record within the related list whose
	 *		field value we wish to retrieve.
	 * @param string $where Where clause used to filter related list if retrieving a related
	 *	 field.
	 * @param string $sort A sort clause used to sort the related list if retrieving a related field.
	 * 
	 * @return string The stringified value stored in the record at the specified field.
	 *
	 * @section Synopsis
	 *
	 * 
	 * This method is a wrapper for the getValue() method.  It converts values to strings
	 * before returning them.  This is helpful for fields that store structures (e.g. 
	 * date fields.
	 *
	 * @section Examples
	 *
	 * @code
	 * $record->val('person_id');  // returns integer.
	 * $record->getValueAsString('person_id');  // returns string representation of integer
	 * $record->val('date_posted');  
	 *     // returns something like 
	 *     // array('year'=>2010, 'month'=>10, 'day'=>2, 'hours'=>9, 'minutes'=>23, 'seconds'=>5);
	 * $record->getValueAsString('date_posted');
	 *     // returns something like 2010-10-2 09:23:05
	 * @endcode
	 *
	 * @section differences getValueAsString() vs display() and getValue()
	 *
	 * Dataface_Record provides a sort of display stack with 4 main types of output.  The stack 
	 * looks like:
	 *
	 * htmlValue()  which wraps display() which wraps getValueAsString() which wraps getValue(). 
	 * For a description of the differences between these methods, see getValue()
	 *
	 * @section overriding Overriding the String Representation of a Field
	 *
	 * The output of the getValueAsString() method can be overridden by implementing the
	 * fieldname__toString() delegate class method.  E.g.
	 *
	 * @code
	 * <?php
	 * class tables_products {
	 *   ...
	 *     function price__toString($record){
	 *         return '$'.$record->val('price');
	 *     }
	 *   ...
	 * }
	 * @endcode
	 *
	 * The above delegate class method would cause the string representation to have a '$'
	 * prefixed to it.
	 *
	 * @subsection warning1 Warning 1: Output in Edit Form
	 *
	 * @attention Be careful when overriding the string representation of a field in this way as it affects every time
	 *  the field value is loaded as a string, including in an edit record form.  If you simply want to 
	 *	change the way a field value is displayed in the list or details view, it is much better
	 *  to instead override the output of the display() method via the fieldname__display() delegate class method
	 *  which is not used for displaying a field's value in a form widget.
	 *
	 * @subsection warning2 Warning 2: Implement fieldname__parse()
	 *
	 * @attention When implementing the fieldname__toString() delegate class method, you should
	 *  almost always implement a complementary fieldname__parse() delegate class method to 
	 *  perform the reverse operation.  This will allow you to remove any extra decoration 
	 *  that has been added when calling the setValue() method.
	 * 
	 * @subsection warning3 Warning 3: Use fieldname__display() instead
	 *
	 * @attention Don't implement the fieldname__toString() method unless you really know what
	 * you are doing.  Implement the fieldname__display() method instead to override the display()
	 * method.
	 *
	 * @see strval()
	 * @see getValue()
	 * @see strvals()
	 * @see display()
	 * @see htmlValue()
	 * @see http://xataface.com/wiki/Delegate_class_methods
	 */
	function getValueAsString($fieldname, $index=0, $where=0, $sort=0){
		//return $this->_table->getValueAsString($fieldname, $this->getValue($fieldname), $index);
		
		$value = $this->getValue($fieldname, $index, $where, $sort);
		
		
		$table =& $this->_table->getTableTableForField($fieldname);
		$delegate =& $table->getDelegate();
		$rel_fieldname = $table->relativeFieldName($fieldname);
		if ( $delegate !== null and method_exists( $delegate, $rel_fieldname.'__toString') ){
			$methodname = $rel_fieldname.'__toString';
			$value = $delegate->$methodname($value); //call_user_func( array(&$delegate, $rel_fieldname.'__toString'), $value);
		} else 
		
		
		if ( !is_scalar($value) ){
			$methodname = $this->_table->getType($fieldname)."_to_string";
			if ( method_exists( $this->_table, $methodname) ){
				
				$value = $this->_table->$methodname($value); //call_user_func( array( &$this->_table, $this->_table->getType($fieldname)."_to_string"), $value );
			} else {
				$value = $this->array2string($value);
				
			}
		}
		
		else if ( ( $parent =& $this->getParentRecord() ) and $parent->_table->hasField($fieldname) ){
			return $parent->getValueAsString($fieldname, $index, $where, $sort);
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
	 * @private
	 */
	function array2string($value){
		if ( is_string($value) ) return $value;
		if ( is_array($value) ){
			if ( count($value) > 0 and is_array($value[0]) ){
				$delim = "\n";
			} else {
				$delim = ', ';
			}
			return implode($delim, array_map(array(&$this,'array2string'), $value));
		}
		return '';
	}
	
	
	/**
	 * @brief Gets the values stored in this table as an associative array.  The values
	 * are all returned as strings.
	 *
	 * @param array $fields An optional array of field names to retrieve.
	 * @param int $index For related fields, the index of the source related record within the related records list.
	 * @param string $where (optional) Where clause to filter related lists in case some related fields are being returned.
	 * @param string $sort (optional) Sort clause to sort related lists in case related fields are being retrieved.
	 * @return array Associative array mapping field names to their associated values as strings.
	 *
	 *
	 * @section Synopsis
	 *
	 * This method is basically the same as the getValues() method except that the results
	 * are returned as strings.  I.e. getValuesAsStrings() is to getValueAsString() as getValues() is to getValue()
	 *
	 *
	 * @section fields Default Fields List
	 *
	 * If the $fields parameter is omitted or entered as the empty string (''), then
	 * all primary fields and grafted fields will be used as default.  This <em>DOES NOT</em>
	 * include related fields, calculated fields, or transient fields.
	 *
	 * @see getValues()
	 * @see getValueAsString()
	 *
	 */
	function getValuesAsStrings($fields='', $index=0, $where=0, $sort=0){
		$keys = is_array($fields) ? $fields : array_keys($this->_table->fields(false,true));
		$values = array();
		foreach ($keys as $key){
			$values[$key] = $this->getValueAsString($key, $index, $where, $sort);
		}
		return $values;
	}
	
	/**
	 * @brief Alias for getValuesAsStrings()
	 */
	function strvals($fields='', $index=0, $where=0, $sort=0){
		return $this->getValuesAsStrings($fields, $index, $where, $sort);
	}
	
	
	/**
	 * @brief Alias for getValueAsString()
	 */
	function strval($fieldname, $index=0, $where=0, $sort=0){
		return $this->getValueAsString($fieldname, $index, $where, $sort);
	}
	
	
	/**
	* @brief Alias for getValueAsString()
	*/
	function stringValue($fieldname, $index=0, $where=0, $sort=0){
		return $this->getValueAsString($fieldname, $index, $where, $sort);
	}
	
	
	/**
	 * @brief Returns the value of a field except it is serialzed to be instered into a database.
	 *
	 * @param string $fieldname The name of the field whose value we are to retrieve.
	 * @param int $index The index of the related record whose value we are retrieving (only if we are retrieving a related record).
	 * @param string $where The where clause to filter related records (only if we are retrieving a related field).
	 * @param string $sorrt The sort clause to use in sorting related records (only if we are retrieving a related field).
	 *
	 * @return string The field value exactly as it would be placed into an SQL query.
	 *
	 * @since 0.5
	 * 
	 * @see Dataface_Serializer
	 */
	function getSerializedValue($fieldname, $index=0, $where=0, $sort=0){
		$s = $this->_table->getSerializer();
		return $s->serialize($fieldname, $this->getValue($fieldname, 0, $where, $sort));
	
	}
	
	
	/**
	 * @brief Returns a the value of a field in a meaningful state so that it can be displayed.
	 *
	 * @param string $fieldname The name of the field to return.
	 * @param int $index For related fields indicates the index within the related list of the record to retrieve.
	 * @param string $where Optional where clause to filter related list when retrieving a related field.
	 * @param string $sort Optional sort clause when retrieving a related field.  Used to sort related list before 
	 *  selecting the related record from which the value is to be returned.
	 * @param boolean $urlencode Optional parameter to urlencode the output.
	 * @return string The displayable result.
	 *
	 * @since 0.5
	 *
	 * @section Synopsis
	 * 
	 * This method is similar to getValueAsString() except that this goes a step further and resolves
	 * references. For example, some fields may store an integer that represents the id for a related 
	 * record in another table.  If a vocabulary is assigned to that field that defines the meanings for 
	 * the integers, then this method will return the resolved vocabulary rather than the integer itself.</p>
	 * 
	 * @section Examples
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
	 * @section Permissions
	 *
	 * The display() method, unlike the getValueAsString() method, respects permissions which
	 * means that if the current user doesn't have <em>view</em> permission granted on the 
	 * record, it will simply return 'NO ACCESS' (or some other configurable string to indicate
	 * that the user has no access to view this field.
	 *
	 * For exmaple, consider the following delegate class for the people table.
	 *
	 * tables/people/people.php
	 * @code
	 * <?php
	 * class tables_people {
	 *     function sin__permissions($record){
	 *         return array('view'=>0);
	 *	   }
	 *     
	 *     function name__permissions($record){
	 *         return array('view'=>1);
	 *     }
	 * }
	 * @endcode
	 *
	 * So that no users have permission to view the <em>sin</em> field, but everyone
	 * has permission to view the <em>name</em> field.
	 *
	 * Then we could have:
	 *
	 * @code
	 * // $record is a Dataface_Record object from the people table
	 * $record->setValue('sin', '123456789');
	 * $record->setValue('name', 'tom');
	 *
	 * // We have full permission to the 'name' field so we can always get the value
	 * echo $record->display('name'); // 'tom'
	 * 
	 * // We don't have view permission for the 'sin' field so display() will return no access
	 * echo $record->display('sin'); // 'NO ACCESS'
	 *
	 * // getValueAsString() does NOT respect permissions so we can use that to get the value
	 * echo $record->getValueAsString('sin'); // '123456789'
	 *
	 * // We could also set the 'secureDisplay' flag to false to tell display() to
	 * // not respect permissions
	 * $record->secureDisplay = false;
	 * echo $record->display('sin'); // '123456789'
	 *
	 * @endcode
	 * 
	 * The above example shows how the display() method respects permissions whereas the
	 * getValueAsString() method does not.  For more information about the different
	 * field rendering methods and their differences see the documentation for the getValue()
	 * method.
	 *
	 * @subsection DisablingPerms Disregarding Permissions
	 * 
	 * If you want to use the output of display() and don't want to be limited by the user's actual permissions
	 * to see the field content, you can turn secure display off on the record by setting the 
	 * secureDisplay flag to false.
	 *
	 * @code
	 * $record->secureDisplay = false;
	 * @endcode
	 *
	 * If you want to change the display security on multiple records at a time, you can
	 * use the df_secure() function of the dataface-public-api.php file to secure or unsecure
	 * an entire array of Dataface_Record objects.
	 *
	 * @code
	 * $records = array(
	 *    $record1, $record2, $record3
	 * );
	 * df_secure($records, false);  // disregard security
	 * 
	 * $record1->secureDisplay; // false
	 * $record2->secureDisplay; // false
	 * $record3->secureDisplay; // false
	 *
	 * df_secure($records, true); // re-enable security
	 * 
	 * $record1->secureDisplay; // true
	 * $record2->secureDisplay; // true
	 * $record3->secureDisplay; // true
	 * @endcode
	 *
	 * @subsection OverrideNoaccess Overriding 'NO ACCESS' Text
	 *
	 * If you call display() on a field for which the user does not have the 'view' permission
	 * it simply returns the string 'NO ACCESS'.  You can, however, override this string by 
	 * 	implementing the no_access_text() method in the table's delegate class.
	 *
	 * e.g.
	 * @code
	 * function no_access_text($record){
	 *     return 'SUBSCRIBE';
	 * }
	 * @endcode
	 *
	 *
	 * @section Overriding Overriding the Display Method
	 *
	 * Since the display() method is used by Xataface for most field display in the Xataface
	 * application, it is often desirable to override its output on particular fields to 
	 * improve usability of the application.
	 *
	 * For example you may want to store prices as simple decimal numbers, want them to be displayed
	 * formatted in the local currency.  In this case you can implement the fieldname__display()
	 * method in the table's delegate class. 
	 *
	 * e.g.
	 * tables/products/products.php
	 * @code
	 * <?php
	 * class tables_products {
	 *     function price__display($record){
	 *         return money_format($record->val('price'));
	 *     }
	 * }
	 * @endcode
	 *
	 * Then when you are using records of the products table you would have:
	 * @code
	 * $record->setValue('price', 1.50);
	 * echo $record->getValue('price'); // 1.5
	 * echo $record->getValueAsString('price'); // '1.50'
	 * echo $record->display('price'); // '$1.50 USD'
	 * @endcode
	 *
	 * @attention The display() method is meant to return a plain text string - not HTML.  If you want to override the display
	 *  with HTML tags and links, you should override the htmlValue() method instead using the fieldname__htmlValue() method
	 *  of the delegate classs.
	 *
	 *
	 * @see getValue()
	 * @see getValueAsString()
	 * @see http://xataface.com/wiki/Delegate_class_methods
	 *
	 */
	function display($fieldname, $index=0, $where=0, $sort=0, $urlencode=false){
		if ( isset($this->cache[__FUNCTION__][$fieldname][$index][$where][$sort]) ){
			return $this->cache[__FUNCTION__][$fieldname][$index][$where][$sort];
		}
		if ( strpos($fieldname,'.') === false ){
			// this is not a related field.
			if ( $this->secureDisplay and !Dataface_PermissionsTool::view($this, array('field'=>$fieldname)) ){
				$del =& $this->_table->getDelegate();
				if ( $del and method_exists($del, 'no_access_text') ){
					return $del->no_access_text($this, array('field'=>$fieldname));
				} else {
					return 'NO ACCESS';
				}
			}
		} else {
			list($relationship,$fieldname) = explode('.',$fieldname);
			$rrecord =& $this->getRelatedRecord($relationship,$index,$where,$sort);
			if ( !$rrecord ) return null;
			$out = $rrecord->display($fieldname);
			$this->cache[__FUNCTION__][$fieldname][$index][$where][$sort] = $out;
			return $out;
		}
		
		
	
		$table =&  $this->_table->getTableTableForField($fieldname);
		
		
		$delegate =& $this->_table->getDelegate();
		if ( $delegate !== null and method_exists( $delegate, $fieldname."__display") ){
			$methodname = $fieldname."__display";
			$out = $delegate->$methodname($this);
			//$out = call_user_func(array(&$delegate, $fieldname."__display"), $this);
			$this->cache[__FUNCTION__][$fieldname][$index][$where][$sort] = $out;
			return $out;
			
		}
		
		$field =& $this->_table->getField($fieldname);
		if ( $this->_table->isBlob($fieldname) or ($this->_table->isContainer($fieldname) and @$field['secure'])  ){
			
			unset($table);
			$table =& Dataface_Table::loadTable($field['tablename']);
			$keys = array_keys($table->keys());
			$qstr = '';
			foreach ($keys as $key){
				$qstr .= "&$key"."=".$this->strval($key,$index,$where,$sort);
			}
			$out = DATAFACE_SITE_HREF."?-action=getBlob&-table=".$field['tablename']."&-field=$fieldname&-index=$index$qstr";
			$this->cache[__FUNCTION__][$fieldname][$index][$where][$sort] = $out;
			return $out;
		}
		
		else if ( $this->_table->isContainer($fieldname) ){
			$field =& $this->_table->getField($fieldname);
			$strvl=$this->strval($fieldname,$index,$where,$sort);
			if ($urlencode)
			{
			    $strvl=rawurlencode($strvl);
			}
			$out = $field['url'].'/'.$strvl;
			if ( strlen($out) > 1 and $out{0} == '/' and $out{1} == '/' ){
				$out = substr($out,1);
			}
			$this->cache[__FUNCTION__][$fieldname][$index][$where][$sort] = $out;
			return $out;
		}
		
		else { //if ( !$this->_table->isBlob($fieldname) ){
		
			$field =& $this->_table->getField($fieldname);
			
			
			if ( PEAR::isError($field) ){
				$field->addUserInfo("Failed to get field '$fieldname' while trying to display its value in Record::display()");
				return $field;
				
				
			}
			$vocab = $field['vocabulary'];
			if ( $vocab ){
				$valuelist =& $table->getValuelist($vocab);
			}
			$value = $this->getValue($fieldname, $index, $where, $sort);
			if ( PEAR::isError($value) ) return '';
			if ( isset($valuelist) && !PEAR::isError($valuelist) ){
				if ( $field['repeat'] and is_array($value) ){
					$out = "";
					foreach ($value as $value_item){
						if ( isset( $valuelist[$value_item] ) ){
							$out .= $valuelist[$value_item].', ';
						} else {
							$out .= $value_item.', ';
						}
					}
					if ( strlen($out) > 0 ) $out = substr($out, 0, strlen($out)-2);
					$this->cache[__FUNCTION__][$fieldname][$index][$where][$sort] = $out;
					return $out;
				}
				
				//else if ( isset( $valuelist[$value]) ){
				else {
					if ( is_array($value) ) $value = $this->strval($fieldname, $index, $where, $sort);
					if ( isset($valuelist[$value]) ){
						$out = $valuelist[$value];
						$this->cache[__FUNCTION__][$fieldname][$index][$where][$sort] = $out;
						return $out;
					} else {
						return $value;
					}
				} 
			} else {
				$parent =& $this->getParentRecord();
				
				
				
				if ( isset($parent) and $parent->_table->hasField($fieldname) ){
					
					return $parent->display($fieldname, $index, $where, $sort);
				}
				$out = $this->getValueAsString($fieldname, $index, $where, $sort);
				
				
				$out = $table->format($fieldname, $out);
				
				
				// Let's pass the value through an event filter to give modules
				// a crack at the final output.
				$evt = new stdClass;
				$evt->record = $this;
				$evt->field =& $field;
				$evt->value = $out;
				$table->app->fireEvent('Record::display', $evt);
				$out = $evt->value;
				
				
				
				$this->cache[__FUNCTION__][$fieldname][$index][$where][$sort] = $out;
				return $out;
			}
		
		
			//return $this->_table->display($fieldname, $this->getValue($fieldname, $index));
		} 
				
				
	}
	
	
	
	
	/**
	 * @brief Returns an HTML-friendly value of a field.
	 *
	 * @param string $fieldname The name of the field to return.
	 * @param int $index For related fields indicates the index within the related list of the record to retrieve.
	 * @param string $where Optional where clause to filter related list when retrieving a related field.
	 * @param string $sort Optional sort clause when retrieving a related field.  Used to sort related list before 
	 *  selecting the related record from which the value is to be returned.
	 * @param array $params Optional additional parameters to customize the HTML output.  This may be passed to 
	 *		include HTML attributes width and height to blob fields containing an image.
	 *
	 * @return string The HTML string result.
	 *
	 * @since 0.5
	 *
	 * @section Synopsis
	 * 
	 * This method sits above "display" on the output stack for a field.
	 * I.e. it wraps display() and adds some extra filtering to make the
	 * output directly appropriate to be displayed as HTML.  In text fields
	 * this will convert newlines to breaks, and in blob fields, this will output
	 * either the full a-href tag or img tag depending on the type of content that
	 * is stored.
	 *
	 * 
	 * @see display()
	 * @see getValue()
	 * @see getValueAsString()
	 * 
	 */
	function htmlValue($fieldname, $index=0, $where=0, $sort=0,$params=array()){
		$recid = $this->getId();
		$uri = $recid.'#'.$fieldname;
		$domid = $uri.'-'.rand();
		
		
		
		$delegate =& $this->_table->getDelegate();
		if ( isset($delegate) && method_exists($delegate, $fieldname.'__htmlValue') ){
			$methodname = $fieldname.'__htmlValue';
			$res = $delegate->$methodname($this);
			//$res = call_user_func(array(&$delegate, $fieldname.'__htmlValue'), $this);
			if ( is_string($res) and DATAFACE_USAGE_MODE == 'edit' and $this->checkPermission('edit', array('field'=>$fieldname)) and !$this->_table->isMetaField($fieldname) ){
				$res = '<span id="'.df_escape($domid).'" df:id="'.df_escape($uri).'" class="df__editable">'.$res.'</span>';
			}
			return $res;
		}
		$parent =& $this->getParentRecord();
		if ( isset($parent) and $parent->_table->hasField($fieldname) ){
			return $parent->htmlValue($fieldname, $index, $where, $sort, $params);
		}	
		$val = $this->display($fieldname, $index, $where, $sort);
                $strval = $this->strval($fieldname, $index, $where, $sort);
		$field = $this->_table->getField($fieldname);
		if ( !@$field['passthru'] and $this->escapeOutput) $val = nl2br(df_escape($val));
		if ( $this->secureDisplay and !Dataface_PermissionsTool::view($this, array('field'=>$fieldname)) ){
			$del =& $this->_table->getDelegate();
			if ( $del and method_exists($del, 'no_access_link') ){
				$link = $del->no_access_link($this, array('field'=>$fieldname));
				return '<a href="'.df_escape($link).'">'.$val.'</a>';
			}
		}
		
		
		//if ( $field['widget']['type'] != 'htmlarea' ) $val = htmlentities($val,ENT_COMPAT, 'UTF-8');
		//if ( $this->_table->isText($fieldname) and $field['widget']['type'] != 'htmlarea' and $field['contenttype'] != 'text/html' ) $val = nl2br($val);
		
		if ( $this->_table->isBlob($fieldname) or $this->_table->isContainer($fieldname) ){
			if ( $this->getLength($fieldname, $index,$where,$sort) > 0 ){
				if ( $this->isImage($fieldname, $index, $where, $sort) ){
					$val = '<img src="'.$val.'"';
                                        if ( !isset($parmas['alt']) ){
                                            $params['alt'] = $strval;
                                        }
					if ( !isset($params['width']) and isset($field['width']) ){
						$params['width'] = $field['width'];
					}
					foreach ($params as $pkey=>$pval){
						$val .= ' '.df_escape($pkey).'="'.df_escape($pval).'"';
					}
					$val .= '/>';
				} else {
					$file_icon = df_translate(
						$this->getMimetype($fieldname,$index,$where,$sort).' file icon',
						df_absolute_url(DATAFACE_URL).'/images/document_icon.gif'
						);
					$val = '<img src="'.df_escape($file_icon).'"/><a href="'.$val.'" target="_blank"';
					foreach ($params as $pkey=>$pval){
						$val .= ' '.df_escape($pkey).'="'.df_escape($pval).'"';
					}
					$val .= '>'.df_escape($strval).' ('.df_escape($this->getMimetype($fieldname, $index,$where,$sort)).')</a>';
				}
			} else {
				$val = "(Empty)";
			}
		}
		if ( is_string($val) and DATAFACE_USAGE_MODE == 'edit' and $this->checkPermission('edit', array('field'=>$fieldname))  and !$this->_table->isMetaField($fieldname)){
			$val = '<span id="'.df_escape($domid).'" df:id="'.df_escape($uri).'" class="df__editable">'.$val.'</span>';
		}
		return $val;
		
	
	
	}
	
	
	/**
	 * @brief Returns a preview of a field.  A preview is a shortened version of the text of a field
	 * with all html tags stripped out.
	 *
	 * @param $fieldname The name of the field for which the preview pertains.
	 * @param $index The index of the field (for related field only).
	 * @param $maxlength The number of characters for the preview.
	 * @param string $where Optional where clause to filter related list when retrieving a related field.
	 * @param string $sort Optional sort clause when retrieving a related field.  Used to sort related list before 
	 * 
	 * 
	 * @return string The preview of the field value
	 * @since 0.6
	 *
	 * @see display()
	 * @see getRelatedRecords()
	 *
	 */
	function preview($fieldname, $index=0, $maxlength=255, $where=0, $sort=0){
		
		$strval = strip_tags($this->display($fieldname,$index, $where, $sort));
		$field =& $this->table()->getField($fieldname);
		if ( $field['Type'] == 'container' ){
			$strval = strip_tags($this->val($fieldname, $index, $where, $sort));
		}
		$out = substr($strval, 0, $maxlength);
		if ( strlen($strval)>$maxlength) {
			$out .= '...';
		}	 
		return $out;
		
	}
	
	/**
	 * @brief Returns the URL to a thumbnail of the image stored in a field.
	 *
	 * @since 2.0
	 *
	 * @param string $fieldname The name of the field that stores the record.
	 * @param mixed $params The parameters for the thumbnail resizing.  This can be either
	 *	a URL encoded query string or an equivalent associative array.
	 * @param int $index For related records this indicates the index of the record
	 *	to load.
	 * @param string $where For related records a where clause to filter the relationship.
	 * @param string $sort For related records a clause to sort the related records.
	 * @returns string The URL to the image.
	 *
	 * <h3>Parameters</h3>
	 * <p>The @c $params argument can contain the following parameters:</p>
	 * <table>
	 *		<tr><th>Name</th><th>Type</th><th>Description</th><th>Default</th></tr>
	 *		<tr><td>max_width</td><td>Int</td><td>The maximum width of the image in pixels.   It will resize the image to this size if it is greater.</td><td>No default</td></tr>
	 *		<tr><td>max_height</td><td>Int</td><td>The maximum height of the image in pixels.  It will resize the image to this height if it is greater.</td><td>No Default</td></tr>
	 *	</table>
	 */
	function thumbnail($fieldname, $params='', $index=0, $where=0, $sort=0){
		if ( is_array($params) ) $params = http_build_query($params);
		if ( !$params ) $params = 'max_width=75&max_height=100';
		$out = $this->display($fieldname, $index, $where, $sort);
		if ( strpos($out, '?') === false ) $out .= '?';
		else $out .= '&';
		$out .= $params;
		return $out;
	
	}
	
	/**
	 * @brief Alias for display()
	 * @deprecated
	 * @since 0.5
	 */
	function printValue($fieldname, $index=0, $where=0, $sort=0 ){
		return $this->display($fieldname, $index, $where, $sort);
	}
	
	/**
	 * @brief Alias of display()
	 * @deprecated
	 * @since 0.5
	 */
	function printval($fieldname, $index=0, $where=0, $sort=0){
		return $this->display($fieldname, $index, $where, $sort);
	}
	
	/**
	 * @brief Alias of  display()
	 * @deprecated
	 * @since 0.5
	 */
	function q($fieldname, $index=0, $where=0, $sort=0){
		return $this->display($fieldname, $index, $where, $sort);
	}
	
	/**
	 * @brief Alias of htmlValue()
	 * @deprecated
	 * @since 0.5
	 *
	 */
	function qq($fieldname, $index=0, $where=0, $sort=0){
		return $this->htmlValue($fieldname, $index, $where, $sort);
	}
	
	/**
	 * @brief Indicates whether a field exists in the table. 
	 *
	 * @attention This method has an extremely confusing name as it doesn't actually check
	 *  to see if the field has a value.  It just checks if there is a place for the value
	 *  in the array.  This method has been deprecated as of 2.0.  Please use alternate
	 *  means to see if a record has a value.
	 *
	 * @param string $fieldname The name of the field to check.
	 *
	 * @return boolean True if the record contains a field by the name $fieldname
	 *
	 * @since 0.5
	 * @deprecated As of 2.0
	 */
	function hasValue($fieldname){
		return (isset( $this->_values) and array_key_exists($fieldname, $this->_values) );
	}
	
	
	/**
	 * @brief Returns associative array of the record's values where the keys are the 
	 * absolute field paths including the table name (using dot notation).
	 *
	 * @return array Associative array mapping absolute field names to their values.
	 *
	 * @since 0.5
	 *
	 * @section Synopsis
	 *
	 * This method is very similar to getValues() except that the array keys are the 
	 * absolute field name (including the table name) instead of just the field name.
	 *
	 * Eg.
	 * @code
	 *
	 * // For a reord of the 'people' table
	 * $vals = $record->getValues();
	 *
	 * echo $vals['name']; // 'Steve'
	 * echo $vals['age']; // 27
	 *
	 * $absVals = $record->getAbsoluteValues();
	 * echo $absVals['people.name']; // 'Steve'
	 * echo $absVals['people.age']; // 27
	 * echo $absVals['name']; // null
	 * @endcode
	 *
	 * @see getValues()
	 */
	function getAbsoluteValues(){
		$values = $this->getValues();
		$absValues = array();
		foreach ( $values as $key=>$value){
			$absValues[$this->_table->tablename.".".$key] = $value;
		}
		return $absValues;
	}
	
	
	
	/**
	 * @brief Returns the full path to the  file contained in a container field.
	 *
	 * @param string $fieldname The name of the field.
	 * @return string The path to the file in the container field or null if field is empty.
	 *
	 * @since 1.0
	 * 
	 * @section Synopsis
	 * 
	 * Since container fields only store the filename of the stored file, and doesn't store
	 * the full path to the directory that contains the file, loading the file requires 
	 * more than just a call to getValue().  This method bridges the gap by returning the
	 * full filesystem path to where the file is stored.
	 *
	 * @code
	 * echo $record->getValue('uploaded_image'); // 'my_image.jpg'
	 * echo $record->getContainerSource('uploaded_image'); // 'tables/people/uploaded_image/my_image.jpg'
	 * @endcode
	 *
	 * @see http://xataface.com/documentation/how-to/how-to-handle-file-uploads
	 *
	 */
	function getContainerSource($fieldname){
		$filename = $this->strval($fieldname);
		if ( strlen($filename)===0 ){
			return null;
		}
		$field =& $this->_table->getField($fieldname);
		return $field['savepath'].'/'.$filename;
	
	}

	
	/**
	 * @brief Sets the value of a metadata field.  
	 *
	 * @param string $key The field key.
	 * @param mixed $value The value to store
	 *
	 * @since 0.5
	 *
	 * Metadata fields are fields that store supplementary information for other fields.   
	 * E.g. If you have a container field, you probably have other fields to store the 
	 * mimetype of the file.  This is also used to store the lengths of all field
	 * data that is loaded.
	 *
	 * @see getLength()
	 *
	 */  
	function setMetaDataValue($key, $value){
		if ( !isset( $this->_metaDataValues ) ) $this->_metaDataValues = array();
		$this->_metaDataValues[$key] = $value;
		$parent =& $this->getParentRecord();
		if ( isset($parent) ){
			$parent->setMetaDataValue($key, $value);
		}
	}
	
	
	
	
	
	
	
	/**
	 * @brief Clears all fields in this record.
	 *
	 * @since 0.6
	 */
	function clearValues(){
		$this->_values = array();
		$this->_relatedValues = array();
		$this->_valCache = array();
		
		$this->clearFlags();
		
		$parent =& $this->getParentRecord();
		if ( isset($parent) ){
			$parent->clearValues();
		}
	
	}
	
	/**
	 * @brief Clears the value in a field.
	 *
	 * @param string $field The name of the field whose value we are clearing.
	 * @since 0.6
	 *
	 */
	function clearValue($field){
		
		unset($this->_values[$field]);
		$this->clearFlag($field);
		
		$parent =& $this->getParentRecord();
		if ( isset($parent) ){
			$parent->clearValue();
		}
	}
	
	
	
	// @}
	// End Field Values Methods
	//-------------------------------------------------------------------------------
	
	
	// @{
	/**
	 * @name Property Change Events
	 */
	
	
	/**
	 * @brief Adds a listener to be notified when field values are changed.
	 *
	 * @param string $key The name of the field to listen for changes on.
	 * @param Object &$listener The listener to register to receive notifications.
	 *
	 * @section Synopsis
	 * 
	 * Any object implementing the informal PropertyChangeListener interface
	 * may be added as a listener to receive notifications of changes to field
	 * values.  This this interface dictates only that a a method with the
	 * following signature is implemented:
	 *
	 * @code
	 * function propertyChanged( 
	 *                Dataface_Record $source, 
	 *                string $field, 
	 *                mixed $oldValue, 
	 *                mixed $newValue
	 *           );
	 * @endcode
	 *
	 * @since 1.2
	 *
	 * @see removePropertyChangeListener()
	 * @see firePropertyChangeEvent()
	 * @see propertyChanged()
	 */
	function addPropertyChangeListener($key, &$listener){
		$this->propertyChangeListeners[$key][] = &$listener;
	}
	
	/**
	 * @brief Removes a listener from the list of objects to be notified when 
	 * changes are made on a particular field.
	 *
	 * @param string $key The name of the field that was changed.
	 * @param object $listener
	 *
	 * @since 1.2
	 *
	 * @see addPropertyChangeListener()
	 * @see firePropertyChangeEvent()
	 * @see propertyChanged()
	 *
	 */
	function removePropertyChangeListener($key, &$listener){
		if ( !isset($key) ) $key = '*';
		if ( !isset($callback) ) unset($this->propertyChangeListeners[$key]);
		else if ( isset($this->propertyChangeListeners[$key]) ){
			if ( ($index = array_search($listener,$this->propertyChangeListeners[$key])) !== false){
				unset($this->propertyChangeListeners[$key][$index]);
			}
		}
	}
	
	/**
	 * @brief Fires a property change event to all listeners registered to be notifed
	 * of changes to the specified field.
	 * 
	 * @param string $key The name of the field that was changed.
	 * @param mixed $oldValue The old value of the field.
	 * @param mixed $newValue The new value of the field.
	 *
	 * @since 1.2
	 *
	 * @see addPropertyChangeListener()
	 * @see removePropertyChangeListener()
	 * @see propertyChanged()
	 */
	function firePropertyChangeEvent($key, $oldValue, $newValue){
		$origKey = $key;
		$keys = array('*');
		if ( isset($key) ) $keys[] = $key;
		foreach ($keys as $key){
			if ( !isset($this->propertyChangeListeners[$key] ) ) continue;
			foreach ( array_keys($this->propertyChangeListeners[$key]) as $lkey){
				$this->propertyChangeListeners[$key][$lkey]->propertyChanged($this,$origKey, $oldValue, $newValue);
				
			}
		}
		
		
	}
	
	/**
	 * @brief This method is to implement the PropertyChangeListener interface.  This method
	 * will be called whenever a change is made to the parent record's primary
	 * key, so that we can keep our keys in sync.
	 *
	 * @param Object &$source The source of the property change.
	 * @param string $key The name of the field that was changed.
	 * @param mixed $oldValue The old value.
	 * @param mixed $newValue The new value.
	 *
	 * @since 1.2
	 * 
	 * @see addPropertyChangeListener()
	 * @see removePropertyChangeListener()
	 * @see firePropertyChangeEvent()
	 * @see propertyChanged()
	 */
	function propertyChanged(&$source, $key, $oldValue, $newValue){
		$parentRecord =& $this->getParentRecord();
		if ( is_a($source, 'Dataface_Record') and is_a($parentRecord, 'Dataface_Record') and $source->_id === $parentRecord->_id ){
			$pkeys = $source->_table->keys();
			$pkey_names = array_keys($pkeys);
			$okeys = $this->_table->keys();
			$okey_names = array_keys($okeys);
			
			if ( !array_key_exists($key, $pkeys) ) return false;
				// The field that was changed was not a key so we don't care
				
			$key_index = array_search($key, $pkey_names);
			if ( $key_index === false ) throw new Exception("An error occurred trying to find the index of the parent's key.  This is a code error that should be fixded by the developer.", E_USER_ERROR);
			
			
			if ( !isset($okey_names[$key_index]) )
				throw new Exception("Attempt to keep the current record in sync with its parent but they seem to have a different number of primary keys.  To use Dataface inheritance, tables must have a corresponding primary key.", E_USER_ERROR);
			
			
			$this->setValue( $okey_names[$key_index], $newValue);
		}
	}
	
	
	
	
	// @}
	// END Property Change Events
	//-------------------------------------------------------------------------------------
	
	// @{
	/**
	 * @name Tab Management
	 */
	
	/**
	 * @brief Returns a join record for the given table.  
	 *
	 * @param string $tablename The name of the table from which the join record
	 * 				should be drawn.
	 * @param boolean $nullIfNotFound If set, then this will return null if no join 
	 *		record yet exists in the database.  Added in Xataface 2.0
	 *
	 * @return Dataface_Record Join record from the specified join table or 
	 * 			a new record with the correct primary key values if none exists.
	 *
	 * @return PEAR_Error If the specified table in incompatible.
	 * @since 0.8
	 *
	 * @section Synopsis
	 * 
	 * A join record is one that contains auxiliary data for the current record.  
	 * It is specified by the [__join__] section of the fields.ini file or the __join__() 
	 * method of the delegate class.
	 *
	 * It is much like a one-to-one relationship.  The key difference
	 * between a join record and a related record is that a join record 
	 * is assumed to be one-to-one, and an extra tab is added to the edit form 
	 * to edit a join record.
	 *
	 *
	 */
	function getJoinRecord($tablename, $nullIfNotFound=false){
		$table =& Dataface_Table::loadTable($tablename);
		$query = $this->getJoinKeys($tablename);
		foreach ( $query as $key=>$val ){
			$query[$key] = '='.$val;
		}
		
		$record = df_get_record($tablename, $query);
		if ( !$record ){
			if ( $nullIfNotFound ) return null;
			// No record was found, so we create a new one.
			$record = new Dataface_Record($tablename, array());
			foreach ( $query as $key=>$value){
				$record->setValue($key, substr($value,1));
			}
		}
		return $record;
		
	}
	
	
	/**
	 * @brief Gets the keys that are necessary to exist in a join record for the given
	 * table.
	 *
	 * @param string $tablename The name of the join table.
	 *
	 * @return array An associative array of key value pairs.  This is essentially
	 * 		the values for the primary key of the join record in question.
	 *
	 * @section Example
	 * @code
	 *	// Table: Persons(PersonID, Name, SSN)  PKEY (PersonID)
	 *	// Table: Authors(AuthorID, AuthorCategory, Description) PKEY (AuthorID)
	 *  // Suppose AuthorID is a foreign key for PersonID (1-to-1).
	 *  // In the fields.ini file we have:
	 *  // [__join__]
	 *	// Authors=Author Details
	 *
	 *	$person = df_get_record('Persons', array('PersonID'=>10));
	 *  $authorKeys = $person->getJoinKeys('Authors');
	 *	print_r($authorKeys);
	 *		// array( 'AuthorID'=>10)
	 * @endcode
	 */
	function getJoinKeys($tablename){
		$table =& Dataface_Table::loadTable($tablename);
		$query = array();
		
		$pkeys1 = array_keys($this->_table->keys());
		$pkeys2 = array_keys($table->keys());
		
		if ( count($pkeys1) != count($pkeys2) ){
			return PEAR::raiseError("Attempt to get join record [".$this->_table->tablename."] -> [".$table->tablename."] but they have a different number of columns as primary key.");
		}
		
		for ($i =0; $i<count($pkeys1); $i++ ){
			$query[$pkeys2[$i]] = $this->strval($pkeys1[$i]);
		}
		
		return $query;
	
	}
	
	/**
	 * @brief Returns the list of tabs that are to be used in the edit form for this record.
	 *
	 * @return array Array of the tabs to be used in this record.
	 * @since 0.8
	 *
	 * @see Dataface_Table::tabs()
	 */
	function tabs(){
		return $this->_table->tabs($this);
	}
	
	// @}
	// END TAB Management
	
	
	//---------------------------------------------------------------------------
	// @{
	/**
	* @name Permissions
	* Methods that deal with the record permissions.
	*/
	
	
	
	
	/**
	 * @brief Returns an array of the roles assigned to the current user with respect
	 * to this record.
	 *
	 * @param array $params Extra parameters.  See getPermissions() for possible
	 *		values.
	 * @return array of strings
	 *
	 *
	 *
	 * @since 0.8
	 *
	 * @see getPermissions()
	 * @see getRolePermissions()
	 * @see checkPermission()
	 */
	function getRoles($params=array()){
		return $this->_table->getRoles($this, $params);
		
	}
	
	/**
	 * @brief Returns permissions as specified by the current users' roles.  This differs
	 * from getPermissions() in that getPermissions() allows the possibility of
	 * defining custom permissions not associated with a user role.
	 * @param array $params See getPermissions() for possible values.
	 */
	function getRolePermissions($params=array()){
		return $this->_table->getRolePermissions($this, $params);
	}
	
	
	/**
	 * Gets the permissions associated witha  field.  Permissions are returned
	 * as an associative array whose keys are the permissions names, where a 
	 * permission is granted only if a key by its name exists and evaluates to
	 * true.
	 * @param array $params (Optional) Associative array with keys to target the method toward a particular field or relationship.  The possible keys are as follows:
	 *  @code
	 *  array(
	 *      field => <string>         // The name of a field to return permissions for.
	 *      relationship => <string>  // The relationship name to return permissions for.
	 *      fieldmask => <array>      // Permissions mask to apply to field permissions.
	 *      recordmask => <array>     // Permissions mask to apply to record permissions.
	 *  )
	 *  @endcode
	 * @return array Associative array of permissions granted.  The format should look something
	 *  like:
	 * @code
	 * array(
	 *     view => 1,
	 *     edit => 0,
	 *     delete => 0
	 * )
	 * @endcode
	 *
	 * @since 0.5
	 *
	 * @section generating Generating Permissions Arrays
	 *
	 * Normally you would use the Dataface_PermissionsTool class to generate permissions masks
	 * for you based on roles.  Dataface_PermissionsTool provides the following static convenience
	 * methods to generate commonly used permissions sets for you:
	 *
	 * <table>
	 * 	<tr>
	 *		<th>Method</th>
	 *		<th>Description</th>
	 *	</tr>
	 *	<tr>
	 *		<td> Dataface_PermissionsTool::ALL() </td>
	 *		<td> Returns associative array with all permissions granted. </td>
	 *  </tr>
	 *  
	 *  <tr>
	 *		<td>Dataface_PermissionsTool::NO_ACCESS()</td>
	 *		<td>Returns associative array with all permissions explicitly denied.</td>
	 *  </tr>
	 *  <tr>
	 *		<td>Dataface_PermissionsTool::READ_ONLY()</td>
	 *		<td>Returns associative array with only those permissions in the READ ONLY role granted.  It does not explicitly deny access
	 *			to other permissions so this method should not be used for field-level permissions for the purpose of denying access.
	 *		</td>
	 *	</tr>
	 *	<tr>
	 *		<td>Dataface_PermissionsTool::getRolePermissions()</td>
	 *		<td>Returns associative array of permissions granted for the specified role.</td>
	 *	</tr>
	 * </table>
	 *
	 *
	 *
	 * @section Flowchart
	 *
	 *
	 * The following flowchart shows the flow of control Xataface uses to determine the
	 * record-level permissions for a record.  (<a href="http://media.weblite.ca/files/photos/Xataface_Permissions_Flowchart.png" target="_blank">click here to enlarge</a>):
	 * <img src="http://media.weblite.ca/files/photos/Xataface_Permissions_Flowchart.png?max_width=640"/>
	 * 
	 *
	 * The following flowchart shows the flow of control Xataface uses to determine the field-level permissions for a field in a record.
	 *
	 * <img src="http://media.weblite.ca/files/photos/Xataface_Field-level_Permissions_Flowchart.png?max_width=640"/>
	 * <a href="http://media.weblite.ca/files/photos/Xataface_Field-level_Permissions_Flowchart.png">Click here to enlarge</a>
	 *
	 * @see DelegateClass::getPermissions()
	 * @see DelegateClass::getRoles()
	 * @see ApplicationDelegateClass::getPermissions()
	 * @see ApplicationDelegateClass::getRoles()
	 *
	 * @see Dataface_PermissionsTool
	 * @see Dataface_Table::getPermissions()
	 * @see Dataface_Table::getFieldPermissions()
	 * @see Dataface_Table::getRelationshipPermissions()
	 * @see http://www.xataface.com/wiki/permissions.ini_file
	 * @see http://www.xataface.com/documentation/tutorial/getting_started/permissions
	 */
	function getPermissions($params=array()){
		$params['record'] =& $this;
		return $this->_table->getPermissions($params);
	}
	
	/**
	 * @brief Checks to see is a particular permission is granted in this record.
	 *
	 * @param $perm The name of a permission.
	 * @param $params Associative array of parameters:
	 *  		field: The name of a field to check permissions on.
	 * 			relationship: The name of a relationship to check permissions on.
	 * @return boolean whether permission is granted on the current record.
	 */
	function checkPermission($perm, $params=array()){
		$perms = $this->getPermissions($params);
		return ( isset($perms[$perm]) and $perms[$perm] );
	}
	
	
	
	// @}
	// END Permissions
	//----------------------------------------------------------------------------------
	
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
	 * @param array &$param An out parameter.  Optionally allows the method to set an
	 *	error message.  If an error message is provided, it would be provided as the 'message'
	 *  key of the out parameter. E.g.
	 * @code
	 * if ( !$record->validate('myfield', 'myvalue', $params){
	 *     echo "Message was ".$params['message'];
	 * }
	 * @endcode
	 * 
	 * @return boolean True if the value is valid.  False otherwise.
	 *
	 * @see DelegateClass::fieldname__validate()
	 *
	 *
	 */
	function validate( $fieldname, $value, &$params){
		//if ( func_num_args() > 2 ){
		//	$params =& func_get_arg(2);
		//}
		//else {
		//	$params = array();
		//}
		
		if ( !is_array($params) ){
			$params = array('message'=> &$params);
		}
		$res = $this->_table->validate($fieldname, $value, $params);
		
		$field =& $this->_table->getField($fieldname);
		
		if ( $field['widget']['type'] == 'file' and @$field['validators']['required'] and is_array($value) and $this->getLength($fieldname) == 0 and !is_uploaded_file(@$value['tmp_name'])){
				// This bit of validation operates on the upload values assuming the file was just uploaded as a form.  It assumes
				// that $value is of the form
				//// eg: array('tmp_name'=>'/path/to/uploaded/file', 'name'=>'filename.txt', 'type'=>'image/gif').
				$messageStr = "%s is a required field";
                                $params['message'] = sprintf(
                                        df_translate('Field is a required field', $messageStr),
                                        $fieldname
                                );
                                //$params['message'] = "$fieldname is a required field.";
				//$params['message_i18n_id'] = "Field is a required field";
				//$params['message_i18n_params'] = array('field'=>$fieldname);
				return false;
		}
		if ( $res ){
			$delegate =& $this->_table->getDelegate();
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
		
		if ($res){
			$parent =& $this->getParentRecord();
			if ( isset($parent) and $parent->_table->hasField($fieldname) ){
				$res = $parent->validate($fieldname, $value, $params);
			}
		}
		
		return $res;
		
		
	}
	

	/**
	 * @brief Obtains a reference to the Dataface_Record object that holds the parent 
	 * of this record (in terms of table heirarchy).  
	 *
	 * Tables can extend other tables using the __isa__ property of the fields.ini
	 * file.
	 *
	 * @see Dataface_Table::getParent();
	 * @returns Dataface_Record The parent record of this record (by the __isa__ hierarchy).
	 *
	 */
	function &getParentRecord(){
		if ( !isset($this->_parentRecord) ){
			$parent =& $this->_table->getParent();
			if ( isset($parent) ){
				$this->_parentRecord = new Dataface_Record($parent->tablename, array());
				foreach ( array_keys($parent->keys()) as $key ){
					$this->_parentRecord->addPropertyChangeListener( $key, $this);
				}
			}	
		}
		return $this->_parentRecord;
	
	}
	
	
	//------------------------------------------------------------------------------------
	// @{ 
	/**
	 * @name transactions Transaction Support
	 */
	
	
	/**
	 * @brief Signifies that we are beginning a transaction.  So a snapshot of the values
	 * can be saved and possibly be later reverted.
	 *
	 * @return Dataface_Record Self for chaining.
	 *
	 * @see clearFlags()
	 */
	function setSnapshot(){
		 
		$this->clearFlags();
		if ( isset($this->_values) ){
			// If there are no values, then we don't need to set the snapshot
			$this->_oldValues = $this->getValues();
		}
		$parent =& $this->getParentRecord();
		if ( isset($parent) ){
			$parent->setSnapshot();
		}
		
		return $this;
		
	}
	
	/**
	 * @brief Indicates whether a snapshot of values exists.
	 * 
	 * @return boolean True if a snapshot has been created.
	 *
	 * @see setSnapshot()
	 *
	 */
	function snapshotExists(){
		return (is_array($this->_oldValues) and count($this->_oldValues) > 0);
	}
	
	/**
	 * @brief Clears a snapshot of values.  Note that for an update to take place 
	 * properly, a snapshot should be obtained before any changes are made to the 
	 * Table schema.
	 *
	 * @return Dataface_Record Self for chaining.
	 * 
	 */
	function clearSnapshot(){
		$this->_oldValues = null;
		$parent =& $this->getParentRecord();
		if ( isset($parent) ){
			$parent->clearSnapshot();
		}
		return $this;
	}
	
	/**
	 * @brief Returns the snapshot values for this table.  These are copies of the values
	 * as they appeared the last time a snapshot was taken.
	 *
	 * @param array $fields A list of field names for which the snapshot should be returned.  If omitted
	 *	this should return all fields.
	 * @return array Key value pairs of the snapshots as an associative array.
	 *
	 */
	function &getSnapshot($fields=''){
		if ( is_array($fields) ){
			$out = array();
			foreach ($fields as $field){
				if ( isset( $this->_oldValues[$field] ) ){
					$out[$field] = $this->_oldValues[$field];
				}
			}
			
			return $out;
		} else {
			return $this->_oldValues;
		}
	}
	
	/**
	 * @brief Returns snapshots of only the primary key fields in this record.
	 * @return array Associative array of snapshot values of the primary key fields.
	 *
	 * @see getSnapshot()
	 * @see setSnapshot()
	 * @see clearSnapshot()
	 */
	function snapshotKeys(){
		return $this->getSnapshot(array_keys($this->_table->keys()));
	}
	
	/**
	 * @brief Indicates whether a value in the record has been updated since the flags have been cleared.
	 * @param string $fieldname The name of the field we are checking
	 * @param int $index Either the integer index of the record we are checking or an array query to match a record.
	 * @param boolean $checkParent Flag to indicate whether we should also check the parent
	 *		record.
	 * @return boolean True if the value of the specified field has changed since the last
	 *  snapshot was taken.
	 * @see recordChanged()
	 *
	 */
	function valueChanged($fieldname, $index=0, $checkParent=false){
		if ( strpos($fieldname, '.') !== false ){
			// This is a related field, so we have to check the relationship for dirty flags
			$path = explode('.', $fieldname);
			
			if ( is_array($index) ){
				$index = $this->getRelatedIndex($index);
			}
			
			return (isset( $this->_relatedDirtyFlags[$path[0]]) and 
					isset( $this->_relatedDirtyFlags[$path[0]][$path[1]]) and 
					$this->_relatedDirtyFlags[$path[0]][$path[1]] === true );
		} else {
			// this is a local related field... just check the local dirty flags array.
			if ( $checkParent ){
				$parent =& $this->getParentRecord();
				if ( isset($parent) and $parent->_table->hasField($fieldname) ){
					return $parent->valueChanged($fieldname, $index);
				}
			}
			return (@$this->_dirtyFlags[$fieldname]);
		}
	}
	
	
	/**
	 * @brief Boolean indicator to see whether the record has been changed since its flags were last cleared.
	 *
	 * @param boolean $checkParent A boolean flag to tell whether we should check the parent record also.
	 *
	 * @return boolean True if any of the fields in the record have changed since the last snapshot.
	 * @see valueChanged()
	 */
	function recordChanged($checkParent=false){
		if ($checkParent){
			$parent =& $this->getParentRecord();
			if ( isset($parent) ){
				$res = $parent->recordChanged();
				if ( $res ) return true;
			}
		}
		
		$fields =& $this->_table->fields();
		foreach ( array_keys( $fields) as $fieldname){
			if ( $this->valueChanged($fieldname) ) return true;
		}
		return false;
	}
	
	
	
	/**
	 * @brief Clears all of the dirty flags to indicate that this record is up to date.
	 * @return Dataface_Record Self for chaining.
	 *
	 * @see clearSnapshot()
	 * 
	 */
	function clearFlags(){
		$keys = array_keys($this->_dirtyFlags);
		foreach ( $keys as $i) {
			$this->_dirtyFlags[$i] = false;
			$this->vetoFields[$i] = false;
		}
		foreach (array_keys($this->_relatedDirtyFlags) as $rel_name){
			foreach ( array_keys($this->_relatedDirtyFlags[$rel_name]) as $field_name){
				$this->_relatedDirtyFlags[$rel_name][$field_name] = false;
			}
		}
		
		// Clear the snapshot of old values.
		$this->clearSnapshot();
		
		$parent =& $this->getParentRecord();
		if ( isset($parent) ){
			$parent->clearFlags();
		}
		return $this;
		
	}
	
	/**
	 * Clears the dirty flag on a particular field.
	 *
	 * @param string $name The name of the field whose dirty flag we are clearing.
	 * @return Dataface_Record Self for chaining.
	 *
	 * @see clearFlags()
	 * @see clearSnapshots()
	 * @see setSnapshot()
	 * @see valueChanged()
	 */
	function clearFlag($name){
		if ( strpos($name, '.') !== false ){
			// This is a related field.  We store dirty flags in the relationship array.
			$path = explode('.', $name);
			
			if ( !isset($this->_relatedDirtyFlags[$path[0]]) ){
				return;
			}
			if ( !isset($this->_relatedDirtyFlags[$path[0]][$path[1]]) ){
				return;
			}
			$this->_relatedDirtyFlags[$path[0]][$path[1]] = false;
		} else {
			
			$this->_dirtyFlags[$name] = false;
			$this->vetoFields[$name] = false;
			$parent =& $this->getParentRecord();
			if ( isset($parent) and $parent->_table->hasField($name) ){
				$parent->clearFlag($name);
			}
		}
		
		return $this;
	}
		
	
	/**
	 * @brief Sets a dirty flag on a field to indicate that it has been changed.
	 *
	 * @param string $fieldname The name of the field to set the flag on.
	 * @param int $index For related fields marks the index of the related record to set.
	 * @return Dataface_Record Self for chaining.
	 *
	 * @see clearFlag()
	 * @see setSnapshot()
	 *
	 */
	function setFlag($fieldname, $index=0){
		if ( strpos($fieldname, '.') !== false ){
			// This is a related field.  We store dirty flags in the relationship array.
			$path = explode('.', $fieldname);
			
			
			if ( !isset($this->_relatedDirtyFlags[$path[0]]) ){
				$this->_relatedDirtyFlags[$path[0]] = array();
			}
			$this->_relatedDirtyFlags[$path[0]][$path[1]] = true;
		} else {
			// This is a local field
			$this->_dirtyFlags[$fieldname] = true;
			$parent =& $this->getParentRecord();
			if ( isset($parent) and $parent->_table->hasField($fieldname)){
				$parent->setFlag($fieldname, $index);
			}
		}
	}
	
	
	/**
	 * @brief Boolean value indicating if a particular field is loaded.
	 * 
	 * @param string $fieldname The name of the field to check.
	 * @return boolean True if the field is loaded. False otherwise.
	 *
	 */
	function isLoaded($fieldname){
		$parent =& $this->getParentRecord();
		if ( isset($parent) and $parent->_table->hasField($fieldname) ){
			return $parent->isLoaded($fieldname);
		}
		return ( isset( $this->_isLoaded[$fieldname] ) and $this->_isLoaded[$fieldname]);
	}
	
	
	// @}
	// END OF TRANSACTIONS 
	//-----------------------------------------------------------------------------------------
		
	/**
	 * @brief Sometimes a link is specified to be associated with a field.  These links
	 * will be displayed on the forms next to the associated field.
	 * Links may be specified in the fields.ini file with a "link" attribute; or
	 * in the delegate with the $fieldname__link() method.
	 *
	 * @deprecated See Dataface_Record::getLink()
	 */
	function getLink($fieldname){
		
		$field =& $this->_table->getField($fieldname);
		if ( PEAR::isError($field) ){
			return null;
		}
		$table =& Dataface_Table::loadTable($field['tablename']);
		$delegate =& $table->getDelegate();
		if ( !$table->hasField($fieldname) ) return null;
		
		
		
		// Case 1: Delegate is defined -- we use the delegate's link
		if ( method_exists($delegate, $fieldname."__link") ){
			$methodname = $fieldname."__link";
			$link = $delegate->$methodname($this);
			//$link = call_user_func(array(&$delegate, $fieldname."__link"), $this);
			
		
		// Case 2: The link was specified in an ini file.
		} else if ( isset($field['link']) ){
			
			$link = $field['link'];
			
		// Case 3: The link was not specified
		} else {
			
			$link = null;
		}
		
		
		if ( is_array($link) ){
			foreach ( array_keys($link) as $key){
				$link[$key] = $this->parseString($link[$key]);
			}
			
			
			return $link;
			
		} else if ( $link  ){
			return $this->parseString($link);
		} else {
			
			return null;
		}
	
	}
	
	//-----------------------------------------------------------------------------------
	// @{
	/**
	* @name Record Metadata
	*/
	
	
	
	
	/**
	 * @private
	 */
	function _getSubfield(&$fieldval, $path){
		if ( !is_array($fieldval) ){
			return PEAR::raiseError("_getSubfield() expects its first parameter to be an array.");
		}
		$path = explode(":",$path);
		$temp1 =& $fieldval[array_shift($path)];
		$temp2 =& $temp1;
		while ( sizeof($path) > 0 ){
			unset($temp1);
			$temp1 =& $temp2[array_shift($path)];
			unset($temp2);
			$temp2 =& $temp1;
		}
		return $temp2;
	}
	
	/**
	 * @brief Returns the title of this particular record.
	 *
	 * @param boolean $dontGuess If true then it will disable guessing so that it will
	 *   only return the title if it has been explicitly defined by a delegate class's
	 *   getTitle() method.
	 *
	 * @return string The title of the record.
	 *
	 * @since 0.5
	 *
	 * @section Synopsis
	 *
	 * This method is used throughout Xataface to show the title of a record.  This includes
	 * the heading for the record in details view, identification of the record when shown
	 * in a list, and any other time the record needs to be identified in the user interface.
	 *
	 * The output of this method can be overridden by implementing a getTitle() method in the
	 * table's delegate class.  If no such method is defined, then getTitle() will attempt to
	 * guess which field represents the title of a record.   Generally it will just take
	 * the first varchar field that it finds and use that as the title.
	 *
	 * @section Examples
	 *
	 * Given a table with SQL definition:
	 * @code
	 * CREATE TABLE `people` (
	 *   person_id int(11) not null auto_increment primary key,
	 *   first_name varchar(100),
	 *   last_name varchar(100)
	 * )
	 * @endcode
	 *
	 * If no title column has been explicitly assigned, the first_name field
	 * will be treated as the source of the title because it is the first 
	 * varchar field.
	 *
	 * e.g.
	 * @code
	 * $record->setValues(array(
	 *    'first_name'=>'Steve',
	 *    'last_name'=> 'Hannah'
	 * ));
	 *
	 * echo $record->getTitle(); // 'Steve'
	 * 
	 * // Without guessing
	 * $title = $record->getTitle(); // null
	 * echo isset($title) ? 'Yes':'No'; // 'No'
	 *
	 * @endcode
	 *
	 * In the above example you can see that only the first name is used as the record
	 * title.  It would be more appropriate, in this case, to use the full name (first and last).
	 *  We can achieve this by implementing the getTitle() method in the table delegate class:
	 *
	 * tables/people/people.php:
	 * @code
	 * ...
	 * function getTitle($record){
	 *     return $record->val('first_name').' '. $record->val('last_name');
	 * }
	 * ...
	 * @endcode
	 *
	 * Now the same code as above will yield different output:
	 *
	 * @code
	 * $record->setValues(array(
	 *    'first_name'=>'Steve',
	 *    'last_name'=> 'Hannah'
	 * ));
	 *
	 * echo $record->getTitle(); // 'Steve Hannah'
	 * 
	 * // Without guessing
	 * $title = $record->getTitle(); // 'Steve Hannah'
	 * echo isset($title) ? 'Yes':'No'; // 'Yes'
	 *
	 * @endcode
	 *
	 * @see http://xataface.com/documentation/tutorial/getting_started/delegate_classes
	 * @see http://xataface.com/wiki/titleColumn
	 */
	function getTitle($dontGuess=false){
		if ( !isset($this->_title) ){
			$delegate =& $this->_table->getDelegate();
			$title = null;
			if ( $delegate !== null and method_exists($delegate, 'getTitle') ){
				
				$title = $delegate->getTitle($this);
			} else {
			
				$parent =& $this->getParentRecord();
				if ( isset($parent) ){
					$title = $parent->getTitle(true);
				}
			}
			
			if ( $dontGuess ){
				if ( isset($title) ) $this->_title = $title;
				return $title;
			}
			
			if ( !isset($title) ){	
				$fields =& $this->_table->fields();
				$found_title = false; // flag to specify that a specific title field has been found
									  // declared by the 'title' flag in the fields.ini file.
									  
				foreach (array_keys($fields) as $field_name){
					if ( isset($fields[$field_name]['title']) ){
						$title = $this->display($field_name);
						$found_title = true;
					}
					else if ( !isset($title) and $this->_table->isChar($field_name) ){
						$title = $this->display($field_name );
					}
					if ( $found_title) break;
				}
				
				if ( !isset( $title) ){
                                        $titleStr = "Untitled %s Record";
                                        $title = sprintf(df_translate('Untitled table record', $titleStr), 
                                                $this->_table->getLabel());
					//$title = "Untitled ".$this->_table->getLabel()." Record";
				}
				
			}
			$this->_title = $title;
		}
		
		return $this->_title;
		
	
	}
	
	
	
	/**
	 * @brief Returns a brief description of the record for use in listings and summaries.
	 *
	 * @return string A string description of the record.
	 *
	 * @since 0.8
	 *
	 * @section Synopsis
	 *
	 * This method first checks to see if a getDescription() method has been explicitly 
	 * defined in the delegate class and returns its result if found.  If none is found
	 * it will try to guess which field is meant to be used as a description based on 
	 * various heuristics.  Usually it will just use the first TEXT field it finds and
	 * treat that as a description.
	 *
	 * This method is used throughout Xataface, most notably at the top of the details
	 * view (just below the title) where it displays what is intended to be a record 
	 * summary.
	 *
	 * @see http://www.xataface.com/wiki/Delegate_class_methods
	 * @see http://xataface.com/documentation/tutorial/getting_started/delegate_classes
	 * @see Dataface_Table::getDescriptionField()
	 */
	function getDescription(){
		if ( ($res = $this->callDelegateFunction('getDescription')) !== null ){
			return $res;
		} else if ( $descriptionField = $this->_table->getDescriptionField() ){
			return $this->htmlValue($descriptionField);
		} else {
			return '';
		}
	
	}
	
	/**
	 * @brief Returns a Unix timestamp representing the date/time that this record was
	 * created.
	 *
	 * @return long Unix timestamp marking the creation date of this record.
	 * @since 0.9
	 *
	 * @section Synopsis
	 * 
	 * This method will first check to see if the delegate class defines a method named
	 * getCreated() and return its value.  Failing that, it will try to guess which field
	 * holds the creation date based on various heuristics.  These heuristics involve
	 * looking for date fields with appropriate names.  The field guessing is actually
	 * handled by the Dataface_Table::getCreatedField() method.
	 *
	 * This method is used throughout Xataface to display the creation times of records.
	 *
	 *
	 * @see Dataface_Table::getCreatedField()
	 * @see http://www.xataface.com/wiki/Delegate_class_methods
	 * @see http://xataface.com/documentation/tutorial/getting_started/delegate_classes
	 */
	function getCreated(){
		if ( $res = $this->callDelegateFunction('getCreated') ){
			return $res;
		} else if ( $createdField = $this->_table->getCreatedField() ){
			if ( strcasecmp($this->_table->getType($createdField),'timestamp') === 0 ){
				$date = $this->val($createdField);
				return strtotime($date['year'].'-'.$date['month'].'-'.$date['day'].' '.$date['hours'].':'.$date['minutes'].':'.$date['seconds']);
				
			}
			return strtotime($this->display($createdField));
		} else {
			return '';
		}
	}
	
	/**
	 * @brief Returns the name of the person who created this record (i.e. the author).
	 *
	 * @return string The name or username of the person who created this record.
	 * @since 0.9
	 *
	 * @section Synopsis
	 * 
	 * This method will first check to see if the delegate class implements a method named
	 * getCreator() and return its result.  Otherwise it will try to guess which field 
	 * contains the creator/author information for this record based on heuristics 
	 * (handled by the Dataface_Table::getCreatorField() method).  If it 
	 * cannot find any appropriate field, it will simply return ''.
	 *
	 * This method is used throughout Xataface to display the author of various records.
	 *
	 *
	 * @see http://www.xataface.com/wiki/Delegate_class_methods
	 * @see http://xataface.com/documentation/tutorial/getting_started/delegate_classes
	 * @see Dataface_Table::getCreatorField()
	 */
	function getCreator(){
		if ( ($res = $this->callDelegateFunction('getCreator',-1)) !== -1 ){
			return $res;
		} else if ( $creatorField = $this->_table->getCreatorField() ){
			return $this->display($creatorField);
		} else {
			return '';
		}
	}
	
	
	/**
	 * @brief Returns the last modified time of the record.
	 *
	 * @return long Unix timestamp marking the last time the record was modified.
	 *
	 * @since 0.8
	 *
	 * @section Synopsis
	 *
	 * This method will first check to see if the delegate class implements a method
	 * named getLastModified() and return its result.  If none can be found it will 
	 * attempt to guess which field is used to store the last modified date 
	 * (based on the Dataface_Table::getLastUpdatedField() method).  Otherwise it will
	 * simply return 0.
	 *
	 * This method is used throughout Xataface to mark the modification times of records.
	 *
	 * @see http://www.xataface.com/wiki/Delegate_class_methods
	 * @see http://xataface.com/documentation/tutorial/getting_started/delegate_classes
	 * @see Dataface_Table::getLastUpdatedField()
	 */
	function getLastModified(){
		if ( $res = $this->callDelegateFunction('getLastModified') ){
			return $res;
		} else if ( $lastModifiedField = $this->_table->getLastUpdatedField() ){
			if ( strcasecmp($this->_table->getType($lastModifiedField),'timestamp') === 0 ){
				$date = $this->val($lastModifiedField);
				return strtotime($date['year'].'-'.$date['month'].'-'.$date['day'].' '.$date['hours'].':'.$date['minutes'].':'.$date['seconds']);
				
			}
			$strtime = $this->strval($lastModifiedField);
			if ( $strtime){
				return strtotime($strtime);
			} 
		} 
		
		if ( !isset($this->pouch['__mtime']) ){
			$sql = "select mtime from dataface__record_mtimes where recordhash='".addslashes(md5($this->getId()))."'";
			try {
			    try {
			        $res = df_q($sql);
				} catch ( Exception $ex){
				    Dataface_IO::createRecordMtimes();
				    $res = df_q($sql);
				}
				list($mtime) = mysql_fetch_row($res);
				@mysql_free_result($res);
				$this->pouch['__mtime'] = intval($mtime);
			} catch (Exception $ex){
				error_log("Failed SQL query $sql");
				$this->pouch['__mtime'] = 0;
			}
		}
		return $this->pouch['__mtime'];
	}
	
	
	
	/**
	 * @brief Returns the "body" of a record.
	 *
	 * @return string The body of a record.
	 * @since 0.8
	 * @deprecated
	 *
	 * @section Synopsis
	 *
	 * This method first attempts to call the getBody() method of the delegate
	 * class if one is defined.  If not, it will guess at which field of the table
	 * contains the record body and returns that.  Failing that, it will return
	 * an empty string.
	 *
	 * This method was initially created for use with the RSS feed functionality
	 * but that functionality has since been changed to generate the body in a different
	 * way. Currently there are no parts of the core that rely on this method so
	 * it has been deprecated.
	 *
	 * @see http://www.xataface.com/wiki/Delegate_class_methods
	 * @see http://xataface.com/documentation/tutorial/getting_started/delegate_classes
	 * @see Dataface_Table::getBodyField()
	 */
	function getBody(){
		if ( $res = $this->callDelegateFunction('getBody') ){
			return $res;
		} else if ( $bodyField = $this->_table->getBodyField() ){
			return $this->htmlValue($bodyField);
		} else {
			return '';
		}
	}
	
	/**
	 * @brief Returns the "public" URL to the record.
	 *
	 * @param array $params Supplementary parameters that can be 
	 *  passed through to getURL().
	 * @return string The "public" URL to the record.
	 *
	 * @section Synopsis
	 *
	 * It is often the case that a record may be the subject of a public facing 
	 * page on a website and you want to be able to associate the record with 
	 * this page.  The getURL() method will generally return a URL to the record's 
	 * details view in the back end.  This method, by contrast, is meant to allow you
	 * to link to the public page that features the record.
	 *
	 * This method will first attempt to call the getPublicLink() method defined in 
	 * the delegate class.  If one cannot be found, it will simply call getURL().
	 *
	 * So, in essence, this method is just a wrapper around the getURL() method that
	 * allows you to override its output in the delegate class.  Its relationship to
	 * getURL() is very similar to display()'s relationship to getValue().
	 *
	 * @section usage Current Usage
	 *
	 * Most of the Xataface interface uses the getURL() method directly for linking to records.
	 * However there are some parts, which are meant for public consumption, that use the
	 * getPublicLink() method by default.  This includes the RSS feeds and the full-text site 
	 * search.
	 *
	 * @section Example
	 * @code
	 * echo $record->getURL(); // 'index.php?-table=people&-action=view&person_id=10'
	 * echo $record->getPublicLink(); // 'index.php?-table=people&-action=view&person_id=10'
	 * @endcode
	 *
	 * But we can override getPublicLink() by implementing it in the delegate class.
	 *
	 * tables/people/people.php
	 * @code
	 * ...
	 * function getPublicLink($record){
	 *    return 'http://www.example.com/people/'.rawurlencode($record->val('username'));
	 * }
	 * ...
	 * @endcode
	 *
	 * Now our example becomes:
	 * @code
	 * echo $record->getURL(); // 'index.php?-table=people&-action=view&person_id=10'
	 * echo $record->getPublicLink(); // 'http://www.example.com/people/foobar'
	 * @endcode
	 *
	 * @see http://www.xataface.com/wiki/Delegate_class_methods
	 * @see http://xataface.com/documentation/tutorial/getting_started/delegate_classes
	 * @see getURL()
	 */ 
	function getPublicLink($params=null){
		if ( $res = $this->callDelegateFunction('getPublicLink') ){
			return $res;
		} else {
			return $this->getURL($params);
		}
	
	}
	
	
	/**
	 * @brief Returns an array of parts of the bread-crumbs leading to this record.
	 *
	 * @return array Associative array of the form [Part Label]->[Part URL]
	 * @since 0.6
	 * 
	 * @section Synopsis
	 *
	 * Breadcrumbs are used to display the navigational heirarchy for a record.  This method
	 * is used to build the breadcrumbs that are displayed in the UI on the top bar.
	 *
	 * This method first attempts to call the getBreadCrumbs() method of the delegate
	 * class.  If none is found, it will check its parent record (see getParent())
	 * for breadcrumbs and append itself to the end of its breadcrumbs.
	 *
	 * If no parent is found and no explicit breadcrumbs can be found this will return
	 * some default breadcrumbs involving only the table name and the 'browse' action.
	 *
	 * @section Examples
	 * 
	 * Building breadcrumbs string:
	 *
	 * @code
	 * $base = '';
	 * foreach ( $record->getBreadCrumbs() as $label=>$url){
     *     $base .= ' :: <a href="'.$url.'" id="bread-crumbs-'
     *        .str_replace(' ','_', $label).'">'.$label.'</a>';
	 * }
	 * $base = substr($base, 4);
	 * @endcode
	 *
	 * @see Dataface_SkinTool::bread_crumbs()
	 * @see http://xataface.com/documentation/tutorial/getting_started/delegate_classes
	 * @see http://www.xataface.com/wiki/Delegate_class_methods
	 */
	function getBreadCrumbs(){
		$delegate =& $this->_table->getDelegate();
		if ( $delegate !== null and method_exists($delegate, 'getBreadCrumbs') ){
			return $delegate->getBreadCrumbs($this);
		}
		
		
		if ( ( $parent = $this->getParent() ) !== null ){
			$bc = $parent->getBreadCrumbs();
			$bc[$this->getTitle()] = $this->getURL( array('-action'=>'browse'));
			return $bc;
		}
		
		
		return array(
			$this->_table->getLabel() => Dataface_LinkTool::buildLink(array('-action'=>'list', '-table'=>$this->_table->tablename)), 
			$this->getTitle() => $this->getURL(array('-action'=>'browse'))
			);
	}
	
	/**
	 * @brief Returns the URL to this record.
	 *
	 * @param mixed $params An array or urlencode string of parameters to use when 
	 * building the url.  e.g., array('-action'=>'edit') would cause the URL to be 
	 * for editing this record.
	 *
	 * @return string The URL to the record.
	 *
	 * @since 0.5
	 * 
	 * @section Examples
	 *
	 * @code
	 * // default points to record's browse action
	 * echo $record->getURL(); // index.php?-table=people&-action=browse&person_id=10
	 *
	 * // Custom parameters
	 * echo $record->getURL(array(
	 *     '-action'=>'my_custom_action'
	 * ));
	 *     // index.php?-table=people&-action=my_custom_action&person_id=10
	 * 
	 * // Using urlencoded string parameters instead
	 * echo $record->getURL('-action=my_custom_action');
	 *     // index.php?-table=people&-action=my_custom_action&person_id=10
	 *
	 * @endcode
	 *
	 * @section Permissions
	 *
	 * If secureDisplay is set to true in this record and the 'link' permission
	 * is denied to the current user, this method will look for the delegate 
	 * class's no_access_link() methood to override its output. If the method hasn't
	 * been defined, or the link permission is granted, or secureDisplay is set to false
	 * this method will simply return its normal result.
	 *
	 * @subsection Example
	 * 
	 * A delegate class which denies the 'link' permission and defines
	 * the no_access_link() method.
	 *
	 * tables/people/people.php:
	 * @code
	 * ...
	 * function getPermissions($record){
	 *     $perms = Dataface_PermissionsTool::READ_ONLY();
	 *     $perms['link'] = 0;
	 *     return $perms;
	 * }
	 *
	 * function no_access_link($record, $params=array()){
	 *     return 'no_access.html';
	 * }
	 * ...
	 * @endcode
	 *
	 * Some sample code working with records of our people table:
	 *
	 * @code
	 * echo $record->getURL(); // 'no_access.html'
	 * $record->secureDisplay = false;
	 * echo $record->getURL(); // index.php?-table=people&-action=browse&person_id=10
	 * @endcode
	 *
	 *
	 * @see getPublicLink()
	 * @see secureDisplay
	 * @see df_secure()
	 * @see Dataface_LinkTool::buildLink()
	 */
	function getURL($params=array()){
		if ( is_string($params) ){
			$pairs = explode('&',$params);
			$params = array();
			foreach ( $pairs as $pair ){
				list($key,$value) = array_map('urldecode',explode('=', $pair));
				$params[$key] = $value;
			}
		}
		if ( $this->secureDisplay and !$this->checkPermission('link') ){
			$del =& $this->_table->getDelegate();
			if ( $del and method_exists($del, 'no_access_link')){
				return $del->no_access_link($this, $params);
			} 
		}
		
		$params['-table'] = $this->_table->tablename;
		if ( !isset($params['-action']) ) $params['-action'] = 'browse';
		foreach (array_keys($this->_table->keys()) as $key){
			$params[$key] = '='.$this->strval($key);
		}
		
		$delegate =& $this->_table->getDelegate();
		if ( isset($delegate) and method_exists($delegate, 'getURL') ){
			$res = $delegate->getURL($this, $params);
			if ( $res and is_string($res) ) return $res;
		}
		
		import('Dataface/LinkTool.php');
		//$linkTool =& Dataface_LinkTool::getInstance();
	
		return Dataface_LinkTool::buildLink($params ,false);
	}
	
	
	/**
	 * This returns a unique id to this record.  It is in a format similar to a url:
	 * table?key1=value1&key2=value2
	 * @return string
	 */
	function getId(){
		$keys = array_keys($this->_table->keys());
		$params=array();
		foreach ($keys as $key){
			$params[] = urlencode($key).'='.urlencode($this->strval($key));
		}
		return $this->_table->tablename.'?'.implode('&',$params);
	}
	
	
	// @}
	// END Record Metadata
	//--------------------------------------------------------------------------------------
	
	
	// @{
	/**
	 * @name Field Metadata
	 */
	 
	/**
	 * @brief Gets the mimetime of a blob or container field.
	 *
	 * @param string $fieldname The name of the field to check.
	 * @param integer $index If this is a related field then this is the index offset.
	 * @param string $where If this is a related field this is a where clause to filter the results.
	 * @param string $sort If this is a related field then this is a sort clause to sort the results.
	 * @return string The mimetype of the specified field value.
	 *
	 * @since 0.6
	 */
	function getMimetype($fieldname,$index=0,$where=0,$sort=0){
		$field =& $this->_table->getField($fieldname);
		if ( isset($field['mimetype'])  and strlen($field['mimetype']) > 0 ){
			return $this->getValue($field['mimetype'], $index,$where,$sort);
		}
		
		if ( $this->_table->isContainer($fieldname) ){
			$filename = $this->strval($fieldname,$index,$where,$sort);
			if ( strlen($filename) > 0 ){
				$path = $field['savepath'].'/'.$filename;
				$mimetype='';
				//if(!extension_loaded('fileinfo')) {
				//	@dl('fileinfo.' . PHP_SHLIB_SUFFIX);
				//}
				if(extension_loaded('fileinfo')) {
					$res = finfo_open(FILEINFO_MIME); /* return mime type ala mimetype extension */
					$mimetype = finfo_file($res, $path);
				} else if (function_exists('mime_content_type')) {
					
				
					$mimetype = mime_content_type($path);
					
				}
				
				
				return $mimetype;
			}
		}
		return '';
		
	}
	 
	/**
	 * @brief Checks to see if a container or blob field contains an image.
	 *
	 * @param string $fieldname The name of the field to check.
	 * @param integer $index If this is a related field then this is the index offset.
	 * @param string $where If this is a related field this is a where clause to filter the results.
	 * @param string $sort If this is a related field then this is a sort clause to sort the results.
	 * @return boolean True if the field contains an image.
	 *
	 * @since 0.6
	 * @see getMimetype()
	 * @see http://xataface.com/documentation/how-to/how-to-handle-file-uploads
	 */
	function isImage($fieldname, $index=0, $where=0, $sort=0){
		return preg_match('/^image/', $this->getMimetype($fieldname,$index,$where,$sort));
	
	}
	 
	 
	/**
	 * @brief Gets the length of the value in a particular field.  This can be especially
	 * useful for blob and longtext fields that aren't loaded into memory.  It allows
	 * you to see if there is indeed a value there.
	 *
	 * @param string $fieldname The name of the field to check.
	 * @param integer $index If this is a related field then this is the index offset.
	 * @param string $where If this is a related field this is a where clause to filter the results.
	 * @param string $sort If this is a related field then this is a sort clause to sort the results.
	 * @return integer The length of the specified field's value in bytes.
	 *
	 * @since 0.5
	 *
	 */
	function getLength($fieldname, $index=0, $where=0, $sort=0){
		if ( strpos($fieldname, '.') !== false ){
			
			list($relname, $localfieldname) = explode('.',$fieldname);
			$record =& $this->getRelatedRecords($relname, false, $index, null, $where, $sort);
			$relatedRecord = new Dataface_RelatedRecord($this, $relname,$record);
			$relatedRecord->setValues($this->_relatedMetaValues[$relname][0][0][$index]);
			return $relatedRecord->getLength($localfieldname);
			//$key = '__'.$localfieldname.'_length';
			//if ( isset($record[$key]) ){
			//	return $record[$key];
			//} else {
			//	return null;
			//}
		} else {
			$key = '__'.$fieldname.'_length';
			if ( isset($this->_metaDataValues[$key] ) ){
				return $this->_metaDataValues[$key];
			} else {
				return strlen($this->getValueAsString($fieldname));
			}
		}
		
	}
	
        /**
         * @brief Gets the group role metadata for this record as an Object.
         * @return \StdClass An object tree that contains all of the role
         * assignments of this record for each user and group.
         * 
         * The structure would be something like:
         * <code>
         * Object(
         *     users => Object(
         *          shannah => Array('ROLE1','ROLE2',etc..),
         *          user2 => Array('Role3', 'ROLE2', etc..),
         *          etc ...
         *      ),
         *      groups => Object(
         *          1 => Array('ROLE1', 'ROLE2', etc...),
         *          57 => Array('ROLE3, 'ROLE5', etc...),
         *          etc...
         *      )
         * ) 
         * </code>
         */
        function getGroupRoleMetadata(){
            $perms = $this->_metaDataValues['__roles__'];
	    if ( !trim($perms) ){
	        
	        return null;
	    }
            return json_decode($perms);
        }
        
        /**
         * @brief Gets the roles that are assigned to the currently logged-in
         * user using the group_permissions module.
         * @return String[] An array of role names assigned to this user.
         */
	function getGroupRoles(){
	    if ( isset($this->pouch['__roles__']) ){
	        return $this->pouch['__roles__'];
	    }
	    
	    if ( !$this->table()->hasField('__roles__') ){
	        return null;
	    }
	    $perms = $this->_metaDataValues['__roles__'];
	    if ( !trim($perms) ){
	        $this->pouch['__roles__'] = array();
	        return null;
	    }
	    
	    $perms = json_decode($perms);
	    if ( !$perms ){
	        $this->pouch['__roles__'] = array();
	        return null;
	    }
	    
	    if ( !class_exists('Dataface_AuthenticationTool') ){
	        $this->pouch['__roles__'] = array();
	        return null;
	    }

	    $authTool = Dataface_AuthenticationTool::getInstance();
	    
	    
	    // Todo get user groups
	    $groups = $authTool->getUserGroupNames();
	        
	    $userName = $authTool->getLoggedInUserName();
	    if (!$userName ){
	        $userName = 'Anonymous';
	    }
	    
	    $roles = array();
	    
	    foreach ( $groups as $group ){
	        if ( isset($perms->groups) and is_array(@$perms->groups->{$group}) ){
	            foreach ( $perms->groups->{$group} as $groupRole){
	                $roles[] = $groupRole;
	            }
	        }
	    }
	    
	    if ( isset($perms->users) and is_array(@$perms->users->{$userName}) ){
	        foreach ( $perms->users->{$userName}  as $userRole ){
	            $roles[] = $userRole;
	        }
	    }
	    $this->pouch['__roles__'] = $roles;
	    return $roles;
	    
	}
	 
	 
	 // @}
	 // END Field Metadata
	 //--------------------------------------------------------------------------------------
	
	
	
	
	//-------------------------------------------------------------------------------------
	// @{
	/**
	* @name IO Methods
	* Methods for reading and writing records to and from the database.
	*/
	
	
	/**
	 * @brief Saves the current record to the database.
	 *
	 * @param string $lang The 2-digit language code of the language for which to save the record to.  Defaults to the value Dataface_Application::_conf['lang']
	 * @param boolean $secure Whether to check permissions before saving.  If it fails it will return 
	 *  a PEAR::Error object.
	 *
	 * @return mixed True on success.  PEAR_Error object on fail.
	 *
	 */
	function save($lang=null, $secure=false){
		if ( !isset($lang) ) $lang = $this->lang;
		return df_save_record($this, $this->strvals(array_keys($this->_table->keys())), $lang, $secure);
	}
	
	/**
	 * @brief Deletes the record from the database.
	 *
	 * @param boolean $secure Whether to check permissions before saving.
	 *
	 * @return mixed True on success.  PEAR_Error object on fail.
	 */
	function delete($secure=false){
		import('Dataface/IO.php');
		$io = new Dataface_IO($this->_table->tablename);
		return $io->delete($this, $secure);
		
	}
	
	
	// @}
	// END IO Methods
	//---------------------------------------------------------------------------------------
	
	function toJS($fields=null, $override = array()){
		$strvals = $this->strvals($fields);
		$out = array();
		foreach ( $strvals as $key=>$val){
			if ( $this->checkPermission('view', array('field'=>$key)) ){
				$out[$key] = $val;
				
			}
		}
		$out['__title__'] = $this->getTitle();
		//$out[] = "'__title__': '".addslashes($this->getTitle())."'";
		$out['__url__'] = $this->getURL();
		//$out[] = "'__url__': '".addslashes($this->getURL())."'";
		$out['__expandable'] = ($this->checkPermission('expandable')?1:0);
		//$out[] = "'__expandable__': ".($this->checkPermission('expandable')?1:0);
		
		foreach ($override as $k=>$v){
			$out[$k] = $v;
		}
		
		return json_encode($out);
		//return '{'.implode(',',$out).'}';
		
	}
	
	function getStdObject(){
		return new Dataface_Record_StdClass($this);
	}

	
	
	
	
	
}

/**
 * An iterator for iterating through Record objects.
 */
class Dataface_RecordIterator {

	var $_records;
	var $_keys;
	var $_tablename;
	function Dataface_RecordIterator($tablename, &$records){
		$this->_records =& $records;
		$this->_keys = array_keys($records);
		$this->_tablename = $tablename;
		$this->reset();
	}
	
	function &next(){
		$out = new Dataface_Record($this->_tablename, $this->_records[current($this->_keys)]);
		next($this->_keys);
		return $out;
	}
	
	function &current(){
		return new Dataface_Record($this->_tablename, $this->_records[current($this->_keys)]);
	}
	
	function reset(){
		return reset($this->_keys);
	}
	
	function hasNext(){
		
		return (current($this->_keys) !== false);
	}
	
}


/**
 * An iterator for iterating through related records.
 */
class Dataface_RelationshipIterator{
	var $_record;
	var $_relationshipName;
	var $_records;
	var $_keys;
	var $_where;
	var $_sort;
	function Dataface_RelationshipIterator(&$record, $relationshipName, $start=null, $limit=null, $where=0, $sort=0){
		$this->_record =& $record;
		$this->_relationshipName = $relationshipName;
		$this->_where = $where;
		$this->_sort = $sort;
		if ( $start !== 'all' ){
		
			$this->_records =& $record->getRelatedRecords($relationshipName, true, $start, $limit, $where, $sort);
		} else {
			$this->_records =& $record->getRelatedRecords($relationshipName, 'all',$where, $sort);
		}
		if ( is_array($this->_records) ){
			$this->_keys = array_keys($this->_records);
		} else {
			$this->_keys = array();
		}
	}
	
	function &next(){
		$out =& $this->current();
		next($this->_keys);
		return $out;
	}
	
	function &current(){
		$rec = new Dataface_RelatedRecord($this->_record, $this->_relationshipName, $this->_records[current($this->_keys)]);
		$rec->setValues($this->_record->_relatedMetaValues[$this->_relationshipName][$this->_where][$this->_sort][current($this->_keys)]);
		return $rec;
	}
	
	function reset(){
		return reset($this->_keys);
	}
	
	function hasNext(){
		return (current($this->_keys) !== false);
	}
}


class Dataface_Record_StdClass extends StdClass {
	private $r;
	
	public function __construct(Dataface_Record $r){
		$this->r = $r;
	}
	
	public function __get($name){
		return $this->r->val($name);
	}
	
	public function __set($name, $value){
		$this->r->setValue($name, $value);
	}
	
	public function __unset($name){
		$this->r->setValue($name, null);
	}
	
	
}

