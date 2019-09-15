<?php
class tables_Registrations {
	function afterDelete(&$record){
		$response =& Dataface_Applications::getResponse();
		
		@$response['--msg'] .= 'Registration was deleted';
	}
	
	function beforeSave(&$record){
		if ( $record->val('Notes') == 'Bad one'){
			return PEAR::raiseError('This is a bad one', DATAFACE_E_NOTICE);
		} else if ( $record->val('Notes') == 'Really bad one'){
			return PEAR::raiseError('This is a really bad one', DATAFACE_E_ERROR);
		} else {
			return true;
		}
	}

}

?>
