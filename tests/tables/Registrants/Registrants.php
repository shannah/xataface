<?php

class tables_Registrants {

	function beforeDelete(&$record){
	
		if ( $record->val('RegistrantName') == 'Larry' ){
		
			// we'll throw a notice only
			return PEAR::raiseError("Larry didn't like being removed, so we didn't remove him", DATAFACE_E_NOTICE);
		} else if ( $record->val('RegistrantName') == 'Curly'){
			// Let's throw a real error for this one
			return PEAR::raiseError("Something happened when we tried to remove Curly.. errors galore!!", DATAFACE_E_ERROR);
		
		} else {
			$response =& Dataface_Application::getResponse();
			$response['--msg'] = @$response['--msg'] ."\n"."We are happy to remove ".$record->val('RegistrantName');
			return true;
		}
	
	}
	
	
	function beforeInsert(&$record){
	
		if ( $record->val('RegistrantName') == 'Steve1' ){
			return PEAR::raiseError("Steve doesn't like to be inserted, so we won't insert him.", DATAFACE_E_NOTICE);
		} else if ( $record->val('RegistrantName') == 'Steve2' ){
			return PEAR::raiseError("Crash, bang, boom!!!  Major errors occurred inserting Steve2", DATAFACE_E_ERROR);
		} else {
			$response =& Dataface_Application::getResponse();
			$response['--msg'] = @$response['--msg']."\nAll systems are GO!";
			return true;
		}
	}
	
	
	function beforeUpdate(&$record){
	
		if ( $record->val('RegistrantName') == 'Larry2') {
			return PEAR::raiseError("Larry2 doesn't like to be updated, so we won't update him.", DATAFACE_E_NOTICE);
		} else if ( $record->val('RegistrantName') == 'Larry3') {
			return PEAR::raiseError("Larry3 cannot be added.  Major errors!!!", DATAFACE_E_ERROR);
		} else {
			$response =& Dataface_Application::getResponse();
			$response['--msg'] = @$response['--msg']."\nAll systems are good for update.";
			return true;
		}
	}
	
}


?>
