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
}

fail() {
    echo "  FAIL: $1"
    FAILED=$((FAILED + 1))
}

# Helper to run PHP code in the context of a booted Xataface app.
# Writes PHP to a temp file to avoid all shell quoting issues with php -r.
# Usage: run_in_app <<'PHPCODE'
#   echo $app->_conf['_database']['name'];
# PHPCODE
# Or with env vars: XF_TEMPLATES_C=/tmp/foo run_in_app <<'PHPCODE' ...
run_in_app() {
    local tmp_php
    tmp_php=$(mktemp /tmp/xf_serverless_test_XXXXXX.php)
    # Write bootstrap header (needs shell vars for paths)
    cat > "$tmp_php" << BOOTSTRAP
<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
\$_SERVER['PHP_SELF'] = '/index.php';
\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
\$_SERVER['HTTP_HOST'] = 'localhost';
\$_SERVER['REQUEST_URI'] = '/index.php';
\$_SERVER['DOCUMENT_ROOT'] = '$TEST_APP_DIR';
require_once 'xataface/public-api.php';
\$app = df_init('$TEST_APP_DIR/index.php', 'xataface');
BOOTSTRAP
    # Append test code from stdin (no shell expansion — literal PHP)
    cat >> "$tmp_php"
    local result
    result=$(cd "$TEST_APP_DIR" && php -d include_path=".:$XATAFACE:$TEST_APP_DIR" -d display_errors=stderr "$tmp_php" 2>/dev/null) || true
    rm -f "$tmp_php"
    echo "$result"
}

# Helper to run PHP in the context of a specific app directory.
# Usage: run_in_dir /path/to/appdir <<'PHPCODE' ...
run_in_dir() {
    local app_dir="$1"
    local tmp_php
    tmp_php=$(mktemp /tmp/xf_serverless_test_XXXXXX.php)
    cat > "$tmp_php" << BOOTSTRAP
<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
\$_SERVER['PHP_SELF'] = '/index.php';
\$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
\$_SERVER['HTTP_HOST'] = 'localhost';
\$_SERVER['REQUEST_URI'] = '/index.php';
\$_SERVER['DOCUMENT_ROOT'] = '$app_dir';
require_once 'xataface/public-api.php';
\$app = df_init('$app_dir/index.php', 'xataface');
BOOTSTRAP
    cat >> "$tmp_php"
    local result
    result=$(cd "$app_dir" && php -d include_path=".:$XATAFACE:$app_dir" -d display_errors=stderr "$tmp_php" 2>/dev/null) || true
    rm -f "$tmp_php"
    echo "$result"
}

# --- Phase 1: Syntax validation of serverless files ---

echo "--- Phase 1: Syntax validation ---"

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

# --- Phase 2: Standalone tests (no database required) ---

echo "--- Phase 2: Standalone tests ---"

# Test 2.1: DatabaseSessionHandler class implements SessionHandlerInterface
RESULT=$(php -d display_errors=stderr -r "
define('XFAPPROOT', '$XATAFACE/');
require_once '$XATAFACE/config.inc.php';
require_once '$XATAFACE/Dataface/DatabaseSessionHandler.php';
\$ref = new ReflectionClass('Dataface_DatabaseSessionHandler');
echo \$ref->implementsInterface('SessionHandlerInterface') ? 'OK' : 'FAIL';
" 2>/dev/null)
if [ "$RESULT" = "OK" ]; then
    pass "DatabaseSessionHandler implements SessionHandlerInterface"
else
    fail "DatabaseSessionHandler interface check ($RESULT)"
fi

# Test 2.2: DatabaseSessionHandler has all required methods
RESULT=$(php -d display_errors=stderr -r "
define('XFAPPROOT', '$XATAFACE/');
require_once '$XATAFACE/config.inc.php';
require_once '$XATAFACE/Dataface/DatabaseSessionHandler.php';
\$ref = new ReflectionClass('Dataface_DatabaseSessionHandler');
\$required = ['open','close','read','write','destroy','gc'];
\$missing = [];
foreach (\$required as \$m) {
    if (!\$ref->hasMethod(\$m)) \$missing[] = \$m;
}
echo empty(\$missing) ? 'OK' : 'FAIL:' . implode(',', \$missing);
" 2>/dev/null)
if [ "$RESULT" = "OK" ]; then
    pass "DatabaseSessionHandler has all required methods"
else
    fail "DatabaseSessionHandler methods check ($RESULT)"
fi

# Test 2.3: XF_TEMPLATES_C env var is respected
RESULT=$(XF_TEMPLATES_C=/tmp/xf_test_envvar_$$ php -d display_errors=stderr -r "
define('XFAPPROOT', '$XATAFACE/');
require_once '$XATAFACE/config.inc.php';
echo XFTEMPLATES_C;
" 2>/dev/null)
if echo "$RESULT" | grep -q "/tmp/xf_test_envvar_"; then
    pass "XF_TEMPLATES_C env var sets XFTEMPLATES_C constant"
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

echo "--- Phase 3: Database integration tests ---"

# Check MySQL connectivity
MYSQL_CONN_HOST="$MYSQL_HOST"
if [ -n "$MYSQL_PORT" ] && [ "$MYSQL_PORT" != "3306" ]; then
    MYSQL_CONN_HOST="${MYSQL_HOST}:${MYSQL_PORT}"
fi

MYSQL_CHECK=$(php -d display_errors=stderr -r "
mysqli_report(MYSQLI_REPORT_OFF);
\$link = @mysqli_connect('$MYSQL_HOST', '$MYSQL_USER', '$MYSQL_PASSWORD', '', $MYSQL_PORT);
echo \$link ? 'OK' : 'UNAVAILABLE';
" 2>/dev/null)
if [ "$MYSQL_CHECK" != "OK" ]; then
    echo "  SKIP: MySQL not available at $MYSQL_HOST:$MYSQL_PORT"
    echo ""
    echo "============================================="
    echo "Results: $PASSED passed, $FAILED failed, DB tests skipped"
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
# NOTE: trust_proxy_headers MUST be before any [section] to be a root-level config
cat > "$TEST_APP_DIR/conf.ini.php" << CONFEOF
;<?php exit;
trust_proxy_headers=1

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
CONFEOF

# Create index.php
cat > "$TEST_APP_DIR/index.php" << 'INDEXEOF'
<?php
require_once 'xataface/public-api.php';
df_init(__FILE__, 'xataface')->display();
INDEXEOF

# Symlink xataface
ln -s "$XATAFACE" "$TEST_APP_DIR/xataface"

# Create the test database
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
    rm -rf "$TEST_APP_DIR"
    echo ""
    echo "============================================="
    echo "Results: $PASSED passed, $FAILED failed"
    echo "============================================="
    exit 1
fi
pass "Test database and app created"

echo ""

# -------------------------------------------------------------------
# Test 3.1: DatabaseSessionHandler CRUD via real database connection
# -------------------------------------------------------------------
CRUD_OUTPUT=$(run_in_app <<'PHPCODE'
require_once 'Dataface/DatabaseSessionHandler.php';
$db = $app->db();
$handler = new Dataface_DatabaseSessionHandler($db);

// Drop table if leftover from previous run
xf_db_query('DROP TABLE IF EXISTS __xf_sessions', $db);

// open() should create table
$handler->open('', 'PHPSESSID');
$res = xf_db_query("SHOW TABLES LIKE '__xf_sessions'", $db);
if (xf_db_num_rows($res) !== 1) { echo 'FAIL_CREATE'; exit(1); }
echo 'TABLE_OK ';

// write + read
$sid = 'test_' . md5(uniqid('', true));
$handler->write($sid, 'UserName|s:5:"admin";');
$data = $handler->read($sid);
if ($data !== 'UserName|s:5:"admin";') { echo 'FAIL_READ:' . $data; exit(1); }
echo 'WRITE_READ_OK ';

// update
$handler->write($sid, 'UserName|s:6:"admin2";');
$data = $handler->read($sid);
if ($data !== 'UserName|s:6:"admin2";') { echo 'FAIL_UPDATE'; exit(1); }
echo 'UPDATE_OK ';

// destroy
$handler->destroy($sid);
$data = $handler->read($sid);
if ($data !== '') { echo 'FAIL_DESTROY'; exit(1); }
echo 'DESTROY_OK ';

// gc
$sid2 = 'old_' . md5(uniqid('', true));
$handler->write($sid2, 'old_data');
$esc = xf_db_real_escape_string($sid2, $db);
xf_db_query("UPDATE __xf_sessions SET last_access = " . (time() - 7200) . " WHERE session_id = '$esc'", $db);
$removed = $handler->gc(3600);
if ($removed < 1) { echo 'FAIL_GC'; exit(1); }
if ($handler->read($sid2) !== '') { echo 'FAIL_GC_VERIFY'; exit(1); }
echo 'GC_OK ';

// special characters
$sid3 = 'special_' . md5(uniqid('', true));
$specialData = "quotes'and\"backslash\\";
$handler->write($sid3, $specialData);
if ($handler->read($sid3) !== $specialData) { echo 'FAIL_SPECIAL'; exit(1); }
echo 'SPECIAL_OK';
PHPCODE
)

echo "  CRUD output: $CRUD_OUTPUT"

for check in TABLE_OK WRITE_READ_OK UPDATE_OK DESTROY_OK GC_OK SPECIAL_OK; do
    if echo "$CRUD_OUTPUT" | grep -q "$check"; then
        pass "DatabaseSessionHandler $check"
    else
        fail "DatabaseSessionHandler $check"
    fi
done

echo ""

# -------------------------------------------------------------------
# Test 3.2: App boots with session_handler=database and sessions
#           are stored in MySQL
# -------------------------------------------------------------------
BOOT_OUTPUT=$(run_in_app <<'PHPCODE'
// Use output buffering so echo doesn't trigger "headers already sent"
// for setcookie/session_start calls in startSession().
ob_start();
$results = [];

// Verify the auth config has session_handler=database
$results[] = (@$app->_conf['_auth']['session_handler'] === 'database') ? 'CONFIG_OK' : 'CONFIG_FAIL';

// Start session - this should use the database handler
$app->startSession();
$results[] = (session_id() !== '') ? 'SESSION_OK' : 'SESSION_FAIL';

// Verify session data persists through database
$_SESSION['__serverless_test'] = 'hello_cloud';
session_write_close();

// Check the database for our session
$sid = session_id();
$escaped = xf_db_real_escape_string($sid, $app->db());
$res = xf_db_query("SELECT session_data FROM __xf_sessions WHERE session_id = '$escaped'", $app->db());
if ($res && xf_db_num_rows($res) > 0) {
    $row = xf_db_fetch_assoc($res);
    if (strpos($row['session_data'], 'hello_cloud') !== false) {
        $results[] = 'DB_SESSION_OK';
    } else {
        $results[] = 'DB_SESSION_DATA_FAIL';
    }
} else {
    $results[] = 'DB_SESSION_FAIL';
}

ob_end_clean();
echo implode(' ', $results);
PHPCODE
)

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

echo ""

# -------------------------------------------------------------------
# Test 3.3: XF_TEMPLATES_C env var overrides the constant
# -------------------------------------------------------------------
CUSTOM_TC=$(mktemp -d)
TC_OUTPUT=$(XF_TEMPLATES_C="$CUSTOM_TC" run_in_app <<'PHPCODE'
if (strpos(XFTEMPLATES_C, getenv('XF_TEMPLATES_C')) === 0) {
    echo 'ENV_TC_OK';
} else {
    echo 'ENV_TC_FAIL: ' . XFTEMPLATES_C;
}
PHPCODE
)

if echo "$TC_OUTPUT" | grep -q "ENV_TC_OK"; then
    pass "XF_TEMPLATES_C env var overrides XFTEMPLATES_C constant"
else
    fail "XF_TEMPLATES_C env var override ($TC_OUTPUT)"
fi
rm -rf "$CUSTOM_TC"

# -------------------------------------------------------------------
# Test 3.4: App boots without templates_c dir (falls back to /tmp)
# -------------------------------------------------------------------
RO_APP_DIR=$(mktemp -d)
cp "$TEST_APP_DIR/conf.ini.php" "$RO_APP_DIR/"
cp "$TEST_APP_DIR/index.php" "$RO_APP_DIR/"
ln -s "$XATAFACE" "$RO_APP_DIR/xataface"
# Do NOT create templates_c — simulating read-only app directory

RO_OUTPUT=$(run_in_dir "$RO_APP_DIR" <<'PHPCODE'
if (defined('XFTEMPLATES_C') && is_writable(rtrim(XFTEMPLATES_C, DIRECTORY_SEPARATOR))) {
    echo 'FALLBACK_OK';
} else {
    echo 'FALLBACK_FAIL: ' . (defined('XFTEMPLATES_C') ? XFTEMPLATES_C : 'undefined');
}
PHPCODE
)

if echo "$RO_OUTPUT" | grep -q "FALLBACK_OK"; then
    pass "App boots without templates_c dir (uses /tmp fallback)"
else
    fail "App boots without templates_c dir ($RO_OUTPUT)"
fi
rm -rf "$RO_APP_DIR"

echo ""

# -------------------------------------------------------------------
# Test 3.5: getClientIp() resolves X-Forwarded-For in real app
# -------------------------------------------------------------------
IP_OUTPUT=$(run_in_app <<'PHPCODE'
// Override server vars after boot — getClientIp reads them live
$_SERVER['REMOTE_ADDR'] = '10.128.0.5';
$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.42, 10.128.0.5';

// trust_proxy_headers=1 is in conf.ini at root level
$ip = $app->getClientIp();
if ($ip === '203.0.113.42') {
    echo 'PROXY_IP_OK';
} else {
    echo 'PROXY_IP_FAIL: ' . $ip;
    echo ' trust=' . var_export(@$app->_conf['trust_proxy_headers'], true);
}
PHPCODE
)

if echo "$IP_OUTPUT" | grep -q "PROXY_IP_OK"; then
    pass "getClientIp() resolves X-Forwarded-For in real app"
else
    fail "getClientIp() proxy resolution ($IP_OUTPUT)"
fi

# -------------------------------------------------------------------
# Test 3.6: getClientIp() returns REMOTE_ADDR when trust disabled
# -------------------------------------------------------------------
# Create a second app without trust_proxy_headers
NOTRUST_APP_DIR=$(mktemp -d)
mkdir -p "$NOTRUST_APP_DIR/templates_c"
chmod 777 "$NOTRUST_APP_DIR/templates_c"
cat > "$NOTRUST_APP_DIR/conf.ini.php" << NTCONFEOF
;<?php exit;
[_database]
    host=$MYSQL_CONN_HOST
    user=$MYSQL_USER
    password=$MYSQL_PASSWORD
    name=$TEST_DB
    driver=mysqli

[_tables]
    ServerlessTest=ServerlessTest
NTCONFEOF
cp "$TEST_APP_DIR/index.php" "$NOTRUST_APP_DIR/"
ln -s "$XATAFACE" "$NOTRUST_APP_DIR/xataface"

NOTRUST_OUTPUT=$(run_in_dir "$NOTRUST_APP_DIR" <<'PHPCODE'
$_SERVER['REMOTE_ADDR'] = '10.128.0.5';
$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.42, 10.128.0.5';
$ip = $app->getClientIp();
if ($ip === '10.128.0.5') {
    echo 'NOTRUST_OK';
} else {
    echo 'NOTRUST_FAIL: ' . $ip;
}
PHPCODE
)

if echo "$NOTRUST_OUTPUT" | grep -q "NOTRUST_OK"; then
    pass "getClientIp() ignores X-Forwarded-For without trust_proxy_headers"
else
    fail "getClientIp() without trust ($NOTRUST_OUTPUT)"
fi
rm -rf "$NOTRUST_APP_DIR"

# -------------------------------------------------------------------
# Test 3.7: getClientIp() handles IPv6 in X-Forwarded-For
# -------------------------------------------------------------------
IPV6_OUTPUT=$(run_in_app <<'PHPCODE'
$_SERVER['REMOTE_ADDR'] = '::1';
$_SERVER['HTTP_X_FORWARDED_FOR'] = '2001:db8::1, ::1';
$ip = $app->getClientIp();
if ($ip === '2001:db8::1') {
    echo 'IPV6_OK';
} else {
    echo 'IPV6_FAIL: ' . $ip;
}
PHPCODE
)

if echo "$IPV6_OUTPUT" | grep -q "IPV6_OK"; then
    pass "getClientIp() handles IPv6 addresses"
else
    fail "getClientIp() IPv6 ($IPV6_OUTPUT)"
fi

# -------------------------------------------------------------------
# Test 3.8: getClientIp() rejects invalid IPs in X-Forwarded-For
# -------------------------------------------------------------------
INVALID_OUTPUT=$(run_in_app <<'PHPCODE'
$_SERVER['REMOTE_ADDR'] = '10.128.0.1';
$_SERVER['HTTP_X_FORWARDED_FOR'] = 'not-a-valid-ip';
unset($_SERVER['HTTP_X_REAL_IP']);
$ip = $app->getClientIp();
if ($ip === '10.128.0.1') {
    echo 'INVALID_REJECT_OK';
} else {
    echo 'INVALID_REJECT_FAIL: ' . $ip;
}
PHPCODE
)

if echo "$INVALID_OUTPUT" | grep -q "INVALID_REJECT_OK"; then
    pass "getClientIp() rejects invalid IPs, falls back to REMOTE_ADDR"
else
    fail "getClientIp() invalid IP rejection ($INVALID_OUTPUT)"
fi

# -------------------------------------------------------------------
# Test 3.9: getClientIp() falls back to X-Real-IP
# -------------------------------------------------------------------
REALIP_OUTPUT=$(run_in_app <<'PHPCODE'
$_SERVER['REMOTE_ADDR'] = '10.128.0.1';
unset($_SERVER['HTTP_X_FORWARDED_FOR']);
$_SERVER['HTTP_X_REAL_IP'] = '198.51.100.25';
$ip = $app->getClientIp();
if ($ip === '198.51.100.25') {
    echo 'REALIP_OK';
} else {
    echo 'REALIP_FAIL: ' . $ip;
}
PHPCODE
)

if echo "$REALIP_OUTPUT" | grep -q "REALIP_OK"; then
    pass "getClientIp() falls back to X-Real-IP header"
else
    fail "getClientIp() X-Real-IP fallback ($REALIP_OUTPUT)"
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
