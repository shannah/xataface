<?php
/**
 * Database-backed session handler for Xataface.
 *
 * Stores PHP sessions in the application's MySQL database instead of the filesystem.
 * This enables deployment on serverless platforms (AWS Lambda, Google Cloud Run)
 * where the filesystem is ephemeral and not shared across instances.
 *
 * Enable via conf.ini:
 *   [_auth]
 *     session_handler=database
 *
 * The handler creates a `__xf_sessions` table automatically on first use.
 *
 * @since 3.0
 */
class Dataface_DatabaseSessionHandler implements SessionHandlerInterface {

    /**
     * @var string The table name used for session storage.
     */
    private $tableName = '__xf_sessions';

    /**
     * @var resource|mysqli The database connection.
     */
    private $db;

    /**
     * @var bool Whether the session table has been verified to exist.
     */
    private $tableVerified = false;

    /**
     * @param resource|mysqli $db The database connection handle.
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Ensure the session table exists, creating it if necessary.
     */
    private function ensureTable() {
        if ($this->tableVerified) {
            return;
        }
        $sql = "CREATE TABLE IF NOT EXISTS `{$this->tableName}` (
            `session_id` varchar(128) NOT NULL,
            `session_data` mediumblob NOT NULL,
            `last_access` int unsigned NOT NULL,
            PRIMARY KEY (`session_id`),
            KEY `last_access` (`last_access`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        xf_db_query($sql, $this->db);
        $this->tableVerified = true;
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function open($savePath, $sessionName) {
        $this->ensureTable();
        return true;
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function close() {
        return true;
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function read($id) {
        $id = xf_db_real_escape_string($id, $this->db);
        $sql = "SELECT `session_data` FROM `{$this->tableName}` WHERE `session_id` = '{$id}'";
        $result = xf_db_query($sql, $this->db);
        if ($result && $row = xf_db_fetch_assoc($result)) {
            xf_db_free_result($result);
            return $row['session_data'];
        }
        return '';
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function write($id, $data) {
        $id = xf_db_real_escape_string($id, $this->db);
        $data = xf_db_real_escape_string($data, $this->db);
        $time = time();
        $sql = "REPLACE INTO `{$this->tableName}` (`session_id`, `session_data`, `last_access`) "
             . "VALUES ('{$id}', '{$data}', {$time})";
        return xf_db_query($sql, $this->db) ? true : false;
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function destroy($id) {
        $id = xf_db_real_escape_string($id, $this->db);
        $sql = "DELETE FROM `{$this->tableName}` WHERE `session_id` = '{$id}'";
        return xf_db_query($sql, $this->db) ? true : false;
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function gc($maxLifetime) {
        $threshold = time() - $maxLifetime;
        $sql = "DELETE FROM `{$this->tableName}` WHERE `last_access` < {$threshold}";
        if (xf_db_query($sql, $this->db)) {
            return xf_db_affected_rows($this->db);
        }
        return false;
    }
}
