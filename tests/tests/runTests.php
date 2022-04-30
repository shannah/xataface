<?php
ini_set('display_errors', 'on');

$tests = array(
    'TableTest',
    'IOTest',
    'HistoryToolTest',
	//'ServicesTest',
	//'CLIServerTest'
);
if ('true' == getenv('TRAVIS')) {
	// Some tests won't work on Travis because they need 
	// a functioning Apache server installation
	// so we override the tests on travis.
	$tests = array(
	    'TableTest',
	    'IOTest',
	    'HistoryToolTest'
		//'ServicesTest'
	);
}
$envTests = getenv('XATAFACE_TESTS');
if ($envTests) {
	// Optionally specify the specific tests to run
	// using the XATAFACE_TESTS environment variable
	// space delimited
	$tests = array_map('trim', explode(' ', $envTests));
}
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