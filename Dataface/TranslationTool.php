<?php
define('TRANSLATION_STATUS_UNTRANSLATED',8);
define('TRANSLATION_STATUS_UNKNOWN',0);
define('TRANSLATION_STATUS_SOURCE',1);
define('TRANSLATION_STATUS_MACHINE',2);
define('TRANSLATION_STATUS_UNVERIFIED', 3);
define('TRANSLATION_STATUS_APPROVED',4);
define('TRANSLATION_STATUS_NEEDS_UPDATE',5);
define('TRANSLATION_STATUS_NEEDS_UPDATE_MACHINE',6);
define('TRANSLATION_STATUS_NEEDS_UPDATE_UNVERIFIED',7);
define('TRANSLATION_STATUS_EXTERNAL', 9);
class Dataface_TranslationTool {
	
	/**
	 * A schema for the translations table.
	 */
	var $schema = array(
		'id'=>array('Field'=>'id','Type'=>'int(11)','Key'=>'PRI','Null'=>'NOT NULL','Extra'=>'auto_increment'),
		'record_id'=>array('Field'=>'record_id','Type'=>'varchar(126)','Key'=>'record_key','Null'=>'NOT NULL','Extra'=>''),
		'language'=>array('Field'=>'language','Type'=>'varchar(2)','Key'=>'record_key','Null'=>'NOT NULL','Extra'=>''),
		'table'=>array('Field'=>'table','Type'=>'varchar(128)','Key'=>'record_key', 'Null'=>'NOT NULL','Extra'=>''),
		'version'=>array('Field'=>'version','Type'=>'int(11)','Key'=>'','Null'=>'NOT NULL','Default'=>1,'Extra'=>''),
		'translation_status'=>array('Field'=>'translation_status','Type'=>'int(11)','Key'=>'','Null'=>'NOT NULL','Default'=>0,'Extra'=>''),
		'last_modified'=>array('Field'=>'last_modified','Type'=>'datetime','Key'=>'','Null'=>'','Default'=>'0000-00-00','Extra'=>'')
		);
		
	var $submission_schema = array(
		'id'=>array('Field'=>'id','Type'=>'int(11)','Key'=>'PRI','Null'=>'NOT NULL','Extra'=>'auto_increment'),
		'record_id'=>array('Field'=>'record_id','Type'=>'varchar(126)','Key'=>'record_key','Null'=>'NOT NULL','Extra'=>''),
		'language'=>array('Field'=>'language','Type'=>'varchar(2)','Key'=>'record_key','Null'=>'NOT NULL','Extra'=>''),
		'url'=>array('Field'=>'url','Type'=>'text','Key'=>'','Null'=>'NOT NULL','Default'=>'','Extra'=>''),
		'original_text'=>array('Field'=>'original_text','Type'=>'text','Key'=>'','Null'=>'NOT NULL','Default'=>'','Extra'=>''),
		'translated_text'=>array('Field'=>'translated_text','Type'=>'text','Key'=>'','Null'=>'NOT NULL','Default'=>'','Extra'=>''),
		'translated_by'=>array('Field'=>'translated_by','Type'=>'varchar(128)','Key'=>'','Null'=>'NOT NULL','Default'=>'','Extra'=>''),
		'date_submitted'=>array('Field'=>'last_modified','Type'=>'timestamp','Key'=>'','Null'=>'','Default'=>'','Extra'=>'')
		);
		
	/**
	 * Translation status codes to map integers to messages for translation_status column of the 
	 * dataface__translations table.
	 */
	var $translation_status_codes = array(
		TRANSLATION_STATUS_UNTRANSLATED => 'Untranslated',
		TRANSLATION_STATUS_UNKNOWN => 'Unknown translation status',
		TRANSLATION_STATUS_SOURCE => 'Source translation',
		TRANSLATION_STATUS_MACHINE => 'Machine translation',
		TRANSLATION_STATUS_UNVERIFIED => 'Unverified translation',
		TRANSLATION_STATUS_APPROVED => 'Approved translation',
		TRANSLATION_STATUS_NEEDS_UPDATE => 'Out-of-date',
		TRANSLATION_STATUS_NEEDS_UPDATE_MACHINE =>'Out-of-date (Machine translation)',
		TRANSLATION_STATUS_NEEDS_UPDATE_UNVERIFIED =>'Out-of-date (Unverified)',
		TRANSLATION_STATUS_EXTERNAL => 'Managed Externally'
		);
		
	function Dataface_TranslationTool() {
	
	}
	
	/**
	 * Returns a string containing an HTML select list for selecting the 
	 * translation status of a given record.
	 * @param Dataface_Record $record The record whose translation status we want
	 *			to select.
	 * @param string $language 2-digit language code for the translation of interest.
	 * @param string $name The name of the HTML select widget. e.g. <select name="...">
	 * @param string $onchange Optional string containing onchange javascript.
	 * @returns string
	 */
	function getHTMLStatusSelector(&$record, $language, $name, $onchange=''){
		$trec =& $this->getTranslationRecord($record, $language);
		if ( !$trec ){
			//  no translation currently exists, so we will set the default 
			// value to -1, the flag for no translation yet.
			$default = -1;
		} else {
			$default = $trec->val('translation_status');
		}
		$out = array();
		$out[] = <<<END
			<select name="$name" onchange='$onchange'>
END;
		if ( $default == -1 ){
			$out[] = <<<END
			<option value="-1" selected>No translation provided</option>
END;
		}
		foreach ( $this->translation_status_codes as $key=>$val){
			if ( $default == $key ) $selected = "selected";
			else $selected = '';
			$out[] = <<<END
				<option value="$key" $selected>$val</option>
END;
		}
		$out[] = "</select>";
		return implode("\n", $out);
	}	
	
	/**
	 * Creates the table to store the translation information.
	 */
	function createTranslationsTable(){
		$app =& Dataface_Application::getInstance();
		$sql = "create table if not exists `dataface__translations` (";
		$cols = array();
		$primary_key_cols = array();
		$other_keys = array();
		foreach ($this->schema as $field){
			$default = (isset($field['Default']) ? "DEFAULT '{$field['Default']}'" : '');
			$cols[] = "`{$field['Field']}` {$field['Type']} {$field['Extra']} {$field['Null']} {$default}";
			if ( strcasecmp($field['Key'],'PRI') === 0 ){
				$primary_key_cols[$field['Field']] = $field;
			} else if ( $field['Key'] ){
				$other_keys[$field['Key']][$field['Field']] = $field;
			}
		}
		
		$sql .= implode(',',$cols).", PRIMARY KEY (`".implode('`,`',array_keys($primary_key_cols))."`)";
		if ( count($other_keys) > 0 ){
			$sql .=', ';
			foreach ($other_keys as $key_name=>$key){
				$sql .= "KEY `$key_name` (`".implode('`,`',array_keys($key))."`)";
			}
		}
		$sql .= ")";
		
		$res = mysql_query($sql, $app->db());
		if ( !$res ) {throw new Exception(mysql_error($app->db()), E_USER_ERROR);}
		return true;
		
	}
	function updateTranslationsTable(){
		$app =& Dataface_Application::getInstance();
		if ( !Dataface_Table::tableExists('dataface__translations',false) ){
			$this->createTranslationsTable();
		}
		
		$res = mysql_query("show columns from `dataface__translations`", $app->db());
		if ( !$res ) throw new Exception(mysql_error($app->db()), E_USER_ERROR);
		$cols = array();
		while ( $row = mysql_fetch_assoc($res) ){
			$cols[$row['Field']] = $row;
		}
		foreach ($this->schema as $field ){
			if (!isset($cols[$field['Field']]) ){
				$default = (isset($field['Default']) ? "DEFAULT '{$field['Default']}'" : '');
				$sql = "alter table `dataface__translations` add column `{$field['Field']}` {$field['Type']} {$field['Null']} {$default}";
				$res = mysql_query($sql, $app->db());
				if (!$res ) throw new Exception(mysql_error($app->db()), E_USER_ERROR);
			}
		}
		
		return true;
		
	}
	
	/**
	 * Creates the table to store the translation information.
	 */
	function createTranslationSubmissionsTable(){
		$app =& Dataface_Application::getInstance();
		$sql = "create table if not exists `dataface__translation_submissions` (";
		$cols = array();
		$primary_key_cols = array();
		$other_keys = array();
		foreach ($this->submission_schema as $field){
			$default = (isset($field['Default']) ? "DEFAULT '{$field['Default']}'" : '');
			$cols[] = "`{$field['Field']}` {$field['Type']} {$field['Extra']} {$field['Null']} {$default}";
			if ( strcasecmp($field['Key'],'PRI') === 0 ){
				$primary_key_cols[$field['Field']] = $field;
			} else if ( $field['Key'] ){
				$other_keys[$field['Key']][$field['Field']] = $field;
			}
		}
		
		$sql .= implode(',',$cols).", PRIMARY KEY (`".implode('`,`',array_keys($primary_key_cols))."`)";
		if ( count($other_keys) > 0 ){
			$sql .=', ';
			foreach ($other_keys as $key_name=>$key){
				$sql .= "KEY `$key_name` (`".implode('`,`',array_keys($key))."`)";
			}
		}
		$sql .= ")";
		
		$res = mysql_query($sql, $app->db());
		if ( !$res ) {echo $sql;throw new Exception(mysql_error($app->db()), E_USER_ERROR);}
		return true;
		
	}
	function updateTranslationSubmissionsTable(){
		$app =& Dataface_Application::getInstance();
		if ( !Dataface_Table::tableExists('dataface__translation_submissions',false) ){
			$this->createTranslationSubmissionsTable();
		}
		
		$res = mysql_query("show columns from `dataface__translation_submissions`", $app->db());
		if ( !$res ) throw new Exception(mysql_error($app->db()), E_USER_ERROR);
		$cols = array();
		while ( $row = mysql_fetch_assoc($res) ){
			$cols[$row['Field']] = $row;
		}
		foreach ($this->submission_schema as $field ){
			if (!isset($cols[$field['Field']]) ){
				$default = (isset($field['Default']) ? "DEFAULT '{$field['Default']}'" : '');
				$sql = "alter table `dataface__translation_submissions` add column `{$field['Field']}` {$field['Type']} {$field['Null']} {$default}";
				$res = mysql_query($sql, $app->db());
				if (!$res ) throw new Exception(mysql_error($app->db()), E_USER_ERROR);
			}
		}
		
		return true;
		
	}
	
	function submitTranslation(&$record, $params=array()){
		if ( !Dataface_Table::tableExists('dataface__translation_submissions',false) ){
			$this->createTranslationSubmissionsTable();
		}
		$trec = new Dataface_Record('dataface__translation_submissions', array());
		$trec->setValues($params);
		$trec->save();
	}
	/**
	 * Returns the record id of a given record as it would appear in the record_id field of the
	 * translations table.  It is basically a urlencode string of the record keys.
	 */
	function getRecordId(&$record){
		if (!$record ){
			throw new Exception("No record provided");
		}
		$vals = $record->strvals(array_keys($record->_table->keys()));
		$parts = array();
		foreach ($vals as $key=>$val){
			$parts[] = urlencode($key).'='.urlencode($val);
		}
		return implode('&',$parts);
		
	}
	
	function translateRecord(&$record, $sourceLanguage, $destLanguage){}
	function translateRecords(&$records, $sourceLanguage, $destLanguage){}
	
	function getTranslationId(&$record, $language){
		$app =& Dataface_Application::getInstance();
		$sql = "select `id` from `dataface__translations` where `record_id`='".addslashes($this->getRecordId($record))."' and `table`='".addslashes($record->_table->tablename)."' and `language`='".addslashes($language)."' limit 1";
		$res = mysql_query($sql, $app->db());
		if ( !$res ){
			$this->updateTranslationsTable();
			$res = mysql_query($sql, $app->db());
			if ( !$res ){
				throw new Exception(mysql_error($app->db()), E_USER_ERROR);
			}
		}
		if ( mysql_num_rows($res) === 0 ){
			@mysql_free_result($res);
			$sql = "insert into `dataface__translations` (`record_id`,`language`,`table`,`last_modified`) VALUES (
					'".addslashes($this->getRecordId($record))."',
					'".addslashes($language)."',
					'".addslashes($record->_table->tablename)."',
					NOW()
					)";
			$res = mysql_query($sql, $app->db());
			if ( !$res ) {
				$this->updateTranslationsTable();
				$res = mysql_query($sql, $app->db());
				if ( !$res ){
					throw new Exception(mysql_error($app->db()), E_USER_ERROR);
				}
			}
			$id = mysql_insert_id($app->db());
		} else {
			list($id) = mysql_fetch_row($res);
			@mysql_free_result($res);
		}
		
		return $id;
	}
	
	/**
	 * Gets a record from the dataface__translations table that stores the 
	 * pertinent translation status information for the given record in the 
	 * specified language.
	 * @param Dataface_Record &$record The record for which we want to know the translation status.
	 * @param string $language The 2-digit language code.
	 * @returns Dataface_Record
	 */
	function getTranslationRecord(&$record, $language){
		$app =& Dataface_Application::getInstance();
		$id = $this->getTranslationId($record, $language);
		$trecord =& df_get_record('dataface__translations', array('id'=>$id));
		if ( !isset($trecord) ) {
			$this->updateTranslationsTable();
			$trecord =& df_get_record('dataface__translations', array('id'=>$id));
			if ( !isset($trecord) ) throw new Exception("Error loading translation record for translation id '$id'", E_USER_ERROR);
		}
		return $trecord;
	}
	
	/**
	 * Returns the canonical version of the translation for a record.
	 * @param Dataface_Record &$record The record for which we are checking the
	 * 			version.
	 * @param string $language The 2-digit language code for the language we are checking.
	 * @returns string Canonical version in the form major_version.minor_version
	 */
	function getCanonicalVersion(&$record, $language){
		$app =& Dataface_Application::getInstance();
		$trecord =& $this->getTranslationRecord($record, $language);
		if ( PEAR::isError($trecord) ) return $trecord;
		return $trecord->val('version');
	}
	
	/**
	 * Marks a new canonical version for a given record's translation.
	 * The canonical version is an integer stored in the dataface__translations
	 * table's major_version column.  It allows administrators to keep track of
	 * which version a translation corresponds to.
	 */
	function markNewCanonicalVersion(&$record, $language=null){
		$app =& Dataface_Application::getInstance();
		$trecord =& $this->getTranslationRecord($record, $language);
		$trecord->setValue('version', $trecord->val('version')+1);
		$trecord->setValue('translation_status', TRANSLATION_STATUS_SOURCE);
		$res = $trecord->save();
		if ( PEAR::isError($res) ){
			return $res;
		}
		
		$this->invalidateTranslations($record);
		return $this->getCanonicalVersion($record, $language);
		
	}
	
	/**
	 * Invalidates the non-original translations.  This will set the translation
	 * statuses as follows:
	 *	Unknown -> Unknown
	 *	Machine -> Out-of-date (Machine translation)
	 *	Approved -> Out-of-date
	 *	Unverified -> Out-of-date (Unverified)
	 *  Original -> Original
	 *  Out-of-date * -> Out-of-date *
	 */
	function invalidateTranslations(&$record){
	
		$records = df_get_records('dataface__translations',array('record_id'=>'='.$this->getRecordId($record),'table'=>$record->_table->tablename));
		if ( PEAR::isError($records) ){
			throw new Exception($records->toString(), E_USER_ERROR);
		}
		while ($records->hasNext()){
			$trecord =& $records->next();
			$update=true;
			switch($trecord->val('translation_status')){
				case TRANSLATION_STATUS_MACHINE:
					$trecord->setValue('translation_status', TRANSLATION_STATUS_NEEDS_UPDATE_MACHINE);
					break;
				case TRANSLATION_STATUS_UNVERIFIED:
					$trecord->setValue('translation_status', TRANSLATION_STATUS_NEEDS_UPDATE_UNVERIFIED);
					break;
				case TRANSLATION_STATUS_APPROVED:
					$trecord->setValue('translation_status', TRANSLATION_STATUS_NEEDS_UPDATE);
					break;
				default:
					$update = false;
						//  no update necessary
				
			}
			
			if ( $update ){
				$res = $trecord->save();
				if ( PEAR::isError($res) ) return $res;
			}
			unset($trecord);
		}
		
		return true;
	}
	
	function setTranslationStatus(&$record, $language, $status){
		$trecord =& $this->getTranslationRecord($record, $language);
		$trecord->setValue('translation_status', $status);
		if ( $status == TRANSLATION_STATUS_APPROVED || $status == TRANSLATION_STATUS_MACHINE){
			$app =& Dataface_Application::getInstance();
			$def_record =& $this->getTranslationRecord($record, $app->_conf['default_language']);
			if ( $def_record ){
				$trecord->setValue('version', $def_record->val('version'));
			}
		}
		$trecord->save();
	}
	
	/**
	 * Untranslates a record that currently has a machine translation.  Since 
	 * machine translation will only work on records that do not already have 
	 * translations sometimes it is necessary to clear the existing translation
	 * so that it can be retranslated.
	 * This method will only work on records that are marked as machine translated.
	 * @param Dataface_Record The original record.
	 * @param string $language 2-digit language code.
	 * @returns boolean True if it succeeds.
	 */
	function untranslate(&$record, $language, $fieldname=null){
		$trecord =& $this->getTranslationRecord($record, $language);
		$app =& Dataface_Application::getInstance();
		switch ($trecord->val('translation_status')){
			case TRANSLATION_STATUS_MACHINE:
			case TRANSLATION_STATUS_NEEDS_UPDATE_MACHINE:
				
				$keyvals = $record->strvals(array_keys($record->_table->keys()));
				$clauses = array();
				foreach ($keyvals as $key=>$val){
					$clauses[] = "`{$key}`='".addslashes($val)."'";
				}
				if ( count($clauses) === 0 ) throw new Exception("Error trying to untranslate record: '".$record->getTitle()."'.  The table '".$record->_table->tablename."' does not appear to have a primary key.");
				if ( isset($fieldname) ){
					$sql = "update `{$record->_table->tablename}_{$language}` set `{$fieldname}`=NULL where ".implode(' and ', $clauses)." limit 1";
				} else {
					$sql = "delete from `{$record->_table->tablename}_{$language}` where ".implode(' and ', $clauses)." limit 1";
				}
				$res = mysql_query($sql, $app->db());
				if ( !$res ) throw new Exception(mysql_error($app->db()), E_USER_ERROR);
				return true;
		}
		return false;
	}
	
	
	
	/**
	 * Returns the changes for a given record in a particular language, since
	 * a given version number.
	 * @param Dataface_Record &$record The record we are interested in.
	 * @param string $language 2-digit language code
	 * @param float $version <major_version>.<minor_version>
	 * @param string $fieldname Optional field name to get changes for.
	 * @returns mixed Either a Dataface_Record object with the changes, or 
	 *				a string with the changes for $fieldname.
	 *
	 */
	function getChanges(&$record,$version, $lang=null,$fieldname=null){
		$app =& Dataface_Application::getInstance();
		if ( !isset($lang) ) $lang = $app->_conf['lang'];
		list($major_version,$minor_version) = explode('.', $version);
		$trecord = $this->getTranslationRecord($record, $lang);
		
		import('Dataface/HistoryTool.php');
		$ht = new Dataface_HistoryTool();
		
		$hrecord = $ht->searchArchives($trecord, array('major_version'=>$major_version, 'minor_version'=>$minor_version), $lang);
		$modified = $hrecord->strval('history__modified');
		return $ht->getDiffsByDate($record, $modified, null, $lang, $fieldname);
	
	}
	
	
	/**
	 * The early versions of the Dataface QueryTranslation extension stored even the default language
	 * translations in a translation table.  This is not necessary, and even undesired when you consider
	 * that the default language should be a fall-back point for records that do not contain the proper
	 * translation.  This method copies the translation data from the translation table of a particular
	 * language into the main table.  Use this with caution as it will overwrite data from the underlying
	 * table.
	 * @param string $newDefault The 2-digit language code for the new default language.
	 */
	function migrateDefaultLanguage($newDefault, $tables=null){
		
		import('Dataface/Utilities.php');
		import('Dataface/IO.php');
		$app =& Dataface_Application::getInstance();
		$no_fallback = @$app->_conf['default_language_no_fallback'];
			// Whether or not the application is currently set to disable fallback
			// to default language.
			
		$tables = $this->getMigratableTables();
		
		$log = array();
		
		foreach ($tables as $tablename){
			
			
			$table =& Dataface_Table::loadTable($tablename);
			$t_tablename = $tablename.'_'.$app->_conf['default_language'];
			
			if ( !$table || PEAR::isError($table) ) continue;
			$res = mysql_query("create table `{$tablename}_bu_".time()."` select * from `{$tablename}`", $app->db());
			$sql = "select `".join('`,`', array_keys($table->keys()))."` from `".$tablename."`";
			$res2 = mysql_query($sql, $app->db());
			$io = new Dataface_IO($tablename);
			$io->lang = $newDefault;
			while ( $rec = mysql_fetch_assoc($res2) ){
				//foreach (array_keys($rec) as $colkey){
				//	$rec[$colkey] = '='.$rec[$colkey];
				//}
				$app->_conf['default_language_no_fallback'] = 1;
				
				$record = df_get_record($tablename, $rec, $io);
				//print_r($record->strvals());

				$app->_conf['default_language_no_fallback'] = 0;
				
				$record2 = new Dataface_Record($tablename, array());
				$record2->setValues($record->vals());

				$r = $io->write($record2);
				if ( PEAR::isError($r) ){
					$log[$tablename] = "Failed to migrate data from table '{$t_tablename}' to '{$tablename}': ".$r->getMessage()."'";
					
				} else {
					$log[$tablename] = "Successfully migrated data from table '{$t_tablename}' to '{$tablename}'.";
				}
				unset($record);
				
				
			}
			mysql_free_result($res2);
			
			$res = mysql_query("create table `{$t_tablename}_bu_".time()."` select * from `{$t_tablename}`", $app->db());
			$res = mysql_query("truncate `{$t_tablename}`", $app->db());
			
			unset($io);
			unset($table);
			
			
		}
		return $log;
		$app->_conf['default_language_no_fallback'] = $no_fallback;
	}
	
	
	/**
	 * This is to satisfy the Dataface Modules API so that migrations can be run
	 * using the manage_migrate action.
	 */
	function requiresMigration(){
		
		$migrations = $this->getMigratableTables();
		if ( count($migrations) > 0 ){
			return "<p>The following tables need to be migrated so that the default language is stored inside the main table - not the translation table:</p>
			        <ul><li>".implode('</li><li>', $migrations)."</li></ul>
			        <p>This migration is necessary because older versions of the query translation extension would automatically store translations 
			        in their respective translation tables rather than the main table.  However it is desirable to store the default language
			        in the default table so that other translations can fall-back to the correct default translation if the record does not
			        have a translation.</p>";
		}	else {
			return false;
		}
	}
	
	/**
	 * Returns the tables that are eligible to be migrated.
	 */
	function getMigratableTables(){
		$app =& Dataface_Application::getInstance();
		if ( @$app->_conf['default_language_no_fallback'] ) return false;
			// We are still using the old style of translations, so there is no migration required.
			
		$migrations = array();
		$res = mysql_query("show tables", $app->db());
		$tables = array();
		while ( $row = mysql_fetch_row($res) ){
			$tables[] = $row[0];
		}
		mysql_free_result($res);
		foreach ($tables as $tablename){
			$translation_tablename = $tablename."_".$app->_conf['default_language'];
			if ( mysql_num_rows($res = mysql_query("show tables like '".addslashes($translation_tablename)."'", $app->db())) > 0){
				@mysql_free_result($res);
				list($num) = mysql_fetch_row($res = mysql_query("select count(*) from `".$translation_tablename."`",$app->db()));
				if ( $num > 0 ){
					$migrations[] = $tablename;
				}
			} else {
				
			}
			mysql_free_result($res);
		}
		return $migrations;
	
	}
	
	function migrate(){
		$app =& Dataface_Application::getInstance();
		return $this->migrateDefaultLanguage($app->_conf['default_language']);
		
	}
	
	function printTranslationStatusAlert($record, $language=null){
		if ( !isset($language) ){
			$app =& Dataface_Application::getInstance();
			$language = $app->_conf['lang'];
		}
		$trec =& $this->getTranslationRecord($record, $language);
		if ( !$trec) return;
		$status = $trec->val('translation_status');
		switch ($status){
			case TRANSLATION_STATUS_MACHINE:
				$msg = df_translate('machine translation warning',"This section was translated using a machine translator and may contain errors.");
				break;
			
			case TRANSLATION_STATUS_NEEDS_UPDATE_MACHINE:
				$msg = df_translate('old machine translation warning', "This section was translated using a machine translator and may contain errors.  The original version has also been modified since this translation was completed so this translation may be out of date.");
				break;
			
			case TRANSLATION_STATUS_UNVERIVIED:
				$msg = df_translate('unverified translation warning', "This translation has not been verified by an administrator yet.");
				break;
				
			case TRANSLATION_STATUS_NEEDS_UPDATE_UNVERIFIED:
				$msg = df_translate('old unverified translation warning', "This translation has not been verified by an administrator yet.  The original version has also been modified since this translation was completed so this translation may be out of date.");
				break;
				
			case TRANSLATION_STATUS_NEEDS_UPDATE:
				$msg = df_translate('old translation warning', "This translation may be out of date as the original version has been modified since this was last translated.");
				break;
				
		}
		if ( !@$msg ) return;
		import('Dataface/ActionTool.php');
		$at =& Dataface_ActionTool::getInstance();
		$actions = $at->getActions(array('category'=>'translation_warning_actions','record_id'=>$record->getId()));
		$actions_html = "<ul class=\"translation_options\">";
		foreach ($actions as $action){
			$actions_html .= <<<END
				<li><a href="{$action['url']}" title="{$action['description']}">{$action['label']}</a></li>
END;
			
		}	
		$actions_html .= '</ul>';
		echo <<<END
		
		<div class="portalMessage">
			{$msg}
			{$actions_html}
		</div>
END;
	}

	
	
	
	
	/*
	 What do we need to do?
	 
	 1. Set Translation status
	 2. Update translation version.
	 3. Untranslate (machine translations only).
	 4. View changes
	 5. 
	 */
	
	
}
