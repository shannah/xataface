<?php
function xf_db_connect($host,$user,$pass){ return mysql_connect($host, $user, $pass); }
function xf_db_query($sql, $conn=null){ return mysql_query($sql, $conn); }
function xf_db_error($link=null){ return mysql_error($link); }
function xf_db_errno($link=null){ return mysql_errno($link); }
function xf_db_escape_string($unescaped_string){ return mysql_escape_string($unescaped_string); }
function xf_db_fetch_array($link){ return mysql_fetch_array($link); }
function xf_db_fetch_object($link){ return mysql_fetch_object($link); }
function xf_db_fetch_row($link){ return mysql_fetch_row($link); }
function xf_db_select_db($dbname, $link=null){ return mysql_select_db($dbname, $link); }