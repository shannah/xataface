<?php
import('Dataface/ResultList.php');
/**
 * A tree table for displaying records in a heirarchical view.
 *
 * @author Steve Hannah <shannah@sfu.ca>
 * @created July 18, 2006
 */
class Dataface_TreeTable {

	/**
	 * @var Dataface_Record The record that serves as the root of the tree.
	 */
	var $record;
	
	/**
	 * @var string The name of the relationship (optional) comprising the 
	 * first level of the tree.
	 */
	var $relationship;

	
	/**
	 * Constructor.
	 * @param Dataface_Record $record The root record.
	 * @param string $relationship The name of the relationship.
	 */
	function Dataface_TreeTable(&$record, $relationship=null){
		$this->record =& $record;
		$this->relationship = $relationship;
	}
	
	/**
	 * Returns the Dataface_Record object that belongs at the given row id.
	 * @param string $rowid A hyphenated string that uniquely identifies a
	 * row of the tree table.  e.g. 1-3-2 identifies the 2nd subrow of the
	 * 3rd subrow of the 1st row.
	 * @return Dataface_Record.
	 */
	function &getRecordByRowId($rowid){
		$path = explode('-', $rowid);
		if ( empty($path[0]) ) array_shift($path);
		
		if ( empty($path) ) return $this->record;
		
		$root =& $this->record;
		
		if ( isset($this->relationship) ){
			// first level comes from relationship
			$index = intval(array_shift($path));
			$record =& $this->record->getRelatedRecord($this->relationship, $index-1);
			if ( !isset($record) ) {
				$null = null;
				return $null;
			}
			unset($root);
			$root =& $record->toRecord();
			unset($record);
		}
		
		while ( !empty($path) ){
			$currid = array_shift($path);
			$record =& $root->getChild(intval($currid)-1);
			if ( !isset($record) ){
				$null = null; return $null;
			}
			unset($root);
			$root =& $record;
			unset($record);
		}
		
		return $root;
	}
	
	/**
	 * Returns the subrows of the given row as an array with keys being the 
	 * rowid and values being Dataface_Record objects.
	 * 
	 * @param string $rowid The row id of the root row.  This will be a string 
	 *  such as '1-2-4'.  In this example the root would be the 4th subrow of the
	 *  2nd subrow of the first row of the tree-table.
	 * @param integer $depth How deep down the tree heirarchy do we want to go.
	 * @return array An array of Dataface_Record objects that are subrows of the given
	 *	row.
	 */
	function &getSubrows(&$rows, $rowid, &$record, $depth=3){
		if ( $depth == 0 ) return $rows;
		if ( isset($record) ) $root =& $record;
		else {
			$root =& $this->getRecordByRowId($rowid);
			if ( !isset($root ) ) return $rows;
		}
		if ( empty($rowid) and isset($this->relationship) ){
			// we are starting from the root and the first level should from the given relationship.
			$it = $root->getRelationshipIterator($this->relationship);
			$i = 1;
			while ( $it->hasNext() ){
				$curr_rowid = strval($i++);
				$row =& $it->next();
				$rowRecord =& $row->toRecord();
                                
				$rows[$curr_rowid] = array('record'=>& $rowRecord, 'rowid'=>$curr_rowid, 'hasChildren'=>false);
				$numrows = count($rows);
				$this->getSubrows($rows, $curr_rowid, $rowRecord, $depth-1);
				if ( count($rows)>$numrows) $rows[$curr_rowid]['hasChildren'] = true;
				unset($rowRecord);unset($row);
			}
		} else {
			$children = $root->getChildren();
			if ( isset($children) ){
				$i=1;
				foreach (array_keys($children) as $childkey){
					$curr_rowid = $rowid.(!empty($rowid)?'-':'').strval($i++);
					$rowRecord =& $children[$childkey];
					$rows[$curr_rowid] = array('record'=>&$rowRecord, 'rowid'=>$curr_rowid, 'hasChildren'=>false);
					$numrows = count($rows);
					$this->getSubrows($rows, $curr_rowid, $rowRecord, $depth-1);
					if ( count($rows)>$numrows) $rows[$curr_rowid]['hasChildren'] = true;
					unset($rowRecord);
				}
			}
		}
		return $rows;
	}
	
	/**
	 * Returns the subrows rooted at $rowid (not including $rowid's row) as html
	 * &lt;tr&gt; tags.
	 * @param string $rowid The id of the row whose subrowse we wish to retrieve.
	 * @param integer $depth How far down the tree do we want to go.
	 * @return string HTML for the subrows.
	 */
	function getSubrowsAsHTML($rowid, $depth=3, $treetableid='treetable'){
		if ( isset($this->relationship) ){
			$rel =& $this->record->_table->getRelationship($this->relationship);
			$table =$rel->getDomainTable();
			if ( PEAR::isError($table) ){
				$destTables =& $rel->getDestinationTables();
				$table = $destTables[0]->tablename;
			}
		} else {
			$table = $this->record->_table->tablename;
			$rel =& $this->record->_table->getChildrenRelationship();
		}
		
		$default_order_column = $rel->getOrderColumn();
		
		$resultList = new Dataface_ResultList($table);
		$columns = $resultList->_columns;
		$rows = array();
		$null = null;
		
		$this->getSubrows($rows, $rowid, $null, $depth);

		ob_start();
		foreach ( array_keys($rows) as $curr_rowid ){
			$path = explode('-',$curr_rowid);
			$level = count($path);
			$class = ( $rows[$curr_rowid]['hasChildren'] ? 'folder':'doc');
			echo "<tr id=\"".df_escape($curr_rowid)."\">";
			/*
			$keyString = implode('-',$rows[$curr_rowid]['record']->getValuesAsStrings(
					array_keys($rows[$curr_rowid]['record']->_table->keys())
				)
			);*/
                        $relatedRecord = new Dataface_RelatedRecord($this->record, $this->relationship,$rows[$curr_rowid]['record']->vals() );
                        $keyString = $relatedRecord->getId();
			echo "<td class=\"\"><input id=\"remove_".df_escape($keyString)."_checkbox\" type=\"checkbox\" name=\"--remkeys[]\" value=\"".df_escape($keyString)."\"/></td>";
			echo "
				<td><div class=\"tier{$level}\"><a href=\"#\" ";
			if ( $class == 'folder'){
				echo "onclick=\"TreeTable.prototype.trees['$treetableid'].toggleRows(this)\" ";
			} 
			$url = $rows[$curr_rowid]['record']->getURL();
			$editURL = $rows[$curr_rowid]['record']->getURL('-action=edit');
			$deleteURL = $rows[$curr_rowid]['record']->getURL('-action=delete');
			echo "class=\"$class\"></a></td>
				<td><a href=\"".df_escape($url)."\">".df_escape($rows[$curr_rowid]['record']->getTitle())."</a></td>";

			foreach ( $columns as $col ){
				echo "<td>";
				if ( $col == $default_order_column ){
				// If this is the order column, we will provide links to move the record up or down in order.
						
						if ( $path[count($path)-1] !== '1' ){
							echo "<a href=\"#\" onclick=\"moveUp(".(intval($path[count($path)-1])-1).")\" title=\"Move up\"><img src=\"".DATAFACE_URL."/images/arrowUp.gif\"/></a>";
						}
						//if ( $i != $this->_start+$limit-1 ){
							echo "
								<a href=\"#\" onclick=\"moveDown(".(intval($path[count($path)-1])-1).")\" title=\"Move down\"><img src=\"".DATAFACE_URL."/images/arrowDown.gif\"/></a>";
						//}
						//echo "</td>
						//		";
				
				} else {
					if ( $rows[$curr_rowid]['record']->_table->hasField($col) ){
						
						echo "<a href=\"".df_escape($url)."\">".$rows[$curr_rowid]['record']->htmlValue($col)."</a>";
					} else {
						echo '--';
					}
				}
					
				echo "
				</td>";
			}
			echo "
			</tr>
			";
			
		}
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}
	
	/**
	 * Renders the entire treetable as HTML.
	 */
	function toHtml($depth=3, $treetableid='treetable'){
		$app =& Dataface_Application::getInstance();
		if ( isset($this->relationship) ){
			$rel =& $this->record->_table->getRelationship($this->relationship);
			$table =$rel->getDomainTable();
			if ( PEAR::isError($table) ){
				$destTables =& $rel->getDestinationTables();
				$table = $destTables[0]->tablename;
			}
		} else {
			$table = $this->record->_table->tablename;
			$rel =& $this->record->_table->getChildrenRelationship();
		}
	
 		//echo "Def order col = $default_order_column";
 		//ob_start();
 		
 		//$moveUpForm = ob_get_contents();
 		//ob_end_clean();
		
		$resultList = new Dataface_ResultList($table);
		$columns = $resultList->_columns;
		ob_start();
		$default_order_column = $rel->getOrderColumn();
		if ( isset($default_order_column) ){
 			//echo "<script language=\"javascript\" type=\"text/javascript\"><!--";
 			df_display(array('redirectUrl'=>$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING']), 'Dataface_MoveUpForm.html');
 			//echo "//--></script>";
 		}
		
		if ( !defined('Dataface_TreeTable_JAVASCRIPT_LOADED') ){
			define('Dataface_TreeTable_JAVASCRIPT_LOADED',true);
			echo '<script language="javascript" type="text/javascript" src="'.DATAFACE_URL.'/js/TreeTable.js"></script>';
		}
		echo '<form action="'.df_escape($_SERVER['PHP_SELF']).'" method="GET" onsubmit="return validateTTForm(this);">';
		echo '<input type="hidden" name="--selected-ids" value=""/>';
                echo "<table width=\"100%\" id=\"$treetableid\" class=\"treetable\">";
		
		echo "<thead><tr><th><!-- checkbox column --></th><th><!-- Icon column --></th><th>Title</th>";
		foreach ($columns as $col){
			echo "<th>$col</th>";
		}
		echo "</tr></thead><tbody>";
		echo $this->getSubrowsAsHTML('',$depth,$treetableid);
		
		echo "</tbody></table>";
		import('Dataface/ActionTool.php');
		$actionsTool =& Dataface_ActionTool::getInstance();
		$actions = $actionsTool->getActions(array('category'=>'selected_records_actions'));
		if (count($actions)>0 ){
			echo "   Perform on selected records:
			<select name=\"-action\">";
			foreach (array_keys($actions) as $i){
				echo "<option value=\"".df_escape($actions[$i]['name'])."\">"
                                        .df_escape($actions[$i]['label']).
                                        "</option>
				";
			}
		
		
			echo "
			
			</select>
			
			";
			echo "<input type=\"submit\" value=\"Submit\"/>";
		}
		
		import('Dataface/Utilities.php');
		
		// We need to build a query.
		$q = array('-table'=>$this->record->_table->tablename);
		foreach ( array_keys($this->record->_table->keys()) as $tkey){
			$q['--__keys__'][$tkey] = $this->record->strval($tkey);
		}
		$q['-relationship'] = $this->relationship;
		
		echo Dataface_Utilities::query2html($q, array('-action'));
		echo '<input type="hidden" name="-redirect" value="'.$_SERVER['REQUEST_URI'].'"/>';
		echo "</form>";
		echo "
		<script language=\"javascript\" type=\"text/javascript	\"><!--
			TreeTable.prototype.trees['$treetableid'] = new TreeTable('$treetableid','');
		//--></script>
		";
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	}

}
