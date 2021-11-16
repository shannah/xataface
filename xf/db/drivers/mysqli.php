<?php
define("MYSQL_DB_LV",1);
define("MYSQL_DB_FILE","mydriver.log");

function mydriver_log($val,$lev,$prof=1){
	
	if( $lev > MYSQL_DB_LV ){
		return;
	}
	$backtraces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,$prof);
	$fileinfo ="";
	foreach($backtraces as $backtr)
	{
		$fileinfo .= "\t".$backtr['file'] . ":" . $backtr['line']."\n";
	}
	
	error_log(date("d-m-Y H:i:s:").$val."\n",3,MYSQL_DB_FILE);
	error_log("TRACE:\n",3,MYSQL_DB_FILE);
	error_log($fileinfo,3,MYSQL_DB_FILE);
}

function xf_db_replaceInto($tablename,$fields,$pk,$value,$conn=null)
{
	$newSql= "replace into $tablename(".implode(",",$fields).") VALUES (".implode(",",$value).")";
	
	if($conn)
	{
		$res=mysqli_query($conn, $newSql);
		return $res;
	}
	else
		return $newSql;

}



function xf_db_sql_traslator($retStri,$sql,$conn,...$args)
{	
	
	
	switch ($sql) {
    case "SHOW TABLES LIKE":
	 $newSql = "show tables like '".$args[0]."'";
		break;
	case "SHOW TABLES":
        $newSql= "show tables";
        break;
	case "CREATE TABLE dataface__version":	
		$newSql= "create table dataface__version ( `version` int(5) not null default 0)";
		break;
	case "CREATE TABLE dataface__modules":		
		$newSql= "create table dataface__modules (
    					`module_name` varchar(255) not null primary key,
    					`module_version` int(11)
    				) ENGINE=InnoDB DEFAULT CHARSET=utf8 ";
		break;
	case "SELECT CREATE_TIME":
		$newSql = "select CREATE_TIME as Create_time, UPDATE_TIME as Update_time from information_schema.tables where TABLE_SCHEMA='".$args[0]."' and TABLE_NAME='".$args[1]."' limit 1";
		break;
		
		
	case "CREATE TABLE dataface__failed_logins":
		$newSql= "create table if not exists `dataface__failed_logins` (
			`attempt_id` int(11) not null auto_increment primary key,
			`ip_address` varchar(32) not null,
			`username` varchar(32) not null,
			`time_of_attempt` int(11) not null
			) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		break;
	case "SHOW COLUMNS FROM":
	$newSql = "SHOW COLUMNS FROM `".$args[0]."`";
		break;
	case "SET NAMES utf8":
		$newSql = 'set character_set_results = \'utf8\'';
		break;
	case "CREATE TABLE dataface__record_mtimes":
		$newSql= "create table if not exists dataface__record_mtimes (
				recordhash varchar(32) not null primary key,
				recordid varchar(255) not null,
				mtime int(11) not null) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		break;
	case "CREATE TABLE dataface__mtimes":
		$newSql= "create table if not exists dataface__mtimes (
			`name` varchar(255) not null primary key,
			`mtime` int(11)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		break;

	default:
		mydriver_log("xf_db_sql_traslator:Case not found",1,3);
		die("Case not found in postgresql.php");
		break;
	}
	
	
	if(!$retStri)
	{
		$res=mysqli_query($conn, $newSql);
		return $res;
	}
	else
		return $newSql;

}





function xf_db_connect($host,$user,$pass){
    if (strpos($host, ':') === false) {
        return mysqli_connect($host, $user, $pass);
    } else {
        $parts = explode(':', $host);
        return mysqli_connect($parts[0], $user, $pass, "", intval($parts[1]));
    }
}
function xf_db_connect_errno(){ return mysqli_connect_errno();}
function xf_db_connect_error(){ return mysqli_connect_error();}
function xf_db_query($sql, $conn=null){
	if ($conn === null) {
		$conn = df_db();
	}
	return mysqli_query($conn, $sql);
}
function xf_db_error($link=null){
	if ( $link === null ){
		return mysqli_error(df_db());
	} else {
		return mysqli_error($link);
	}
}
function xf_db_errno($link=null){
	if ( $link === null ){
		return mysqli_errno();
	}
	return mysqli_errno($link);
}
function xf_db_escape_string($unescaped_string){ return mysqli_escape_string(df_db(), $unescaped_string); }
function xf_db_real_escape_string($unescaped_string, $link){ return mysqli_real_escape_string($link, $unescaped_string); }
function xf_db_fetch_array($result){ return mysqli_fetch_array($result); }
function xf_db_fetch_assoc($result){ return mysqli_fetch_assoc($result); }
function xf_db_fetch_object($result){ return mysqli_fetch_object($result); }
function xf_db_fetch_row($result){ return mysqli_fetch_row($result); }
function xf_db_select_db($dbname, $link=null){
	if ($link === null) {
		$link = df_db();
	}
	return mysqli_select_db($link, $dbname);
}
function xf_db_free_result($result){ return mysqli_free_result($result);}
function xf_db_affected_rows($link=null){
	if ( $link === null ){
		return mysqli_affected_rows($link);
	}
	return mysqli_affected_rows($link);
}
function xf_db_fetch_lengths($result){ return mysqli_fetch_lengths($result);}
function xf_db_num_rows($result){ return mysqli_num_rows($result);}
function xf_db_insert_id($link=null){
	if ( $link === null ){
		return mysqli_insert_id();
	}
	return mysqli_insert_id($link);
}
function xf_db_data_seek($result, $offset){ return mysqli_data_seek($result, $offset);}
function xf_db_character_set_name($link=null){
	if ( $link === null ){
		return mysqli_character_set_name();
	}
	return mysqli_character_set_name($link);
}
function xf_db_close($link=null){
	if ( $link === null ){
		return mysqli_close();
	}
	return mysqli_close($link);
}
function xf_db_get_server_info($link=null){
	if ( $link === null ){
		return mysqli_get_server_info();
	}
	return mysqli_get_server_info($link);
}
