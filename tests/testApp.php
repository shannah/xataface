<?php
/**
 * A Test dataface application.  Used to perform unit tests on.
 */
require_once 'testconfig.php';

if ( @$_REQUEST['-action'] == 'setUp' ){
	require_once 'BaseTest.php';
	$test = new BaseTest();
	$test->setUp();
	unset($_REQUEST['-action']);
	unset($_GET['-action']);
} else if ( @$_REQUEST['-action'] == 'tearDown' ){
	require_once 'BaseTest.php';
	$test = new BaseTest();
	$test->tearDown();
	unset($_REQUEST['-action']);
	unset($_GET['-action']);
	echo 'Tear down successful';
	exit;
} else {
require_once 'dataface-public-api.php';
$app =& Dataface_Application::getInstance();
$app->display();
}
?>
