Run the PHP 8.0 compatibility test suite for Xataface.

Determine the best way to run the tests based on the current environment:

## Step 1: Check the environment

1. Check if Docker is installed AND the Docker daemon is running (`docker info`).
2. Check the PHP version available locally (`php --version`).
3. Check if MySQL/MariaDB is available locally (`mysql --version` or check for a running service on port 3306).

## Step 2: Choose a test strategy

Based on what's available, pick the best strategy:

- **Docker available (daemon running):** Run `bash tests/docker_php8_test.sh` — this is the gold standard. It builds a container with PHP 8 + MariaDB and runs all 3 test phases (syntax, standalone, DB integration + composer suite). You can pass `PHP_VERSION=8.0` or `PHP_VERSION=8.4` etc. to test against a specific PHP version.

- **Local PHP 8.x but no MySQL:** Run `bash tests/php8_e2e_test.sh` — this runs Phase 1 (syntax validation of all 37 changed files) and Phase 2 (standalone function tests) without needing a database. Phase 3 (integration) will be skipped.

- **Local PHP 8.x with MySQL:** Run `MYSQL_HOST=localhost MYSQL_USER=root bash tests/php8_e2e_test.sh` — this runs all 3 phases including DB integration tests. Adjust MYSQL_USER and MYSQL_PASSWORD as needed for the local MySQL setup.

- **Local PHP 8.x with MySQL (full composer suite):** Run `composer test` — this uses the existing test infrastructure to scaffold a full Xataface app and run TableTest, IOTest, HistoryToolTest, plus the PHP8 compatibility tests. Requires `tests/tests/conf.db.ini` to be configured with valid MySQL credentials.

## Step 3: Run the tests

Execute the chosen command. Monitor output for failures.

## Step 4: Report results

Summarize the results concisely:
- How many tests passed/failed/skipped
- Which phases ran successfully
- Any failures with file paths and brief descriptions
- Suggest fixes for any failures found

## Test files reference

- `tests/docker_php8_test.sh` — Docker-based full test (PHP + MySQL in container)
- `tests/php8_e2e_test.sh` — E2E test (syntax + standalone + optional DB)
- `tests/tests/PHP8CompatibilityUnitTest.php` — Unit tests (30+ tests, no DB needed)
- `tests/tests/PHP8CompatibilityIntegrationTest.php` — Integration tests (DB required)
- `tests/tests/runTests.php` — Main test runner (used by `composer test`)
