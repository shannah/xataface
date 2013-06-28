<?php
import('Dataface/dhtmlxGrid/activegrid.php');

/**
 * Updates a dhtmlXGrid row with the values submitted in the cells array.
 * This accepts the following post parameters:
 *
 * <dl>
 * <dt>-rowid</dt>
 *		<dd>The row array index of the row that is being updated.
 * <dt>-gridid</dt>
 *		<dd>The id of the grid that is being updated.  Grids store
 *		    themselves in session variables. </dd>
 * <dt>cells</dt>
 *		<dd>An array of values of the cells of the row.  These are indexed
 *			by number.</dd>
 * </dl>
 */
class dataface_actions_update_grid {

	function handle(&$params){
		if ( isset($_POST['--cleargrids']) and $_POST['--cleargrids'] == 1) Dataface_dhtmlXGrid_activegrid::clearGrids();
		$cells = @$_REQUEST['cells'];
		
		
		
		
		$gridid = @$_REQUEST['-gridid'];
		$rowid = @$_REQUEST['-rowid'];


		$grid = Dataface_dhtmlXGrid_activegrid::getGrid($gridid);
		if ( !is_object($grid) ){

			echo $this->error('Could not find grid with id "'.$gridid.'"');
			exit;
		}
		
		if ( !is_array($cells) ){
			echo $this->notice('No cells were submitted to be updated.');
			exit;
		}
		//$cells = array_map('trim',$cells);
		foreach (array_keys($cells) as $key){
			$cells[$key] = trim($cells[$key], "\x7f..\xff\x0..\x1f");
		}
		print_r($cells);
		
		$res = $grid->setRowValues($rowid, $cells);
		if ( PEAR::isError($res) ){
			echo $this->error($res->getMessage());
		}
		$grid->update();
		$grid2 = Dataface_dhtmlXGrid_activegrid::getGrid($gridid);
		echo $this->json(array('success'=>1));
		exit;
		
		
	}
	
	function notice($msg){
		return $this->json(array('notice'=>$msg, 'success'=>0));
	}
	
	function warning($msg){
		return $this->json(array('warning'=>$msg, 'success'=>0));
	}
	
	function error($msg){
		return $this->json(array('error'=>$msg, 'success'=>0));
	}
	
	function json($arr){
		if ( is_array($arr) ){
			$out = array();
			foreach ( $arr as $key=>$val){
				$out[] = "'".addslashes($key)."': {$this->json($val)}";
			}
			return "{".implode(', ', $out)."}";
		} else if ( is_int($arr) || is_float($arr) ){
			return $arr;
		} else if ( is_bool($arr) ){
			return ( $arr?'1':'0');
		} else {
			return "'".addslashes($arr)."'";
		}
	}

}

?>
