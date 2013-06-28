<?php
/**
 * Unit tests for Event_Dispatcher class
 * 
 * $Id: test.php,v 1.1.1.1 2005/11/29 19:21:53 sjhannah Exp $
 *
 * @package    Event_Dispatcher
 * @subpackage Tests
 */

require_once 'System.php';
require_once 'PHPUnit.php';
require_once 'Event/Dispatcher.php';

$testcases = array(
    'Dispatcher_testcase'
);

$suite = new PHPUnit_TestSuite();

foreach ($testcases as $testcase) {
    include_once $testcase . '.php';
    $methods = preg_grep('/^test/i', get_class_methods($testcase));
    foreach ($methods as $method) {
        $suite->addTest(new $testcase($method));
    }
}

require_once './Console_TestListener.php';
$result = new PHPUnit_TestResult();
$result->addListener(new Console_TestListener);

$suite->run($result);

?>
