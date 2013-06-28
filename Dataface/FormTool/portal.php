<?php
/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_portal {
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		$widget =& $field['widget'];
		$factory =& Dataface_FormTool::factory();
		$widget['record'] =& $record;
		$el =& $factory->addElement('portal', $formFieldName, $widget['label']);
		$el->init_portal($widget);
		return $el;
	}
}
