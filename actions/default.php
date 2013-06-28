<?php
/********************************************************************************
 *
 *  Xataface Web Application Framework for PHP and MySQL
 *  Copyright (C) 2006  Steve Hannah <shannah@sfu.ca>
 *  
 *  This library is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU Lesser General Public
 *  License as published by the Free Software Foundation; either
 *  version 2.1 of the License, or (at your option) any later version.
 *  
 *  This library is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *  Lesser General Public License for more details.
 *  
 *  You should have received a copy of the GNU Lesser General Public
 *  License along with this library; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *===============================================================================
 */
/**
 * File dataface/actions/default.php
 * Author: Steve Hannah <shannah@sfu.ca>
 * Created April 5, 2006
 *
 * Description:
 * 	A controller class to handle the default action.  The default action is used 
 * when no controller can be found for the current action.
 */
class dataface_actions_default {
	function handle(&$params){
		import('dataface-public-api.php');
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$action =& $params['action'];
		if ( isset( $action['mode'] ) ){
			$query['-mode'] = $action['mode'];
		}
		
		$context =array();
		if ( @$query['-template'] ){
			$template = $query['-template'];
		} else if ( @$action['template'] ){
			$template = $action['template'];
		} else {
			trigger_error("No template found for action '".@$action['name']."'.".Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
		$context = array();
		df_display($context, $template);
		
		
	}

}

?>
