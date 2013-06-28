<?php
class dataface_actions_test_View {
	function handle($params){
		$app = Dataface_Application::getInstance();
		
		$jt = Dataface_JavascriptTool::getInstance();
		$jt->import('xataface/view/tests/test_View.js');
		df_display(array(), 'xataface/tests/test_View.html');
	}
}
