<?php
class dataface_actions_xf_field_preview {
    function handle($params) {
        $app = Dataface_Application::getInstance();
        $query = $app->getQuery();
        $record = $app->getRecord();
        $field = @$query['-field'];
        if (!$field) {
            throw new Exception("-field required");
        }
        if ($record->checkPermission('view', ['field' => $field])) {
            echo $record->htmlValue($field);
        }
    }
}
?>