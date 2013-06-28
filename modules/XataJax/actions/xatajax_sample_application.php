<?php
class actions_xatajax_sample_application {
	function handle($params){
		$js = Dataface_JavascriptTool::getInstance();
		$js->import('tests/xatajax.ui.application.sample.js');
		$js->setMinify(false);
		$js->setUseCache(false);
		df_register_skin('xatajax', XATAJAX_PATH.DIRECTORY_SEPARATOR.'templates');
		df_display(array(), 'tests/xatajax_sample_application.html');
	}
}
