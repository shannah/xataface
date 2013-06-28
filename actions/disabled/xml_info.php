<?php
class dataface_actions_xml_info {

	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		import('Dataface/XMLTool.php');
		$xt = new Dataface_XMLTool();
		echo $xt->header();
		echo $xt->getInfo();
		echo $xt->footer();
		exit;
	}
}
?>
