<?php


require_once 'ValuelistToolTest.php';

$test = new PHPUnit_TestSuite('ValuelistToolTest');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();


?>
