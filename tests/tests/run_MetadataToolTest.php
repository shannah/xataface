<?php
require_once 'MetadataToolTest.php';
$test = new PHPUnit_TestSuite('MetadataToolTest');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();
?>
