<?php
session_start();

require_once 'BaseTest.php';
require_once 'Dataface/dhtmlxGrid/activegrid.php';

$test = new BaseTest();
$test->setUp();

$s =& Dataface_Table::loadTable('Profiles');
	
$records = df_get_records_array('Profiles');


if ( @$_REQUEST['-action'] =='update_grid'){
	$app =& Dataface_Application::getInstance();
	$app->display();
	exit;
}


$grid = new Dataface_dhtmlxGrid_activegrid(array('-table'=>'Profiles'));
if ( $_GET['--dhtmlxGrid_xml'] ){
	if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
  		header("Content-type: application/xhtml+xml"); } else {
  		header("Content-type: text/xml");
	}
	echo("<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n"); 
	
	echo $grid->toXML();
	
} else {
	echo "<html><body>";
	echo $grid->toHTML();
	
	echo <<<END
	<div id="output_div"></div>
	<script>
	function serializeGrid(){
	var output_div = document.getElementById('output_div');
	//for (var j in {$grid->name}){
	//	output_div.innerHTML += j+"<br>";
	//}
	//alert({$grid->name});
	var xmlstr = {$grid->name}.serialize();
	
	output_div.innerHTML = xmlstr;
	}
	</script>
	<input type="button" onclick="serializeGrid()" value="Serialize"/>
END;
	echo "</body></html>";

}



?>
