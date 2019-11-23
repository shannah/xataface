<?php
/**
 * A script to manage services in Xataface instances.
 * It can list installed Xataface apps, report on their
 * status (running/stopped), and start/stop them.
 */
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'XFServices.class.php';

if (php_sapi_name() != "cli") {
	die("CLI only");
}

function xf_service_run($argv) {
	if (count($argv) < 2 or !in_array($argv[1], array('start', 'stop', 'status', 'list', 'list-all', 'add'))) {
		fwrite(STDERR, "usage: php service.php add|start|stop|status|list|list-all [service-name service-port]\n");
		exit(1);
	}
	
	$cmd = $argv[1];

	$userHome = $_SERVER['HOME'];
	$xfDataDir = $userHome . DIRECTORY_SEPARATOR . '.xataface';
	if (!file_exists($xfDataDir)) {
		mkdir($xfDataDir);
	}
	$servicesFile = $xfDataDir . DIRECTORY_SEPARATOR . '.xataface-services.json';

	$serviceManager = new XFServiceManager();
	$serviceManager->setServicesFilePath($servicesFile);

	if ($cmd == 'add') {
		if (count($argv) >= 3) {
			$appPath = realpath($argv[2]);
		} else {
			$appPath = realpath(getcwd());
		}
	
		$mysql = new XFService(array(
			'appPath' => $appPath,
			'name' => 'mysql'
		));
		$httpd = new XFService(array(
			'appPath' => $appPath,
			'name' => 'httpd'
		));

		if (!$mysql->exists() or !$httpd->exists()) {
			fwrite(STDERR, "Directory $appPath is not a Xataface project.\n");
			exit(1);
		}

		$added = false;
		if (!$serviceManager->contains($httpd)) {
			$serviceManager->add($httpd);
			$added = true;
			echo "Adding mysql service @ $appPath\n";
		}
		if (!$serviceManager->contains($mysql)) {
			$serviceManager->add($mysql);
			$added = true;
			echo "Adding http service @ $appPath\n";
		}
		if ($added) {
			$serviceManager->save();
		} else {
			echo "No services added.\n";
		}
		exit(0);

	}
	if ($cmd == 'remove') {
		if (count($argv) >= 3) {
			$appPath = realpath($argv[2]);
		} else {
			$appPath = realpath(getcwd());
		}
	
		$mysql = new XFService(array(
			'appPath' => $appPath,
			'name' => 'mysql'
		));
		$httpd = new XFService(array(
			'appPath' => $appPath,
			'name' => 'httpd'
		));

		if (!$mysql->exists() or !$httpd->exists()) {
			fwrite(STDERR, "Directory $appPath is not a Xataface project.\n");
			exit(1);
		}

		$removed = false;
		if ($serviceManager->contains($httpd)) {
			$serviceManager->remove($httpd);
			$removed = true;
			echo "Removing mysql service @ $appPath\n";
		}
		if ($serviceManager->contains($mysql)) {
			$serviceManager->remove($mysql);
			$removed = true;
			echo "Removing http service @ $appPath\n";
		}
		if ($removed) {
			$serviceManager->save();
		} else {
			echo "No services removed.\n";
		}
		exit(0);

	}
	if ($cmd == 'start') {
		$serviceManager->add($service);
		if (!$serviceManager->save()) {
			fwrite(STDERR, "Failed to mark service as started.\n");
			exit(1);
		}
	} else if ($cmd == 'stop') {
		$serviceManager->remove($service);
		if (!$serviceManager->stop()) {
			fwrite(STDERR, "Failed to mark service as started.\n");
			exit(1);
		}
	} else if ($cmd == 'status') {
		if ($serviceManager->isRunning($service)) {
			echo "Running\n";
		} else {
			echo "Stopped\n";
		}
	} else if ($cmd == 'list') {
		$rows = array();
		$nameLen = 0;
		foreach ($serviceManager->services() as $svc) {
			try {
				$appPath = $svc->getAppPath();
				if (!isset($rows[$appPath])) {
					$rows[$appPath] = array();
					$rows[$appPath]['port'] = '';
				}
				if ($svc->getName() == 'mysql') {
					$rows[$appPath]['mysql'] = $svc->getStatus();
				} else if ($svc->getName() == 'httpd') {
					$rows[$appPath]['httpd'] = $svc->getStatus();
					$rows[$appPath]['port'] = $svc->getPort();
				} else {
					continue;
				}
				$rows[$appPath]['appPath'] = $appPath;
				$nameLen = max(strlen(basename($appPath)), $nameLen);
			} catch (Exception $ex) {

			}

		
		}
		$mask = "%8.8s | %8.8s | %6.6s | %-50.50s\n";
		printf($mask, 'mysql', 'httpd', 'port', 'path');
	
		foreach ($rows as $row) {
			printf($mask, $row['mysql'], $row['httpd'], $row['port'], $row['appPath']);
		}
	}
}

if (@$argv) {
	xf_service_run($argv);
}

