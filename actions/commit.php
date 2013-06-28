<?php
class dataface_actions_commit {
	function handle($params){
		$app = Dataface_Application::getInstance();
		if ( !$_POST ){
			throw new Exception("Commit requires post");
		}
		$data = @$_POST['--data'];
		if ( !$data ){
			throw new Exception("No data provided");
		}
		
		$data = json_decode($data, true);
		
		$updates = array();
		$inserts = array();
		$deletes = array();
		
		if ( isset($data['inserts']) ){
			$inserts =& $data['inserts'];
		}
		
		if ( isset($data['updates']) ){
			$updates =& $data['updates'];
		}
		
		if ( isset($data['deletes']) ){
			$deletes =& $data['deletes'];
		}
		
		$numFailures = 0;
		$numSuccesses = 0;
		
		$deleteResponses = array();
		
		// Let's do the deletes first
		foreach ( $deletes as $deleteInfo ){
			$response = array();
			$deleteResponses[] =& $response;
			$record = df_get_record_by_id($deleteInfo['id']);
			if ( !$record ){
				$response['message'] = 'Record '.$deleteInfo['id'].' could not be found.';
				$response['code'] = 404;
				$numFailures++;
			} else {
				$res = $response->delete(true);
				if ( PEAR::isError($res) ){
					$response['message'] = $res->getMessage();
					$response['code'] = $res->getCode();
					$numFailures++;
				} else {
					$response['message'] = 'Deleted record '.$deleteInfo['id'].'.';
					$response['code'] = 200;
					$response['recordId'] = $deleteInfo['id'];
					$numSuccesses++;
				}
			}
		}
		
		$insertResponses = array();
		foreach ($inserts as $insertInfo){
			$response = array();
			$insertResponses[] =& $response;
			$record = new Dataface_Record($insertInfo['table'], array());
			$record->setValues($insertInfo['data']);
			$res = $record->save(null, true);
			if ( PEAR::isError($res) ){
				$response['message'] = $res->getMessage();
				$response['code'] = $res->getCode();
				$numFailures++;
			} else {
				$response['message'] = 'Inserted record';
				$response['code'] = $res->getCode();
				$response['recordId'] = $record->getId();
				$response['version'] = $record->getVersion();
				$numSuccesses++;
			}
		}
		
		$updateResponses = array();
		foreach ($updates as $updateInfo ){
			$response = array();
			$insertResponses[] =& $response;
			$record = df_get_record_by_id($updateInfo['id']);
			if ( !$record ){
				$response['message'] = 'Record '.$updateInfo['id'].' could not be found.';
				$response['code'] = 404;
				$numFailures++;
			} else {
				$record->setValues($updateInfo['data']);
				$res = $record->save(null, true);
				if ( PEAR::isError($res) ){
					$response['message'] = $res->getMessage(),
					$response['code'] = $res->getCode();
					$numFailures++;
				} else {
					$response['message'] = 'Updated record';
					$response['code'] = 200;
					$response['recordId'] = $record->getId();
					$response['version'] = $record->getVersion();
					$numSuccesses++;
				}
				
			}
		}
		
		
		header('Content-type: text/json; charset="'.$app->_conf['oe'].'"');
		
		$out = array(
			'code' => ($numFailures == 0 and $numSuccesses > 0) ? 200 : 
				($numSuccesses > 0) ? 201 : 202,
			'message' => $numSuccesses . ' successes. '. $numFailures.' failures.',
			'numSuccesses' => $numSuccesses,
			'numFailures' => $numFailures,
			'responses' => array(
				'updates' => $updateResponses,
				'inserts' => $insertResponses,
				'deletes' => $deleteResponses
			)
		);
		echo json_encode($out);
		
		
		
		
	}
}
