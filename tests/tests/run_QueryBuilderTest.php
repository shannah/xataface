<?php
require_once 'QueryBuilderTest.php';
$test = new PHPUnit_TestSuite('QueryBuilderTest');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();
?>
