#!/bin/bash
# =============================================
# Serverless Compatibility Integration Tests
# =============================================
#
# Stands up a real Xataface application with MySQL and tests:
#   1. Database-backed session handler
#   2. Configurable templates_c (/tmp fallback and XF_TEMPLATES_C env var)
#   3. Proxy-aware IP validation (X-Forwarded-For, X-Real-IP)
#
# Prerequisites:
#   - PHP 8.0+ with mysqli extension
#   - MySQL/MariaDB running and accessible
#
# Environment variables:
#   MYSQL_HOST     (default: 127.0.0.1)
#   MYSQL_USER     (default: root)
#   MYSQL_PASSWORD (default: empty)
#   MYSQL_PORT     (default: 3306)
#   XATAFACE       (default: auto-detected from script location)
#
set -e

SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
XATAFACE="${XATAFACE:-$(cd "$SCRIPTPATH/.."; pwd -P)}"
MYSQL_HOST="${MYSQL_HOST:-127.0.0.1}"
MYSQL_USER="${MYSQL_USER:-root}"
MYSQL_PASSWORD="${MYSQL_PASSWORD:-}"
MYSQL_PORT="${MYSQL_PORT:-3306}"
TEST_DB="xf_serverless_test_$$"
PASSED=0
FAILED=0
PHASE_PASSED=0
PHASE_FAILED=0

echo "============================================="
echo "Serverless Compatibility Integration Tests"
echo "============================================="
echo "PHP version: $(php -r 'echo PHP_VERSION;')"
echo "XATAFACE:    $XATAFACE"
echo "MySQL:       $MYSQL_USER@$MYSQL_HOST:$MYSQL_PORT"
echo ""

# --- Helper functions ---

pass() {
    echo "  PASS: $1"
    PASSED=$((PASSED + 1))
    PHASE_PASSED=$((PHASE_PASSED + 1))
}

fail() {
    echo "  FAIL: $1"
    FAILED=$((FAILED + 1))
    PHASE_FAILED=$((PHASE_FAILED + 1))
}

reset_phase() {
    PHASE_PASSED=0
    PHASE_FAILED=0
}

# --- Phase 1: Syntax validation of new/changed files ---

echo "--- Phase 1: Syntax validation ---"
reset_phase

SERVERLESS_FILES=(
    "Dataface/DatabaseSessionHandler.php"
    "Dataface/Application.php"
    "Dataface/SkinTool.php"
    "config.inc.php"
    "init.php"
)

for f in "${SERVERLESS_FILES[@]}"; do
    if php -l "$XATAFACE/$f" 2>&1 | grep -q "No syntax errors"; then
        pass "Syntax OK: $f"
    else
        fail "Syntax error: $f"
    fi
done

echo ""

# --- Phase 2: Standalone unit tests (no database required) ---

echo "--- Phase 2: Standalone tests ---"
reset_phase

# Test 2.1: DatabaseSessionHandler class implements SessionHandlerInterface
php -r "
define('XFAPPROOT', '$XATAFACE/');
require_once '$XATAFACE/config.inc.php';
require_once '$XATAFACE/Dataface/DatabaseSessionHandler.php';

\$ref = new ReflectionClass('Dataface_DatabaseSessionHandler');
if (\$ref->implementsInterface('SessionHandlerInterface')) {
    echo 'OK';
} else {
    echo 'FAIL: does not implement SessionHandlerInterface';
    exit(1);
}
" 2>&1 && pass "DatabaseSessionHandler implements SessionHandlerInterface" || fail "DatabaseSessionHandler interface check"

# Test 2.2: DatabaseSessionHandler has all required methods
php -r "
define('XFAPPROOT', '$XATAFACE/');
require_once '$XATAFACE/config.inc.php';
require_once '$XATAFACE/Dataface/DatabaseSessionHandler.php';

\$ref = new ReflectionClass('Dataface_DatabaseSessionHandler');
\$required = ['open','close','read','write','destroy','gc'];
\$missing = [];
foreach (\$required as \$m) {
    if (!\$ref->hasMethod(\$m)) \$missing[] = \$m;
}
if (empty(\$missing)) {
    echo 'OK';
} else {
    echo 'FAIL: missing methods: ' . implode(', ', \$missing);
    exit(1);
}
" 2>&1 && pass "DatabaseSessionHandler has all required methods" || fail "DatabaseSessionHandler methods check"

# Test 2.3: XF_TEMPLATES_C env var is respected
RESULT=$(XF_TEMPLATES_C=/tmp/xf_test_envvar_$$ php -r "
define('XFAPPROOT', '$XATAFACE/');
require_once '$XATAFACE/config.inc.php';
echo XFTEMPLATES_C;
" 2>&1)
if echo "$RESULT" | grep -q "/tmp/xf_test_envvar_"; then
    pass "XF_TEMPLATES_C env var sets XFTEMPLATES_C constant"
    # Clean up
    rmdir "/tmp/xf_test_envvar_$$" 2>/dev/null || true
else
    fail "XF_TEMPLATES_C env var not respected (got: $RESULT)"
fi

# Test 2.4: getClientIp() method exists on Application
if grep -q 'function getClientIp' "$XATAFACE/Dataface/Application.php"; then
    pass "Application::getClientIp() method exists"
else
    fail "Application::getClientIp() method missing from Application.php"
fi

# Test 2.5: startSession() references DatabaseSessionHandler
if grep -q "session_handler.*database" "$XATAFACE/Dataface/Application.php" && \
   grep -q "DatabaseSessionHandler" "$XATAFACE/Dataface/Application.php"; then
    pass "startSession() wired to DatabaseSessionHandler"
else
    fail "startSession() not wired to DatabaseSessionHandler"
fi

echo ""

# --- Phase 3: Integration tests with real database ---

echo "--- Phase 3: Full app integration tests ---"
reset_phase

# Check MySQL connectivity
MYSQL_CONN_HOST="$MYSQL_HOST"
if [ -n "$MYSQL_PORT" ] && [ "$MYSQL_PORT" != "3306" ]; then
    MYSQL_CONN_HOST="${MYSQL_HOST}:${MYSQL_PORT}"
fi

MYSQL_CHECK=$(php -r "
mysqli_report(MYSQLI_REPORT_OFF);
\$link = @mysqli_connect('$MYSQL_HOST', '$MYSQL_USER', '$MYSQL_PASSWORD', '', $MYSQL_PORT);
if (!\$link) {
    echo 'UNAVAILABLE';
    exit(0);
}
echo 'OK';
" 2>&1)
if [ "$MYSQL_CHECK" != "OK" ]; then
    echo "  SKIP: MySQL not available at $MYSQL_HOST:$MYSQL_PORT"
    echo ""
    echo "============================================="
    echo "Results: $PASSED passed, $FAILED failed, Phase 3 skipped"
    echo "============================================="
    if [ $FAILED -gt 0 ]; then exit 1; fi
    exit 0
fi

# Create test app directory
TEST_APP_DIR=$(mktemp -d)
TEST_TEMPLATES_C="$TEST_APP_DIR/templates_c"
mkdir -p "$TEST_TEMPLATES_C"
chmod 777 "$TEST_TEMPLATES_C"

# Create the conf.ini.php
cat > "$TEST_APP_DIR/conf.ini.php" << CONFEOF
;<?php exit;
[_database]
    host=$MYSQL_CONN_HOST
    user=$MYSQL_USER
    password=$MYSQL_PASSWORD
    name=$TEST_DB
    driver=mysqli

[_tables]
    ServerlessTest=ServerlessTest

[_auth]
    session_handler=database

trust_proxy_headers=1
CONFEOF

# Create index.php
cat > "$TEST_APP_DIR/index.php" << 'INDEXEOF'
<?php
require_once 'xataface/public-api.php';
df_init(__FILE__, 'xataface')->display();
INDEXEOF

# Symlink xataface
ln -s "$XATAFACE" "$TEST_APP_DIR/xataface"

# Copy test infrastructure
cp "$XATAFACE/tests/lib/PHPUnit.php" "$TEST_APP_DIR/"
cp "$XATAFACE/tests/lib/PHPUnit/TestCase.php" "$TEST_APP_DIR/"
cp "$XATAFACE/tests/lib/PHPUnit/TestSuite.php" "$TEST_APP_DIR/"
cp "$XATAFACE/tests/lib/PHPUnit/TestResult.php" "$TEST_APP_DIR/"
cp "$XATAFACE/tests/lib/PHPUnit/TestFailure.php" "$TEST_APP_DIR/"
cp "$XATAFACE/tests/lib/PHPUnit/TestListener.php" "$TEST_APP_DIR/"
cp "$XATAFACE/tests/lib/PHPUnit/Assert.php" "$TEST_APP_DIR/"
mkdir -p "$TEST_APP_DIR/PHPUnit"
for f in TestCase TestSuite TestResult TestFailure TestListener Assert; do
    if [ -f "$TEST_APP_DIR/$f.php" ]; then
        mv "$TEST_APP_DIR/$f.php" "$TEST_APP_DIR/PHPUnit/$f.php"
    fi
done
cp "$XATAFACE/tests/lib/PHPUnit.php" "$TEST_APP_DIR/"
# Copy required test libs
cp "$XATAFACE/tests/lib/mysql_functions.php" "$TEST_APP_DIR/" 2>/dev/null || true

# Copy BaseTest and the serverless test
cp "$XATAFACE/tests/tests/BaseTest.php" "$TEST_APP_DIR/"
cp "$XATAFACE/tests/tests/testconfig.php" "$TEST_APP_DIR/"
cp "$XATAFACE/tests/tests/ServerlessCompatibilityTest.php" "$TEST_APP_DIR/"

# Copy Profiles table definition if it exists
if [ -d "$XATAFACE/tests/tables/Profiles" ]; then
    mkdir -p "$TEST_APP_DIR/tables/Profiles"
    cp -r "$XATAFACE/tests/tables/Profiles/"* "$TEST_APP_DIR/tables/Profiles/"
fi

# Create the test database and bootstrap table
php -r "
\$link = mysqli_connect('$MYSQL_HOST', '$MYSQL_USER', '$MYSQL_PASSWORD', '', $MYSQL_PORT);
mysqli_query(\$link, 'DROP DATABASE IF EXISTS \`$TEST_DB\`');
mysqli_query(\$link, 'CREATE DATABASE \`$TEST_DB\`');
mysqli_select_db(\$link, '$TEST_DB');
mysqli_query(\$link, 'CREATE TABLE ServerlessTest (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(128) NOT NULL
) ENGINE=InnoDB');
mysqli_query(\$link, \"INSERT INTO ServerlessTest (name) VALUES ('test1')\");
echo 'OK';
" 2>&1

if [ $? -ne 0 ]; then
    fail "Database setup"
    echo ""
    echo "============================================="
    echo "Results: $PASSED passed, $FAILED failed"
    echo "============================================="
    # Cleanup
    rm -rf "$TEST_APP_DIR"
    exit 1
fi
pass "Database and test app created"

# Run the serverless compatibility test suite
echo ""
echo "  Running ServerlessCompatibilityTest suite..."
echo ""

TEST_OUTPUT=$(cd "$TEST_APP_DIR" && php -d include_path=".:$XATAFACE:$TEST_APP_DIR" -r "
\$_SERVER['PHP_SELF'] = '/index.php';
\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
\$_SERVER['HTTP_HOST'] = 'localhost';
\$_SERVER['REQUEST_URI'] = '/index.php';
\$_SERVER['DOCUMENT_ROOT'] = '$TEST_APP_DIR';

require_once 'testconfig.php';
require_once 'PHPUnit.php';
require_once 'ServerlessCompatibilityTest.php';

\$test = new PHPUnit_TestSuite('ServerlessCompatibilityTest');
\$result = new PHPUnit_TestResult;
\$test->run(\$result);
print \$result->toString();
if (!\$result->wasSuccessful()) {
    exit(1);
}
" 2>&1)
TEST_EXIT=$?

echo "$TEST_OUTPUT"
echo ""

if [ $TEST_EXIT -eq 0 ]; then
    # Count tests from PHPUnit output
    SUITE_COUNT=$(echo "$TEST_OUTPUT" | grep -oP '\d+ run' | grep -oP '\d+' || echo "0")
    SUITE_FAILURES=$(echo "$TEST_OUTPUT" | grep -oP '\d+ failure' | grep -oP '\d+' || echo "0")
    if [ -z "$SUITE_COUNT" ]; then SUITE_COUNT=0; fi
    if [ -z "$SUITE_FAILURES" ]; then SUITE_FAILURES=0; fi
    pass "ServerlessCompatibilityTest suite ($SUITE_COUNT tests, $SUITE_FAILURES failures)"
else
    fail "ServerlessCompatibilityTest suite"
fi

# --- Phase 4: Real app boot with serverless config ---

echo ""
echo "--- Phase 4: Real app smoke tests ---"
reset_phase

# Test 4.1: App boots with session_handler=database
BOOT_OUTPUT=$(cd "$TEST_APP_DIR" && php -d include_path=".:$XATAFACE:$TEST_APP_DIR" -r "
\$_SERVER['PHP_SELF'] = '/index.php';
\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
\$_SERVER['HTTP_HOST'] = 'localhost';
\$_SERVER['REQUEST_URI'] = '/index.php';
\$_SERVER['DOCUMENT_ROOT'] = '$TEST_APP_DIR';

require_once 'xataface/public-api.php';
\$app = df_init('$TEST_APP_DIR/index.php', 'xataface');

// Verify the auth config has session_handler=database
if (@\$app->_conf['_auth']['session_handler'] === 'database') {
    echo 'CONFIG_OK ';
} else {
    echo 'CONFIG_FAIL ';
}

// Start session - this should use the database handler
\$app->startSession();
if (session_id() !== '') {
    echo 'SESSION_OK ';
} else {
    echo 'SESSION_FAIL ';
}

// Verify session data persists through database
\$_SESSION['__serverless_test'] = 'hello_cloud';
session_write_close();

// Check the database for our session
\$sid = session_id();
\$escaped = xf_db_real_escape_string(\$sid, \$app->db());
\$res = xf_db_query(\"SELECT session_data FROM __xf_sessions WHERE session_id = '\$escaped'\", \$app->db());
if (\$res && xf_db_num_rows(\$res) > 0) {
    \$row = xf_db_fetch_assoc(\$res);
    if (strpos(\$row['session_data'], 'hello_cloud') !== false) {
        echo 'DB_SESSION_OK';
    } else {
        echo 'DB_SESSION_DATA_FAIL';
    }
} else {
    echo 'DB_SESSION_FAIL';
}
" 2>&1)

echo "  Boot output: $BOOT_OUTPUT"

if echo "$BOOT_OUTPUT" | grep -q "CONFIG_OK"; then
    pass "App reads session_handler=database from conf.ini"
else
    fail "App reads session_handler=database from conf.ini"
fi

if echo "$BOOT_OUTPUT" | grep -q "SESSION_OK"; then
    pass "App starts session with database handler"
else
    fail "App starts session with database handler"
fi

if echo "$BOOT_OUTPUT" | grep -q "DB_SESSION_OK"; then
    pass "Session data persisted to __xf_sessions table"
else
    fail "Session data persisted to __xf_sessions table"
fi

# Test 4.2: App boots with XF_TEMPLATES_C pointing to custom /tmp path
CUSTOM_TC=$(mktemp -d)
TC_OUTPUT=$(cd "$TEST_APP_DIR" && XF_TEMPLATES_C="$CUSTOM_TC" php -d include_path=".:$XATAFACE:$TEST_APP_DIR" -r "
\$_SERVER['PHP_SELF'] = '/index.php';
\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
\$_SERVER['HTTP_HOST'] = 'localhost';
\$_SERVER['REQUEST_URI'] = '/index.php';
\$_SERVER['DOCUMENT_ROOT'] = '$TEST_APP_DIR';

require_once 'xataface/public-api.php';
\$app = df_init('$TEST_APP_DIR/index.php', 'xataface');

if (strpos(XFTEMPLATES_C, '$CUSTOM_TC') === 0) {
    echo 'ENV_TC_OK';
} else {
    echo 'ENV_TC_FAIL: ' . XFTEMPLATES_C;
}
" 2>&1)

if echo "$TC_OUTPUT" | grep -q "ENV_TC_OK"; then
    pass "XF_TEMPLATES_C env var overrides XFTEMPLATES_C constant"
else
    fail "XF_TEMPLATES_C env var override ($TC_OUTPUT)"
fi
rm -rf "$CUSTOM_TC"

# Test 4.3: App boots with read-only templates_c (falls back to /tmp)
RO_APP_DIR=$(mktemp -d)
cp "$TEST_APP_DIR/conf.ini.php" "$RO_APP_DIR/"
cp "$TEST_APP_DIR/index.php" "$RO_APP_DIR/"
ln -s "$XATAFACE" "$RO_APP_DIR/xataface"
# Do NOT create templates_c — simulating read-only app directory

RO_OUTPUT=$(cd "$RO_APP_DIR" && php -d include_path=".:$XATAFACE:$RO_APP_DIR" -r "
\$_SERVER['PHP_SELF'] = '/index.php';
\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
\$_SERVER['HTTP_HOST'] = 'localhost';
\$_SERVER['REQUEST_URI'] = '/index.php';
\$_SERVER['DOCUMENT_ROOT'] = '$RO_APP_DIR';

require_once 'xataface/public-api.php';
\$app = df_init('$RO_APP_DIR/index.php', 'xataface');

// If we got here without dying, the fallback worked
if (defined('XFTEMPLATES_C') && is_writable(rtrim(XFTEMPLATES_C, '/'))) {
    echo 'FALLBACK_OK';
} else {
    echo 'FALLBACK_FAIL';
}
" 2>&1)

if echo "$RO_OUTPUT" | grep -q "FALLBACK_OK"; then
    pass "App boots without templates_c dir (uses /tmp fallback)"
else
    fail "App boots without templates_c dir ($RO_OUTPUT)"
fi
rm -rf "$RO_APP_DIR"

# Test 4.4: getClientIp() works in real app with trust_proxy_headers
IP_OUTPUT=$(cd "$TEST_APP_DIR" && php -d include_path=".:$XATAFACE:$TEST_APP_DIR" -r "
\$_SERVER['PHP_SELF'] = '/index.php';
\$_SERVER['REMOTE_ADDR'] = '10.128.0.5';
\$_SERVER['HTTP_HOST'] = 'localhost';
\$_SERVER['REQUEST_URI'] = '/index.php';
\$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.42, 10.128.0.5';

require_once 'xataface/public-api.php';
\$app = df_init('$TEST_APP_DIR/index.php', 'xataface');

// trust_proxy_headers=1 is in conf.ini
\$ip = \$app->getClientIp();
if (\$ip === '203.0.113.42') {
    echo 'PROXY_IP_OK';
} else {
    echo 'PROXY_IP_FAIL: ' . \$ip;
}
" 2>&1)

if echo "$IP_OUTPUT" | grep -q "PROXY_IP_OK"; then
    pass "getClientIp() resolves X-Forwarded-For in real app context"
else
    fail "getClientIp() proxy resolution ($IP_OUTPUT)"
fi

# --- Cleanup ---
echo ""
echo "--- Cleanup ---"
php -r "
\$link = mysqli_connect('$MYSQL_HOST', '$MYSQL_USER', '$MYSQL_PASSWORD', '', $MYSQL_PORT);
mysqli_query(\$link, 'DROP DATABASE IF EXISTS \`$TEST_DB\`');
echo 'OK';
" 2>&1
rm -rf "$TEST_APP_DIR"
echo "  Cleaned up test database and app directory"

# --- Summary ---
echo ""
echo "============================================="
echo "Results: $PASSED passed, $FAILED failed"
echo "============================================="
if [ $FAILED -gt 0 ]; then
    echo "FAILURE"
    exit 1
else
    echo "SUCCESS"
    exit 0
fi
