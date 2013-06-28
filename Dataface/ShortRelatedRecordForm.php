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
 * 
 * File: 	Dataface/ShortRelatedRecordForm.php
 * Author: 	Steve Hannah <shannah@sfu.ca>
 * Created: October 2005
 *
 * Description:
 * -------------
 * A form that allows users to add new records to a relationship.
 *
 */
import('HTML/QuickForm.php');
import('Dataface/QuickForm.php');
/**
 * @ingroup formsAPI
 */
class Dataface_ShortRelatedRecordForm extends HTML_QuickForm {

	/*
	 * Reference to the parent table of the relationship (aka the source table)
	 * @type Dataface_Table
	 */
	var $_parentTable;
	
	/*
	 * A Reference to the relationship that the record is being added to.
	 * @type Dataface_Relationship
	 */
	var $_relationship;
	
	/*
	 * The name of the relationship that the record is being added to.
	 * @type string
	 */
	var $_relationshipName;
	
	
	var $_quickForms;
	
	/*
	 * Flag to indicate if the form is built yet.
	 * @type boolean
	 */
	var $_built;
	
	/*
	 * Database resource handle.
	 * @type resource handle
	 */
	var $_db;
	
	/*
	 * Reference to the record that is having a record added to its relationship.
	 * @type Dataface_Record
	 */
	var $_record;
	
	/*
	 * Container for the related record that is being added to the relationship.
	 * @type Dataface_RelatedRecord
	 */
	var $_relatedRecord;
	
	/*
	 * Custom renderer to make the form nice and pretty.
	 * @type HTML_Renderer_Dataface
	 */
	var $_renderer;
	
	/**
	 * The field names to include in this form.
	 */
	var $_fieldNames;
	
	/** Field groups in form. */
	var $_groups=array();
	
	/*
	 * Constructor.
	 * @param record The record that we are added a related record to. (ie; the parent record).
	 *		@type Dataface_Record
	 * @param relationshipName The name of the relationship that we are added to.
	 * 		@type string
	 * @param db A database resource handle.
	 */
	function Dataface_ShortRelatedRecordForm(&$record, $relationshipName, $db='', $fieldNames=null){
		$app =& Dataface_Application::getInstance();
		if ( is_a($record, 'Dataface_Record') ){
			/*
			 * A Record has been passed as a parameter.
			 */
			$this->_record =& $record;
		} else {
			/*
			 * A null record was passed as a parameter so we will load the correct record ourselves.
			 */
			$this->_record =& Dataface_ShortRelatedRecordForm::getRecord();
			$record =& $this->_record;
		
		}
		$tableName = $record->_table->tablename;
		
		$this->_relatedRecord = new Dataface_RelatedRecord($record, $relationshipName);
		$this->_db = $db;
		$this->_relationshipName = $relationshipName;
		$this->_parentTable  =& Dataface_Table::loadTable($tableName, $db);
		$this->_relationship =& $this->_parentTable->getRelationship($relationshipName);
		
		$this->_quickForms = array();
		//  Don't need this anymore because we load necessary quickforms at build time.
		
		$this->_built = false;
		
		$this->HTML_QuickForm($tableName.'_'.$relationshipName,'post','','',array('accept-charset'=>$app->_conf['ie']),true);
		
		$this->_renderer = new HTML_QuickForm_Renderer_Dataface($this); //$this->defaultRenderer();
		$this->_renderer->setFormTemplate($this->getFormTemplate());
		$this->_requiredNote = '';
		
		if ( $fieldNames === null ){
			//$this->_fieldNames =& $this->_relationship->_schema['columns'];
			$this->_fieldNames = $this->_relationship->fields(true,true);
		} else {
			$this->_fieldNames =& $fieldNames;
		}
	}

	
	/**
	 * Checks if the form has been submitted.  This can be called even before the
	 * form has been built in static context.
	 */
	function formSubmitted(){
		return ( isset( $_POST['__keys__']) and isset( $_POST['-table']) );
	}
	
	/**
	 * Loads the parent record for this form.  This may be called in static context. 
	 * This method is called in the constructor if no valid record is supplied.
	 */
	function &getRecord(){
		if ( Dataface_ShortRelatedRecordForm::formSubmitted() ){
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
		if ( $this->_built) return true;
		$r =& $this->_relationship->_schema;
		$t =& $this->_parentTable;
		$fkCols = $this->_relatedRecord->getForeignKeyValues();
		if ( PEAR::isError($fkCols) ){
			$fkCols->addUserInfo("Error getting foreign key columns while building Related Record Form");
			error_log($fkCols->toString());
			return $fkCols;
		}
		
		//echo "<h1>fkcols</h1>";print_r($fkCols);
		
		//$cols =& $r['columns'];
		$cols =& $this->_fieldNames;
		$dummyRecords = array();	// to hold records that will allow us to get permissions information form existing data.
		foreach ($cols as $col){
			list($tablename,$fieldname) = explode('.', $col);
			if ( !isset( $dummyRecords[$tablename] ) ) $dummyRecords[$tablename] = new Dataface_Record($tablename,array());
		}
		foreach ( array_keys($dummyRecords) as $dummyTable){
			if ( isset( $fkCols[$dummyTable] ) ){
				$dummyRecords[$dummyTable]->setValues($fkCols[$dummyTable]);
			}
		}
		
		$quickForms = array(); // array for each quickform object.. one for each table in relationship.
		
		//$permissions = $t->getRelationshipPermissions($this->_relationshipName);
		$permissions = $this->_record->getPermissions(array('relationship'=>$this->_relationshipName));
		
		if ( isset($permissions['add new related record']) and $permissions['add new related record'] ){
			// We are allowed to add a new related record, so we will create a mask to allow this.
			$mask=array('edit'=>1, 'new'=>1, 'view'=>1);
		} else {
			$mask = array();
		}	
		
		$groupsStarted = array();

		
		$fieldDefs = array();
		foreach ($cols as $col){
			$absFieldname = Dataface_Table::absoluteFieldName($col, $r['tables']);
			if ( PEAR::isError($absFieldname) ){
				$absFieldname->addUserInfo("Error obtaining absolute field name for field '$col' while building Related Record Form ");
				return $absFieldname;
			}
			
			list( $tablename, $fieldname ) = explode('.', $absFieldname);
			$thisTable =& Dataface_Table::loadTable($tablename);
			
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
			
			$field =& $this->_parentTable->getTableField($col);
			if ( @$field['grafted'] && !@$field['transient'] ) continue;
			$fieldDefs[$absFieldname] =& $field;
			
			unset($field);
			unset($thisTable);
			
			
			
		}
		//foreach ($cols as $col){
		$formTool =& Dataface_FormTool::getInstance();
		$groups = $formTool->groupFields($fieldDefs);
		$firstGroup=true;
		
		// Let's see if we need to use tabs

		foreach ( $groups as $sectionName => $fields ){
			unset($group);
			$firstField = reset($fields);
			if ( !$firstField ) continue;
			$thisTable =& Dataface_Table::loadTable($firstField['tablename']);
			$group =& $thisTable->getFieldgroup($sectionName);
			if ( PEAR::isError($group) ){
				$group = array('label'=>df_translate('scripts.Dataface_QuickForm.LABEL_EDIT_DETAILS', 'Edit Details'), 'order'=>1);
				
			}
			
			$groupEmpty = true; // A flag to check when the group has at least one element
			
			
			foreach ( $fields as $field){
				$tablename = $field['tablename'];
				$fieldname = $field['name'];
				
				
				$absFieldname = $tablename.'.'.$fieldname;
				unset($thisTable);
				$thisTable =& Dataface_Table::loadTable($tablename);
			
				if ( isset($r[$thisTable->tablename]['readonly']) ) continue;
				if ( !isset($this->_quickForms[$tablename]) ) $this->_quickForms[$tablename] = new Dataface_QuickForm($tablename,'','','',true);
				if (isset($quickForm) ) unset($quickForm);
				$quickForm =& $this->_quickForms[$tablename];
			
			
			
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
					
					//continue;
				}
				//$field =& $this->_parentTable->getTableField($col);
				$widget =& $field['widget'];
				$perms = $dummyRecords[$tablename]->getPermissions(array('field'=>$fieldname,'recordmask'=>$mask));

				if ( !Dataface_PermissionsTool::view($perms) ) continue;
				
				$el = $quickForm->_buildWidget($field, $perms);
				
				if ( PEAR::isError($el) ){
					error_log($el->toString()."\n".implode("\n",$el->getBacktrace()));
					throw new Exception("Failed to build widget for $fieldname.  See error log for details.", E_USER_ERROR);
				}
				
				
				
				if ( $groupEmpty ){
					// This is the first field in the group, so we add a header for the 
					// group.
					if ( !$firstGroup ) $this->addElement('submit','','Save');
					$headerel =& $this->addElement('header', $group['label'], $group['label']);
					$headerel->setFieldDef($group);
					unset($headerel);
					$groupEmpty = false;
					$firstGroup=false;
				}
				
				$this->addElement($el);
				
					
			
			
			
				// set default value
				
				$defaultValue = $thisTable->getDefaultValue($fieldname);
				if ( isset($defaultValue) ){
					$defaults = array($fieldname=>$defaultValue);
					
					$this->setDefaults( $defaults);
				}
				
				
				
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
					
					$this->addRule($fieldname, $validator['message'], $vname, $validator['arg'], 'client');
					
				}
				
				
				unset($field);
				unset($widget);
				unset($grp);
				unset($thisTable);
				unset($el);
				
			}
			
		}
		
		
		$factory = new HTML_QuickForm('factory');
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
		$this->addElement('submit','-Save','Save');
		$this->setDefaults(
			array(
				'-table'=>$this->_parentTable->tablename,
				'-relationship'=> $this->_relationshipName,
				'-action'=>"new_related_record"
			)
		);
		
		
		/*
		 * There may be some default values specified in the relationship schema.
		 */
		if ( isset( $r['new'] ) ){
			$this->setDefaults($r['new']);
		}
		$this->_built = true;
			
	
	
	}
	
	/**
	 * Displays the form.
	 */
	function display(){
		if ( !$this->_built ) $this->_build();
		import('Dataface/FormTool.php');
		$formTool =& Dataface_FormTool::getInstance();
		echo $formTool->display($this, null, false, $this->_relationship->showTabsForAddNew());
		
		//$this->accept($this->_renderer);
		//echo $this->_renderer->toHtml(); //parent::display();
	}
	
	/**
	 *
	 * Saves the related record.
	 *
	 * @param values Associative array of values received from the submitted form.
	 *
	 */
	function save($values){
		import('Dataface/LinkTool.php');
		$colVals = array();

		
		/*
		 * In case some values were not submitted, we will use the defaults (as specified in the relationships.ini
		 * file for this relationship to fill in the blanks.
		 */
		if ( isset( $this->_relationship->_schema['new'] ) ){
			foreach ( $this->_relationship->_schema['new'] as $key=>$value){
				if ( !isset( $values[$key] ) ){
					$values[$key] = $value;
				}
			}
		}
		
		
		$io = new Dataface_IO($values['-table']);
			// for writing the related record
		
		$record = new Dataface_Record($values['-table'], array());
			// The parent record... not the record being inserted.
			
		$io->read($values['__keys__'], $record);
			// We submitted the keys to the parent record in the form
			// so that we can load the parent record of this related record.

		// convert groups
		foreach ( array_keys($values) as $key){
			if ( isset($this->_groups[$key]) ){
				foreach ( $values[$key] as $fieldkey=>$fieldval){
					$values[$fieldkey] = $fieldval;
				}
				unset($values[$key]);
			}
		}

		foreach ($values as $key=>$value){
			// We will go through each submitted value from the form and try 
			// to figure out how it should be stored.
			
			
			if ( strpos($key,'-') === 0 ) continue;
			
			if ( $key == "-Save" ) continue;
				// We don't parse the "Save" button
				
			$fullPath = $this->_relationshipName.'.'.$key;
				// The full path to the field can be used to obtain information
				// about the field from the parent table, since most methods
				// in the table class will take field names of the form
				// <relationship name>.<fieldname>
			
			if ( !$this->_parentTable->exists($fullPath) ) {
				/*
				 * If the field in question does not exist then we just skip it.
				 * Perhaps we should throw an error?!!
				 *
				 */
				 //echo $fullPath.' does not exist in table '.$this->_parentTable->tablename;
				continue;
			
			}
			
			// At this point we know that the field exists so lets obtain references
			// to the useful components for us to work with this field.
			if (isset($field) ) unset($field);
			$field =& $this->_parentTable->getField($fullPath);
				// Field array with data about the field.
				
			if ( PEAR::isError($field) ){
				throw new Exception("Error obtaining field '$fullPath' while saving related record.", E_USER_ERROR);
			}
			
			$abs_fieldName = $this->_parentTable->absoluteFieldName($key, $this->_relationship->_schema['selected_tables']);
				// The absolute fieldname of this field. e.g., of the form <Tablename>.<Fieldname>
			if ( PEAR::isError($abs_fieldName) ){
				throw new Exception("Error trying to obtain absolute field name for the related field: '$fullPath'", E_USER_ERROR);
			}
			
			list($tablename, $fieldname) = explode('.', $abs_fieldName);
			if ( isset($table) ) unset($table);
			$table =& Dataface_Table::loadTable($tablename);
				// Reference to the table object where this field resides
			
			if ( isset($quickForm) ) unset($quickForm);
			$quickForm =& $this->_quickForms[$tablename];
				// QuickForm object for this field's table.
			
			
			$el = $this->getElement($key);
			
			
			
			$metaValues = array();
				// The $metaValues array will store the meta values associated with 
				// the current field.  A meta value is an associated value that 
				// should be stored in another field.  For example the mimetype
				// is a metavalue for a file upload field.  The $metaValues array
				// be of the form [Column Name] -> [Column Value].
			
				// Get the absolute field name of the field.  An absolute field name is
				// of the form <Tablename>.<Fieldname>
			
			
			$tempVal = $quickForm->pushValue($fieldname, $metaValues, $el);//$serializedValue;
				// $tempVal will contain the value as submitted by the form.. ready
				// to be added to a record.
			
			
			
				// The QuickForm element.
		
			// !!! Just changed arg from $abs_fieldName to $fullPath to fix errors... but still don't
			// !!! fully understand what was going on  - or why it was working before!?!?!
			if ( $this->_parentTable->isMetaField($fullPath) ){
				// If this is a meta field, we don't insert it on its own...
				// we will wait until its value is supplied by its described
				// field.
				
				unset($tempVal);
				unset($el);
				continue;
			
			}
			
			foreach ($metaValues as $metaKey => $metaValue){	
				// Set the meta values
				$colVals[ $tablename.'.'.$metaKey ] = $metaValue;
			}
			
			
			$colVals[ $abs_fieldName ] = $tempVal;
				// Add the value to the array to be saved in the RelatedRecord
				// object.
				// Note that right now, Dataface_RelatedRecord will just ignore
				// the part of the field name before the period, but in the future,
				// this extra information may be used to allow multiple fields
				// with the same name from different tables in a single relationship.
			
			unset($tempVal);
		}
		
			
		//$queryBuilder = new Dataface_QueryBuilder($this->_parentTable->tablename);
		$relatedRecord = new Dataface_RelatedRecord($record, $this->_relationshipName, array()/*$colVals/**/);
		
		$relatedRecord->setValues($colVals);
		$res = $io->addRelatedRecord($relatedRecord, true /* Using Security Now!!! */);

		if ( PEAR::isError($res) ){
			
			return $res;
		} 
		
		
		//$res = $io->performSQL($sql);
		return $res;
		
	
	}
	
	
	/**
	 * Generates an html template that can be used by HTML_QuickForm to render the form.
	 */
	function getFormTemplate(){
		//$atts =& $this->_parentTable->attributes();
		$formname = $this->getAttribute('name');
		return <<<END
			<script language="javascript" type="text/javascript"><!--
			function Dataface_QuickForm(){
				
			}
			Dataface_QuickForm.prototype.setFocus = function(element_name){
				document.{$formname}.elements[element_name].focus();
				document.{$formname}.elements[element_name].select();
			}
			var quickForm = new Dataface_QuickForm();
			//--></script>
		
				<form{attributes}>
					<fieldset>
					<legend>New {$this->_relationshipName}</legend>
					<table width="100%" class="Dataface_QuickForm-table-wrapper Dataface_ShortRelatedRecordForm-table-wrapper">
					
					{content}
					</table>
					</fieldset>
				</form>
END;
		
	
	}
	
	/**
	 * Generates an html template that can be used by HTML_QuickForm to render groups of elements.
	 */
	function getGroupTemplate($name){
		//$name = $this->_formatFieldName($name);
		$group = $this->_parentTable->getTableField($name);
		
		//$group = $this->_parentTable->getField($name);
		$context = array( 'group'=>&$group, 'content'=>'{content}');
		$skinTool =& Dataface_SkinTool::getInstance();
		ob_start();
		$skinTool->display($context, 'Dataface_Quickform_group.html');
		$o = ob_get_contents();
		ob_end_clean();
		
		return $o;
	}

	
	function validate(){
		$this->_build();
	 	if ( $this->isSubmitted() ){
	 		/*
	 		 *
	 		 * We only need to validate if the form was submitted.
	 		 *
	 		 */
	 		//foreach ( array_keys($this->_fields) as $field ){
	 		foreach ($this->_fieldNames as $field){
	 			list($relname, $field) = explode('.', $field);
	 			/*
	 			 *
	 			 * Go through each field (corresponding to a record field) in the form
	 			 * and validate against the record's validation script.
	 			 *
	 			 */
	 			$el =& $this->getElement($field);
	 			if ( PEAR::isError($el) ){
	 				
	 				continue;
	 			}
	 			
	 			
	 			$params = array('message'=>"Permission Denied");
	 				// default error message to be displayed beside the field.
	 			$res = $this->_relatedRecord->validate($field, $el->getValue(), $params );
	 			if ( !$res){
	 				/*
	 				 *
	 				 * The default validate() method checks to see if the form validates based
	 				 * on the size of the _errors array.  (If it has count = 0, then it validates.
	 				 * Adding an error to this array will cause the parent's validate method to return
	 				 * false.
	 				 *
	 				 */
	 				 
	 				$this->_errors[$el->getName()] = $params['message'];
	 			}
	 		}
	 	}
	 	
	 	/*
	 	 *
	 	 * Now that we have done our work, we can let the default validate method do the rest
	 	 * of the work.
	 	 *
	 	 */
	 	return parent::validate();
	 	
		//$this->_build();
		//return parent::validate();
	}
		



}
