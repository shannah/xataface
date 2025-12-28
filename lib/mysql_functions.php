<?php

// adapted from Six MySQL/PHP functions to streamline development
//http://builder.com.com/5100-6371-1045433-4.html

function fnDatabaseExists($dbName, $oConn='') {
//Verifies existence of a MySQL database
$bRetVal = FALSE;
if ($oConn or $oConn = @xf_db_connect(DB_HOST, DB_USER, DB_PASSWORD)) {
$result = xf_db_query("SHOW DATABASES", $oConn);
while ($row=xf_db_fetch_array($result)) {
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
xf_db_select_db(DB_NAME, $oConn);
$result = xf_db_query("SHOW TABLES", $oConn);
while ($row=xf_db_fetch_array($result)) {
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
if (!xf_db_select_db(DB_NAME,$oConn)) {
$sRetVal = xf_db_error();
} else {
if (!$result = xf_db_query($sSQL, $oConn)) {
$sRetVal = xf_db_error();
} else {
$sRetVal = "<table border=1>\n";
$numFields = mysqli_num_fields($result);
$fieldInfo = mysqli_fetch_field_direct($result, 0);
$sRetVal .= "<tr><th colspan=" . $numFields . ">";
$sRetVal .= $fieldInfo->table . "</th></tr>";
$sRetVal .= "<tr>";
$i=0;
while ($i < $numFields) {
$fieldInfo = mysqli_fetch_field_direct($result, $i);
$sRetVal .= "<th>" . $fieldInfo->name . "</th>";
$i++;
}
$sRetVal .= "</tr>";
while ($line = xf_db_fetch_assoc($result)) {
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
if (!xf_db_select_db(DB_NAME,$oConn)) {
$sRetVal = xf_db_error();
} else {
if (!$result = xf_db_query($sSQL, $oConn)) {
$sRetVal = xf_db_error();
} else {
$fieldInfo = mysqli_fetch_field_direct($result, 0);
while ($line = xf_db_fetch_assoc($result)) {
$sRetVal = "\n<" . $fieldInfo->table . ">";
$iThisField = 0;
foreach ($line as $col_value) {
$oTMP = mysqli_fetch_field($result);
$sThisFieldName = $oTMP->name;
$sRetVal .= "\n\t<$sThisFieldName value=" . $col_value . ">";
$sRetVal .= "</$sThisFieldName>";
}
$sRetVal .= "\n</" . $fieldInfo->table . ">\n";
}
xf_db_free_result($result);
}  }
xf_db_close($oConn);
}
return ($sRetVal);
}


?>
