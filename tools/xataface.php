<?php
if (php_sapi_name() != "cli") {
	die('CLI only');
}

class CLICommand {
	var $name;
	var $description;
	
	function exec() {
		fwrite(STDERR, "Command {$this->name} implemented yet\n");
		exit(1);
	}
    
	function find_base_dir($start = null) {
        $cmd = 'apachectl.sh';
		if (!$start) {
			$start = getcwd();
		}
		$appctl = $start . DIRECTORY_SEPARATOR . $cmd;
		if (file_exists($appctl)) {
			return dirname(dirname($appctl));
		}
		$appctl = $start . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . $cmd;
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
    
    function get_xataface_version($versionTxt = null) {
        if (!$versionTxt) {
            $versionTxt = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'version.txt';
        }
        
        $versionStr = trim(file_get_contents($versionTxt));
        if (!$versionStr) {
            throw new Exception("No xataface version found at $versionTxt");
        }
        list($longVersion, $buildNumber) = explode(' ', $versionStr);
        $buildNumber = intval($buildNumber);
        return array($longVersion, $buildNumber);
    }
    
    function get_xataface_version_number($versionTxt = null) {
        return $this->get_xataface_version($versionTxt)[1];
    }
    
    function get_xataface_version_string($versionTxt = null) {
        return $this->get_xataface_version($versionTxt)[0];
    }
}

class CLICommand_Service extends CLICommand {
	var $argv;
	function __construct($argv) {
		$this->argv = $argv;
		$this->name = 'service';
		$this->description = "Shows running Xataface services";
	}
	function exec() {
		$scriptPath = dirname(__FILE__).'/service.php';
		include $scriptPath;
		$args = array_slice($this->argv, 1);
		if (count($args) == 1) {
			$args[] = 'list';
		}
		xf_service_run($args);
	}
}


class CLICommand_Create extends CLICommand {
	var $argv;
	function __construct($argv) {
		$this->argv = $argv;
		$this->name = 'create';
		$this->description = "Create a new Xataface app";
	}
	function exec() {
		$scriptPath = dirname(__FILE__).'/create.php';
		include $scriptPath;
		xf_create_run(array_slice($this->argv, 1));
	}
}

class CLICommand_Help extends CLICommand {
	var $ctrl;
	function __construct(CLIController $ctrl) {
		$this->ctrl = $ctrl;
		$this->name = 'help';
		$this->description = "Show help";
	}
	function exec() {
		echo "Usage: xataface command [options]\n";
		echo "\nCommands:\n\n";
		foreach ($this->ctrl->commands as $cmd) {
			echo $cmd->name.'		: ' . $cmd->description."\n";
		}
	}
}

class CLICommand_Start extends CLICommand {
	var $argv;
	function __construct($argv) {
		$this->argv = $argv;
		$this->name = 'start';
		$this->description = "Start the development server";
	}
	function exec() {
		$scriptPath = dirname(__FILE__).'/lib/XFAppCommand.class.php';
		include $scriptPath;
		$args = $this->argv;
		$appctl = new XFAppCommand('appctl.sh', $args);
		$appctl->run();
	}
}

class CLICommand_Stop extends CLICommand {
	var $argv;
	function __construct($argv) {
		$this->argv = $argv;
		$this->name = 'stop';
		$this->description = "Stop the development server";
	}
	function exec() {
		$scriptPath = dirname(__FILE__).'/lib/XFAppCommand.class.php';
		include $scriptPath;
		$args = $this->argv;
		$appctl = new XFAppCommand('appctl.sh', $args);
		$appctl->run();
	}
}

class CLICommand_SetupAuth extends CLICommand {
	var $argv;
	function __construct($argv) {
		$this->argv = $argv;
		$this->name = 'setup-auth';
		$this->description = "Enable authentication in app";
	}
	function exec() {
		$scriptPath = dirname(__FILE__).'/lib/XFAppCommand.class.php';
		include $scriptPath;
		$args = array_slice($this->argv, 1);
		$appctl = new XFAppCommand('setup-auth.sh', $args);
		$appctl->run();
	}
}

class CLICommand_AddUser extends CLICommand {
	var $argv;
	function __construct($argv) {
		$this->argv = $argv;
		$this->name = 'add-user';
		$this->description = "Add app user to database";
	}
	function exec() {
		$scriptPath = dirname(__FILE__).'/lib/XFAppCommand.class.php';
		include $scriptPath;
		$args = array_slice($this->argv, 1);
		$appctl = new XFAppCommand('add-user.sh', $args);
		$appctl->run();
	}
}

class CLICommand_CreateDelegate extends CLICommand {
	var $argv;
	function __construct($argv) {
		$this->argv = $argv;
		$this->name = 'create-delegate';
		$this->description = "Create delegate class for table";
	}
	function exec() {
		$scriptPath = dirname(__FILE__).'/lib/XFAppCommand.class.php';
		include $scriptPath;
		$args = array_slice($this->argv, 1);
		$appctl = new XFAppCommand('create-delegate.sh', $args);
		$appctl->run();
	}
}

class CLICommand_CreateFieldsIni extends CLICommand {
	var $argv;
	function __construct($argv) {
		$this->argv = $argv;
		$this->name = 'create-fieldsini';
		$this->description = "Create fields.ini for table";
	}
	function exec() {
		$scriptPath = dirname(__FILE__).'/lib/XFAppCommand.class.php';
		include $scriptPath;
		$args = array_slice($this->argv, 1);
		$appctl = new XFAppCommand('create-fieldsini.sh', $args);
		$appctl->run();
	}
}

class CLICommand_CreateAppDelegate extends CLICommand {
	var $argv;
	function __construct($argv) {
		$this->argv = $argv;
		$this->name = 'create-app-delegate';
		$this->description = "Create application delegate class";
	}
	function exec() {
		$scriptPath = dirname(__FILE__).'/lib/XFAppCommand.class.php';
		include $scriptPath;
		$args = array_slice($this->argv, 1);
		$appctl = new XFAppCommand('create-app-delegate.sh', $args);
		$appctl->run();
	}
}

class CLICommand_InstallModule extends CLICommand {
	var $argv;
	function __construct($argv) {
		$this->argv = $argv;
		$this->name = 'install-module';
		$this->description = "Install a module in application";
	}
	function exec() {
		$scriptPath = dirname(__FILE__).'/lib/XFAppCommand.class.php';
		include $scriptPath;
		$args = array_slice($this->argv, 1);
		$appctl = new XFAppCommand('install-module.sh', $args);
		$appctl->run();
	}
}

class CLICommand_CreatePackage extends CLICommand {
	var $argv;
	function __construct($argv) {
		$this->argv = $argv;
		$this->name = 'create-package';
		$this->description = "Generate an install package for this app";
	}
	function exec() {
		$scriptPath = dirname(__FILE__).'/lib/XFAppCommand.class.php';
		include $scriptPath;
		$args = array_slice($this->argv, 1);
		$appctl = new XFAppCommand('create-package.sh', $args);
		$appctl->run();
	}
}

class CLICommand_Update extends CLICommand {
    var $argv;
    function __construct($argv) {
        $this->argv = $argv;
        $this->name = 'update';
        $this->description = "Update project to system version of Xataface.";
    }
    
    function exec() {
        $xatafaceDir =  dirname(dirname(__FILE__));
        $systemVersion = $this->get_xataface_version();
        
        $baseDir = $this->find_base_dir();
        $projectXataface = $baseDir . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'xataface';
        $projectVersion = $this->get_xataface_version( $projectXataface . DIRECTORY_SEPARATOR . 'version.txt');
        
        if ($systemVersion[1] > $projectVersion[1]) {
            fwrite(STDOUT, "System Xataface version (".$systemVersion[0].") is newer than project xataface version (".$projectVersion[0].").\nWould you like to update the project's Xataface directory? [y/N] :");
            $line = trim(fgets(STDIN));
            if (strtolower($line) == 'y') {
                if (!is_dir($projectXataface)) {
                    fwrite(STDERR, "Cannot overwrite $projectXataface because it is not a directory.\n");
                    exit(1);
                } else {
                    echo "Deleting $projectXataface...\n ";
                    passthru("rm -rf ".escapeshellarg($projectXataface), $res);
                    if ($res !== 0) {
                        fwrite(STDERR, "Failed to delete $projectXataface.\n");
                        exit(1);
                    }
                    echo "Copying $xatafaceDir to $projectXataface...\n";
                    passthru("cp -r ".escapeshellarg($xatafaceDir)." ".escapeshellarg($projectXataface), $res);
                    if ($res !== 0) {
                        fwrite(STDERR, "Failed to copy $xatafaceDir to $projectXataface\n");
                        exit(1);
                    }
                }
                
            }
        }
        
        if ($baseDir === null or !file_exists($baseDir)) {
            fwrite(STDERR, 'Working directory must be inside a xataface project for update command to work.'."\n");
            exit(1);
        }
        
        $scaffoldBin = $projectXataface . DIRECTORY_SEPARATOR . 'scaffold_template' . DIRECTORY_SEPARATOR . 'bin';
        if (!file_exists($scaffoldBin)) {
            fwrite(STDERR, "Xataface installation is missing the ".$scaffoldBin." directory.\n");
            exit(1);
        }
        
        $updateCount = 0;
        $binDir = $baseDir . DIRECTORY_SEPARATOR . 'bin';
        foreach (scandir($scaffoldBin) as $f) {
            $fPath = $scaffoldBin . DIRECTORY_SEPARATOR . $f;
            if (is_file($fPath)) {
                $fDest = $binDir . DIRECTORY_SEPARATOR . $f;
                if (!file_exists($fDest) or filemtime($fDest) < filemtime($fPath)) {
                    echo "Updating $fDest...\n";
                    if (!copy($fPath, $fDest)) {
                        fwrite(STDERR, "ERROR: Failed to copy $fPath to $fDest\n");
                        exit(1);
                    }
                    $updateCount++;
                }
            }
            
        }
        
        $scaffoldBinInc = $scaffoldBin . DIRECTORY_SEPARATOR . 'inc';
        $binIncDir = $binDir . DIRECTORY_SEPARATOR . 'inc';
        foreach (scandir($scaffoldBinInc) as $f) {
            $fPath = $scaffoldBinInc . DIRECTORY_SEPARATOR . $f;
            if (is_file($fPath)) {
                $fDest = $binIncDir . DIRECTORY_SEPARATOR . $f;
                if (!file_exists($fDest) or filemtime($fDest) < filemtime($fPath)) {
                    echo "Updating $fDest...\n";
                    if (!copy($fPath, $fDest)) {
                        fwrite(STDERR, "ERROR: Failed to copy $fPath to $fDest\n");
                        exit(1);
                    }
                    $updateCount++;
                }
            }
            
        }
        
        if ($updateCount > 1) {
            echo "Updated $updateCount files in the project bin and bin/inc directories.\n";
        } else if ($updateCount == 1) {
            echo "Updated $updateCount file in the project bin and bin/inc directories.\n";
        } else {
            echo "All files in bin and bin/inc already up to date.\n";
        }
        
    }
    
}

class CLICommand_Test extends CLICommand {
	var $argv;
	function __construct($argv) {
		$this->argv = $argv;
		$this->name = 'test';
		$this->description = "Run unit tests";
	}
	function exec() {
		$xatafaceDir =  dirname(dirname(__FILE__));
		$testsDir = $xatafaceDir . DIRECTORY_SEPARATOR . 'tests';
		$runtests = $testsDir . DIRECTORY_SEPARATOR . 'runtests.sh';
		
		if (!file_exists($runtests)) {
			fwrite(STDERR, "Cannot find $runtests\n");
			exit(1);
		}
		
		passthru("command -v mysql", $res);
		if ($res !== 0) {
			fwrite(STDERR, "No mysql found in your path.  Please add mysql to your PATH and try running tests again.\n");
			exit(1);
		}
		

		$home = $_SERVER['HOME'];
		$xatafaceHome = $home . DIRECTORY_SEPARATOR . '.xataface';
		if (!file_exists($xatafaceHome)) {
			if (!mkdir($xatafaceHome)) {
				fwrite(STDERR, "Failed to create directory $xatafaceHome\n");
				exit(1);
			}
		}
		$testSandboxRoot = $xatafaceHome . DIRECTORY_SEPARATOR . 'tmp';
		if (!file_exists($testSandboxRoot)) {
			if (!mkdir($testSandboxRoot)) {
				fwrite(STDERR, "Failed to create directory $testSandboxRoot\n");
				exit(1);
			}
		}
		
		$testDir = $testSandboxRoot . DIRECTORY_SEPARATOR . 'test';
		$pidFile = $testDir . DIRECTORY_SEPARATOR . 'pid';
		if (file_exists($pidFile)) {
			$pid = trim(file_get_contents($pidFile));
			if ($pid and posix_kill($pid, 0)) {
				// There is already a test running
				fwrite(STDERR, "Xataface unit tests running in another process. PID=$pid\n");
				exit(1);
			}
		}
		if (file_exists($testDir)) {
			passthru("rm -rf ".escapeshellarg($testDir), $res);
			if ($res !== 0) {
				fwrite(STDERR, "Failed to delete old test directory.  Exit code $res\n");
				exit(1);
			}
		}
		
		$tests = array();
		for ($i=2; $i<count($this->argv); $i++) {
			$tests[] = $this->argv[$i];
		}
		$XATAFACE_TESTS = '';
		if ($tests) {
			$XATAFACE_TESTS = ' XATAFACE_TESTS='.escapeshellarg(implode(' ', $tests));
		}
		
		mkdir($testDir);
		chdir($testDir);
		passthru('XATAFACE='.escapeshellarg($xatafaceDir).$XATAFACE_TESTS.' bash '.escapeshellarg($runtests), $res);
		if ($res !== 0) {
			fwrite(STDERR, "Tests failed.  Exit code $res\n");
			exit(1);
		}
		echo "Tests PASSED\n";

		
	}
}

class CLICommand_Environment extends CLICommand {
	var $argv;
	
	function __construct($argv) {
		$this->argv = $argv;
		$this->name = "env";
		$this->description = "Manage the server's environment";
	}
	
	function exec() {
		require_once dirname(__FILE__). DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'XFServers.class.php';
		$servers = new XFServers();
		
		if (count($this->argv) > 2 and $this->argv[2] == 'setup') {
			
			$servers->setup();
			
			exit(0);
			
		}
		foreach ($servers->servers() as $server) {
			echo $server->getName() . "\n";
		}
	}
	
	
}

class CLIController {
	
	var $commands = array();
	
	function __construct($argv) {
		$this->commands[] = new CLICommand_Service($argv);
		$this->commands[] = new CLICommand_Create($argv);
		$this->commands[] = new CLICommand_Help($this);
		$this->commands[] = new CLICommand_Start($argv);
		$this->commands[] = new CLICommand_Stop($argv);
		$this->commands[] = new CLICommand_SetupAuth($argv);
		$this->commands[] = new CLICommand_AddUser($argv);
		$this->commands[] = new CLICommand_CreateDelegate($argv);
        $this->commands[] = new CLICommand_CreateFieldsIni($argv);
		$this->commands[] = new CLICommand_CreateAppDelegate($argv);
		$this->commands[] = new CLICommand_InstallModule($argv);
		$this->commands[] = new CLICommand_CreatePackage($argv);
		$this->commands[] = new CLICommand_Test($argv);
		$this->commands[] = new CLICommand_Environment($argv);
        $this->commands[] = new CLICommand_Update($argv);
	}
	
	function exec($cmdName) {
		foreach ($this->commands as $cmd) {

			if ($cmd->name == $cmdName) {
				
				$cmd->exec();
				return;
			}
		}
		$this->help();
		exit(1);
	}
	
	function help() {
		$this->exec('help');
	}
	
}
$controller = new CLIController($argv);
if (count($argv) < 2) {
	$controller->help();
	exit(1);
}
$cmd = $argv[1];
$controller->exec($cmd);
?>