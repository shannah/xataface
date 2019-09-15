<?php

class tables_RegistrationProducts {

	function beforeSave(&$record){
		if ( $record->val('ProductID') == 3 and $record->val('RegistrationID') == 1 ){
			return PEAR::raiseError("We don't really like this combination of product and registration", DATAFACE_E_NOTICE);
		
		}
		return true;
	}
}


?>
