<?php
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['htmlarea'] = array('HTML/QuickForm/htmlarea.php', 'HTML_QuickForm_htmlarea');
/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_htmlarea {
	function &buildWidget(&$record,&$field, &$form, $formFieldName, $new=false){
		if ( is_string($field) ) throw new Exception("buildWidget expects field to be a structure but recieved a string: $field", E_USER_ERROR);
		
		$table =& $record->_table;

		$widget =& $field['widget'];
		
		$factory =& Dataface_FormTool::factory();
		$el =& $factory->addElement('htmlarea', $formFieldName, $widget['label'],array('class'=>$widget['class'], 'id'=>$field['name']) );
		
		if ( method_exists($el, 'setWysiwygOptions') ){
			$el->setWysiwygOptions($widget);
		
			if ( isset($widget['editor']) ){
				$el->editorName = $widget['editor'];
			}
		}
		return $el;
	}

}
