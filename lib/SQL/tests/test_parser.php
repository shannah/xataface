<?php
define('XF_OUTPUT_ENCODING', 'UTF-8');
require_once '../public-api.php';
require_once 'parser_cases.php';
require_once 'PHPUnit.php';

$suite = new PHPUnit_TestSuite('SqlParserTest');
$result = PHPUnit::run($suite);

echo $result->toString();
?>
