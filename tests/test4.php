<?php


require_once 'RelatedListTest.php';

$test = new PHPUnit_TestSuite('RelatedListTest');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();


?>
