<?php
require_once 'PHPUnit.php';

class ServicesTest extends PHPUnit_TestCase {
	
	private $serviceManager;
	private $servicesFilePath = '.test-services.json';
	private $xatafacePath;
	
	function ServicesTest($name = 'ServicesTest') {
		$this->PHPUnit_TestCase($name);

	}
	
	public function __construct($name = 'ServicesTest') {
		$this->ServicesTest($name);
	}
	

	
    function setUp() {
		$xatafacePath = getenv('XATAFACE', true);
		$this->xatafacePath = $xatafacePath;
		if (!$xatafacePath) {
			throw new Exception("Xataface path not set.  Please set XATAFACE environment varable to point to xataface directory");
		}
		require_once $xatafacePath . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'XFServices.class.php';
		
		$this->serviceManager = new XFServiceManager();
		$this->servicesFilePath = '.test-services.json';
		if (file_exists($this->servicesFilePath)) {
			unlink($this->servicesFilePath);
		}
		
		$this->serviceManager->setServicesFilePath('.test-services.json');
		
	}
	
	
	function test_XFService_toArray() {
		$props = array(
			'appPath' => dirname(__FILE__),
			'name' => 'mysql'
		);
		$service = new XFService($props);
		$arr = $service->toArray();
		$this->assertEquals($props['appPath'], $arr['appPath']);
		$this->assertEquals($props['name'], $arr['name']);

		
	}
	
	function test_XFService_isSameApp() {
		$props = array(
			'appPath' => dirname(__FILE__),
			'name' => 'mysql'
		);
		$service1 = new XFService($props);
		$service2 = new XFService($props);
		$this->assertTrue($service1->isSameApp($service2));
		$this->assertTrue($service2->isSameApp($service1));
		$this->assertTrue($service2->isSameApp($service2));
		
		$props['port'] = 3307;
		$service3 = new XFService($props);
		$this->assertTrue($service1->isSameApp($service3));
		
		$props['name'] = 'apache';
		$service4 = new XFService($props);
		$this->assertTrue($service1->isSameApp($service4));
		$props['appPath'] .= '1';
		$service5 = new XFService($props);
		$this->assertTrue(!$service1->isSameApp($service5));
		
	}
	
	function test_XFService_status() {
		$props = array(
			'appPath' => dirname(dirname(__FILE__)),
			'name' => 'mysql'
		);
		$service1 = new XFService($props);
		$status = $service1->getStatus();
		$this->assertEquals('RUNNING', $status);
		
		// Let's create a new app
		$servicesDir = 'tmpServices';
		@mkdir($servicesDir);
		
		$app1 = $servicesDir . DIRECTORY_SEPARATOR . 'app1';
		$createScript = $this->xatafacePath . DIRECTORY_SEPARATOR . 'tools' . DIRECTORY_SEPARATOR . 'create.php';
		exec("php ".escapeshellarg($createScript) . ' ' . escapeshellarg($app1), $buffer, $res);
		if ($res !== 0) {
			throw new Exception("Failed to create app at ".$app1."  Response code $res.\n");
		}
		try {
			$app1MysqlService = new XFService(array(
				'appPath' => realpath($app1),
				'name' => 'mysql'
			));
			$this->assertEquals('STOPPED', $app1MysqlService->getStatus());
			exec("sh ".escapeshellarg($app1.'/bin/mysql.server.sh').' start', $buffer, $res);
			if ($res !== 0) {
				throw new Exception("Failed to start mysql server for app at $app1.\n");
			}
			$this->assertEquals('RUNNING', $app1MysqlService->getStatus());
			exec("sh ".escapeshellarg($app1.'/bin/mysql.server.sh').' stop', $buffer, $res);
			if ($res !== 0) {
				throw new Exception("Failed to stop mysql server for app at $app1.\n");
			}
			$this->assertEquals('STOPPED', $app1MysqlService->getStatus());
			
			$app1HttpdService = new XFService(array(
				'appPath' => realpath($app1),
				'name' => 'httpd'
			));
			$this->assertEquals('STOPPED', $app1HttpdService->getStatus());
			
			passthru("sh ".escapeshellarg($app1.'/bin/apachectl.sh').' start', $res);
			if ($res !== 0) {
				throw new Exception("Failed to start httpd.  Response code $res\n");
			}
			$this->assertEquals('RUNNING', $app1HttpdService->getStatus());
			passthru("sh ".escapeshellarg($app1.'/bin/apachectl.sh').' stop', $res);
			if ($res !== 0) {
				throw new Exception("Failed to stop httpd.  Response code $res\n");
			}
			$this->assertEquals('STOPPED', $app1HttpdService->getStatus());

			$this->assertEquals(null, $app1HttpdService->getRunningPort());
			$this->assertEquals(9090, $app1HttpdService->getConfigPort());
			$this->assertEquals(9090, $app1HttpdService->getPort());
			$res = $app1HttpdService->start();
			$this->assertTrue($res, "Failed to start http service with start()");
			$this->assertEquals('RUNNING', $app1HttpdService->getStatus());
			$this->assertEquals(9090, $app1HttpdService->getRunningPort());
			$this->assertEquals(9090, $app1HttpdService->getConfigPort());
			$this->assertEquals(9090, $app1HttpdService->getPort());
			$this->assertTrue($app1HttpdService->stop(), "Failed to stop httpd service");
			$this->assertEquals('STOPPED', $app1HttpdService->getStatus());
			$this->assertEquals(null, $app1HttpdService->getRunningPort());
			$this->assertEquals(9090, $app1HttpdService->getConfigPort());
			$this->assertEquals(9090, $app1HttpdService->getPort());



			
		} finally {
			// Cleanup app 1
			exec("sh ".escapeshellarg($app1.'/bin/mysql.server.sh').' stop', $buffer, $res);
			passthru("sh ".escapeshellarg($app1.'/bin/apachectl.sh').' stop', $res);
			//exec("rm -rf ".escapeshellarg($app1), $buffer, $res);
		}
		
		
		
	}
	
	function test_XFServiceManager_services() {
		$props = array(
			'appPath' => dirname(dirname(__FILE__)),
			'name' => 'mysql'
		);
		$service1 = new XFService($props);
		$this->assertEquals(0, count($this->serviceManager->services()));
		$this->serviceManager->add($service1);
		$this->assertEquals(1, count($this->serviceManager->services()));
		$this->assertEquals(1, count($this->serviceManager->getRunningServices()));
		$this->serviceManager->remove($service1);
		$this->assertEquals(0, count($this->serviceManager->services()));
		
		$this->serviceManager->revert();
		$this->assertEquals(0, count($this->serviceManager->services()));
		$this->serviceManager->add($service1);
		$this->assertEquals(1, count($this->serviceManager->services()));
		$this->serviceManager->save();
		$this->serviceManager->revert();
		$this->assertEquals(1, count($this->serviceManager->services()));
		
	}
	
	function tearDown() {
		if (file_exists($this->servicesFilePath)) {
			unlink($this->servicesFilePath);
		}
	}
	
	
	
}
