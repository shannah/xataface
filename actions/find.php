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
 * File dataface/actions/new.php
 * Author: Steve Hannah <shannah@sfu.ca>
 * Created April 5, 2006
 *
 * Description:
 * 	A controller class to handle the 'new' action.  The 'new' action is the action that
 *  allows the user to create a new record in the database.
 */
class dataface_actions_find {
	function handle($params){
		import( 'Dataface/SearchForm.php');
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		$new = true;
		
		
		$form = new Dataface_SearchForm($query['-table'], $app->db(),  $query);
		$res = $form->_build();
		if ( PEAR::isError($res) ){
			trigger_error($res->toString().Dataface_Error::printStackTrace(), E_USER_ERROR);
		
		}
				
		/*
		 *
		 * We need to add the current GET parameter flags (the GET vars starting with '-') so
		 * that the controller knows to pass control to this method again upon form submission.
		 *
		 */
		

		$form->setDefaults( array( '-action'=>$query['-action']) );
		if ( $form->validate() ){
			$res = $form->process( array(&$form, 'performFind'));
		}
		$jt = Dataface_JavascriptTool::getInstance();
		$jt->import('find.js');
		
		
		
		
		ob_start();
		$form->display();
		$out = ob_get_contents();
		ob_end_clean();
		
		
		
		$context = array('form'=>&$out);
		df_display($context, 'Dataface_Find_View.html', true);
	}
}

?>
