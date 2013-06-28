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
 * File:	Dataface/ExistingRelatedRecordForm.php
 * Author:	Steve Hannah <shannah@sfu.ca>
 * Created: November 2005
 *
 * Description:
 * ------------
 * A form to add existing records to a relationship.  It provides a select list of existing
 * records in the relationship domain, along with extra fields necessary to make the relationship.
 *
 */
require_once 'HTML/QuickForm.php';
require_once 'Dataface/QuickForm.php';
require_once 'HTML/QuickForm/related_select.php';
/**
 * @ingroup formsAPI
 */
class Dataface_ExistingRelatedRecordForm extends HTML_QuickForm {

	/**
	 * Reference to the parent table of the relationship.
	 * @type Dataface_Table
	 */
	var $_parentTable;
	
	/**
	 * Reference to the relationship.
	 * @type Dataface_Relationship
	 */
	var $_relationship;
	
	/**
	 * Name of the relationship.
	 * @type string
	 */
	var $_relationshipName;
	
	var $_quickForm;
	
	/**
	 * Flag to indicate if the form is built yet.
	 * @type boolean
	 */
	var $_built;
	
	/**
	 * Database resource handle.
	 */
	var $_db;
	
	/**
	 * Reference to parent record.
	 * @type Dataface_Record
	 */
	var $_record;
	
	/**
	 * Reference to related record that is being added.
	 * @type Dataface_RelatedRecord
	 */
	var $_relatedRecord;
	
	
	/**
	 * Constructor.
	 * 
	 * @param record The parent record
	 *		@type Dataface_Record
	 *		If this parameter is null or left blank, then the appropriate parent record will
	 *		be automatically loaded based on the POST and GET parameters.
	 * @param relationshipName The name of the relationship.
	 *		@type string
	 * @param db The database resource handle.
	 */
	 function Dataface_ExistingRelatedRecordForm(&$record, $relationshipName, $db=''){
		if ( !$record ){
			$record =& $this->getRecord();
		}
		$tableName = $record->_table->tablename;
		$this->_record = $record;
		$this->_relatedRecord = new Dataface_RelatedRecord($record, $relationshipName);
		$this->_db = $db;
		$this->_relationshipName = $relationshipName;
		$this->_parentTable  =& Dataface_Table::loadTable($tableName, $db);
		$this->_relationship =& $this->_parentTable->getRelationship($relationshipName);
		
		$this->_quickForm = new Dataface_QuickForm($tableName, $db);
		
		$this->_built = false;
		$app =& Dataface_Application::getInstance();
		$this->HTML_QuickForm($tableName.'_'.$relationshipName, 'POST', '','',array('accept-charset'=>$app->_conf['ie']), true);
		
	
	}
	
	
	/**
	 * This can even be called in static context (which makes it callable in the constructor
	 * as a means to load the current record.
	 *
	 * @return boolean  True if the form has been submitted, false otherwise.
	 */
	function formSubmitted(){
		return ( isset( $_POST['__keys__']) and isset( $_POST['-table']) );
	}
	
	
	/**
	 * Loads the parent record based on POST and GET parameters.
	 */
	function &getRecord(){
		if ( Dataface_ExistingRelatedRecordForm::formSubmitted() ){
			$record = new Dataface_Record($_POST['-table'], array());
			$io = new Dataface_IO($_POST['-table']);
			$query = $_POST['__keys__'];
			
			if ( is_array($query) ){
				foreach ( array_keys($query) as $postKey ){
					if ( $query[$postKey]{0} != '=' ){
						$query[$postKey] = '='.$query[$postKey];
					}
				}
			}
			$io->read($query, $record);
			return $record;
		} else {	
			$app =& Dataface_Application::getInstance();
			$qt =& Dataface_QueryTool::loadResult($app->_currentTable);
			return $qt->loadCurrent();
		}
	}
	
	
	/**
	 * Builds the form.
	 */
	function _build(){
		$app =& Dataface_Application::getInstance();
		$mainQuery =& $app->getQuery();
		
		if ( $this->_built ) return true;
		$r =& $this->_relationship->_schema;
		$t =& $this->_parentTable;
		
		$fkCols = $this->_relatedRecord->getForeignKeyValues();
		if ( PEAR::isError($fkCols) ){
			$fkCols->addUserInfo(df_translate('scripts.Dataface.ExistingRelatedRecordForm._build.ERROR_GETTING_FOREIGN_KEY_COLS',"Error getting foreign key columns while building Related Record Form ",array('line'=>0,'file'=>"_")));
			throw new Exception($fkCols->toString(), E_USER_ERROR);

		}
		$factory = new HTML_QuickForm('factory');
		$fkeys = $this->_relationship->getForeignKeyValues();
			// Values of foreign keys (fields involved in where and join clauses)
			
		$table = $this->_relationship->getDomainTable();
			// The name of the table holding related records.
			
		if ( !isset($table) || PEAR::isError($table) ) $table = $r['selected_tables'][0];
			// It is possible for getDomainTable() to return an error if no foreign
			// keys are specified.  In this case, we will just use the table associated
			// with the first selected column.
			
		$relatedTableObject =& Dataface_Table::loadTable($table);
			// The Dataface_Table object for the related records.
			
		$tkey_names = array_keys($relatedTableObject->keys());
			// The names of the key fields for the related record.
			// The main table that holds the related records
			
		$options = $this->_relationship->getAddableValues($this->_record);
		if ( !$options ) return PEAR::raiseError('There are no records that can be added to this relationship.', DATAFACE_E_NOTICE);
		$select =& $this->addElement('select','select',df_translate('scripts.Dataface.ExistingRelatedRecordForm._build.LABEL_SELECT','Select'), $options, array('class'=>'record_selector'));
		
		
		$permissions = $this->_record->getPermissions(array('relationship'=>$this->_relationshipName));
		
		if ( isset($permissions['add existing related record']) and $permissions['add existing related record'] ){
			// We are allowed to add a new related record, so we will create a mask to allow this.
			$mask=array('edit'=>1);
		}
		// Now we still need to add fields so that the user can specify information about the relationship.
		// ie: some fields of the join table may be descriptive.
		foreach (array_keys($fkCols) as $fkTable) {
			if ( $fkTable == $table ){
				// This table is the main domain table... we don't want to input any data for this table.
				continue;
			}
			$qfFactory = new Dataface_QuickForm($fkTable,$this->_parentTable->db );
			$tableRef =& Dataface_Table::loadTable($fkTable);
			$recordRef = new Dataface_Record($fkTable, array());
			$recordRef->setValues($fkCols[$fkTable]);
			$currFieldnames = array_keys($tableRef->fields());
			foreach ($currFieldnames as $currFieldname){
				if ( isset( $fkCols[$fkTable][$currFieldname]) ){
					// this value is bound, and should not be changed.
					continue;
				}
				$field =& $tableRef->getField($currFieldname); 
				//$el = $qfFactory->_buildWidget($field, array_merge($mask, $this->_record->getPermissions(array('field'=>$this->_relationshipName.'.'.$currFieldname))));
				//$el = $qfFactory->_buildWidget($field, $recordRef->getPermissions(array('field'=>$currFieldname, 'recordmask'=>$mask)));
				$fperms =  $this->_relationship->getPermissions(array('field'=>$currFieldname));
				if ( ! $fperms['new'] ) continue;
				$el = $qfFactory->_buildWidget($field, $fperms);
				// To Do: Make it work with groups
				
				$this->addElement($el);
				/*
				 *
				 * If there are any validation options set for the field, we must add these rules to the quickform
				 * element.
				 *
				 */
				$validators = $field['validators'];
				
				foreach ($validators as $vname=>$validator){
					/*
					 *
					 * $validator['arg'] would be specified in the INI file.
					 * Example ini file listing:
					 * -------------------------
					 * [FirstName]
					 * widget:label = First name
					 * widget:description = Enter your first name
					 * validators:regex = "/[0-9a-zA-Z/"
					 *
					 * This would result in $validator['arg'] = "/[0-9a-zA-Z/" in this section
					 * and $vname == "regex".  Hence it would mean that a regular expression validator
					 * is being placed on this field so that only Alphanumeric characters are accepted.
					 * Please see documentation for HTML_QuickForm PEAR class for more information
					 * about QuickForm validators.
					 *
					 */
					
					$this->addRule($field['name'], $validator['message'], $vname, $validator['arg'], 'client');
					
				}
				
				unset($field);
			}
			unset($tableRef);
			unset($qfFactory);
			
		}
		
		$keyEls = array();
		$keyDefaults = array();
		foreach ( array_keys($this->_parentTable->keys()) as $key ){
			$keyEls[] = $factory->addElement('hidden', $key);
			
			
		}
		
		
		$this->addGroup($keyEls,'__keys__');
		$keyvals = array();
		
		foreach ( array_keys($this->_parentTable->keys()) as $key ){
			$keyvals[$key] = $this->_record->getValueAsString($key);
		}
		$this->setDefaults( array('__keys__'=>$keyvals) );
		
		$this->addElement('hidden','-table');
		$this->addElement('hidden','-relationship');
		$this->addElement('hidden','-action');
		$this->addElement('submit','Save','Save');
		$this->setDefaults(
			array(
				'-table'=>$this->_parentTable->tablename,
				'-relationship'=> $this->_relationshipName,
				'-action'=>"existing_related_record"
			)
		);
		
		// Set the return page
		$returnPage = @$_SERVER['HTTP_REFERER'];
		if ( isset($mainQuery['-redirect']) ){
			$returnPage = $mainQuery['-redirect'];
		} else if ( isset($mainQuery['--redirect']) ){
			$returnPage = $mainQuery['--redirect'];
		}
		
		if ( !$returnPage ){
			$returnPage = $app->url('-action=related_records_list&-relationship='.urlencode($this->_relationshipName));
		}
		
		$this->addElement('hidden','--redirect');
		$this->setDefaults(array('--redirect'=>$returnPage));
		
		/*
		 * There may be some default values specified in the relationship schema.
		 */
		if ( isset( $r['existing'] ) ){
			$this->setDefaults($r['existing']);
		}
		
		$this->_built = true;
			
	
	
	}
	
	/**
	 * Displays the form as html.
	 */
	function display(){
		if ( !$this->_built ) $this->_build();
		parent::display();
	}
	
	
	/**
	 * Saves the record.  Ie: creates the necessary join table records to add the 
	 * desired record to the relationship.
	 */
	function save($values){
		
		$colVals = array();
		
		/*
		 * In case some values were not submitted, we will use the defaults (as specified in the relationships.ini
		 * file for this relationship to fill in the blanks.
		 */
		if ( isset( $this->_relationship->_schema['existing'] ) ){
			foreach ( $this->_relationship->_schema['existing'] as $key=>$value){
				if ( !isset( $values[$key] ) ){
					$values[$key] = $value;
				}
			}
		}
		
		$io = new Dataface_IO($values['-table']);
		$record = new Dataface_Record($values['-table'], array());
		$io->read($values['__keys__'], $record);
		
		$idstring = $values['select'];
		$pairs = explode('&',$idstring);
		foreach ($pairs as $pair){
			list($attname, $attval) = explode('=',$pair);
			$attname = urldecode($attname);
			$attval = urldecode($attval);
			$colVals[$attname] = $attval;
		}
		foreach ($values as $key=>$value){
			if ( strpos($key,'-') === 0 ) continue;
			if ( $key == "Save" ) continue;
			if ( $key == "select") continue;
			$fullPath = $values['-relationship'].'.'.$key;
			if ( !$this->_parentTable->exists($fullPath) ) {
				//echo "Field $fullPath does not exist";
				continue;
			
			}
			$metaValues = array();
			$abs_fieldName = $this->_parentTable->absoluteFieldName($key, array_merge(array($this->_relationship->getDomainTable()), $this->_relationship->_schema['selected_tables']));
			if ( PEAR::isError($abs_fieldName) ){
				continue;
			
			}
			$serializer = new Dataface_Serializer($this->_parentTable->tablename);
			//echo "Serializing $fullPath\n";
			$serializedValue = $serializer->serialize( $fullPath, $this->_quickForm->pushValue($fullPath, $metaValues, $this->getElement($key)));
			
			$colVals[ $abs_fieldName ] = $serializedValue;
		}

		$relatedRecord = new Dataface_RelatedRecord($record, $values['-relationship'], $colVals);
		
		$res = $io->addExistingRelatedRecord($relatedRecord, true /*Using security*/);
		return $res;
	}
		
		

		



}

