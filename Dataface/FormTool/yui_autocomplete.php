<?php
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['yui_autocomplete'] = array('HTML/QuickForm/yui_autocomplete.php', 'HTML_QuickForm_yui_autocomplete');
/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_yui_autocomplete {
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		/*
		 * This field uses a calendar widget
		 */
		
		$widget =& $field['widget'];
		$factory =& Dataface_FormTool::factory();
		$el =& $factory->addElement('yui_autocomplete', $formFieldName, $widget['label']);
		$el->setProperties($widget);

		if ( @$field['vocabulary'] ){
			$el->options = $record->_table->getValuelist($field['vocabulary']);
			$el->vocabularyName = $field['vocabulary'];
			$el->updateAttributes(array('df:vocabulary'=>$el->vocabularyName));
		} else if ( isset($widget['datasource']) ){
			$datasource =& $widget['datasource'];
			if ( isset($datasource['url']) ){
				$el->datasourceUrl = $datasource['url'];
				$el->updateAttributes(array('df:datasource'=>$el->datasourceUrl));
				
				if ( !isset($datasource['resultNode']) ) $datasource['resultNode'] = 'Result';
				$el->resultNode = $datasource['resultNode'];
				$el->updateAttributes(array('df:resultNode'=>$el->resultNode));
				
				if ( !isset($datasource['fieldname']) ) $datasource['fieldname'] = 'df:title';
				$el->queryKeyNode = $datasource['fieldname'];
				$el->updateAttributes(array('df:queryKeyNode'=>$el->queryKeyNode));
				
				if ( !@$datasource['other_fieldnames'] ){
					$el->additionalNodes = array();
				} else {
					$el->additionalNodes = array_map('trim', explode(',', $datasource['other_fieldnames']));
				}
				$el->updateAttributes(array('df:additionalNodes'=>implode(',',$el->additionalNodes)));
				
				if ( !@$datasource['scriptQueryParam'] ){
					$datasource['scriptQueryParam'] = '-search';
					
				}
				
				$el->scriptQueryParam = $datasource['scriptQueryParam'];
				$el->updateAttributes(array('df:scriptQueryParam'=>$el->scriptQueryParam));
			}
		}
		
		if ( @$field['yui_autocomplete'] and is_array($field['yui_autocomplete']) ){
		    $el->updateAttributes(
		        array(
		            'data-xf-max-results-displayed' => $field['yui_autocomplete']['maxResultsDisplayed']
		        )
		    ); 
		}
	
		return $el;
	}
}
