<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp (shannah@sfu.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */
if ( !isset( $_REQUEST['-field'] ) ) die("Could not complete request.  No field name specified.");
if ( !isset( $_REQUEST['-table'] ) ) die("Could not complete request.  No table specified.");

require_once 'Dataface/Application.php';
$app =& Dataface_Application::getInstance();
$fieldname = $_REQUEST['-field'];
$tablename = $_REQUEST['-table'];
$table =& Dataface_Table::loadTable($tablename);

if ( !$table->isBlob($fieldname) ) die("blob.php can only be used to load BLOB or Binary columns.  The requested field '$fieldname' is not a blob");
$field =& $table->getField($fieldname);
print_r($field); exit;
if ( isset($_REQUEST['-index']) ) $index = $_REQUEST['-index'];
else $index = 0;
$queryTool =& Dataface_QueryTool::loadResult($tablename, null, $_REQUEST);
$mimetype = $field['mimetype'];
$columns = array($fieldname, $mimetype);
$queryTool->loadCurrent($columns, true, true);
//echo $mimetype;
//echo $table->getValue($mimetype);
header("Content-type: ".$table->getValue($mimetype, $index));
//echo "Here";
echo $table->getValue($fieldname, $index);


//$res = mysql_query("select small_image from unit_plans limit 1", $app->_db);
//list($val2) = mysql_fetch_row($res);
//echo "Len 1: ".strlen($val);
//echo "Len 2: ".strlen($val2);
//if ( $val === $val2 ) echo "They are the same";

