<?php
/**
 * Service manager class. Can be used to start/stop
 * Apache and MySQL services associated with xataface
 * apps.
 */
class XFServiceManager {
	private $services;
	private $serviceFilePath;
	
	public function setServicesFilePath($path) {
		$this->servicesFilePath = $path;
	}
	
	public function services() {
		if (!isset($this->services)) {
			$this->services = array();
			if (is_readable($this->servicesFilePath())) {
				$tmp = json_decode(file_get_contents($this->servicesFilePath()), true);
				foreach ($tmp as $row) {
					$svc = new XFService($row);
					if ($svc->exists()) {
						$this->services[] = new XFService($row);
					}
					
				}
			}
		}
		return $this->services;
	}
	
	public function getRunningServices() {
		$tmp = array();
		foreach ($this->services() as $svc) {
			if ($this->isRunning($svc)) {
				$tmp[] = $svc;
			}
		}
		return $tmp;
	}
	
	
	public function isRunning(XFService $service) {
		return strcasecmp($service->getStatus(), 'RUNNING') === 0;
	}
	
	public function add(XFService $service) {
		
		$this->services[] = $service;
		
	}
	
	public function remove(XFService $service) {
		
		$tmp = array();
		foreach ($this->services as $svc) {
			if (!$svc->equals($service)) {
				$tmp[] = $svc;
			}
		}
		$this->services = $tmp;
		
	}
	
	public function revert() {
		$this->services = null;
	}
	
	public function save() {
		$tmp = array();
		foreach ($this->services() as $svc) {
			$tmp[] = $svc->toArray();
		}
		$res = file_put_contents($this->servicesFilePath, json_encode($tmp), LOCK_EX);
		return $res !== false;
	}
	
	private function servicesFilePath() {
		if ($this->servicesFilePath) {
			return $this->servicesFilePath;
		}
		return $_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.xataface' . DIRECTORY_SEPARATOR . 'services.json';
	}
}

class XFService {
	private $appPath;
	private $name;
	
	public function __construct(array $service) {
		$this->appPath = $service['appPath'];
		$this->name = $service['name'];
	}
	
	public function toArray() {
		return array(
			'appPath' => $this->appPath,
			'name' => $this->name
		);
	}
	
	public function exists() {
		return file_exists($this->appPath) and is_dir($this->appPath)
			and file_exists($this->appPath . DIRECTORY_SEPARATOR . 'bin')
				and is_dir($this->appPath . DIRECTORY_SEPARATOR . 'bin')
					and file_exists($this->appPath . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'apachectl.sh');
	}
	
	public function start() {
		
	}
	
	public function stop() {
		
	}
	
	public function isSameApp(XFService $svc) {
		return $svc->appPath === $this->appPath;
	}
	
	public function equals(XFService $svc) {

		return $svc->appPath === $this->appPath and
			$svc->name === $this->name;

		
	}
	
	private function getMysqlServerScriptPath() {
		return $this->appPath . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysql.server.sh';
	}
	
	private function getApachectlPath() {
		return $this->appPath . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'apachectl.sh';
	}
	
	public function getStatus() {
		if ($this->name == 'mysql') {
			exec("sh ".escapeshellarg($this->getMysqlServerScriptPath())." status", $buffer, $res);
			return $res === 0 ? 'RUNNING' : 'STOPPED';
			
		}
		if ($this->name == 'httpd') {
			exec("sh ".escapeshellarg($this->getApacheCtlPath())." status", $buffer, $res);
			return $res === 0 ? 'RUNNING' : 'STOPPED';
			
		}
		throw new Exception("No getStatus implementation yet or this service type ".$this->name);
	}
	
	public function getName() {
		return $this->name;
	}
	
	
}