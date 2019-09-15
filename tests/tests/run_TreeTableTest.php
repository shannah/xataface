<?php
require_once 'TreeTableTest.php';
$test = new PHPUnit_TestSuite('TreeTableTest');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();
?>
