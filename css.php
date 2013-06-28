<?php
if ( !defined('DATAFACE_SITE_PATH') ) die("Cannot be called directly");
if ( !$_GET['--id'] ) die("No id specified");
$path = DATAFACE_SITE_PATH.DIRECTORY_SEPARATOR.'templates_c'.DIRECTORY_SEPARATOR.basename($_GET['--id']).'.css';
if ( !file_exists($path) ){
	dir("File could not be found");
}
// seconds, minutes, hours, days
//ob_start("ob_gzhandler");  //Removed due to PHP BUG 55544
ob_start();
$expires = 60*60*24*14;
header("Pragma: public");
header("Cache-Control: maxage=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
header('Content-type: text/css');
echo file_get_contents($path);
exit;

