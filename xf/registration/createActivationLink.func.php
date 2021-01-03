<?php
namespace xf\registration;
import(XFROOT.'xf/registration/createRegistrationTable.func.php');
function createActivationLink($values=[]) {
    
	createRegistrationTable();
	
	// Now we will store the registration attempt
	
	// A unique code to be used as an id
	$code = null;
	do {
		$code = md5(rand());
	} while ( 
		xf_db_num_rows(
			xf_db_query(
				"select registration_code 
				from dataface__registrations 
				where registration_code='".addslashes($code)."'", 
				df_db()
				)
			) 
		);
	
	// Now that we have a unique id, we can insert the value
	
	$sql = "insert into dataface__registrations 
			(registration_code, registration_data) values
			('".addslashes($code)."',
			'".addslashes(
				serialize($values)
				)."')";
	$res = xf_db_query($sql, df_db());
	if ( !$res ) throw new Exception(xf_db_error(df_db()), E_USER_ERROR);
	
	$activation_url = $_SERVER['HOST_URI'].DATAFACE_SITE_HREF.'?--enable-sessions=1&-action=activate&code='.urlencode($code);
    return $activation_url;
}




?>