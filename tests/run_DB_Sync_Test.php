<?php
require_once 'DB_Sync_Test.php';
$test = new PHPUnit_TestSuite('DB_Sync_Test');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();
?>
