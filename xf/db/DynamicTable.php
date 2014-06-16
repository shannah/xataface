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
 
namespace xf\db;

/**
 * A dynamic table that will regenerate itself automatically if any of the tables that
 * it depends on is modified.  You can use this in cases where a View doesn't offer
 * enough performance.
 */
class DynamicTable {

	/**
	 * List of table names that this dynamic table depends upon.
	 */
	private $dependencies = array();
	
	/**
	 * The name of this table.
	 */
	private $tableName;
	
	/**
	 * Either a single string with the SQL create table statement, or an array of 
	 * SQL strings that will be executed each time the table needs to be regenerated.
	 */
	private $sql;
	
	/**
	 * Creates the new dynamic table object.  This doesn't create the table itself.
	 *
	 * @param string $tableName The name of this table.
	 * @param mixed $sql Either a single string with the SQL create table statement, or an array of 
	 * SQL strings that will be executed each time the table needs to be regenerated.
	 * @param array $dependencies  List of table names that this dynamic table depends upon.
	 */
	public function __construct($tableName, $sql, array $dependencies){
		$this->tableName = $tableName;
		$this->dependencies = $dependencies;
		$this->sql = $sql;
	}
	
	/**
	 * Checks to see if any of the dependent tables have been modified since this table
	 * was last modified.  If so, it will drop the table and regenerate it.
	 */
	public function update(){
		$mod_times = \Dataface_Table::getTableModificationTimes();
		if ( !isset($mod_times[$this->tableName]) ){
			$me = 0;
		} else {
			$me = $mod_times[$this->tableName];
		}
		
		$outOfDate = false;
		foreach ( $this->dependencies as $dep ){
			if ( @$mod_times[$dep] > $me ){
				$outOfDate = true;
				break;
			}
		}
		
		if ( $outOfDate ){
			\df_q("DROP TABLE IF EXISTS `".str_replace('`','', $this->tableName)."`");
			\df_q($this->sql);
			import('Dataface/IO.php');
			\Dataface_IO::touchTable($this->tableName);
		}
		
		
	}
}