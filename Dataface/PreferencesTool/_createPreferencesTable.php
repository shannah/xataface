<?php
/**
 * This function acts as a member method of the PreferencesTool class.  It has
 * been factored out for efficiency because it only needs to be run once.
 * @see Dataface_PreferncesTool::_createPreferencesTable()
 */
function Dataface_PreferencesTool__createPreferencesTable(){
	$app =& Dataface_Application::getInstance();
	if( $app->_conf['_database']['driver']=='postgresql'){
	$res = xf_db_query(
		"create table if not exists dataface__preferences (
			pref_id serial not null ,
			username varchar(64) not null,
			\"table\" varchar(128) not null,
			record_id varchar(255) not null,
			key varchar(128) not null,
			value varchar(255) not null,
			CONSTRAINT pref_pkey PRIMARY KEY (pref_id));
			
			CREATE INDEX dataface__preferences_username_idx ON  dataface__preferences USING btree (username);	
			CREATE INDEX dataface__preferences_table_idx ON  dataface__preferences USING btree (\"table\");	", df_db());
	//die("da sviluppare:"__FILE__.":"__LINE__);
	}
	
			else
				$res = xf_db_query(
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
	
	if ( !$res ) throw new Exception(xf_db_error(df_db()), E_USER_ERROR);

}
