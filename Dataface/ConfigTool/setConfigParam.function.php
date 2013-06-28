<?php
/**
  * Sets a configuration parameter in the configuration table.
  * This should not be called directly.  It should be called through the 
  * Dataface_ConfigTool class as its setConfigParam method.
  *
  * @param string $file The name of the ini file in which the config value is being set.
  * @param string $section The name of the section (could be null).
  * @param string $key The name of the parameter's key (not null)
  * @param string $value The value to set (not null)
  * @param string $username The username for which the parameter is being set (null for all users)
  * @param string $lang The 2-digit language code for which the parameter is being set (null for all languages).
  * @param integer $priority The priority of this config variable (priority dictates which 
  *					parameters take priority. Default vallue of 5.
  * @returns true if success or PEAR_Error if failure.
  *
  * This will create the configuration table if it doesn't already exist.
  *
  *	@author Steve Hannah <shannah@sfu.ca>
  * @created Feb. 26, 2007
  */
function Dataface_ConfigTool_setConfigParam($file, $section, $key, $value, $username=null, $lang=null, $priority=5){
	$self =& Dataface_ConfigTool::getInstance();
	// See if this parameter has already been set:
	$where = array();
	$where[] = "`key`='".addslashes($key)."'";
	$where[] = "`file`='".addslashes($file)."'";
	$where[] = "`section`".(isset($section) ? "='".addslashes($section)."'" : ' IS NULL');
	$where[] = "`username`".(isset($username) ? "='".addslashes($username)."'" : ' IS NULL');
	$where[] = "`lang`".(isset($lang) ? "='".addslashes($lang)."'" : ' IS NULL');
	
	$where = implode(' and ',$where);
	$sql = "select `config_id` from `".$self->configTableName."` where $where limit 1";

	$res = mysql_query($sql,df_db());
	if ( !$res ){
		$self->createConfigTable();
		$res = mysql_query($sql, df_db());
	}
	if ( !$res ){
		return PEAR::raiseError("Failed to get config parameter: ".mysql_error(df_db()));
	}
	
	$vals = array(
			"section"=>(isset($section) ? "'".addslashes($section)."'" : 'NULL'),
			"key"=>"'".addslashes($key)."'",
			"value"=>"'".addslashes($value)."'",
			"username"=>"'".addslashes($username)."'",
			"lang"=>"'".addslashes($lang)."'",
			"priority"=>$priority
			);
	if ( mysql_num_rows($res) > 0 ){
		$row = mysql_fetch_assoc($res);

		// We need to perform an update
		
		$updates = array();
		foreach ($vals as $vkey=>$vval){
			$updates[] = '`'.$vkey.'`='.$vval;
		}
		$sets = implode(' and ', $updates);
		$sql = "update `".$self->configTableName."` set ".$sets." where `config_id`='".$row['config_id']."' limit 1";
		
	} else {
		$values = array();
		$cols = array();
		foreach ($vals as $vkey=>$vval){
			$cols[] = "`$vkey`";
			$values[] = $vval;
		}
		$cols = implode(',',$cols);
		$values = implode(',',$values);
		$sql = "insert into `".$self->configTableName."` ($cols) VALUES ($values)";
		
	}
	@mysql_free_result($res);
	$res = mysql_query($sql, df_db());
	if ( !$res ){
		return PEAR::raiseError("Could not write config value: ".mysql_error(df_db()));
	}
	return true;
}
