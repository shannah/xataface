<?php


require_once 'IOTest.php';

$test = new PHPUnit_TestSuite('IOTest');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();


?>
