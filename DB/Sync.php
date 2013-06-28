<?php


class DB_Sync {

	var $db1;
	var $db2;
	
	var $table1;
	var $table2;

	var $table1Data;
	var $table2Data;
	
	var $renamed = array();
	var $listeners = array();
	
	function DB_Sync($db1, $db2, $table1=null, $table2=null, $renamed=null){
		$this->db1 = $db1;
		$this->db2 = $db2;
		
		$this->init($table1, $table2, $renamed);
	}

	
	/**
	 * Compares 2 tables to see if they are identical in definition.
	 *
	 * @param string $table1 The name of the first table.
	 * @param string $table2 The name of the second table.
	 * @returns boolean  True if the tables have identical schemas.
	 *
	 */
	function equals($table1=null, $table2=null){
		
		$this->init($table1,$table2);
		echo "Now here";
		print_r($this->table1Data);
		print_r($this->table2Data);
		return ( $this->table1Data == $this->table2Data );
		
	}
	
	function init($table1, $table2, $renamed=null){
		
		if ( isset($table1) and isset($table2) and ($table1 != $this->table1 || $table2 != $this->table2) ){
			
			
			$this->table1 = $table1;
			$this->table2 = $table2;
			if ( isset($renamed) ) $this->renamed = $renamed;
			$this->loadTableData();
			
		}
	}
	
	function checkTableNames(){
	
		if ( !isset($this->table1) ){
			trigger_error("Attempt to load data for tables in DB_Sync, but table 1 has not been specified.", E_USER_ERROR);
		}
		
		if ( !isset($this->table2) ){
			trigger_error("Attempt to load data for tables in DB_Sync, but table 2 has not been specified.", E_USER_ERROR);
		}
		
		if ( !preg_match('/^[a-zA-Z0-9_]+$/', $this->table1) ){
			trigger_error("The table '{$this->table1}' has an invalid name.", E_USER_ERROR);
		}
		
		if ( !preg_match('/^[a-zA-Z0-9_]+$/', $this->table2) ){
			trigger_error("The table '{$this->table2}' has an invalid name.", E_USER_ERROR);
		}
	
	}
	
	
	
	/**
	 * Loads the table data for table 1 and table 2 into table1Data and table2Data respectively.
	 */
	function loadTableData(){
	
		$this->checkTableNames();
		
		$res = mysql_query("show full fields from `".$this->table1."`", $this->db1);
		if ( !$res ) trigger_error(mysql_error($this->db1));
		
		
		$this->table1Data = array();
		while ( $row = mysql_fetch_assoc($res) ){
			$this->table1Data[$row['Field']] = $row;
		}
		
		@mysql_free_result($res);
		
		
		$res = mysql_query("show columns from `".$this->table2."`", $this->db2);
		if ( !$res ) trigger_error(mysql_error($this->db2));
		
		$this->table2Data = array();
		while ( $row = mysql_fetch_assoc($res) ){
			$this->table2Data[$row['Field']] = $row;
		}
		
		@mysql_free_result($res);
		
		
	}
	
	/**
	 * Converts a field's array data into an SQL string definition.
	 * @param array $field The array data for a field.
	 * @returns string SQL definition for field.
	 */
	function fieldArrayToSQLDef($field){
	
		if ( $field['Default']  ){
		
			if ( strcasecmp($field['Default'], 'NULL') === 0 ){
			
				$default = 'default NULL';
				
			} else if ( $field['Default'] ) {
			
				$default = 'default \''.$field['Default'].'\'';
			} else {
				$default = '';
			}
			
		} else {
		
			$default = '';
			
		}
		
		if ( $field['Collation'] and strcasecmp($field['Collation'],'null') !== 0){
		
			$charset = 'CHARACTER SET '.substr($field['Collation'],0, strpos($field['Collation'], '_')).' COLLATE '.
						$field['Collation'];
						
		}	else {
		
			$charset = '';
		}
		
		if ( $field['Null'] ){
		
			$null = ( ( strcasecmp('yes',$field['Null'])===0 ) ? '' : 'NOT NULL');
		} else {
			$null = '';
		}
		
		
		
		
		
		return "`{$field['Field']}` {$field['Type']} {$charset} {$null} {$field['Extra']} {$default}";
	
	}
	
	/**
	 * Synchronizes the field named $fieldname.
	 */
	function syncField($fieldname, $after=null, $renameMap=null){
		
		if (isset($renameMap) ) $this->renamed = $renameMap;
		
		
		
		if ( !isset($this->table1Data[$fieldname]) ){
			
			// Table 1 does not have this field... see if it has been renamed.
			
			if ( isset($this->renamed[$fieldname]) ){
				
				$newname = @$this->renamed[$fieldname];
				
				if ( !$newname ){
					trigger_error("Attempt to rename field '{$fieldname}' in table '{$this->table2}' to {$newname} but the source table '{$this->table1}' has no such field to copy.", E_USER_ERROR);
				}
				
				$sql = "alter table `{$this->table2}` change `{$fieldname}` ".$this->fieldArrayToSQLDef($this->table1Data[$newname]);
				$res = mysql_query($sql, $this->db2);
				if ( !$res ) trigger_error(mysql_error($this->db2), E_USER_ERROR);
				
			} else {
			
				trigger_error("Attempt to syncronize field '{$fieldname}' but the source table has no such field.", E_USER_ERROR);
			}
			
			
		} else if ( !isset( $this->table2Data[$fieldname] ) ) {
		
			$sql = "alter table `{$this->table2}` add ".$this->fieldArrayToSQLDef($this->table1Data[$fieldname]);
			if ( isset($after) ){
				$sql .= "after `{$after}`";
			}
			$res = mysql_query($sql, $this->db2);
			if ( !$res ) trigger_error($sql."\n".mysql_error($this->db2), E_USER_ERROR);
			
		} else if ( $this->table1Data[$fieldname] != $this->table2Data[$fieldname] ) {
			
			$sql = "alter table `{$this->table2}` change `{$fieldname}` ".$this->fieldArrayToSQLDef($this->table1Data[$fieldname]);
			$res = mysql_query($sql, $this->db2);
			if ( !$res ) trigger_error(mysql_error($this->db2), E_USER_ERROR);
			
		} else {
		
			// nothing to do here.
		}
	}
	
	
	
	function syncTables($table1=null, $table2=null, $renamedMap=null){
		
		$this->init($table1, $table2, $renamedMap);
		
		if (!$this->equals() ){
			echo "Here";
			$positions = array();
			$fieldnames = array_keys($this->table1Data);
			$i=0;
			foreach ($fieldnames as $f){
				$positions[$f] = $i++;
				
			}
			
			$fields = array_merge(array_keys($this->table1Data), array_keys($this->table2Data));
			$fields = array_unique($fields);
			print_r($fields);
			foreach ($fields as $field ){
				if ( isset( $positions[$field] ) and $positions[$field] > 0 ){
					$after = $fieldnames[$positions[$field]-1];
				} else if ( isset($positions[$field]) ){
					$after = "first";
				} else {
					$after = null;
				}
				$this->syncField($field, $after);
			}
		}
		
		
		
	
	}
}

?>
