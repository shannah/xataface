<?php
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['advmultiselect'] = array('HTML/QuickForm/advmultiselect.php', 'HTML_QuickForm_advmultiselect');

/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_advmultiselect {
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		$table =& $record->_table;
		
		$widget =& $field['widget'];
		if ( !@$field['repeat'] ) $field['repeat'] = 1;
		$factory =& Dataface_FormTool::factory();
		$attributes = array('class'=>$widget['class'], 'id'=>$field['name']);
		if ( $field['repeat'] ){
			$attributes['multiple'] = true;
			$attributes['size'] = 5;
		}
		$options =& Dataface_FormTool::getVocabulary($record, $field);
		
		if ( !isset( $options) ) $options = array();
		
		/*
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
		*/
		foreach ($options as $val) {
		    $opts[$val] = $val;
		}
		
		$el =&  $factory->addElement('advmultiselect', $formFieldName, $widget['label'], $opts  , $attributes  );
		//$el->setFieldDef($field);
		//return $el;
		
		$el->setButtonAttributes('moveup'  , 'class=inputCommand');
		$el->setButtonAttributes('movedown', 'class=inputCommand');
		return $el;
	}
	
	function pushValue(&$record, &$field, &$form, &$element, &$metaValues){
	    $field['repeat'] = 1;
	    $options =& Dataface_FormTool::getVocabulary($record, $field);
	    if ( !isset( $options) ) $options = array();
		
		$vals = $element->getValue();
		$out = array();
		if (is_array($vals)) {
		    foreach ($vals as $v) {
		        if (!$v) {
		            continue;
		        }
		        $key = array_search($v, $options);
		        if ($key) {
		            $out[] = $key;
		        }
		    }
		}
		return $out;
		
	}
	function pullValue(&$record, &$field, &$form, &$element, $new=false){
	    $field['repeat'] = 1;
		$options =& Dataface_FormTool::getVocabulary($record, $field);
		if ( !isset( $options) ) $options = array();
		$vals = $record->getValue($field['name']);
		$out = array();
		if (!is_array($vals) and $vals) {
		    $vals = array($vals);
		}
		if (is_array($vals)) {
		    foreach ($vals as $k) {
		        $v = @$options[$k]; 
		        if ($v) {
		            $out[$v] = $v;
		        }
		    }
		} 
		return $out;
	}
	
	
}
