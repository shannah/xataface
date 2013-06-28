<?php
/**
 * This action is more for backward compatibility with Dataface 0.5.x than anything
 * else.  Dataface 0.5.x allowed developers to place php scripts in a directory
 * named 'pages' in their application folder to essentially serve as custom actions
 * but to a much more limited extent than the current actions framework.  This action
 * is triggered by "-action=-custom_<pagename>" where <pagename> is the name of the
 * php script in the "pages" directory omitting the '.php' extension.
 * For example.  If you had a script pages/home.php then you would access this page
 * with -action=custom_home .
 */
class dataface_actions_custom {
	function handle($params){
		if ( !isset($params['action']['page']) ){
			trigger_error(
				df_translate(
					'Page not specified',
					'No page specified at '.Dataface_Error::printStackTrace(),
					array('stack_trace'=>Dataface_Error::printStackTrace())
					)
				,
				E_USER_ERROR
				);
		} else {
			$page = $params['action']['page'];
		}
		$app =& Dataface_Application::getInstance();
		$pages = $app->getCustomPages();
		if (!isset( $pages[$page] ) ){
			trigger_error( 
				df_translate(
					'Custom page not found',
					"Request for custom page '$page' failed because page does not exist in pages directory.". Dataface_Error::printStackTrace(),
					array('page'=>$page, 'stack_trace'=>Dataface_Error::printStackTrace())
					), 
					E_USER_ERROR
				);
		}
		ob_start();
		include $pages[$page];
		$out = ob_get_contents();
		ob_end_clean();
		df_display(array('content'=>$out), 'Dataface_Custom_Template.html');
		return true;	
	}

}

?>
