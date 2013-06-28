<?php
/********************************************************************************
 *
 *  Xataface Web Application Framework for PHP and MySQL
 *  Copyright (C) 2005  Steve Hannah <shannah@sfu.ca>
 *  
 *  This library is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU Lesser General Public
 *  License as published by the Free Software Foundation; either
 *  version 2.1 of the License, or (at your option) any later version.
 *  
 *  This library is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *  Lesser General Public License for more details.
 *  
 *  You should have received a copy of the GNU Lesser General Public
 *  License along with this library; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *===============================================================================
 */

//ini_set('include_path', '..:../lib:'.ini_get('include_path'));
//
define('DB_HOST', 'localhost');
define('DB_NAME', 'test_contentmanager');
define('DB_USER', 'tester');
define('DB_PASSWORD', 'test');
mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
@mysql_query("create database `".DB_NAME."`");

/**
 * The URL of the testApp.php file which contains the test web application.
 */
define('TEST_APP_URL', 'http://powerbook.local/~shannah/dataface/tests/testApp.php');

// The path to the database installation (from the Document root)
$dataface_url = '/~shannah/dataface';

require_once '../dataface-public-api.php';
df_init(__FILE__, $dataface_url);
require_once 'Dataface/Application.php';



?>
