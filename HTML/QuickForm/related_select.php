<?php
/**
 * File: HTML/QuickForm/related_select.php
 * Author: Steve Hannah <shannah@sfu.ca>
 * Created: Nov. 14, 2005
 * Description
 * -----------
 * A Select widget to select an element of a relationship.
 */
 
require_once 'HTML/QuickForm/select.php';
require_once 'Dataface/Relationship.php';
require_once 'Dataface/QueryTool.php';
require_once 'Dataface/DB.php';
class HTML_QuickForm_related_select extends HTML_QuickForm_select {
	
	function HTML_QuickForm_related_select( &$relationship ){
		$sql = $relationship->getDomainSQL();
			// The sql query to return all candidate rows of relationship
		$fkeys = $relationship->getForeignKeyValues();
			// Values of foreign keys (fields involved in where and join clauses)
		$table = $relationship->getDomainTable();
		if ( isset( $fkeys[$table] ) ){
			$query = $fkeys[$table];
			foreach ($query as $key=>$val){
				if ( strpos($val,'$')===0 or $val == '__'.$table.'__auto_increment__'){
					unset($query[$key]);
				}
			}
		} else {
			$query = array();
		}
		$qt = new Dataface_QueryTool($table, $relationship->_sourceTable->db, $query);
		$options = $qt->getTitles();
		//print_r($options);
		
		$this->HTML_QuickForm_select('test','test',$options);
		
		echo $this->toHtml();
		
	
	}


}


