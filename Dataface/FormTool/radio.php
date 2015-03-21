<?php
/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_radio {
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		$table =& $record->_table;
		$widget =& $field['widget'];
		
		if ( !@$widget['separator'] ) $widget['separator'] = '<br />';
		$factory =& Dataface_FormTool::factory();
		$boxes = array();
        if ( @$field['vocabulary'] ){
            $options =& Dataface_FormTool::getVocabulary($record, $field);
            $options__classes = Dataface_FormTool::getVocabularyClasses($record, $field);
        }
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
        $dummyForm = new HTML_QuickForm();
        foreach ($options as $opt_val=>$opt_text){
            if ( $opt_val==='' ){
                if (!(@$field['validators'] && @$field['validators']['required'])){
                    //$boxes[] = $dummyForm->createElement('radio', $field['name'], null , 'None', '', array('class'=>'radio-of-'.$field['name'].' '.@$options__classes[$opt_val]));
                }
            } else {
                $boxes[] =& $dummyForm->createElement('radio', $field['name'], null , $opt_text, $opt_val, array('class'=>'radio-of-'.$field['name'].' '.@$options__classes[$opt_val]));
            }
            
        }
        $el =& $factory->addGroup($boxes, $field['name'], $widget['label']);
		
		return $el;
	}
	
	function &pushValue(&$record, &$field, &$form, &$element, &$metaValues){
		$table =& $record->_table;
		$formTool =& Dataface_FormTool::getInstance();
		$formFieldName = $element->getName();
		
		$val = $element->getValue();
		//print_r($val);exit;
		if (is_array($val)){
		    $val = $val[$field['name']];
		}
		
        if ( preg_match('/int/', @$field['Type']) and $val!=='' and $val!==null){
            //print_r($_POST);
            //echo "Val is $val";exit;
            $out = intval($val);
        } else {
            //echo "Out is $val";exit;
            $out = $val;
        }
        
        //$res =& $s->setValue($fieldname, $val);
    
		if (PEAR::isError($val) ){
			$val->addUserInfo(
				df_translate(
					'scripts.Dataface.QuickForm.pushValue.ERROR_PUSHING_VALUE',
					"Error pushing value for field '$field[name]' in QuickForm::pushWidget() on line ".__LINE__." of file ".__FILE__,
					array('name'=>$field['name'],'file'=>__FILE__,'line'=>__LINE__)
					)
				);
			return $val;
		}
		return $out;
	}
	
	function pullValue(&$record, &$field, &$form, &$element, $new=false){
		//echo "Pulling value";
		//print_r($record->vals());exit;
		$val = $record->getValueAsString($field['name']);
        //echo "Pulled is $val";
    
		
		return $val;
	}
}
