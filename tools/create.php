<?php
ini_set('display_errors', 'on');
ini_set('memory_limit', '2048M');
error_reporting(E_ALL);

if (php_sapi_name() != "cli") {
    fwrite(STDERR, "CLI ONLY");
    exit(1);
}
require_once dirname(__FILE__).'/lib/XFProject.class.php';

function help() {

}


function extract_flags($args) {
    $out = array();
    foreach ($args as $arg) {
        if ($arg and $arg[0] == '-') {
            if (($pos = strpos($arg, '=')) !== false) {
                $out[substr($arg, 1, $pos)] = substr($arg, $pos+1);
            } else {
                $out[substr($arg, 1)] = substr($arg, 1);
            }
        }
    }
    return $out;
}
function strip_flags($args) {
    $out = array();
    foreach ($args as $arg) {
        if ($arg and $arg[0] != '-') {
            $out[] = $arg;
        }
    }
    return $out;
}
function get_bootstrap_sql($args) {
    $out = [];
    foreach ($args as $arg) {
        if (strstr($arg, ".sql") === ".sql") {
            if (file_exists($arg)) {
                $out[] = $arg;
            }
        }
    }
    return $out;
}

function xf_create_run($argv) {
	$flags = extract_flags($argv);
	$argv = strip_flags($argv);
	if (count(@$argv) < 2) {
	    help();
	    exit(1);
	}
	$p = $argv[1];
	echo "Create project at {$p}\n";
	$proj = new XFProject($p);
	if (@$flags['db.name']) {
	    $proj->dbName = $flags['db.name'];
	}
    $proj->bootstrapSqlFiles = get_bootstrap_sql($argv);
    
	$proj->create_scaffold();
}
if (@$argv) {
	xf_create_run($argv);
}

