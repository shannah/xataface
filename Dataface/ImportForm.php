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
 * File:	Dataface/ImportForm.php
 * Author:	Steve Hannah <shannah@sfu.ca>
 * Created:	Dec. 1, 2005
 *
 * Description:
 * ------------
 * A Form to import records into a table or a relationship.
 *
 */

require_once 'HTML/QuickForm.php';
require_once 'HTML/QuickForm/optionalelement.php';
require_once 'Dataface/IO.php';
require_once 'Dataface/Record.php';

/**
 * @ingroup formsAPI
 */
class Dataface_ImportForm extends HTML_QuickForm {

	var $_table;
	var $_relationship = null;
	var $_record = null;
	var $_built = false;
	var $_step = 1;
	var $_filterNames=array();
	
	
	function Dataface_ImportForm( $tablename, $relationshipname=null ){
		$this->_table =& Dataface_Table::loadTable($tablename);
		if ( $relationshipname !== null ) $this->_relationship =& $this->_table->getRelationship($relationshipname);
		else $this->_relationship =& $this->getRelationship($this->_table);
		
		$this->_record =& $this->getRecord();
		$this->HTML_QuickForm("Import Form");
		
		if ( isset( $_REQUEST['--step'] ) ) $this->_step = $_REQUEST['--step'];
	

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
		if ( Dataface_ImportForm::formSubmitted() ){
			$record = new Dataface_Record($_POST['-table'], array());
			$io = new Dataface_IO($_POST['-table']);
			$io->read($_POST['__keys__'], $record);
			return $record;
		} else {
			$app =& Dataface_Application::getInstance();
			$qt =& Dataface_QueryTool::loadResult($app->_currentTable);
			$record =& $qt->loadCurrent();
			return $record;
		}
	}
	
	
	function &getRelationship(&$table){
		
		if ( Dataface_ImportForm::formSubmitted() ){
			if ( !isset($_POST['-relationship']) ){
				throw new Exception(
					df_translate(
						'scripts.Dataface.ImportForm.getRelationship.ERROR_RELATIONSHIP_NOT_FOUND',
						'Field \'-relationship\' not found in Import Form.'
						), E_USER_ERROR);
			}
			$relname = $_POST['-relationship'];
			if (strlen($relname) > 0 ){
				$rel =& $table->getRelationship($relname);
				return $rel;
			} else {
				$null = null;
				return $null;
			}
		}
		
		else {
			if ( !isset( $_GET['-relationship'] ) ){
				$null = null;
				return $null;
			} else {
				$relname = $_GET['-relationship'];
				if (strlen($relname) > 0 ){
					$rel =& $table->getRelationship($relname);
					return $rel;
				}
			}
		}
		$null = null;
		return $null;
		
	
	}
	
	function _build(){
		if ( $this->_built ) return;
		$app =& Dataface_Application::getInstance();
		$mainQuery = $app->getQuery();
		
		/*
		 * Add necessary flag fields so that the controller will find its way back here
		 * on submit.
		 */
		$this->addElement('hidden','-table');
		$this->addElement('hidden','--step');
		$this->addElement('hidden','-relationship');
		$this->addElement('hidden','-query');
		$this->addElement('hidden','-action');
		
		$this->setDefaults( array('-table'=>$this->_table->tablename,
								'--step'=>$this->_step,
								'-action'=>'import',
								'-query'=>$_SERVER['QUERY_STRING']) );
		
		if ( $this->_relationship !== null ){
			$this->setDefaults( array('-relationship'=>$this->_relationship->getName()));
		}
		
		/*
		 * Add keys of the current record as hidden fields so that we know we are importing
		 * into the correct record.
		 */
		$factory = new HTML_QuickForm('factory');
		$keyEls = array();
		$keyDefaults = array();
		foreach ( array_keys($this->_table->keys()) as $key ){
			$keyEls[] = $factory->addElement('hidden', $key);
			
			
		}
		$this->addGroup($keyEls,'__keys__');
		$keyvals = array();

		if ( is_object($this->_record) ){
			foreach ( array_keys($this->_table->keys()) as $key ){
				$keyvals[$key] = $this->_record->getValueAsString($key);
			}
		}
		$this->setDefaults( array('__keys__'=>$keyvals) );
		
		/*
		 * Now add the fields of the form.
		 */

		if ( intval($this->_step) === 1 ){
			/*
			 * Import filters define what formats can be imported into the table.
			 */
			if ( $this->_relationship === null ){

				$filters =& $this->_table->getImportFilters();
				$currentTableName = $this->_table->tablename;
				$currentTable =& $this->_table;
				$df_factory = df_create_new_record_form($currentTableName);
				
										
				$fields = $this->_table->fields(false,true);
				
			} else {
				$domainTablename = $this->_relationship->getDomainTable();

				
				if ( PEAR::isError($domainTable) ){
					$destTables =& $this->_relationship->getDestinationTables();
					$domainTablename = $destTables[0];
				}
				$domainTable =& Dataface_Table::loadTable($domainTablename);
				$currentTable =& $domainTable;
				$currentTablename = $domainTable->tablename;
				$filters =& $domainTable->getImportFilters();
				//$df_factory = df_create_new_related_record_form($currentTablename, $this->_relationship->getName());

				$df_factory = df_create_new_record_form($domainTable->tablename);

				//$df_factory->_build();

				$fields = $domainTable->fields(false,true);
			
				
			}
			
			$options = array(0=>df_translate('scripts.GLOBAL.FORMS.OPTION_PLEASE_SELECT','Please select ...'));
			foreach ( array_keys($filters) as $key ){
				$options[$key] = $filters[$key]->label;
				$this->_filterNames[] = $key;
			}
			$this->addElement('select','filter',df_translate('scripts.Dataface.ImportForm._build.LABEL_IMPORT_FILE_FORMAT','Import File Format:'),$options, array('onchange'=>'updateFilterDescription(this.options[this.options.selectedIndex].value)'));
			
			$this->addElement('textarea','content',df_translate('scripts.Dataface.ImportForm._build.LABEL_PASTE_IMPORT_DATA','Paste Import Data'), array('cols'=>60,'rows'=>10));
			$this->addElement('file','upload',df_translate('scripts.Dataface.ImportForm._build.LABEL_UPLOAD_IMPORT_DATA','Upload Import Data'));
			$defaultValsEl =& $this->addElement( 'optionalelement', '__default_values__', 'Default Values');
			require_once 'dataface-public-api.php';
			
			foreach (array_keys($fields) as $field){
				if ( $fields[$field]['widget']['type'] == 'hidden' ){
					$fields[$field]['widget']['type'] = 'text';
				}
				$tempEl = $df_factory->_buildWidget($fields[$field]);
					
				if (!$tempEl->getLabel() || $tempEl->_type == 'hidden' ) {
				} else{
					$defaultValsEl->addField($tempEl);
				}
				unset($tempEl);
			}
			
			
			
			$this->addElement('submit', 'submit', 'Submit');
		
			$this->addRule('filter','required',df_translate('scripts.Dataface.ImportForm._build.MESSAGE_IMPORT_FILE_FORMAT_REQUIRED','Import File Format is a required field'),null,'client');
		} else {
			/*
			 * We are in step 2 where we are verifying the data only.
			 */
			//$this->addElement('submit', 'back', 'Data is incorrect. Go back');
			$this->addElement('submit', 'continue', df_translate('scripts.Dataface.ImportForm._build.MESSAGE_PROCEED_WITH_IMPORT','Looks good.  Proceed with import'));
			$this->addElement('hidden', '--importTablename');
			$this->setDefaults(
				array('--importTablename'=>$_REQUEST['--importTablename'])
			);
		}
		// Set the return page
		$returnPage = @$_SERVER['HTTP_REFERER'];
		if ( isset($mainQuery['-redirect']) ){
			$returnPage = $mainQuery['-redirect'];
		} else if ( isset($mainQuery['--redirect']) ){
			$returnPage = $mainQuery['--redirect'];
		}

		if ( !$returnPage ){
			if ( isset($this->_relationship) ){
				$returnPage = $app->url('-action=related_records_list&-relationship='.$this->_relationship->getName());
			} else {
				$returnPage = $app->url('-action=list');
			}
			
		}
		
		$this->addElement('hidden','--redirect');
		$this->setDefaults(array('--redirect'=>$returnPage));
		
		
	
		$this->_built = true;
	}
	
	function display(){
		$this->_build();
		
		if ( $this->_step == 2 ){
			require_once 'Dataface/RecordGrid.php';
			$records = $this->loadImportTable();

			$grid = new Dataface_RecordGrid($records);
			$grid->id="import-records-preview";
			df_display(array('preview_data'=>$grid->toHTML(), 'num_records'=>count($records)), 'ImportForm_step2.html');
		
		}
		$res = parent::display();
		return $res;
	}
	
	function loadImportTable(){
		
		
		$dumpFile = $_SESSION['__dataface__import_data__'];
		$importData = unserialize(file_get_contents($dumpFile));
		
		$tablename = $this->_table->tablename;
		if ( $this->_relationship !== null ){
			$tablename = $this->_relationship->getDomainTable();
			if ( PEAR::isError($tablename) ){
				$destTables =& $this->_relationship->getDestinationTables();
				$tablename = $destTables[0]->tablename;
			}
		}
		
		$records = array();
		foreach ($importData['rows'] as $row){
			if ( isset($row['__CLASS__']) and isset($row['__CLASSPATH__']) ){
				if ( @$row['__CLASSPATH__'] and !class_exists($row['__CLASS__']) ){
					import($row['__CLASSPATH__']);
				}
				$class = $row['__CLASS__'];
				$importRecord = new $class($row);
				$records[] = $importRecord->getValues();
				unset($importRecord);
			} else {
				$records[] = new Dataface_Record($tablename, $row);
			}
		}
		
		return $records;
	
	}
	
	function import($values){
	
		if ( intval($this->_step) === 1 ){
		
			
			
			$upload =& $this->getElement('upload');
			if ( $upload->isUploadedFile() ){
				/*
				 * A file was uploaded.
				 */
				$val = $upload->getValue();
				$data = file_get_contents($val['tmp_name']);
				
			
			} else {
				/*
				 * No file was uploaded so we will get data from the paste field.
				 */
				$data = $values['content'];
			}
			
			
			$io = new Dataface_IO($this->_table->tablename);
			$relname = ( $this->_relationship === null ) ? null : $this->_relationship->getName();

			$importTablename = $io->importData($this->_record, $data, $values['filter'], $relname, false,  @$values['__default_values__']);
			return $importTablename;
		}
		
		else if ( $this->_step == 2 ){
		
			$io = new Dataface_IO($this->_table->tablename);
			$relname = ( $this->_relationship === null ) ? null : $this->_relationship->getName();
			$records = $io->importData($this->_record, $values['--importTablename'], @$values['filter'], $relname, true);
			return $records;
		}
		
	
	}
	
	function validate(){
	
		if ( intval($this->_step) === 1 ){

			return (!empty($_POST['filter']) and !empty($_POST['-query']) and !empty($_POST['-table']));
		} else {
			return (!empty($_POST['-query']) and !empty($_POST['-table']) and !empty($_POST['--importTablename']));
			
			
		}
	
	}
	


}
