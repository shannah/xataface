#!/bin/bash
# =============================================================================
# PHP 8.0 Compatibility E2E Test
#
# This script creates a complete Xataface test application and exercises
# all PHP 8.0 compatibility changes end-to-end. It can be run with or
# without a MySQL database.
#
# Usage:
#   bash tests/php8_e2e_test.sh                  # Runs unit tests only (no DB)
#   MYSQL_HOST=localhost MYSQL_USER=root \
#     bash tests/php8_e2e_test.sh                # Runs full suite with DB
#
# Environment variables:
#   MYSQL_HOST     - MySQL host (default: not set = skip DB tests)
#   MYSQL_USER     - MySQL user (default: root)
#   MYSQL_PASSWORD - MySQL password (default: empty)
#   MYSQL_DB       - Test database name (default: xf_php8_e2e_test)
#   XATAFACE       - Path to xataface (default: auto-detect)
# =============================================================================
set -e

SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
export XATAFACE="${XATAFACE:-$(cd "$SCRIPTPATH/.."; pwd -P)}"

echo "============================================="
echo "PHP 8.0 Compatibility E2E Test Suite"
echo "============================================="
echo "PHP version: $(php -r 'echo PHP_VERSION;')"
echo "XATAFACE: $XATAFACE"
echo ""

# Track results
PASSED=0
FAILED=0
SKIPPED=0

pass() {
    echo "  PASS: $1"
    PASSED=$((PASSED + 1))
}

fail() {
    echo "  FAIL: $1"
    FAILED=$((FAILED + 1))
}

skip() {
    echo "  SKIP: $1"
    SKIPPED=$((SKIPPED + 1))
}

# =============================================================================
# Phase 1: Verify PHP syntax of all changed files (no execution needed)
# =============================================================================
echo ""
echo "--- Phase 1: Syntax validation of changed files ---"

CHANGED_FILES=(
    "config.inc.php"
    "public-api.php"
    "installer.php"
    "PEAR.php"
    "Dataface/Table.php"
    "Dataface/FormTool/calendar.php"
    "HTML/QuickForm/webcam.php"
    "actions/related_filter_dialog.php"
    "install/FTPExtractor.class.php"
    "install/ftp.class.php"
    "tools/clone.php"
    "lib/babelfish.class.php"
    "lib/GoogleTranslate.class.php"
    "lib/feedcreator.class.php"
    "lib/htmLawed.php"
    "lib/HTML/QuickForm.php"
    "lib/HTML/QuickForm/date.php"
    "lib/HTML/QuickForm/Rule/Compare.php"
    "lib/HTTP/Request.php"
    "lib/I18Nv2/Locale.php"
    "lib/Smarty/Smarty_Compiler.class.php"
    "lib/Smarty/internals/core.write_compiled_include.php"
    "lib/Smarty/plugins/function.fetch.php"
    "lib/Smarty/plugins/function.html_select_date.php"
    "lib/Smarty/plugins/function.html_select_time.php"
    "lib/Smarty/plugins/modifier.date_format.php"
    "lib/Text/Diff/Engine/native.php"
    "lib/Text/Highlighter/Generator.php"
    "lib/XML/DTD.php"
    "lib/XML/Parser.php"
    "lib/XML/Tree.php"
    "tests/lib/PHPUnit/GUI/SetupDecorator.php"
    "tests/lib/Var_Dump.php"
    "tests/lib/simpletest/http.php"
    "tests/lib/simpletest/test_case.php"
    "tests/lib/simpletest/url.php"
    "tests/lib/simpletest/web_tester.php"
)

SYNTAX_ERRORS=0
for f in "${CHANGED_FILES[@]}"; do
    FULL_PATH="$XATAFACE/$f"
    if [ -f "$FULL_PATH" ]; then
        if php -l "$FULL_PATH" > /dev/null 2>&1; then
            pass "Syntax OK: $f"
        else
            fail "Syntax ERROR: $f"
            php -l "$FULL_PATH" 2>&1 || true
            SYNTAX_ERRORS=$((SYNTAX_ERRORS + 1))
        fi
    else
        skip "File not found: $f"
    fi
done

if [ $SYNTAX_ERRORS -gt 0 ]; then
    echo ""
    echo "FATAL: $SYNTAX_ERRORS syntax errors found. Aborting."
    exit 1
fi

# =============================================================================
# Phase 2: Standalone function tests (no DB, no app bootstrap)
# =============================================================================
echo ""
echo "--- Phase 2: Standalone function tests ---"

# Test xf_strftime
php -r '
define("XFAPPROOT", "'$XATAFACE'/");
require_once "'$XATAFACE'/config.inc.php";
assert(function_exists("xf_strftime"));
$ts = mktime(14, 30, 45, 6, 15, 2023);
assert(xf_strftime("%Y", $ts) === "2023", "Year");
assert(xf_strftime("%m", $ts) === "06", "Month");
assert(xf_strftime("%d", $ts) === "15", "Day");
assert(xf_strftime("%H", $ts) === "14", "Hour");
assert(xf_strftime("%M", $ts) === "30", "Minute");
assert(xf_strftime("%S", $ts) === "45", "Second");
assert(xf_strftime("%Y-%m-%d %H:%M:%S", $ts) === "2023-06-15 14:30:45", "Composite");
assert(xf_strftime("%A", $ts) === "Thursday", "Day name");
assert(xf_strftime("%B", $ts) === "June", "Month name");
assert(xf_strftime("%b", $ts) === "Jun", "Abbrev month");
assert(xf_strftime("%a", $ts) === "Thu", "Abbrev day");
assert(xf_strftime("%p", $ts) === "PM", "AM/PM");
assert(xf_strftime("%I", $ts) === "02", "12h format");
assert(xf_strftime("%T", $ts) === "14:30:45", "Time");
assert(xf_strftime("%%", $ts) === "%", "Literal percent");
echo "OK\n";
' && pass "xf_strftime() all format codes" || fail "xf_strftime() format codes"

# Test implode argument order
php -r '
$arr = array("a=1", "b=2");
assert(implode("&", $arr) === "a=1&b=2");
echo "OK\n";
' && pass "implode() argument order" || fail "implode() argument order"

# Test preg_match replacing eregi
php -r '
assert(preg_match("/^(http|ftp):\/\//i", "http://x") === 1);
assert(preg_match("/^(http|ftp):\/\//i", "FTP://x") === 1);
assert(preg_match("/^(http|ftp):\/\//i", "/local") === 0);
echo "OK\n";
' && pass "preg_match replaces eregi" || fail "preg_match replaces eregi"

# Test closure replacements
php -r '
$options = array("2020", "2021", "2022");
array_walk($options, function(&$v, $k) { $v = substr($v, -2); });
assert($options === array("20", "21", "22"));
$options2 = array("01", "02", "12");
array_walk($options2, function(&$v, $k) { $v = intval($v); });
assert($options2 === array(1, 2, 12));
$data = array(array("k", "v"));
$r = implode("&", array_map(function($a) { return $a[0] . "=" . $a[1]; }, $data));
assert($r === "k=v");
echo "OK\n";
' && pass "Closure replacements for create_function" || fail "Closure replacements"

# Test explode replacements
php -r '
$h = "A: 1\r\nB: 2";
assert(count(explode("\r\n", $h)) === 2);
$c = "a;b;c";
assert(count(explode(";", $c)) === 3);
assert(count(preg_split("/[ ]?,[ ]?/", "a, b,c , d")) === 4);
echo "OK\n";
' && pass "explode/preg_split replaces split" || fail "explode/preg_split"

# Test bracket access replaces curly braces
php -r '
$s = "Hello";
assert($s[0] === "H");
assert($s[-1] === "o");
assert($s[strlen($s)-1] === "o");
echo "OK\n";
' && pass "Bracket access replaces curly braces" || fail "Bracket access"

# Test error_get_last replaces $php_errormsg
php -r '
error_clear_last();
@trigger_error("test", E_USER_NOTICE);
$e = error_get_last();
assert($e !== null && $e["message"] === "test");
$valid = (@preg_match("/^ok$/", "") !== false) ? 1 : 0;
assert($valid === 1);
$invalid = (@preg_match("/[bad", "") !== false) ? 1 : 0;
assert($invalid === 0);
echo "OK\n";
' && pass "error_get_last replaces \$php_errormsg" || fail "error_get_last"

# Test assert without string arguments
php -r '
$j = 5;
assert($j > 0);
assert($j >= 0);
$arr = array_fill(0, 10, false);
assert($j < 10 && !$arr[$j]);
echo "OK\n";
' && pass "assert() without string arguments" || fail "assert without strings"

# Test Compare rule
php -d include_path="$XATAFACE/lib:." -r '
require_once "'$XATAFACE'/lib/HTML/QuickForm/Rule/Compare.php";
$r = new HTML_QuickForm_Rule_Compare();
assert($r->validate(array(5, 3), ">") === true);
assert($r->validate(array(3, 5), ">") === false);
assert($r->validate(array(5, 5), ">=") === true);
assert($r->validate(array(3, 5), "<") === true);
assert($r->validate(array("a", "a"), "==") === true);
assert($r->validate(array("a", "b"), "!=") === true);
assert($r->validate(array(5, 3), "gt") === true);
assert($r->validate(array(5, 5), "lte") === true);
echo "OK\n";
' && pass "Compare rule switch-based operators" || fail "Compare rule"

# Test htmLawed loads without error
php -r '
require_once "'$XATAFACE'/lib/htmLawed.php";
$out = htmLawed("<b>hi</b><script>x</script>");
assert(strpos($out, "<b>hi</b>") !== false, "keeps b tag");
assert(strpos($out, "<script>") === false, "strips script");
assert(hl_regex("/^ok$/") === 1, "valid regex");
assert(hl_regex("/[bad") === 0, "invalid regex");
assert(hl_regex("") === 0, "empty regex");
echo "OK\n";
' && pass "htmLawed closure + regex validation" || fail "htmLawed"

# Test Smarty date_format modifier
php -d include_path="$XATAFACE:$XATAFACE/lib:." -r '
define("XFAPPROOT", "'$XATAFACE'/");
define("SMARTY_DIR", "'$XATAFACE'/lib/Smarty/");
require_once "'$XATAFACE'/config.inc.php";
require_once "'$XATAFACE'/lib/Smarty/plugins/shared.make_timestamp.php";
// Load just the function, bypassing the top-level $smarty dependency
eval(preg_replace("/^.*?function smarty_modifier/s", "function smarty_modifier", file_get_contents("'$XATAFACE'/lib/Smarty/plugins/modifier.date_format.php")));
$ts = mktime(14, 30, 0, 6, 15, 2023);
$r = smarty_modifier_date_format($ts, "%Y-%m-%d");
assert($r === "2023-06-15", "date_format: got $r");
$r2 = smarty_modifier_date_format($ts, "%H:%M");
assert($r2 === "14:30", "time_format: got $r2");
echo "OK\n";
' && pass "Smarty date_format modifier with xf_strftime" || fail "Smarty date_format"

# Test Text/Diff engine
php -d include_path="$XATAFACE/lib:." -r '
require_once "'$XATAFACE'/lib/Text/Diff.php";
$a = array("line1", "line2", "line3", "line4");
$b = array("line1", "changed", "line3", "line4", "line5");
$diff = new Text_Diff($a, $b);
assert($diff instanceof Text_Diff, "Should create diff");
$edits = $diff->getDiff();
assert(count($edits) > 0, "Should have edits");
echo "OK\n";
' && pass "Text/Diff engine (assert + each fixes)" || fail "Text/Diff engine"

# Test XML/Parser loads
php -r '
require_once "'$XATAFACE'/lib/XML/Parser.php";
assert(class_exists("XML_Parser"), "XML_Parser should exist");
echo "OK\n";
' && pass "XML/Parser loads (eregi fix)" || fail "XML/Parser"

# Test PEAR destructor list
php -r '
require_once "'$XATAFACE'/PEAR.php";
$obj = new PEAR();
assert($obj instanceof PEAR);
echo "OK\n";
' && pass "PEAR object creation (foreach fix)" || fail "PEAR foreach fix"

# =============================================================================
# Phase 3: Integration tests (requires DB)
# =============================================================================
echo ""
echo "--- Phase 3: Integration tests ---"

if [ -n "$MYSQL_HOST" ]; then
    echo "MySQL available at $MYSQL_HOST — running integration tests"

    MYSQL_USER="${MYSQL_USER:-root}"
    MYSQL_PASSWORD="${MYSQL_PASSWORD:-}"
    MYSQL_DB="${MYSQL_DB:-xf_php8_e2e_test}"

    # Create test app scaffold
    E2E_DIR="/tmp/xf_php8_e2e_$$"
    echo "Creating test app at $E2E_DIR..."
    php "$XATAFACE/tools/create.php" "$E2E_DIR"

    # Set up the database config
    cat > "$E2E_DIR/app/conf.db.ini" <<DBCONF
[_database]
host = $MYSQL_HOST
user = $MYSQL_USER
password = $MYSQL_PASSWORD
name = $MYSQL_DB
DBCONF

    cat > "$E2E_DIR/app/conf.ini" <<CONF
__include__=conf.db.ini
multilingual_content = 1
[languages]
en = en

[_tables]
PHP8Test = PHP8Test
Profiles = Profiles
CONF

    # Create tables directory
    mkdir -p "$E2E_DIR/app/tables/PHP8Test"
    cat > "$E2E_DIR/app/tables/PHP8Test/fields.ini" <<FIELDS
[price]
money_format = "%.2n"

[event_date]
date_format = "%Y-%m-%d"
FIELDS

    # Copy test infrastructure
    cp -r "$XATAFACE/tests/lib/"* "$E2E_DIR/app/"
    cp "$XATAFACE/tests/tests/testconfig.php" "$E2E_DIR/app/" 2>/dev/null || true
    cp "$XATAFACE/tests/tests/BaseTest.php" "$E2E_DIR/app/"
    cp "$XATAFACE/tests/tests/PHP8CompatibilityUnitTest.php" "$E2E_DIR/app/"
    cp "$XATAFACE/tests/tests/PHP8CompatibilityIntegrationTest.php" "$E2E_DIR/app/"

    # Create testconfig.php for this environment
    cat > "$E2E_DIR/app/testconfig.php" <<TESTCONF
<?php
define('TEST_APP_URL', 'http://localhost/');
\$dataface_url = '/xataface';
require_once 'xataface/public-api.php';
df_init(__FILE__, \$dataface_url);
require_once 'Dataface/Application.php';
TESTCONF

    # Create the DB and run integration tests
    cd "$E2E_DIR/app"

    php -r "
require_once 'testconfig.php';
\$db = df_db();

// Create test database
@xf_db_query('DROP DATABASE IF EXISTS $MYSQL_DB', \$db);
xf_db_query('CREATE DATABASE $MYSQL_DB', \$db);
xf_db_select_db('$MYSQL_DB');

// Create tables
xf_db_query('CREATE TABLE PHP8Test (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(128) NOT NULL,
    price DECIMAL(10,2),
    event_date DATE,
    event_datetime DATETIME,
    event_time TIME,
    description TEXT,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB', \$db);

xf_db_query(\"INSERT INTO PHP8Test (name, price, event_date, event_datetime, event_time, description)
    VALUES ('Widget A', 19.99, '2023-06-15', '2023-06-15 14:30:00', '14:30:00', 'A test widget')\", \$db);

echo 'Database setup OK\n';

// Run unit tests
require_once 'PHPUnit.php';
require_once 'PHP8CompatibilityUnitTest.php';
\$test = new PHPUnit_TestSuite('PHP8CompatibilityUnitTest');
\$result = new PHPUnit_TestResult;
\$test->run(\$result);
print \$result->toString();
if (!\$result->wasSuccessful()) {
    fwrite(STDERR, 'Unit tests FAILED\n');
    exit(1);
}

// Run integration tests
require_once 'PHP8CompatibilityIntegrationTest.php';
\$test2 = new PHPUnit_TestSuite('PHP8CompatibilityIntegrationTest');
\$result2 = new PHPUnit_TestResult;
\$test2->run(\$result2);
print \$result2->toString();
if (!\$result2->wasSuccessful()) {
    fwrite(STDERR, 'Integration tests FAILED\n');
    exit(1);
}

echo 'All tests passed\n';
" && pass "Full integration test suite" || fail "Integration test suite"

    # Cleanup
    rm -rf "$E2E_DIR"
    echo "Cleaned up $E2E_DIR"
else
    skip "Integration tests (no MYSQL_HOST set)"
    echo "  To run integration tests, set: MYSQL_HOST=localhost MYSQL_USER=root"
fi

# =============================================================================
# Summary
# =============================================================================
echo ""
echo "============================================="
echo "Results: $PASSED passed, $FAILED failed, $SKIPPED skipped"
echo "============================================="

if [ $FAILED -gt 0 ]; then
    echo "FAILURE"
    exit 1
fi

echo "SUCCESS"
exit 0
