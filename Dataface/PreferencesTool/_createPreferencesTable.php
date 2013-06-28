<?php
/**
 * This function acts as a member method of the PreferencesTool class.  It has
 * been factored out for efficiency because it only needs to be run once.
 * @see Dataface_PreferncesTool::_createPreferencesTable()
 */
function Dataface_PreferencesTool__createPreferencesTable(){
	$res = mysql_query(
		"create table if not exists `dataface__preferences` (
			`pref_id` int(11) unsigned not null auto_increment,
			`username` varchar(64) not null,
			`table` varchar(128) not null,
			`record_id` varchar(255) not null,
			`key` varchar(128) not null,
			`value` varchar(255) not null,
			primary key (pref_id),
			index `username` (`username`),
			index `table` (`table`),
			index `record_id` (`record_id`))", df_db());
	if ( !$res ) throw new Exception(mysql_error(df_db()), E_USER_ERROR);

}
