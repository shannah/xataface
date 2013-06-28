<?php
import('Dataface/JSON.php');
import('Dataface/ValuelistTool.php');

class dataface_actions_ajax_valuelist_append {

	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		
		if ( !@$_POST['-valuelist'] ){
			echo JSON::error("No valuelist specified.");
			exit;
		}
		
		$valuelist = $_POST['-valuelist'];
		$query =& $app->getQuery();
		
		$table =& Dataface_Table::loadTable($query['-table']);
		
		
		if ( !@$_POST['-value'] ){
			echo JSON::error("No value was provided to be appended to the valuelist.");
			exit;
		}
		
		$value = $_POST['-value'];
		if ( @$_POST['-key'] ){
			$key = $_POST['-key'];
		} else {
			$key = null;
		}	
		
		$vt =& Dataface_ValuelistTool::getInstance();
		$res = $vt->addValueToValuelist($table, $valuelist, $value, $key, true);
		if ( PEAR::isError($res) ){
			echo JSON::error($res->getMessage());
			exit;
		}
		echo JSON::json(array(
			'success'=>1,
			'value'=>array('key'=>$res['key'], 'value'=>$res['value'])
			)
		);
		exit;
		
		
	}

}

?>
