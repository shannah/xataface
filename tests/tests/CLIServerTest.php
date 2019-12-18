<?php
require_once 'PHPUnit.php';


class CLIServerTest extends PHPUnit_TestCase {
	
	private $xfServers;
	private $server;
	private $configFilePath = '.test-servers-config.ini';
	private $prefsPath = '.test-servers-prefs.json';
	private $xatafacePath;
	private $serverRunning;
	
	function CLIServerTest($name = 'CLIServerTest') {
		$this->PHPUnit_TestCase($name);
		echo "WARNING: The CLIServerTest needs to run some scripts with sudo.  Only run this test if you have sudo permissions, and you are OK with starting and stopping the web server.\n";

	}
	
	public function __construct($name = 'CLIServerTest') {
		$this->CLIServerTest($name);
	}
	

	
    function setUp() {
		$xatafacePath = getenv('XATAFACE', true);
		$this->xatafacePath = $xatafacePath;
		if (!$xatafacePath) {
			throw new Exception("Xataface path not set.  Please set XATAFACE environment varable to point to xataface directory");
		}
		require_once $xatafacePath . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'XFServers.class.php';
		
		$this->xfServers = new XFServers();
		
		$this->configFilePath = '.test-servers-config.ini';
		if (file_exists($this->configFilePath)) {
			unlink($this->configFilePath);
		}
		$prefsPath = $this->prefsPath;
		if (file_exists($prefsPath)) {
			unlink($prefsPath);
		}
		$this->xfServers->setPreferencesPath($prefsPath);
		
		$httpdConf = 'server-httpd.conf';
		if (file_exists($httpdConf)) {
			unlink($httpdConf);
		}
		touch($httpdConf);
		
		$this->xfServers->setConfigFilePath($this->configFilePath);
		
		
		$this->server = new XFServer($this->xfServers, 'default', array(
			'startCommand' => 'sudo apachectl start',
			'stopCommand' => 'sudo apachectl stop',
			'restartCommand' => 'sudo apachectl restart',
			'statusCommand' => 'sudo apachectl status',
			'mysqlCommand' => 'mysql',
			'mysqlRootUser' => 'root',
			'mysqlRootPassword' => '',
			'configPath' => $httpdConf
		));
		
		$this->serverRunning = $this->server->isRunning();
		
		
		$configFileContents = 
<<<END
[default]
startCommand=sudo apachectl start
stopCommand=sudo apachectl stop
restartCommand=sudo apachectl restart
statusCommand=sudo apachectl status
mysqlCommand=mysql
mysqlRootUser=root
mysqlRootPassword=
configPath={$httpdConf}

END;
		file_put_contents($this->configFilePath, $configFileContents);
		
	}
	
	function testPrefsPath() {
		$this->xfServers->setPreferencesPath(null);
		$this->assertEquals($_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.xataface' . DIRECTORY_SEPARATOR . 'server-prefs.json', $this->xfServers->preferencesPath());
		$this->xfServers->setPreferencesPath($this->prefsPath);
		$this->assertEquals($this->prefsPath, $this->xfServers->preferencesPath());
	}
	
	function testIsRunning() {
		
		if ($this->server->isRunning()) {
			echo "Server running.  Stopping to start unit tests.\n";
			$this->server->stop();
		}
		$this->assertTrue(!$this->server->isRunning(), "The server should be stopped");
		echo "Trying to start server...\n";
		$this->server->start();
		$this->assertTrue($this->server->isRunning(), "The server should be running after start()");
		echo "Trying to stop server...\n";
		$this->server->stop();
		$this->assertTrue(!$this->server->isRunning(), "The server should be stopped after stop()");
		
	}
	
	function testRestart() {
		echo "Testing restart...\n";
		if ($this->server->isRunning()) {
			echo "Server running.  Stopping to start unit tests.\n";
			$this->server->stop();
		}
		$this->assertTrue(!$this->server->isRunning(), "The server should be stopped");
		$this->server->restart();
		$this->assertTrue($this->server->isRunning(), "Server should be started after restart()");
		$this->server->restart();
		$this->assertTrue($this->server->isRunning(), "Server should still be running after restart()");
		
		
	}
	
	function testSQLQuery() {
		$result = $this->server->executeSQLQuery("show databases");
		$this->assertTrue(count($result) >= 2, "There should be at least 2 databases in the mysql server");
		$this->assertTrue(in_array('information_schema', $result), 'information_schema should one of the databases.');
	}
	
	function testSQLFile() {
		file_put_contents("query.sql", "show databases");
		$result = $this->server->executeSQLFile("query.sql");
		$this->assertTrue(count($result) >= 2, "There should be at least 2 databases in the mysql server");
		$this->assertTrue(in_array('information_schema', $result), 'information_schema should one of the databases.');
	}
	
	function testLoadVirtualHosts() {
		$configPath = $this->server->getConfigPath();
		file_put_contents($configPath, 
<<<END
<VirtualHost *:80>
    DocumentRoot "/www/example2"
    ServerName www.example.org

    # Other directives here
</VirtualHost>
END
		);
		
		$vhosts = $this->server->loadVirtualHosts();
		$this->assertEquals(0, count($vhosts), "None of the virtual hosts have XATAFACE marker, so we shouldn't receive any when we load virtual hosts.");
			
		file_put_contents($configPath, 
<<<END
<VirtualHost *:80>
    DocumentRoot "/www/example2"
    ServerName www.example.org
	#XATAFACE#
    # Other directives here
</VirtualHost>
END
		);
			
		$vhosts = $this->server->loadVirtualHosts();
		$this->assertEquals(1, count($vhosts), "Didn't find virtual host");
		$h1 = $vhosts[0];
		
		$this->assertEquals("/www/example2", $h1->getDocRoot());
		$this->assertEquals("*", "{$h1->getAddress()}");
		$this->assertEquals("80", "{$h1->getPort()}");
		$this->assertEquals("www.example.org", $h1->getName());
		$expectedRaw = 
<<<END
<VirtualHost *:80>
    DocumentRoot "/www/example2"
    ServerName www.example.org
	#XATAFACE#
    # Other directives here
</VirtualHost>
END;
		$this->assertEquals($expectedRaw, $h1->getRaw());
		$this->assertEquals(0, $h1->getStartLine());
		$this->assertEquals(6, $h1->getEndLine());
		$this->assertEquals(
<<<END
<VirtualHost *:80>
    DocumentRoot "/www/example2"
    ServerName www.example.org
	#XATAFACE#
    # Other directives here
</VirtualHost>
END,
		$h1->toString()
		);
		

	}
	
	function testSetServer() {
		$this->assertEquals(null, $this->xfServers->getCurrentServer());
		$this->xfServers->setCurrentServer('notexists');
		$this->assertEquals(null, $this->xfServers->getCurrentServer());
		$this->xfServers->setCurrentServer(null);
		$this->assertEquals(null, $this->xfServers->getCurrentServer());
		$this->xfServers->setCurrentServer('default');
		$this->assertEquals('default', $this->xfServers->getCurrentServer());
		$server = $this->xfServers->getServerByName($this->xfServers->getCurrentServer());
		$this->assertEquals('default', $server->getName());
	}
	
	function tearDown() {
		if ($this->serverRunning and !$this->server->isRunning()) {
			echo "Starting server again...\n";
			$this->server->start();
		} else if (!$this->serverRunning and $this->server->isRunning()) {
			echo "Shutting down server...\n";
			$this->server->stop();
		}
		if (file_exists($this->configFilePath)) {
			unlink($this->configFilePath);
		}
	}
	
	
	
}
