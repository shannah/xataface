<?php


require_once 'QuickFormTest.php';

$test = new PHPUnit_TestSuite('QuickFormTest');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();


?>
