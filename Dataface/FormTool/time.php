<?php
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['time'] = array('HTML/QuickForm/time.php', 'HTML_QuickForm_time');
/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_time {
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		
		$widget =& $field['widget'];
		$factory =& Dataface_FormTool::factory();
		$el =& $factory->addElement('time', $formFieldName, $widget['label'], array(), $widget);
		return $el;
	}
}

