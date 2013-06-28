<?php
class dataface_actions_test_MasterDetailView {
	function handle($params){
		$app = Dataface_Application::getInstance();
		
		$jt = Dataface_JavascriptTool::getInstance();
		$jt->import('xataface/view/tests/test_MasterDetailView.js');
		df_display(array(), 'xataface/tests/test_MasterDetailView.html');
	}
}
