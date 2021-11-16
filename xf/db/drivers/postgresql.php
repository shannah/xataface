<?php
//Convertita da gio


global $LAST_ID;

/*
* Replace fà update se il record esiste altrimenti fà un insert
* Questa è la versione generica
*/
function xf_db_replaceInto($tablename,$fields,$pk,$value,$conn=null)
{
	$newSql= "INSERT INTO $tablename(".implode(",",$fields).") VALUES (".implode(",",$value).")
	ON CONFLICT (".implode(",",$pk).")
	DO UPDATE SET(".implode(",",$fields).") = (".implode(",",$value).");";
	
	if($conn)
	{
		$res=pg_query($conn, $newSql);
		return $res;
	}
	else
		return $newSql;

}


/*
* Se conosco già la query non passo per il regexp
* cosi sono più veloce!
*/
function xf_db_sql_traslator($retStri,$sql,$conn,...$args)
{	
	
	//error_log("xf_db_sql_traslator:".debug_backtrace()."\n",3,"gio.log");
	error_log("sql xf_db_sql:".$sql."\n",3,"gio.log");
	$app = Dataface_Application::getInstance();
	$partsHost = explode(':', $app->_conf['_database']['host']);
	$partsDatabase = explode('/', $partsHost[0]);
	
	switch ($sql) {
    case "SHOW TABLES LIKE":
        $newSql="(SELECT viewname as \"tablename\" FROM pg_catalog.pg_views where  schemaname='".$app->_conf['_database']['name']."' and viewname like '".$args[0]."%' )
union 
(SELECT tablename   FROM pg_catalog.pg_tables where  schemaname='".$app->_conf['_database']['name']."' and tablename like '".$args[0]."%') ";
		
		break;
	case "SHOW TABLES":
        $newSql= "SELECT tablename FROM pg_catalog.pg_tables where schemaname='".$app->_conf['_database']['name']."'";
        break;
	case "CREATE TABLE dataface__version":	
		$newSql= "create table dataface__version ( version int4 not null default 0)";
		break;
	case "CREATE TABLE dataface__modules":		
		$newSql= "create table dataface__modules (
						module_name varchar(255) not null primary key,
						module_version int4
					) ";
		break;
	case "SELECT CREATE_TIME":
		$newSql= "SELECT (pg_stat_file('base/'||oid ||'/PG_VERSION')).modification as \"Create_time\",null as \"Update_time\" FROM pg_database where datname='".$partsDatabase[1] ."'";
		break;
		
		
	case "CREATE TABLE dataface__failed_logins":
		$newSql= "create table if not exists dataface__failed_logins (
			attempt_id serial not null primary key,
			ip_address varchar(32) not null,
			username varchar(32) not null,
			time_of_attempt int8 not null)";
		break;
	case "SHOW COLUMNS FROM":
		$newSql= "SELECT  
f.attname AS \"Field\",  
pg_catalog.format_type(f.atttypid,f.atttypmod) AS \"Type2\",
case
	when pg_catalog.format_type(f.atttypid,f.atttypmod)='integer' then 'int(11)'
	when pg_catalog.format_type(f.atttypid,f.atttypmod)='bytea' then 'longblob'
	when pg_catalog.format_type(f.atttypid,f.atttypmod)='character varying(10)' then 'varchar(10)'
	when pg_catalog.format_type(f.atttypid,f.atttypmod)='character varying(64)' then 'varchar(64)'
	when pg_catalog.format_type(f.atttypid,f.atttypmod)='character varying(128)' then 'varchar(128)'
	when substring(pg_catalog.format_type(f.atttypid,f.atttypmod),1,8)='geometry' then 'geometry'
	else pg_catalog.format_type(f.atttypid,f.atttypmod) 
end as \"Type\",
case
	when f.attnotnull='true' then 'NO'
	when f.attnotnull='false' then 'YES'
end as  \"Null\", 
CASE  
    WHEN p.contype = 'p' THEN 'PRI'  
    ELSE ''  
END AS \"Key\",  
CASE
    WHEN f.atthasdef = 't' and position('nextval' in pg_get_expr(d.adbin, d.adrelid))>0 THEN null
    WHEN f.atthasdef = 't' and position('NULL' in pg_get_expr(d.adbin, d.adrelid))>0 THEN null
	WHEN f.atthasdef = 't' and position('nextval' in pg_get_expr(d.adbin, d.adrelid))=0 THEN  ( REGEXP_REPLACE(pg_get_expr(d.adbin, d.adrelid),'''(.*)''(.*)','\\1') )::varchar
END AS \"Default\"  ,
case
	when SUBSTRING (pg_get_expr(d.adbin, d.adrelid),1,7) = 'nextval' then 'auto_increment'
end as \"Extra\"
FROM pg_attribute f  
JOIN pg_class c ON c.oid = f.attrelid  
JOIN pg_type t ON t.oid = f.atttypid  
LEFT JOIN pg_attrdef d ON d.adrelid = c.oid AND d.adnum = f.attnum  
LEFT JOIN pg_namespace n ON n.oid = c.relnamespace  
LEFT JOIN pg_constraint p ON p.conrelid = c.oid AND f.attnum = ANY (p.conkey)  
LEFT JOIN pg_class AS g ON p.confrelid = g.oid
LEFT JOIN pg_index AS ix ON f.attnum = ANY(ix.indkey) and c.oid = f.attrelid and c.oid = ix.indrelid 
LEFT JOIN pg_class AS i ON ix.indexrelid = i.oid 
WHERE c.relkind = 'r'::char  
AND n.nspname = '".$app->_conf['_database']['name']."'  -- Replace with Schema name 
AND c.relname = '". $args[0]."'  -- Replace with table name, or Comment this for get all tables
AND f.attnum > 0
ORDER BY c.relname,f.attname;  ";
		break;
	case "SET sql_mode=\"\"":
	case "SET sql_mode=":
	case "SET NAMES utf8":
		return;
		break;
	case "SET character_set_results":
	case "SET character_set_client": 
		$newSql="SET CLIENT_ENCODING TO '".$args[0]."'";
		break;
		
	case "CREATE TABLE dataface__record_mtimes":
		$newSql= "create table if not exists dataface__record_mtimes (
				recordhash varchar(32) not null primary key,
				recordid varchar(255) not null,
				mtime int8 not null)";
		break;
	case "CREATE TABLE dataface__mtimes":
		$newSql= "create table if not exists dataface__mtimes (
				name varchar(255) not null primary key,
				mtime int4
			) ";
		break;
	/*case "REPLACE dataface__mtimes":
		$newSql= "update dataface__mtimes set(name,mtime) = ('".$args[0]."','".$args[1]."')";
		break;
	case	"REPLACE dataface__record_mtimes":
		$newSql= "update dataface__record_mtimes set(recordhash, recordid, mtime) = ('".$args[0]."','".$args[1]."',".$args[2].")";
		break;

	case	"REPLACE dataface__tokens":
		$newSql= "update dataface__tokens  set(token, hashed_token) =  ('".$args[0]."','".$args[1]."')";
		break;
		*/
	default:
		error_log("xf_db_sql_traslator:".print_r(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,2),true)."\n",3,"gio.log");
		die("Case not found in postgresql.php");
		break;
	}
	
	error_log("newSql xf_db_sql:".$newSql."\n",3,"gio.log");
	
	if(!$retStri)
	{
		$res=pg_query($conn, $newSql);
		return $res;
	}
	else
		return $newSql;

}


function xf_db_connect($host,$user,$pass){
	
	$partsHost = explode(':', $host);
	$partsDatabase = explode('/', $partsHost[0]);
//var_dump(debug_backtrace());
//die();
	
	//echo "string con: "."host=$partsDatabase[0] port=$partsHost[1] dbname=$partsDatabase[1] user=$user password=$pass"."<br>";
	
	$conn=pg_connect("host=$partsDatabase[0] port=$partsHost[1] dbname=$partsDatabase[1] user=$user password=$pass");
	//var_dump($conn);
	return  $conn;
	
	
	
}
function xf_db_connect_errno(){ return -1;/*return mysqli_connect_errno();*/}
function xf_db_connect_error(){ return "Errore di connessione"; /*return mysqli_connect_error();*/}
//Convertita da gio
function xf_db_query($sql, $conn=null){
	global $LAST_ID;
	$sql=str_replace ('`','',$sql);
	
	$pattern[0] = '/SELECT (.*) LIMIT (\d+),(\d+)/'; 
	$replace[0] = 'SELECT $1 limit $3 offset $2';
	$pattern[1] = '/ifnull/';
	$replace[1] = 'coalesce';
	$pattern[2] = '/UPDATE(.*) SET(.*) WHERE (.*)[ *]=[ *](.*) LIMIT 1/';
	$replace[2] = 'UPDATE $1 SET $2 WHERE $3=(SELECT $3 FROM $1 WHERE $3 = $4 LIMIT 1)';

	//$pattern[3] = '/ length/';
	//$replace[3] = ' pg_column_size';

	//$pattern[3] = '/([\s,]*)length/';
	//$replace[3] = '$1pg_column_size';
	
	$pattern[3] = '/([\s,]*)length[\s]*\(([\w\d_\-\.]+)\)/';
	$replace[3] = '$1 length($2::text)';
	
	
	
	//$pattern[2] = "/INSERT INTO (.*)'nextval\(\\'(.*)\\\'::regclass\)\'(.*)/"; 
	//$replace[2] = "INSERT INTO $1 nextval($2::regclass) $3";
	
	$newSql = preg_replace($pattern, $replace, $sql);
	if ($newSql == null)
		if (PREG_BACKTRACK_LIMIT_ERROR  ==preg_last_error())
			error_log("PREG_BACKTRACK_LIMIT_ERROR".preg_last_error()."\n",3,"gio.log"); 
		else
			error_log("preg error n°:".preg_last_error()."\n",3,"gio.log"); 
	
	error_log(date("d-m-Y H:i:s")."xf_db_query:".print_r(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,1),true)."\n",3,"gio.log");
	
	error_log("sql:".$sql."\n",3,"gio.log");
	error_log("newSql:".$newSql."\n",3,"gio.log");
	
	
	
	$res=pg_query($conn, $newSql);
	if ( !$res ){
		error_log("Query Error:".pg_last_error($conn)."\n",3,"gio.log");
		error_log("Stack:".print_r(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,1),true)."\n",3,"gio.log");
	}		
	/*
	
	if (pg_send_query($conn, $newSql)) {
		$res=pg_get_result($conn);
		if ($res) {
			$state = pg_result_error_field($res, PGSQL_DIAG_SQLSTATE);
			if ($state!=0) {
				error_log("Query Error:".pg_last_error($conn)."\n",3,"gio.log");
				error_log("Stack:".print_r(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,2),true)."\n",3,"gio.log");
			}
		}  
		else{
			error_log("Query Error:".pg_last_error($conn)."\n",3,"gio.log");
			error_log("Stack:".print_r(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,2),true)."\n",3,"gio.log");
		}
	}
	*/
	
	error_log("Query Error:".pg_last_error($conn)."\n",3,"gio.log");
	

	//error_log(date("d-m-Y H:i:s")."xf_db_query:".print_r(debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT,2),true)."\n",3,"gio.log");
	
	$word = explode(' ', trim($newSql));		// Get the 1'st word	
	if(strtoupper($word[0])=='INSERT')
	{
		$query="SELECT lastval();";
		$result = pg_query($conn, $query);
		$insert_row = pg_fetch_row($result);
		$LAST_ID=$insert_row[0];
		error_log("LAST_ID query:".$LAST_ID."\n",3,"gio.log");
	}
	/*else if ( ($word[0]=='select' || $word[0] =='SELECT' ) && $word[1]=='COUNT(*)')
	if($res)
	{	
		$row=pg_fetch_row($res);
		error_log("count(*) row:".print_r($row,true)."\n",3,"C:\Users\giorgio.saporito\Documents\App\PHP\gio.log");
		
		if ( is_countable($row) && sizeof($row)==1)
		$LAST_ID=$row[0];
	}*/
	return $res;
}
//Convertita da gio
function xf_db_error($link=null){
	if ( $link === null ){
		return pg_last_error();
	} else {
		return pg_last_error($link);
	}
}
function xf_db_errno($link=null){
	if ( $link === null ){
		return pg_last_error();
	} else {
		return pg_last_error($link);
	}
}
function xf_db_escape_string($unescaped_string){ die("xf_db_escape_string non implementato"); /*return mysqli_escape_string(df_db(), $unescaped_string);*/ }
function xf_db_real_escape_string($unescaped_string, $link){ die("xf_db_real_escape_string non implementato"); /*return mysqli_real_escape_string($link, $unescaped_string); */}
function xf_db_fetch_array($result){ 

	global $LAST_ID;
	$res=pg_fetch_array($result, NULL, PGSQL_BOTH ); 
	//error_log("Pura_res:".serialize($res)."\n",3,"C:\Users\giorgio.saporito\Documents\App\PHP\gio.log");
	
	/*if($res and sizeof($res)>0)
		$LAST_ID=$res[0];
	else
		$res=false;
	*/
	return $res; 


return $res;

}
function xf_db_fetch_assoc($result){ 
	global $LAST_ID;
	//error_log("xf_db_fetch_assoc:\n".print_r(debug_backtrace(),true)."\n",3,"gio.log");
	$res=pg_fetch_array($result, NULL, PGSQL_ASSOC);
	error_log("Pura_res2:".serialize($res)."\n",3,"gio.log");
	if($res)
	{
		//$LAST_ID=reset ( $res );
		error_log("LASTID:".$LAST_ID."\n",3,"gio.log");
	}
	return $res;
	
	}
function xf_db_fetch_object($result){ die("xf_db_fetch_object non implementato"); /*return mysqli_fetch_object($result); */}
function xf_db_fetch_row($result){
	global $LAST_ID;
	$res=pg_fetch_row ($result);
	error_log("Pura_res:".serialize($res)."\n",3,"gio.log");
	
	/*if($res and sizeof($res)>0)
		$LAST_ID=$res[0];
	else
		$res=false;
*/
	return $res; 
}
//Convertita da gio
function xf_db_select_db($dbname, $link=null){
	$sql = "SET search_path TO $dbname;";
	if ( $link === null )
		$result = pg_query($sql);
	else
		$result = pg_query($link, $sql);
	if (!$result) {
		return false;
		 //die("Error in SQL query: " . pg_last_error());
	}
	else	
		return true;
}
function xf_db_free_result($result){ return pg_free_result ($result);}
function xf_db_affected_rows($link=null){
	die("xf_db_affected_rows non implementato");
	/*if ( $link === null ){
		return mysqli_affected_rows($link);
	}
	return mysqli_affected_rows($link);*/
}
function xf_db_fetch_lengths($result){ return mysqli_fetch_lengths($result);}
function xf_db_num_rows($result){ return pg_num_rows ($result);}
function xf_db_insert_id($link=null){
	global $LAST_ID;
	error_log("link:".serialize($link)."\n",3,"gio.log");
	error_log("LASTID:".$LAST_ID."\n",3,"gio.log");
	//error_log("Chiamata:".print_r(debug_backtrace(),true)."\n",3,"C:\Users\giorgio.saporito\Documents\App\PHP\gio.log");
	return $LAST_ID;
}
function xf_db_data_seek($result, $offset){ return pg_result_seek($result, $offset);}
function xf_db_character_set_name($link=null){
	die("xf_db_character_set_name non implementato");
	/*if ( $link === null ){
		return mysqli_character_set_name();
	}
	return mysqli_character_set_name($link);*/
}
function xf_db_close($link=null){
	if ( $link === null ){
		return pg_close();
	}
	return pg_close($link);
}
//Convertita da gio
function xf_db_get_server_info($link=null){
	if ( $link === null ){
		return pg_parameter_status ('server_version');
	}
	return pg_parameter_status ($link,'server_version');
}
