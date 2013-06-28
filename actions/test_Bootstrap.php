<?php
class dataface_actions_test_Bootstrap {
	function handle($params){
		Dataface_JavascriptTool::getInstance()->import('xataface/bootstrap.js');
		df_display(array(), 'xataface/tests/test_Bootstrap.html');
	}
}
