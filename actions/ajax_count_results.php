<?php
class dataface_actions_ajax_count_results {
    function handle($params) {
        $app = Dataface_Application::getInstance();
        $query = $app->getQuery();
        $table = Dataface_Table::loadTable($query['-table']);
        if (!$table or PEAR::isError($table)) {
            $this->out(['code' => 500, 'message' => 'Invalid request']);
            return;
        }
        $perms = $table->getPermissions();
        if (!@$perms['list']) {
            $this->out(['code' => 400, 'message' => 'Permission denied']);
            return;
        }
        $resultSet = $app->getResultSet();
        $count = $resultSet->found();
        $this->out(['code' => 200, 'found' => $count]);
        
    }
    
    function out($data) {
        header('Content-type:application/json; charset="utf-8"');
        echo json_encode($data);
    }
}
?>