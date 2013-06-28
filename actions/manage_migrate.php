<?php
/**
 * This action manages the migration of data from older versions to newer versions.
 */
 
import('Dataface/TranslationTool.php');
import('Dataface/ModuleTool.php');

class dataface_actions_manage_migrate {


	function handle(&$params){
	
		$app =& Dataface_Application::getInstance();
		if ( !is_array(@$app->_conf['_modules']) ) $app->_conf['_modules'] = array();
		
		if ( !isset($app->_conf['_modules']['Dataface_TranslationTool']) ){
			$app->_conf['_modules']['Dataface_TranslationTool'] = 'Dataface/TranslationTool.php';
		}
		$context = array();
		$mt =& Dataface_ModuleTool::getInstance();
		if ( count($_POST) > 0 ){
			
			$modules = $_POST['modules'];
			
			$log = $mt->migrate(array_keys($modules));
			$context['log'] = $log;
			

		} else {
		
			
			
			$context['migrations'] = $mt->getMigrations();
			
			
			
			ob_start();
			//$form->display();
			$context['form'] = ob_get_contents();
			ob_end_clean();
		
		}
		
		df_display($context, 'actions_manage_migrate.html');
		
		
	}
}


?>
