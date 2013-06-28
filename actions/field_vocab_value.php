<?php
/**
 * An action used by the select widget to find the value in a field's vocabulary
 * that corresponds to a particular key.  This is used after adding a new value
 * to the list, so that the new value can be looked up via AJAX.
 * 
 * Requires the new or view permission on the field in question.
 * 
 * @param string -table The name of the table
 * @param string -action Always "field_vocab_value"
 * @param string -field The name of the field whose vocabulary we are checking.
 * @param string -key The key whose value (in the vocabulary) we wish to retrieve.
 * 
 * @return JSON {
 *      code : <INT>  (200 for success)
 *      message : <String>  ( a message)
 *      value : <String>  (The value that was retrieved)
 * }
 *
 * @author shannah
 * @since 2.0.2
 */
class dataface_actions_field_vocab_value {
    
    function handle($params){
        try {
            $this->handle2($params);
        } catch (Exception $ex){
            error_log(__FILE__.'['.__LINE__.']:'.$ex->getMessage().' code='.$ex->getCode());
            df_write_json(array(
                'code' => $ex->getCode(),
                'message' => 'Failed to retrieve value.  See error log for details.'
            ));
        }
    }
    
    function handle2($params){
        $app = Dataface_Application::getInstance();
        $query = $app->getQuery();
        $table = $query['-table'];
        if ( !@$query['-field'] ){
            throw new Exception("No field specified", 500);
        }
        if ( !@$query['-key'] ){
            throw new Exception("No key specified", 500);
        }
        
        $tableObj = Dataface_Table::loadTable($table);
        if ( PEAR::isError($tableObj)){
            throw new Exception($tableObj->getMessage(), $tableObj->getCode());
        }
        
        $field =& $tableObj->getField($query['-field']);
        if ( PEAR::isError($field) ){
            throw new Exception("Field not found ".$field->getMessage(), $field->getCode());
        }
        if ( !@$field['vocabulary'] ){
            throw new Exception("Field has no vocabulary assigned", 500);
        }
        
        $perms = $tableObj->getPermissions(array(
            'field' => $field['name']
        ));
        
        if ( !@$perms['edit'] && !@$perms['new'] ){
            throw new Exception("You don't have permission to access this vocabulary.", 400);
        }
        
        $valuelist = $tableObj->getValuelist($field['vocabulary']);
        if ( PEAR::isError($valuelist) ){
            throw new Exception("Valuelist not found.", 404);
        }
        $value = @$valuelist[$query['-key']];
        
        df_write_json(array(
            'code' => 200,
            'message' => 'Found',
            'value' => $value
        ));
        
        
        
    }
}

?>
