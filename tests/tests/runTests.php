<?php
ini_set('display_errors', 'on');
$tests = array(
    'TableTest',
    'IOTest',
    'HistoryToolTest'
);
$runTests = 0;
foreach ($tests as $t) {
    require_once($t.'.php');
    $test = new PHPUnit_TestSuite($t);
    $result = new PHPUnit_TestResult;
    //$result->addListener(new Benchmarker());
    $test->run($result);
    if (php_sapi_name() == "cli") {
        print $result->toString();
    } else {
        print $result->toHtml();
    }
    if (!$result->wasSuccessful()) {
        fwrite(STDERR, "Failed test $t");
        exit(1);
    }
    $runTests++;
}
echo "Completed $runTests tests\n";