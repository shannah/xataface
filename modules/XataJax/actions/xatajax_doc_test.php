<?php
class actions_xatajax_doc_test {
	function handle($params){
		$js = Dataface_JavascriptTool::getInstance();
		$js->import('tests/doctest.js');
		$js->setMinify(false);
		$js->setUseCache(false);
		df_register_skin('xatajax', XATAJAX_PATH.DIRECTORY_SEPARATOR.'templates');
		df_display(array(), 'tests/doctest.html');
	}
}
