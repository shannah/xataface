<?php
require_once 'TranslationToolTest.php';
$test = new PHPUnit_TestSuite('TranslationToolTest');
$result = new PHPUnit_TestResult;
//$result->addListener(new Benchmarker());
$test->run($result);

print $result->toHtml();
?>
