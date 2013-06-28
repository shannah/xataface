<?php
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['table'] = array('HTML/QuickForm/table.php', 'HTML_QuickForm_table');

/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_table {

	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		/*
		 *
		 * This field uses a table widget.
		 *
		 */
		
		$table =& $record->_table;
		$formTool =& Dataface_FormTool::getInstance();
		$factory =& Dataface_FormTool::factory();
		$widget =& $field['widget'];
		$el =& $factory->addElement('table',$formFieldName, $widget['label']);
		if ( isset($widget['fields']) ){
			$widget_fields =& $widget['fields'];
			foreach ($widget_fields as $widget_field){
				$widget_field =& Dataface_Table::getTableField($widget_field, $this->db);

				if ( PEAR::isError($widget_field) ){
					return $widget_field;
				}
				
				$widget_widget = $formTool->buildWidget($record, $widget_field, $factory, $widget_field['name']);
				$el->addField($widget_widget);
			}
		} else if ( isset($field['fields']) ){
			foreach ( array_keys($field['fields']) as $field_key){
				$widget_widget = $formTool->buildWidget($record, $field['fields'][$field_key], $factory, $field['fields'][$field_key]['name']);
				$el->addField($widget_widget);
				unset($widget_widget);
			
			}
		}

		return $el;
	}
	
	function pullValue(&$record, &$field, &$form, &$element, $new=false){
		return $record->getValue($field['name']);
	}
}
