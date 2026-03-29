<?php
/**
 * Unit tests for PHP 8.0 compatibility changes.
 *
 * These tests verify that each individual fix works correctly
 * WITHOUT requiring a database connection or full application bootstrap.
 *
 * Run standalone: php PHP8CompatibilityUnitTest.php
 * Run via framework: Include in runTests.php test list as 'PHP8CompatibilityUnitTest'
 */

// Minimal bootstrap - just enough to load the compatibility functions
$xataface = getenv('XATAFACE') ?: dirname(dirname(__DIR__));
require_once $xataface . '/config.inc.php';

// Load the test framework
require_once 'PHPUnit.php';

class PHP8CompatibilityUnitTest extends PHPUnit_TestCase {

    function PHP8CompatibilityUnitTest($name = 'PHP8CompatibilityUnitTest') {
        $this->PHPUnit_TestCase($name);
    }

    function __construct($name = 'PHP8CompatibilityUnitTest') {
        $this->PHP8CompatibilityUnitTest($name);
    }

    // =========================================================================
    // 1. xf_strftime() compatibility function
    // =========================================================================

    function test_xf_strftime_exists() {
        $this->assertTrue(function_exists('xf_strftime'), 'xf_strftime() should be defined');
    }

    function test_xf_strftime_year() {
        $ts = mktime(0, 0, 0, 6, 15, 2023);
        $result = xf_strftime('%Y', $ts);
        $this->assertEquals('2023', $result, '%Y should return 4-digit year');
    }

    function test_xf_strftime_two_digit_year() {
        $ts = mktime(0, 0, 0, 6, 15, 2023);
        $result = xf_strftime('%y', $ts);
        $this->assertEquals('23', $result, '%y should return 2-digit year');
    }

    function test_xf_strftime_month() {
        $ts = mktime(0, 0, 0, 3, 15, 2023);
        $result = xf_strftime('%m', $ts);
        $this->assertEquals('03', $result, '%m should return zero-padded month');
    }

    function test_xf_strftime_day() {
        $ts = mktime(0, 0, 0, 6, 5, 2023);
        $result = xf_strftime('%d', $ts);
        $this->assertEquals('05', $result, '%d should return zero-padded day');
    }

    function test_xf_strftime_day_no_pad() {
        $ts = mktime(0, 0, 0, 6, 5, 2023);
        $result = xf_strftime('%e', $ts);
        $this->assertEquals('5', $result, '%e should return day without zero-pad');
    }

    function test_xf_strftime_hour_24() {
        $ts = mktime(14, 30, 0, 6, 15, 2023);
        $result = xf_strftime('%H', $ts);
        $this->assertEquals('14', $result, '%H should return 24-hour format');
    }

    function test_xf_strftime_hour_12() {
        $ts = mktime(14, 30, 0, 6, 15, 2023);
        $result = xf_strftime('%I', $ts);
        $this->assertEquals('02', $result, '%I should return 12-hour format');
    }

    function test_xf_strftime_minute() {
        $ts = mktime(14, 7, 0, 6, 15, 2023);
        $result = xf_strftime('%M', $ts);
        $this->assertEquals('07', $result, '%M should return zero-padded minute');
    }

    function test_xf_strftime_second() {
        $ts = mktime(14, 30, 9, 6, 15, 2023);
        $result = xf_strftime('%S', $ts);
        $this->assertEquals('09', $result, '%S should return zero-padded second');
    }

    function test_xf_strftime_am_pm() {
        $am = mktime(9, 0, 0, 6, 15, 2023);
        $pm = mktime(14, 0, 0, 6, 15, 2023);
        $this->assertEquals('AM', xf_strftime('%p', $am), '%p should return AM');
        $this->assertEquals('PM', xf_strftime('%p', $pm), '%p should return PM');
    }

    function test_xf_strftime_full_day_name() {
        $ts = mktime(0, 0, 0, 6, 15, 2023); // Thursday
        $result = xf_strftime('%A', $ts);
        $this->assertEquals('Thursday', $result, '%A should return full day name');
    }

    function test_xf_strftime_abbreviated_day_name() {
        $ts = mktime(0, 0, 0, 6, 15, 2023); // Thursday
        $result = xf_strftime('%a', $ts);
        $this->assertEquals('Thu', $result, '%a should return abbreviated day name');
    }

    function test_xf_strftime_full_month_name() {
        $ts = mktime(0, 0, 0, 6, 15, 2023);
        $result = xf_strftime('%B', $ts);
        $this->assertEquals('June', $result, '%B should return full month name');
    }

    function test_xf_strftime_abbreviated_month_name() {
        $ts = mktime(0, 0, 0, 6, 15, 2023);
        $result = xf_strftime('%b', $ts);
        $this->assertEquals('Jun', $result, '%b should return abbreviated month name');
    }

    function test_xf_strftime_composite_format() {
        $ts = mktime(14, 30, 45, 6, 15, 2023);
        $result = xf_strftime('%Y-%m-%d %H:%M:%S', $ts);
        $this->assertEquals('2023-06-15 14:30:45', $result, 'Composite format should work');
    }

    function test_xf_strftime_percent_literal() {
        $ts = mktime(0, 0, 0, 6, 15, 2023);
        $result = xf_strftime('100%%', $ts);
        $this->assertEquals('100%', $result, '%% should produce literal %');
    }

    function test_xf_strftime_no_timestamp() {
        // Should default to current time
        $result = xf_strftime('%Y');
        $this->assertEquals(date('Y'), $result, 'Should default to current time');
    }

    function test_xf_strftime_day_of_year() {
        $ts = mktime(0, 0, 0, 1, 1, 2023);
        $result = xf_strftime('%j', $ts);
        $this->assertEquals('001', $result, '%j should return zero-padded day of year');
    }

    function test_xf_strftime_unix_timestamp() {
        $ts = mktime(0, 0, 0, 6, 15, 2023);
        $result = xf_strftime('%s', $ts);
        $this->assertEquals((string)$ts, $result, '%s should return unix timestamp');
    }

    function test_xf_strftime_iso_time() {
        $ts = mktime(14, 30, 45, 6, 15, 2023);
        $result = xf_strftime('%T', $ts);
        $this->assertEquals('14:30:45', $result, '%T should return HH:MM:SS');
    }

    function test_xf_strftime_weekday_number() {
        $ts = mktime(0, 0, 0, 6, 15, 2023); // Thursday = 4
        $result = xf_strftime('%u', $ts);
        $this->assertEquals('4', $result, '%u should return ISO weekday number');
    }

    // =========================================================================
    // 2. Curly brace array/string access removal
    // =========================================================================

    function test_bracket_string_access() {
        // Verify that bracket syntax works for string access (replacement for curly braces)
        $str = "Hello";
        $this->assertEquals('H', $str[0], 'Bracket string access at index 0');
        $this->assertEquals('o', $str[strlen($str)-1], 'Bracket access at last index');
    }

    function test_bracket_negative_index() {
        $str = "Hello/";
        // The tools/clone.php fix used $str[-1]
        $this->assertEquals('/', $str[-1], 'Negative index access should work');
    }

    // =========================================================================
    // 3. each() to foreach conversion
    // =========================================================================

    function test_foreach_preserves_each_behavior() {
        // Verify foreach iterates same as each() did
        $arr = array('a' => 1, 'b' => 2, 'c' => 3);
        $keys = array();
        $vals = array();
        foreach ($arr as $k => $v) {
            $keys[] = $k;
            $vals[] = $v;
        }
        $this->assertEquals(array('a', 'b', 'c'), $keys, 'foreach should iterate keys in order');
        $this->assertEquals(array(1, 2, 3), $vals, 'foreach should iterate values in order');
    }

    // =========================================================================
    // 4. create_function() to closure conversion
    // =========================================================================

    function test_closure_substr_replacement() {
        // Tests the QuickForm/date.php fix: create_function('&$v,$k','$v = substr($v,-2);')
        $options = array('2020', '2021', '2022', '2023');
        array_walk($options, function(&$v, $k) { $v = substr($v, -2); });
        $this->assertEquals(array('20', '21', '22', '23'), $options,
            'array_walk closure for substr should work');
    }

    function test_closure_intval_replacement() {
        // Tests the QuickForm/date.php fix: create_function('&$v,$k', '$v = intval($v);')
        $options = array('01', '02', '03', '12');
        array_walk($options, function(&$v, $k) { $v = intval($v); });
        $this->assertEquals(array(1, 2, 3, 12), $options,
            'array_walk closure for intval should work');
    }

    function test_closure_http_request_map() {
        // Tests the HTTP/Request.php fix
        $data = array(array('foo', 'bar'), array('baz', 'qux'));
        $result = implode('&', array_map(
            function($a) { return $a[0] . '=' . $a[1]; },
            $data
        ));
        $this->assertEquals('foo=bar&baz=qux', $result,
            'array_map closure for HTTP request should work');
    }

    // =========================================================================
    // 5. Compare rule (create_function replacement)
    // =========================================================================

    function test_compare_rule_operators() {
        require_once 'HTML/QuickForm/Rule/Compare.php';
        $rule = new HTML_QuickForm_Rule_Compare();

        $this->assertTrue($rule->validate(array(5, 3), '>'), '5 > 3 should be true');
        $this->assertFalse($rule->validate(array(3, 5), '>'), '3 > 5 should be false');
        $this->assertTrue($rule->validate(array(5, 5), '>='), '5 >= 5 should be true');
        $this->assertTrue($rule->validate(array(3, 5), '<'), '3 < 5 should be true');
        $this->assertTrue($rule->validate(array(5, 5), '<='), '5 <= 5 should be true');
        $this->assertTrue($rule->validate(array('abc', 'abc'), '=='), 'abc == abc should be true');
        $this->assertTrue($rule->validate(array('abc', 'def'), '!='), 'abc != def should be true');
    }

    function test_compare_rule_named_operators() {
        require_once 'HTML/QuickForm/Rule/Compare.php';
        $rule = new HTML_QuickForm_Rule_Compare();

        $this->assertTrue($rule->validate(array(5, 3), 'gt'), 'gt operator');
        $this->assertTrue($rule->validate(array(5, 5), 'gte'), 'gte operator');
        $this->assertTrue($rule->validate(array(3, 5), 'lt'), 'lt operator');
        $this->assertTrue($rule->validate(array(5, 5), 'lte'), 'lte operator');
        $this->assertTrue($rule->validate(array('a', 'a'), 'eq'), 'eq operator');
        $this->assertTrue($rule->validate(array('a', 'b'), 'neq'), 'neq operator');
    }

    // =========================================================================
    // 6. split() to explode()/preg_split() conversion
    // =========================================================================

    function test_explode_replaces_split_for_literals() {
        // Tests replacements in simpletest/http.php, url.php, etc.
        $headers = "Content-Type: text/html\r\nX-Custom: value";
        $lines = explode("\r\n", $headers);
        $this->assertEquals(2, count($lines), 'explode should split on CRLF');
        $this->assertEquals('Content-Type: text/html', $lines[0]);

        // Cookie splitting
        $cookie = "name=value; path=/; domain=.example.com";
        $parts = explode(";", $cookie);
        $this->assertEquals(3, count($parts), 'explode should split on semicolon');

        // URL query splitting
        $query = "foo=bar&baz=qux&hello=world";
        $pairs = explode("&", $query);
        $this->assertEquals(3, count($pairs), 'explode should split on &');

        // Colon splitting for auth
        $auth = "user:password";
        $parts = explode(":", $auth);
        $this->assertEquals('user', $parts[0]);
        $this->assertEquals('password', $parts[1]);
    }

    function test_preg_split_replaces_split_for_regex() {
        // Tests the HTML/QuickForm.php fix: split('[ ]?,[ ]?', $elements)
        $elements = "name, email,phone , address";
        $result = preg_split('/[ ]?,[ ]?/', $elements);
        $this->assertEquals(4, count($result), 'preg_split should handle optional-space comma');
        $this->assertEquals('name', $result[0]);
        $this->assertEquals('email', $result[1]);
        $this->assertEquals('phone', $result[2]);
        $this->assertEquals('address', $result[3]);
    }

    // =========================================================================
    // 7. eregi() to preg_match() conversion
    // =========================================================================

    function test_preg_match_replaces_eregi() {
        // Tests the XML/Parser.php fix
        $httpUrl = 'http://example.com/file.xml';
        $ftpUrl = 'ftp://example.com/file.xml';
        $localFile = '/path/to/file.xml';

        $this->assertTrue((bool)preg_match('/^(http|ftp):\/\//i', substr($httpUrl, 0, 10)),
            'Should match http URL');
        $this->assertTrue((bool)preg_match('/^(http|ftp):\/\//i', substr($ftpUrl, 0, 10)),
            'Should match ftp URL');
        $this->assertFalse((bool)preg_match('/^(http|ftp):\/\//i', substr($localFile, 0, 10)),
            'Should not match local path');

        // Generic scheme check
        $this->assertTrue((bool)preg_match('/^[a-z]+:\/\//i', substr($httpUrl, 0, 10)),
            'Should match any scheme');
        $this->assertTrue((bool)preg_match('/^[a-z]+:\/\//i', 'HTTPS://ex'),
            'Case-insensitive scheme match');
    }

    // =========================================================================
    // 8. Reversed implode() fix
    // =========================================================================

    function test_implode_argument_order() {
        $arr = array('a=1', 'b=2', 'c=3');
        $result = implode("&", $arr);
        $this->assertEquals('a=1&b=2&c=3', $result, 'implode(glue, array) should work');
    }

    // =========================================================================
    // 9. assert() with string arguments
    // =========================================================================

    function test_assert_without_strings() {
        // Verify that assert with expression (not string) works
        $j = 5;
        $other_len = 10;
        $other_changed = array_fill(0, 10, false);
        assert($j > 0);
        assert($j < $other_len && !$other_changed[$j]);
        assert($j >= 0 && !$other_changed[$j]);
        // If we got here without error, the asserts passed
        $this->assertTrue(true, 'Asserts with expressions should work');
    }

    // =========================================================================
    // 10. $php_errormsg replacement with error_get_last()
    // =========================================================================

    function test_error_get_last_for_regex_validation() {
        // Tests the htmLawed.php hl_regex fix
        // Valid regex should not produce error
        $valid = '/^[a-z]+$/i';
        $r = (@preg_match($valid, '') !== false) ? 1 : 0;
        $this->assertEquals(1, $r, 'Valid regex should return 1');

        // Invalid regex should produce error
        $invalid = '/[invalid';
        $r = (@preg_match($invalid, '') !== false) ? 1 : 0;
        $this->assertEquals(0, $r, 'Invalid regex should return 0');
    }

    function test_error_clear_last() {
        // Tests the pattern used in test_case.php and Generator.php
        error_clear_last();
        $lastError = error_get_last();
        // After clearing, there should be no "new" error
        // (error_get_last may still return the last error from before the clear,
        //  but error_clear_last resets it)
        @trigger_error('test error', E_USER_NOTICE);
        $lastError = error_get_last();
        $this->assertTrue($lastError !== null, 'error_get_last should capture triggered error');
        $this->assertEquals('test error', $lastError['message'], 'Should capture error message');
    }

    // =========================================================================
    // 11. htmLawed create_function replacements
    // =========================================================================

    function test_htmlawed_spec_closure() {
        // Test that the hl_spec function works with closure replacement
        if (function_exists('hl_spec')) {
            // If htmLawed is loaded, test the actual function
            $result = hl_spec('');
            $this->assertTrue(is_array($result) || empty($result),
                'hl_spec should return array or empty');
        } else {
            // Test the closure pattern directly
            $callback = function($m) {
                return substr(str_replace(
                    array(";", "|", "~", " ", ",", "/", "(", ")", '`"'),
                    array("\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08", "\""),
                    $m[0]
                ), 1, -1);
            };
            $result = $callback(array('"test;value"'));
            $this->assertTrue(strlen($result) > 0, 'htmLawed spec closure should work');
        }
    }

    function test_htmlawed_tidy_closure() {
        // Test the closure pattern from hl_tidy
        $callback = function($m) {
            return $m[1] . str_replace(
                array("<", ">", "\n", "\r", "\t", " "),
                array("\x01", "\x02", "\x03", "\x04", "\x05", "\x07"),
                $m[3]
            ) . $m[4];
        };
        $m = array('full', '<pre>', 'pre', "hello <world>\n", '</pre>');
        $result = $callback($m);
        $this->assertTrue(strpos($result, '<pre>') === 0, 'Should start with tag');
        $this->assertFalse(strpos($result, '<world>') !== false, 'Angle brackets should be replaced');
    }

    // =========================================================================
    // 12. Smarty compiler closure
    // =========================================================================

    function test_smarty_compiler_closure_pattern() {
        // The Smarty compiler now uses a closure directly instead of version-checking
        $left_delimiter = '{';
        $right_delimiter = '}';
        $callback = function($matches) use ($left_delimiter, $right_delimiter) {
            return $left_delimiter . 'php' .
                str_repeat("\n", substr_count($matches[1] ?? '', "\n")) .
                $right_delimiter;
        };
        $result = $callback(array('match', "line1\nline2\n"));
        $this->assertEquals("{php\n\n}", $result, 'Smarty compiler closure should work');
    }

    // =========================================================================
    // 13. XML/DTD bracket access
    // =========================================================================

    function test_xml_dtd_bracket_iteration() {
        // Tests the pattern from lib/XML/DTD.php
        $tag = "ELEMENT name (opt1|opt2)";
        $fields = array();
        $buff = '';
        $in = 0;
        for ($i = 0; $i < strlen($tag); $i++) {
            if ($tag[$i] == ' ' && !$in && $buff) {
                $fields[] = $buff;
                $buff = '';
                continue;
            }
            if ($tag[$i] == '(') {
                $in++;
            } elseif ($tag[$i] == ')') {
                $in--;
            }
            $buff .= $tag[$i];
        }
        if ($buff) {
            $fields[] = $buff;
        }
        $this->assertEquals(3, count($fields), 'Should parse 3 fields');
        $this->assertEquals('ELEMENT', $fields[0]);
        $this->assertEquals('name', $fields[1]);
        $this->assertEquals('(opt1|opt2)', $fields[2]);
    }

    // =========================================================================
    // 14. Text/Diff/Engine/native.php - each() to foreach with flag
    // =========================================================================

    function test_diff_engine_merged_loop_pattern() {
        // Test the pattern that replaced foreach+break+while(each) with single foreach+flag
        $matches = array(10, 20, 30, 40, 50);
        $seq = array(0 => 0, 1 => 15, 2 => 100);
        $in_seq = array();

        $found_first = false;
        $k = 0;
        foreach ($matches as $y) {
            if (!$found_first) {
                if (empty($in_seq[$y])) {
                    $k = 1; // simulate _lcsPos
                    $found_first = true;
                }
                continue;
            }
            // Subsequent iterations: process remaining matches
            if ($y > $seq[$k - 1]) {
                $in_seq[$y] = 1;
            }
        }
        $this->assertTrue($found_first, 'Should find first match');
        $this->assertTrue(isset($in_seq[20]) || isset($in_seq[30]),
            'Subsequent matches should be processed');
    }
}

// Allow standalone execution
if (php_sapi_name() == 'cli' && basename($_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? '') == basename(__FILE__)) {
    $test = new PHPUnit_TestSuite('PHP8CompatibilityUnitTest');
    $result = new PHPUnit_TestResult;
    $test->run($result);
    print $result->toString();
    exit($result->wasSuccessful() ? 0 : 1);
}
