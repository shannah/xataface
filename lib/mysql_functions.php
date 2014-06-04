<?php

// adapted from Six MySQL/PHP functions to streamline development
//http://builder.com.com/5100-6371-1045433-4.html

function fnDatabaseExists($dbName, $oConn='') {
//Verifies existence of a MySQL database
$bRetVal = FALSE;
if ($oConn or $oConn = @xf_db_connect(DB_HOST, DB_USER, DB_PASSWORD)) {
$result = mysql_list_dbs($oConn);
while ($row=xf_db_fetch_array($result, MYSQL_NUM)) {
if ($row[0] ==  $dbName)
$bRetVal = TRUE;
}
xf_db_free_result($result);
xf_db_close($oConn);
}
return ($bRetVal);
}

function fnConnectionOK() {
// Verifies a connection to a MySQL database server
if (!$oConn = @xf_db_connect(DB_HOST, DB_USER, DB_PASSWORD)) {
$bRetVal = FALSE;
} else {
$bRetVal = TRUE;
}
return $bRetVal;
}


function fnTableExists($TableName, $oConn='') {
//Verifies that a MySQL table exists
if (!oConn and !$oConn = @xf_db_connect(DB_HOST, DB_USER, DB_PASSWORD)) {
$bRetVal = FALSE;
} else {
$bRetVal = FALSE;
$result = mysql_list_tables(DB_NAME, $oConn);
while ($row=xf_db_fetch_array($result, MYSQL_NUM)) {
if ($row[0] ==  $TableName)
$bRetVal = TRUE;
break;
}
xf_db_free_result($result);
xf_db_close($oConn);
}
return ($bRetVal);
}


function fnSQLtoHTML($sSQL, $oConn='') {
//Returns an HTML table from a SQL statement
if (!$oConn and !$oConn = @xf_db_connect(DB_HOST, DB_USER, DB_PASSWORD)) {
$sRetVal = xf_db_error();
} else {
if (!mysql_selectdb(DB_NAME,$oConn)) {
$sRetVal = xf_db_error();
} else {
if (!$result = xf_db_query($sSQL, $oConn)) {
$sRetVal = xf_db_error();
} else {
$sRetVal = "<table border=1>\n";
$sRetVal .= "<tr><th colspan=" . mysql_num_fields($result) . ">";
$sRetVal .= mysql_field_table($result,0) . "</th></tr>";
$sRetVal .= "<tr>";
$i=0;
while ($i < mysql_num_fields($result)) {
$sRetVal .= "<th>" . mysql_field_name($result, $i) . "</th>";
$i++;
}
$sRetVal .= "</tr>";
while ($line = xf_db_fetch_array($result, MYSQL_ASSOC)) {
$sRetVal .= "\t<tr>\n";
foreach ($line as $col_value) {
$sRetVal .= "\t\t<td>$col_value</td>\n";
}
$sRetVal .= "\t</tr>\n";
}
$sRetVal .= "</table>\n";
xf_db_free_result($result);
}
}
xf_db_close($oConn);
}
return ($sRetVal);
}



function fnSQLtoXML($sSQL, $oConn='') {
//Returns an XML data island from an SQL statement or an error string
if (!$oConn and !$oConn = @xf_db_connect(DB_HOST, DB_USER, DB_PASSWORD)) {
$sRetVal = xf_db_error();
} else {
if (!mysql_selectdb(DB_NAME,$oConn)) {
$sRetVal = xf_db_error();
} else {
if (!$result = xf_db_query($sSQL, $oConn)) {
$sRetVal = xf_db_error();
} else {
while ($line = xf_db_fetch_array($result, MYSQL_ASSOC)) {
$sRetVal = "\n<" . mysql_field_table($result,0) . ">";
$iThisField = 0;
foreach ($line as $col_value) {
$oTMP = mysql_fetch_field($result, $iThisField);
$iThisField ++;
$sThisFieldName = $oTMP -> name;
$sRetVal .= "\n\t<$sThisFieldName value=" . $col_value . ">";
$sRetVal .= "</$sThisFieldName>";
}
$sRetVal .= "\n</" . mysql_field_table($result,0) . ">\n";
}
xf_db_free_result($result);
}  }
xf_db_close($oConn);
}
return ($sRetVal);
}


?>
