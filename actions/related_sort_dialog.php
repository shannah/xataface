<?php
import(XFROOT.'xf/core/XFException.php');
use xf\core\XFException;

class dataface_actions_related_sort_dialog {
    function handle($params) {
        $app = Dataface_Application::getInstance();
        $query = $app->getQuery();
        
        $currSort = @$query['-related:sort'];
        $currSortDirection = 'asc';
        $table = Dataface_Table::loadTable($query['-table']);
        if (PEAR::isError($table)) {
            die("Table not found");
        }
        $record = $app->getRecord();
        if (!$record) {
            XFException::throwBadRequest("Record not found");
        }
        $relationship = null;
        $relationshipAction = null;
        $relationshipActions = null;
        if (@$query['-relationship']) {
            $relationship = $table->getRelationship($query['-relationship']);
            if (PEAR::isError($relationship) or !$relationship) {
                XFException::throwBadRequest("No relationship found");
            }
        } else {
            $relationshipActions = $table->getRelationshipsAsActions();
            if (count($relationshipActions) === 0) {
                throw new Exception("No relationships found");
            }
            foreach ($relationshipActions as $v) {
                $relationshipName = $v['name'];
                $relationship = $table->getRelationship($relationshipName);
                if (PEAR::isError($relationship) or !$relationship) {
                    XFException::throwBadRequest("Relationship not found");
                }
                break;
            }
        }

        if (!$relationship) {
            XFException::throwBadRequest("Relationship not found");
        }
        
        if (!$relationshipAction) {
            if (!$relationshipActions) {
                $relationshipActions = $table->getRelationshipsAsActions();
            }
            $relationshipAction = $relationshipActions[$relationship->getName()];
        }
        
        $perms = $record->getPermissions(['relationship' => $relationship->getName()]);
        if (!@$perms['view related records']) {
            XFException::throwPermissionDenied();
        }
        
        if (!$currSort) {
            $orderCol = $relationship->getOrderColumn();
            if (!PEAR::isError($orderCol)) {
                $currSort = $orderCol;
            }
        }
        if (!$currSort) {
            $currSort = '';
        }
        if ($currSort) {
            $pos = strpos($currSort, ',');
            if ($pos !== false) {
                $currSort = trim(substr($currSort, $pos));
            }
            $pos = strpos($currSort, ' ');
            if ($pos !== false) {
                $currSortDirection = trim(substr($currSort, $pos+1));
                $currSort = trim(substr($currSort, 0, $pos));
            } else {
                $currSortDirection = 'asc';
            }
        }
        if ($currSort) {
            $perms = $record->getPermissions(['field' => $currSort, 'relationship' => $relationship->getName()]);
            if (!$perms['view']) {
                // No permission to this field.
                // so we won't allow sorting on it.
                $currSort = '';
                $currSortDirection = '';
            }
        }
        
        // We are going to break the sort fields up into sort actions
        // Using sortable+ and sortable-
        $sortActions = [];
        import(XFROOT.'Dataface/ActionTool.php');
        $at = Dataface_ActionTool::getInstance();
        foreach ($at->getActions(['category' => 'related_sort_actions']) as $action) {
            $sortActions[] = $action;
        }
        
        $sortableFields = [];
        // Try testing for explicit opt-in on sortable fields first.
        foreach ($relationship->fields(true) as $fieldName) {
            $fieldDef = $relationship->getField($fieldName, true);
            if ($this->isSortableSet($fieldDef) and $this->isSortable($fieldDef)) {
                $perms = $record->getPermissions(['field' => $fieldName, 'relationship' => $relationship->getName()]);
                if (@$perms['view']) {
                    $sortableFields[] = $fieldDef;
                }
            }
        }
        
        if (count($sortableFields) === 0 and count($sortActions) === 0) {
            // No sortable fields were found with explicit opt-in.  
            // We'll generate our own set of sortable fields.
            // Include dates, times, varchar, numeric
            // Exclude blobs, text, fields with vocabulary.
            // Note on vocab exclusions: this is because sorting will yield
            // unexpected results since the sort would be on the key and not the value.
            // Create grafted field for sorting instead.

            foreach ($relationship->fields(true) as $fieldName) {
                $fieldDef = $relationship->getField($fieldName, true);
                $t = $relationship->getTable($fieldName);
                $fieldName = $fieldDef['name'];
                //echo "$fieldName";
                //echo "isText={$table->isText($fieldName)}, isXML={$table->isXML($fieldName)},
                //isBlob={$table->isBlob($fieldName)}, isContainer={$table->isContainer($fieldName)},
                //vocabulary{$fieldDef['vocabulary']}, password={$table->isPassword($fieldName)},
                //isSortableSet={$this->isSortableSet($fieldDef)}, isSortable={$this->isSortable($fieldDef)}";
                if ($t->isText($fieldDef['name']) or 
                        $t->isXML($fieldName) or 
                        $t->isBlob($fieldName) or
                        $t->isContainer($fieldName) or 
                        @$fieldDef['vocabulary'] or
                        @$t->isPassword($fieldName) or
                        ($this->isSortableSet($fieldDef) and !$this->isSortable($fieldDef))) {
                    continue;
                }
                $perms = $record->getPermissions(['field' => $fieldName, 'relationship' => $relationship->getName()]);
                if (@$perms['view']) {
                    $sortableFields[] = $fieldDef;
                }
            }
            
        }
        
        



        
        foreach ($sortableFields as $fieldDef) {
            $t = Dataface_Table::loadTable($fieldDef['tablename']);
            $ascending = (@$fieldDef['sortable'] or @$fieldDef['sortable+']);
            $descending = (@$fieldDef['sortable'] or @$fieldDef['sortable-']);
            if (!$ascending and !$descending) {

                $ascending = $descending = true;
            }
            if ($ascending) {
                $selected = false;
                $label = $fieldDef['widget']['label'];
                if ($t->isDate($fieldDef['name']) or $t->isTime($fieldDef['name'])) {
                    $label .= ': Oldest first';
                } else if ($t->isChar($fieldDef['name'])) {
                    $label .= ': A to Z';
                }
                $materialIcon = 'arrow_upward';
                if (@$fieldDef['sort.label']) {
                    $label = $fieldDef['sort.label'];
                }
                if (@$fieldDef['sort+.label']) {
                    $label = $fieldDef['sort+.label'];
                }
                if ($currSort == $fieldDef['name'] and $currSortDirection == 'asc') {
                    $selected = true;
                }
                $sortActions[] = [
                    'label' => $label,
                    'materialIcon' => $materialIcon,
                    'selected' => $selected,
                    'name' => $t->tablename.'_sort_actions_'.$fieldDef['name'].'_asc',
                    'onclick' => 'sortAscending("'.$fieldDef['name'].'")',
                ];
            }
            
            if ($descending) {
                $label = $fieldDef['widget']['label'];
                $selected = false;
                if ($t->isDate($fieldDef['name']) or $t->isTime($fieldDef['name'])) {
                    $label .= ': Newest first';
                } else if ($t->isChar($fieldDef['name'])) {
                    $label .= ': Z to A';
                }
                $materialIcon = 'arrow_downward';
                if (@$fieldDef['sort.label']) {
                    $label = $fieldDef['sort.label'];
                }
                if (@$fieldDef['sort-.label']) {
                    $label = $fieldDef['sort+.label'];
                }
                
                if ($currSort == $fieldDef['name'] and $currSortDirection == 'desc') {
                    $selected = true;
                }
                $sortActions[] = [
                    'label' => $label,
                    'materialIcon' => $materialIcon,
                    'selected' => $selected,
                    'name' => $t->tablename.'_sort_actions_'.$fieldDef['name'].'_desc',
                    'onclick' => 'sortDescending("'.$fieldDef['name'].'")',
                    
                ];
                
            }
        }
        
        df_display(['sort_actions' => $sortActions], 'xataface/actions/related_sort_dialog.html');
        
    }
    
    function isSortable($fieldDef) {
        return @$fieldDef['sortable'] or @$fieldDef['sortable+'] or @$fieldDef['sortable-'];
    }
    
    function isSortableSet($fieldDef) {
        return isset($fieldDef['sortable']) or 
            isset($fieldDef['sortable+']) or 
                isset($fieldDef['sortable-']);
    }
    
}
?>