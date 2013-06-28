<?php
import('Dataface/PermissionsTool.php');
class dataface_actions_reorder_related_records {
	function handle(&$params){
		
		if ( !isset( $_POST['-redirect'] ) and !isset($_POST['relatedList-body']) ){
			return PEAR::raiseError('Cannot reorder related records because no redirect url was specified in the POST parameters.'.Dataface_Error::printStackTrace());
		}
		
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		if ( !($record = df_get_selected_records($query)) ){
			$record =& $app->getRecord();
		} else {
			$record = $record[0];
		}
		if ( PEAR::isError($record) ) return $record;
		if ( !$record ){
			return PEAR::raiseError('The specified record could not be found.');
		}
		
		
		if ( !@$query['-relationship'] ){
			return PEAR::raiseError("No relationship specified.");
		}
		
		$relationship =& $record->_table->getRelationship($query['-relationship']);
		if ( PEAR::isError($relationship) ) return $relationship;
		
		$orderColumn = $relationship->getOrderColumn();
		if ( !$orderColumn ){
			return PEAR::raiseError('Could not reorder records of this relationship because it does not have any order column specified.');
		}
		
		
		
		
		if ( !Dataface_PermissionsTool::checkPermission('reorder_related_records', $record, array('relationship'=>$query['-relationship']) ) ) {
			return Dataface_Error::permissionDenied('You do not have permission to reorder the records in this relationship.');
		}
		
		if ( isset($_POST['relatedList-body']) ){
			$relatedIds = array_map('urldecode', $_POST['relatedList-body']);
			// In this case we are not just moving a record up or down the list,
			// we may be reordering the list altogether.
			// We may also just be ordering a subset of the list.
			// so we will want to be reordering the given set of records
			// with respect to each other.
			
			// First let's see if the ordering has been initialized yet.
			$records = array();
			//print_r($relatedIds);exit;
			foreach ($relatedIds as $recid ){
				//$recid = urldecode($recid);
				$records[] = df_get_record_by_id($recid);
			}
			$start = ( isset($query['-related:start']) ? $query['-related:start'] : 0);
			$record->sortRelationship($query['-relationship'], $start, $records);
		
			echo 'Sorted Successfully';
			exit;
		}
		
		if ( !isset( $_POST['-reorder:direction'] ) ){
			return PEAR::raiseError('Cannot reorder related records because no direction was specified.');
		}
		
		if ( !isset( $_POST['-reorder:index']) ){
			return PEAR::raiseError('Cannot reorder related records because no index was specified.');
		}
		
		$index = intval($_POST['-reorder:index']);
		
		switch ( $_POST['-reorder:direction']){
			case 'up': 
				//echo "Moving up";exit;
				$res = $record->moveUp($query['-relationship'], $index);
				
				break;
				
			case 'down':
				$res = $record->moveDown($query['-relationship'], $index);
				break;
				
			default:
				return PEAR::raiseError('Invalid input for direction of reordering.  Must be up or down but received "'.$_POST['-reorder:direction'].'"');
		}
		if ( PEAR::isError($res) ) return $res;
		header('Location: '.$_POST['-redirect']);
		exit;
		
	
	}
}
?>
