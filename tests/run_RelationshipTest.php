<?php


require_once 'RelationshipTest.php';

$test = new PHPUnit_TestSuite('RelationshipTest');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();


?>
