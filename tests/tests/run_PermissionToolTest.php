<?php
require_once 'PermissionsToolTest.php';
$test = new PHPUnit_TestSuite('PermissionsToolTest');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();
?>
