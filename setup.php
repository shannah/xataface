#!/usr/bin/php
<?php
if ( isset( $_SERVER['REQUEST_URI'] ) ){
	die("Cannot access this file through the browser");
}


// make the template directories writable
$dataface_path = dirname(__FILE__);

$cache_dirs = array();
$cache_dirs['templates_c'] = $dataface_path.'/Dataface/templates_c';
$cache_dirs['phpThumbCache'] = $dataface_path.'/lib/phpThumb/cache';

$cache_dirs['phpThumbSourceCache'] = $cache_dirs['phpThumbCache'].'/source';


foreach (array_keys($cache_dirs)  as $key ){
	fwrite(STDOUT, "Making ".$cache_dirs[$key]." writable globally...\n");
	chmod( $cache_dirs[$key], 0777);
	
}

fwrite(STDOUT, "Setup completed\n");
