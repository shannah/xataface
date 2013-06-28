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

/*******************************************************************************
 * File:	Dataface/QuickForm.php
 * Author:	Steve Hannah
 * Description:
 * 	An extension of HTML_QuickForm to auto-generate a form for a particular table
 * 	in an SQL database.
 * 	
 *******************************************************************************/
 
import( 'HTML/QuickForm.php');
import( 'Dataface/Table.php');
import( 'Dataface/Vocabulary.php');
import( 'Dataface/QueryBuilder.php');
import( 'Dataface/QueryTool.php');
import( 'Dataface/IO.php');
import( 'Dataface/SkinTool.php');
import('HTML/QuickForm/Renderer/Dataface.php');
import( 'Dataface/PermissionsTool.php');
import('Dataface/FormTool.php');


// Register our special types
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['htmlarea'] = array('HTML/QuickForm/htmlarea.php', 'HTML_QuickForm_htmlarea');
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['table'] = array('HTML/QuickForm/table.php', 'HTML_QuickForm_table');
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['calendar'] = array('HTML/QuickForm/calendar.php', 'HTML_QuickForm_calendar');
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['time'] = array('HTML/QuickForm/time.php', 'HTML_QuickForm_time');
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['webcam'] = array('HTML/QuickForm/webcam.php', 'HTML_QuickForm_webcam');
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['portal'] = array('HTML/QuickForm/portal.php', 'HTML_QuickForm_portal');
define( 'QUICKFORM_AMBIGUOUS_FIELD_ERROR', 2);
define( 'QUICKFORM_NO_SUCH_FIELD_ERROR',3);

/**
 * An HTML_QuickForm object that is aware of the Dataface framework foundation classes.
 * @ingroup formsAPI
 */
class Dataface_QuickForm extends HTML_QuickForm {

	public static $TRACK_SUBMIT = true;
	
	/**
	 * The name of the table upon which this form is based.
	 */
	var $tablename;
	
	/**
	 * Database handle.
	 */
	var $db;
	
	/**
	 * Path to the ini file for this form.  This is used when the form is defined by an ini file.
	 * If no ini file is set, then this form is generated based on the fields of the table.
	 */
	var $_iniFile;
	
	/**
	 * Array of query values that dictate which record is loaded in this form.
	 */
	var $_query;
	
	/**
	 * ???
	 */
	var $_exactMatches = false;
	
	/**
	 * Reference to the Dataface_Table object for this form.
	 */
	var $_table;
	
	/**
	 * Dataface_Record object used as a model for the data in this form.
	 */
	var $_record;
	
	/**
	 * Reference to the result set.  This is used to load the appropriate record into the form for editing.
	 */
	var $_resultSet;
	
	/**
	 * ???
	 */
	var $_attributes = array();
	
	/**
	 * The renderer used for this form.
	 */
	var $_renderer;

	/**
	 * Boolean flag indicating if we are creating a new record or editing an existing record.
	 */
	var $_new = false;
	
	/**
	 * A flag that overrides the noquery directive that is passed for new record forms.
	 * This is needed for join record forms because they are created as new record forms
	 * but still the query needs to allow loading the existing record.
	 */
	var $overrideNoQuery = false;
	
	
	/**
	 * Some columns may require some special loading mechanisms.  This is an 
	 * associative array of columns => callbacks to load the column.
	 */
	var $_fields = array();
	
	/**
	 * Flag that indicates whether the form has been built yet.
	 */
	
	var $_isBuilt = false;
	
	var $_fieldnames = null;
	
	var $_lang;
	
	/**
	 * Will store an array of the names of fields that were changed as a result
	 * of processing this form.
	 */
	var $_changed_fields=array();
	
	
	/**
	 * The name of the tab that this quick form is on.
	 */
	var $tab;
	
	var $app;
	
	
	var $submitLabel = null;
	
	/**
	 * @param $tablename The name of the table upon which this form is based. - or a Dataface_Record object to edit.
	 * @type string | Dataface_Record
	 *
	 * @param $db DB handle for the current database connection.
	 * @type resource
	 *
	 * @param $query Associative array of query parameters to dictate which record is loaded for editing.
	 * @type array([String]->[String])
	 *
	 * @param $new Flag to indicate whether this form is creating a new record or editing an existing one.
	 * @type boolean
	 *
	 * @param $fieldnames An optional array of field names to include in the form.
	 * @type array(string)
	 *
	 */
	function Dataface_QuickForm($tablename, $db='',  $query='', $formname='', $new=false, $fieldnames=null, $lang=null){
		$app =& Dataface_Application::getInstance();
		$this->app =& $app;
		$appQuery =& $app->getQuery();
		if ( !isset($lang) && !isset($this->_lang) ){
			$this->_lang = $app->_conf['lang'];
		} else if ( isset($lang) ){
			$this->_lang = $lang;
		}
		if ( is_a($tablename, 'Dataface_Record') ){
			if ( !$this->formSubmitted() ){
				$this->_record =& $tablename;
				$this->tablename = $this->_record->_table->tablename;
				$this->_table =& $this->_record->_table;
				unset($tablename);
				$tablename = $this->tablename;
			} else {
				$this->_record =& Dataface_QuickForm::getRecord();
				$this->tablename = $tablename;
				$this->_table =& Dataface_Table::loadTable($this->tablename);
			}
				
		} else  if ( !$new ){
			if ( $tablename == $appQuery['-table'] ){
				$this->_record =& Dataface_QuickForm::getRecord();
			} else if ( $query ){
				$this->_record =& df_get_record($tablename, $query);
			}
			if ( !$this->_record ) $this->_record = new Dataface_Record($tablename, array());
			
			$this->tablename = $tablename;
			
			$this->_table =& Dataface_Table::loadTable($this->tablename);
			
			//$tablename = $this->tablename;
		} else {
			$this->tablename = $tablename;
			$this->_table =& Dataface_Table::loadTable($this->tablename, $this->db);
			$this->_record = new Dataface_Record($this->tablename, array());
		}
		
		$this->_new = $new;
		if ( !$formname ) {
			if ( $new ){
				$formname = "new_".$tablename."_record_form";
			} else {
				$formname = "existing_".$tablename."_record_form";
			}
		
		}
		if ( !$db and defined('DATAFACE_DB_HANDLE') ){
			
			$db = DATAFACE_DB_HANDLE;
		} else {
			$db = $app->_db;
		}
			
		$this->db = $db;
		$this->_query = is_array($query) ? $query : array();
		// The cursor tells us which record in the dataset we will be editing.
		if ( !isset( $this->_query['-cursor'] ) ){
			$this->_query['-cursor'] = 0;
		}
		
		// Load the results of the query.
		$this->_resultSet =& Dataface_QueryTool::loadResult($tablename, $db, $this->_query);
		
		parent::HTML_QuickForm($formname, 'post', df_absolute_url($_SERVER['PHP_SELF']),'',array('accept-charset'=>$app->_conf['ie']),self::$TRACK_SUBMIT);
		
		//$this->_fields =& $this->_table->fields(false,false,true);
		$this->_fields =& $this->_table->formFields(false,true);
		
		
		//$this->_record = new Dataface_Record($this->_table->tablename, array());
		$this->_renderer = new HTML_QuickForm_Renderer_Dataface($this); //$this->defaultRenderer();
		$this->_renderer->setFormTemplate($this->getFormTemplate());
		$this->_requiredNote = '';
		
		
		if ( is_array($fieldnames) ){
		    /*
		     * $fieldnames were specified in the parameters.  We will use the provided
		     * field names but we must make sure that the fields exist.
		     */
			$this->_fieldnames = array();
			foreach ($fieldnames as $fieldname){
				if ( isset($this->_fields[$fieldname]) ){
					$this->_fieldnames[] = $fieldname;
				}
			}
		}
		
		//$this->_build();

			
	}
	
	
	function formSubmitted(){
		return ( isset( $_POST['__keys__']) and isset( $_POST['-table']) );
	}
	
	function &getRecord(){
		
		if ( Dataface_QuickForm::formSubmitted() ){
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
				
			$curr =& $qt->loadCurrent();
			
			return $curr;
		}
	}
	
	/**
	 * @brief Sets the label of the submit button for this form.  This 
	 * will override the default submit label in xataface.  If it is set
	 * to null then the default label will be used.
	 *
	 * @param string $label The label to place on the submit button.
	 */
	function setSubmitLabel($label){
		$this->submitLabel = $label;
	}
	
	/**
	 * @brief Returns the label to use as the submit button for the form.
	 * @returns string The submit button label.  This may be null in which
	 *
	 * Note: This is used by the Renderer_ArrayDataface class for rendering.
	 *
	 * case the default label will be used.
	 */
	function getSubmitLabel(){
		return $this->submitLabel;
	}
	
	
	/**
	 * Fills arr1 with elements of arr2 if they don't already exist in arr1.  This 
	 * will work to infinite levels of depth.
	 */
	function _fillArray( &$arr1, $arr2 ){
		$keys = array_keys($arr2);
		foreach ($keys as $key){
			if ( !isset( $arr1[ $key ] ) ){
				$arr1[ $key ] = $arr2[ $key ];
			} else if ( is_array( $arr1[ $key ] ) and is_array( $arr2[ $key ] ) ){
				$this->_fillArray( $arr1[$key], $arr2[$key]);
			}
		}
	}
	
	
	/**
	 *
	 * Build an HTML_QuickForm_element object to represent a field from the table.
	 *
	 * @param $field A field descriptor array for specific field.
	 * @param $permissions An optional second argument to pass a permissions descriptor array
	 * 		  to define the current permissions.
	 *
	 */
	function _buildWidget(&$field){
		global $myctr;
		
		if ( func_num_args() > 1 ){
			/*
			 *
			 * A second argument is present.  It must be a permissions array.
			 *
			 */
			$permissions = func_get_arg(1);
				
		} else {
			/*
			 *
			 * No permissions were specified so we give Global permissions by default.
			 *
			 */
			$permissions = Dataface_PermissionsTool::ALL();
		}
		
		
		
		$formTool =& Dataface_FormTool::getInstance();
		$el =& $formTool->buildWidget($this->_record, $field, $this, $field['name'], $this->_new, $permissions);
		
		
		return $el;
		
	
	
	}
	
	/**
	 * Tags the form and the session with matching hash codes that can be used to prevent spoof 
	 * form submissions.  The goal is to make it so that this form will only be processed if the hash
	 * is in place.  Of course, if there is no session set up, this won't work. So this method first
	 * does a check to see if there is a session.  If there is a session, it tags the form.
	 * This method has a matching method that is called when validating the form to make sure that
	 * it was submitted.
	 *
	 */
	function tagFormAndSession(){
		if ( session_id() ){
			
		}
	
	}
	
	
	/**
	 *
	 * Builds the form. (Ie: adds all of the fields, sets the default values, etc..)
	 *
	 */
	function _build(){
		if ( $this->_isBuilt ){
			/*
			 *
			 * We only need to build the form once.  If it is already build, just return.
			 *
			 */
			return;
		}
		
		
		/*
		 * Now to figure out which fields will be displayed on the form.
		 */
		if ($this->_fieldnames === null or !is_array($this->_fieldnames) or count($this->_fieldnames)==0 ){
		    /*
		     * No fieldnames were explicitly provided (or they were improperly provided
		     * so we use all of the fields in the table.
		     */
		   // if ( isset( $query['--tab'] ) and !$new ){
		   // 	$flds =& $this->_table->fields(true);
		    
		   // 	$this->_fieldnames = array_keys($flds[$query['--tab']]);
		   // } else {
			$this->_fieldnames = array();
			foreach ($this->_fields as $field){
				if ( isset($this->tab) and ($this->tab != @$field['tab']) /*and ($this->tab != @$group['tab'])*/ ) continue;
				// If we are using tabs, and this field isn't in the current tab, then
				// we skip it.
				$this->_fieldnames[] = $field['name'];
				//$this->_fieldnames = array_keys($this->_fields);
			}
			//}
		} 
		
		
		
		
		$this->_isBuilt = true;
			// set flag to indicate that the form has already been built.
		
		if ( !$this->_record || !is_a($this->_record, 'Dataface_Record') ){
			return PEAR::raiseError(
				Dataface_LanguageTool::translate(
					'Cannot build quickform with no record',
					'Attempt to build quickform with no record set.'
				),
				E_USER_ERROR
			);
		}
		
		$relationships =& $this->_table->relationships();
			// reference to relationship descriptors for this table.
		
		
		$formTool =& Dataface_FormTool::getInstance();
		$groups = $formTool->groupFields($this->_fields);
		foreach ( $groups as $sectionName => $fields ){
			unset($group);
			$group =& $this->_record->_table->getFieldgroup($sectionName);
			if ( PEAR::isError($group) ){
				unset($group);
				$group = array('label'=>df_translate('scripts.Dataface_QuickForm.LABEL_EDIT_DETAILS', 'Edit Details'), 'order'=>1);
				
			}
			
			$groupEmpty = true; // A flag to check when the group has at least one element
			if ( !$fields ) continue;
			foreach ( $fields as $field){
				if ( !in_array($field['name'], $this->_fieldnames) ) continue;
				//if ( isset($this->tab) and ($this->tab != @$field['tab']) and ($this->tab != @$group['tab']) ) continue;
					// If we are using tabs, and this field isn't in the current tab, then
					// we skip it.
				
			
				$name = $field['name'];
					// reference to field descriptor array.
				$widget =& $field['widget'];
					// reference to widget descriptor array
				$vocabulary = $field['vocabulary'];
					// reference to field's vocabulary
			
				/*
				 * 
				 * If the user does not have permission to view this field, we should not generate this widget.
				 *
				 */
				if ( !Dataface_PermissionsTool::view($this->_record, array('field'=>$name))
					and !($this->_new and Dataface_PermissionsTool::checkPermission('new',$this->_record->getPermissions(array('field'=>$name))))
				){
					unset($widget);
					continue;
				
				}
				
				if ( $groupEmpty ){
					// This is the first field in the group, so we add a header for the 
					// group.
					$headerel =& $this->addElement('header', $group['label'], $group['label']);
					$headerel->setFieldDef($group);
					unset($headerel);
					$groupEmpty = false;
				}
				
				/*
				 *
				 * Build the widget for this field.  Note that we pass the permissions array
				 * to the method to help it know which widget to build.
				 *
				 */
				$el = $this->_buildWidget($field, $this->_record->getPermissions(array('field'=>$name)));
				if ( PEAR::isError($el) ){
					$el->addUserInfo(
						df_translate(
							'scripts.Dataface.QuickForm._build.ERROR_FAILED_TO_BUILD_WIDGET',
							"Failed to build widget for field $name ",
							array('name'=>$name,'line'=>0,'file'=>'_')
							)
						);
					return $el;
				}
				
				
					
				//$this->addElement($el);
				
				unset($field);
				unset($el);
				unset($widget);
				
			}
		} // end foreach $groups
		/*
		 *
		 * We need to add elements to the form to specifically store the keys for the current
		 * record.  These elements should not be changeable by the user as they are used upon 
		 * submission to find out which record is currently being updated.  We will store
		 * the keys for this record in a group of hidden fields where a key named "ID" would 
		 * be stored in a hidden field as follows:
		 * <input type="hidden" name="__keys__[ID]" value="10"/>  (assuming the value of the ID field for this record is 10)
		 *
		 */
		$factory = new HTML_QuickForm('factory');
			// a dummy quickform object to be used tgo create elements.
		$keyEls = array();
			// 
		$keyDefaults = array();
		foreach ( array_keys($this->_table->keys()) as $key ){
			$keyEls[] = $factory->addElement('hidden', $key);
			
		}
		$this->addGroup($keyEls,'__keys__');
		
		/*
		 *
		 * We add a field to flag whether or not we are creating a new record.
		 * This does not mean that we are always creating a new record.  That will
		 * depend on the value that is placed in this field as a default.
		 *
		 */
		$this->addElement('hidden','-new');
		
		$this->setDefaults(array('-new'=>$this->_new));
		if ( ($this->_new and Dataface_PermissionsTool::checkPermission('new',$this->_table) ) or 
		     (!$this->_new and Dataface_PermissionsTool::edit($this->_record) ) ){
		     $saveButtonLabel = df_translate('tables.'.$this->_table->tablename.'.save_button_label', '');
			if ( !$saveButtonLabel ) $saveButtonLabel = df_translate('save_button_label','Save');
			$this->addElement('submit','--session:save',$saveButtonLabel);
			//$this->addGroup($formTool->createRecordButtons($this->_record, $this->tab));
		}
		
		if ( $this->_new and !$this->overrideNoQuery){
		
			$this->addElement('hidden','--no-query',1);
		}
			// add the submit button.
		
		
		
		
		/*
		 *
		 * We need to set the default values for this form now.
		 *
		 */
		
		$keys = $this->getKeys();
			// may not be necessary -- not sure....
			
		if ( $this->isSubmitted() and !$this->_new){
			/*
			 *
			 * This part is unnecessary because the record is not populated
			 * in the Dataface_QuickForm constructor.
			 *
			 */ 
			$key_vals = $this->exportValues('__keys__');
			$query = $key_vals['__keys__'];
			//$io = new Dataface_IO($this->tablename, $this->db);
			//$io->read($query, $this->_record);
			
		} else if ( !$this->_new ){
			/*
			 *
			 * The form has not been submitted yet and we are not creating a new
			 * record, so we need to populate the form with values from the record.
			 *
			 */
			foreach ( array_keys($this->_table->keys()) as $key ){
				$keyDefaults[$key] = $this->_record->strval($key);
				
			}
			
			$this->setConstants( array('__keys__'=>$keyDefaults) );
			$this->pull();
			
			
		} else { // $this->_new
			$defaults = array();
			foreach ( array_keys($this->_fields) as $key ){
				$defaultValue = $this->_table->getDefaultValue($key);
				if ( isset($defaultValue) ){
					//if ( isset($this->_fields[$key]['group']) and $this->_fields[$key]['group'] ){
						
					//	$defaults[$this->_fields[$key]['group']][$key] = $defaultValue;
					//} else {
						$defaults[$key] = $defaultValue;
					//}
				}
			}
			
			$this->setDefaults($defaults);
		}
		
	}
	
	
	
	/**
	 *
	 * Returns a reference to the Quickform element that is used to display a specified field.
	 *
	 * @param $fieldname The name of the field whose quickform element we wish to obtain.
	 *
	 */
	function &getElementByFieldName($fieldname){
		$field =& $this->_table->getField($fieldname);
		
		$formTool =& Dataface_FormTool::getInstance();
		$el =& $formTool->getElement($this, $field, $fieldname);
		return $el;
	}
	
	/**
	 *
	 * Pulls the value from the record into its appropriate field in the form.
	 * 
	 * @param $fieldname The name of the field to pull.
	 *
	 */
	function pullField($fieldname){
		// Step 1: Load references to objects that we will need to use
		$s =& $this->_table;
			// Reference to the table
		$field =& $s->getField($fieldname);
		
		$formTool =& Dataface_FormTool::getInstance();
		$res = $formTool->pullField($this->_record, $field, $this, $fieldname, $this->_new);
		return $res;
		
	
	}
	
	/**
	 * 
	 * Fills in the form fields with values from the record.  In a sense, this "Pulls"
	 * the values from the record into the form.
	 *
	 */
	function pull(){
		//$fields = array_keys($this->_fields);
		$fields =& $this->_fieldnames;
		foreach ($fields as $field){
			$res = $this->pullField($field);
			if ( PEAR::isError($res) ){
				continue;
				
				
			}
			
		}
		return true;
	
	}
	
	
	
	/**
	 *
	 * Pushes the values in all form elements into their corresponding field in
	 * the record.
	 *
	 * @throws Dataface_Error::PermissionDenied error if the user doesn't have permission.  You should always
	 * 			try to catch this error if calling this function - otherwise the push will fail, and you won't know
	 *			.
	 *
	 */
	function push(){
		//$fields = array_keys($this->_fields);
		$fields =& $this->_fieldnames;
		//$ctr = 0;
		foreach ($fields as $field){
			
			$res = $this->pushField($field);
			if ( Dataface_Error::isPermissionDenied($res) ){
				/*
				 *
				 * The user does not have permission to set this value for this field.
				 * We return an error, that should result in a "PERMISSION DENIED" page if
				 * if is propogated up properly.
				 *
				 */
				return $res;
			}
			if (PEAR::isError($res) ){
				
				
				
				continue;
				$res->addUserInfo(
					df_translate(
						'scripts.Dataface.QuickForm.push.ERROR_PUSHING_DATA',
						"Error pushing data onto field $field in QuickForm::push()",
						array('field'=>$field,'line'=>0,'file'=>'_')
						)
					);
				throw new Exception($res->toString(), E_USER_ERROR);
				
			}
		}
		
		return true;
	
	}
	
	
	/**
	 *
	 * Validates the form input to make sure that it is valid.  This extends the 
	 * standard QuickForm method by adding custom validation from the Record object.
	 *
	 */
	 function validate(){
	 	$this->_build();
	 	//$this->push();
	 	if ( $this->isSubmitted() ){
	 		$app =& Dataface_Application::getInstance();
	 		$res = $app->fireEvent('Dataface_QuickForm_before_validate');
	 		if ( PEAR::isError($res) ){
	 			
	 			$this->_errors[] = $res->getMessage();
	 		}
	 		/*
	 		 *
	 		 * We only need to validate if the form was submitted.
	 		 *
	 		 */
	 		//foreach ( array_keys($this->_fields) as $field ){
	 		$rec = new Dataface_Record($this->_record->_table->tablename, $this->getSubmitValues());
	 		$rec->pouch = $this->_record->pouch;
	 		foreach ($this->_fieldnames as $field){
	 			/*
	 			 *
	 			 * Go through each field (corresponding to a record field) in the form
	 			 * and validate against the record's validation script.
	 			 *
	 			 */
	 			$el =& $this->getElementByFieldName($field);
	 			if ( PEAR::isError($el) ){
	 				unset($el);
	 				continue;
	 			}
	 			
	 			
	 			$params = array('message'=>df_translate('scripts.GLOBAL.MESSAGE.PERMISSION_DENIED',"Permission Denied"));
	 				// default error message to be displayed beside the field.
	 			
	 			$res = $rec->validate($field, $el->getValue(), $params );
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
	 			
	 			
	 			unset($params);
	 		}
	 	}
	 	
	 	
	 	
	 	/*
	 	 *
	 	 * Now that we have done our work, we can let the default validate method do the rest
	 	 * of the work.
	 	 *
	 	 */
	 	return parent::validate();
	 	
	 }
	
	/**
	 *
	 * "Pushes" the value in a form element into the record.
	 * @param $fieldname The name of the field to be pushed.
	 *
	 */
	function pushField($fieldname){
		
		$formTool =& Dataface_FormTool::getInstance();
		$field =& $this->_table->getField($fieldname);
		
		$res = $formTool->pushField($this->_record, $field, $this, $fieldname, $this->_new);
		
		return $res;
		
	}
	
	/**
	 * REturns a value for the specified field name that is in a format that can be "pulled"
	 * into the corresponding widget of the form.
	 */
	function pullValue($fieldname){
		$fieldname = $this->_formatFieldName($fieldname);
	
	
	}
	
	/**
	 * Extracts value from the form ready to be stored in the table.
	 */
	function pushValue($fieldname, &$metaValues, $element=null){
	
		$formTool =& Dataface_FormTool::getInstance();
		$field =& $this->_table->getField($fieldname);
		if ( !isset($element) ) $element =& $formTool->getElement($this, $field, $fieldname);

		$res = $formTool->pushValue($this->_record, $field, $this, $element, $metaValues);
		return $res;			
	
	}
	
	
	
	
	function display(){
		if ( $this->_resultSet->found()>0 || $this->_new ){
			$res = $this->_build();
			if ( PEAR::isError($res) ){
				return $res;
			}
			else {
				//$this->displayTabs();
				if ( !$this->_new and !Dataface_PermissionsTool::edit($this->_record) ){
					$this->freeze();
				}
				
				if ( $this->_new  and /*!Dataface_PermissionsTool::edit($this->_table)*/!Dataface_PermissionsTool::checkPermission('new',$this->_table) ){
					$this->freeze();
				}
				$formTool =& Dataface_FormTool::getInstance();
				
				
				if ( $this->_new || Dataface_PermissionsTool::view($this->_record) ){
					//echo $this->_renderer->toHtml();
					echo $formTool->display($this);
				} else {
					echo "<p>".df_translate('scripts.GLOBAL.INSUFFICIENT_PERMISSIONS_TO_VIEW_RECORD','Sorry you have insufficient permissions to view this record.')."</p>";
				}
				//parent::display();
			}
		} else {
			echo "<p>".df_translate('scripts.GLOBAL.NO_RECORDS_MATCHED_REQUEST','No records matched your request.')."</p>";
		}
	}
	
	
	function displayTabs(){
		
		if ( isset($this->_record) ){
			echo "<ul id=\"quick-form-tabs\">";
			foreach ( $this->_record->_table->getTabs() as $tab ){
				echo "<li><a href=\"".$this->app->url('-tab='.$tab)."\">".$tab."</a></li>";
			}
			echo "</ul>";
		}
	}
	
	function getElementTemplate($fieldName=''){
		$fieldname = $this->_formatFieldName($fieldname);
		
		if ( $fieldName && isset($this->_fields[$fieldName]) && isset($this->_fields[$fieldName]['widget']['description']) ){
			$widget = $this->_fields[$fieldName]['widget'];
			$description = $widget['description'];
			
		} else {
			$description = '';
		}
			
		$o = "<div class=\"field\">
			<label>{label}</label>
			<!-- BEGIN required --><span style=\"color: #ff0000\" class=\"fieldRequired\" title=\"required\">*</span><!-- END required -->
			<!-- BEGIN error --><div class=\"fieldError\" style=\"color: #ff0000\">{error}</div><!-- END error -->
			<div class=\"formHelp\">$description</div>
			{element}
			</div>
			";
		return $o;
	
	}
	
	function getFormTemplate(){
		$atts =& $this->_table->attributes();
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
					<legend>{$atts['label']}</legend>
					<table width="100%" class="Dataface_QuickForm-table-wrapper">
					
					{content}
					</table>
					</fieldset>
				</form>
END;
	}
	
	function getFieldGroupTemplate($name){
		$name = $this->_formatFieldName($name);
		$group =& $this->_table->getFieldgroup($name);
		
		
		$o = "<tr><td colspan=\"2\"><fieldset class=\"fieldgroup\" style=\"border: 1px solid #8cacbb; margin: 0.5em;\">
			<legend>".$group['label']."</legend>
			<div class=\"formHelp\">".$group['description']."</div>
			<table width=\"100%\" border=\"0\" class=\"Dataface_QuickForm-group-table-wrapper\">
			{content}
			</table>
			
			</fieldset></td></tr>";
		return $o;
	}
	
	function getGroupTemplate($name){
		$name = $this->_formatFieldName($name);
		$group =& $this->_table->getField($name);
		
		$context = array( 'group'=>&$group, 'content'=>'{content}');
		$skinTool =& Dataface_SkinTool::getInstance();
		ob_start();
		$skinTool->display($context, 'Dataface_Quickform_group.html');
		$o = ob_get_contents();
		ob_end_clean();
		
		return $o;
	}
	
	function getGroupElementTemplate($groupName=''){
		$groupName = $this->_formatFieldName($groupName);
		$group =& $this->_table->getFieldgroup($groupName);
		if ( PEAR::isError($group) ){
			$group->addUserInfo(
				df_translate(
					'scripts.Dataface.QuickForm.getGroupElementTemplate.ERROR_GETTING_FIELD_GROUP',
					"Error getting field group '$groupName' in QuickForm::getGroupElementTemplate() ",
					array('groupname'=>$groupName,'line'=>0,'file'=>'_')
					)
				);
			return $group;
		}
		
		$description = '';
		$label = '';
		
		if ( $group['element-description-visible'] ){
			$description = "<div class=\"formHelp\" style=\"display: inline\">".$group['description']."</div>";
		}
		
		if ( $group['element-lavel-visible'] ){
			$label = '';
		}
		
		if ( $fieldName && isset($this->_fields[$fieldName]) && isset($this->_fields[$fieldName]['widget']['description']) ){
			$widget = $this->fields[$fieldName]['widget'];
			$description = $widget['description'];
			
		} else {
			$description = '';
		}
			
		$o = "<div class=\"field\">
			<label>{label}</label>
			<!-- BEGIN required --><span style=\"color: #ff0000\" class=\"fieldRequired\" title=\"required\">*</span><!-- END required -->
			<!-- BEGIN error --><div class=\"fieldError\" style=\"color: #ff0000\">{error}</div><!-- END error -->
			<div class=\"formHelp\">$description</div>
			{element}
			</div>
			";
			
		return $o;
	
	}
	
	function save( $values ){
		
		// First let's find out if we should SAVE the data or if we should just be
		// storing it in the session or if we are saving the data to the database
		
		
		if (!$this->_new){
			// Make sure that the correct form is being submitted.  
			if ( !isset( $values['__keys__'] ) ){
				throw new Exception(
					df_translate(
						'scripts.Dataface.QuickForm.save.ERROR_SAVING_RECORD',
						"Error saving record in QuickForm::save().\n<br>"
						), E_USER_ERROR);
			}
			if ( array_keys($values['__keys__']) != array_keys($this->_table->keys()) ){
				throw new Exception(
					df_translate(
						'scripts.Dataface.QuickForm.save.ERROR_SAVING_RECORD',
						"Error saving record in QuickForm::save().\n<br>"
						), E_USER_ERROR);
			}
		}

		if ( $this->_new ){

			$this->_record->clearValues();
		}
		
		$res = $this->push();
		
		if ( !$this->_new ){
			if ( $this->_record->snapshotExists() ){
				
				$tempRecord = new Dataface_Record($this->_record->_table->tablename, $this->_record->getSnapshot());
			} else {
				$tempRecord =& $this->_record;
			}
			if ( $values['__keys__'] != $tempRecord->strvals(array_keys($this->_record->_table->keys())) ){
				throw new Exception(
					df_translate(
						'scripts.Dataface.QuickForm.save.ERROR_SAVING_RECORD',
						"Error saving record in QuickForm::save().\n<br>"
						), E_USER_ERROR);
			}
		}
		
		
		if (PEAR::isError($res) ){
			
			
			$res->addUserInfo(
				df_translate(
					'scripts.Dataface.QuickForm.save.ERROR_PUSHING_DATA',
					"Error pushing data from form onto table in QuickForm::save() ",
					array('line'=>0,'file'=>"_")
					)
				);
			
			
			return $res;
		}

		
		// Let's take an inventory of which fields were changed.. because
		// we are going to make their values available in the htmlValues()
		// method which is used by the ajax form to gather updates.
		foreach ( $this->_fields as $changedfield ){
			if ( $this->_record->valueChanged($changedfield['name']) ){
				$this->_changed_fields[] = $changedfield['name'];
			}
		}
	
		$io = new Dataface_IO($this->tablename, $this->db);
		$io->lang = $this->_lang;
		if ( $this->_new ) $keys = null;
		else $keys = $values['__keys__'];

		$res = $io->write($this->_record,$keys,null,true /*Adding security!!!*/, $this->_new);
		if ( PEAR::isError($res) ){
			if ( Dataface_Error::isDuplicateEntry($res) ){
				/*
				 * If this is a duplicate entry (or just a notice - not fatal), we will propogate the exception up to let the application
				 * decide what to do with it.
				 */
				return $res;
			} 
			if ( Dataface_Error::isNotice($res) ){
				return $res;
			} 
		
			$res->addUserInfo(
				df_translate(
					'scripts.Dataface.QuickForm.save.ERROR_SAVING_RECORD',
					"Error saving form in QuickForm::save()",
					array('line'=>0,'file'=>"_")
					)
				);
			throw new Exception($res->toString(), E_USER_ERROR);
			
		}
		
		
		
		if ( isset( $io->insertIds[$this->tablename]) and $this->_table->getAutoIncrementField() ){
			$this->_record->setValue($this->_table->getAutoIncrementField(), $io->insertIds[$this->tablename]);
			$this->_record->setSnapshot();

		} 
		
		return true;
		
		

	}
		
	
	/**
	 * Returns an array of references to the key fields of this form.
	 */
	function getKeys(){
		$keys = array();
		foreach ($this->_fields as $key=>$value){
			if ( strtolower($value['Key']) == strtolower('PRI') ){
				$keys[$key] =& $this->_fields[$key];
			}
		}
		return $keys;
	}
	
	
	/**
	 * @deprecated Use Dataface_IO::read()
	 */
	function deserialize($field){
		return Dataface_Table::_deserialize($field);
		
					
	}
	
	
	/**
	 * @deprecated Use Dataface_Serializer::serialize()
	 */
	function serialize($field){
	
		return Dataface_Table::_serialize($field);
		
		
		
	}
	
	/**
	 * Does nothing....
	 */
	function _formatFieldName($fieldname){
		return $fieldname;
		//return str_replace(':','.', $fieldname);
	}
	
	
	/**
	 * 
	 * Static method to create a New record form (ie: A form to create a new record).
	 * 
	 * @param $tablename The name of the table in which to store the record.
	 * @type string
	 *
	 * @param $fieldnames Optional array of field names to be included on the form.
	 * @type array(string)
	 *
	 * @returns Dataface_QuickForm object
	 *
	 * @usage
	 * <code>
	 *    $form =& Dataface_QuickForm::createNewRecordForm('Profiles');
	 *    if ( $form->validate() ){
	 *        $form->process( array(&$form, 'save'), true);
	 *        header('Location: success.php');
	 *        exit;
	 *    }
	 *    $form->display();
	 * </code>
	 *
	 */
	public static function &createNewRecordForm($tablename, $fieldnames=null){
	
		$form = new Dataface_QuickForm($tablename, '','','',true, $fieldnames);
		return $form;
	
	
	}
	
	/**
	 * 
	 * Static method to create an Edit record form (ie: a form to edit an existing record).
	 *
	 * @param $tablenameOrRecord Either the name of the table from which to edit the record or a 
	 *        Dataface_Record object that is to be edited.  If this parameter is the name of the
	 *        table then the record to be edited will be obtained form the request parameters
	 *        and Dataface_QueryTool.
	 * @type string | Dataface_Record
	 *
	 * @param $fieldnames (Optional( Array of field names to include on the form.
	 * @type array(string)
	 *
	 * @returns Dataface_QuickForm object
	 *
	 * @usage
	 * <code>
	 *   $form =& DatafaceQuickForm::createEditRecordForm('Profiles');
	 *   if ( $form->validate()){
	 *       $form->process( array(&$form, 'save'), true);
	 *       header('Location: success.php');
	 *       exit;
	 *   }
	 *   $form->display();
	 *
	 */
	public static function &createEditRecordForm(&$tablenameOrRecord, $fieldnames=null){
		$form = new Dataface_QuickForm($tablenameOrRecord, '','','',false,$fieldnames);
		return $form;
	}
	
	function htmlValues(){
		$vals = array();
		$record =& $this->_record;
		foreach ($this->_changed_fields as $fieldname){
			$vals[$fieldname] = $record->htmlValue($fieldname);
		}
		$vals['__id__'] = $record->getId();
		$vals['__url__'] = $record->getURL('-action=view');
		return $vals;
	}
	
}
