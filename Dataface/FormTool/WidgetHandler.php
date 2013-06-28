<?php
/**
 * @brief An interface for widget handlers.  This interface is not *real*.  It is
 * only used for documentation purposes to demonstrate the methods that can be 
 * implemented by widget handlers.
 *
 * A widget handler is used by Dataface_FormTool to define how widgets are built, 
 * how data is loaded into them and how data is retrieved from them.  All widgets
 * in Xataface are built on <a href="http://pear.php.net/package/HTML_QuickForm/redirected">HTML_QuickForm</a>
 * but it is the job of Dataface_FormTool to construct these widgets for the Xataface
 * application.
 *
 * Generally a widget handler will implement at least the buildWidget() method as this
 * defines how the widget is built.  Some of them will also define pushValue() and 
 * pullValue() methods which serve as transformers to move data from an underlying
 * Dataface_Record object to the widget and back.
 *
 * @section widgethandler_registration Registering Widget Handlers with the Form Tool
 *
 * Before a widget handler can be used it needs to be registered with Dataface_FormTool
 * so that it knows which widget type the handler should be used to render.
 *
 * @see Dataface_FormTool::registerWidgetHandler()
 *
 * @section widgethandler_custom_widgets Building Custom Widgets
 *
 * Building a custom widget requires two key parts:
 *
 * -# Create a widget handler.
 * -# Register the widget handler with the Dataface_FormTool object.
 *
 * The recommended approach for creating a new widget is to package it in a module.  This
 * gives you maximum flexibility to includej all of the files necessary for your widget
 * and make it portable.
 *
 * - See @ref module_developer_guide_widget For more a brief tutorial on developing a 
 * custom widget as a module.
 */
interface WidgetHandler {


	/**
	 * @brief Creates a widget for a specific field on a form. 
	 *
	 * @param Dataface_Record $record The Record that is being edited by this form.
	 * @param array &$field The field definition of the field (per the fields.ini file)
	 * @parm HTML_QuickForm The form that the field is to be added to.
	 * @param string $formFieldName The name of the field within the form.
	 * @param boolean $new Whether this form is to create a new record.  If false then it
	 *	is to edit an existing record.
	 *
	 * @see Dataface_FormTool::buildWidget()
	 * @see <a href="http://pear.php.net/package/HTML_QuickForm/docs/3.2.13/HTML_QuickForm/HTML_QuickForm.html">HTML_QuickForm</a>
	 *
	 * @par Example From The Hidden Widget
	 * @code
	 * function buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
     *		// Get a quickform factory to create a base widget with the 
     *		// field's name
     *		$factory =& Dataface_FormTool::factory();
     *		$el =& $factory->addElement('hidden', $field['name']);
     *		if ( PEAR::isError($el) ) {
     *		
     *			throw new Exception("Failed to get element for field $field[name] of table ".$record->_table->tablename."\n"
     *				."The error returned was ".$el->getMessage(), E_USER_ERROR);
     *
     *		}
     *		// Store the field definition in the widget so that it is accessible later
     *		// on.
     *		$el->setFieldDef($field);
     *		// return the element
     *		return $el;
     *	}
     * @endcode
     *
     * @par Exmaples
     * - Dataface_FormTool_select::buildWidget()
     * - Dataface_FormTool_htmlarea::buildWidget()
     * - Dataface_FormTool_lookup::buildWidget()
     * - Dataface_FormTool_calendar::buildWidget()
     * - Dataface_FormTool_yui_autocomplete::buildWidget()
	 */
	public function buildWidget(
		Dataface_Record $record, 
		array &$field, 
		HTML_QuickForm $form, 
		$formFieldName, 
		$new=false);
		
	
	/**
	 * @brief Pulls contents of a column in a Dataface_Record object and places 
	 * the value into the specified field.  This method is optional as the Dataface_FormTool
	 * will perform this duty automatically if this method is omitted.   Only in more
	 * complex situations is this method required.  Even if the FormTool's default behavior
	 * for this is insufficient, it is still possible that the pullValue() method would
	 * be sufficient for most complex purposes.
	 *
	 *
	 * @param Dataface_Record $record The record from which to pull the value.
	 * @param array &$field The field definition of the field we are pulling.
	 * @param HTML_QuickForm $form The form in which the widget is currently rendered.
	 * @param String $formFieldName The name of the field in the form.
	 * @param boolean $new True if this is a new record form.  False otherwise.
	 * @returns mixed PEAR_Error if there is an error.  True on success.
	 *
	 * @see Dataface_FormTool::pullField()
	 * @see pullValue()
	 */
	public function pullField(
		Dataface_Record $record, 
		array &$field, 
		HTML_QuickForm $form, 
		$formFieldName, 
		$new=false);
		
		
	/**
	 * @brief Retrieves the contents of a column in a Dataface_Record object in a form
	 * that can be placed in the widget.  This method is optional as the Dataface_FormTool
	 * will perform this duty automatically if this method is omitted.   Only in more
	 * complex situations is this method required.  
	 *
	 * If implemented, this should be the inverse of the pushValue() method.
	 *
	 * @param Dataface_Record $record The record from which to pull the value.
	 * @param array &$field The field definition of the field we are pulling.
	 * @param HTML_QuickForm $form The form in which the widget is currently rendered.
	 * @param String $formFieldName The name of the field in the form.
	 * @param boolean $new True if this is a new record form.  False otherwise.
	 * @returns mixed The value that was pulled from the record and can be added to the field.
	 *
	 * @see pullField()
	 */
	public function pullValue(
		Dataface_Record $record, 
		array &$field, 
		HTML_QuickForm $form, 
		$formFieldName, 
		$new=false);
		
	
	/**
	 * @brief Pushes a value from a form widget into the underlying Dataface_Record object.  This
	 * acts as the inverse of the pullField() method.  This method is optional and is only 
	 * required for more complex situations where the default functionality of the Dataface_FormTool::pushField()
	 * method is insufficient.
	 *
	 * @param Dataface_Record The record that the value is being pushed into.
	 * @param array &$field The field definition of the field to pull (per fields.ini file).
	 * @param HTML_QuickForm The form where the field resides.
	 * @param string $formFieldName The name of the field within the form.
	 * @param boolean $new Whether this is a new record form.  False if it is a form for editing an existing record.
	 *
	 * @returns mixed PEAR_Error If there is an error.  True on success.
	 * 
	 * @see <a href="http://pear.php.net/package/HTML_QuickForm/docs/3.2.13/HTML_QuickForm/HTML_QuickForm.html">HTML_QuickForm</a>
	 * @see pullField()
	 * @see Dataface_FormTool::pushField()
	 */
	public function pushField(
		Dataface_Record $record, 
		array &$field, 
		HTML_QuickForm $form, 
		$formFieldName, 
		$new=false);
		
		
	/**
	 * @brief Retrieves the value from a form widget that is ready to be added to the underlying
	 * Dataface_Record object.  This is an optional method that only needs to be implemented
	 * if the default behavior of Dataface_FormTool::pushField() is not sufficient.
	 *
	 *
	 * @param Dataface_Record The record that the value is being pushed into.
	 * @param array &$field The field definition of the field to pull (per fields.ini file).
	 * @param HTML_QuickForm The form where the field resides.
	 * @param string $formFieldName The name of the field within the form.
	 * @param boolean $new Whether this is a new record form.  False if it is a form for editing an existing record.
	 *
	 * @returns mixed PEAR_Error If there is an error.  True on success.
	 * 
	 * @see <a href="http://pear.php.net/package/HTML_QuickForm/docs/3.2.13/HTML_QuickForm/HTML_QuickForm.html">HTML_QuickForm</a>
	 * @see pullField()
	 * @see Dataface_FormTool::pushField()
	 *
	 * @par Examples
	 * -# Dataface_FormTool_select::pushField()
	 */
	public function pushField(
		Dataface_Record $record, 
		array &$field, 
		HTML_QuickForm $form, 
		$formFieldName, 
		$new=false);

}
