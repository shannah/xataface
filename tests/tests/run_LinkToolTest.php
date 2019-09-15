<?php
require_once 'LinkToolTest.php';
$test = new PHPUnit_TestSuite('LinkToolTest');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();
?>
