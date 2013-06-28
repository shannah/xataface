<?php
require_once 'RecordTest.php';
$test = new PHPUnit_TestSuite('RecordTest');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();
?>
