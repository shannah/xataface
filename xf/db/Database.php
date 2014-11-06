<?php
namespace xf\db;

class Database {

    private $db;
    private $conf;

    /**
     *
     * @var QueryTranslator
     */
    private $translator;

    public function __construct($conf) {
        if (is_resource($conf)) {
            $this->db = $conf;
        } else {
            $this->conf = $conf;
        }
    }

    public function translator(QueryTranslator $translator = null) {
        if (isset($translator)) {
            $this->translator = $translator;
            return $this;
        } else {
            return $this->translator;
        }
    }

    public function db() {
        if (!isset($this->db)) {
            $conf = $this->conf;
            $this->db = xf_db_connect($conf['host'], $conf['user'], $conf['password'], true /* necessary to force new link */);
            xf_db_select_db($conf['name'], $this->db);
            xf_db_query('set character_set_results = \'utf8\'', $this->db);
            xf_db_query("SET NAMES utf8", $this->db);
            xf_db_query('set character_set_client = \'utf8\'', $this->db);
            unset($this->conf);
        }
        return $this->db;
    }

    public function prepareQuery($sql, $vars = null) {

        if (isset($vars)) {
            $callback = function($matches) use ($vars) {
                if (is_array($vars) and isset($vars[$matches[1]])) {
                    if (is_array($vars[$matches[1]])) {
                        if (!$vars[$matches[1]]) {
                            return 'NULL';
                        }
                        return "'" . implode("','", array_map('addslashes', $vars[$matches[1]])) . "'";
                    } else {
                        return "'" . addslashes($vars[$matches[1]]) . "'";
                    }
                } else if (is_object($vars) and isset($vars->{$matches[1]})) {
                    return "'" . addslashes($vars->{$matches[1]}) . "'";

                    if (is_array($vars->{$matches[1]})) {
                        if (!$vars->{$matches[1]}) {
                            return 'NULL';
                        }
                        return "'" . implode("','", array_map('addslashes', $vars->{$matches[1]})) . "'";
                    } else {
                        return "'" . addslashes($vars->{$matches[1]}) . "'";
                    }
                } else {
                    return "NULL";
                }
            };
            $sql = preg_replace_callback('/\:([a-zA-Z_][a-zA-Z0-9_]*)/', $callback, $sql);
        }
        return $sql;
    }

    public function query($sql, $vars = null) {
        $sql = $this->prepareQuery($sql, $vars);
        if (isset($this->translator)) {
            $sql = $this->translator->translateQuery($sql);
        }
        $res = xf_db_query($sql, $this->db());
        if (!$res) {
            //echo $sql;
            error_log("Query Failed: " . $sql);
            throw new \Exception(xf_db_error($this->db()));
        }
        return $res;
    }

    public function getRow($sql, $vars = null) {
        $res = $this->query($sql, $vars);
        $row = xf_db_fetch_row($res);
        @xf_db_free_result($res);
        return $row;
    }

    public function getAssoc($sql, $vars = null) {
        $res = $this->query($sql, $vars);
        $row = xf_db_fetch_assoc($res);
        @xf_db_free_result($res);
        return $row;
    }

    public function getObject($sql, $vars = null) {
        $res = $this->query($sql, $vars);
        $row = xf_db_fetch_object($res);
        @xf_db_free_result($res);
        return $row;
    }

    public function getRows($sql, $vars = null) {
        $out = array();
        $res = $this->query($sql, $vars);
        while ($row = xf_db_fetch_row($res)) {
            $out[] = $row;
        }
        @xf_db_free_result($res);
        return $out;
    }

    public function getAssocs($sql, $vars = null) {
        $out = array();
        $res = $this->query($sql, $vars);
        while ($row = xf_db_fetch_assoc($res)) {
            $out[] = $row;
        }
        @xf_db_free_result($res);
        return $out;
    }

    public function getObjects($sql, $vars = null) {
        $out = array();
        $res = $this->query($sql, $vars);
        while ($row = xf_db_fetch_object($res)) {
            $out[] = $row;
        }
        @xf_db_free_result($res);
        return $out;
    }

    public function getReader($sql, $vars = null) {
        $sql = $this->prepareQuery($sql, $vars);
        return new Dataface_ResultReader($sql, $this->db(), 100);
    }

    public function insertObject($table, \StdClass $row) {
        $arr = (array) $row;
        $keys = '(`' . implode('`,`', array_keys($arr)) . '`)';
        $evals = array();
        foreach ($arr as $val) {
            if (isset($val)) {
                $evals[] = "'" . addslashes($val) . "'";
            } else {
                $evals[] = 'NULL';
            }
        }
        $vals = "(" . implode(',', $evals) . ")";

        $sql = "insert into `$table` $keys values $vals";
        return $this->query($sql);
    }

    public function updateObject($table, \StdClass $row, $where) {
        $arr = (array) $row;
        $vals = array();
        foreach ($arr as $key => $val) {
            if (!isset($val)) {
                $val = 'NULL';
            } else {
                $val = "'" . addslashes($val) . "'";
            }
            $vals[] = "`$key`=$val";
        }
        
        if ( !is_string($where) ){
            if ( is_object($where) ){
                $where = (array)$where;
            }
            if ( is_array($where) ){
                $w = array();
                foreach ( $where as $key=>$val ){
                    $w[] = "`".str_replace('`','', $key)."`='".addslashes($val)."'";
                }
                $where = implode(' AND ', $w);   
            } else {
                throw new \Exception("3rd param of updateObject 'where' expects a string, object, or array.");
            }
            
        }
        
        $sql = "update `$table` set " . implode(',', $vals) . " where $where";
        return $this->query($sql);
    }

    public function deleteObject($table, \StdClass $row) {
        $arr = (array) $row;
        $vals = array();
        foreach ($arr as $key => $val) {
            if (!isset($val)) {
                $val = 'NULL';
            } else {
                $val = "'" . addslashes($val) . "'";
            }
            $vals[] = "`$key`=$val";
        }
        $sql = "delete from  `$table` where " . implode(' AND ', $vals);
        return $this->query($sql);
    }

    public function getInsertId() {
        return xf_db_insert_id($this->db());
    }

}
