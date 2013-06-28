<?php
/**
 * A method to create the configuration table in the database.  The configuration
 * table is where configuration (e.g. fields.ini etc..) may be stored.  This is
 * a new feature in 0.6.14.
 *
 * @author Steve Hannah <shannah@sfu.ca>
 * @created Feb. 26, 2007
 */
function Dataface_ConfigTool_createConfigTable(){
	$self =& Dataface_ConfigTool::getInstance();
	if ( !Dataface_Table::tableExists($self->configTableName, false) ){
		$sql = "CREATE TABLE `".$self->configTableName."` (
					config_id int(11) NOT NULL auto_increment primary key,
					`file` varchar(255) NOT NULL,
					`section` varchar(128),
					`key` varchar(128) NOT NULL,
					`value` text NOT NULL,
					`lang` varchar(2),
					`username` varchar(32),
					`priority` int(5) default 5
					)";
		$res = mysql_query($sql, df_db());
		if ( !$res ){
			throw new Exception(mysql_error(df_db()), E_USER_ERROR);

		}
	}

}
