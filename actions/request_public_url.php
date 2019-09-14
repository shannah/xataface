<?php
/**
 * Action request a public one-use URL.  This is handy if you have a URL
 * that is user-specific, and you need to use it without a session.
 * 
 */
import(XFROOT.'xf/actions/BaseAction.php');
class dataface_actions_request_public_url extends xf\actions\BaseAction {
    private static function create_table() {
        df_q("create table if not exists xf_public_urls (
                `id` varchar(36) primary key,
                `expires` DATETIME NOT NULL,
                `username` VARCHAR(100),
                `query` TEXT NOT NULL
            )");
    }


    public static function apply_url($app) {
        if (!$_GET['--url']) {
            return false;
        }
        $id = $_GET['--url'];

        $res = xf_db_query("select * from xf_public_urls where expires > NOW() and id='".addslashes($id)."'", $app->_db);
        if (!$res) {
            xf_db_query("delete from xf_public_urls where id='".addslashes($id)."'", $app->_db);
            return false;
        }
        $o = xf_db_fetch_object($res);
        xf_db_free_result($res);

        if (!$o) {
            return false;
        }

        if ($o->username) {
            $_SESSION['UserName'] = $o->username;
            if (!defined('REQUEST_PUBLIC_URL_USERNAME')) {
                define('REQUEST_PUBLIC_URL_USERNAME', $o->username);
            }
        }
        $query = json_decode($o->query, true);
        $_GET = $query;
        $_POST = array();
        $_REQUEST = $query;
        //print_r($_GET);exit;
        //xf_db_query("delete from xf_public_urls where id='".addslashes($id)."'", $app->_db);
        return true;
        
    }


    private static function create_url($query) {
        $user = '';
        if (class_exists('Dataface_AuthenticationTool')) {
            $user = Dataface_AuthenticationTool::getInstance()->getLoggedInUserName();
        }
        self::create_table();
        $rec = new Dataface_Record('xf_public_urls', array());

        if (@$query['--action']) {
            $query['-action'] = $query['--action'];
            unset($query['--action']);
        }
        $rec->setValues(array(
            'id' => df_uuid(),
            'expires' => date('Y-m-d H:i:s', time() + 30),
            'username' => $user,
            'query' => json_encode($query)
        ));
        $rec->save();
        if (PEAR::isError($rec)) {
            throw new Exception($rec->getMessage());
        }
        return $rec->val('id');
    }
    
    public function handleImpl($params) {
        $query = null;
        if (@$_POST['--url-to-encode']) {
            $query_string = parse_url($_POST['--url-to-encode'], PHP_URL_QUERY);
            parse_str($query_string, $query);
        }  else {
            $query = Dataface_Application::getInstance()->getQuery();
        }
        $id = self::create_url($query);
        return array(
            'code' => 200,
            'message' => 'Successfully created URL',
            'url' => df_absolute_url(DATAFACE_SITE_HREF.'?--url='.urlencode($id))
        );
        
    }
}