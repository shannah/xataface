<?php
import('Dataface/Table.php');

/**
 * Handles the building of database tables and their associated configuration
 * files.  This version is only support the fields.ini file, but future versions
 * will support relationships and valuelists.  
 *
 * Usage:
 * <code>
 * $builder = new Dataface_Table_builder('mytable');
 * $builder->addField(
 *		array(
 *			'Field'=>'id',
 *			'Type'=>'int(11)',
 *			'Extra'=>'auto_increment',
 *			'Null'=>''
 *			)
 *		);
 * $builder->addField(
 *		array(
 *			'Field'=>'title',
 *			'Type'=>'varchar(64)',
 *			)
 *		);
 * $builder->save();	// saves the table to the database and the config files to disk.
 * </code>
 *
 */ 
class Dataface_Table_builder {

	/**
	 * @var Dataface_Table
	 */
	var $table;
	
	/**
	 * @var string $name
	 */
	var $name;
	
	var $fields=array();
	
	function Dataface_Table_builder($name){
		$app =& Dataface_Application::getInstance();
		$this->name = $name;
		if ( mysql_num_rows(mysql_query('show tables like \''.addslashes($name).'\'', $app->db())) > 0 ){
			$this->table =& Dataface_Table::loadTable($name);
		}
	
	}
	
	function createPrimaryKey(){
		foreach ($this->fields as $field){
			if ( strtolower($field['Key']) == 'pri' ){
				return;
			}
		}
		$this->fields['id'] = Dataface_Table::_newSchema('int(11)','id',$this->name);
		$this->fields['id']['Key'] = 'PRI';
		$this->fields['id']['Extra'] = 'auto_increment';
		
		
	}
	
	/**
	 * Returns a reference to the key fields of this table.  If the table has 
	 */
	function &keys(){
		if ( isset( $this->table ) ){
			$keys =& $this->table->keys();
		} else {
			$keys = array();
			foreach ( array_keys($this->fields) as $key ){
				if ( strtolower($this->fields[$key]['Key']) == 'pri' ){
					$keys[$key] =& $this->fields[$key];
				}
			}
		}
		return $keys;
	}
	
	/**
	 * Saves the table to the database and writes the configuration files.
	 */
	function save(){
		if ( isset($this->table) ) return $this->update();
		return $this->create();
	}	
	
	
	/**
	 * Creates a table in the database based on the name and field definitions
	 * in the builder. This also sets the table property to a Dataface_Table
	 * object for the created table.
	 *
	 * @return mixed Returns a PEAR_Error object if the create fails.
	 */
	function create(){
		$app =& Dataface_Application::getInstance();
		$this->createPrimaryKey();
		$sql = 'create table `'.addslashes($this->name).'` (
			';
		foreach ($this->fields as $field){
			$sql .= '`'.addslashes($field['Field']).'` '.addslashes($field['Type']).' '.addslashes($field['Extra']);
			//if ( $field['Extra'] ) $sql .= ' '.$field['Extra'];
			if ( !$field['Null'] ) $sql .= ' NOT NULL';
			if ( $field['Default'] ) $sql .= ' DEFAULT \''.addslashes($field['Default']).'\'';
			$sql .= ',
			';
			
		}
		
		$sql .= ' PRIMARY KEY (`'.implode('`,`',array_keys($this->keys())).'`)
			)';
		
		$res = mysql_query($sql, $app->db());
		if ( !$res ) return PEAR::raiseError(mysql_error($app->db()));
		
		$res = $this->writeConfigFiles();
		if ( PEAR::isError($res) ) return $res;
		
		$this->table =& Dataface_Table::loadTable($this->name);
		return true;
		
	}
	
	/**
	 * Updates the database table schema and config files to match the state 
	 * of the table object.
	 */
	function update(){
		$app =& Dataface_Application::getInstance();
		$res = mysql_query("show columns from `".str_replace('`','\\`',$this->table->tablename)."`", $app->db());
		$existing_fields = array();
		while ( $row = mysql_fetch_assoc($res) ){
			$existing_fields[$row['Field']] = $row;
		}
		
		// add new / modify existing fields
		foreach ( $this->table->fields() as $field ){
			if ( !isset($existing_fields[$field['Field']]) ){
				// the field does not exist yet.. let's add it
				$res = $this->addFieldToDB($field);
				if ( PEAR::isError($res) ) return $res;
			} else if ( $this->compareFields($field, $existing_fields[$field['Field']]) !== 0 ){
				$res = $this->alterFieldInDB($field, $existing_fields[$field['Field']]);
				if ( PEAR::isError($res) ) return $res;
			}
		}
		
		// remove fields that are no longer there
		$table_fields =& $this->table->fields();
		foreach ( $existing_fields as $field ){
			if ( !isset($table_fields[$field['Field']]) ){
				$res = $this->removeFieldFromDB($field);
				if ( PEAR::isError($res) ) return $res;
			}
		}
		
		// now we can write the config files
		$res = $this->writeConfigFiles();
		if ( PEAR::isError($res) ) return $res;
	}
	
	/**
	 * Alters a field.
	 * @param array $field The field definition to be modified.
	 * @param string $op The operation to perform.  Can take values 'add' or 'modify'.
	 *
	 */
	function alterFieldInDB($field, $op='modify'){
		$app =& Dataface_Application::getInstance();
		$sql = 'alter table `'.str_replace('`','\\`',$this->table->tablename).'` ';
		if ( strtolower($field['Type']) == 'container')  {
			$type = 'varchar(128)';
		} else {
			$type = $field['Type'];
		}
		$sql .= ' '.$op.' column `'.str_replace('`','\\`',$field['Field']).'` '.$type.' ';
		if ( isset($field['Extra']) ) $sql .= ' '.$field['Extra'];
		if ( !isset($field['Null']) ) $sql .= ' NOT NULL';
		if ( isset($field['Default']) ) $sql .= ' DEFAULT \''.$field['Default'].'\'';
		$res = mysql_query($sql, $app->db());
		if ( !$res ){
			return PEAR::raiseError("Unable to add field '$field[Field]': ".mysql_error($app->db()));
		}
		return true;
	}
	
	/**
	 * Adds a field to the table.
	 * @param array $field The field definition to add.
	 */
	function addFieldToDB($field){
		return $this->alterFieldInDB($field, 'add');
	}
	
	
	/**
	 * Removes a field from the table.
	 * @param array Field definition.
	 */
	function removeFieldFromDB($field){
		$app =& Dataface_Application::getInstance();
		$res = mysql_query("alter table `".str_replace('`','\\`', $this->table->tablename)."` 
			drop `".str_replace('`','\\`', $field['Field'])."`", $app->db());
		if ( !$res ) return PEAR::raiseError("Failed to remove field '$field[Field]': ".mysql_error($app->db()));
		return true;
	}
	

	
	/**
	 * Writes the configuration files (e.g. fields.ini file) for the table.
	 * @param array $params Associative array of parameters
	 * @param array $params[fields] An optional array of field definitions
	 */
	function writeConfigFiles($params=array()){
		if ( isset($params['fields']) ) $fields = $params['fields'];
		else if ( isset($this->table) ) $fields = $this->table->fields();
		else $fields = $this->fields;
		
		$path = DATAFACE_SITE_PATH.'/tables/'.$this->name;
		if ( !file_exists($path) ) mkdir($path,0777, true);
		$fieldsinipath = $path.'/fields.ini';
		$fh = fopen($fieldsinipath,'w');
		if ( !$fh ){
			return PEAR::raiseError("Failed to open file '$fieldsinipath'");
		}
		if ( flock($fh, LOCK_EX) ){
			foreach ( $fields as $field ){
				$flatfield = array();
				$this->flattenConfigArray($field, $flatfield);
				fwrite($fh, '['.$field['name']."]\n");
				foreach ( $flatfield as $key=>$value ){
					if ( $key == 'name' ) continue;
					fwrite($fh, $key .'= "'.str_replace('"','\\"', $value).'"'."\n");
				}
				fwrite($fh, "\n");
			}
			flock($fh, LOCK_UN);
		} else {
			return PEAR::raiseError("Failed to lock file for writing: $fieldsinipath");
		}
		
	}
	
	/**
	 * Flattens a configuration array into a normal key-value list so that it is ready to 
	 * write to a config file.
	 *
	 * @param array $field Associative array of configuration options.
	 * @param array &$arr Output parameter .  The flattened array that is output.
	 * @param string $prefix A string prefix for the keys.
	 * @return void
	 */
	function flattenConfigArray($field, &$arr, $prefix=''){
		
		foreach ( $field as $key=>$value ){
			$full_key = ( empty($prefix) ? $key : $prefix.':'.$key);
			if ( is_array($value) ){
				$this->flattenConfigArray($value, $arr, $full_key);
			} else {
				$arr[$full_key] = $value;
			}
		}
	}
	
	/**
	 * Compares two field definitions to make sure that they are identical.
	 * @param array $field1 Associative array representing a field.
	 * @param array $field2 Associative array representing b field.
	 * @return integer 0 If field1 and field2 are the same.  Non-zero otherwise.
	 */
	function compareFields($field1, $field2){
		$indicators = array('Field','Type','Null','Default','Extra');
		foreach ($indicators as $indicator){
			if ( @$field1[$indicator] != @$field2[$indicator] ) return 1;
			
		}
		return 0;
	}
	
	/**
	 * Obtains a reference to the fields array for this table.
	 */
	function &fields(){
		if ( isset($this->table) ) $fields =& $this->table->fields();
		else $fields =& $this->fields;
		return $fields;
	}
	
	/**
	 * Adds a field to this table.
	 * @param array $field A partial field definition.  Must contain at least
	 *	Field (or name) and Type keys.
	 * @param string $field[Field] The name of the field
	 * @param string $field[Type] The type of the field (e.g. int(11))
	 * @param string $field[Default] The default value
	 * @param string $field[Key] 'PRI' if this is part of the primary key.
	 * @param string $field[Null] Empty if the field is not null.
	 * @return array The finished field definition.
	 */
	function &addField($field){
		if ( !isset($field['Field']) and !isset($field['name']) ){
			$err = PEAR::raiseError("Attempt to add field that has no name.");
			return $err;
		}
			
		if ( !isset($field['Field']) ) $field['Field'] = $field['name'];
		if ( !isset($field['name']) ) $field['name'] = $field['Field'];
		
		$schema = Dataface_Table::_newSchema($field['Type'],$field['name'], $this->name);
		
		
		
		$fields =& $this->fields();
		$fields[$field['name']] =& $schema;
		
		$conf = array();
		$this->flattenConfigArray($field,$conf);
		foreach ( array_keys($conf) as $key){
			$this->setParameter($field['name'], $key, $conf[$key]);
		}
		return $conf;
	}
	
	function removeField($name){
		$fields =& $this->fields();
		
		unset($fields[$name]);
		return true;
	}
	
	
	function &getField($name){
		$fields =& $this->fields();
		return $fields[$name];
	}
	
	function getParameter($fieldname, $paramname){
		$fields =& $this->fields();
		$field =& $fields[$fieldname];
		$param =& $field;
		$path = explode(':', $paramname);
		foreach ( $path as $key ){
			if ( !isset($param[$key]) ) return null;
			$temp =& $param[$key];
			unset($param);
			$param =& $temp;
			unset($temp);
		}
		return $param;
		
	}
	
	function setParameter($fieldname, $paramname, $paramvalue){
		$fields =& $this->fields();
		$field =& $fields[$fieldname];
		$param =& $field;
		$path = explode(':', $paramname);
		$last = end($path);
		reset($path);
		foreach ( $path as $key ){
			if ( !isset($param[$key]) && $key != $last) $param[$key] = array();
			if ( $key == $last ) {
				$param[$key] = $paramvalue;
				return true;
			}
			$temp =& $param[$key];
			unset($param);
			$param =& $temp;
			unset($temp);
		}
		
		trigger_error("Something went wrong settingn parameter $paramname to value $paramvalue on line ".__LINE__." of file ".__FILE__, E_USER_ERROR);
		
	}
	
	
	
	
	
	

}
