<?php
if ( !defined('DATAFACE_SITE_PATH') ) die("Cannot be called directly");
if ( !$_GET['--id'] ) die("No id specified");
$path = DATAFACE_SITE_PATH.DIRECTORY_SEPARATOR.'templates_c'.DIRECTORY_SEPARATOR.basename($_GET['--id']).'.js';
if ( !file_exists($path) ){
	dir("File could not be found");
}
// seconds, minutes, hours, days
//ob_start("ob_gzhandler");  //Removed due to PHP BUG 55544
ob_start();
$expires = 60*60*24*14;
session_cache_limiter('public');
header("Pragma: public", true);
header("Cache-Control:max-age=".$expires.', public, s-maxage='.$expires, true);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT', true);
header('Content-type: text/javascript', true);
//header('Content-Length: '.strlen($out), true);
header('Connection: close', true);
echo file_get_contents($path);


exit;

