<?php

/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_select {
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		$table =& $record->_table;
		
		$widget =& $field['widget'];
		$factory = Dataface_FormTool::factory();
		$attributes = array('class'=>$widget['class'], 'id'=>$field['name']);
		if ( $field['repeat'] ){
			$attributes['multiple'] = true;
			$attributes['size'] = 5;
		}
		$options = $record->_table->getValuelist($field['vocabulary']);//Dataface_FormTool::getVocabulary($record, $field);
		if ( !isset( $options) ) $options = array();
		$emptyOpt = array(''=>df_translate('scripts.GLOBAL.FORMS.OPTION_PLEASE_SELECT',"Please Select..."));
		$opts = $emptyOpt;
		if ( $record and $record->val($field['name']) ){
			if ( !@$field['repeat'] and !isset($options[$record->strval($field['name'])]) ){
				$opts[$record->strval($field['name'])] = $record->strval($field['name']);
			} else if ( @$field['repeat'] ){
			
				$vals = $record->val($field['name']);
				if ( is_array($vals) ){
					foreach ( $vals as $thisval){
						if ( !isset($options[$thisval]) ){
							$opts[$thisval] = $thisval;
						}
					}
				}
			}
		}
		foreach($options as $kopt=>$opt){
			$opts[$kopt] = $opt;
		}
		
		$el =  $factory->addElement('select', $formFieldName, $widget['label'], $opts, $attributes  );
		
		// Now to make it editable
		if ( @$field['vocabulary'] ){
			try {
				$rel =& Dataface_ValuelistTool::getInstance()->asRelationship($table, $field['vocabulary']);
				if ( $rel and !PEAR::isError($rel) ){
					if ( !is_a($rel, 'Dataface_Relationship') ){
						throw new Exception("The relationship object for the vocabulary ".$field['vocabulary']." could not be loaded.");
						
					}
					if ( !$rel->getDomainTable() ){
						throw new Exception("The relationship object for the vocabulary ".$field['vocabulary']." could not be loaded or the domain table could not be found");
					}
					$dtable = Dataface_Table::loadTable($rel->getDomainTable());
					if ( $dtable and !PEAR::isError($dtable) ){
						$perms = $dtable->getPermissions();
						if ( @$perms['new'] ){
							$fields =& $rel->fields();
							if ( count($fields) > 1 ) {
								$valfield = $fields[1];
								$keyfield = $fields[0];
							}
							else {
								$valfield = $fields[0];
								$keyfield = $fields[0];
							}
							if ( strpos($valfield, '.') !== false ) list($tmp, $valfield) = explode('.', $valfield);
							if ( strpos($keyfield, '.') !== false ) list($tmp, $keyfield) = explode('.', $keyfield);
							$jt = Dataface_JavascriptTool::getInstance();
							$jt->import('RecordDialog/RecordDialog.js');
							//$suffix =  '<script type="text/javascript" src="'.DATAFACE_URL.'/js/jquery-ui-1.7.2.custom.min.js"></script>';
							//$suffix .= '<script type="text/javascript" src="'.DATAFACE_URL.'/js/RecordDialog/RecordDialog.js"></script>';
							$suffix = '<a href="#" onclick="return false" id="'.df_escape($field['name']).'-other">Other..</a>';
							$suffix .= '<script>
							jQuery(document).ready(function($){
								$("#'.$field['name'].'-other").each(function(){
									var tablename = "'.addslashes($dtable->tablename).'";
									var valfld = '.json_encode($valfield).';
									var keyfld = '.json_encode($keyfield).';
									var fieldname = '.json_encode($field['name']).';
									var btn = this;
									$(this).RecordDialog({
										table: tablename,
										callback: function(data){
											var key = data[keyfld];
											var val = data[valfld];
                                                                                        var $option = $(\'<option value="\'+key+\'">\'+val+\'</option>\');
                                                                                        
											$("#"+fieldname).append($option);
											$("#"+fieldname).val(key);
                                                                                        if ( !val || val === key ){
                                                                                            var q = {
                                                                                                "-action" : "field_vocab_value",
                                                                                                "-key" : key,
                                                                                                "-table" : '.json_encode($field['tablename']).',
                                                                                                "-field" : '.json_encode($field['name']).'
                                                                                            };
                                                                                            $.get(DATAFACE_SITE_HREF, q, function(res){
                                                                                                if ( res && res.code === 200 ){
                                                                                                    $option.text(res.value);
                                                                                                }
                                                                                            });
                                                                                        }
											
										}
									});
								});
							});
							</script>
							';
							$widget['suffix'] = $suffix;
						}
					}
					
					
				}
			} catch (Exception $ex){
				error_log($ex->getMessage());
			}
		}
		
		//$el->setFieldDef($field);
		//return $el;
		return $el;
	}
	
	function pushValue(&$record, &$field, &$form, &$element, &$metaValues){
		// quickform stores select fields as arrays, and the table schema will only accept 
		// array values if the 'repeat' flag is set.
		$table =& $record->_table;
		$formTool =& Dataface_FormTool::getInstance();
		//$formFieldName =& $element->getName();
		
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
