<?php
require_once 'QueryTranslator_Test.php';
$test = new PHPUnit_TestSuite('QueryTranslator_Test');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();
?>
