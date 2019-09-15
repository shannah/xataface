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

$d = dir(dirname(__FILE__));
$tests = array();
while ( false !== ($entry = $d->read()) ){
	if ( preg_match('/run_(.*)\.php/', $entry) ){
		$tests[] = $entry;
	}
}
$d->close();
?>
<html>
<head><title>Dataface Tests</title></head>
<body>
	<p>Click on the links below to perform the tests</p>
	<ul>
	<?php
	foreach ( $tests as $test ){
		?><li><a href="<?=$test?>"><?=$test?></a></li>
		<?php
	}
	?>
	<li><a href="selenium/TestRunner.html?test=../Sel_TestSuite.htm">Web Tests (Using Selenium)</a></li>
	</ul>
</body>
</html>
