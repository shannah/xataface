<?php
ini_set('display_errors', 'on');
error_reporting(E_ALL);

if (php_sapi_name() != "cli") {
    fwrite(STDERR, "CLI ONLY");
    exit(1);
}

class Xataface_Tools_install_pkg {
	private $argv;
	
	function __construct($argv) {
		$this->argv = $argv;
	}
	function run() {
		if (count($this->argv) < 2) {
			fwrite(STDERR, "Missing parameters\n");
			$this->help();
			exit(1);
		}
		
		$pkgPath = $this->argv[1];
		if (!file_exists($pkgPath)) {
			fwrite(STDERR, "Package $pkgPath not found\n");
			$this->help();
			exit(1);
		}
		$pkgName = basename($pkgPath);
		if (!preg_match('/\.xfpkg$/', $pkgName)) {
			fwrite(STDERR, "Package '$pkgPath' missing .xfpkg extension.\n");
			$this->help();
			exit(1);
		}
		
		$pkgBase = preg_replace('/\.xfpkg$/', '', $pkgName);
		
		$destDir = $pkgBase;
		if (count($this->argv) > 2) {
			$destDir = $this->argv[2];
		}
		if (file_exists($destDir)) {
			fwrite(STDERR, "Destination $destDir already exists.  Please specify a destination that doesn't yet exist.\n");
			$this->help();
			exit(1);
		}
		
	}
	
	function help() {
		echo "Usage: xataface install-pkg /path/to/package.xfpkg [dest]\n";
	}
	
}
