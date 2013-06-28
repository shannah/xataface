<?php
class dataface_actions_load_grid {
	function handle(&$params){
		import('Dataface/dhtmlxGrid/activegrid.php');
		$app =& Dataface_Application::getInstance();
		$grid =& Dataface_dhtmlxGrid_activegrid::getGrid($_REQUEST['-gridid']);
		if ( stristr($_SERVER["HTTP_ACCEPT"],"application/xhtml+xml") ) {
			header("Content-type: application/xhtml+xml"); } else {
			header("Content-type: text/xml");
		}
		echo("<?xml version=\"1.0\" encoding=\"{$app->_conf['oe']}\"?>\n"); 
		echo $grid->toXML();
		exit;
	}
}

?>
