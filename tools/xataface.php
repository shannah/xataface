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
		$this->commands[] = new CLICommand_CreateAppDelegate($argv);
		$this->commands[] = new CLICommand_InstallModule($argv);
		$this->commands[] = new CLICommand_CreatePackage($argv);
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