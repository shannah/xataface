<?php
function xf_db_connect($host,$user,$pass){ return mysqli_connect($host, $user, $pass); }
function xf_db_connect_errno(){ return mysqli_connect_errno();}
function xf_db_connect_error(){ return mysqli_connect_error();}
function xf_db_query($sql, $conn=null){ return mysqli_query($sql, $conn); }
function xf_db_error($link=null){ return mysqli_error($link); }
function xf_db_errno($link=null){ return mysqli_errno($link); }
function xf_db_escape_string($unescaped_string){ return mysqli_escape_string($unescaped_string); }
function xf_db_real_escape_string($link, $unescaped_string){ return mysqli_real_escape_string($link, $unescaped_string); }
function xf_db_fetch_array($link){ return mysqli_fetch_array($link); }
function xf_db_fetch_object($link){ return mysqli_fetch_object($link); }
function xf_db_fetch_row($link){ return mysqli_fetch_row($link); }
function xf_db_select_db($dbname, $link=null){ return mysqli_select_db($dbname, $link); }
function xf_db_free_result($result){ return mysqli_free_result($result);}
function xf_db_affected_rows($result){ return mysqli_affected_rows($result);}
function xf_db_fetch_lengths($result){ return mysqli_fetch_lengths($result);}
function xf_db_num_rows($result){ return mysqli_num_rows($result);}
function xf_db_insert_id($link){ return mysqli_insert_id($link);}
function xf_db_data_seek($result, $offset){ return mysqli_data_seek($result, $offset);}
function xf_db_character_set_name($link){ return mysqli_character_set_name($link);}
function xf_db_close($link){ return mysqli_close($link);}
