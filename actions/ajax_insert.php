<?php
/**
 * @brief An AJAX action that inserts a new record into the database.
 * 
 * @section ajax_save_postparams POST Parameters
 *
 * @param String --record_id The record ID of the record to save.
 * 
 * @par Other Parameters
 *
 * The values that you want to save should be passed directly as POST variables with the column names 
 * as the key and the value to save as the value.
 *
 * @returns JSON data structure with:
 *
 * @code
 * {
 *		code: <int>,   // The status code.  200 for success.
 * 		message: <string>,  // The status message
 *		recordId: <string>, // The record id of the record after save.  Only on success.
 * }
 * @endcode
 *
 */
class dataface_actions_ajax_insert  {

	function handle($params){
	
		
		$app = Dataface_Application::getInstance();
		$query = $app->getQuery();
		try {
		
			if ( !@$_POST['-table'] ){
				throw new Exception("No table was specified");
			}
			
			
			$vals = array();
			foreach ($query as $k=>$v){
				if ( $k and $k{0} != '-' ) $vals[$k] = $v;
			}
			
			$record = new Dataface_Record($_POST['-table'], array());
			
			$record->setValues($vals);
			if ( !$record->checkPermission('ajax_save') ){
				throw new Exception("Permission Denied", 502);
			}
			$res = $record->save(null, true);
			if ( PEAR::isError($res) ){
				error_log($res->getMessage(), $res->getCode());
				throw new Exception("Failed to save record due to a server error.  See log for details.");
			}
			
			$this->out(array(
				'code' => 200,
				'message' => 'Successfully inserted record.',
				'recordId' => $record->getId()
			));
		
		} catch (Exception $ex){
			$this->out(array(
				'code' => $ex->getCode(),
				'message' => $ex->getMessage()
			));
		
		}
		
	}
	
	
	function out($params){
		header('Content-type: application/json; charset="'.Dataface_Application::getInstance()->_conf['oe'].'"');
		$out = json_encode($params);
		header('Content-Length: '.strlen($out));
		header('Connection: close');
		echo $out;
		flush();
	}

}
