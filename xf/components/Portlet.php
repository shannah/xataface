<?php
namespace xf\components;

require_once 'xf/db/Database.php';
require_once 'Dataface/Table.php';
require_once 'Dataface/PermissionsTool.php';
require_once 'Dataface/JavascriptTool.php';

use xf\db\Database;

class Portlet {
    public $cols;
    public $rows;
    public $cssClass;
    public $table;
    public $opts;
    public $canEdit=false;
    public $canAdd=false;
    public $canDelete=false;
    public $rowActions;
    public $newParams;
    public $params;
    public $addButtonLabel = 'Add New Record';
    
    public function __construct($rows, $cols = null, $opts = array()){
        
        // Make sure rows are all arrays
        foreach ($rows as $k=>$row ){
            if ( !is_array($row) ){
                $rows[$k] = (array)$rows[$k];
            }
        }
    
        if ( !isset($cols) ){
            $cols = array();
            if ( count($rows) > 0 ){
                $row = $rows[0];
                foreach ( $row as $k=>$v ){
                    if ( $k === '__meta__' ){
                        continue;
                    }
                    $cols[] = array(
                        'label' => $k,
                        'name' => $k
                    );  
                }
            } 
        }    
        
        $flds = array(
            'table', 'cssClass', 'canEdit', 'canAdd', 'canDelete', 'rowActions', 'newParams', 'params', 'addButtonLabel'
        );
        foreach ( $flds as $fld){
            if ( isset($opts[$fld]) ){
                $this->{$fld} = $opts[$fld];
            }
        }
        
        $this->cssClass .= ' xf-portlet';
        
        $decorateRow = null;
        if ( isset($opts['decorateRow']) and is_callable($opts['decorateRow']) ){
            $decorateRow = $opts['decorateRow'];
        }
    
        foreach ( $rows as $k=>$row ){
            if ( !isset($rows[$k]['__meta__']) ){
                $rows[$k]['__meta__'] = array();
            }
            $dfRec = new \Dataface_Record($this->table, array());
        
            if ( !@$rows[$k]['__meta__']['recordId'] ){
                $dfRec->setValues($row);
                $rows[$k]['__meta__']['recordId'] = $dfRec-getId();
            }
            $rows[$k]['__meta__']['record'] = $dfRec;
            if ( isset($decorateRow) ){
                $decorateRow($rows[$k]);
            }
        }
        
        $this->cols = $cols;
        $this->rows = $rows;
        $this->opts = $opts;  
    }
    
    
    public function toHtml(){
        \Dataface_JavascriptTool::getInstance()->import('xataface/components/Portlet.js');
        ob_start();
        df_display(array('portlet'=>$this), 'xataface/components/Portlet.html');
        
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
    
    public static function createPortletWithSQL($sql, $cols = null, $opts = array() ){
        $db = new Database(df_db());
        if ( is_array($sql) ){
            if ( count($sql) > 1 ){
                $queryParams = $sql[1];
            } else {
                $queryParams = array();
            }
            $sql = $sql[0];
        }
        $rows = $db->query($sql, (object)$queryParams);
        return new Portlet($rows, $cols, $opts);
        
    }
    
    public static function createPortletWithQuery($query, $cols = null, $opts = array() ){
        $table = $query['-table'];
        $opts['table'] = $table;
        
        
        
        if ( !isset($cols) ){
            $cols = array();
            $tableObj = \Dataface_Table::loadTable($table);
            $fields = $tableObj->fields(false, true);
            foreach ($fields as $f){
                if ( $f['visibility']['list'] === 'hidden' ){
                    continue;
                }
                if ( @$opts['secure'] ){
                    if ( !$tableObj->checkPermission('view', array('field'=>$f['name'])) ){
                        continue;
                    }
                    
                    if ( !$tableObj->checkPermission('new') ){
                        unset($opts['canAdd']);
                    }
                }
                $cols[] = array(
                    'name' => $f['name'],
                    'label' => $f['widget']['label']
                );
                
            }
        }
        
        if ( is_array($cols) ){
            $tableObj = \Dataface_Table::loadTable($table);
            
            foreach ( $cols as $k=>$col ){
                if ( !is_array($col) ){
                    $lbl = $col;
                    if ( $tableObj->hasField($col) ){
                        $fld = $tableObj->getField($col);
                        $lbl = $fld['widget']['label'];
                    }
                    $cols[$k] = array(
                        'name' => $col,
                        'label' => $lbl
                    );
                }
            }
        }
        
        $records = df_get_records_array($table, $query);
        $rows = array();
        $canEditAnyRows = false;
        foreach ( $records as $rec ){
            $row = array(
                '__meta__' => array('recordId' => $rec->getId())
            );
            
            if ( !@$opts['secure'] ){
                $rec->secureDisplay = false;
            } else {
                if ( !$canEditAnyRows and $rec->checkPermission('edit') ){
                    $canEditAnyRows = true;
                }
            }
            
            
            
            foreach ( $cols as $col ){
                $row[$col['name']] = $rec->display($col['name']);
            }
            $rows[] = $row;
        }
        
        if ( @$opts['secure'] and !$canEditAnyRows ){
            unset($opts['canEdit']);
        }
        
        return new Portlet($rows, $cols, $opts);
        
    }
    
    public function newParamsJson(){
        if ( $this->newParams){
            return json_encode($this->newParams);
        } else {
            return json_encode((object)array());
        }
    }
    
    public function paramsJson(){
        if ( $this->params){
            return json_encode($this->params);
        } else {
            return json_encode((object)array());
        }
    }
    
}