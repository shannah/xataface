<?php
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['calendar'] = array('HTML/QuickForm/calendar.php', 'HTML_QuickForm_calendar');

/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_calendar {
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		/*
		 * This field uses a calendar widget
		 */
		
		$widget =& $field['widget'];
		if ( !@$widget['lang'] ){
			$widget['lang'] = Dataface_Application::getInstance()->_conf['lang'];
		}
		$factory =& Dataface_FormTool::factory();
		$el =& $factory->addElement('calendar', $formFieldName, $widget['label'], null, $widget);
		//$el->setProperties($widget);
	
		return $el;
	}
	
	/**
	 * @brief Added support to transform date values in alternate formats
	 * as provided by the widget:ifFormat directive.
	 * http://xataface.com/forum/viewtopic.php?f=4&t=5345
	 */
	function pushValue(&$record, &$field, &$form, &$element, &$metaValues){
		$table =& $record->_table;
		$formTool =& Dataface_FormTool::getInstance();
		$formFieldName = $field['name'];
		$val = $element->getValue();
		if ( !trim($val) ) return null;
		if ( @$field['widget']['ifFormat'] ){
			
			$ts = $this->strptime($element->getValue(), $field['widget']['ifFormat']);
			$ts = mktime($ts['tm_hour'], $ts['tm_min'], $ts['tm_sec'],
      					$ts['tm_mon']+1, $ts['tm_mday'], ($ts['tm_year'] + 1900));
      		return date('Y-m-d H:i:s', $ts);
		} else {
			return $element->getValue();
		}
		
	}
	
	private function strptime($date, $format){
	    if ( function_exists('strptime') ){
	        return strptime($date, $format);
	    } else {
	        $masks = array( 
              '%d' => '(?P<d>[0-9]{2})', 
              '%m' => '(?P<m>[0-9]{2})', 
              '%Y' => '(?P<Y>[0-9]{4})', 
              '%H' => '(?P<H>[0-9]{2})', 
              '%M' => '(?P<M>[0-9]{2})', 
              '%S' => '(?P<S>[0-9]{2})'
            ); 
        
            $rexep = "#".strtr(preg_quote($format), $masks)."#"; 
            if(!preg_match($rexep, $date, $out)) 
              return false; 
        
            $ret = array( 
              "tm_sec"  => (int) $out['S'], 
              "tm_min"  => (int) $out['M'], 
              "tm_hour" => (int) $out['H'], 
              "tm_mday" => (int) $out['d'], 
              "tm_mon"  => $out['m']?$out['m']-1:0, 
              "tm_year" => $out['Y'] > 1900 ? $out['Y'] - 1900 : 0, 
            ); 
            return $ret; 
	    
	    }
	}
	
	/**
	 * @brief Added support to transform date values in alternate formats
	 * as provided by the widget:ifFormat directive.
	 * http://xataface.com/forum/viewtopic.php?f=4&t=5345
	 */
	function pullValue(&$record, &$field, &$form, &$element, &$metaValues){
		
		$table =& $record->_table;
		$formTool =& Dataface_FormTool::getInstance();
		$formFieldName = $field['name'];
		$val = $record->strval($formFieldName);
		if ( !trim($val) ) return '';
		if ( @$field['widget']['ifFormat'] ){
			return strftime($field['widget']['ifFormat'], strtotime($val));
			
		} else {
			return $val;
		}
		
	}
}
