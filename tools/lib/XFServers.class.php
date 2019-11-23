<?php
class XFServers {
	private $servers;
	private $configFilePath;
	
	function servers() {
		if (!isset($this->servers)) {
			$this->servers = array();
			$configPath = $this->configFilePath();
			if (file_exists($configPath)) {
				$cfg = parse_ini_file($configPath, true);
				foreach ($cfg as $k=>$v) {
					if (is_array($v)) {
						$this->servers[] = new XFServer($this, $k, $v);
					}
				}
				
			}
		}
		return $this->servers;
	}
	
	function configFilePath() {
		if ($this->configFilePath) {
			return $this->configFilePath;
		}
		return $_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.xataface' . DIRECTORY_SEPARATOR . 'server-config.ini';
	}
	
	
}

class XFServer {
	private $servers;
	private $config;
	private $name;
	
	public function __construct(XFServers $servers, $name, $config) {
		$this->servers = $servers;
		$this->name = $name;
		$this->config = $config;
	}
	
	public function getConfigPath() {
		return $this->config['configPath'];
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getStartCommand() {
		return $this->config['startCommand'];
	}
	
	public function getStopCommand() {
		return $this->config['stopCommand'];
	}
	
	public function getRestartCommand() {
		return $this->config['restartCommand'];
	}
	
	public function getStatusCommand() {
		return $this->config['statusCommand'];
	}
	
	public function getMysqlCommand() {
		return $this->config['mysqlCommand'];
	}
	
	public function getDocRoot() {
		return $this->config['docRoot'];
	}
	
	public function getMysqlRootUser() {
		return $this->config['mysqlRootUser'];
	}
	
	public function getMysqlRootPassword() {
		return $this->config['mysqlRootPassword'];
	}
	
	public function restart() {
		$cmd = $this->getRestartCommand();
		if (!$cmd) {
			$start = $this->getStartCommand();
			$stop = $this->getStopCommand();
			if (!$start or !$stop) {
				throw new Exception("In order to restart server, either the server's restart command must be defined, or both the start and stop command must be defined.  To set these up, define either the restartCommand property in the {$this->getName()} section of the {$this->servers->configFilePath()} config file");
			}
			$cmd = $stop . ' && ' . $start;
		}
		
		passthru($cmd, $res);
		if ($res !== 0) {
			throw new Exception("Failed to restart the server.  Response code $res");
		}
		
	}
	
	private function fullMysqlCommand($user=null, $password=null) {
		$out = '';
		$pass = $user === null ? ($this->getMysqlRootPassword() or '') : ($password or '');
		$user = $user === null ? ($this->getMysqlRootUser() or '') : ($user or '');
		$cmd = $this->getMysqlCommand() or '';

		if ($pass) {
			$out . = 'MYSQL_PWD='.escapeshellarg($pass).' ';
		}
		$out .= $cmd;
		if ($user) {
			$out .= ' -u '.escapeshellarg($user);
		}
		return $out;
	}
	
	public function executeSQLFile($path, $user=null, $password=null) {
		if (!file_exists($path)) {
			throw new Exception("Attempt to execute SQL in file at path $path that doesn't exist");
		}
		$cmd = $this->fullMysqlCommand($user, $password);
		if (!$cmd) {
			throw new Exception("In order to execute SQL, the server's mysqlCommand property must be defined.  Set this up by adding a mysqlCommand directive to the {$this->getName()} section of the {$this->servers->configFilePath()} config file.");
		}
		passthru($cmd . ' < ' . escapeshellarg($path), $res);
		if ($res !== 0) {
			throw new Exception("Failed to execute SQL file.  Exit code $res.");
		}
	}
	
	public function executeSQLQuery($sql, $user=null, $password=null) {
		$cmd = $this->fullMysqlCommand($user, $password);
		if (!$cmd) {
			throw new Exception("In order to execute SQL, the server's mysqlCommand property must be defined.  Set this up by adding a mysqlCommand directive to the {$this->getName()} section of the {$this->servers->configFilePath()} config file.");
		}
		passthru($cmd . ' -e ' . escapeshellarg($sql), $res);
		if ($res !== 0) {
			throw new Exception("Failed to execute SQL query.  Exit code $res.");
		}
	}
	
	
	
	
}
	
class XFVirtualHost {
	private $server;
	private $name;
	private $aliases;
	private $docRoot;
	private $listen;
	
	function hasNameOrAlias($name) {
		if ($this->name == $name) {
			return true;
		}
		if ($this->aliases) {
			foreach ($this->aliases as $alias) {
				if ($alias == $name) {
					return true;
				}
			}
		}
		return false;
		
	}
	
	function isInConfigFile() {
		$found = false;
		if (!$this->name or !trim($this->name)) {
			return false;
		}
		if (self::findInFile($this->server, $this->name) !== null) {
			return true;
		}
		if ($this->aliases) {
			foreach ($this->aliases as $alias) {
				if (self::findInFile($this->server, $alias) !== null) {
					return true;
				}
			}
		}
		return false;
	}
		
	
	function appendToConfigFile() {
		if (!$this->name or !trim($this->name)) {
			throw new Exception("Cannot append virtual host definition to config file because the name is not set.");
		}
		if ($this->isInConfigFile()) {
			throw new Exception("Cannot append virtual host definition to config file because it already contains another virtual host with an overlapping ServerName or ServerAlias directive.");
		}
		$path = $this->server->getConfigPath();
		
		
	}
	
	static function findInFile(XFServer $server, $vhostServerName) {
		$path = $server->getConfigPath();
		if (!$path) {
			throw new Exception("No config path defined for server.  To add a config path, add a configPath directive to the {$server->getName()} section of the {$server->servers->configFilePath()} config file.");
		}
		if (!file_exists($path)) {
			throw new Exception("findInFile: path $path does not exist");
		}
		$lines = file($path);
		$state = 0;
		$vhost = null;
		foreach ($lines as $line) {
			$hashPos = strpos($line, '#');
			if ($hashPos !== false) {
				$line = substr($line, $hashPos);
			}
			$line = trim($line);
			if (!$line) {
				continue;
			}
			
			if ($state === 0) {
				// We are not currently in a VirtualHost definition
				if (preg_match('/<VirtualHost /', $line)) {
					$state = 1;
					$vhost = new XFVirtualHost($server, $vhostServerName);
		
				} 
				continue;
			}
			
			if ($state === 1) {
				if (preg_match('/^DocumentRoot (.*)/', $line, $matches)) {
					$vhost->docRoot = $matches[1];
					if ($vhost->docRoot and $vhost->docRoot{0} == '"') {
						$vhost->docRoot = str_replace('"', '', $vhost->docRoot);
					}
					continue;
				}
				if (preg_match('/^(ServerName|ServerAlias) (.*)/', $line, $matches)) {
					$serverNames = array_map('trim', explode(' ', $marches[1]);
					$vhost->aliases = $vhost->aliases or array();
					if ($vhost->name and strpos($line, 'ServerName') === 0) {
						$vhost->aliases[] = $vhost->name;
					}
					if (!$vhost->name) {
						$vhost->name = array_shift($serverName);
					}
					
					while (count($serverNames) > 0) {
						$vhost->aliases[] = array_shift($serverNames);
					}
					continue;
				}
				if (preg_match('#</VirtualHost>#', $line)) {
					if ($vhost->hasNameOrAlias($vhostServerName)) {
						return $vhost;
					}
					$vhost = null;
					$state = 0;
					continue;
					
				}
				
				
			}
				
		}
		return null;
	}
}	
	

	
?>