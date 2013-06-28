<?php
import('Dataface/AuthenticationTool.php');
/**
 * <p>Manages the history of records.  This class will record the state of each record
 * in the database by copying it to a 'history' table.  A separate history table
 * is created for each table in the database.</p>
 * <p>In addition to storing all of the information in the subject table (the 
 * table that is being backed up), the history table stores the language of the
 * record, the username of the user performing the update, some optional state 
 * information, a comments field and a modified timestamp to track when the record is from.</p>
 *
 * <p>This class can be used to restore records to a previous state.</p>
 *
 * <p>Usage:
 *
 * <code>
 *  $ht = new Dataface_HistoryTool();
 *  $record = df_get_record('Profiles', array('id'=>10)); // get the profile with ID 10.
 *  $ht->restoreToDate($record, '2004-5-10');
 *       // restores the record to the way it was on 2004-5-10
 *  
 *  </code>
 *
 *  </p>
 *
 * <p>History tables are created to follow the following naming convention.
 *  The history table for the table named 'Foo' would be called 'Foo__history'.
 *  If Foo contains a container field whose files are saved in the folder /path/to/foo
 *  then their history files (the backups for the history table) will be stored
 *  in /path/to/foo/.dataface_history/  with the file name the same as the history
 * id (The value of the history__id column for that entry.</p>
 *
 * <p>History is automatically stored if the [history] section is present in
 *  the conf.ini file.  If it is not, you can manually record the history
 * for a record using the logRecord method as follows:</p>
 * <code>
 * $ht = new Dataface_HistoryTool();
 * $hid = $ht->logRecord($record);
 *     // $hid is the id of the history record.
 *
 * $historyRecord = $ht->getRecordById($record->_table->tablename, $hid);
 *		// obtains the Dataface_Record for the corresponding record of the history
 *		// table.
 *      // $historyRecord and $record should contain nearly identical values, 
 *		// except that $historyRecord contains extra history information
 *		// like timestamps etc.
 * </code>
 *
 * <p>You can obtain a previous version of a record without actually restoring
 *  the database record to that version using the getPreviousVersion() method:</p>
 * <code>
 *	$historyTool = new Dataface_HistoryTool();
 *  $record = df_get_record('Profiles', array('id'=>10));
 *  $old_record = $ht->getPreviousVersion($record, '2004-10-10');
 *  // Now $old_record is a Dataface_Record object for the "Profiles" table
 *  //representing the status of $record on 2004-10-10.
 * </code>
 *
 *
 *
 * @author Steve Hannah (shannah@sfu.ca)
 * @created September 2006
 */
class Dataface_HistoryTool {

	/**
	 * The fields that should appear in every history table.
	 */
	var $meta_fields = array(
	        'history__id'=>array('Type'=>'int(11)', 'Extra'=>'auto_increment'),
			'history__language'=> array('Type'=>'varchar(2)'),
			'history__comments'=> array('Type'=>'text'),
			'history__user'=>array('Type'=>'varchar(32)'),
			'history__state'=>array('Type'=>'int(5)'),
			'history__modified'=>array('Type'=>'datetime')
			);
			
	
	
	/**
	 * Backs up a record to the history table. This will automatically happen
	 * when using Dataface_IO::save() if the [history] section exists in the 
	 * conf.ini file.
	 *
	 * @param Dataface_Record &$record The record that is being backed up.
	 * @param string $comments  Comments about this version to be stored.
	 * @param string $lang The 2-digit language code of which language
	 * 				 to use to back up this record.  If none is specified
	 *				 then the current language of the system will be used.
	 * @param integer $state Unused as yet.  Was intended to store state/workflow
	 *				 information.. but ..
	 * @returns integer The history id of the resulting history record.
	 */
	function logRecord(&$record, $comments='', $lang=null, $state=null){
		$app =& Dataface_Application::getInstance();
		
		if ( !isset($lang) ){
			$lang = $app->_conf['lang'];
		}
		
		if ( !isset($state) ){
			$state = 0;
		}
		
		
		$fieldnames = array_keys($record->_table->fields());
		$sql = 'select `'.implode('`,`', $fieldnames).'` from `'.$record->_table->tablename.'` where';
		$keynames = array_keys($record->_table->keys());
		$where_clauses = array();
		foreach ( $keynames as $keyname){
			$where_clauses[] = '`'.$keyname.'`=\''.addslashes($record->strval($keyname)).'\'';
		}
		$sql .= ' '.implode(' and ', $where_clauses);
		
		if ( @$app->_conf['multilingual_content'] ){
			$db =& Dataface_DB::getInstance();
			$sql = $db->translate_query($sql, $lang);
			$sql = $sql[0];
		}
		
		$auth =& Dataface_AuthenticationTool::getInstance();
		$userRecord =& $auth->getLoggedInUser();
		if ( !isset($userRecord) ){
			$user = null;
		} else {
			$user = $auth->getLoggedInUsername();
		}
		
		
		$insertsql = "insert into `".$this->logTableName($record->_table->tablename)."` 
			(`".implode('`,`', $fieldnames)."`, `history__language`,`history__comments`,`history__user`,`history__state`,`history__modified`) 
			select *, '".addslashes($lang)."','".addslashes($comments)."','".addslashes($user)."','".addslashes($state)."', NOW() 
			from (".$sql.") as t";
		
		$res = mysql_query($insertsql, $app->db());
		if ( !$res ){
			$this->updateHistoryTable($record->_table->tablename);
			$res = mysql_query($insertsql, $app->db());
		}
		if ( !$res ){
			echo $insertsql;
			trigger_error(mysql_error($app->db()), E_USER_ERROR);
		}
		
		// Now for the individual fields
		$hid = mysql_insert_id($app->db());
		foreach ($fieldnames as $fieldname){
			$this->logField($record, $fieldname, $hid);
		}
		
		return $hid;
		
	
	}
	
	/**
	 * Performs extra logging steps for fields.  This method is called by logRecord().
	 * In particular for container fields, this will copy the container's files
	 * into the hidden .dataface_history subdirectory of the container field's 
	 * save path.
	 * @param Dataface_Record &$record The record whose field is being logged.
	 * @param string $fieldname The name of the field to perform extra steps for.
	 * @param integer $history_id The id of the history record for the record
	 *                that is being logged.
	 */
	function logField(&$record, $fieldname, $history_id){
		$field =& $record->_table->getField($fieldname);
		$s = DIRECTORY_SEPARATOR;
		switch(strtolower($field['Type'])){
			case 'container':
				$savepath = $field['savepath'];
				if ( $savepath{strlen($savepath)-1} != $s ) $savepath.=$s;
				if ( !$record->val($fieldname) ) break; // there is no file currently stored in this field.
				if ( !is_readable($savepath.$record->val($fieldname)) ) break; // the file does not exist
				if ( !file_exists($savepath) || !is_dir($savepath) )
					trigger_error(
						df_translate(
							'scripts.Dataface.HistoryTool.logField.ERROR_CONTAINER_FIELD_SAVEPATH_MISSING',
							"Field {$fieldname} is a Container field but its corresponding savepath {$savepath} does not exist.  Please create the directory {$savepath} and ensure that it is writable by the web server",
							array('fieldname'=>$fieldname,'savepath'=>$savepath)
							), E_USER_ERROR);
				$histpath = $savepath.'.dataface_history'.$s;
				if ( !file_exists($histpath) ){
					$res = mkdir($histpath, 0777);
					if ( !$res ) trigger_error(
						df_translate(
							'scripts.Dataface.HistoryTool.logField.ERROR_FAILED_TO_MAKE_HISTORY_FOLDER',
							"Failed to make history folder {$histpath} to store the history for container field {$fieldname} in table {$record->_table->tablename}.  It could be a permissions problem.  Please ensure that the {$savepath} directory is writable by the web server.",
							array('histpath'=>$histpath,'fieldname'=>$fieldname,'tablename'=>$record->_table->tablename,'savepath'=>$savepath)
							), E_USER_ERROR);
					
				}
				if ( !is_dir($histpath) ){
					trigger_error(
						df_translate(
							'scripts.Dataface.HistoryTool.logField.ERROR_NOT_A_DIRECTORY',
							"The history path for the field {$fieldname} in table {$record->_table->tablename} is not a directory.  Perhaps a file has been uploaded with the reserved name '.history'.  Please delete this file to allow Dataface's history feature to work properly.",
							array('fieldname'=>$fieldname, 'tablename'=>$record->_table->tablename)
							), E_USER_ERROR);
				}
				if ( !is_writable($histpath) ){
					trigger_error("The history folder for field {$fieldname} of table {$record->_table->tablename} is not writable by the web server.  Please make it writable by the web server for Dataface's history feature to work properly.", E_USER_ERROR);
				}
				

				$destpath = $histpath.$history_id;
				if ( file_exists($destpath) ) { return;}	// the file already exists... just skip it.

				$srcpath = $savepath.$record->val($fieldname);
				$res = copy($srcpath,$destpath);

				break;
				
				
				
		}
	}
	
	
	/**
	 * Given a table name - this returns the name of the table's history table.
	 *
	 * @param string $tablename The name of the table whose history we want.
	 * @return string
	 */
	function logTableName($tablename){
		return $tablename.'__history';
	}
	
	/**
	 * Updates the history table for a particular table so that it exists and
	 * contains all of the correct columns.
	 */
	function updateHistoryTable($tablename){
		$app =& Dataface_Application::getInstance();
		$name = $this->logTableName($tablename);
		
		//first check to see if the table exists
		if ( mysql_num_rows(mysql_query("show tables like '".$name."'", $app->db())) == 0 ){
			$this->createHistoryTable($tablename);
		}
		
		$res = mysql_query("show columns from `".$this->logTableName($tablename)."`", $app->db());
		if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		$history_fields = array();
		while ( $row = mysql_fetch_assoc($res) ){
			$history_fields[$row['Field']] = $row;
		}
		@mysql_free_result($res);
		
		$table =& Dataface_Table::loadTable($tablename);
		$fieldnames = array_keys($table->fields());
		
		foreach ($fieldnames as $fieldname){
			if ( !isset($history_fields[$fieldname]) ){
				$field =& $table->getField($fieldname);
				$type = (( strcasecmp($field['Type'],'container') === 0 ) ? 'varchar(64)' : $field['Type'] );
				$sql = "alter table `".$name."` add column `".$fieldname."` {$type} DEFAULT NULL";
				$res = mysql_query($sql, $app->db());
				if ( !$res ){
					trigger_error(mysql_error($app->db()), E_USER_ERROR);
				}
				unset($field);
			}
		}
		
		$meta_fields = $this->meta_fields;
			
		foreach ( array_keys($meta_fields) as $fieldname){
			if ( !isset($history_fields[$fieldname]) ){
				$sql = "alter table `".$name."` add column `".$fieldname."` ".$history_fields[$fieldname]['Type'].' '.@$history_fields[$fieldname]['Extra'];
				$res = mysql_query($sql, $app->db());
				if ( !$res ){
					trigger_error(mysql_error($app->db()), E_USER_ERROR);
				}
				
			}
		}
		
	}
	
	
	/**
	 * Creates a history table corresponding to the given table.
	 * @param string $tablename The name of the table whose history table is
	 * 		to be created.
	 */
	function createHistoryTable($tablename){
		$app =& Dataface_Application::getInstance();
		$sql = "create table `".$this->logTableName($tablename)."` (
			`history__id` int(11) auto_increment NOT NULL,
			`history__language` varchar(2) DEFAULT NULL,
			`history__comments` text default null,
			`history__user` varchar(32) default null,
			`history__state` int(5) default 0,
			`history__modified` datetime,";
		
		$table =& Dataface_Table::loadTable($tablename);
		$res = df_q("SHOW TABLE STATUS LIKE '".addslashes($tablename)."'");
		$status = mysql_fetch_assoc($res);
		$charset = substr($status['Collation'],0, strpos($status['Collation'],'_'));
		$collation = $status['Collation'];
		$fieldnames = array_keys($table->fields());
		$fielddefs = array();
		foreach ( $fieldnames as $fieldname){
			$field =& $table->getField($fieldname);
			$type = (( strcasecmp($field['Type'],'container') === 0 ) ? 'varchar(64)' : $field['Type'] );
			$fielddefs[] = "`".$fieldname."` ".$type;
			unset($field);
		}
		
		$sql .= implode(",\n",$fielddefs);
		$sql .= ",
			PRIMARY KEY (`history__id`),
			KEY prikeys using hash (`".implode('`,`', array_keys($table->keys()))."`),
			KEY datekeys using btree (`history__modified`)) ".(@$status['Engine'] ? "ENGINE=".$status['Engine']:'')." ".($charset ? "DEFAULT CHARSET=".$charset.($collation ? " COLLATE $collation":''):'');
		
		$res = mysql_query($sql, $app->db());
		if ( !$res ){
			trigger_error(mysql_error($app->db()), E_USER_ERROR);
		}
	}
	
	
	/**
	 * Gets an HTML diff output between the records at $id1 and $id2 
	 * respectively, where $id1 and $id2 are history ids from the history__id
	 * column of the history table.
	 * @param string $tablename The name of the base table.
	 * @param integer $id1 The id number of the first record (from the history__id column)
	 * @param integer $id2 The id of the second record (from the history__id column)
	 * @param string $fieldname Optional name of a field to return.
	 * @returns mixed Either the value of the specified field name if $fieldname is specified,
	 *			or a Dataface_Record object whose field values are formatted diffs.
	 */
	function getDiffs($tablename, $id1, $id2=null, $fieldname=null ){
		import('Text/Diff.php');
		import('Text/Diff/Renderer/inline.php');
		$htablename = $tablename.'__history';
		if ( !Dataface_Table::tableExists($htablename) )
			return PEAR::raiseError(
				df_translate('scripts.Dataface.HistoryTool.getDiffs.ERROR_HISTORY_TABLE_DOES_NOT_EXIST',
				"History table for '{$tablename}' does not exist, so we cannot obtain changes for records of that table.",
				array('tablename'=>$tablename)
				), DATAFACE_E_ERROR);
		
		$rec1 = df_get_record($htablename, array('history__id'=>$id1));
		
		if ( !isset($id2) ){
			// The 2nd id wasn't provided so we assume we want to know the diffs 
			// against the current state of the record.
			$table =& Dataface_Table::loadTable($tablename);
			$query = $rec1->strvals(array_keys($table->keys()));
			$io = new Dataface_IO($tablename);
			$io->lang = $rec1->val('history__language');
			$rec2 = new Dataface_Record($tablename, array());
			$io->read($query, $rec2);
		} else {
			$rec2 = df_get_record($htablename, array('history__id'=>$id2));
		}
		
		$vals1 = $rec1->strvals();
		$vals2 = $rec2->strvals();
		
		$vals_diff = array();
		$renderer = new Text_Diff_Renderer_inline();
		foreach ($vals2 as $key=>$val ){
			$diff = new Text_Diff(explode("\n", @$vals1[$key]), explode("\n", $val));
			
			$vals_diff[$key] = $renderer->render($diff);
		}
		
		$diff_rec = new Dataface_Record($htablename, $vals_diff);
		if ( isset($fieldname) ) return $diff_rec->val($fieldname);
		return $diff_rec;
		
		
	}
	
	
	/**
	 * Gets the changes for a given record between dates.
	 * @param Dataface_Record &$record The record we are checking for changes on.  This is
	 *			a record of the base table, and not its history table.
	 * @param string $date1 The start date as an SQL date string (e.g. YYYY-MM-DD)
	 * @param string $date2 The end date as an SQL date string (e.g. YYYY-MM-DD)
	 * @param string $lang The 2-digit language code of the language we are using.
	 * @param string $fieldname Optional fieldname
	 * @returns mixed Either a Dataface_Record object from the history table, whose
	 *			values are the formatted diffs - or the formatted diff for the specified
	 *			$fieldname.
	 */
	function getDiffsByDate(&$record, $date1, $date2=null, $lang=null, $fieldname=null){
		if ( !isset($date2) ) $date2 = date('Y-m-d H:i:s');
		$time1 = strtotime($date1);
		$time2 = strtotime($date2);
		if ( $time1 > $time2 ){
			$temp = $date2;
			$date2 = $date1;
			$date1 = $temp;
		}
		$app =& Dataface_Application::getInstance();
		if ( !isset($lang) )  $lang = $app->_conf['lang'];
		$htablename = $record->_table->tablename.'__history';
		if ( !Dataface_Table::tableExists($htablename) ) 
			return PEAR::raiseError(
				df_translate('scripts.Dataface.HistoryTool.getDiffs.ERROR_HISTORY_TABLE_DOES_NOT_EXIST',
				"History table for '{$tablename}' does not exist, so we cannot obtain changes for records of that table.",
				array('tablename'=>$tablename)
				), DATAFACE_E_ERROR);
		$clauses = array();
		$keyvals = $record->strvals(array_keys($record->_table->keys()));
		foreach ($keyvals as $key=>$val){
			$clauses[] = "`{$key}`='".addslashes($val)."'";
		}
		$clauses[] = "`history__language`='".addslashes($lang)."'";
		
		$sql = "select `history__id` from `{$htablename}` where ".implode(' and ',$clauses);
		$sql1 = $sql . " and `history__modified` <= '".addslashes($date1)."' order by `history__modified` desc limit 1";
		$sql2 = $sql . " and `history__modified` <= '".addslashes($date2)."' order by `history__modified` desc limit 1";
		
		$res2 = mysql_query($sql2, $app->db());
		if ( !$res2 ){
			//echo $sql2;
			trigger_error(mysql_error($app->db()), E_USER_ERROR);
		}
		if ( mysql_num_rows($res2) == 0 ){
			if (isset($fieldname) ) return '';
			else return new Dataface_Record($htablename, array());
		} 
		list($id2) = mysql_fetch_row($res2);
		@mysql_free_result($res2);
		
		$res1 = mysql_query($sql1, $app->db());
		if ( !$res1 ){
			//echo $sql1;
			trigger_error(mysql_error($app->db()), E_USER_ERROR);
		}
		if ( mysql_num_rows($res1) == 0 ){
			$rec = df_get_record($htablename, array('history__id'=>$id2));
			if ( !isset($rec) ) 
				trigger_error(
					df_translate(
						'scripts.Dataface.HistoryTool.getDiffsByDate.ERROR_FAILED_TO_LOAD_HISTORY_RECORD',
						"Failed to load history record with id {$id2}",
						array('id'=>$id2)
						), DATAFACE_E_ERROR);
			if ( isset($fieldname) ) return $rec->val($fieldname);
			return $rec;
		}
		list($id1) = mysql_fetch_row($res1);
		@mysql_free_result($res1);
		$out = $this->getDiffs($record->_table->tablename, $id1, $id2, $fieldname);
		return $out;
		
	}
	
	
	/**
	 * Restores an old version of the given record, from the history table.
	 */
	function restore(&$record, $id, $fieldname=null, $secure=false){
		$app =& Dataface_Application::getInstance();
		if ( isset($fieldname) ) $fieldnames = array($fieldname);
		else $fieldnames = array_keys($record->_table->fields());
		if ( $secure ){
			$tmp = array();
			foreach ($fieldnames as $k=>$f){
				$fld = $record->table()->getField($f);
				if ( @$fld['encryption'] ) continue;
				if ( $record->checkPermission('edit', array('field'=>$f)) ){
					$tmp[] = $f;
				}
			}
			$fieldnames = $tmp;
		}
		$htablename = $record->_table->tablename.'__history';
		$res = mysql_query("select `".implode('`,`', $fieldnames)."`,`history__language` from `{$htablename}` where `history__id`='".addslashes($id)."'", $app->db());
		if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		if ( mysql_num_rows($res) == 0 ) 
			return PEAR::raiseError(
				df_translate(
					'scripts.Dataface.HistoryTool.restore.ERROR_NO_SUCH_RECORD',
					"Could not restore record with id {$id} in table {$htablename} because no such record exists.  Perhaps the history was cleaned out.",
					array('id'=>$id, 'tablename'=>$htablename)
					), DATAFACE_E_ERROR);
		$vals = mysql_fetch_assoc($res);
		@mysql_free_result($res);
		$old_record = new Dataface_Record($record->_table->tablename, $record->getValues());
		$lang = $vals['history__language'];
		unset($vals['history__language']);
		if ( isset($fieldname) ){
			$record->setValue($fieldname, $vals[$fieldname]);
		} else {
			$record->setValues($vals);
		}
		$record->save($lang, $secure);
		foreach ($fieldnames as $fld){
			$this->restoreField($record,$old_record, $id, $fld);
		}
		return true;
		
	}
	
	/**
	 * Performs extra steps to restore particular fields. Any steps outside of
	 * copying the field values form the history table back to the main table.
	 * In particular container fields need to have their files copied back
	 * from the history subdirectory into the container field's save path.
	 * @param Dataface_Record &$record The record whose field we are restoring.
	 * @param Dataface_Record &$old_record The record as is was before the restore.
	 * @param integer $id The History id (The value in the history__id column)
	 * @param string $fieldname The name of the field to restore.
	 */
	function restoreField(&$record, &$old_record, $id, $fieldname){
		$app =& Dataface_Application::getInstance();
		$htablename = $record->_table->tablename.'__history';
		$field =& $record->_table->getField($fieldname);
		switch (strtolower($field['Type'])){
			case 'container': 
				$savepath = $field['savepath'];
				if ( $savepath{strlen($savepath)-1} != '/' ) $savepath .= '/';
				
				if ( $old_record->val($fieldname) ){
					// we need to delete the existing file
					$filepath = $savepath.basename($old_record->val($fieldname));
					if ( file_exists($filepath) ) unlink($filepath);
				}
				
				$hsavepath = $savepath.'.dataface_history/'.$id;
				if ( !file_exists($hsavepath) || !is_readable($hsavepath) ) return false;
				$filename = basename($record->val($fieldname));
				$filepath = $savepath.'/'.$filename;
				while ( file_exists($filepath) ){
					$filepath = $savepath.'/'.strval(rand(0,10000)).'_'.$filename;
				}
				return copy($hsavepath, $filepath);
				
		}
		
	}
	
	/**
	 * Restores a record to a particular date.
	 * @param Dataface_Record &$record The record to be restored.
	 * @param string $date The date to restore to as a MySQL date string (e.g. YYYY-MM-DD HH:MM:SS)
	 * @param string $lang The 2-digit language code of the version to restore.
	 * @param string $fieldname If we are only restoring a single field we include the name here.
	 */
	function restoreToDate(&$record, $date, $lang=null, $fieldname=null){
		$app =& Dataface_Application::getInstance();
		$id = $this->getPreviousVersion($record, $date, $lang, $fieldname, true);
		return $this->restore($record, $id, $fieldname);
		/*
		$sql = "select * from `{$record->_table->_tablename}__history}` where `history__id` = '{$id}' limit 1";
		
		$res = mysql_query($sql, $app->db());
		if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		if ( mysql_num_rows($res) == 0 ){
			return PEAR::raiseError("Attempt to restore record \"{$record->getTitle()}\" to nonexistent history record with id '{$id}'", DATAFACE_E_ERROR);
		}
		$row = mysql_fetch_assoc($res);
		@mysql_free_result($res);
		$old_record = new Dataface_Record($record->_table->tablename, $record->getValues());
		$record->setValues($row);
		$res = $record->save($lang);
		foreach ($fieldnames as $fld){
			$this->restoreField($record, $old_record, $id, $fld);
		}
		
		return $res;
		*/
		
	
	}
	
	/**
	 * Returns a previous version of the given record.
	 * @param Dataface_Record &$record The record whose previous version we are interested in.
	 * @param string $date The MySQL date to obtain.  Returns the most recent record on or before this date. (e.g. YYYY-MM-DD HH:MM:SS)
	 * @param string $fieldname If we only want the previous version of a single field we can include it here.
	 * @param boolean $idonly  If this is set to true then only the history id of the appropriate record will be returned.
	 * @returns mixed If $idonly is true then an integer is returned corresponding to the value of the history__id field for the matching record.
	 *                If The $fieldname field was specified then the value of that field will be returned.
	 *                Otherwise a Dataface_Record will be returned.
	 */
	function getPreviousVersion(&$record, $date, $lang=null, $fieldname=null, $idonly=false){
		$app =& Dataface_Application::getInstance();
		if ( !isset($lang) )  $lang = $app->_conf['lang'];
		$htablename = $record->_table->tablename.'__history';
		if ( !Dataface_Table::tableExists($htablename) ) 
			return PEAR::raiseError(
				df_translate(
					'scripts.Dataface.HistoryTool.getDiffs.ERROR_HISTORY_TABLE_DOES_NOT_EXIST',
					"History table for '{$record->_table->tablename}' does not exist, so we cannot obtain changes for records of that table.",
					array('tablename'=>$record->_table->tablename)
					), DATAFACE_E_ERROR);
		$clauses = array();
		$keyvals = $record->strvals(array_keys($record->_table->keys()));
		foreach ($keyvals as $key=>$val){
			$clauses[] = "`{$key}`='".addslashes($val)."'";
		}
		$clauses[] = "`history__language`='".addslashes($lang)."'";
		
		$sql = "select `history__id` from `{$htablename}` where ".implode(' and ',$clauses)."
				and `history__modified` <= '".addslashes($date)."' order by `history__modified` desc limit 1";
		
		$res = mysql_query($sql, $app->db());
		if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		if ( mysql_num_rows($res) == 0 ){
			return null;
		} 
		list($id) = mysql_fetch_row($res);
		@mysql_free_result($res);
		if ( $idonly ) return $id;
		$out = $this->getRecordById($record->_table->tablename, $id);
		
		if ( isset($fieldname) ) return $out->val($fieldname);
		return $out;
		
		
	
	}
	
	/**
	 * Obtains a record from the history table given the value in the history__id column.
	 * @param string $tablename The name of the base table.
	 * @param integer $id The id (history__id column value).
	 * @returns Dataface_Record from the history table.
	 */
	function getRecordById($tablename, $id){
		$htablename = $tablename.'__history';
		if ( !Dataface_Table::tableExists($htablename) ) 
			return PEAR::raiseError(
				df_translate(
					'scripts.Dataface.HistoryTool.getDiffs.ERROR_HISTORY_TABLE_DOES_NOT_EXIST',
					"History table for '{$tablename}' does not exist, so we cannot obtain changes for records of that table.",
					array('tablename'=>$tablename)
					), DATAFACE_E_ERROR);
		
		$rec = df_get_record($htablename, array('history__id'=>$id));
		return $rec;
	}
	
	
	/**
	 * Returns an array of the meta fields from the history table in descending order
	 * of modified date.
	 * @param Dataface_Record &$record The record we wish to obtain history for.
	 * @param string $lang The 2-digit language code.
	 * @param integer $limit The maximum number of records to return.  null for unlimited.
	 * @returns Array of Associative arrays.
	 */
	function getHistoryLog(&$record, $lang=null, $limit=100){
		$app =& Dataface_Application::getInstance();
		$history_tablename = $record->_table->tablename.'__history';
		if ( !Dataface_Table::tableExists($history_tablename) ) return array();
		$keys = $record->strvals(array_keys($record->_table->keys()));
		$clauses = array();
		foreach ( $keys as $key=>$val){
			$clauses[] = "`{$key}`='".addslashes($val)."'";
		}
		if ( isset($lang) ) $clauses[] = "`history__language`  = '".addslashes($lang)."'";
		$where = implode(' and ', $clauses);
		if ( isset($limit) ) $limit = "LIMIT $limit";
		else $limit = '';
		
		$sql = "select `".implode('`,`', array_keys($this->meta_fields))."` from `{$history_tablename}` where {$where} order by `history__modified` desc {$limit}";
		//echo $sql;
		$res = mysql_query($sql, $app->db());
		if ( !$res ) trigger_error(mysql_error($app->db()), E_USER_ERROR);
		$out = array();
		while ( $row = mysql_fetch_assoc($res) ) $out[] = $row;
		@mysql_free_result($res);
		return $out;
		
		
		
		
	}
	
	
	/**
	 * Returns an array of history ids of history records that match the 
	 * given query.
	 */
	function findMatchingSnapshots($record, $query, $idsOnly=true){
		$app =& Dataface_Application::getInstance();
		$htablename = $record->_table->tablename.'__history';
		if ( !Dataface_Table::tableExists($htablename) ) return array();
		$keys = $record->strvals(array_keys($record->_table->keys()));
		foreach ($keys as $key=>$val){
			$query[$key] = '='.$val;
		}
		if ( $idsOnly ){
			$qbuilder = new Dataface_QueryBuilder($htablename, $query);
			$sql = $qbuilder->select(array('history__id'), $query);
			$res = mysql_query($sql, df_db());
			$ids = array();
			while ( $row = mysql_fetch_row($res) ) $ids[] = $row[0];
			@mysql_free_result($res);
			return $ids;
		} else {
			return df_get_records_array($htablename, $query);
		}
		
	}
	
	
	
	

}
