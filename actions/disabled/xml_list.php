<?php
class dataface_actions_xml_list {

	function handle(&$params){
	
		import('Dataface/XMLTool.php');
		$xml = new Dataface_XMLTool();
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$table =& Dataface_Table::loadTable($query['-table']);
		echo $xml->header();
		$auth =& Dataface_AuthenticationTool::getInstance();
		echo "<![CDATA[";
		print_r($_SESSION);
		echo "]]>";
		echo "<user>".$auth->getLoggedInUsername()."</user>";
		echo $xml->toXML($table);
		//echo $xml->toXML($app->getRecord());
		echo $xml->toXML($app->getResultSet());
		echo $xml->footer();
		exit;
	}
}

?>
