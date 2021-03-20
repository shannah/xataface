<?php
namespace xf\db;
import(XFROOT.'xf/db/Database.php');

/**
 * A class to encapsulate a bindings between two columns across different tables.
 * When two fields are bound, it means that their values stay in sync even when one of
 * them changes.  If either field changes, their other field in the binding is 
 * automatically updated to match.
 *
 * == Implementation
 * 
 * Binding is implemented using triggers.  Each binding comprises 6 triggers.  3
 * triggers on each side of the binding for UPDATE, INSERT, and DELETE.  
 *
 * == Usage
 * 
 * Bindings are defined in the fields.ini file via the 'binding' property.  Only 
 * one side of the binding needs to be specified in the fields.ini file in order
 * to create all of the triggers.  The binding property should include the "address"
 * of the record and field that it is bound to.  Addresses have the form:
 *
 * {TABLE NAME}?{ROW ID}#{FIELD NAME}
 * where:
 * - {TABLE NAME} is the name of the target table
 * - {FIELD NAME} is the field name in the target table that it is bound to.
 * - {ROW ID} a URL-encoded query string of column-name/column-value pairs
 *   that identify the row of the target table that is bound.
 *
 * Examples:
 * 
 * [source,ini]
 * ----
 * [admin_email]
 * binding=properties?property_name=admin_email#property_value
 * ----
 *
 * . A binding to the "property_value" field of the "properties" table, 
 * . The "row" of the binding is the row with property_name = "admin_email".
 * . This example is atypical because it doesn't include any "join" fields.  It
 *        assumes that the "properties" table will have a unique row with 
 *		  property_name='admin_email'.  However, usually you'll need to define a join
 *		  field (i.e. that matches on values in the source record).
 * 
 * [source,ini]
 * ----
 * [admin_email]
 * binding=properties?userid=$userid&property_name=admin_email#property_value
 * ----
 * 
 * . Same as previous example, except that this binding includes a "join"
 *		  field: "userid".  This binds only on the row with userid matching the userid
 *		  of the source record, and where property_name is admin_email.
 *
 * === Syntax of Query String Values
 *
 * The query string (e.g. field1=value1&...&fieldn=valuen) values are treated as
 * strings by default.  If you use the `$` prefix, it will perform special handling
 * as follows:
 *
 * - `$foo` - Matches the value of the "foo" column in the source record.
 * - `$foo:string` - The string "foo"
 * - `$1:int` - The integer "1"
 * - `$1.5:float` - The float value "1.5"
 * - `$:NULL` - NULL
 * - `$NULL` - Value of column named "NULL" in the source record
 * - `NULL` - The string "NULL"
 *
 * == Edge Cases: Insert and Delete Rules
 *
 *  In the common case, where both the source record and target record already
 * exist, the behaviour of a binding is easy to understand:  When one value changes,
 * so does the other.  However, it isn't as obvious when one or the other record doesn't 
 * exist.  E.g. in the above examples with a binding between the admin_email field and 
 * the property_value field of the "properties" table, what should happen if we update 
 * admin_email field of the source record, but there is no corresponding row yet for
 * the binding in the "properties" table?  Should a row be created automatically?  If so,
 * what values should be inserted into the other columns of that row?
 *
 * Similarly, what happens in the reverse situation, where there *is* a row in the properties
 * table, that matches the binding, but no corresponding row in the source table?
 *
 * The default behavior of the binding is to "do nothing" in both of these cases.  I.e. operate
 * normally, as if there isn't a binding at all.  This is the safest thing to do since
 * choosing to insert a record at the other end of the binding may require additional
 * information - such as the values to insert into the other fields of the row.  The first 
 * (insertion in the source table), the behaviour can be overridden using the `binding.insert`
 * property.  
 *
 * Another edge case to consider is what happens when either the source or target record
 * of the binding is deleted?  The default behaviour is as follows:
 *
 * . If the destination record is deleted, then the source field of the binding is set
 * NULL.
 * . If the source record is deleted, then nothing happens to the destination record.
 *
 * In the second case (source record deletion), the behaviour can be changed using the
 * `binding.delete` property.
 *
 * === Auto-Insertion on Update (binding.insert)
 *
 * The default behaviour when the destination table has no row matching the binding,
 * is to "do nothing" when the source record is updated.  However, you can change this
 * behaviour so that a row is automatically inserted into the destination table by
 * adding the "binding.insert" property.  The basic usage is to simple set this to "1",
 * in which case it will automatically figure out what values to insert into the row based
 * on the query string in the binding.  It will use the values necessary for the binding
 * to be fulfilled.
 *
 * If you require more specialized handling on insert, then you can specify the
 * values to insert using JSON in the "binding.insert" property.  E.g.
 *
 * [source,ini]
 * ----
 * binding.insert="{'field1':'value1', ..., 'fieldn':'valuen'}"
 * ----
 *
 * NOTE: The values (value1...value2) can use the same "$" notation as the query string
 * if you want to insert data that depends of the values in the source record.
 *
 * === Deletion Rules
 *
 * If a record is deleted from the destination table, then the "source" field of the binding
 * will be set to NULL.
 *
 * If a record is deleted from the source table, then the "destination" field is left untouched
 * by default.  However, this can be changed using the "binding.delete" property.  The 
 * "binding.delete" property can take 2 possible values:
 *
 * . "CASCADE" - Results in the "source" row being deleted when the destination row is deleted.
 * . "SET NULL" - Results in the "source" fieidl being set to NULL when the destination row
 * is deleted.
 *
 * == Asymmetry of Bindings
 *
 * The insert and delete rules discussed above highlight the asymmetry of bindings.  While
 * a binding is certainly 2-way (changing either field will automatically update the
 * other to keep it in sync), the logic for propagating changes from the source field to the
 * destination field is different than the reverse.  Deleting the "source" record of the
 * binding will have no effect on the "destination" record, but deleting the "destination"
 * record will set the "source" field to NULL.  
 *
 * If the "destination" record of a binding doesn't exist when the "source" field is updated,
 * then the "binding.insert" property  allows you to automatically insert a row in the 
 * destination table to satisfy the binding.  No such property exists for the reverse 
 * situation.  E.g. If you update the "destination" field when no matching "source" record
 * exists, then the binding is just ignored.
 *
 * These differences follow from the rationale that bindings are NOT symmetric.  The 
 * binding only exists, if the "source" record exists.  If the source record does not
 * exist, then there is no binding.  However, if the "destination" record doesn't exist
 * the binding still exists (so long as the source record exists).  We may just need to 
 * answer the question of what the binding should *do* in such instances.
 *
 * Through this lense you should be able to predict how a binding will behave in edge 
 * cases.  Updating a destination record, when the source record doesn't exist results in
 * nothing special happening, because: !exists(sourceRecord) => !exists(binding).
 * Deleting a source record should also result in nothing special happening for the same
 * reason.
 *
 * Conversely if we update a source record, and there is no matching destination record
 */
 
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
		if ($v and $v[0] == '$') {
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
		
		$reverse_if = '';
		$first = true;
		foreach ($query as $k=>$v) {
			if ($first) {
				$first = false;
			} else {
				$where .= ' AND ';
			}
			
			$colName = $this->columnName($v);
			if (!$colName) {
				$reverse_if .= $revSelf.'.`'.$k.'`<=>'.$this->formatVar($v, $revSelf);
			}
			
		}
		if (!$reverse_if) {
			$reverse_if = '1';
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
				$reverse_where .= '`'.$colName.'`<=>'.$revSelf.'.`'.$k.'`';
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
			} else if ($insertData[0] == '{') {
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
			'reverse_if' => $reverse_if,
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
		$this->createBeforeInsertTrigger();
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
				//echo "$sql <br>";
				//$res = xf_db_query($sql, df_db());
				//if (!$res) {
				//	throw new \Exception("Failed to drop trigger {$this->getTriggerName($type, $reverse)}. Error: ".xf_db_error(df_db()));
				//}
			}
			
		}
		//echo "Triggers dropped";exit;
		
	}
	
	private function createReverseTrigger($type) {
		if ($type == 'UPDATE') {
			$bvars = $this->parseBinding('NEW', 'NEW');
		
			$sql = <<<END
				IF {$bvars['reverse_if']} AND NOT(NEW.`{$bvars['field']}` <=> OLD.`{$bvars['field']}`) THEN
					IF EXISTS(SELECT `{$this->fieldName}` FROM `{$this->table->tablename}` WHERE {$bvars['reverse_where']} AND NOT(`{$this->fieldName}` <=> NEW.`{$bvars['field']}`) ) THEN
						UPDATE `{$this->table->tablename}` SET `{$this->fieldName}` = NEW.`{$bvars['field']}` WHERE {$bvars['reverse_where']};
					END IF;
				END IF;
END;
			self::$triggers[$bvars['table'].'.after.update'][] = $sql;
		} else if ($type == 'INSERT') {
			$bvars = $this->parseBinding('NEW', 'NEW');
		
			$sql = <<<END
				IF {$bvars['reverse_if']} AND EXISTS(SELECT `{$this->fieldName}` FROM `{$this->table->tablename}` WHERE {$bvars['reverse_where']} AND NOT(`{$this->fieldName}` <=> NEW.`{$bvars['field']}`)) THEN
					UPDATE `{$this->table->tablename}` SET `{$this->fieldName}` = NEW.`{$bvars['field']}` WHERE {$bvars['reverse_where']};
				END IF;

END;
			self::$triggers[$bvars['table'].'.after.insert'][] = $sql;
		} else if ($type == 'DELETE') {
			$bvars = $this->parseBinding('OLD', 'OLD');
			$sql = <<<END
				IF {$bvars['reverse_if']} AND EXISTS(SELECT `{$this->fieldName}` FROM `{$this->table->tablename}` WHERE {$bvars['reverse_where']} AND `{$this->fieldName}` <=> OLD.`{$bvars['field']}`) THEN
					UPDATE `{$this->table->tablename}` SET `{$this->fieldName}` = NULL WHERE {$bvars['reverse_where']};
				END IF;
	
END;
			self::$triggers[$bvars['table'].'.after.delete'][] = $sql;
		} else {
			throw new \Exception("Unsupported trigger type $type");
		}
		
		
		//$res = xf_db_query($sql, df_db());
		//if (!$res) {
		//	error_log('Failed to create trigger.  '.xf_db_error(df_db()).' SQL: '.$sql);
		//	throw new \Exception("Failed to create trigger of type ".$type.".");
		//}
	}
	
	private function createBeforeInsertTrigger() {
		$bvars = $this->parseBinding('NEW');
		$field =& $this->table->getField($this->fieldName);
		self::$triggerDeclarations[$this->table->tablename.'.before.insert'][] = "DECLARE tmp_{$this->fieldName} {$field['Type']};\n";
		$sql = <<<END
			IF NEW.`{$this->fieldName}` <=> NULL THEN
				SELECT `{$bvars['field']}` INTO tmp_{$this->fieldName} FROM `{$bvars['table']}` WHERE {$bvars['where']};
				SET NEW.`{$this->fieldName}` = tmp_{$this->fieldName};
			END IF;
END;
		self::$triggers[$this->table->tablename.'.before.insert'][] = $sql;
	}
	
	private function createTrigger($type) {
	
		if ($type == 'UPDATE') {
			$bvars = $this->parseBinding('NEW');
			
			$sql = <<<END
				IF NOT(NEW.`{$this->fieldName}` <=> OLD.`{$this->fieldName}`) THEN
					IF EXISTS(SELECT `{$bvars['field']}` FROM `{$bvars['table']}` WHERE {$bvars['where']}) THEN
						IF EXISTS(SELECT `{$bvars['field']}` FROM `{$bvars['table']}` WHERE {$bvars['where']} AND NOT(`{$bvars['field']}` <=> NEW.`{$this->fieldName}`)) THEN
							UPDATE `{$bvars['table']}` SET `{$bvars['field']}` = NEW.`{$this->fieldName}` WHERE {$bvars['where']};
						END IF;
					ELSEIF NOT(NEW.`{$this->fieldName}`<=> NULL) THEN
						{$bvars['insert']}
					END IF;
				END IF;
END;
			self::$triggers[$this->table->tablename.'.after.update'][] = $sql;
		} else if ($type == 'INSERT') {
			$bvars = $this->parseBinding('NEW');
		
			$sql = <<<END
				IF EXISTS(SELECT `{$bvars['field']}` FROM `{$bvars['table']}` WHERE {$bvars['where']}) THEN
					IF EXISTS(SELECT `{$bvars['field']}` FROM `{$bvars['table']}` WHERE {$bvars['where']} AND NOT(`{$bvars['field']}` <=> NEW.`{$this->fieldName}`)) THEN
						UPDATE `{$bvars['table']}` SET `{$bvars['field']}` = NEW.`{$this->fieldName}` WHERE {$bvars['where']};
					END IF;
				ELSEIF NOT(NEW.`{$this->fieldName}` <=> NULL) THEN
					{$bvars['insert']}

				END IF;
END;
			self::$triggers[$this->table->tablename.'.after.insert'][] = $sql;
		}  else if ($type == 'DELETE') {
			$bvars = $this->parseBinding('OLD');
			$sql = <<<END
				IF EXISTS(SELECT `{$bvars['field']}` FROM `{$bvars['table']}` WHERE {$bvars['where']}) THEN
						{$bvars['delete']}
				END IF;
END;
			self::$triggers[$this->table->tablename.'.after.delete'][] = $sql;
		} else {
			throw new \Exception("Unsupported trigger type $type");
		}
		
		//echo "Creating trigger $sql <br>";
		//$res = xf_db_query($sql, df_db());
		//if (!$res) {
		//	error_log('Failed to create trigger.  '.xf_db_error(df_db()).' SQL: '.$sql);
		//	throw new \Exception("Failed to create trigger of type ".$type.".");
		//}
		
	}
	
	private static $triggers;
	private static $triggerDeclarations;
	
	/**
	 * Updates all bindings in the application.  This loops through all tables, and
	 * all fields to update the bindings for the fields.
	 *
	 */
	public static function updateAllBindings(\Dataface_Table $table = null) {
		
		if (!isset($table)) {
			self::$triggers = array();
			self::$triggerDeclarations = array();
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
			self::commitTriggers();
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
	
	private static function commitTriggers() {
		foreach (self::$triggers as $k=>$v) {
			$keyParts = explode('.', $k);
			$table = $keyParts[0];
			$when = strtoupper($keyParts[1]);
			$event = strtoupper($keyParts[2]);
			$triggerName = 'xf_bindings_'.md5($k);
			$queries = array();
			$queries[] = "DROP TRIGGER IF EXISTS `$triggerName`;";
			$sql = "CREATE TRIGGER `$triggerName` $when $event ON `$table`
				FOR EACH ROW
				BEGIN
				";
			if (@self::$triggerDeclarations[$k]) {
				foreach (self::$triggerDeclarations[$k] as $row) {
					$sql .= $row . "\n";
				}
			}
			foreach ($v as $row) {
				$sql .= $row . "\n";
			}
			$sql .= "END;";
			$queries[] = $sql;
			df_q($queries);
			/*
			foreach ($queries as $q) {
				$res = xf_db_query($q, df_db());
				if (!$res) {
					throw new \Exception("SQL error executing ".$q.". ".xf_db_error(df_db()));
				}
			}*/
		}
	}
	
}