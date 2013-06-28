<?php
require_once 'parser_cases.php';
require_once 'PHPUnit.php';

$suite = new PHPUnit_TestSuite('SqlParserTest');
$result = PHPUnit::run($suite);

echo $result->toString();
?>
