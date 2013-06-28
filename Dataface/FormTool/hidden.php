<?php
/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_hidden {
	function &buildWidget(&$record, &$field, &$form, $formFieldName, $new=false){
		$factory =& Dataface_FormTool::factory();
		$el =& $factory->addElement('hidden', $field['name']);
		if ( PEAR::isError($el) ) {
		
			throw new Exception("Failed to get element for field $field[name] of table ".$record->_table->tablename."\n"
				."The error returned was ".$el->getMessage(), E_USER_ERROR);

		}
		$el->setFieldDef($field);
		return $el;
	}
}
