<?php
require_once 'BaseTest.php';

require_once 'Dataface/dhtmlxGrid/grid.php';

class dhtmlxGridTest extends BaseTest {



	function dhtmlxGridTest($name = "dhtmlxGridTest"){
		$this->BaseTest($name);
	}
	
	function test_html(){
		
		$s =& Dataface_Table::loadTable('Profiles');
		
		$records = df_get_records_array('Profiles');
		
		
		
		$grid = new Dataface_dhtmlxGrid_grid($s->fields(), $records);
		echo $grid->toXML();
		
	
	}
	
	
	
	
	


}


?>
