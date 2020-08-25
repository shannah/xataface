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
        if (!@$query['-relationship']) {
            $resultSet = $app->getResultSet();
            $count = $resultSet->found();
        } else {
            import(XFROOT.'xf/relationships/RelatedQueryTool.php');
            $qb = new xf\relationships\RelatedQueryTool();

            $qb->setIncludeLimits(false);
            $qb->setIncludeOrderBy(false);
            $filterSql = $qb->getSQL(['override_columns' => ['__COLUMN__']]);
            $filterSql = str_replace('`__COLUMN__`', "count(*) as `num`", $filterSql);
            list($count) = xf_db_fetch_row(df_query($filterSql));

        }
        
        $this->out(['code' => 200, 'found' => $count]);
        
    }
    
    function out($data) {
        header('Content-type:application/json; charset="utf-8"');
        echo json_encode($data);
    }
}
?>