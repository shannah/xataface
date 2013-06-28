<?php
/**
 * @brief A class encapsulating the most common form building and processing functions
 * in dataface.
 *
 * @author Steve Hannah <shannah@sfu.ca>
 * @created May 2007
 */
class Dataface_FormTool {

	/**
	 * The paths to the widget handler files.
	 */
	var $widgetHandlerPaths = array();
	/**
	 * The classes for handling widgets.
	 */
	var $widgetHandlerClasses = array();
	
	/**
	 * Cache for instantiated widget handler objects.
	 */
	var $widgetHandlers = array();
	
	/**
	 * @brief Singleton method for obtaining a reference to the Dataface_FormTool
	 * object.
	 */
	public static function &getInstance(){
		static $instance = 0;
		if ( $instance === 0 ){
			$instance = new Dataface_FormTool();
			Dataface_Application::getInstance()->fireEvent('Dataface_FormTool::init', $instance);
			
		}
		return $instance;
	}
	
	
	
	
	/**
	 * @brief Pulls the data from the underlying $record object into the field.  
	 * This method will obtain it's value from the following mechanisms in order:
	 *
	 * -# WidgetHandler::pullField() if it exists (for the widget handler for the current widget type
	 * -# DelegateClass::fieldname__pullValue() if it exists
	 * -# WidgetHandler::pullValue() if it exists (for the widget handler for the current widget type).
	 * -# Dataface_Record::getValueAsString() it is exists
	 *
	 * The difference between this method and pullValue() is that this method will
	 * actually set the value into the widget, whereas pullValue() will only
	 * retrieve the value that should be set into the widget.
	 *
	 *
	 * @param Dataface_Record &$record The Dataface_Record object from which the
	 *			data is being pulled.
	 *
	 * @param array &$field The field configuration array for the field being 
	 *				pulled.
	 *
	 * @param HTML_QuickForm &$form The form that is pulling the data.
	 *
	 * @param string $formFieldName The name of the field within the form.
	 *
	 * @param boolean $new Whether or not this is a new record form. In this
	 *			case default values will be used.
	 *
	 * @returns mixed PEAR_Error if there is an error. or true on success.
	 *
	 * @see WidgetHandler::pullField()
	 * @see pullValue()
	 *
	 */
	function pullField($record, &$field, $form, $formFieldName, $new=false){
		
		$element =& $this->getElement($form,$field,$formFieldName);
		// Reference to the form element that will contain the field's value

		if ( PEAR::isError($element) ){
			
			return $element;
		}
		
		
	// Step 1: Load references to objects that we will need to use
		$table =& $record->_table;
		
		if ( !$table->hasField($field['name']) ){
			return PEAR::raiseError("Table ".$table->tablename." has no field $field[name] while trying to pull field value.", DATAFACE_E_NOTICE);
		}
			// Reference to the table

			// Reference to the field descriptor array that we are pulling
		$widget =& $field['widget'];
		
		// See if there is a widgethandler registered for this widget type
		$widgetHandler =& $this->getWidgetHandler($widget['type']);
		if ( isset($widgetHandler) and method_exists($widgetHandler, 'pullField') ){
			return $widgetHandler->pullField($record, $field, $form, $formFieldName, $new);
		}
		
			// Reference to the widget descriptor
		if ( !Dataface_PermissionsTool::view($record, array('field'=>$field['name'])) ) 
			return Dataface_Error::permissionDenied(
				df_translate(
					'scripts.Dataface.QuickForm.pullField.ERROR_NO_ACCESS_TO_FIELD',
					"No read access on field '$field[name]'",
					array('fieldname'=>$field['name'])
					)
				
			);
		

		
		$raw = $record->getValue($field['name']);
			// the raw value from the field
		$delegate =& $table->getDelegate();
			// Reference to the table's delegate object (may be null).
		// Step 2: Insert the value into the form element
		
		$filterValue = true;
			// In most cases we give the application an opportunity to
			// filter the value using the 'FormTool::pullValue' event.
			// This flag will store which case it is.
		
		if ( $delegate !== null and method_exists($delegate, $field['name']."__pullValue") ){
			/*
			 *
			 * The delegate defines a conversion method that should be used.
			 *
			 */
			$method = $field['name'].'__pullValue';
			$val = $delegate->$method($record, $element);
			$filterValue = false;
			
		} else if ( isset($widgetHandler) and method_exists($widgetHandler, 'pullValue') ){
			$val = $widgetHandler->pullValue($record, $field, $form, $element, $new);
			
		} else {
			$val = $record->getValueAsString($field['name']);

		}
		if ( $filterValue ){
			$evt = new stdClass;
			$evt->record = $record;
			$evt->field =& $field;
			$evt->form = $form;
			$evt->element = $element;
			$evt->value = $val;
			$evt->{'new'} = $new;
			$table->app->fireEvent('FormTool::pullValue', $evt);
			$val = $evt->value;
		}
		
		$form->setDefaults( array( $formFieldName=>$val) );

		
		
		/*
		 *
		 * If we got this far, it must have been a success.  Return true.
		 *
		 */
		return true;
	
	
	}
	
	
	/**
	 * @brief Pushes data from a form widget into a Dataface_Record object.  This will
	 * try to delegate to the following mechanisms if found:
	 *
	 * -# WidgetHandler::pushField() if it exists for the current widget type.
	 * -# WidgetHandler::pushValue() if it exists for the current widget type.
	 *
	 * @param Dataface_Record &$record The record into which the data is being pushed.
	 * @param array &$field The field configuration array as loaded from the fields.ini
	 *				file.
	 * @param HTML_QuickForm &$form The form from which the data is being taken.
	 * @param string $formFieldName The name of the field in the form.
	 * @param boolean $new Whether this is a new record form.
	 * @returns mixed PEAR_Error if there is an error.  true on success.
	 *
	 * @see WidgetHandler::pushField()
	 * @see pullField()
	 */
	function pushField($record, &$field, $form, $formFieldName, $new=false){
		if ( !is_array($field) ) throw new Exception("No field passed to pushField");
		// See if there is a widgethandler registered for this widget type
		$table =& $record->_table;
		
		$widget =& $field['widget'];
		
		$widgetHandler =& $this->getWidgetHandler($widget['type']);
		
		
		if ( isset($widgetHandler) and method_exists($widgetHandler, 'pushField') ){
			
			return $widgetHandler->pushField($record, $field['name'], $form, $formFieldName, $new);
		}
		
		
		$metaValues = array();	// will store any meta values that are produced by pushValue
								// a meta value is a field that exists only to support another field.
								// Currently the only examples of this are filename and mimetype fields 
								// for File fields.
		
		
		/*
		 *
		 * First we must obtain the value from the element on the form.
		 * $metaValues will hold an associative array of keys and values
		 * of Meta fields for this field.  Meta fields are fields that describe
		 * this field.  For example, if this field is a BLOB, then a meta field
		 * might contain this field's mimetype.
		 *
		 */
		if ( is_a($formFieldName, 'HTML_QuickForm_element') ){
			$element =& $formFieldName;
			unset($formFieldName);
			$formFieldName = $element->getName();
		} else {
			$element =& $this->getElement($form, $field, $formFieldName);
		}
		
		
		if ( PEAR::isError($element) || !is_a($element, 'HTML_QuickForm_element') || $element->isFrozen() || $element->getType() == 'static'){
			
			return;
		}
		
		$value = $this->pushValue($record, $field, $form, $element, $metaValues);
		
		
		
		
		$params = array();
		if ( !$record->validate($field['name'], $value, $params) ){
			
			return Dataface_Error::permissionDenied($params['message']);
		}
		
		
		if ( PEAR::isError($value) ){
			$value->addUserInfo(
				df_translate(
					'scripts.Dataface.QuickForm.pushField.ERROR_GETTING_VALUE',
					"Error getting value for field '$field[name]' in QuickForm::pushField()",
					array('file'=>"_", 'line'=>0,'fieldname'=>$field['name'])
					)
				);
			
			return $value;
		}
		
		
		if ( !$table->isMetaField($field['name']) ){
			

		
			/*
			 *
			 * A MetaField is a field that should not be updated on its own merit.
			 * An example of a MetaField is a mimetype field for a BLOB field.  This
			 * field will be updated as a meta value for the BLOB field when the BLOB 
			 * field is updated.
			 *
			 */
			$res = $record->setValue($field['name'], $value);
			
			if (PEAR::isError($res) ){
				$value->addUserInfo(
					df_translate(
						'scripts.Dataface.QuickForm.pushField.ERROR_SETTING_VALUE',
						"Error setting value for field '$field[name]' in QuickForm::pushField()",
						array('file'=>"_",'line'=>0,'fieldname'=>$field['name'])
						)
					);
				throw new Exception($value->toString(), E_USER_ERROR);
				return $res;
			}
		}
		
		/*
		 *
		 * If this field has any meta fields, then we will set them now.
		 *
		 */
		foreach ($metaValues as $key=>$value){
			$res = $record->setValue($key, $value);
			if ( PEAR::isError($res) ){
				$res->addUserInfo(
					df_translate(
						'scripts.Dataface.QuickForm.pushField.ERROR_SETTING_METAVALUE',
						"Error setting value for meta field '$key' in QuickForm::pushField() ",
						array('file'=>"_",'line'=>0,'field'=>$key)
						)
					);
				throw new Exception($res->toString(), E_USER_ERROR);

			}
		}
		
		
		
	}
	
	
	/**
	 * @brief Extracts value from the form ready to be stored in the table.  This doesn't
	 * actually push the data into the record, only obtains the data ready to 
	 * be pushed.
	 *
	 * This method will delegate to the WidgetHandler::pushValue() method of 
	 * the field's widget if defined.
	 *
	 *
	 * @param Dataface_Record &$record The record into which the data is meant
	 *		to be pushed.
	 *
	 * @param HTML_QuickForm &$form The form from which the data is taken.
	 *
	 * @param HTML_QuickForm_element The element (i.e. widget)  from which the 
	 *			data is taken.
	 *
	 * @param array &$metaValues An associative array of meta values in case 
	 *				there are other fields that should be filled in based on
	 *				the data in this field.
	 *
	 * @returns mixed The value that is to be pushed in the record.
	 *
	 */
	function pushValue($record,&$field, $form, &$element, &$metaValues){
		if ( is_string($field) ){
			throw new Exception("Invalid field parameter: $field", E_USER_ERROR);
			
		}
		
		$widgetHandler =& $this->getWidgetHandler($field['widget']['type']);
		
		//$formFieldName = $element->getName();
		
		$app =& Dataface_Application::getInstance();
		// Obtain references to frequently used objects
		$table =& $record->_table;
		
		if ( PEAR::isError($field) ){
			$field->addUserInfo(
				df_translate(
					'scripts.Dataface.QuickForm.pushValue.ERROR_PUSHING_VALUE',
					"Error trying to push value onto field name in push()",
					array('file'=>"_",'line'=>0)
					)
				);
			return $field;
		}
		
		$widget =& $field['widget'];
		
		
		
		
		$delegate =& $table->getDelegate();
		
		$out = null;
		
			// chops off the relationship part of the field name if it is there.
		if ( $delegate !== null and method_exists( $delegate, $field['name'].'__pushValue') ){
			// A delegate is defined and contains a 'prepare' method for this field --- we use it
			$val =& $element->getValue();
			if ( PEAR::isError($val) ){
				$val->addUserInfo(
					df_translate(
						'scripts.Dataface.QuickForm.pushValue.ERROR_GETTING_ELEMENT_VALUE',
						"Error getting element value for element $field[name] in QuickForm::pushField ",
						array('fieldname'=>$field['name'],'file'=>"_",'line'=>0)
						)
					);
				return $val;
			}
			
			// call the delegate's prepare function
			$method = $field['name'].'__pushValue';
			$val = $delegate->$method($record, $element);
			if ( PEAR::isError($val) ){
				$val->addUserInfo(
					df_translate(
						'scripts.Dataface.QuickForm.pushValue.ERROR_THROWN_BY_DELEGATE',
						"Error thrown by delegate when preparing value for field '$field[name]'",
						array('file'=>"_",'line'=>0,'fieldname'=>$field['name'])
						)
					);
				return $val;
			}
			
			// If the delegate class defines a custom push value then
			// we return it directly... we don't push it through any further filters.
			return $val;
		} else if ( isset($widgetHandler) and method_exists($widgetHandler, 'pushValue') ){
			$out = $widgetHandler->pushValue($record, $field, $form, $element, $metaValues);
		} else {
			// There is no delegate defined... we just do standard preparations based on field and widget types.
			$out = $element->getValue();
					
			
		}
		$evt = new stdClass;
		$evt->record = $record;
		$evt->field =& $field;
		$evt->form = $form;
		$evt->element = $element;
		$evt->metaValues =& $metaValues;
		$evt->value = $out;
		$app->fireEvent('FormTool::pushValue', $evt);
		$out = $evt->value;
		
		return $out;
		
			
	
	}
	
	/**
	 * @brief Builds a widget that can be added to a form.  This will delegate
	 * to the WidgetHandler::buildWidget() method if defined for the field's widget
	 * type.
	 *
	 * @param Dataface_Record &$record The Dataface Record that this widget 
	 * 			is to be editing.
	 * @param array &$field The field definition.
	 * @param HTML_QuickForm The form to which the widget will be added.
	 * @param string $formFieldName The name of the field on the form.
	 * @returns HTML_QuickForm_element
	 */
	function &buildWidget($record, &$field, $form, $formFieldName, $new=false, $permissions=null){
		$table =& $record->_table;
		
		
		$widget =& $field['widget'];
		if ( !isset($permissions) ){
			//$permissions =& $record->getPermissions(array('field'=>$field['name']));
			$permissions = Dataface_PermissionsTool::ALL();
			// reference to widget descriptor array
		}
		
		$pt =& Dataface_PermissionsTool::getInstance();
			// Reference to permissions tool to operate on $permissions
		
		
		$widgetHandler =& $this->getWidgetHandler($widget['type']);
		if ( isset($widgetHandler) and method_exists($widgetHandler, 'buildWidget') ){
			$el =& $widgetHandler->buildWidget($record, $field, $form, $formFieldName, $new);
			
		} else {
			$factory =& Dataface_FormTool::factory();
				// A dummy HTML_QuickForm used as a factory to create temporary elements.
				// Reference to the table object.
			$el =& $factory->addElement($widget['type'], $formFieldName, $widget['label'], array('class'=>$widget['class'], 'id'=>$formFieldName) );
		}
		
		
		if ( PEAR::isError($el) ){
			throw new Exception($el->toString(), E_USER_ERROR);
		}
		$el->setFieldDef($field);
		if ( isset( $record ) && $record && $record->_table->hasField($field['name']) ){
			if ( $link = $record->getLink($field['name']) ){
				$el->setProperty('link',$link);
			}
			$el->setProperty('record_url', $record->getURL());
		}
		$atts = $el->getAttributes();
		if ( !is_array($atts) ) $atts = array();
		$atts = array_merge($atts, $field['widget']['atts']);

		if ( !isset($atts['data-xf-field']) ){
			$atts['data-xf-field'] = $field['name'];
		}
		$el->setAttributes($atts);
		
		if ( $new and !$pt->checkPermission('new', $permissions) ){
			$el->freeze();
		} else if ( !$new and !$pt->checkPermission('edit', $permissions) ){
			$el->freeze();
		}
		
		/*
		// Deal with permissions on this field.
		if ( $pt->view($permissions) and !$pt->edit($permissions) ){
			if ( !($new && $pt->checkPermission('new', $permissions)) ){
				$el->freeze();
			}
		}
		*/
		$el->record =& $record;
		
		$form->addElement($el);
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
			if ( $vname == 'required' && $widget['type'] == 'file' ){
				continue;
			}
			
			$form->addRule($formFieldName, $validator['message'], $vname, @$validator['arg'], (($widget['type'] == 'htmlarea' )?null:'client'));
			
		}

		
		
		$this->pullField($record, $field, $form, $formFieldName, $new);
		
		
		$el->_persistantFreeze = true;
		return $el;
	}
	
	/**
	 * @brief Registers a class to handle the pushing and pulling for a particular type 
	 * of widget.  This is the mechanism by which custom widgets can be created and
	 * registered.  All that is required is to define a WidgetHandler and then
	 * register that handler with the form tool with this method.
	 *
	 * @param string $widgetType The name of the widget type being registered.
	 *			e.g. file, text, checkbox
	 *
	 * @param string $path The path to the file containing the class.
	 *
	 * @param string $class The name of the class.
	 *
	 * @return void
	 *
	 * @par Example
	 * @code
	 * $formTool = Dataface_FormTool::getInstance();
	 * $formTool->registerWidgetHandler('colorpicker', '/path/to/my/colorpicker.php', 'my_colorpicker_class');
	 * @endcode
	 *
	 */
	function registerWidgetHandler($widgetType, $path, $class){
		$this->widgetHandlerClasses[$widgetType] = $class;
		$this->widgetHandlerPaths[$widgetType] = $path;
	}
	
	
	/**
	 * @brief Unregisters a particular widget handler.
	 * @see registerWidgetHandler()
	 */
	function unregisterWidgetHandler($widgetType){
		unset($this->widgetHandlerClasses[$widgetType] );
		unset($this->widgetHandlerPaths[$widgetType]);
		unset($this->widgetHandlers[$widgetType]);
	}
	
	/**
	 * @brief Obtains a reference to the widget handler object for a particular type
	 * of widget.
	 *
	 * @param string $widgetType The name of the type of widget to be handled.
	 *
	 * @returns WidgetHandler The widget handler.
	 */
	function &getWidgetHandler($widgetType){
		
		if ( !isset($this->widgetHandlers[$widgetType]) ){
			
			if ( !isset($this->widgetHandlerPaths[$widgetType]) and !isset($this->widgetHandlerClasses[$widgetType]) ){
				
				$class = 'Dataface_FormTool_'.$widgetType;
				if ( class_exists('Dataface_FormTool_'.$widgetType) ){
					
					$this->widgetHandlers[$widgetType] = new $class;
				} else if ( $this->_file_exists_incpath('Dataface/FormTool/'.$widgetType.'.php') ){
					
					import('Dataface/FormTool/'.$widgetType.'.php');
					$this->widgetHandlers[$widgetType] = new $class; 
				} else {
					
					//$err = PEAR::raiseError("Attempt to get widget handler for '$widgetType' which is not registered.");
					$out = null;
					
					return $out;
				}
			} else {
			
				if ( !class_exists($this->widgetHandlerClasses[$widgetType]) ){
					import($this->widgetHandlerPaths[$widgetType]);
				}
				$class = $this->widgetHandlerClasses[$widgetType];
				$this->widgetHandlers[$widgetType] = new $class;
			}
		}
		return $this->widgetHandlers[$widgetType];
	}
	
	
	/**
	 * @brief Returns a QuickForm object that can be used to generate elements safely.
	 */
	public static function &factory(){
		static $factory = -1;
		if ( is_scalar($factory) ){
			$factory = new HTML_QuickFormFactory('factory');
		}
		return $factory;
	}
	
	/**
	 * @brief Gets a vocabulary that can be used in a particular field.
	 *
	 * @param Dataface_Record &$record The record that is being edited.
	 * 
	 * @param array &$field The config array for the field.
	 *
	 * @returns array The associative array of options in the valuelist.
	 */
	public static function &getVocabulary($record, &$field){
		$res = Dataface_FormTool::_getVocabAndClasses($record, $field);
		return $res['options'];
	}
	
	/**
	 * @brief Gets the list of meta values that is associated with the valuelist
	 * for a particular field.  The idea is that the options in a valuelist
	 * can be categorized into classes.  This returns those classes.
	 * @param Dataface_Record &$record The record that is being edited.
	 * 
	 * @param array &$field The config array for the field being edited.
	 *
	 * @returns array The associative array of classes [key => class]
	 */
	public static function getVocabularyClasses($record, &$field){
		$res = Dataface_FormTool::_getVocabAndClasses($record, $field);
		return $res['options__classes'];
	}
	
	/**
	 * @brief Reutrns a 2-element array of arrays, containing both the classes
	 * and values for a valuelist.
	 * @private
	 *
	 * @param Dataface_Record &$record The record being edited.
	 *
	 * @param array &$field The field config array.
	 *
	 *
	 * @returns array
	 */
	static function _getVocabAndClasses($record, &$field){
		if ( !$record ) {
			throw new Exception("No record foudn in getVocabulary()", E_USER_ERROR);
			
		}
		$table =& $record->_table;
		$options = null;
		
		if ( isset($field['vocabulary']) and $field['vocabulary'] ){
			/*
			 *
			 * This field has a vocabulary set on it. Let's load it and get it ready to be used
			 * as an options array for a quickform select, checkbox, or radio group element.
			 *
			 */
			$options =& $table->getValuelist($field['vocabulary']);
			$options__classes =& $table->getValuelist($field['vocabulary'].'__meta');
			
			if ( PEAR::isError($options) ){
				$options->addUserInfo("Error getting vocabulary '$field[vocabulary]' when building widget for field '$field[name]' in QuickForm::buildWidget() ");
				throw new Exception($options->toString(), E_USER_ERROR);
			}
			
			/*
			 * 
			 * We should have the option to choose none of the options, so we will add a blank option
			 * to the beginning of the options list.
			 *
			 */
			if ( is_array($options) ){
				$opts = array(''=>df_translate('scripts.GLOBAL.FORMS.OPTION_PLEASE_SELECT',"Please Select..."));
				foreach ($options as $key=>$value){
					$opts[$key] = $value;
				}
				$options = $opts;
			}
			
		}
		return array('options'=>&$options, 'options__classes'=>&$options__classes);
	}
	
	/**
	* @brief Check if a file exists in the include path
	* @private
	*
	* @version     1.2.1
	* @author      Aidan Lister <aidan@php.net>
	* @link        http://aidanlister.com/repos/v/function.file_exists_incpath.php
	* @param       string     $file       Name of the file to look for
	* @return      mixed      The full path if file exists, FALSE if it does not
	*/
	 function _file_exists_incpath ($file)
	 {
		 $paths = explode(PATH_SEPARATOR, get_include_path());
	  
		 foreach ($paths as $path) {
			 // Formulate the absolute path
			 $fullpath = $path . DIRECTORY_SEPARATOR . $file;
	  
			 // Check it
			 if (@file_exists($fullpath)) {
				 return $fullpath;
			 }
		 }
	  
		 return false;
	 }
	 
	 
	 /**
	  * @brief Checks to see if a field group exists in a given form.  This refers to a 
	  * group in the sense of an HTML_QuickForm_group element and not the Dataface
	  * notion of a 'group' widget.
	  * @private
	  *
	  * @param HTML_QuickForm &$form The form to check.
	  * @param string $group The name of the group.
	  * @returns boolean True if the group exists, otherwise false.
	  */
	 function _formGroupExists(&$form, $group){
	 	$el =& $form->getElement($group);
	 	return ( $el and !PEAR::isError($el) and ($el->getType() == 'group') );
	 }
	 
	 /**
	  * @brief Gets an element from a form.
	  *
	  * @param HTML_QuickForm &$form The form from which the element is being 
	  *			retrieved.
	  *
	  * @param array &$field The field config array for the field in question.
	  *
	  * @param string $name The name of the field.
	  *
	  * @returns HTML_QuickForm_element
	  */
	 function &getElement(&$form, &$field, $name){
	 	if ( !is_array($field) ){
	 		throw new Exception("Expected field definition for 2nd parameter but received something else.");
	 	}
	 	$fieldname = $field['name'];

		if ( isset($field['group']) and $this->_formGroupExists($form,$field['group'])){
			/*
			 *
			 * This field is part of a larger group of fields.  The widget that is used for this
			 * larger group is named after the field's group (rather than the field itself).
			 *
			 */
			$el =& $form->getElement($field['group']);
			if ( PEAR::isError($el) ){
				$el->addUserInfo(
					df_translate(
						'scripts.Dataface.QuickForm.getElementByFieldName.ERROR_GETTING_GROUP',
						"Failed to get group '$field[group]' when trying to load field '$fieldname' in Dataface_Quickform::pushWidget() ",
						array('groupname'=>$field['group'], 'fieldname'=>$fieldname,'line'=>0,'file'=>"_")
						)
					);
				throw new Exception($el->toString(), E_USER_ERROR);
				
				return $el;
			}
			
			/*
			 *
			 * Find the field within this group that has the same name as the field we are looking for.
			 *
			 */
			$els =& $el->getElements();
			unset($el);
				// prevent accidental change of the group element
			foreach ( array_keys($els) as $key) {
				$el =& $els[$key];
				if ( $el->getName() == $name ){
					/*
					 *
					 * We have found the element.  Break out of this loop.
					 *
					 */
					$element =& $el;
					break;
				}
				unset($el);
			}
			unset($els);
			if ( !isset($element) ){
				return PEAR::raiseError(QUICKFORM_NO_SUCH_FIELD_ERROR,null,null,null,
					df_translate(
						'scripts.Dataface.QuickForm.getElementByFieldName.ERROR_GETTING_GROUP_FIELD',
						"Error trying to load field '$fieldname' in group '$field[group][name]'.  The group was found but not the field. in Dataface_Quickform::pushWidget()",
						array('fieldname'=>$fieldname,'groupname'=>$field['group']['name'], 'line'=>0,'file'=>"_")
						)
					);
			}
		} else {
			/*
			 *
			 * This field is not part of a larger group.  The name of the element for this field is the
			 * same as the name of the field itself.
			 *
			 */
			$element =& $form->getElement($name);
		}
		
	
		return $element;
	
	}
	
	/**
	 * @brief Groups a collection of fields together by group.
	 *
	 * @param array &$fields associative array of field config arrays.
	 *
	 * @returns array
	 *
	 */
	function groupFields(&$fields){
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		// Take query parameters to set the collapsed config settings
		// for the groups.
		if ( isset($query['--collapsed']) ){
			$collapsed = explode(',',$query['--collapsed']);
		} else {
			$collapsed = array();
		}
		
		if ( isset($query['--expanded']) ){
			$expanded = explode(',', $query['--expanded']);
		} else {
			$expanded = array();
		}
		$groups = array();
		$groupOrders = array();
		foreach ( array_keys($fields) as $fieldname){
			$groupname = ( @$fields[$fieldname]['group'] ? $fields[$fieldname]['group'] : '__global__');
			$groups[$groupname][] =& $fields[$fieldname];
			if ( !isset($groupOrders[$groupname]) ){
				$table =& Dataface_Table::loadTable($fields[$fieldname]['tablename']);
				$grp =& $table->getFieldgroup($groupname);
				if ( in_array($groupname, $collapsed) ){
					$grp['collapsed'] = 1;
				}
				if ( in_array($groupname, $expanded) ){
					$grp['collapsed'] = 0;
				}
				if ( $grp ){
					$groupOrders[$groupname] = array( 'order'=>( (is_array($grp) and isset($grp['order'])) ? floatval($grp['order']) : 0));
				} else {
					$groupOrders[$groupname] = array('order'=>0);
				}
				unset($table);
				unset($grp);
			}
		}
		$groupOrders['__global__']['order'] = -1;
		uasort($groupOrders, array(&$this, '_compareGroups'));
		foreach ( array_keys($groupOrders) as $group){
			$groupOrders[$group] =& $groups[$group];
		}
		return $groupOrders;
	}
	
	/**
	 * @private
	 */
	function _compareGroups($g1, $g2){
		$o1 = ( isset($g1['order']) ? intval($g1['order']) : 0 );
		$o2 = ( isset($g2['order']) ? intval($g2['order']) : 0 );
		if ( $o1 <= $o2 ) return -1;
		return 1;
	}
	
	/**
	 * @brief Displays a form.
	 *
	 * @param HTML_QuickForm &$form The form to be displayed.
	 * @param string $template An alternate template that can be used to display
	 * 			the form.
	 * @param string $singleField Optional name of a single field to be rendered.
	 *			if this is the case, then only one field from the form will be
	 *			rendered and it will include a javascript onblur handler to auto
	 *			save as soon as the user leaves the field.
	 * 
	 * @returns void
	 */
	function display(&$form, $template=null, $singleField=false, $useTabs=false){
		
		
		import('HTML/QuickForm/Renderer/ArrayDataface.php');
		//$skinTool =& Dataface_SkinTool::getInstance();
		$renderer = new HTML_QuickForm_Renderer_ArrayDataface(true);
		$form->accept($renderer);
		$form_data = $renderer->toArray();
		if ( !@$form_data['sections'] ){
			$form_data['sections'] = array('__global__'=>array('header'=>df_translate('scripts.Dataface_FormTool.LABEL_EDIT_DETAILS', 'Edit Details'), 'name'=>'Edit','elements'=>&$form_data['elements']));
			unset($form_data['elements']);
		}
		if ( $useTabs ){
			$form_data['tabs'] = array();
			if ( @is_array($form_data['elements']) ){
				foreach ($form_data['elements'] as $key=>$element){
					if ( !@$element['field'] ) continue;
					$tabname = $element['field']['tab'];
					if ( !$tabname ) $tabname = '__main__';
					if ( !isset($form_data['tabs'][$tabname] ) ){
						$table =& Dataface_Table::loadTable($element['field']['table']);
						if ( PEAR::isError($table) ) throw new Exception($table->getMessage(), E_USER_ERROR);
						
						$form_data['tabs'][$tabname] = $table->getTab($element['field']['tab']);
						$form_data['tabs'][$tabname]['elements'] = array();
						$form_data['tabs'][$tabname]['sections'] = array();
						unset($table);
					}
					$form_data['tabs'][$tabname]['elements'][$key] =& $form_data['elements'][$key];
				
					
				}
			}
			if ( is_array($form_data['sections']) ){
				foreach ($form_data['sections'] as $skey=>$section ){
					if ( !@$section['elements'] ) continue;
					foreach ($section['elements'] as $ekey=>$element ){
						if ( !@$element['field'] ) continue;
						$tabname = $element['field']['tab'];
						if ( !$tabname ) $tabname = '__main__';
						if ( !isset($form_data['tabs'][$tabname] ) ){
							$table =& Dataface_Table::loadTable($element['field']['tablename']);
							if ( PEAR::isError($table) ) throw new Exception($table->getMessage(), E_USER_ERROR);
							
							$form_data['tabs'][$tabname] = $table->getTab($tabname);
							$form_data['tabs'][$tabname]['elements'] = array();
							$form_data['tabs'][$tabname]['sections'] = array();
							unset($table);
						}
						
						if ( !isset($form_data['tabs'][$tabname]['sections'][$skey]) ){
							$section_copy = $section;
							$section_copy['elements'] = array();
							$form_data['tabs'][$tabname]['sections'][$skey] =& $section_copy;
							unset($section_copy);
						}
						
						$form_data['tabs'][$tabname]['sections'][$skey]['elements'][$ekey] =& $form_data['sections'][$skey]['elements'][$ekey];
						
					}
				}
			}
			
			uasort($form_data['tabs'], array(&$this, '_sortTabs'));
		}
		$context = array('form_data'=>$form_data);
		
		if ( isset($form->_record) and method_exists($form->_record, 'getId') ){
			$context['form_record_id'] = $form->_record->getId();
		}
		if ( $singleField ){
			$context['singleField'] =& $form->getElement($singleField);
			$context['singleField']->updateAttributes(array('onblur'=>'submitThisForm(this.form);'));
			if (!isset($template) ) $template = 'Dataface_Form_single_field_template.html';
		}
		if ( !isset($template) ) $template = 'Dataface_Form_Template.html';
		df_display($context, $template);
	}
	
	/**
	 * @private
	 */
	function _sortTabs($a,$b){
		if ( @$a['order'] == @$b['order'] ) return 0;
		return (@$a['order'] < @$b['order']) ? -1 : 1;
	}
	
	
	/**
	 * @brief Builds an HTML_QuickForm object to edit the given record.
	 * @param Dataface_Record &$record The record that is to be edited.
	 *
	 * @param string $tab The name of the tab to display.  In the case of multi
	 *			tab forms, we may specify the tab which we wish to display.
	 *			the tab may contain only a subset of the fields in the table,
	 *			or it could be a 'partition' tab.  A 'partition' tab.
	 */
	function createRecordForm($record, $new=false, $tab=null, $query=null, $fields=null){
		
		$table =& $record->_table;
		
		if ( $table->hasJoinTable($tab, $record) ){
			$query['--tab'] = null;
			$jrecord = $record->getJoinRecord($tab, true /*null if not found*/);
			if ( !isset($jrecord) ) $new = true;
			else $new = false;
			$form =  $this->createRecordForm($record->getJoinRecord($tab), $new, null, $query, $fields);
			$form->overrideNoQuery = true;
			return $form;
			
		} else { 
			// TO DO:  Finish this method
			$form = new Dataface_QuickForm($table->tablename, df_db(),  $query, '', $new, $fields);
			$form->_record =& $record;
			$form->tab = $tab;
			return $form;
		} 
		
	}
	/**
	 * @brief Adds the necessary extra fields to a form to equip it to be used to edit the 
	 * given record.  In particular, it adds the next, submit, etc.. buttons to the 
	 * bottom of the form.  It also adds a __keys__ group to store/submit the keys
	 * of the record so that the correct record is processed when the form is 
	 * submitted.
	 *
	 * @param Dataface_Record &$record The record that the form is to edit.
	 * @param HTML_QuickForm &$form The form that is to be decorated.
	 * @param boolean $new Whether we should treat this as a new record form.
	 * @param string $tab The name of the tab that we are rendering (in the case of a multi-tab form).
	 * @return void
	 *
	 */
	function decorateRecordForm($record, &$form, $new=false, $tab=null){
		@$form->removeElement('__keys__');
		if ( $new ){
			
			
		} else {
			$factory =& self::factory();
			$els = array();
			foreach ( array_keys($record->_table->keys()) as $key ){
				$els[] = $factory->addElement('hidden',$key);
			}
			$form->addGroup($els, '__keys__');
			$form->setConstants(array('__keys__'=>$record->strvals(array_keys($record->_table->keys()))));
		}
		$form->addElement('hidden', '--form-session-key', $this->getSessionKey());
		@$form->removeElement('--session:save');
		$form->addElement('header','__submit__','Submit');
		$grp =& $form->addGroup($this->createRecordButtons($record, $tab));
		$grp->_separator = "\n";
		
		$data = $this->getSessionData($tab);
		if ( isset($data) ){
			//$form->setDefaults($data);
			if ( $_POST ){
				$vals = array_merge($data['_submitValues'], $_POST);
				$fvals = array_merge($data['_submitFiles'], $_FILES);
				$form->_setSubmitValues($vals, $fvals);
			} else {
			
				// TODO: File uploads will not be handled properly with this method.
				// Need to make it so that we can support file uploads before actually
				// saving the record.
				$form->setDefaults($data['_submitValues']);
			}
		}
		
		if ( $record->_table->hasJoinTable($tab, $record ) ){
			foreach ( $record->getJoinKeys($tab) as $key=>$value ){
				@$form->removeElement($key);
				//if ( $new ) $value = '-1';
				//$form->addElement('hidden',$key, $value);
			}
		}
		
		
		
	}
	
	
	/**
	 * @brief Validates a form to see if it is ready to be processed.
	 * @param Dataface_Record &$record The record that is being edited with this form.
	 * @param HTML_QuickForm The form that is to be validated.
	 * @param boolean $new Whether this form is creating a new record.
	 * @param string $tab The name of the tab that is being processed.
	 * @return boolean True if the form validates.
	 */
	function validateRecordForm($record, &$form, $new=false, $tab=null){
	
		if ( !$form->validate() ) return false;
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		$targets = preg_grep('/^--session:target:/', array_keys($query));

		if ( count($targets) > 0 ) return true;
		
		$tabs = $record->tabs();
		
		if ( count($tabs) <= 1 ) return true;
			// There is only one tab so we don't have to do anything fancy.
		//$post = $_POST;
		//$_POST=array();
		//echo "About to validate each tab";
		$session_data =& $this->getSessionData();
		foreach ( array_keys($tabs) as $tabname ){
			if ($tabname == $tab) continue;
			if ( !$session_data or !$session_data['tabs'] or !in_array($tabname, array_keys($session_data['tabs'])) ) continue;
			$currForm =& $this->createRecordForm($record, $new, $tabname);
			$currForm->_build();
			
			//$currForm->setConstants($currForm->_defaultValues);
			//$_POST = $currForm->exportValues();
			$this->decorateRecordForm($record, $currForm, $new, $tabname);
			//$currForm->_submitValues = $currForm->_defaultValues;
			$currForm->_flagSubmitted = true;
			if ( !$currForm->validate() ){
				
				$form->setElementError('global.'.$tabname, df_translate('classes.FormTool.errors.ERROR_IN_TAB', 'A validation error occurred in the '.$tabs[$tabname]['label'].' tab.  Please verify that this tab\'s input is correct before saving.', array('tab'=>$tabs[$tabname]['label'])));
				
			}
			unset($currForm);
		}

		

		return (count($form->_errors) == 0 );
		
		
	}
	
	/**
	 * @brief Handles form submission for the given form.  It handles multi-tab forms
	 * and even sends an HTTP redirect to the correct tab upon submission, if
	 * the action requested was to go to a different tab.
	 *
	 * This uses the special --session:target:xyz POST variables to 
	 * see which tab is being submitted.
	 *
	 * @param Dataface_Record The record to be edited.
	 * @param HTML_QuickForm The form that is submitted.
	 * @param string $tab The name of the tab that has been submitted.
	 * @return void
	 */
	function handleTabSubmit($record, $form, $tab=null){
		$tabs = $record->tabs();
		$tabnames = array_keys($tabs);
		if ( count($tabs) > 1 and isset($tab) ){
			// We are working with tabs, so before we save, we should store the data in
			// a session variable.
			$this->storeSessionData(array('_submitValues'=>$form->_submitValues,'_submitFiles'=>$form->_submitFiles), $tab, null, $record->getId());
			
		}
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		$targets = preg_grep('/^--session:target:/', array_keys($query));
		
		if ( isset($tab) and count($targets) >0  ){
			// We aren't saving this session, so we'll just forward to 
			// the next tab.
			$target = reset($targets);
			
			$res = preg_match('/--session:target:(.*)$/', $target, $matches);
			if ( !$res ) throw new Exception("Programming error: no matching target in query.", E_USER_ERROR);
			
			$target = $matches[1];
			
			if ( $target == '__default__' ) $target = $query['--session:target:__default__'];
			if ( $target == '__save__' ) return;
			
			$currentTabKey = intval(array_search($tab, $tabnames));
			
			if ( $currentTabKey === false ){
				// Current tab was not in the list of tabs.. this si 
				// a problem
				return PEAR::raiseError("Sorry there was a problem finding the specified tab: ".$query['--tab']." in the tabs for the record ".$currentRecord->getId().".  The available tabs are '".implode(', ', $tabnames).".");
					
			} 
			if ( $target == '__next__' ){
				// The user clicked the 'next' button so they should
				// be directed to the next tab
				
				if ( isset($tabnames[$currentTabKey+1]) ){
					$target = $tabnames[$currentTabKey+1];
				} else {
					$target = $tab;
				}
			} else if ( $target == '__back__' ){
				// The user clicked the 'back' button so they should
				// be directed to the previous tab
				
				if ( isset($tabnames[$currentTabKey-1]) ){
					$target = $tabnames[$currentTabKey-1];
				} else {
					$target = $tab;
				}
			}
			
			
			// Now we just redirect to the next tab
			if ( isset( $query['-query'] ) ){
				$q = $query['-query'];
			} else if ( isset($_SERVER['HTTP_REFERER']) and strpos($_SERVER['HTTP_REFERER'], '?') !== false ){
				$q = substr($_SERVER['HTTP_REFERER'], strpos($_SERVER['HTTP_REFERER'], '?')+1);
				
			} else {
				$couples = array();
				foreach ( $record->keys() as $key=>$value ){
					$couples[] = urlencode($key).'='.urlencode($value);
				}
				$q = '-table='.urlencode($query['-table']).'&-action='.$query['-action'].'&'.implode('&', $couples);
			}
			
			if ( strpos($q, '&--form-session-key=') === false ) $q .= '&--form-session-key='.$this->getSessionKey();
			if ( strpos($q,'&--tab=') === false ) $q .= '&--tab='.urlencode($target);
			else $q = preg_replace('/([&?])--tab=[^&]*(&?)/', '${1}--tab='.urlencode($target).'$2', $q);
			$q = preg_replace('/[&?]--msg=[^&]*/', '',$q);
			
			$url = DATAFACE_SITE_HREF.'?'.$q;
			$app->redirect($url);
			
			
		} 

	
	
	}
	
	/**
	 * @brief Creates an data-structure representing the tabs that can be displayed for a 
	 * record form.
	 *
	 * @param Dataface_Record &$record The record that is being edited.
	 * @param HTML_QuickForm &$form The form that we are creating the tabs for.
	 * @param string $selectedTab The name of the tab that is currently selected.
	 * @return array Datastructure of the form:
	 * 		array(
	 *			array(	// Tab 1
	 *				'url' => ... // URL for the tab.
	 *				'css_class' => ... // The CSS class that should be used for the tab.
	 *			),
	 *			array( ... // Tab 2
	 *			),
	 *			...
	 *		);
	 */		
	function createHTMLTabs($record, $form, $selectedTab){
		$out = array();
		$formname = $form->getAttribute('name');
		$tabs = $record->_table->tabs($record);
		if ( !$tabs or count($tabs)<2 ) return null;
		foreach ( $tabs as $tab ){
			$tab['url'] = 'javascript: document.forms[\''.$formname.'\'].elements[\'--session:target:'.$tab['name'].'\'].click()';
			
			$tab['css_class'] = 'edit-form-tab'. ( ( $tab['name'] == $selectedTab ) ? ' selected tabs-selected':'');
			$out[] = $tab;
		}
		return $out;
	}
	
	/**
	 * @brief Returns the key that is used to keep track of this particular session.
	 * This manifests itself as the $_GET['--form-session-key'] variable. 
	 * If no session key is set, this will generate a new one and add
	 * a '--form-session-key' variable to the current $query.
	 *
	 * @return The current session key.
	 */
	function getSessionKey(){
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		if ( isset($query['--form-session-key']) ){
			return $query['--form-session-key'];
		} else {
			$key = rand().'_'.time();
			$query['--form-session-key'] = $key;
			return $key;
		}
	}
	
	/**
	 * @brief Returns an associative array of the session data, either for the current
	 * tab or for all tabs.
	 *
	 * @param string $tab The name of the tab that we wish to get data for.  
	 *		  If this is left null, it will return all tabs data.
	 * @param string $session_key The session key.  If this is left null, it will
	 *		  return data for the current session.
	 * @return array Associative array of session data, as it was submitted in the forms.
	 *   The data structure looks like:
	 *		array(
	 *			'tabs' => array(
	 *				'tab1' => array(
	 *					'myfield1' => 'val1',
	 *					... etc ...
	 *				),
	 *				'tab2' => array(
	 *					'myfieldx' => 'valx'
	 *					... etc ...
	 *				),
	 *				...
	 *			)
	 *		)
	 */
	function getSessionData($tab=null, $session_key = null){
		
		if ( !isset($session_key) ) $session_key = $this->getSessionKey();
		
		
		if ( isset($tab) and isset($_SESSION[$session_key]['tabs'][$tab]) ){
		
			return $_SESSION[$session_key]['tabs'][$tab];
			
		} else if ( !isset($tab) and  isset($_SESSION[$session_key]) ) {
			return $_SESSION[$session_key];
			
		} else {
			return null;
		}
	}
	
	/**
	 * @brief Clears the session data for the tab current form.  This does not clear 
	 * the session data for the entire session, just this form.
	 * @param string $session_key The session key that is being cleared.  Null
	 * for current key.
	 * @return void
	 */
	function clearSessionData($session_key=null){
		if ( !isset($session_key) ) $session_key = $this->getSessionKey();
		
		unset($_SESSION[$session_key]);
	}
	
	/**
	 * @brief Stores data from a form session so that it can be retrieved in the next
	 * page or the next time a tab is loaded for this form.
	 *
	 * If we are using a multi-tab implementation, then we need to store
	 * the data for each tab in sessions until we are ready to save the 
	 * entire record.
	 */
	function storeSessionData($data, $tab, $session_key = null, $record_id=null){
		if ( !isset($session_key) ) $session_key = $this->getSessionKey();
		if ( !isset($record_id) ){
			$app =& Dataface_Application::getInstance();
			$record =& $app->getRecord();
			if ( $record ){
				$record_id = $record->getId();
			}
		}
		if ( !isset($_SESSION[$session_key]) ) $_SESSION[$session_key] = array('tabs'=>array(),'table'=>$data['_submitValues']['-table'], 'id'=>$record_id);
		
		$_SESSION[$session_key]['tabs'][$tab] = $data;
		return true;
		
	}
	
	/**
	 * @brief Creates and HTML_QuickForm group that can be added a a QuickForm object
	 * to serve as the submit buttons for edit (or new record) forms. 
	 * By default this will create buttons for each tab, a back, next, and 
	 * save buttons.
	 *
	 * CSS is used to hide the tabs by default.
	 *
	 * @return array(HTML_QuickForm_element) An array of submit elements.
	 *
	 */ 
	function createRecordButtons($record, $currentTab=null){
		
		$factory =& self::factory();
		$out = array();
		$tabs = $record->tabs();
		$tabnames = array_keys($tabs);
		if ( count($tabnames) > 0 and !isset($currentTab) ) $currentTab = $tabnames[0];
		$saveButtonLabel = df_translate('tables.'.$record->table()->tablename.'.save_button_label', '');
		if ( !$saveButtonLabel ) $saveButtonLabel = df_translate('save_button_label','Save');
		$out[] = $factory->createElement('submit', '--session:save', $saveButtonLabel);
	
		
		if ( isset($currentTab) and count($tabnames)>1 ){
			if ( isset($tabs[$currentTab]['actions']['default']) ){
				$default = $tabs[$currentTab]['actions']['default'];
			} else {
				$default = '__save__';
			}
			$out[] = $factory->createElement('submit', '--session:target:__default__', $default, array('style'=>'display:none'));
			
			$currIndex = array_search($currentTab, $tabnames);
			$next = ( isset( $tabnames[$currIndex+1] ) ? $tabnames[$currIndex+1] : null);
			$prev = ( isset( $tabnames[$currIndex-1] ) ? $tabnames[$currIndex-1] : null);
			if ( isset($tabs[$currentTab]['actions']['next']) ) $next = $tabs[$currentTab]['actions']['next'];
			if ( isset($tabs[$currentTab]['actions']['back']) ) $prev = $tabs[$currentTab]['actions']['back'];
			$default = null;
			if ( isset($tabs[$currentTab]['actions']['default'] ) ) $default = $tabs[$currentTab]['actions']['default'];
			
			foreach ( $tabs as $tab ){
				if ( @$params['tab'] == $tab['name'] ) continue; // we don't include a button to the current tab
				$tabname = $tab['name'];
				$atts = array();
				
				if ( isset($tab['button']['atts']) ) $atts = $tab['button']['atts'];
				if ( isset($params['atts']['__global__']) ) $atts = array_merge($atts, $params['atts']['__global__']);
				if ( isset($params['atts'][$tab]) ) $atts = array_merge($atts, $params['atts'][$tab]);
				if ( !isset($atts['style']) ) $atts['style'] = 'display: none';
				
				
				
				$out[] = $factory->createElement('submit', '--session:target:'.$tabname, $tab['label'], $atts);
			}
		}
		if ( isset($prev) ) $out[] = $factory->createElement('submit', '--session:target:__back__', df_translate('scripts.GLOBAL.LABEL_BACK', 'Back'));
		if ( isset($next) ) $out[] = $factory->createElement('submit', '--session:target:__next__', df_translate('scripts.GLOBAL.LABEL_NEXT', 'Next'));
		
		return $out;
	}
	
	/**
	 * @brief Now that we allow tabbed forms, we may be temporarily storing data in 
	 * the session.  This goes through the session data and saves it to the 
	 * appropriate places
	 */
	function saveSession($record, $new=false, $session_key = null ){
		// First get the session data
		$session_data = $this->getSessionData(null, $session_key);
		if ( !isset($session_data) ) return false;
		
		// Next make sure that the session is acting on the same record
		if ( !$new and $session_data['id'] != $record->getId() ){

			return PEAR::raiseError('Failed to save session because the session data is not registered to the same record.');
		}
		
		if ( $session_data['table'] != $record->_table->tablename ){

			return PEAR::raiseError('Failed to save session because the session data is for a different table than the record.');
		}
		
		$joinRecords = array();	// A form to store all of the join records
								// that need to be saved.
		

		foreach ( array_keys($session_data['tabs']) as $tabname ){
			$temp =& $this->createRecordForm($record, $new, $tabname);
			
				// Note that this form could be a form for the $record object
				// or it could be a form for one of its join records
			
			$temp->_build();
			$this->decorateRecordForm($record, $temp, $new, $tabname);
			$temp->push();
			
			if ( $temp->_record->getId() != $record->getId() ){
				$joinRecords[$tabname] =& $temp->_record;
			}
			unset($temp);
		}
		
		// Now we need to save the current record..
		$res = $record->save(null, true);
		if ( PEAR::isError($res) ) return $res;
		
		// Now we save all of the join records
		
		foreach ( $joinRecords as $name=>$jrecord ){
			// Let's make sure we have the proper join keys so that the
			// record is linked properly to the current record.
			
			$jrecord->setValues($record->getJoinKeys($name));
			
			
			$res = $jrecord->save(null, true);
			if ( PEAR::isError($res) ){
				return $res;
			}
			unset($jrecord);
		}
		
		
		$this->clearSessionData($session_key);

		return true;
		
		
		
	}
	 
		
	

}

import('HTML/QuickForm.php');
/**
 * @brief An HTML_QuickForm class that can be used to build widgets that will eventually
 * be added to other forms.  It is the same as any other quickform except that it
 * handles the creation of multiple fields of the same name gracefully.
 */
class HTML_QuickFormFactory extends HTML_QuickForm {
	function HTML_QuickFormFactory($name){
		$this->HTML_QuickForm($name);
	}
	
	function &addElement($element){
		$args = func_get_args();
		if ( is_object($element) and $this->elementExists($element->getName()) ){
			$this->removeElement($element->getName());
		} else {
			
			if ( $this->elementExists($args[1]) ){
				$this->removeElement($args[1]);
			}
		}
		switch ( count($args) ){
			case 1:
				$res =& parent::addElement($args[0]);break;
			case 2:
				$res =& parent::addElement($args[0],$args[1]);break;
			case 3:
				$res =& parent::addElement($args[0],$args[1],$args[2]); break;
			case 4:
				$res =& parent::addElement($args[0], $args[1], $args[2], $args[3]);break;
			case 5:
				$res =& parent::addElement($args[0], $args[1], $args[2], $args[3], $args[4]);break;
			case 6:
				$res =& parent::addElement($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);break;
			case 7:
				$res =& parent::addElement($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);break;
		}
		return $res;
	}
}
