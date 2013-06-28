<?php
/**
 * An ajax action to delete one or more records.
 *
 * REST Interface:
 *
 * Records are selected using standard Xataface URL conventions.  It also accepts
 * the --selected-ids POST parameter which is a list of record ids separated by new lines.
 *
 * Returns:
 * JSON data structure with the following keys:
 *
 * code	- The response code.  See response codes below.
 * message - The response or error message.
 * deletedIds - An array of record ids that were deleted.
 * numDeleted - The number of records that were deleted.
 *
 * @created January 29, 2010 by Steve Hannah <steve@weblite.ca>
 */
define('XATAJAX_DELETE_RESPONSE_CODE_SUCCESS', 200);
define('XATAJAX_DELETE_RESPONSE_CODE_SOME_FAILURES', 201);
define('XATAJAX_DELETE_RESPONSE_CODE_FAIL', 400);
define('XATAJAX_DELETE_RESPONSE_CODE_NO_RECORDS_FOUND',404);
define('XATAJAX_DELETE_RESPONSE_CODE_SERVER_ERROR', 500);

class actions_xatajax_delete {
	private $errors=null;
	private $updated=0;
	private $deletedIds=null;
	function handle(&$params){
		try {
			if ( !$_POST ) return PEAR::raiseError("This method is only available via POST");
			$app =& Dataface_Application::getInstance();
			$query =& $app->getQuery();
			
			$table = Dataface_Table::loadTable($query['-table']);
			$q = array(
				'-table'=>$query['-table']
			);
			foreach (array_keys($table->keys()) as $key){
				$q[$key] = '=';
				
				
				if ( @$query[$key] ){
					if ( strpos($query[$key],'=')===0){
						$q[$key] = $query[$key];
					} else {
						$q[$key] .= $query[$key];
					}
				} else {
					if ( !isset($query['--selected-ids']) ){
						throw new Exception("Delete action must include either all primary keys in the query, or must specify the --selected-ids parameter.  This request specified neither");
						
					}
				}
				
			}
			
			
			
			$records = df_get_selected_records($query);
			$rec =  df_get_record($q['-table'], $q);
			if ( $rec ) $records[] = $rec;
			if ( !$records ){
				throw new Exception("No matching records found", XATAJAX_DELETE_RESPONSE_CODE_NO_RECORDS_FOUND);
				
			}
			//print_r(array_keys($records));exit;
			$updated = 0;
			$errs = array();
			$deletedIds = array();
			$failedIds = array();
			foreach ($records as $rec){
				if ( !$rec->checkPermission('delete') ){
					$errs[] = sprintf(
						"You do not have permission to delete '%s' because you do not have the 'delete' permission.",
						$rec->getTitle()
					);
					continue;
				}
				$res = $rec->delete(true /*secure*/);
				if ( PEAR::isError($res) ){
					$errs[] = array('message'=>$res->getMessage(), 'code'=>$res->getCode(), 'record_id'=>$rec->getId());
					
				}
				else{
					$updated++;
					$deletedIds[] = $rec->getId();
				}
				
			}
			
			if ( $errs ){
				$this->errors = $errs;
				$this->updated = $updated;
				$this->deletedIds = $deletedIds;
				$code = XATAJAX_DELETE_RESPONSE_CODE_SOME_FAILURES;
				if ( !$updated ) $code = XATAJAX_DELETE_RESPONSE_CODE_FAIL;
				
				throw new Exception(sprintf(
					'%d records were successfully updated.  %d errors occurred.',
					$updated,
					count($errs)
				), $code);
			}
			
			
			
			xj_json_response(array(
				'code'=>200,
				'message'=>sprintf('%d records successfully deleted', $updated),
				'deletedIds'=>$deletedIds,
				'numDeleted'=>$updated,
				'errors'=>array()
			));
			exit;
		} catch (Exception $ex){
			xj_json_response(array(
				'code'=>$ex->getCode(),
				'message'>$ex->getMessage(),
				'deletedIds'=>$this->deletedIds,
				'numDeleted'=>$this->updated,
				'errors'=>$this->errors
			));
				
		}
	}
}
