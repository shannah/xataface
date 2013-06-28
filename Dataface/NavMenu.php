<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp (shannah@sfu.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */
require_once 'Dataface/Globals.php';
//require_once 'Smarty/Smarty.class.php';
require_once 'Dataface/SkinTool.php';

class Dataface_NavMenu {
	
	
	var $_tables;
	//var $_smarty;
	var $_skinTool;
	var $_current;
	function Dataface_NavMenu( $tables, $current='' ){
		$this->_tables = $tables;
		if ( $current ){
			$this->_current = $current ;
		} else {
			foreach ( $tables as $table ){
				$this->_current = $table;
				break;
			}
		}
		
		//$this->_smarty = new Smarty;
		//$this->_smarty->template_dir = $GLOBALS['Dataface_Globals_Templates'];
		//$this->_smarty->compile_dir = $GLOBALS['Dataface_Globals_Templates_c'];
		$this->_skinTool =& Dataface_SkinTool::getInstance();
	}
	
	
	function toHtml(){
	
		$context = array( 'tables'=>$this->_tables, 'current'=>$this->_current);
		//$this->_smarty->assign($context);
		//$this->_smarty->display('Dataface_NavMenu.html');
		$this->_skinTool->display($context, 'Dataface_NavMenu.html');
	
	}
	
}
