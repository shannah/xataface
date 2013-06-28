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
 * File:	Dataface/RemoveRelatedRecordForm.php
 * Author:	Steve Hannah <shannah@sfu.ca>
 * Created:	November 2005
 *
 * Description:
 * -------------
 *
 * An HTML form to remove a set of records from a relationship.  It also has an option
 * to remove the records from the database.
 */
require_once 'Dataface/IO.php';
require_once 'HTML/QuickForm.php';


/**
 * @ingroup formsAPI
 */
class Dataface_RemoveRelatedRecordForm extends HTML_QuickForm {
	
	/*
	 * Reference to parent record.
	 * @type Dataface_Record
	 */
	var $_record;
	
	/*
	 * The relationship name.
	 * @type string
	 */
	var $_relationshipName;
	
	/*
	 * Reference to the relationship object.
	 * @type Dataface_Relationship
	 */
	var $_relationship;
	
	/*
	 * Array of urlencoded key/value strings representing records to be deleted.
	 * @type array(string)
	 */
	//var $_toBeRemoved;
	
	/*
	 * Flag to indicate if the form is built.
	 * @type boolean
	 */
	var $_isBuilt = false;
	
	var $query;
	
	/**
	 * Will store an array of the selected records.
	 * @see getSelectedRecords()
	 * @var array
	 */
	var $records;
	
	/**
	 * Will store a reference to the domain table of the relationship.
	 * @var Dataface_Table
	 * @see getDomainTable()
	 */
	var $_domainTable;
	
	/**
	 * Flag to indicate whether the user can delete the related records.
	 * If this is false, then the user can only remove the related records,
	 * but not delete.
	 * @var boolean
	 * @see allowDeleteRecords()
	 */
	var $allowDelete;
	
	/**
	 * Constructor
	 *
	 * @param record Reference to record object.  If left null or blank, the proper record will be loaded
	 *			using GET and POST parameters.
	 *			@type Dataface_Record
	 * @param relationshipName The name of the relationship from which records will be removed.
	 * @param array of urlencoded strings of the form key1=value1&key2=value2& ... etc..
	 *				where keyi is the ith key in the related record.
	 */
	function Dataface_RemoveRelatedRecordForm(&$record, $relationshipName, $query=null){
		if ( !isset($query) ){
			$app =& Dataface_Application::getInstance();
			$this->query = $app->getQuery();
			unset($query);
			$query =& $this->query;
		} else {
			$this->query =& $query;
		}
		
		if ( !$record ||  !isset($record)){
			$record =& $this->getRecord(); //df_get_record($query['-table'], $query);
		}
		$this->_record =& $record;
		$this->_relationshipName = $relationshipName;

		$this->_relationship = $record->_table->getRelationship($relationshipName);
		//$this->_toBeRemoved = $toBeRemoved;
		$this->HTML_QuickForm('RemoveRecord');
		
		
	}
	
	/**
	 * 
	 * Indicates whether or not form can be submitted.  This can be called in static context 
	 * also which makes it usable in the constructor.
	 *
	 * @return boolean True if form has been submitted. False otherwise.
	 */
	function formSubmitted(){
		return ( isset( $_POST['--__keys__']) and isset( $_POST['-table']) );
	}
	
	/**
	 * 
	 * Loads and returns the Dataface_Record object that is specified by the current
	 * POST and GET variables.  This may be called in static context which makes it
	 * useful in the constructor.
	 *
	 * @return Dataface_Record object
	 */
	function &getRecord(){
		$app =& Dataface_Application::getInstance();
		$rec =& $app->getRecord();
		return $rec;
		
	}
	
	function allowDeleteRecords(){
		if ( !isset($this->allowDelete) ){
			$records =& $this->getSelectedRecords();
			$domainTable =& $this->getDomainTable();
			// We are allowed to delete the records if for each record in the 
			// set, we have either:
			// 1. Delete permission on the domain record.
			// 2. delete related record permission on the parent record
			$this->allowDelete = true;
			foreach ( $records as $record ){
				$domainRecord =& $record->toRecord($domainTable->tablename);
				if ( !$record->_record->checkPermission('delete related record', array('relationship'=>$this->_relationshipName)) and 
					 !$domainRecord->checkPermission('delete') ){
					 $this->allowDelete = false;
				}
			}
		}
		return $this->allowDelete;
	}
	
	/**
	 * Indicates whether this removal *REQUIRES* a delete to occur.
	 * For one to many relationships, deletion is required.
	 * For many-to-many relationships, deletion is not required.
	 */
	function deleteRequired(){
		return ( $this->_relationship->getDistance( $this->_relationship->getDomainTable() )  == 1);
	}
	
	/**
	 * 
	 * Builds the form.
	 *
	 */
	function _build(){
		if ( $this->_isBuilt ) return;
		$keys = array_keys($this->_record->_table->keys());
		$factory = new HTML_QuickForm('factory');
		$keyEls = array();
		foreach ($keys as $key){
			$keyEls[] =& $factory->addElement('hidden', $key);
			
		}
		$this->addGroup($keyEls, '--__keys__');
		$this->addElement('hidden', '--selected-ids');
		$this->addElement('hidden', '-table');
		$this->addElement('hidden', '-relationship');
		$this->addElement('hidden', '-queryString');
		$this->addElement('hidden', '-action');
		
		if ( $this->allowDeleteRecords() and !$this->deleteRequired()){
			$this->addElement('checkbox', 'delete',df_translate('scripts.Dataface.RemoveRelatedRecordForm._build.LABEL_ALSO_DELETE_FROM_DB','Also delete record(s) from database?'));
		} else {
			$this->addElement('hidden', 'delete');
		}
		$this->addElement('hidden','-confirm_delete_hidden','1');
		$this->addElement('submit','-confirm_delete',df_translate('scripts.Dataface.RemoveRelatedRecordForm._build.LABEL_REMOVE',"Remove"));
		
		
		
		$this->setDefaults(array(
			'--__keys__'=>$this->_record->getValues(array_keys($this->_record->_table->keys())),
			'-table'=>$this->_record->_table->tablename,
			'-relationship' => $this->_relationshipName,
			'--selected-ids'=> $_POST['--selected-ids'],
			'-queryString'=> $_SERVER['QUERY_STRING'],
			'-action'=>'remove_related_record'
			));
		if ( $this->deleteRequired() ){
			$this->setDefaults(array('delete'=>1));
		}
		$this->_isBuilt = true;	
			
	
	}
	
	
	/**
	 * Displays the form.
	 */
	function display(){
		
		$this->_build();
		$domainTable = $this->_relationship->getDomainTable();
		if ( PEAR::isError($domainTable) ){
			$domainTable = $this->_relationship->_schema['selected_tables'][0];
		}
		$domainTable = Dataface_Table::loadTable($domainTable);
		$io = new Dataface_IO($domainTable->tablename);
		echo "<p>".df_translate(
				'scripts.Dataface.RemoveRelatedRecordForm.display.MESSAGE_ARE_YOU_SURE',
				"Are you sure you want to remove the following records from the relationship '".$this->_relationshipName."'?",
				array('relationship'=>$this->_relationshipName)
				)
			."</p>";
		echo "<ul>";
		
		$records =& df_get_selected_records($this->query);
		
		foreach ($records as $record){
			
			echo "<li>".$record->getTitle()."</li>\n";
			
			
		}
		echo "</ul>";
			
		parent::display();
	}
	
	/**
	 * Returns an array of the selected records.
	 */
	function &getSelectedRecords(){
		if ( !isset($this->records) ) $this->records = df_get_selected_records($this->query);
		return $this->records;
	}
	
	/**
	 * Returns a reference to the Dataface_Table object encapsulating the 
	 * domain table for this relationship.
	 * @returns Dataface_Table The domain table.
	 */
	function &getDomainTable(){
		if ( !isset($this->_domainTable) ){
			$domainTable = $this->_relationship->getDomainTable();
			if ( PEAR::isError($domainTable) ){
				/*
				 * Dataface_Relationship::getDomainTable() throws an error if there are 
				 * no join tables.  We account for that by explicitly setting the domain
				 * table to the first table in the list.
				 */
				$domainTable = $this->_relationship->_schema['selected_tables'][0];
			}
			$this->_domainTable =& Dataface_Table::loadTable($domainTable);
		}
		return $this->_domainTable;
		
	}
	
	/**
	 * Deletes the appropriate records from the join table and the domain table (if requested).
	 */
	function delete($values){
		
		/*
		 * Next we construct an IO object to write to the domain table.
		 */
		$domainTable =& $this->getDomainTable();
		$domainIO = new Dataface_IO($domainTable->tablename);
		
		$records =& $this->getSelectedRecords();
		$messages = array();
		$confirmations = array();
		$warnings = array();
		$table =& $this->_record->_table; // This is the table of the parent record.
		$io = new Dataface_IO($table->tablename);
		$removePermission = $this->_record->checkPermission('remove related record', array('relationship'=>$this->_relationshipName));
		//if ($removePermission ){
		//	$mask = array('delete'=>1);
		//} else {
		//	$mask = array();
		//}
		
		$deleteRequired = $this->deleteRequired(); // Do we have to delete the domain record
												   // to make the removal effective
		foreach ($records as $record){
			
			// If deletion is required, we will do ou
			$res = $io->removeRelatedRecord($record, @$values['delete'], true /* Use security */);
			if ( PEAR::isError($res) ){
				$warnings[] = $res->getMessage();
			} else {
				$confirmations[] = $confirmations[] = df_translate(
				 		'Successfully deleted record',
				 		"Successfully deleted entry for record '".$record->getTitle()."' in table '".$table->tablename."'",
				 		array('title'=>$record->getTitle(), 'table'=>$table->tablename)
				 		);
			}
			
			
		}
		return array('confirmations'=>$confirmations, "warnings" => $warnings);
		
	
	}
	
	
	


}
