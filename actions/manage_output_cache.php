<?php
class dataface_actions_manage_output_cache {
	function handle(&$params){
		
		$app =& Dataface_Application::getInstance();
		$context=array();
		if ( !is_array(@$app->_conf['_output_cache']) or !(@$app->_conf['_output_cache']['enabled']) ){
			
			$context['enabled'] = false; //return PEAR::raiseError('The output cache is currently disabled.  You can enable it by adding an [_output_cache] section to your conf.ini file with a value \'enabled=1\'');

		} else {
			$context['enabled'] = true;
		}
		
		
		
		if ( @$_POST['--clear-cache'] ){
			// We should clear the cache
			@xf_db_query("delete from `__output_cache`", df_db());
			$app->redirect($app->url('').'&--msg='.urlencode('The output cache has been successfully cleared.'));
		}
		
		$res = xf_db_query("select count(*) from `__output_cache`", df_db());
		if ( !$res ) throw new Exception(xf_db_error(df_db()), E_USER_ERROR);
		list($numrows) = xf_db_fetch_row($res);
		$context['numrows'] = $numrows;
		
		df_display($context, 'manage_output_cache.html');
		
		
		
		
	}
}
