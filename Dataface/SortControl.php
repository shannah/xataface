<?php
class Dataface_SortControl {
	var $current_sort;
	var $fields;
	var $prefix;
	var $table;
	function Dataface_SortControl($fields, $prefix=''){
		
		if ( is_string($fields) ){
			$t =& Dataface_Table::loadTable($fields);
			$fields = array_keys($t->fields(false,true));
		} else {
			$app =& Dataface_Application::getInstance();
			$query =& $app->getQuery();
			$t =& Dataface_Table::loadTable($query['-table']);
		}
		
		$this->table =& $t;
		$this->fields = array();
		
		if ( isset($t->_atts['__global__']) ) $globalProps = $t->_atts['__global__'];
		else $globalProps = array();
		foreach ( $fields as $field ){
			$fieldDef =& $t->getField($field);
			if ( isset($globalProps['sortable']) and !$globalProps['sortable'] and !@$fieldDef['sortable']){
				continue;
			} else if ( isset($fieldDef['sortable']) and !@$fieldDef['sortable'] ){
				continue;
			}
			$this->fields[] = $field;
		}
		
		$this->prefix = $prefix;
		//$this->fields = $fields;
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		if ( !isset($query['-'.$prefix.'sort']) ){
			$sort = '';
		} else {
			$sort = $query['-'.$prefix.'sort'];
		}
		
		$sort = array_map('trim',explode(',', $sort));
		
		$sort2 = array();
		foreach ( $sort as $col ){
			if ( !trim($col) ) continue;
			$col = explode(' ',$col);

			if ( count($col) <= 1 ){
				$col[1] = 'asc';
			}
			$sort2[$col[0]] = $col[1];
		}
		
		// Now sort2 looks like array('col1'=>'asc', 'col2'=>'desc', etc...)
		$this->current_sort=&$sort2;
		$this->fields = array_diff($this->fields, array_keys($this->current_sort));
		
	}
	
	function toHtml(){
		$id = rand(10,100000);
		$app =& Dataface_Application::getInstance();
		$p = $this->prefix;
		if ( @$app->prefs['default_collapse_sort_control'] ){
			$out = '<a href="#" onclick="document.getElementById(\'Dataface_SortControl-'.$id.'\').style.display=\'\'; this.style.display=\'none\'; return false">Sort Results</a>';
			$style = 'display:none';
		} else {
			$style = '';
		}	
		$out .= '<div style="'.$style.'" id="Dataface_SortControl-'.$id.'" class="Dataface_SortControl"><fieldset><legend>Sorted on:</legend><ul class="Dataface_SortControl_current_sort-list">
				';
		foreach ($this->current_sort as $fieldname=>$dir){
			$fieldDef = $this->table->getField($fieldname);
			$out .= '<li>
			
			<a class="Dataface_SortControl-reverse-'.$dir.'" href="'.$app->url('-'.$p.'sort='.urlencode($this->reverseSortOn($fieldname))).'" title="Sort the results in reverse order on this column"><img src="'.DATAFACE_URL.'/images/'.($dir=='asc' ? 'arrowUp.gif' : 'arrowDown.gif').'"/>'.$fieldDef['widget']['label'].'</a>
			<a href="'.$app->url('-'.$p.'sort='.urlencode($this->removeParameter($fieldname))).'" title="Remove this field from the sort parameters"><img src="'.DATAFACE_URL.'/images/delete.gif"/></a>
			</li>';
		}
		$out .= '</ul>';
		
		$out .= '<select onchange="window.location=this.options[this.selectedIndex].value">
			<option value="">Add Columns</th>';
		foreach ($this->fields as $fieldname){
			$fieldDef = $this->table->getField($fieldname);
			$out .= '<option value="'.$app->url('-'.$p.'sort='.urlencode($this->addParameter($fieldname))).'">'.$fieldDef['widget']['label'].'</option>';
		}
		$out .= '</select><div style="clear:both"></div></fieldset></div>';
		return $out;
	
	}
	
	function reverseSortOn($fieldname){
		$params = $this->current_sort;
		$curr = strtolower($params[$fieldname]);
		$params[$fieldname] = ( $curr == 'asc' ? 'desc' : 'asc');
		return $this->sortString($params);
	}
	
	function addParameter($fieldname,$dir='asc'){
		$params = $this->current_sort;
		$params[$fieldname] = $dir;
		return $this->sortString($params);
	}
	
	function removeParameter($fieldname){
		$params = $this->current_sort;
		unset($params[$fieldname]);
		return $this->sortString($params);
	}
	
	function sortString($params){
		$out = array();
		foreach ( $params as $fieldname=>$dir){
			$out[] = $fieldname.' '.$dir;
		}
		return implode(',',$out);
	}
}
