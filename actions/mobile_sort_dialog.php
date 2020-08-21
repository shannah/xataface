<?php
class dataface_actions_mobile_sort_dialog {
    function handle($params) {
        $app = Dataface_Application::getInstance();
        $query = $app->getQuery();
        $currSort = @$query['-sort'];
        $currSortDirection = 'asc';
        $table = Dataface_Table::loadTable($query['-table']);
        if (!$currSort) {
            $currSort = @$table->_atts['default_sort'];
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
       
        if (PEAR::isError($table)) {
            die("Table not found");
        }
        // We are going to break the sort fields up into sort actions
        // Using sortable+ and sortable-
        $sortActions = [];
        import(XFROOT.'Dataface/ActionTool.php');
        $at = Dataface_ActionTool::getInstance();
        foreach ($at->getActions(['category' => 'sort_actions']) as $action) {
            $sortActions[] = $action;
        }
        
        $sortableFields = [];
        // Try testing for explicit opt-in on sortable fields first.
        foreach ($table->fields(false, true, true) as $fieldName => $fieldDef) {
            if ($this->isSortableSet($fieldDef) and $this->isSortable($fieldDef)) {
                $perms = $table->getPermissions(['field' => $fieldName]);
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

            foreach ($table->fields(false, true, true) as $fieldName => $fieldDef) {
                //echo "$fieldName";
                //echo "isText={$table->isText($fieldName)}, isXML={$table->isXML($fieldName)},
                //isBlob={$table->isBlob($fieldName)}, isContainer={$table->isContainer($fieldName)},
                //vocabulary{$fieldDef['vocabulary']}, password={$table->isPassword($fieldName)},
                //isSortableSet={$this->isSortableSet($fieldDef)}, isSortable={$this->isSortable($fieldDef)}";
                if ($table->isText($fieldName) or 
                        $table->isXML($fieldName) or 
                        $table->isBlob($fieldName) or
                        $table->isContainer($fieldName) or 
                        @$fieldDef['vocabulary'] or
                        @$table->isPassword($fieldName) or
                        ($this->isSortableSet($fieldDef) and !$this->isSortable($fieldDef))) {
                    continue;
                }
                $perms = $table->getPermissions(['field' => $fieldName]);
                if (@$perms['view']) {
                    $sortableFields[] = $fieldDef;
                }
            }
            
        }
        
        



        
        foreach ($sortableFields as $fieldDef) {
            $ascending = (@$fieldDef['sortable'] or @$fieldDef['sortable+']);
            $descending = (@$fieldDef['sortable'] or @$fieldDef['sortable-']);
            if (!$ascending and !$descending) {

                $ascending = $descending = true;
            }
            if ($ascending) {
                $selected = false;
                $label = $fieldDef['widget']['label'];
                if ($table->isDate($fieldDef['name']) or $table->isTime($fieldDef['name'])) {
                    $label .= ': Oldest first';
                } else if ($table->isChar($fieldDef['name'])) {
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
                    'name' => $table->tablename.'_sort_actions_'.$fieldDef['name'].'_asc',
                    'onclick' => 'sortAscending("'.$fieldDef['name'].'")',
                ];
            }
            
            if ($descending) {
                $label = $fieldDef['widget']['label'];
                $selected = false;
                if ($table->isDate($fieldDef['name']) or $table->isTime($fieldDef['name'])) {
                    $label .= ': Newest first';
                } else if ($table->isChar($fieldDef['name'])) {
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
                    'name' => $table->tablename.'_sort_actions_'.$fieldDef['name'].'_desc',
                    'onclick' => 'sortDescending("'.$fieldDef['name'].'")',
                    
                ];
                
            }
        }
        
        df_display(['sort_actions' => $sortActions], 'xataface/actions/mobile_sort_dialog.html');
        
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