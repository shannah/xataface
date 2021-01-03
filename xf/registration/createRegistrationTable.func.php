<?php
namespace xf\registration;
/**
 * Creates a table to hold the temporary user registrations.
 */
function createRegistrationTable(){
	if ( !\Dataface_Table::tableExists('dataface__registrations', false) ){
		$sql = "create table `dataface__registrations` (
			registration_code varchar(32) not null,
			registration_date timestamp not null,
			registration_data longtext not null,
			primary key (registration_code)) ENGINE=InnoDB DEFAULT CHARSET=utf8";
			// registration_code stores an md5 code used to identify the registration
			// registration_date is the date that the registration was made
			// registration_data is a serialized array of the data from getValues()
			// on the record.
			
			
		$res = xf_db_query($sql, df_db());
		if ( !$res ) throw new \Exception(xf_db_error(df_db()), E_USER_ERROR);
	}
	return true;

}
?>