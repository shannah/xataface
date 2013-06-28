<?php
class tables_formula_ingredients {

	var $testPermissions = false;

	function getPermissions($record){
		if ( !$this->testPermissions) return null;
		return array('view'=>0);
	}
	
	function ingredient_id__permissions($record){
		if ( !$this->testPermissions) return null;
		return array('view'=>1);
	}
	
	function concentration_units__permissions($record){
		if ( !$this->testPermissions) return null;
		return array('view'=>1);
	}
}
