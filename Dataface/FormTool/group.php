<?php
import('Dataface/FormTool/table.php');
/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_group extends Dataface_FormTool_table {

	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		$formTool =& Dataface_FormTool::getInstance();
		$factory =& Dataface_FormTool::factory();
		if ( isset( $field['fields'] ) ){
			$els = array();
			foreach ( array_keys( $field['fields'] ) as $field_key){
				$els[] = $formTool->buildWidget($record, $field['fields'][$field_key],$factory, $field['fields'][$field_key]['name']);
			}
			$el =& $factory->addGroup($els, $field['name'], $field['widget']['label']);
		} else{
			$el =& $factory->addElement('text', $field['name'], $widget['label']);
		}
		if ( !@$field['widget']['layout'] ) $field['widget']['layout'] = 'table';
		if ( !@$field['widget']['layout'] ) $field['widget']['columns'] = 1;
		if ( !@$field['widget']['separator'] ) $field['widget']['separator'] = '<br />';
		$el->setFieldDef($field);
		return $el;
	}
}
