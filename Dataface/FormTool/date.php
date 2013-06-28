<?php
/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_date {
	function &buildWidget(&$record, &$field, $form, $formFieldName, $new=false){
		
		$widget =& $field['widget'];
		$factory =& Dataface_FormTool::factory();
		$el =& $factory->addElement('date', $formFieldName, $widget['label'], $widget);
		return $el;
		
	}
	
	function pushValue(&$record, &$field, &$form, &$element, &$metaValues){
		$table =& $record->_table;
		$formTool =& Dataface_FormTool::getInstance();
		$formFieldName = $element->getName();
		if ( $table->isDate($field['name']) ){
			return Dataface_converters_date::qf2Table($element->getValue());
			
		} else if ( $table->isInt($field['name']) ){
			return Dataface_converters_date::qf2UnixTimestamp($element->getValue()) ;
			
		} else {
			return Dataface_converters_date::datetime_to_string(
									Dataface_converters_date::qf2Table($element->getValue())
								);
		}
		
	}
}
