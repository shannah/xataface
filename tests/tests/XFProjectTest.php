<?php
require_once 'PHPUnit.php';

class XFProjectTest extends PHPUnit_TestCase {
	
	private $project;
	
	function XFProjectTest($name = 'XFProjectTest') {
		$this->PHPUnit_TestCase($name);

	}
	
	public function __construct($name = 'XFProjectTest') {
		$this->XFProjectTest($name);
	}
	

	
    function setUp() {
		$xatafacePath = getenv('XATAFACE', true);
		$this->xatafacePath = $xatafacePath;
		if (!$xatafacePath) {
			throw new Exception("Xataface path not set.  Please set XATAFACE environment varable to point to xataface directory");
		}
		require_once $xatafacePath . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'XFProject.class.php';
		require_once $xatafacePath . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'XFServers.class.php';
		$baseDir = dirname(dirname(__FILE__));
		if (!file_exists($baseDir . DIRECTORY_SEPARATOR . 'www')) {
			throw new Exception("XFProjectTest cannot be run in this context.  This file must be in root of Xataface app");
		}
		if (!file_exists($baseDir . DIRECTORY_SEPARATOR . 'bin')) {
			throw new Exception("XFProjectTest cannot be run in this context.  This file must be in root of Xataface app");
		}
		if (!file_exists($baseDir . DIRECTORY_SEPARATOR . 'etc')) {
			throw new Exception("XFProjectTest cannot be run in this context.  This file must be in root of Xataface app");
		}
		if (!file_exists($baseDir . DIRECTORY_SEPARATOR . 'tmp')) {
			throw new Exception("XFProjectTest cannot be run in this context.  This file must be in root of Xataface app");
		}
		$this->project = new XFProject($baseDir);
		
		
	}
	
	function test_get_config_var() {
		$this->assertEquals('testapp', $this->project->get_config_var('_database.name'));
		
		
	}
	
	function test_mysqldump() {
		$file = 'dump.sql';
		if (file_exists($file)) {
			unlink($file);
		}
		$this->project->mysqldump($file);
		$this->assertTrue(file_exists($file));
		$contents = file_get_contents($file);
		$this->assertTrue(strpos($contents, 'DROP TABLE IF EXISTS `test`') !== false, 'Dump file missing DROP TABLE call');
	}
	
	function test_installOnServer() {
		$xamppPath = '/Applications/XAMPP';
		if (!file_exists($xamppPath)) {
			echo "Skipping test_installOnServer because XAMPP could not be found\n";
			return;
		}
		$dbName = $this->project->get_config_var('_database.name');
		if ($dbName !== 'testapp') {
			echo "Skipping test_installOnServer because the current app is not named testapp\n";
			return;
		}
		$dbUser = $this->project->get_config_var('_database.user');
		if ($dbUser !== 'testapp') {
			echo "Skipping test_installOnServer because the current  app user is not testapp\n";
			return;
		}
		
		$httpdConf = 'server-httpd.conf';
		if (file_exists($httpdConf)) {
			unlink($httpdConf);
		}
		touch($httpdConf);
		$servers = new XFServers();
		
		$server = new XFServer($servers, 'default', array(
			'startCommand' => 'sudo apachectl start',
			'stopCommand' => 'sudo apachectl stop',
			'restartCommand' => 'sudo apachectl restart',
			'statusCommand' => 'sudo apachectl status',
			'mysqlCommand' => 'mysql',
			'mysqlRootUser' => 'root',
			'mysqlRootPassword' => '',
			'configPath' => $httpdConf
		));
		if ($server->databaseUserExists($dbUser)) {
			$server->dropDatabaseUser($dbUser);
		}
		
		$server->executeSQLQuery("DROP DATABASE `testapp`");
		
		$this->project->installOnServer($server);
		
		
		
		
		
		
	}
	
	
	
	function tearDown() {
		
	}
	
	
	
}
