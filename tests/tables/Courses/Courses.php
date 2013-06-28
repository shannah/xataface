<?php
class tables_Courses {

	function __import__xml($data){
		require_once 'Dataface/ImportFilter/xml.php';
		$xmlfilter = new Dataface_ImportFilter_xml();
		$importRecords = $xmlfilter->import($data);
		return $importRecords;
	}
}


?>
