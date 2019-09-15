<?php
require_once 'RecordTest2.php';
$test = new PHPUnit_TestSuite('RecordTest2');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();
?>
