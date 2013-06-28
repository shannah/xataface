<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Copyright (c) 2003-2004 John Griffin                                 |
// +----------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or        |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This library is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// |                                                                      |
// | You should have received a copy of the GNU Lesser General Public     |
// | License along with this library; if not, write to the Free Software  |
// | Foundation, Inc., 59 Temple Place, Suite 330,Boston,MA 02111-1307 USA|
// +----------------------------------------------------------------------+
// | Authors: Steve Hannah <shannah@sfu.ca>                     |
// +----------------------------------------------------------------------+
//
//
require_once 'SQL/Compiler.php';

/**
 * An SQL Compiler to generate MySQL compliant SQL.
 */
class SQL_Compiler_mysql extends SQL_Compiler {

	function SQL_Compiler_mysql( $array = null ){
		$this->SQL_Compiler($array);
		$this->type = 'mysql';
	
	}
	
	/**
	 * Wraps identifier in back-ticks.
	 */
	function compileIdent($value){
		if ( strpos($value, '.') !== false ){
			return implode('.',array_map(array(&$this, 'compileIdent'), explode('.', $value)));
		} 
		switch ($value) {
			case '*' :
				return $value;
			default:
				return '`'.$value.'`';
		}
	}
	
	


}?>
