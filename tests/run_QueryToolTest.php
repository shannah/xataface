<?php
require_once 'QueryToolTest.php';
$test = new PHPUnit_TestSuite('QueryToolTest');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();
?>
