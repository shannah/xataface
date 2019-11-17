<?php
ini_set('display_errors', 'on');
error_reporting(E_ALL);

if (php_sapi_name() != "cli") {
    fwrite(STDERR, "CLI ONLY");
    exit(1);
}
require_once dirname(__FILE__).'/lib/XFProject.class.php';

class XFProject_AppCtl {
	var $argv;
	function __construct($argv) {
		$this->argv = $argv;
	}
	
	function run() {
		$base_dir = $this->find_base_dir(getcwd());
		if ($base_dir === null) {
			fwrite(STDERR, "You are not in a Xataface application\n");
			exit(1);
		}
		$appctl = $base_dir . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'appctl.sh';
		if (!file_exists($appctl)) {
			fwrite(STDERR, "Cannot find $appctl");
			exit(1);
		}
		
		$args = '';
		if (count($this->argv) > 1) {
			foreach (array_slice($this->argv, 1) as $arg) {
				$args .= ' '.escapeshellarg($arg);
			}
		}
		$args = trim($args);
		
		
		passthru("bash ".escapeshellarg($appctl)." ".$args, $res);
		exit($res);
		
	}
	
	function find_base_dir($start = null) {
		if (!$start) {
			$start = getcwd();
		}
		$appctl = $start . DIRECTORY_SEPARATOR . 'appctl.sh';
		if (file_exists($appctl)) {
			return dirname(dirname($appctl));
		}
		$appctl = $start . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'appctl.sh';
		if (file_exists($appctl)) {
			return $start;
		}
		$parent = dirname($start);
		if (!$parent) {
			$parent = dirname(realpath($start));
		}
		if (!$parent) {
			return null;
		}
		return $this->find_base_dir($parent);
	}
	
	
}

if (@$argv) {
	$start = new XFProject_AppCtl($argv);
	$start->run();
}