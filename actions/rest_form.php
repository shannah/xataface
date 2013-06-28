<?php
class dataface_actions_rest_form {
	function handle($params){
		session_write_close();
		$app = Dataface_Application::getInstance();
		$query = $app->getQuery();
		
		try {
			
			if ( @$query['--id'] ){
				// This is a form for a particular record
				
				if ( @$query['-relationship'] ){
					// This is a related record form
					
				
				} else {
					// This is an edit form for a particular record
					
					$rec = df_get_record_by_id($query['--id']);
					if ( !$rec ){
						throw new Exception("Record could not be found");
					}
					if ( PEAR::isError($rec) ) throw new Exception($rec->getMessage());
					
					
					if ( !$rec->checkPermission('edit') ){
						throw new Exception("Failed to get edit form for record.  Permission denied");
					}
					
					$tableObj = $rec->_table;
					
					$fields = null;
					if ( @$query['--fields'] ){
						$fields = explode(',', $query['--fields']);
						
					} else {
						
						$temp = $tableObj->fields(false, false, true);
						$fields = array_keys($temp);
					
					}
					
					$form = array();
					
					
				
				
				}
				
				
			} else if ( @$query['-table'] ){
				// This is a new record form for a particular table
				$table = $query['-table'];
				$tableObj = Dataface_Table::loadTable($table);
				
				$tablePerms = $tableObj->getPermissions();
				
				if ( !@$tablePerms['new'] ){
					throw new Exception("Failed to build form data because you do not have permission to create new records on this table.");
					
				}
				
				
				
				
				$fields = null;
				if ( @$query['--fields'] ){
					$fields = explode(',', $query['--fields']);
					
				} else {
					$temp = $tableObj->fields(false, false, true);
					$fields = array_keys($temp);
				
				}
				
				$form = array();
				$defaults = array();
				$valuelists = array();
				
				if ( !$fields ){
					throw new Exception("No fields were specified for the form.");
				}
				
				foreach ($fields as $f){
				
					$perms = $tableObj->getPermissions(array('field'=>$f));
					if ( !@$perms['new']){
						// No permission to create 'new' data on this field.
						continue;
					}
					
					$data = $tableObj->getField($f);
					
					$form[$f] = array(
						'widget'=>$data['widget']
					);
					
					$defaults[$f] = $tableObj->getDefaultValue($f);
					
					if ( @$data['vocabulary'] ){
						$form[$f]['vocabulary'] = $data['vocabulary'];
						if ( !isset($valuelists[$data['vocabulary']]) ){
							$valuelists[$data['vocabulary']] = $tableObj->getValuelist($data['vocabulary']);
							
						}
					}
					
					if ( @$data['validators'] ){
						$form[$f]['validators'] = $data['validators'];
					}
					
				}
				
				$this->out(array(
					'code'=>200,
					'message'=>'Form successfully created',
					'form'=>$form,
					'defaults'=>$defaults,
					'valuelists'=>$valuelists
					
				));
				exit;
				
				
			
			} else {
			
				throw new Exception("Invalid parameters for rest_form");
			}
		
		} catch (Exception $ex){
		
			$this->out(array(
				'code' => $ex->getCode(),
				'message' => $ex->getMessage()
			));
			exit;
		}
		
	}
	
	function out($params){
		header('Content-type: application/json; charset="'.Dataface_Application::getInstance()->_conf['oe'].'"');
		echo json_encode($params);
	}
}
