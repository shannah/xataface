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
$checks = array();

$dataface_path = dirname(__FILE__);

if ( is_writable($dataface_path.'/Dataface/templates_c') ){
	$installation_status = "INSTALLED CORRECTLY";
} else {
	$installation_status = "Installation Incomplete: Please make the Dataface/templates_c directory writable by the web server";
}

$version = file_get_contents($dataface_path.'/version.txt');





?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html><head>
<style type="text/css"><!--
body {background-color: #ffffff; color: #000000;}
body, td, th, h1, h2 {font-family: sans-serif;}
pre {margin: 0px; font-family: monospace;}
a:link {color: #000099; text-decoration: none; background-color: #ffffff;}
a:hover {text-decoration: underline;}
table {border-collapse: collapse;}
.center {text-align: center;}
.center table { margin-left: auto; margin-right: auto; text-align: left;}
.center th { text-align: center !important; }
td, th { border: 1px solid #000000; font-size: 75%; vertical-align: baseline;}
h1 {font-size: 150%;}
h2 {font-size: 125%;}
.p {text-align: left;}
.e {background-color: #ccccff; font-weight: bold; color: #000000;}
.h {background-color: #9999cc; font-weight: bold; color: #000000;}
.v {background-color: #cccccc; color: #000000;}
.vr {background-color: #cccccc; text-align: right; color: #000000;}
img {float: right; border: 0px;}
hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000000;}
//--></style>
<title>dataface_info()</title></head>
<body><div class="center">
<table border="0" cellpadding="3" width="600">
<tr class="h"><td>
<h1 class="p">Dataface <?php echo $version;?></h1>
<h2 class="p">Installed at <?php echo dirname($_SERVER['REQUEST_URI']); ?></h2>
</td></tr>
</table><br />
<table border="0" cellpadding="3" width="600">
<tr><td class="e">Installation status </td><td class="v"><?php echo $installation_status;?> </td></tr>
<tr><td class="e">Templates Dir</td><td class="v"><?php echo $dataface_path.'/Dataface/templates';?> </td></tr>

<tr><td class="e">Templates Compile Dir </td><td class="v"><?php echo $dataface_path.'/Dataface/templates_c';?></td></tr>

</table><br />

<h2>Dataface License</h2>
<table border="0" cellpadding="3" width="600">
<tr class="v"><td>
 <p>
 Xataface Web Application Framework<br>
 Copyright (C) 2005-2007  Steve Hannah, Web Lite Solutions Corporation
 </p>
 <p>
 This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.
 </p>
 <p>
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 </p>
 <p>
 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
  </p>
</td></tr>
</table><br />
</div></body></html>
