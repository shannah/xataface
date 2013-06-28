<?php
class dataface_actions_test_TableView {
	function handle($params){
		$app = Dataface_Application::getInstance();
		
		$jt = Dataface_JavascriptTool::getInstance();
		$jt->import('xataface/view/tests/test_TableView.js');
		df_display(array(), 'xataface/tests/test_TableView.html');
	}
}
