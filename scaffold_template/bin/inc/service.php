<?php
require_once dirname($_SERVER['USER_HOME']) 
	. DIRECTORY_SEPARATOR . '.xataface' 
	. DIRECTORY_SEPARATOR . 'tools' 
	. DIRECTORY_SEPARATOR .'lib' 
	. DIRECTORY_SEPARATOR . 'XFServices.class.php';


if (!@$argv) {
	die("CLI only");
}
if (count($argv) < 2 or !in_array($argv[1], array('start', 'stop', 'status', 'list', 'list-all'))) {
	fwrite(STDERR, "usage: php service.php start|stop|status|list|list-all [service-name service-port]\n");
	exit(1);
}

$cmd = $argv[1];

$appPath = dirname(dirname(dirname(__FILE__)));
if (in_array($cmd, array('start', 'stop', 'status')) and count($argv) < 4) {
	fwrite(STDERR, "Service name and port parameters are required.\ne.g. php service.php $cmd mysql 3306\n");
	exit(1);
}
if (in_array($cmd, array('start', 'stop', 'status'))) {
	$serviceName = $argv[2];
	$servicePort = $argv[3];

	$service = new XFService(array(
		'appPath' => $appPath,
		'name' => $serviceName,
		'port' => $servicePort
	));
} else {
	$service = new XFService(array(
		'appPath' => $appPath,
		'name' => '*',
		'port' => 0
	));
}


$serviceManager = new XFServiceManager();
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
	$mask = "|%5.5s |%-30.30s\n";
	printf($mask, 'Port', 'Service Name');
	foreach ($serviceManager->services() as $svc) {
		if ($service->isSameApp($svc)) {
			printf($mask, $svc->getPort(), $svc->getName());
		}
	}
} else if ($cmd == 'list-all') {
	$mask = "|%5.5s |%-10.10s |%-30.30s\n";
	printf($mask, 'Port', 'Service Name', 'App Path');
	foreach ($serviceManager->services() as $svc) {
		if ($service->isSameApp($svc)) {
			printf($mask, $svc->getPort(), $svc->getName(), $svc->getAppPath());
		}
	}
}
