<?php
import('Dataface/AuthenticationTool.php');
import('Dataface/IO.php');
define('Dataface_Clipboard_tablename', '_df_clipboard');
define('Dataface_Clipboard_lifetime', 1800);
define('Dataface_Clipboard_threshold', 20);
define('Dataface_Clipboard_clipboard_id_key', '_df_clipboard_id');

/**
 * A clipboard class to enable users to cut and paste records between relationships
 * and tables.
 */
class Dataface_Clipboard {

	var $id;
	var $errors;
	var $warnings;
	var $messages;
	
	function Dataface_Clipboard($id){
		$this->id = $id;
	}

	/**
	 * Checks whether the clipboard is currently installed.
	 * @scope staticlutie
	 */
	function isInstalled(){
		static $isInstalled = -1;
		if ( $isInstalled == -1 ){
		
			$app =& Dataface_Application::getInstance();
			$isInstalled = ( mysql_num_rows(mysql_query("show tables like '".Dataface_Clipboard_tablename."'", $app->db())) == 0 );
		}
		return $isInstalled;
	}
	
	/**
	 * Installs the clipboard by creating a table named '_df_clipboard' in the 
	 * database to store the clipped items.
	 * @scope static
	 */
	function install(){
		if ( !Dataface_Clipboard::isInstalled() ){
			$app =& Dataface_Application::getInstance();
			mysql_query(
				"CREATE TABLE `".Dataface_Clipboard_tablename."` (
					`clipid` INT(11) auto_increment NOT NULL,
					`clipperid` VARCHAR(32) NOT NULL,
					`cut` TINYINT(1) DEFAULT 0,
					`recordids` TEXT,
					`lastmodified` datetime,
					PRIMARY KEY (`clipid`),
					UNIQUE (`clipperid`))", $app->db()) or trigger_error("Failed to create clipboard table: ".mysql_error($app->db()), E_USER_ERROR);
			return true;
		}
		return false;
	
	}
	
	
	/**
	 * Cleans out old entries from the clipboard.
	 * @scope static
	 */
	function clean(){
		$app =& Dataface_Application::getInstance();
		mysql_query("delete from `".Dataface_Clipboard_tablename."` where UNIX_TIMESTAMP(`lastmodified`) > ".(time()-Dataface_Clipboard_lifetime),
			$app->db()) or trigger_error("Failed to clean old data from the clipboard: ".mysql_error($app->db()), E_USER_ERROR);
	}
	
	
	/**
	 * Cleans the old entries from the clipboard using a shotgun approach.
	 * This generates a random number betweenm 0 and 100 and cleans the clipboard
	 * only if the number is above the threshold defined in the 
	 * Dataface_Clipboard_threshold constant.
	 * @scope static
	 */
	function shotgunClean(){
		if ( rand(0,100) > Dataface_Clipboard_threshold ){
			Dataface_Clipboard::clean();
		}
	}
	
	
	/**
	 * Obtains a reference to the clipboard instance.
	 *
	 * @return Dataface_Clipboard If the user is logged in or sessions are enabled.
	 *			Otherwise will return a PEAR_Error object.
	 * @scope static
	 */
	public static function &getInstance(){
		static $clipboard = 0;
		if ( $clipboard == 0 ){
			// we need to get an id for the clipboard
			$auth =& Dataface_AuthenticationTool::getInstance();
			$username = $auth->getLoggedInUsername();
			if ( isset($username) ) $id = $username;
			else {
				if ( @session_id() ){
					if ( isset($_SESSION[Dataface_Clipboard_clipboard_id_key]) ){
						$id = $_SESSION[Dataface_Clipboard_clipboard_id_key];
					} else {
						$id = md5(rand(0,10000000));
						$_SESSION[Dataface_Clipboard_clipboard_id_key] = $id;
					}
				} else {
					$err= PEAR::raiseError("No clipboard is available because the user is not logged in and sessions are not enabled.");
					return $err;
				}
			}
		
			$clipboard = new Dataface_Clipboard($id);
			
		}
		return $clipboard;
	}
	
	/**
	 * Indicates whether the clipboard is empty.
	 * @return boolean True if the clipboard is empty for the current user.
	 */
	function empty(){
		return (mysql_num_rows(mysql_query("select count(*) from `".Dataface_Clipboard_tablename."` where `clipperid`='".addslashes($this->id)."'")) == 0);
	}
	
	
	/**
	 * Copies the given record onto the clipboard.
	 *
	 * <p>This takes multilingual issues into account so that transation records
	 *	in translation tables will also be copied.</p>
	 *
	 * @param array $recordids Array of record ids of the records to copy.  Record ids
	 *		follow the pattern: table/relationship?key1=val1&key2=val2&relationship::key1=val1&relationship::key2=val2
	 *
	 * @return boolen True if the copy is successful.
	 */
	function copy($recordids){
		$this->clearLogs();
		$app =& Dataface_Application::getInstance();
		Dataface_Clipboard::shotgunClean();
		$res = mysql_query(
			"REPLACE INTO `".Dataface_Clipboard_tablename."` 
			(`clipperid`,`cut`,`recordids`,`lastmodified`)
			VALUES
			('".addslashes($this->id)."',
			0,'".addslashes(implode("\n",$recordids))."', NOW()
			)", $app->db();
		if ( !$res ){
			return PEAR::raiseError(mysql_error($app->db()));
		}
		return true;
	
	}
	
	/**
	 * Cuts a record and stores it on the clipboard.  This won't erase the record
	 * until it is pasted.
	 *
	 * @param array $recordids
	 *
	 * @return boolean True if the cut is successful.
	 */
	function cut($recordids){
		$this->clearLogs();
		$app =& Dataface_Application::getInstance();
		Dataface_Clipboard::shotgunClean();
		$res = mysql_query(
			"REPLACE INTO `".Dataface_Clipboard_tablename."` 
			(`clipperid`,`cut`,`recordids`,`lastmodified`)
			VALUES
			('".addslashes($this->id)."',
			1,'".addslashes(implode("\n",$recordids))."', NOW()
			)", $app->db();
		if ( !$res ){
			return PEAR::raiseError(mysql_error($app->db()));
		}
		return true;
	}
	
	/**
	 * Pastes a record from the clipboard into a new location.  The new location
	 * may be a table or in a relationship with another record.
	 *
	 * @param string $destid The id of the destination record.
	 * @param string $relationship The name of the relationship into which the 
	 *		clipboard contents should be pasted.  If none is provided the contents
	 *		will be pasted into the relationship designated as the chldren relationship.
	 *
	 * @return mixed True if the paste is sucessful.  If it fails, it will return
	 * 	a PEAR_Error object with the reason for failure.
	 */
	function paste($destid, $relationship=null){
		$this->clearLogs();
		$app =& Dataface_Application::getInstance();
		Dataface_Clipboard::shotgunClean();
		$res = mysql_query("SELECT * FROM `".Dataface_Clipboard_tablename."` where `clipperid`='".addslashes($this->id)."'", $app->db());
		if ( mysql_num_rows($res)>0 ){
			$row = mysql_fetch_assoc($res);
		} else {
			return PEAR::raiseError('The clipboard is empty.');
		}
		
		$dest =& Dataface_IO::loadRecordById($destid);
			// The destination record.
		
		if ( !isset( $dest ) ) return PEAR::raiseError('The destination "'.$destid.'" could not be found to paste the clipboard contents.');
		if ( is_a($dest, 'Dataface_RelatedRecord') ){
			// we don't paste into related records... so lets turn this into a Dataface_Record object
			$destrecord =& $dest->toRecord();
			unset($dest);
			$dest =& $destrecord;
			unset($destrecord);
		}
		
		if ( !isset($relationship) ){
			$rel =& $dest->_table->getChildrenRelationship();
			if ( !isset($rel) ){
				return PEAR::raiseError('No relationship was specified into which to paste the contents of the clipboard.');
			} else {
				$relationship = $rel->getName();
				unset($rel);
			}
		}
		
		$io = new Dataface_IO($dest->_table->tablename);
		
		// $row should now contain the clipboard contents.
		$recordids = explode("\n", $row['recordids']);
		foreach ( $recordids as $recordid ){
			$record =& Dataface_IO::loadRecordById($recordid);
			if ( is_a($record, 'Dataface_Record')){
				$io2 = new Dataface_IO($record->_table->tablename);
			} else {
				$io2 = new Dataface_IO($record->_record->_table);
			}
			
			if ( isset($record) and !PEAR::isError($record) ){
				// the record id was loaded successfully
				$newrecord = new Dataface_RelatedRecord($dest, $relationship, $record->vals());
				$io->addExistingRelatedRecord($newrecord);
				
				if ( $row['cut'] ){
					// This was cut from the clipboard so we need to remove the original
					$res = $io2->removeRelatedRecord($record, false);
					if ( PEAR::isError($res) ){
						if ( Dataface_Error::isWarning($res) ) $this->logWarning($res);
						else $this->logError($res);
					}
				}
			}
			
			unset($record);
			unset($newrecord);
			unset($io2);
		}
		
		
	}
	

	
	function logError($error){
		$this->errors[] = $error;
	}
	
	function logWarning($warning){
		$this->warnings[] = $warning;
	}
	
	function logMessage($message){
		$this->messages[] = $message;
	}
	
	function clearLogs(){
		$this->errors = array();
		$this->warnings = array();
		$this->messages = array();
	}

}
