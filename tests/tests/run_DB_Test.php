<?php
require_once 'DB_Test.php';
$test = new PHPUnit_TestSuite('DB_Test');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();
?>
