<?php
function xf_db_connect($host,$user,$pass){ return mysql_connect($host, $user, $pass); }
function xf_db_connect_errno(){ return mysql_connect_errno();}
function xf_db_connect_error(){ return mysql_connect_error();}
function xf_db_query($sql, $conn=null){ 
	if ( $conn === null ){
		return mysql_query($sql);
	} else {
		return mysql_query($sql, $conn); 
	}
}
function xf_db_error($link=null){
	if ( $link === null ){
		return mysql_error();
	} else {
		return mysql_error($link);
	}
}
function xf_db_errno($link=null){ 
	if ( $link === null ){
		return mysql_errno();
	}
	return mysql_errno($link); 
}
function xf_db_escape_string($unescaped_string){ return mysql_escape_string($unescaped_string); }
function xf_db_real_escape_string($link, $unescaped_string){ return mysql_real_escape_string($link, $unescaped_string); }
function xf_db_fetch_array($result){ return mysql_fetch_array($result); }
function xf_db_fetch_assoc($result){ return mysql_fetch_assoc($result); }
function xf_db_fetch_object($result){ return mysql_fetch_object($result); }
function xf_db_fetch_row($result){ return mysql_fetch_row($result); }
function xf_db_select_db($dbname, $link=null){ 
	if ( $link === null ){
		return mysql_select_db($dbname);
	}
	return mysql_select_db($dbname, $link); 
}
function xf_db_free_result($result){ return mysql_free_result($result);}
function xf_db_affected_rows($link=null){ 
	if ( $link === null ){
		return mysql_affected_rows($link);
	}
	return mysql_affected_rows($link);
}
function xf_db_fetch_lengths($result){ return mysql_fetch_lengths($result);}
function xf_db_num_rows($result){ return mysql_num_rows($result);}
function xf_db_insert_id($link=null){ 
	if ( $link === null ){
		return mysql_insert_id();
	}
	return mysql_insert_id($link);
}
function xf_db_data_seek($result, $offset){ return mysql_data_seek($result, $offset);}
function xf_db_character_set_name($link=null){ 
	if ( $link === null ){
		return mysql_character_set_name();
	}
	return mysql_character_set_name($link);
}
function xf_db_close($link=null){ 
	if ( $link === null ){
		return mysql_close();
	}
	return mysql_close($link);
}
function xf_db_get_server_info($link=null){ 
	if ( $link === null ){
		return mysql_get_server_info();
	}
	return mysql_get_server_info($link);
}
