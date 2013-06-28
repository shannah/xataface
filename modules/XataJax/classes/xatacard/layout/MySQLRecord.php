<?php
import('xatacard/layout/Record.php');
class xatacard_layout_MySQLRecord extends xatacard_layout_Record {
	
	/**
	 * @type array(string=>Dataface_Record)
	 */
	private $baseRecords;
	
	/**
	 * @type array(string=>Dataface_Record)
	 */
	private $fieldRecords;
	
	
	public function setValue($path, $value){
		$rec = @$fieldRecords[$path];
		if ( !$rec ){
			throw new Exception(sprintf(
				"No underlying record found for field '%s' while trying to set value",
				$path
			));
		}
		
		parent::setValue($path, $value);
		
		
	}
	
	
	
	
}
