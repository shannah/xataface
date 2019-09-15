<?php
// A delegate class for the Profiles table.
// This may contain parsing functions, serializing functions, and formatting functions
// It may also contain permissions functions.
// It may also contain
class tables_Profiles {
/*
	//Examples of functions
	
	// Signatures
	// ==========
	//
	// Field Functions
	// ----------------
	// function field_name__parse($value);
	// function field_name__pushValue($value, &$quickform_element);
	// function field_name__pullValue(&$quickform_element);
	// function field_name__serialize($value);
	// function field_name__toString($value);
	// function field_name__permissions($user, &$record);
	// function field_name__link($values=null);
	//
	// Table Functions
	// ----------------
	// function permissions($user, &$record);
	// function init(&$table);
	// function beforeSave(&$record);
	// function afterSave(&$record);
	// function beforeUpdate(&$record);
	// function afterUpdate(&$record);
	// function beforeDelete(&$record);
	// function afterDelete(&$record);

	
	// A Parsing function that doesn't do anything.  This is called anytime the
	// value of the `created` field's value is set.  It should handle any type
	// of input that may be received especially input as would be received from
	// the database directly.  It must also be able to handle input in the native
	// format.  Ie: if $value is given in the correct format to be stored, it should
	// be passed through with no change.  
	//
	// assertTrue( created__parse( created__parse($value) ) == created__parse($value) )
	//		ie: This function is transitive.
	function created__parse($value){
		return $value;
	}
	
	// Prepares the value of a quickform_element to be inserted into the table.  
	// The output of this function should be acceptable as input for created__parse($value)
	function created__prepare($quickform_element){
		
		return $quickform_element->getValue();
	
	}
	
	// A function that takes a value in the specific format that is stored in the table
	// and formats it in such a way that it can be stored in the database.
	//
	// assertTrue( created__serialize($value) == created__serialize( created__parse( created__serialize($value) ) )
	//		ie: The output of this function should be acceptable to create__parse and vice versa
	function created__serialize($value){
		return $value;
	}
	
	// A function that takes a value as stored in the table and formats it as a string.
	// The output should be consistent as it is also used to compare fields for equality.
	function created__toString($value){
		return $value;
	}
	
	// Returns a permissions array indicating whether this user has view or update permissions
	// Presumeably this function
	function created__permissions($user, &$record){
		return array(
			'View' => true,
			'Update' => false);
		
	}
	*/
	
	
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
}


?>
