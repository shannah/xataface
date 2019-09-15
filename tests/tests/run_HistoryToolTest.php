<?php
require_once 'HistoryToolTest.php';
$test = new PHPUnit_TestSuite('HistoryToolTest');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();
?>
