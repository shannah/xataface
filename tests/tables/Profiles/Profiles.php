<?php
// A delegate class for the Profiles table.
// This may contain parsing functions, serializing functions, and formatting functions
// It may also contain permissions functions.
// It may also contain
class tables_Profiles {

	
	
	
	function fname__link(&$record){
		if ( !is_a($record, "Dataface_Record") ){
			trigger_error("in tables_Profiles::fname__link() expecting 'Dataface_Record' as first argument but received '".get_class($record)."'.\n<br>".Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		
		return array(
			"-action"=>"browse",
			"-table"=>"Profiles",
			"fname"=>$record->strval('fname'),
			"lname"=>$record->strval('lname'),
			"description"=>"My name is \$fname");
	}
	
	function lname__link(&$record){
		if ( !is_a($record, "Dataface_Record") ){
			trigger_error("in tables_Profiles::lname__link() expecting 'Dataface_Record' as first argument but received '".get_class($record)."'.\n<br>".Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		
		
		return "http://www.google.ca?fname=".$record->strval('fname')."&lname=".$record->strval('lname');
	}
	
	function description__link(&$record){
		
		return "http://www.google.ca?fname=\$fname&lname=\$lname";
	}
	
	/**
	 * An import function to import XML.
	 * @param $data Raw data to be converted to Record objects.
	 * @return array of Dataface_Record objects.
	 *
	 */
	function __import__xml($data){
		require_once 'Dataface/ImportFilter/xml.php';
		$xmlfilter = new Dataface_ImportFilter_xml();
		$importRecords = $xmlfilter->import($data);
		return $importRecords;
	}
	
	/**
	 * An import function.
	 * @param $data Raw data to be converted to Record objects.
	 * @return array of Dataface_Record objects.
	 */
	function __import__test2($data){
	
	
	}
	
	function beforeSave(&$record){
		echo " beforeSave";
	
	}
	
	function afterSave(&$record){
		echo " afterSave";
	}
	
	function beforeInsert(&$record){
		echo " beforeInsert";
	}
	
	function afterInsert(&$record){
		echo " afterInsert";
	}
	
	function beforeUpdate(&$record){
		echo " beforeUpdate";
	}
	
	
	function afterUpdate(&$record){
		echo " afterUpdate";
	}
	
	function beforeAddRelatedRecord(&$record){
		echo " beforeAddRelatedRecord";
	}
	
	function afterAddRelatedRecord(&$record){
		echo " afterAddRelatedRecord";
	}
	
	function beforeAddNewRelatedRecord(&$record){
		echo " beforeAddNewRelatedRecord";
	}
	
	function afterAddNewRelatedRecord(&$record){
		echo " afterAddNewRelatedRecord";
	}
	
	function beforeAddExistingRelatedRecord(&$record){
		echo " beforeAddExistingRelatedRecord";
	}
	
	function afterAddExistingRelatedRecord(&$record){
		echo " afterAddExistingRelatedRecord";
	}
}


?>
