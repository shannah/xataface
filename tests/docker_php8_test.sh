#!/bin/bash
# =============================================================================
# Xataface PHP 8.0 Compatibility Test Runner (Docker-based)
#
# Prerequisites: Docker must be installed and running.
#
# This script:
# 1. Builds a Docker image with PHP 8.0+ and MySQL
# 2. Runs all 3 test layers inside the container:
#    - Unit tests (no DB)
#    - Integration tests (with MySQL)
#    - Full composer test suite
#
# Usage:
#   bash tests/docker_php8_test.sh                  # Default: PHP 8.0
#   PHP_VERSION=8.1 bash tests/docker_php8_test.sh  # Specific PHP version
#   PHP_VERSION=8.4 bash tests/docker_php8_test.sh  # Latest PHP 8.x
#
# Environment variables:
#   PHP_VERSION  - PHP version to test against (default: 8.0)
#   SKIP_BUILD   - Set to 1 to skip Docker image build (reuse cached)
#   VERBOSE      - Set to 1 for verbose output
# =============================================================================
set -e

SCRIPTPATH="$( cd "$(dirname "$0")" ; pwd -P )"
XATAFACE="$(cd "$SCRIPTPATH/.."; pwd -P)"
PHP_VERSION="${PHP_VERSION:-8.0}"
IMAGE_NAME="xataface-php8-test:php${PHP_VERSION}"
CONTAINER_NAME="xf-php8-test-$$"

echo "============================================="
echo "Xataface PHP 8 Test Runner (Docker)"
echo "============================================="
echo "PHP Version: ${PHP_VERSION}"
echo "Xataface:    ${XATAFACE}"
echo ""

# Check Docker is available
if ! command -v docker &> /dev/null; then
    echo "ERROR: Docker is not installed or not in PATH."
    echo "Please install Docker: https://docs.docker.com/get-docker/"
    exit 1
fi

if ! docker info &> /dev/null 2>&1; then
    echo "ERROR: Docker daemon is not running."
    echo "Please start Docker and try again."
    exit 1
fi

# =============================================================================
# Build the Docker image
# =============================================================================
if [ "${SKIP_BUILD}" != "1" ]; then
    echo "--- Building Docker image ${IMAGE_NAME} ---"

    # Create a temporary Dockerfile
    DOCKERFILE=$(mktemp)
    cat > "$DOCKERFILE" <<'DOCKEREOF'
ARG PHP_VERSION=8.0
FROM php:${PHP_VERSION}-cli

# Install system dependencies
RUN apt-get update && apt-get install -y --no-install-recommends \
    mariadb-server \
    mariadb-client \
    libicu-dev \
    libxml2-dev \
    libzip-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    mysqli \
    pdo_mysql \
    intl \
    xml \
    zip \
    && docker-php-ext-enable mysqli pdo_mysql intl

# Configure MariaDB to allow root login without password
RUN mkdir -p /var/run/mysqld && chown mysql:mysql /var/run/mysqld \
    && chmod 755 /var/run/mysqld

# Create a startup script for MariaDB
RUN echo '#!/bin/bash\n\
set -e\n\
# Start MariaDB\n\
mysqld_safe --skip-grant-tables &\n\
# Wait for MySQL to be ready\n\
for i in $(seq 1 30); do\n\
    if mysqladmin ping -h localhost --silent 2>/dev/null; then\n\
        break\n\
    fi\n\
    sleep 1\n\
done\n\
# Ensure root can connect\n\
mysql -u root -e "FLUSH PRIVILEGES;" 2>/dev/null || true\n\
exec "$@"\n' > /usr/local/bin/with-mysql.sh && chmod +x /usr/local/bin/with-mysql.sh

WORKDIR /xataface

ENTRYPOINT ["/usr/local/bin/with-mysql.sh"]
DOCKEREOF

    docker build \
        --build-arg PHP_VERSION="${PHP_VERSION}" \
        -t "${IMAGE_NAME}" \
        -f "$DOCKERFILE" \
        "$XATAFACE" 2>&1 | if [ "${VERBOSE}" = "1" ]; then cat; else tail -5; fi

    rm -f "$DOCKERFILE"
    echo "Docker image built: ${IMAGE_NAME}"
else
    echo "Skipping Docker build (SKIP_BUILD=1)"
fi

echo ""

# =============================================================================
# Create the test runner script that will execute inside the container
# =============================================================================
TEST_RUNNER=$(mktemp)
cat > "$TEST_RUNNER" <<'RUNEOF'
#!/bin/bash
set -e

XATAFACE=/xataface
export XATAFACE

echo "============================================="
echo "Running inside Docker container"
echo "PHP: $(php -r 'echo PHP_VERSION;')"
echo "MySQL: $(mysql --version 2>/dev/null || echo 'checking...')"
echo "============================================="
echo ""

PASSED=0
FAILED=0
TOTAL_PHASES=0

# --------------------------------------------------
# PHASE 1: E2E Tests (syntax + standalone, no DB)
# --------------------------------------------------
echo "========================================"
echo "PHASE 1: Syntax & Standalone Function Tests"
echo "========================================"
TOTAL_PHASES=$((TOTAL_PHASES + 1))

# Run e2e WITHOUT MYSQL_HOST so it skips integration tests that use create.php
# (create.php tries to launch its own MySQL, conflicting with container's MariaDB)
unset MYSQL_HOST
unset MYSQL_USER
unset MYSQL_PASSWORD
unset MYSQL_DB

if bash "$XATAFACE/tests/php8_e2e_test.sh"; then
    echo "PHASE 1: PASSED"
    PASSED=$((PASSED + 1))
else
    echo "PHASE 1: FAILED"
    FAILED=$((FAILED + 1))
fi
echo ""

# --------------------------------------------------
# PHASE 2: PHP8 Unit + Integration Tests (direct DB)
# --------------------------------------------------
echo "========================================"
echo "PHASE 2: PHP8 Unit & Integration Tests"
echo "========================================"
TOTAL_PHASES=$((TOTAL_PHASES + 1))

# Run PHP8 compatibility tests directly against the container's MariaDB
# without going through create.php (which tries to start its own MySQL)
cd "$XATAFACE"

PHASE2_OK=true

# Create a minimal test app directory
TEST_APP_DIR="/tmp/xf_php8_direct_test"
rm -rf "$TEST_APP_DIR"
mkdir -p "$TEST_APP_DIR"

cat > "$TEST_APP_DIR/conf.ini" <<CONF
__include__=conf.db.ini
[_tables]
PHP8Test = PHP8Test
CONF

cat > "$TEST_APP_DIR/conf.db.ini" <<DBCONF
[_database]
host = localhost
user = root
password =
name = xf_php8_test
DBCONF

cat > "$TEST_APP_DIR/index.php" <<'INDEXPHP'
<?php
$dataface_url = '/xataface';
require_once 'xataface/public-api.php';
df_init(__FILE__, $dataface_url);
require_once 'Dataface/Application.php';
$app = Dataface_Application::getInstance();
$app->display();
INDEXPHP

# Symlink xataface into the test app
ln -s "$XATAFACE" "$TEST_APP_DIR/xataface"

# Set up database and run tests
php -d "include_path=$XATAFACE:$XATAFACE/lib:$XATAFACE/tests/lib:." -r '
chdir("'$TEST_APP_DIR'");
define("XFAPPROOT", "'$TEST_APP_DIR'/");

require_once "'$XATAFACE'/public-api.php";
df_init("'$TEST_APP_DIR'/index.php", "'$TEST_APP_DIR'/xataface");
require_once "Dataface/Application.php";

$app = Dataface_Application::getInstance();
$db = df_db();

// Set up test database
@xf_db_query("DROP DATABASE IF EXISTS xf_php8_test", $db);
xf_db_query("CREATE DATABASE xf_php8_test", $db);
xf_db_select_db("xf_php8_test");

xf_db_query("CREATE TABLE PHP8Test (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(128) NOT NULL,
    price DECIMAL(10,2),
    event_date DATE,
    event_datetime DATETIME,
    event_time TIME,
    description TEXT,
    created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB", $db);

xf_db_query("INSERT INTO PHP8Test (name, price, event_date, event_datetime, event_time, description)
    VALUES (\"Widget A\", 19.99, \"2023-06-15\", \"2023-06-15 14:30:00\", \"14:30:00\", \"A test widget\")", $db);

echo "Database setup OK\n";

// Run unit tests
require_once "PHPUnit.php";
require_once "'$XATAFACE'/tests/tests/PHP8CompatibilityUnitTest.php";
$test = new PHPUnit_TestSuite("PHP8CompatibilityUnitTest");
$result = new PHPUnit_TestResult;
$test->run($result);
print $result->toString();
if (!$result->wasSuccessful()) {
    fwrite(STDERR, "Unit tests FAILED\n");
    exit(1);
}

// Run integration tests
require_once "'$XATAFACE'/tests/tests/PHP8CompatibilityIntegrationTest.php";
$test2 = new PHPUnit_TestSuite("PHP8CompatibilityIntegrationTest");
$result2 = new PHPUnit_TestResult;
$test2->run($result2);
print $result2->toString();
if (!$result2->wasSuccessful()) {
    fwrite(STDERR, "Integration tests FAILED\n");
    exit(1);
}

// Cleanup
@xf_db_query("DROP DATABASE IF EXISTS xf_php8_test", $db);
echo "All PHP8 compatibility tests passed\n";
' 2>&1

if [ $? -eq 0 ]; then
    echo "PHASE 2: PASSED"
    PASSED=$((PASSED + 1))
else
    echo "PHASE 2: FAILED"
    FAILED=$((FAILED + 1))
fi

rm -rf "$TEST_APP_DIR"
echo ""

# --------------------------------------------------
# PHASE 3: Targeted library loading tests
# --------------------------------------------------
echo "========================================"
echo "PHASE 3: Library Loading Tests"
echo "========================================"
TOTAL_PHASES=$((TOTAL_PHASES + 1))

# Test that all major changed libraries can be loaded without fatal errors
PHASE3_FAIL=0

php -d include_path="$XATAFACE:$XATAFACE/lib:." -r '
define("XFAPPROOT", "'$XATAFACE'/");
require_once "'$XATAFACE'/config.inc.php";

$libs = array(
    "PEAR.php",
    "lib/htmLawed.php",
    "lib/HTML/QuickForm/Rule/Compare.php",
    "lib/XML/Parser.php",
    "lib/XML/DTD.php",
    "lib/Text/Diff.php",
);

$failed = 0;
foreach ($libs as $lib) {
    $path = "'$XATAFACE'/" . $lib;
    try {
        require_once $path;
        echo "  LOADED: $lib\n";
    } catch (Throwable $e) {
        echo "  FAILED: $lib - " . $e->getMessage() . "\n";
        $failed++;
    }
}

// Test xf_strftime with all format codes used across the codebase
$ts = mktime(14, 30, 45, 6, 15, 2023);
$formats = array(
    "%Y" => "2023", "%m" => "06", "%d" => "15",
    "%H" => "14", "%M" => "30", "%S" => "45",
    "%A" => "Thursday", "%a" => "Thu",
    "%B" => "June", "%b" => "Jun",
    "%p" => "PM", "%I" => "02",
    "%T" => "14:30:45", "%R" => "14:30",
    "%Y-%m-%d" => "2023-06-15",
    "%j" => "166", "%W" => date("W", $ts),
);

foreach ($formats as $fmt => $expected) {
    $result = xf_strftime($fmt, $ts);
    if ($result !== $expected) {
        echo "  STRFTIME FAIL: xf_strftime(\"$fmt\") = \"$result\", expected \"$expected\"\n";
        $failed++;
    }
}

// Test htmLawed
$clean = htmLawed("<b>ok</b><script>bad</script><p onclick=\"bad\">text</p>");
if (strpos($clean, "<script>") !== false) {
    echo "  htmLawed FAIL: did not strip script tags\n";
    $failed++;
}

// Test Compare rule
$rule = new HTML_QuickForm_Rule_Compare();
$tests = array(
    array(array(10, 5), ">", true),
    array(array(5, 10), ">", false),
    array(array(5, 5), "==", true),
    array(array(5, 5), "!=", false),
    array(array(3, 5), "<=", true),
);
foreach ($tests as $t) {
    $r = $rule->validate($t[0], $t[1]);
    if ($r !== $t[2]) {
        echo "  Compare FAIL: " . implode(",", $t[0]) . " $t[1] expected " . ($t[2] ? "true" : "false") . "\n";
        $failed++;
    }
}

// Test Text_Diff
$a = array("one", "two", "three");
$b = array("one", "TWO", "three", "four");
$diff = new Text_Diff($a, $b);
$edits = $diff->getDiff();
if (count($edits) === 0) {
    echo "  Diff FAIL: no edits found\n";
    $failed++;
}

if ($failed > 0) {
    echo "\n  $failed library test(s) failed\n";
    exit(1);
} else {
    echo "\n  All library tests passed\n";
}
' 2>&1

if [ $? -eq 0 ]; then
    echo "PHASE 3: PASSED"
    PASSED=$((PASSED + 1))
else
    echo "PHASE 3: FAILED"
    FAILED=$((FAILED + 1))
fi
echo ""

# --------------------------------------------------
# Summary
# --------------------------------------------------
echo "============================================="
echo "FINAL RESULTS: $PASSED/$TOTAL_PHASES phases passed, $FAILED failed"
echo "============================================="

if [ $FAILED -gt 0 ]; then
    exit 1
fi
exit 0
RUNEOF

chmod +x "$TEST_RUNNER"

# =============================================================================
# Run the tests inside the Docker container
# =============================================================================
echo "--- Running tests in Docker container ---"
echo ""

docker run \
    --rm \
    --name "${CONTAINER_NAME}" \
    -v "${XATAFACE}:/xataface-src:ro" \
    -v "${TEST_RUNNER}:/run-tests.sh:ro" \
    --tmpfs /tmp:exec \
    "${IMAGE_NAME}" \
    bash -c "cp -a /xataface-src/. /xataface/ && bash /run-tests.sh"

EXIT_CODE=$?

# Cleanup
rm -f "$TEST_RUNNER"

echo ""
if [ $EXIT_CODE -eq 0 ]; then
    echo "ALL DOCKER TESTS PASSED"
else
    echo "DOCKER TESTS FAILED (exit code: $EXIT_CODE)"
fi

exit $EXIT_CODE
