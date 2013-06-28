<?php
class dataface_actions_rest_delete {
	
	const PERMISSION_DENIED = 401;
	const NOT_FOUND=404;
	const SERVER_ERROR=500;
	const BAD_REQUEST = 400;


	function handle($params){
		$app = Dataface_Application::getInstance();
		$query = $app->getQuery();
		$record_id = @$_POST['--record_id'];
		
		try {
			if ( !$record_id ){
				throw new Exception(
					df_translate('Bad Request', 'Bad Request.  Missing parameter.'),
					self::BAD_REQUEST
				);
			}
			$record = df_get_record_by_id($record_id);
			if ( PEAR::isError($record) ){
				error_log($record->getMessage());
				throw new Exception(
					df_translate('Bad Request', 'Bad Request - invalid ID.'),
					self::BAD_REQUEST
				);
			}
			if ( !$record ){
				throw new Exception(
					df_translate('No records matched request','No records matched the request'),
					self::NOT_FOUND
				);
			}
			if ( !$record->checkPermission('delete') ){
				throw new Exception(
					df_translate('scripts.GLOBAL.MESSAGE.PERMISSION_DENIED','Permission Denied'),
					self::PERMISSION_DENIED
				);
			}
			
			$res = $record->delete(false); // We've already done a security check...
			if ( PEAR::isError($res) ){
				error_log($res->getMessage());
				throw new Exception(
					df_translate('actions.rest_delete.messages.SERVER_ERROR', 'Failed to delete record due to a server error.  See error log for details.'), 
					self::SERVER_ERROR
				);
					
			}
			
			$this->out(array(
				'code'=>200,
				'message'=>df_translate('actions.rest_delete.messages.SUCCESS', 'Successfully deleted record.'),
				'record_id'=>$record->getId()
			));
			exit;
		} catch (Exception $ex){
			switch ($ex->getCode() ){
				case self::PERMISSION_DENIED:
				case self::NOT_FOUND:
				case self::SERVER_ERROR:
					$msg = $ex->getMessage();
					$code = $ex->getCode();
					break;
				default:
					$msg = df_translate('actions.rest_delete.messages.SUCCESS', 'Successfully deleted record.');
					$code = self::SERVER_ERROR;
					error_log($ex->getMessage());
					break;
			}
			$this->out(array(
				'code' => $code,
				'message' => $msg
			));
			exit;
		}
	}
	
	
	function out($params){
		header('Content-type: application/json; charset="'.Dataface_Application::getInstance()->_conf['oe'].'"');
		echo json_encode($params);
	}
}
