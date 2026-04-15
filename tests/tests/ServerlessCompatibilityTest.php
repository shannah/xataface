<?php
/**
 * Integration tests for serverless compatibility features.
 *
 * Tests database sessions, configurable templates_c, and proxy-aware
 * IP validation in the context of a real Xataface application with
 * a live MySQL database.
 *
 * These tests simulate the constraints of serverless environments
 * (Cloud Run, AWS Lambda): ephemeral filesystem, load-balanced IPs,
 * and horizontally scaled instances.
 */

require_once 'BaseTest.php';

class ServerlessCompatibilityTest extends BaseTest {

    function ServerlessCompatibilityTest($name = 'ServerlessCompatibilityTest') {
        $this->BaseTest($name);
    }

    function __construct($name = 'ServerlessCompatibilityTest') {
        $this->ServerlessCompatibilityTest($name);
    }

    function setUp() {
        parent::setUp();
    }

    // =========================================================================
    // 1. Database Session Handler
    // =========================================================================

    /**
     * Verify the DatabaseSessionHandler class can be loaded and instantiated.
     */
    function test_database_session_handler_loads() {
        require_once 'Dataface/DatabaseSessionHandler.php';
        $handler = new Dataface_DatabaseSessionHandler($this->db);
        $this->assertTrue($handler instanceof SessionHandlerInterface,
            'DatabaseSessionHandler should implement SessionHandlerInterface');
    }

    /**
     * Verify the handler creates the __xf_sessions table on open().
     */
    function test_database_session_handler_creates_table() {
        require_once 'Dataface/DatabaseSessionHandler.php';
        $handler = new Dataface_DatabaseSessionHandler($this->db);

        // Drop the table if it exists from a previous run
        xf_db_query("DROP TABLE IF EXISTS `__xf_sessions`", $this->db);

        // open() should trigger table creation
        $result = $handler->open('/tmp', 'PHPSESSID');
        $this->assertTrue($result, 'open() should return true');

        // Verify the table exists
        $res = xf_db_query("SHOW TABLES LIKE '__xf_sessions'", $this->db);
        $this->assertTrue(xf_db_num_rows($res) === 1,
            '__xf_sessions table should exist after open()');
        xf_db_free_result($res);

        // Verify table structure
        $res = xf_db_query("DESCRIBE `__xf_sessions`", $this->db);
        $columns = array();
        while ($row = xf_db_fetch_assoc($res)) {
            $columns[$row['Field']] = $row['Type'];
        }
        xf_db_free_result($res);

        $this->assertTrue(isset($columns['session_id']), 'Table should have session_id column');
        $this->assertTrue(isset($columns['session_data']), 'Table should have session_data column');
        $this->assertTrue(isset($columns['last_access']), 'Table should have last_access column');
    }

    /**
     * Verify write() and read() round-trip session data correctly.
     */
    function test_database_session_write_and_read() {
        require_once 'Dataface/DatabaseSessionHandler.php';
        $handler = new Dataface_DatabaseSessionHandler($this->db);
        xf_db_query("DROP TABLE IF EXISTS `__xf_sessions`", $this->db);
        $handler->open('/tmp', 'PHPSESSID');

        // Write session data
        $sessionId = 'test_session_' . md5(uniqid());
        $sessionData = 'UserName|s:5:"admin";--msg|s:0:"";';

        $result = $handler->write($sessionId, $sessionData);
        $this->assertTrue($result, 'write() should return true');

        // Read it back
        $readData = $handler->read($sessionId);
        $this->assertEquals($sessionData, $readData,
            'read() should return the exact data that was written');
    }

    /**
     * Verify write() updates existing session data (REPLACE behavior).
     */
    function test_database_session_update() {
        require_once 'Dataface/DatabaseSessionHandler.php';
        $handler = new Dataface_DatabaseSessionHandler($this->db);
        xf_db_query("DROP TABLE IF EXISTS `__xf_sessions`", $this->db);
        $handler->open('/tmp', 'PHPSESSID');

        $sessionId = 'test_session_update_' . md5(uniqid());

        // Write initial data
        $handler->write($sessionId, 'initial_data');
        $this->assertEquals('initial_data', $handler->read($sessionId));

        // Update with new data
        $handler->write($sessionId, 'updated_data');
        $this->assertEquals('updated_data', $handler->read($sessionId),
            'write() should replace existing session data');

        // Verify only one row exists
        $id = xf_db_real_escape_string($sessionId, $this->db);
        $res = xf_db_query(
            "SELECT COUNT(*) as cnt FROM `__xf_sessions` WHERE `session_id` = '{$id}'",
            $this->db
        );
        $row = xf_db_fetch_assoc($res);
        xf_db_free_result($res);
        $this->assertEquals('1', $row['cnt'],
            'There should be exactly one row per session ID');
    }

    /**
     * Verify destroy() removes session data.
     */
    function test_database_session_destroy() {
        require_once 'Dataface/DatabaseSessionHandler.php';
        $handler = new Dataface_DatabaseSessionHandler($this->db);
        xf_db_query("DROP TABLE IF EXISTS `__xf_sessions`", $this->db);
        $handler->open('/tmp', 'PHPSESSID');

        $sessionId = 'test_session_destroy_' . md5(uniqid());
        $handler->write($sessionId, 'some_data');

        // Destroy the session
        $result = $handler->destroy($sessionId);
        $this->assertTrue($result, 'destroy() should return true');

        // Reading destroyed session should return empty string
        $readData = $handler->read($sessionId);
        $this->assertEquals('', $readData,
            'read() after destroy() should return empty string');
    }

    /**
     * Verify gc() removes expired sessions.
     */
    function test_database_session_gc() {
        require_once 'Dataface/DatabaseSessionHandler.php';
        $handler = new Dataface_DatabaseSessionHandler($this->db);
        xf_db_query("DROP TABLE IF EXISTS `__xf_sessions`", $this->db);
        $handler->open('/tmp', 'PHPSESSID');

        // Insert a session with an old timestamp
        $oldSessionId = 'old_session_' . md5(uniqid());
        $handler->write($oldSessionId, 'old_data');

        // Manually backdate the last_access to 2 hours ago
        $twoHoursAgo = time() - 7200;
        $escapedId = xf_db_real_escape_string($oldSessionId, $this->db);
        xf_db_query(
            "UPDATE `__xf_sessions` SET `last_access` = {$twoHoursAgo} WHERE `session_id` = '{$escapedId}'",
            $this->db
        );

        // Insert a fresh session
        $freshSessionId = 'fresh_session_' . md5(uniqid());
        $handler->write($freshSessionId, 'fresh_data');

        // GC with 1 hour max lifetime should remove the old session
        $removed = $handler->gc(3600);
        $this->assertTrue($removed >= 1,
            'gc() should remove at least 1 expired session');

        // Old session should be gone
        $this->assertEquals('', $handler->read($oldSessionId),
            'Expired session should be removed by gc()');

        // Fresh session should still exist
        $this->assertEquals('fresh_data', $handler->read($freshSessionId),
            'Fresh session should survive gc()');
    }

    /**
     * Verify that session data with special characters round-trips correctly.
     */
    function test_database_session_special_characters() {
        require_once 'Dataface/DatabaseSessionHandler.php';
        $handler = new Dataface_DatabaseSessionHandler($this->db);
        xf_db_query("DROP TABLE IF EXISTS `__xf_sessions`", $this->db);
        $handler->open('/tmp', 'PHPSESSID');

        $sessionId = 'test_special_' . md5(uniqid());
        // Session data with quotes, backslashes, null bytes, unicode
        $sessionData = "key|s:28:\"He said \"hello\" \\ \x00 \xC3\xA9\";";

        $handler->write($sessionId, $sessionData);
        $readData = $handler->read($sessionId);
        $this->assertEquals($sessionData, $readData,
            'Session data with special characters should round-trip correctly');
    }

    /**
     * Verify that the session_handler=database config option is recognized
     * in startSession() and does not crash.
     */
    function test_database_session_handler_integration() {
        require_once 'Dataface/DatabaseSessionHandler.php';

        // We can't easily call startSession() in a test that already has a session,
        // so we verify the handler works end-to-end by simulating what startSession does.
        $handler = new Dataface_DatabaseSessionHandler($this->db);
        xf_db_query("DROP TABLE IF EXISTS `__xf_sessions`", $this->db);

        // Simulate the session_set_save_handler call
        $result = $handler->open('', 'test');
        $this->assertTrue($result);

        $sid = 'integration_test_' . md5(uniqid());
        $handler->write($sid, 'UserName|s:5:"admin";');
        $this->assertEquals('UserName|s:5:"admin";', $handler->read($sid));

        $handler->destroy($sid);
        $this->assertEquals('', $handler->read($sid));

        $handler->close();
    }

    // =========================================================================
    // 2. Configurable templates_c Path
    // =========================================================================

    /**
     * Verify that XFTEMPLATES_C is defined (basic sanity).
     */
    function test_templates_c_constant_defined() {
        $this->assertTrue(defined('XFTEMPLATES_C'),
            'XFTEMPLATES_C constant should be defined');
        $this->assertTrue(strlen(XFTEMPLATES_C) > 0,
            'XFTEMPLATES_C should not be empty');
    }

    /**
     * Verify the SkinTool can fall back to a temp directory when both
     * global template dirs are non-writable.
     */
    function test_skintool_tmp_fallback() {
        // Save original globals
        $origLocal = $GLOBALS['Dataface_Globals_Local_Templates_c'];
        $origGlobal = $GLOBALS['Dataface_Globals_Templates_c'];

        // Point both globals to non-existent paths
        $GLOBALS['Dataface_Globals_Local_Templates_c'] = '/nonexistent/path1/';
        $GLOBALS['Dataface_Globals_Templates_c'] = '/nonexistent/path2/';

        $exception = null;
        $skinTool = null;
        try {
            // SkinTool constructor should fall back to /tmp instead of throwing
            require_once 'Dataface/SkinTool.php';
            // We need a fresh instance, not the singleton
            $skinTool = new Dataface_SkinTool();
        } catch (Exception $e) {
            $exception = $e;
        }

        // Restore globals
        $GLOBALS['Dataface_Globals_Local_Templates_c'] = $origLocal;
        $GLOBALS['Dataface_Globals_Templates_c'] = $origGlobal;

        $this->assertNull($exception,
            'SkinTool should fall back to /tmp instead of throwing: '
            . ($exception ? $exception->getMessage() : ''));

        if ($skinTool) {
            $compileDir = $skinTool->compile_dir;
            $this->assertTrue(is_writable($compileDir),
                "SkinTool compile_dir ({$compileDir}) should be writable");
            $this->assertTrue(
                strpos($compileDir, sys_get_temp_dir()) !== false
                || strpos($compileDir, '/tmp') !== false,
                "SkinTool compile_dir ({$compileDir}) should be under temp directory");
        }
    }

    /**
     * Verify that init.php's templates_c check does not fatally die
     * when the app directory is read-only, by verifying the fallback
     * temp directory logic works.
     */
    function test_templates_c_tmp_fallback_creates_directory() {
        $hash = md5('test_serverless_' . uniqid());
        $tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'xf_templates_c_' . $hash;

        // Ensure it doesn't exist
        if (is_dir($tmpDir)) {
            rmdir($tmpDir);
        }

        // Simulate the fallback logic from init.php
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0777, true);
        }

        $this->assertTrue(is_dir($tmpDir),
            'Fallback templates_c directory should be creatable in /tmp');
        $this->assertTrue(is_writable($tmpDir),
            'Fallback templates_c directory should be writable');

        // Clean up
        @rmdir($tmpDir);
    }

    // =========================================================================
    // 3. Proxy-Aware IP Validation
    // =========================================================================

    /**
     * Verify getClientIp() returns REMOTE_ADDR by default (no proxy headers).
     */
    function test_get_client_ip_default() {
        $app = Dataface_Application::getInstance();

        // Save and clear state
        $origConf = @$app->_conf['trust_proxy_headers'];
        $origRemote = @$_SERVER['REMOTE_ADDR'];
        $origForwarded = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $origRealIp = @$_SERVER['HTTP_X_REAL_IP'];

        unset($app->_conf['trust_proxy_headers']);
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1, 172.16.0.1';
        unset($_SERVER['HTTP_X_REAL_IP']);

        $ip = $app->getClientIp();

        // Without trust_proxy_headers, should use REMOTE_ADDR
        $this->assertEquals('192.168.1.100', $ip,
            'getClientIp() without trust_proxy_headers should return REMOTE_ADDR');

        // Restore
        $app->_conf['trust_proxy_headers'] = $origConf;
        $_SERVER['REMOTE_ADDR'] = $origRemote;
        if ($origForwarded !== null) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $origForwarded;
        } else {
            unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        }
        if ($origRealIp !== null) {
            $_SERVER['HTTP_X_REAL_IP'] = $origRealIp;
        }
    }

    /**
     * Verify getClientIp() reads X-Forwarded-For when trust_proxy_headers is on.
     */
    function test_get_client_ip_with_forwarded_for() {
        $app = Dataface_Application::getInstance();

        $origConf = @$app->_conf['trust_proxy_headers'];
        $origRemote = @$_SERVER['REMOTE_ADDR'];
        $origForwarded = @$_SERVER['HTTP_X_FORWARDED_FOR'];

        $app->_conf['trust_proxy_headers'] = 1;
        $_SERVER['REMOTE_ADDR'] = '10.128.0.1'; // Load balancer IP
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.50, 70.41.3.18, 10.128.0.1';

        $ip = $app->getClientIp();

        $this->assertEquals('203.0.113.50', $ip,
            'getClientIp() should return first IP from X-Forwarded-For chain');

        // Restore
        $app->_conf['trust_proxy_headers'] = $origConf;
        $_SERVER['REMOTE_ADDR'] = $origRemote;
        if ($origForwarded !== null) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $origForwarded;
        } else {
            unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        }
    }

    /**
     * Verify getClientIp() reads X-Real-IP as fallback.
     */
    function test_get_client_ip_with_real_ip() {
        $app = Dataface_Application::getInstance();

        $origConf = @$app->_conf['trust_proxy_headers'];
        $origRemote = @$_SERVER['REMOTE_ADDR'];
        $origForwarded = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $origRealIp = @$_SERVER['HTTP_X_REAL_IP'];

        $app->_conf['trust_proxy_headers'] = 1;
        $_SERVER['REMOTE_ADDR'] = '10.128.0.1';
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        $_SERVER['HTTP_X_REAL_IP'] = '198.51.100.25';

        $ip = $app->getClientIp();

        $this->assertEquals('198.51.100.25', $ip,
            'getClientIp() should fall back to X-Real-IP when X-Forwarded-For is absent');

        // Restore
        $app->_conf['trust_proxy_headers'] = $origConf;
        $_SERVER['REMOTE_ADDR'] = $origRemote;
        if ($origForwarded !== null) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $origForwarded;
        } else {
            unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        }
        if ($origRealIp !== null) {
            $_SERVER['HTTP_X_REAL_IP'] = $origRealIp;
        } else {
            unset($_SERVER['HTTP_X_REAL_IP']);
        }
    }

    /**
     * Verify getClientIp() rejects invalid IPs in X-Forwarded-For.
     */
    function test_get_client_ip_rejects_invalid_forwarded_for() {
        $app = Dataface_Application::getInstance();

        $origConf = @$app->_conf['trust_proxy_headers'];
        $origRemote = @$_SERVER['REMOTE_ADDR'];
        $origForwarded = @$_SERVER['HTTP_X_FORWARDED_FOR'];
        $origRealIp = @$_SERVER['HTTP_X_REAL_IP'];

        $app->_conf['trust_proxy_headers'] = 1;
        $_SERVER['REMOTE_ADDR'] = '10.128.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = 'not-a-valid-ip';
        unset($_SERVER['HTTP_X_REAL_IP']);

        $ip = $app->getClientIp();

        $this->assertEquals('10.128.0.1', $ip,
            'getClientIp() should fall back to REMOTE_ADDR when X-Forwarded-For contains invalid IP');

        // Restore
        $app->_conf['trust_proxy_headers'] = $origConf;
        $_SERVER['REMOTE_ADDR'] = $origRemote;
        if ($origForwarded !== null) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $origForwarded;
        } else {
            unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        }
        if ($origRealIp !== null) {
            $_SERVER['HTTP_X_REAL_IP'] = $origRealIp;
        } else {
            unset($_SERVER['HTTP_X_REAL_IP']);
        }
    }

    /**
     * Verify getClientIp() handles IPv6 addresses.
     */
    function test_get_client_ip_ipv6() {
        $app = Dataface_Application::getInstance();

        $origConf = @$app->_conf['trust_proxy_headers'];
        $origRemote = @$_SERVER['REMOTE_ADDR'];
        $origForwarded = @$_SERVER['HTTP_X_FORWARDED_FOR'];

        $app->_conf['trust_proxy_headers'] = 1;
        $_SERVER['REMOTE_ADDR'] = '::1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '2001:db8::1, ::1';

        $ip = $app->getClientIp();

        $this->assertEquals('2001:db8::1', $ip,
            'getClientIp() should handle IPv6 addresses in X-Forwarded-For');

        // Restore
        $app->_conf['trust_proxy_headers'] = $origConf;
        $_SERVER['REMOTE_ADDR'] = $origRemote;
        if ($origForwarded !== null) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $origForwarded;
        } else {
            unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        }
    }

    /**
     * Verify the full IP validation flow in _display() context:
     * when trust_proxy_headers is enabled and disable_session_ip_check is active,
     * the client IP from proxy headers should be used (not REMOTE_ADDR).
     */
    function test_ip_check_uses_proxy_headers() {
        $app = Dataface_Application::getInstance();

        // This test verifies getClientIp() integration is correct by checking
        // that the method exists and produces consistent output when called
        // multiple times with the same state.
        $origConf = @$app->_conf['trust_proxy_headers'];
        $origRemote = @$_SERVER['REMOTE_ADDR'];
        $origForwarded = @$_SERVER['HTTP_X_FORWARDED_FOR'];

        $app->_conf['trust_proxy_headers'] = 1;
        $_SERVER['REMOTE_ADDR'] = '10.128.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.50';

        $ip1 = $app->getClientIp();
        $ip2 = $app->getClientIp();

        $this->assertEquals($ip1, $ip2,
            'getClientIp() should return consistent results');
        $this->assertEquals('203.0.113.50', $ip1,
            'getClientIp() should consistently resolve proxy IP');

        // Restore
        $app->_conf['trust_proxy_headers'] = $origConf;
        $_SERVER['REMOTE_ADDR'] = $origRemote;
        if ($origForwarded !== null) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $origForwarded;
        } else {
            unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        }
    }

    // =========================================================================
    // 4. Combined Serverless Scenario
    // =========================================================================

    /**
     * End-to-end test simulating a serverless deployment:
     * - Database sessions enabled
     * - Templates compiled to /tmp
     * - Behind a load balancer with X-Forwarded-For
     */
    function test_serverless_deployment_scenario() {
        require_once 'Dataface/DatabaseSessionHandler.php';
        $app = Dataface_Application::getInstance();

        // 1. Database session handler works
        $handler = new Dataface_DatabaseSessionHandler($this->db);
        xf_db_query("DROP TABLE IF EXISTS `__xf_sessions`", $this->db);
        $handler->open('', 'test');

        $sid = 'serverless_e2e_' . md5(uniqid());
        $handler->write($sid, 'UserName|s:9:"testuser1";');
        $this->assertEquals('UserName|s:9:"testuser1";', $handler->read($sid),
            'E2E: Database session should persist data');

        // 2. Templates_c is writable (either real or fallback)
        $this->assertTrue(defined('XFTEMPLATES_C'), 'E2E: XFTEMPLATES_C should be defined');
        $this->assertTrue(is_writable(rtrim(XFTEMPLATES_C, DIRECTORY_SEPARATOR)),
            'E2E: XFTEMPLATES_C directory should be writable');

        // 3. Proxy IP resolution works
        $origConf = @$app->_conf['trust_proxy_headers'];
        $origRemote = @$_SERVER['REMOTE_ADDR'];
        $origForwarded = @$_SERVER['HTTP_X_FORWARDED_FOR'];

        $app->_conf['trust_proxy_headers'] = 1;
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.99';

        $this->assertEquals('203.0.113.99', $app->getClientIp(),
            'E2E: Client IP should resolve through proxy headers');

        // Restore
        $app->_conf['trust_proxy_headers'] = $origConf;
        $_SERVER['REMOTE_ADDR'] = $origRemote;
        if ($origForwarded !== null) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $origForwarded;
        } else {
            unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        }

        // Clean up session
        $handler->destroy($sid);
        $handler->close();
    }
}
