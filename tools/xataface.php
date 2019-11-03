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
		xf_service_run(array_slice($this->argv, 1));
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



class CLIController {
	
	var $commands = array();
	
	function __construct($argv) {
		$this->commands[] = new CLICommand_Service($argv);
		$this->commands[] = new CLICommand_Create($argv);
		$this->commands[] = new CLICommand_Help($this);
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