<?php
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['advmultiselect'] = array('HTML/QuickForm/advmultiselect.php', 'HTML_QuickForm_advmultiselect');

/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_advmultiselect {
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		$table =& $record->_table;
		
		$widget =& $field['widget'];
		if ( !@$widget['repeat'] ) $widget['repeat'] = 1;
		$factory =& Dataface_FormTool::factory();
		$attributes = array('class'=>$widget['class'], 'id'=>$field['name']);
		if ( $field['repeat'] ){
			$attributes['multiple'] = true;
			$attributes['size'] = 5;
		}
		$options =& Dataface_FormTool::getVocabulary($record, $field);
		
		if ( !isset( $options) ) $options = array();
		
		if ( $record and $record->val($field['name']) ){
			$vals = $record->val($field['name']);
			if ( is_array($vals) ){
				foreach ( $vals as $thisval){
					if ( !isset($options[$thisval]) ){
						$options[$thisval] = $thisval;
					}
				}
			}
		
		}
		$el =&  $factory->addElement('advmultiselect', $formFieldName, $widget['label'], $options, $attributes  );
		//$el->setFieldDef($field);
		//return $el;
		
		$el->setButtonAttributes('moveup'  , 'class=inputCommand');
		$el->setButtonAttributes('movedown', 'class=inputCommand');
		return $el;
	}
	
	function pushValue(&$record, &$field, &$form, &$element, &$metaValues){
		// quickform stores select fields as arrays, and the table schema will only accept 
		// array values if the 'repeat' flag is set.
		$table =& $record->_table;
		$formTool =& Dataface_FormTool::getInstance();
		$formFieldName =& $element->getName();
		
		if ( !$field['repeat'] ){
			$val = $element->getValue();
			if ( count($val)>0 ){
				return $val[0];
				
			} else {
				return null;
				
			}
		} else {
			return $element->getValue();
		}
			
		
		
	}
	
	
}
