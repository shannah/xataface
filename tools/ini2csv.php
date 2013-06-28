<?php
/**
 * This script is meant to be run on a series of language INI files to convert them
 * to a CSV file allowing translations to be done in a spreadsheet application.
 *
 * usage:
 * php ini2csv *.ini out.csv
 * php ini2csv en.ini fr.ini zh.ini out.csv
 * php ini2csv /path/to/lang/*.ini out.csv
 *
 * Copyright 2009 Steve Hannah.  All Rights Reserved.
 */
define('_Q','"');
define('XATAFACEQ', '"');
function is_utf8($str) {
	if ($str === mb_convert_encoding(mb_convert_encoding($str, "UTF-32", "UTF-8"), "UTF-8", "UTF-32")) {
		return true;
	} else {
		return false;
	}
}

function encode_if_necessary($str){
	if ( !is_utf8($str) ) return utf8_encode($str);
	else return $str;
}

$dicts = array();
$files = array();
$out = 'translations.csv';

foreach ( $argv as $file ){
 	if ( preg_match('/\.csv$/', $file) ){
		$out = $file;
		continue;
	}
	if ( !preg_match('/\.ini$/', $file ) )continue;
	
	$files[] = $file;
	$dicts[$file] = parse_ini_file($file);
}


$keys = array();
foreach ($dicts as $dict){
	foreach ($dict as $key=>$val){
		if ( !isset($keys[$key]) ) $keys[$key] = $key;
	}
}

$fp = fopen($out, 'wb');
$fields = $files;
array_unshift($fields, 'Key');
$fields = array_map('encode_if_necessary', $fields);

fputcsv($fp, $fields);
foreach ($keys as $key){
	$row = array($key);
	foreach ($files as $file){
		if ( isset($dicts[$file][$key]) ) $row[] = $dicts[$file][$key];
		else $row[] = '';
	}
	$row = array_map('encode_if_necessary', $row);
	fputcsv($fp, $row);
}
fclose($fp);
echo "Translations saved in file $out\n";

