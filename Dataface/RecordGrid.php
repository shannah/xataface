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

/******************************************************************************************************************
 * File: 	Dataface/RecordGrid.php
 * Author:	Steve Hannah <shannah@sfu.ca>
 * Created: December 4, 2005
 *
 * Description:
 * -------------
 * Displays a database result or array of records as HTML.
 *
 * Usage:
 * ------
 * <code>
 * // Simple scenario.  Load an array of Dataface_Record objects and display them
 * // in a grid.  This scenario displays all fields of each record.
 * $records =& getRecords(); // get an array of DatafaceRecord objects.
 * $grid = new Dataface_RecordGrid($records);
 * echo $grid->toHTML();
 *
 * // More complex scenario: Same as simple scenario, except we only display certain
 * // fields of the records.
 * $records =& getRecords();
 * $grid = new Dataface_RecordGrid($records, array('id','fname','lname'));
 * echo $grid->toHTML();	// displays 3 column table.
 *
 * // Using grid with normal associative arrays
 * $res = mysql_query("SELECT * FROM Students");
 * $records = array();
 * while ( $row = mysql_fetch_array($res) ){
 * 		$record = array();
 * 		foreach ( $row as $key=>$value){
 *			if ( is_int($key) ) continue;
 *				// discard all of the numeric keys
 *			$record[$key] = $value;
 *		}
 *		$records[] = $record;
 *	}
 *	$grid = new Dataface_RecordGrid($records);
 *	echo $grid->toHTML();
 * </code>
 ********************************************************************************************************************/
require_once 'Dataface/Record.php';
define('RecordGrid_ActionLabel', '____actions____');
 
class Dataface_RecordGrid {
	var $records;
	var $columns;
	var $labels;
	var $id="sortable";
	var $cssclass = "";
	var $actionCellCallbacks = array();
	var $cellFilters = array();
	
	function Dataface_RecordGrid(&$records, $columns=null, $labels=null){
		$this->records =& $records;
		if ( !is_array($this->records) ){
			throw new Exception('In Dataface_RecordGrid the first parameter is expected to be an array but received "'.get_class($records).'"', E_USER_ERROR);
		}
		
		$this->columns = $columns;
		$this->labels = $labels;
	}
	
	function addActionCellCallback($callback){
		$this->actionCellCallbacks[] = $callback;
	}
	
	function toHTML(){
		import('Dataface/SkinTool.php');
		$recKeys = array_keys($this->records);
		$sampleRecord =& $this->records[$recKeys[0]];
		if ( $this->columns === null ){
			$columns = array();
			if ( is_a($sampleRecord, 'Dataface_Record') ){
				$columns = array_keys($sampleRecord->_table->fields(false,true));
			} else if ( is_a($sampleRecord, 'Dataface_RelatedRecord') ){
				$columns = $sampleRecord->_relationship->_schema['short_columns'];
			} else {
				$columns = array_keys($sampleRecord);
			}
		} else {
			$columns =& $this->columns;
		}
		if ( count($this->actionCellCallbacks) > 0 ){
			$hasCallbacks = true;
			array_unshift($columns, RecordGrid_ActionLabel);
		} else {
			$hasCallbacks = false;
		}
		//print_r($columns);
		
		$gridContent = array();
		foreach ($this->records as $record){
			if ( $hasCallbacks ) $row[RecordGrid_ActionLabel] = '';
			if ( is_a($record, 'Dataface_Record') or is_a($record, 'Dataface_RelatedRecord') ){
				$row = array();
				foreach ( $columns as $column){
					if ( $column == RecordGrid_ActionLabel ) continue;
					$row[$column] = $record->printValue($column);
					if ( isset($this->cellFilters[$column]) ){
						$row[$column] = call_user_func($this->cellFilters[$column], $record, $column, $row[$column]);
						
					}
				}
				if ( $hasCallbacks ){
					$cbout = array();
					foreach ( $this->actionCellCallbacks as $cb ){
						$cbout[] = call_user_func($cb, $row);
					}
					$row[RecordGrid_ActionLabel] =implode('', $cbout);
				}
				$gridContent[] =& $row;
				unset($row);
			} else if ( is_array($record) ){
				$row = array();
				foreach ( $columns as $column){
					if ( $column == RecordGrid_ActionLabel ) continue;
					$row[$column] = @$record[$column];
					if ( isset($this->cellFilters[$column]) ){
						$row[$column] = call_user_func($this->cellFilters[$column], $row, $column, $row[$column]);
					}
				}
				if ( $hasCallbacks ){
					$cbout = array();
					foreach ( $this->actionCellCallbacks as $cb ){
						$cbout[] = call_user_func($cb, $row);
					}
					$row[RecordGrid_ActionLabel] = implode('', $cbout);
				}
				
				$gridContent[] =& $row;
				unset($row);
			}
		}
		
		
		if ( $this->labels === null ){
			$this->labels = array();
			foreach ($columns as $column){
				if ( $column == RecordGrid_ActionLabel ){
					$labels[$column] = '';
					continue;
				}
				if ( is_a( $sampleRecord, 'Dataface_Record') ){
					$field =& $sampleRecord->_table->getField($column);
					$labels[$column] = $field['widget']['label'];
				} else if ( is_a($sampleRecord, 'Dataface_RelatedRecord') ){
					$table =& $sampleRecord->_relationship->getTable($column);
					$field =& $table->getField($column);
					$labels[$column] = $field['widget']['label'];
				} else {
					$labels[$column] = ucwords(str_replace('_',' ',$column));
				}
				unset($field);
				unset($table);
			}
		
		
		} else {
			$labels =& $this->labels;
		}
		
		
		
		$context = array( 'data'=> &$gridContent, 'labels'=>&$labels, 'columns'=>&$columns, 'id'=>$this->id, 'class'=>$this->cssclass);
		$skinTool =& Dataface_SkinTool::getInstance();
		ob_start();
		$skinTool->display($context, 'Dataface_RecordGrid.html');
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}
}
