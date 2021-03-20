<?php
class XFServers {
	private $servers;
	private $configFilePath;
	private $preferencesPath;
	private $preferences;
	
	function preferences() {
		if (!$this->preferences) {
			$file = $this->preferencesPath();
			if (!file_exists($file)) {
				$this->preferences = array();
			} else {
				$this->preferences = json_decode(file_get_contents($file), true);
			}
		}
		return $this->preferences;
	}
	
	function setPreferencesPath($path) {
		$this->preferencesPath = $path;
	}
	
	function preferencesPath() {
		if (isset($this->preferencesPath)) {
			return $this->preferencesPath;
		}
	    return $_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.xataface' . DIRECTORY_SEPARATOR . 'server-prefs.json';
	}
	
	function setCurrentServer($serverName) {
		$this->preferences();
		$this->preferences['server'] = $serverName;
		$this->flushPrefs();
	}
	
	
	function flushPrefs() {
		file_put_contents($this->preferencesPath(), json_encode($this->preferences()), LOCK_EX);
	}
	
	function getCurrentServer() {
		
		$prefs = $this->preferences();
		$server = @$prefs['server'] or '';
		if (!$server) {
			return null;
		} else {
			$servers = $this->servers();
			foreach ($servers as $s) {
				if ($s->getName() == $server) {
					return $server;
				}
			}
		}
		return null;
	}
	

	function getServerByName($name) {
		$servers = $this->servers();
		if (!$servers) {
			return null;
		}
		foreach ($servers as $server) {
			if ($server->getName() == $name) {
				return $server;
			}
		}
	}
	
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
	
	function setConfigFilePath($path) {
		$this->configFilePath = $path;
	}
	
	
	function configFilePath() {
		if ($this->configFilePath) {
			return $this->configFilePath;
		}
		return $_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.xataface' . DIRECTORY_SEPARATOR . 'server-config.ini';
	}
	
	function setup() {
		$servers = $this->servers();
		if (count($servers) > 0) {
			throw new Exception("Server environment is already set up.");
		}
		
		if (file_exists('/opt/bitnami/ctlscript.sh')) {
			return $this->setupBitnami();
		}
		if (file_exists('/Applications/XAMPP/xamppfiles/bin/apachectl')) {
			return $this->setupXAMPP();
		}
		
		throw new Exception("This type of server is not yet supported.  Please set up manually");
		
	}
	
	function setupXAMPP() {
		$confFile = $_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.xataface' . DIRECTORY_SEPARATOR . 'xataface.httpd.conf';
		if (!file_exists($confFile)) {
			touch($confFile);
		}
		
		$globalConfig = "/Applications/XAMPP/xamppfiles/etc/httpd.conf";
		$globalConfigContents = file_get_contents($globalConfig);
		if (strpos($globalConfigContents, $confFile) === false) {
			passthru("echo 'Include \"$confFile\"' >> " . escapeshellarg($globalConfig), $res);
			if ($res !== 0) {
				throw new Exception("Failed add xataface config file to the global config file '$globalConfig'");
			}
		}
		
		$base = "/Applications/XAMPP/xamppfiles/bin";
		$config = 
<<<END
[xampp]
startCommand=sudo $base/apachectl start
stopCommand=sudo $base/apachectl stop
restartCommand=sudo $base/apachectl restart
statusCommand=sudo $base/apachectl status
mysqlCommand=$base/mysql
mysqlRootUser=root
mysqlRootPassword=
docRoot=/Applications/XAMPP/xamppfiles/apache2/htdocs
END
;			
		file_put_contents($this->configFilePath(), $config);
		return true;
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
	
	public function start() {
		$cmd = $this->getStartCommand();
		if (!$cmd) {
			throw new Exception("In order to start the server, the server's start command must be defined.  To set this up, define the startCommand directive in the {$this->getName()} section of the {$this->servers->configFilePath()} config file.");
		}
		passthru($cmd, $res);
		if ($res !== 0) {
			throw new Exception("Failed to start server");
		}
	}
	
	public function stop() {
		$cmd = $this->getStopCommand();
		if (!$cmd) {
			throw new Exception("In order to stop the server, the server's stop command must be defined.  To set this up, define the stopCommand directive in the {$this->getName()} section of the {$this->servers->configFilePath()} config file.");
		}
		passthru($cmd, $res);
		if ($res !== 0) {
			throw new Exception("Failed to stop server");
		}
	}
	
	public function isRunning() {
		$cmd = $this->getStatusCommand();
		if (!$cmd) {
			throw new Exception("In order to check server status, the server's status command must be defined.  To set this up, define the statusCommand directive in the {$this->getName()} section of the {$this->servers->configFilePath()} config file.");
		}
		passthru($cmd, $res);
		return $res === 0;
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
	
	private function fullMysqlCommand($user=null, $password=null, $database=null) {
		$out = '';
		$pass = $password;
		if (!$pass) {
			$pass = $this->getMysqlRootPassword();
			if (!$pass) {
				$pass = '';
			}
		}
		if (!$user) {
			$user = $this->getMysqlRootUser();
			if (!$user) {
				$user = '';
			}
		}
		$cmd = $this->getMysqlCommand();
		if ($pass) {
			$out .= 'MYSQL_PWD='.escapeshellarg($pass).' ';
		}
		$out .= $cmd;
		if ($user) {
			$out .= ' -u '.escapeshellarg($user);
		}
		if ($database) {
			$out .= ' ' . escapeshellarg($database);
		}
		return $out;
	}
	
	public function executeSQLFile($path, $user=null, $password=null, $database=null) {
		if (!file_exists($path)) {
			throw new Exception("Attempt to execute SQL in file at path $path that doesn't exist");
		}
		$cmd = $this->fullMysqlCommand($user, $password, $database);
		if (!$cmd) {
			throw new Exception("In order to execute SQL, the server's mysqlCommand property must be defined.  Set this up by adding a mysqlCommand directive to the {$this->getName()} section of the {$this->servers->configFilePath()} config file.");
		}
		exec($cmd . ' < ' . escapeshellarg($path), $buffer, $res);
		if ($res !== 0) {
			throw new Exception("Failed to execute SQL file.  Exit code $res.");
		}
		return $buffer;
	}
	
	public function executeSQLQuery($sql, $user=null, $password=null, $database=null) {
		$cmd = $this->fullMysqlCommand($user, $password, $database);
		echo "CMD=$cmd\n";
		if (!$cmd) {
			throw new Exception("In order to execute SQL, the server's mysqlCommand property must be defined.  Set this up by adding a mysqlCommand directive to the {$this->getName()} section of the {$this->servers->configFilePath()} config file.");
		}
		$fullCmd = $cmd . ' -e ' . escapeshellarg($sql);
		//echo "Executing $fullCmd";
		exec($fullCmd, $buffer, $res);
		if ($res !== 0) {
			throw new Exception("Failed to execute SQL query. $sql. Exit code $res.");
		}
		
		return $buffer;
	}
	
	public function queryResultToArray(array $buffer) {
		$out = array();
		$len = count($buffer);
		for ($i=0; $i<$len; $i++) {
			$out[$i] = str_getcsv($buffer[$i], "\t");
			
			
		}
		return $out;
	}
	
	public function createDatabaseUser($dbUser, $dbPass) {
		$sql = "CREATE USER '".addslashes($dbUser)."'@'localhost' identified by '".addslashes($dbPass)."'; FLUSH PRIVILEGES;";
		$this->executeSQLQuery($sql);
	}
	
	public function dropDatabaseUser($dbUser) {
		$this->executeSQLQuery("DROP USER '".addslashes($dbUser)."'@'localhost'; FLUSH PRIVILEGES;");
	}
	
	public function databaseUserExists($dbUser) {
		print_r($this->queryResultToArray($this->executeSQLQuery("select * from mysql.user")));
	}
	
	public function grantDatabasePermissions($dbUser, $dbName) {
		$this->executeSQLQuery("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '".addslashes($dbUser)."'@'localhost'; FLUSH PRIVILEGES;");
	}
	
	public function revokeDatabasePermissions($dbUser, $dbName) {
		$this->executeSQLQuery("REVOKE ALL PRIVILEGES ON `{$dbName}`.* FROM '".addslashes($dbUser)."'@'localhost'; FLUSH PRIVILEGES;");
	}
	
	public function loadVirtualHosts() {
		if (!$this->getConfigPath()) {
			throw new Exception("No config file specified for this server. Specify the config file by adding the configPath directive to the {$this->getName()} section of the {$this->servers->configFilePath()} config file.");
		}
		
		$configPath = $this->getConfigPath();
		if (!file_exists($configPath)) {
			throw new Exception("Cannot find config file $configPath\n");
		}
		
		$lines = file($configPath);
		$status = 0;
		$vhost = null;
		$isVhostOurs = false;
		$out = array();
		$buffer = '';
		foreach ($lines as $lineNum=>$line) {
			$rawLine = $line;
			$line = trim($line);
			if ($status === 1) {

				if ($line === '#XATAFACE#') {
					$isVhostOurs = true;
					$buffer .= $rawLine;
					continue;
				}
			}
			
			$hashpos = strpos($line, '#');
			if ($hashpos !== false) {
				$line = substr($line, 0, $hashpos);
				$line = trim($line);
			}
			
			if ($status === 0) {
				// Not currently inside a VirtualHost directive
				if (preg_match('/<VirtualHost (.*)>/', $line, $matches)) {
					$listen = $matches[1];
					$colonPos = strpos($listen, ':');
					$address = "*";
					$port = "*";
					if ($colonPos !== false) {
						list($address, $port) = explode(':', $listen);
					} else {
						$address = $listen;
						$port = "*";
					}
					$vhost = new XFVirtualHost($this);
					$vhost->setAddress($address);
					$vhost->setPort($port);
					$vhost->setStartLine($lineNum);
					$buffer = $rawLine;
					$status = 1;
					continue;
				}
			}
			
			if ($status === 1) {
				// Currently inside a VirtualHost directive
				$buffer .= $rawLine;
				if (preg_match("#</VirtualHost>#", $line)) {
					$vhost->setEndLine($lineNum+1);
					$vhost->setRaw($buffer);
					if ($isVhostOurs) {
						$out[] = $vhost;
					}
					$vhost = null;
					$isVhostOurs = false;
					$buffer = '';
					$status = 0;
					continue;
				}
				
				if (preg_match('/ServerName (.*)/', $line, $matches)) {
					$name = $matches[1];
					$names = preg_split('/\s+/', $name);
					$vhost->setName(array_shift($names));
					foreach ($names as $alias) {
						$vhost->addAlias($alias);
					}
					continue;
				}
				if (preg_match('/ServerAlias (.*)/', $line, $matches)) {
					$name = $matches[1];
					$names = preg_split('/\s+/', $name);
					foreach ($names as $alias) {
						$vhost->addAlias($alias);
					}
					continue;
				}
				if (preg_match('/DocumentRoot (.*)/', $line, $matches)) {
					$path = trim($matches[1]);
					$path = str_replace("\"", '', $path);
					$vhost->setDocRoot($path);
					continue;
				}
				
				
				
			}
		}
		
		return $out;
		
		
	}
	
	
}
	
class XFVirtualHost {
	private $server;
	private $name;
	private $aliases;
	private $docRoot;
	private $port;
	private $address;
	private $startLine;
	private $endLine;
	private $raw;
	
	public function __construct(XFServer $server) {
		$this->server = $server;
	}
	
	function setAddress($address) {
		$this->address = $address;
		
	}
	function getAddress() {
		return $this->address;
	}
	function setPort($port) {
		$this->port = $port;
	}
	function getPort() {
		return $this->port;
	}
	function setName($name) {
		$this->name = $name;
	}
	function getName() {
		return $this->name;
	}
	function setStartLine($lineNum) {
		$this->startLine = $lineNum;
	}
	function getStartLine() {
		return $this->startLine;
	}
	function setEndLine($lineNum) {
		$this->endLine = $lineNum;
	}
	function getEndLine() {
		return $this->endLine;
	}
	function setRaw($rawConfig) {
		$this->raw = $rawConfig;
	}
	function getRaw() {
		return $this->raw;
	}
	function setDocRoot($root) {
		$this->docRoot = $root;
	}
	function getDocRoot() {
		return $this->docRoot;
	}
	
	function getAliasesAsString() {
		if (!$this->aliases) {
			return '';
		}
		return implode(' ', $this->aliases);
	}
	
	function toString() {
		if (!isset($this->raw)) {
			$serverAliases = $this->getAliasesAsString();
			if ($serverAliases) {
				$serverAliases = 'ServerAlias '.$serverAliases;
			}
			return preg_replace("/\n\n/", "\n", 
<<<END
<VirtualHost {$this->address}:{$this->port}>
ServerName {$this->name}
${serverAliases}
DocumentRoot "{$this->docRoot}"
<Directory {$this->docRoot}>
	Options Indexes FollowSymLinks ExecCGI Includes
	AllowOverride All
</Directory>
</VirtualHost>
END
);
		} else {
			$out = $this->raw;
			$out = preg_replace('/ServerName (.*)/', 'ServerName '.$this->name, $out);
			if (preg_match('/ServerAlias (.*)/', $out)) {
				if ($this->aliases) {
					$out = preg_replace('/ServerAlias .*/', 'ServerAlias '.$this->getAliasesAsString(), $out);
				} else {
					$out = preg_replace('/ServerAlias .*/', '', $out);
				}
			} else if ($this->getAliasesAsString()){
				$lines = explode("\n", $out);
				array_splice($lines, 1, 0, 'ServerAlias '.$this->getAliasesAsString());
				$out = implode("\n", $lines);
			}
			$out = preg_replace("/DocumentRoot .*/", "DocumentRoot \"".$this->docRoot."\"", $out);
			/*
			if (!preg_match("/<Directory ".preg_quote($this->docRoot, '/').">/", $out)) {
				$lines = explode("\n", $out);
				
				array_splice($lines, count($lines)-2, 0, '<Directory '.$this->docRoot.">\n.   Options Indexes FollowSymLinks ExecCGI Includes\n    AllowOverride All\n</Directory>");
				$out = implode("\n", $lines);
			}
			*/
			return $out;

			
		}
	}
	
	function addAlias($alias) {
		if (!isset($this->aliases)) {
			$this->aliases = array();
		}
		$this->aliases[] = $alias;
	}
	
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
					if ($vhost->docRoot and $vhost->docRoot[0] == '"') {
						$vhost->docRoot = str_replace('"', '', $vhost->docRoot);
					}
					continue;
				}
				if (preg_match('/^(ServerName|ServerAlias) (.*)/', $line, $matches)) {
					$serverNames = array_map('trim', explode(' ', $marches[1]));
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