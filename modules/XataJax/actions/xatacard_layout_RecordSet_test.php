<?php
class actions_xatacard_layout_RecordSet_test {
	function handle($params){
		$js = Dataface_JavascriptTool::getInstance();
		$js->import('xatacard/layout/tests/RecordSetTest.js');
		$js->setMinify(false);
		$js->setUseCache(false);
		df_register_skin('xatajax', XATAJAX_PATH.DIRECTORY_SEPARATOR.'templates');
		try {
			df_display(array(), 'tests/xatacard/layout/RecordSet/RecordSetTest.html');
		} catch (Exception $ex){
			//echo "here";exit;
			while ($ex){
				echo '<h3>'.$ex->getMessage().'</h3>';
				echo nl2br(df_escape($ex->getTraceAsString()));
				$ex = $ex->getPrevious();
			}
		
		}
	}
}
