<?php
namespace xf\db;
import(XFROOT.'xf/db/Database.php');
class Binding {


	/**
	 * @var \Dataface_Table
	 */
	private $table;
	
	/**
	 * @var string
	 */
	private $fieldName;
	
	const BINDINGS_TABLE = 'xf_bindings';
	
	private static $bindingsCache;
	
	
	/**
	 * @param $table \Dataface_Table
	 * @param $fieldName string
	 */
	public function __construct(\Dataface_Table $table, $fieldName) {
		$this->table = $table;
		$this->fieldName = $fieldName;
	}
	
	private static function loadBindingsFromDatabase() {
		$db = new Database(df_db()); 
		$bindings = array();
		try {
			foreach ($db->getObjects('select * from `'.self::BINDINGS_TABLE.'`') as $o) {
				$bindings[$o->table_name.'.'.$o->column_name] = $o;
			}
		} catch (\Exception $ex) {
			self::createBindingsTable();
			foreach ($db->getObjects('select * from `'.self::BINDINGS_TABLE.'`') as $o) {
				$bindings[$o->table_name.'.'.$o->column_name] = $o;
			}
		}
		
		self::$bindingsCache = $bindings;
	}
	
	/**
	 * Gets a bindings from the database
	 * @return array [string : [string : string]] 
	 */
	private static function &getBindings() {
		if (self::$bindingsCache === null) {
			self::loadBindingsFromDatabase();
		}
		return self::$bindingsCache;
	}
	
	/**
	 * Gets all bindings from database.
	 */
	private static function getBinding($table, $column) {
		$bindings = &self::getBindings();
		return @$bindings[$table.'.'.$column];
	}
	
	
	
	public function getTriggerName($type = 'INSERT', $reverse = false) {
		$key = ($reverse ? '-':'+').$this->table->tablename.'#'.$this->fieldName.'?'.$type;
		return 'xfbinding__'.md5($key);
	}
	
	private static function createBindingsTable() {
		$sql = "CREATE TABLE IF NOT EXISTS `".self::BINDINGS_TABLE."` (".
		
			"`table_name` VARCHAR(64) NOT NULL COMMENT 'Table name of bound field',
			`column_name` VARCHAR(64) NOT NULL COMMENT 'Bound column name',
			`insert_trigger_name` VARCHAR(64) NOT NULL COMMENT 'ON INSERT trigger name',
			`update_trigger_name` VARCHAR(64) NOT NULL COMMENT 'ON UPDATE trigger name',
			`delete_trigger_name` VARCHAR(64) NOT NULL COMMENT 'ON DELETE trigger name',
			`insert_trigger_name_reverse` VARCHAR(64) NOT NULL,
			`update_trigger_name_reverse` VARCHAR(64) NOT NULL,
			`delete_trigger_name_reverse` VARCHAR(64) NOT NULL,
			`binding` TEXT NOT NULL,
			`binding_delete` TEXT,
			`binding_insert` TEXT,
			PRIMARY KEY (`table_name`, `column_name`)
			)";
			
		df_q($sql);
	}
	
	/**
	 * Gets the column name specified by the given variable
	 * wrapped in backticks.  If the variable doesn't define 
	 * a column name, then it returns null.
	 *
	 */
	private function columnName($var) {
		$var = $this->formatVar($var, '::self::');
		if (strpos($var, '::self::') === 0) {
			return substr($var, strpos($var, '.')+1);
		}
		return null;
	}
	
	/**
	 * Formats a variable for inclusion in SQL query according to type.
	 * Variable format is $value:type. 
	 * 
	 * Examples:
	 *
	 * $this->formatVar('$foo', 'NEW')  -> NEW.`foo`
	 * $this->formatVar('$0:int', 'NEW') -> 0
	 * $this->formatVar('foo') -> 'foo'
	 * $this->formatVar('$foo:string', 'NEW') -> 'foo'
	 * $this->formatVar('$1.2:float', 'NEW') -> 1.2
	 * $this->formatVar('$:NULL', 'NEW') => NULL
	 */
	private function formatVar($v, $self) {
		if ($v and $v{0} == '$') {
			$pos = strpos($v, ':');
			if ($pos === false) {
				return $self.'.`'.substr($v, 1).'`';
			} else {
				$type = substr($v, $pos+1);
				if (strcasecmp($type, 'NULL') === 0) {
					return 'NULL';
				} else if (strcasecmp($type, 'string')) {
					return "'".addslashes(substr($v, 1, $pos-1))."'";
				} else if (strcasecmp($type, 'int')) {
				
					return intval(substr($v, 1, $pos-1));
				} else if (strcasecmp($type, 'float')) {
					return floatval(substr($v, 1, $pos-1));
				} else { // it's an expression
					return "'".addslashes(substr($v, 1, $pos-1))."'";
				}
			}
		} else {
			return "'".addslashes($v)."'";
		}
	}
	
	/**
	 * Parses the binding into an array with keys:
	 *
	 * 'table' => string The target table of the binding
	 * 'field' => string The target field of the binding
	 * 'where' => string Where clause for the target table (e.g. id=10 AND name=NEW.`name`
	 * 'insert' => string Insert SQL query for target table in case bound record doesn't exist yet.
	 * 'delete' => string Delete SQL query for target table in case source record is deleted.
	 */
	private function parseBinding($self = 'NEW', $revSelf = 'NEW') {
		$field = $this->table->getField($this->fieldName);
		$binding = @$field['binding'];
		if (!$binding) {
			throw new \Exception("Field has no binding defined");
		}
		$parts = parse_url('http://example.com/'.$binding);
		$table = substr($parts['path'], 1);
		$fieldName = $parts['fragment'];
		parse_str($parts['query'], $query);
		$where = '';
		$first = true;
		foreach ($query as $k=>$v) {
			if ($first) {
				$first = false;
			} else {
				$where .= ' AND ';
			}
			
			
			$where .= '`'.$k.'`<=>'.$this->formatVar($v, $self);
		}
		
		$reverse_where = '';
		$first = true;
		foreach ($query as $k=>$v) {
			if ($first) {
				$first = false;
			} else {
				$reverse_where .= ' AND ';
			}
			
			$colName = $this->columnName($v);
			if ($colName) {
				$reverse_where .= '`'.$colName.'`<=>`'.$revSelf.'`.`'.$k.'`';
			}
			
		}
		if (!$reverse_where) {
			$reverse_where = '1';
		}
		
		$insert = 'SET @dummy=1;';
		
		if (@$field['binding.insert']) {
			$insertData = array();
			if ($field['binding.insert'] == '1' or $field['binding.insert'] == 'true') {
				foreach ($query as $k=>$v) {
					$insertData[$k] = $v;
				}
			} else if ($insertData{0} == '{') {
				$insertData = json_decode($field['binding.insert'], true);
				foreach ($query as $k=>$v) {
					$insertData[$k] = $v;
				}
				
			}
			$insertData[$fieldName] = '$'.$this->fieldName;
			$colnames = '';
			$colvals = '';
			$first = true;
			foreach ($insertData as $k=>$v) {
				if ($first) {
					$first = false;
				} else {
					$colnames .= ', ';
					$colvals .= ', ';
				}
				$colnames .= '`'.$k.'`';
				$colvals .= $this->formatVar($v, $self);
			}
			$insert = 'INSERT INTO `'.$table.'` ('.$colnames.') VALUES ('.$colvals.');';
		}
		
		$delete = 'SET @dummy=1;';
		if (@$field['binding.delete'] and strcasecmp($field['binding.delete'], 'CASCADE') === 0) {
			// When this record is deleted, it should delete the bound record
			$delete = 'DELETE FROM `'.$table.'` WHERE $where LIMIT 1;';
		}
		if (@$field['binding.delete'] and strcasecmp($field['binding.delete'], 'SET NULL') === 0) {
			// When this record is deleted, it should delete the bound record
			$delete = 'UPDATE `'.$table.'` SET `{$fieldName}` = NULL WHERE $where LIMIT 1;';
		}
		
		return array(
			'table' => $table,
			'field' => $fieldName,
			'where' => $where,
			'reverse_where' => $reverse_where,
			'insert' => $insert,
			'delete' => $delete
		);
		
	}
	
	private function createTriggers() {
		$this->createTrigger('UPDATE');
		$this->createTrigger('INSERT');
		$this->createTrigger('DELETE');
		$this->createReverseTrigger('UPDATE');
		$this->createReverseTrigger('INSERT');
		$this->createReverseTrigger('DELETE');
	}
	
	
	/**
	 * Checks if the binding triggers require update.
	 */
	private function requiresUpdate() {
		$row = self::getBinding($this->table->tablename, $this->fieldName);
		
		$field =& $this->table->getField($this->fieldName);
		if ((@$field['binding'] and !$row) or (!$field['binding'] and $row)) {
			return true;
		} else if (!@$field['binding'] and !$row) {
			return false;
		} else {
			// Binding exists in conf and db.
			// Check that values haven't changed.
			return $row->binding == $field['binding'] and $row->binding_delete == @$field['binding.delete'] and $row->binding_insert == @$field['binding.insert'];
			
		}
	}
	
	/**
	 * Synchronizes the binding triggers with the file system.  This will first
	 * try optimistically to update the database.  If it fails, it will try to 
	 * create the bindings table, then try to update them again.
	 *
	 */
	public function synchronizeWithFileSystem() {
		try {
			if ($this->requiresUpdate()) {
				$this->update();
			}
		} catch (\Exception $ex) {
			$this->createBindingsTable();
			$this->update();
		}
		
	}
	
	private static function deleteBinding($table, $column) {
		if (self::$bindingsCache !== null and isset(self::$bindingsCache[$table.'.'.$column])) {
			unset(self::$bindingsCache[$table.'.'.$column]);
		}
		$db = new Database(df_db());
		$db->deleteObject($this->table->tablename, array(
			'table_name' => $table,
			'column_name' => $column
		));
	}
	
	
	
	/**
	 * Updates the triggers to match the settings in the fields.ini file.
	 */
	private function update() {
		$db = new Database(df_db());
		$row = self::getBinding($this->table->tablename, $this->fieldName);
		$field =& $this->table->getField($this->fieldName);
		if ($row and @$field['binding']) {
			
			$this->dropTriggers();
			$row->binding = @$field['binding'];
			$row->binding_insert = @$field['binding_insert'];
			$row->binding_delete = @$field['binding_delete'];
			$db->updateObject(self::BINDINGS_TABLE, $row, array(
				'table_name' => $this->table->tablename,
				'column_name' => $this->fieldName
			));
			$this->createTriggers();
		} else if ($row and !@$field['binding']) {
			$this->dropTriggers();
			self::deleteBinding($this->table->tablename, $row);
		} else if (!$row and @$field['binding']) {
			
			self::$bindingsCache[$this->table->tablename.'.'.$this->fieldName] = (object)array(
				'table_name' => $this->table->tablename,
				'column_name' => $this->fieldName,
				'binding' => $field['binding'],
				'binding_insert' => @$field['binding.insert'],
				'binding_delete' => @$field['binding.delete'],
				'insert_trigger_name' => $this->getTriggerName('INSERT', false),
				'update_trigger_name' => $this->getTriggerName('UPDATE', false),
				'delete_trigger_name' => $this->getTriggerName('DELETE', false),
				'insert_trigger_name_reverse' => $this->getTriggerName('INSERT', true),
				'update_trigger_name_reverse' => $this->getTriggerName('UPDATE', true),
				'delete_trigger_name_reverse' => $this->getTriggerName('DELETE', true)
			);
			$db->insertObject($this->table->tablename, self::$bindingsCache[$this->table->tablename.'.'.$this->fieldName]);
			$this->dropTriggers();
			$this->createTriggers();
		}
	}
	
	private function dropTriggers() {
		foreach (array('UPDATE', 'INSERT', 'DELETE') as $type) {
			foreach (array(false, true) as $reverse) {
				$sql = 'DROP TRIGGER IF EXISTS `'.$this->getTriggerName($type, $reverse).'`;';
				echo "$sql <br>";
				$res = xf_db_query($sql, df_db());
				if (!$res) {
					throw new \Exception("Failed to drop trigger {$this->getTriggerName($type, $reverse)}. Error: ".xf_db_error(df_db()));
				}
			}
			
		}
		//echo "Triggers dropped";exit;
		
	}
	
	private function createReverseTrigger($type) {
		if ($type == 'UPDATE') {
			$bvars = $this->parseBinding('NEW', 'NEW');
		
			$sql = <<<END
				CREATE TRIGGER `{$this->getTriggerName($type, true)}` AFTER UPDATE ON `{$bvars['table']}`
				FOR EACH ROW
				BEGIN
					IF NOT(NEW.`{$bvars['field']}` <=> OLD.`{$bvars['field']}`) THEN
						IF EXISTS(SELECT `{$this->fieldName}` FROM `{$this->table->tablename}` WHERE {$bvars['reverse_where']} AND NOT(`{$this->fieldName}` <=> NEW.`{$bvars['field']}`) ) THEN
							UPDATE `{$this->table->tablename}` SET `{$this->fieldName}` = NEW.`{$bvars['field']}` WHERE {$bvars['reverse_where']};
						END IF;
					END IF;
					
				END	;
END;
		} else if ($type == 'INSERT') {
			$bvars = $this->parseBinding('NEW', 'NEW');
		
			$sql = <<<END
				CREATE TRIGGER `{$this->getTriggerName($type, true)}` AFTER INSERT ON `{$bvars['table']}`
				FOR EACH ROW
				BEGIN
					IF EXISTS(SELECT `{$this->fieldName}` FROM `{$this->table->tablename}` WHERE {$bvars['reverse_where']} AND NOT(`{$this->fieldName}` <=> NEW.`{$bvars['field']}`)) THEN
						UPDATE `{$this->table->tablename}` SET `{$this->fieldName}` = NEW.`{$bvars['field']}` WHERE {$bvars['reverse_where']};
					END IF;
				END	;
END;
		} else if ($type == 'DELETE') {
			$bvars = $this->parseBinding('OLD');
			$sql = <<<END
				CREATE TRIGGER `{$this->getTriggerName($type, true)}` AFTER DELETE ON `{$bvars['table']}`
				FOR EACH ROW
				BEGIN
					IF EXISTS(SELECT `{$this->fieldName}` FROM `{$this->table->tablename}` WHERE {$bvars['reverse_where']} AND `{$this->fieldName}` <=> OLD.`{$bvars['field']}`) THEN
						UPDATE `{$this->table->tablename}` SET `{$this->fieldName}` = NULL WHERE {$bvars['reverse_where']};
					END IF;
				END;	
END;
		} else {
			throw new \Exception("Unsupported trigger type $type");
		}
		
		
		$res = xf_db_query($sql, df_db());
		if (!$res) {
			error_log('Failed to create trigger.  '.xf_db_error(df_db()).' SQL: '.$sql);
			throw new \Exception("Failed to create trigger of type ".$type.".");
		}
	}
	
	private function createTrigger($type) {
	
		if ($type == 'UPDATE') {
			$bvars = $this->parseBinding('NEW');
		
			$sql = <<<END
				CREATE TRIGGER `{$this->getTriggerName($type, false)}` AFTER UPDATE ON `{$this->table->tablename}`
				FOR EACH ROW
				BEGIN
					IF NOT(NEW.`{$this->fieldName}` <=> OLD.`{$this->fieldName}`) THEN
						IF EXISTS(SELECT `{$bvars['field']}` FROM `{$bvars['table']}` WHERE {$bvars['where']}) THEN
							IF EXISTS(SELECT `{$bvars['field']}` FROM `{$bvars['table']}` WHERE {$bvars['where']} AND NOT(`{$bvars['field']}` <=> NEW.`{$this->fieldName}`)) THEN
								UPDATE `{$bvars['table']}` SET `{$bvars['field']}` = NEW.`{$this->fieldName}` WHERE {$bvars['where']};
							END IF;
						ELSEIF NOT(NEW.`{$this->fieldName}`<=> NULL) THEN
							{$bvars['insert']}
						END IF;
					END IF;
					
				END	;
END;
		} else if ($type == 'INSERT') {
			$bvars = $this->parseBinding('NEW');
		
			$sql = <<<END
				CREATE TRIGGER `{$this->getTriggerName($type, false)}` AFTER INSERT ON `{$this->table->tablename}`
				FOR EACH ROW
				BEGIN
					IF EXISTS(SELECT `{$bvars['field']}` FROM `{$bvars['table']}` WHERE {$bvars['where']}) THEN
						IF EXISTS(SELECT `{$bvars['field']}` FROM `{$bvars['table']}` WHERE {$bvars['where']} AND NOT(`{$bvars['field']}` <=> NEW.`{$this->fieldName}`)) THEN
							UPDATE `{$bvars['table']}` SET `{$bvars['field']}` = NEW.`{$this->fieldName}` WHERE {$bvars['where']};
						END IF;
					ELSEIF NOT(NEW.`{$this->fieldName}` <=> NULL) THEN
						{$bvars['insert']}

					END IF;
				END	;
END;
		}  else if ($type == 'DELETE') {
			$bvars = $this->parseBinding('OLD');
			$sql = <<<END
				CREATE TRIGGER `{$this->getTriggerName($type, false)}` AFTER DELETE ON `{$this->table->tablename}`
				FOR EACH ROW
				BEGIN
					IF EXISTS(SELECT `{$bvars['field']}` FROM `{$bvars['table']}` WHERE {$bvars['where']}) THEN
						{$bvars['delete']}
					END IF;
				END;
END;
		} else {
			throw new \Exception("Unsupported trigger type $type");
		}
		
		echo "Creating trigger $sql <br>";
		$res = xf_db_query($sql, df_db());
		if (!$res) {
			error_log('Failed to create trigger.  '.xf_db_error(df_db()).' SQL: '.$sql);
			throw new \Exception("Failed to create trigger of type ".$type.".");
		}
		
	}
	
	/**
	 * Updates all bindings in the application.  This loops through all tables, and
	 * all fields to update the bindings for the fields.
	 *
	 */
	public static function updateAllBindings(\Dataface_Table $table = null) {
		if (!isset($table)) {
			foreach (array_keys(\Dataface_Table::getTableModificationTimes()) as $tableName) {
				$ts = \Dataface_Table::loadTable($tableName, null, true);
				$t =& $ts[$tableName];
				if ($t instanceof \Dataface_Table) {
					echo "Updating bindings for table $tableName<br/>";
					self::updateAllBindings($t);
				}
				unset($t);
				unset($ts[$tableName]);
				gc_collect_cycles();
			}
			return;
		}
		foreach (array_keys($table->fields()) as $fieldName) {
			$field =& $table->getField($fieldName);
			$dbBinding = self::getBinding($table->tablename, $fieldName);
			if (@$field['binding'] or @$dbBinding) {
				$binding = new Binding($table, $fieldName);
				$binding->synchronizeWithFileSystem();
			}
			unset($field);
		}
		
		
	}
	
}