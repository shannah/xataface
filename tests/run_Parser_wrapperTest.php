<?php
require_once 'Parser_wrapperTest.php';
$test = new PHPUnit_TestSuite('Parser_wrapperTest');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();
?>
