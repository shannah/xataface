<?php
if (!@$argv) {
	die('cli only');
}
$getServerRoot = count($argv) > 1 and $argv[1] == 'ServerRoot' ? true : false;
$files = array();
if (getenv('XATAFACE_HTTPD')) {
	$files[] = getenv('XATAFACE_HTTPD');
}
$files[] = '/Applications/XAMPP/xamppfiles/bin/httpd';
$files[] = '/opt/lampp/bin/httpd';
$files[] = '/usr/local/bin/httpd';
$files[] = '/usr/local/sbin/httpd';
$files[] = '/usr/bin/httpd';
$files[] = '/usr/sbin/http';

foreach ($files as $file) {
	if (file_exists($file)) {
		if ($getServerRoot) {
			echo dirname(dirname($file));
		} else {
			echo $file;
		}
		exit;
	}
}
echo '/usr/local/bin/http';