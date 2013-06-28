<?php
class Dataface_Field {
	var $atts;
	
	function Field(&$atts){
		$this->atts =& $atts;
	}	
	
	function getName(){ return $this->atts['name'];}
	function setName($name){ $this->atts['name'] = $name;}
	function &getWidget(){ return $this->atts['widget'];}
	function getType(){ return $this->atts['Type']; }
	function getWidgetType(){ return $this->atts['widget']['type']; }
	function getWidgetDescription(){ return $this->atts['widget']['description'];}
	function getWidgetQuestion(){ return $this->atts['widget']['question'];}
	function getWidgetLabel(){ return $this->atts['widget']['label'];}
	function getTableName(){ return $this->atts['table']; }

}
