<?php
import('Dataface/Table.php');

/**
 * A tool for managing table metadata.  Meta data can be any data that describes
 * a record but is not part of the record itself.  This is handy for storing 
 * workflow information about records such as translation status.  Generally
 * metadata for a given table is stored in a separate table named tablename__metadata
 * where 'tablename' is the name of the table described by the metadata.
 *
 * @author Steve Hannah (shannah@sfu.ca)
 * @created August 29, 2006
 */
class Dataface_MetadataTool {
	
	/**
	 * @var array Associative array of field definitions as loaded from the
	 * metadata.ini files.
	 */
	var $fieldDefs = null;
	
	/**
	 * @var string Name of the subject table.
	 */
	var $tablename = null;
	
	/**
	 * @var array Associative array of column definitions for the metadata 
	 *			  table as returned by a show columns query.
	 */
	var $columns = null;
	
	/**
	 * @var array An associative array of columns that are included in the
	 *			  primary key.  The columns are in a form as returned by the show columns query.
	 */
	var $keyColumns = null;
	
	function Dataface_MetadataTool($tablename){
		$this->tablename = $tablename;
	}
	
	/**
	 * Checks a table name to see if it is a metadata table.  A metadata table
	 * always ends in '__metadata'.
	 * @param string $tablename The name of the table to check.
	 * @returns boolean
	 */
	function isMetadataTable($tablename=null){
		if ( !isset($tablename) ) $tablename = $this->tablename;
		return (strstr( $tablename, '__metadata') == '__metadata');
	
	}
	
	/**
	 * Gets the column definitions of the metadata table as produced by show columns SQL query.
	 * @param string $tablename The name of the subject table.
	 * @param boolean $usecache Whether to use cached results or to forcefully obtain up-to-date data.
	 * @returns array Associative array of column definitions.
	 */
	function &getColumns($tablename=null, $usecache=true){
		$app =& Dataface_Application::getInstance();
		if (!isset($tablename) ) $tablename = $this->tablename;
		$md_tablename = $tablename.'__metadata';
		if ( !isset($this->columns) || !$usecache ){
			$this->columns = array();
			$sql = "show columns from `".$md_tablename."`";
			$res = mysql_query($sql, $app->db());
			if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
			if ( mysql_num_rows($res) == 0) trigger_error("No metadata table '{$md_tablename}' could be found.", E_USER_ERROR);
			
			while ( $row = mysql_fetch_assoc($res) ){
				$this->columns[$row['Field']] = $row;
			}
			@mysql_free_result($res);
		}
		return $this->columns;
	
	}
	
	/**
	 * Returns the columns of this metadata table that are part of the primary
	 * key.
	 * @param string $tablename The name of the subject table.
	 * @param boolean $usecache Whether to use cached results or to force a new call to show columns
	 * @returns array Associative array of primary key columns with key=name, value=associative array.
	 */
	function &getKeyColumns($tablename=null, $usecache=true){
		if (!isset($tablename) ) $tablename = $this->tablename;
		$md_tablename = $tablename.'__metadata';
		if ( !isset($this->keyColumns) || !$usecache ){
			$this->keyColumns = array();
			$cols = $this->getColumns($tablename, $usecache);
			foreach (array_keys($cols) as $col){
				if ( strcasecmp($this->columns[$col]['Key'], 'PRI') === 0 ){
					$this->keyColumns[$this->columns[$col]['Field']] =& $this->columns[$col];
				}
			}
		}
		
		return $this->keyColumns;
		
	}

	
	
	/**
	 * Loads the field definitions for meta data for the given table.  These
	 * are defined in the metadata.ini files at the table, application, and 
	 * dataface levels.
	 */
	function loadMetadataFieldDefs($tablename=null){
		if ( !isset($tablename) ) $tablename = $this->tablename;
		if ( !isset($this->fieldDefs) ){
			
		
			import('Dataface/ConfigTool.php');
			$configTool =& Dataface_ConfigTool::getInstance();
			$this->fieldDefs = $configTool->loadConfig('metadata',$tablename);
			foreach (array_keys($this->fieldDefs) as $key ){
				$field =& $this->fieldDefs[$key];
				$field['name'] = '__'.$key;
				$field['Field'] = $field['name'];
				if ( !isset($field['Type']) ) $field['Type'] = 'varchar(64)';
				$this->fieldDefs['__'.$key] =& $field;
				unset($this->fieldDefs[$key]);
				unset($field);
			}
		}
		
		return $this->fieldDefs;
	
	}
	

	/**
	 * Creates a table to store the metadata for the given table.
	 *
	 * @param string $tablename The name of the table for which the metadata is 
	 *							to be stored.
	 * @returns boolean True if the table is created.. false otherwise.
	 */
	function createMetadataTable($tablename=null){
		if ( !isset($tablename) ) $tablename = $this->tablename;
		if ( Dataface_MetadataTool::isMetadataTable($tablename) ) return false;
		$app =& Dataface_Application::getInstance();
		
		$table =& Dataface_Table::loadTable($tablename);
		
		if ( !Dataface_Table::tableExists($tablename.'__metadata', false) ){
			$sql = "CREATE TABLE `{$tablename}__metadata` (
				";
			foreach ($table->keys() as $field ){
				$type = (strtolower($field['Type']) != 'container' ? $field['Type'] : 'varchar(64)');
				
				$sql .= "`{$field['name']}` {$type} DEFAULT NULL,
				";
				
			}
			$metafields = $this->loadMetadataFieldDefs($tablename);
			foreach ($metafields as $fieldname=>$field){
				if ( @$field['Default'] ) $default = " DEFAULT '{$field['Default']}'";
				else $default = '';
				$sql .= "`{$fieldname}` {$field['Type']}{$default},";
			}
			
			$keynames = array_keys($table->keys());
			$sql .= "primary key (`".implode('`,`', $keynames)."`))";
			$res = mysql_query($sql, $app->db());
			if ( !$res ) trigger_error(mysql_error($res), E_USER_ERROR);
			return true;
				
		} 
		
		return false;
		
		
	}
	
	/**
	 * Refreshes the metadata table for a given table.  This means that missing
	 * columns and keys are created so that the schema matches the schema of
	 * the current table structure.
	 *
	 * @param string $tablename The name of the table for which the metadata is being
	 *					 stored.
	 */
	function refreshMetadataTable($tablename=null){
		if ( !isset($tablename) ) $tablename = $this->tablename;
		if ( Dataface_MetadataTool::isMetadataTable($tablename) ) return false;
		$app =& Dataface_Application::getInstance();
		$table =& Dataface_Table::loadTable($tablename);
		$md_tablename = $tablename.'__metadata';
		if ( !Dataface_Table::tableExists($md_tablename, false) ){
			if ( $this->createMetadataTable($tablename) ) return true;
		}
		$cols =& $this->getColumns($tablename, false);
		
		
		// First we have to go through all of the key fields of the subject table
		// and make sure that they appear in the metadata table.
		$updatePrimaryKey = false;
		foreach ($table->keys() as $field){
			if ( !isset($cols[$field['Field']]) ){
				$updatePrimaryKey=true;
				$default = ( @$field['Default'] ? " DEFAULT {$field['Default']}" : '');
				$sql = "alter table `{$md_tablename}` add column `{$field['Field']}` {$field['Type']}{$default}";
				$res = mysql_query($sql, $app->db());
				if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
			}
		}
		
		$table_keys =& $table->keys();
		
		//Next we have to go through all of the key fields in the metadata table ane make sure that they
		// appear in the subject table primary keys.
		foreach ($this->getKeyColumns($tablename, false) as $field){
			if ( !isset($table_keys[$field['Field']]) ){
				$updatePrimaryKey = true;
				$sql = "alter table `{$md_tablename}` drop column `{$field['Field']}`";
				$res = mysql_query($sql, $app->db());
				if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
			}
		}
		
		// If the primary key needed to be updated, we will update it now.
		if ( $updatePrimaryKey ){
			// The primary key needs to be updated
			$sql = "drop primary key";
			@mysql_query($sql, $app->db());
			$sql = "alter table `{$md_tablename}` add primary key (`".implode('`,`',array_keys($table->keys()))."`)";
			$res = mysql_query($sql, $app->db());
			if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		
		}
		
		// Now we need to make sure that all of the prescribed meta fields are 
		// in the metadata field.
		
		$fielddefs = $this->loadMetadataFieldDefs($tablename);
		$cols = $this->getColumns($tablename, false);
		
		foreach ($fielddefs as $field){
			if ( !isset($cols[$field['Field']]) ){
				$default = (@$field['Default'] ? " DEFAULT {$field['Default']}": '');
				$sql = "alter table `{$md_tablename}` add column `{$field['Field']}` {$field['Type']}{$default}";
				$res = mysql_query($sql, $app->db());
				if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
			}
		}
		return true;
		
	}
}
