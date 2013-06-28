<?php
import('Dataface/QuickForm.php');
/**
 * A form that is composed of fields from multiple tables.  The fields are addressed
 * using their unique dataface id.  e.g. tablename?key1=val1&key2=val2#fieldname
 */
class Dataface_CompositeForm extends HTML_QuickForm {
	/**
	 * URIs of records and fields that are being edited on this form.
	 */
	var $uris = array(); 
	
	/**
	 * Index of Dataface_QuickForm objects that are used to build 
	 * forms. Keyed on URIs.
	 */
	var $quickforms = array();	
								
	/**
	 * Index of Dataface Record objects that are being edited on this form
	 * keyed on URI.
	 */
	var $records = array();
	
	
	var $fields;
	
	var $changed_fields = array();

							
	function Dataface_CompositeForm($uris){
		$this->uris = $uris;
		$this->HTML_QuickForm();
	}
	
	function &getQuickForm($uri){
		// This returns a builder quickform to build the form 
		// for the given URI.
		if ( strpos($uri,'?') !== false ){
			// We strip the fieldname off the end of the uri
			// because we only want to store one of each record.
			list($uri) = explode('?',$uri);
		}
		
		if ( strpos($uri,'/') !== false ){
			list($uri) = explode('/',$uri);
		}
		
		if ( !isset($this->quickforms[$uri]) ){
			$this->quickforms[$uri] = new Dataface_QuickForm($uri);
		}
		return $this->quickforms[$uri];
		
		
	}
	
	function &getRecord($uri){
		if ( strpos($uri,'#') !== false ){
			// We strip the fieldname off the end of the uri
			// because we only want to store one of each record.
			list($uri) = explode('#',$uri);
		}
		
		if ( !isset($this->records[$uri]) ){
			$this->records[$uri] =& df_get($uri);
		}
		return $this->records[$uri];
		
	}
	
	function &getTable($uri){
		if ( strpos($uri,'?') !== false ){
			// We strip the fieldname off the end of the uri
			// because we only want to store one of each record.
			list($uri) = explode('?',$uri);
		}
		
		if ( strpos($uri,'/') !== false ){
			list($uri) = explode('/',$uri);
		}
		return Dataface_Table::loadTable($uri);
	}
	
	function &getFieldDef($uri){
		if ( strpos($uri,'#') === false ){
			$err =& PEAR::raiseError('No field specified in CompositeForm::getFieldDef.');
			return $err;
		}
		list($uri,$fieldname) = explode('#',$uri);
		$table =& $this->getTable($uri);
		$field =& $table->getField($fieldname);
		return $field;
		
	}
	
	function &getFieldDefs($uri=null){

		if ( isset($uri) ){
			$defs = array();
			if ( strpos($uri,'#') !== false ){
				$fld =& $this->getFieldDef($uri);
				$defs[$uri] =& $fld;
			} else {
				$table =& $this->getTable($uri);
				$flds =& $table->fields();
				foreach (array_keys($flds) as $key){
					$defs[$uri.'#'.$key] =& $flds[$key];
				}
			}
			return $defs;
		} else {

			if ( !isset($this->fields) ){
				$this->fields = array();
				foreach ( $this->uris as $uri ){
					$this->fields = array_merge($this->fields, $this->getFieldDefs($uri));
				}
			}
			return $this->fields;
		}
		
	}
	
	
	function build(){
		$formTool =& Dataface_FormTool::getInstance();
		foreach ( $this->getFieldDefs() as $uri=>$fieldDef ){
			
			//$qf =& $this->getQuickForm($uri);
			$record =& $this->getRecord($uri);
			/*
			 * 
			 * If the user does not have permission to view this field, we should not generate this widget.
			 *
			 */
			if ( !Dataface_PermissionsTool::view($record, array('field'=>$fieldDef['name']))){
			
				continue;
			
			}
			$el =& $formTool->buildWidget($record,$fieldDef, $this, $uri);
			if ( PEAR::isError($el) ) trigger_error($el->getMessage(), E_USER_ERROR);
			//$el->setName($uri);
			//$this->addElement($el);
			//$this->setDefaults(array( $uri => df_get($uri,'strval')));
			unset($el);
			unset($record);
			unset($fieldDef);
			
			
		}
		$this->addElement('submit','submit','Save');
	}
	
	function save(){
		$db =& Dataface_DB::getInstance();
		$db->startTransaction();
		$formTool =& Dataface_FormTool::getInstance();
		foreach ($this->getFieldDefs() as $uri=>$fieldDef ){
			$record =& $this->getRecord($uri);
			$formTool->pushField($record, $fieldDef, $this, $uri);
			if ( $record->valueChanged($fieldDef['name']) ) $this->changed_fields[] = $uri;
		}
		
		foreach ( array_keys($this->records) as $uri ){
			$res = $this->records[$uri]->save(null, true);
			if ( PEAR::isError($res) ){
				$db->rollbackTransaction();
				return $res;
			}
			
		}
		$db->commitTransaction();
		return true;
	}
	
	function htmlValues(){
		$vals = array();
		foreach ($this->changed_fields as $uri){
			$record =& $this->getRecord($uri);
			list($record_uri, $fieldname) = explode('#',$uri);
			$vals[$uri] = $record->htmlValue($fieldname);
			unset($record);
		}
		return $vals;
	}
}
