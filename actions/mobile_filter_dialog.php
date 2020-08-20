<?php
class dataface_actions_mobile_filter_dialog {
    function handle($params) {
        $app = Dataface_Application::getInstance();
        $query = $app->getQuery();
        $table = Dataface_Table::loadTable($query['-table']);
        
       
        if (PEAR::isError($table)) {
            die("Table not found");
        }
        $qb = new Dataface_QueryBuilder($table->tablename, $query);
        
        $filterFields = [];
        // Try testing for explicit opt-in on sortable fields first.
        foreach ($table->fields(false, true, true) as $fieldName => $fieldDef) {
            if ($this->isFilterableSet($fieldDef) and $this->isFilterable($fieldDef)) {
                $perms = $table->getPermissions(['field' => $fieldName]);
                
                if (@$perms['view'] and @$perms['find'] ) {
                    $hidden = (@$fieldDef['visibility'] and @$fieldDef['visibility']['find'] == 'hidden');
                    if (!$hidden and !@$fieldDef['not_findable']) {
                        $filterFields[] = $fieldDef;
                    }
                    
                }
            }
        }
        
        if (count($filterFields) === 0) {
            // No filterable fields were found with explicit opt-in.  
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
                //isSortableSet={$this->isFilterableSet($fieldDef)}, isSortable={$this->isFilterable($fieldDef)}";
                if ((@$fieldDef['visibility'] and @$fieldDef['visibility']['find'] == 'hidden') or 
                        @$fieldDef['not_findable'] or
                        $table->isXML($fieldName) or 
                        @$table->isPassword($fieldName) or
                        ($this->isFilterableSet($fieldDef) and !$this->isFilterable($fieldDef))) {
                    continue;
                }
                $perms = $table->getPermissions(['field' => $fieldName]);
                if (@$perms['view'] and @$perms['find'] ) {
                    $filterFields[] = $fieldDef;
                }
            }
            
        }
        
       
        
        $actions = [];
        foreach ($filterFields as $fieldDef) {
            $type = @$fieldDef['filter.type'];
            $name = $fieldDef['name'];
            $int = $table->isInt($name);
            $float = $table->isFloat($name);
            $vocabulary = @$fieldDef['vocabulary'];
            $valuelist = null;
            if ($vocabulary) {
                $valuelist = $table->getValuelist($vocabulary);
            }
            if (!$type) {
                if ($vocabulary or @$fieldDef['filter']) {
                    $type = 'filter';
                } else {
                    if ($int) {
                        if (@$fieldDef['widget']['type'] == 'checkbox') {
                            $type = 'checkbox';
                        } else {
                            $type = 'range';
                        }
                    } else if ($float) {
                        $type = 'range';
                    } else if ($table->isChar($name) or $table->isText($name)) {
                        $type = 'contains';
                    } else if ($table->isDate($name)) {
                        $type = 'date-range';
                    } else if ($table->isTime($name)) {
                        $type = 'time-range';
                    } else {
                        $type = 'contains';
                    }
                }
                
            }
            
            $types = explode(' ', $type);
            foreach ($types as $type) {
                $label = @$fieldDef['widget']['label'];
                if (!$label and @$fieldDef['filter.'.$type.'.label']) {
                    $label = $fieldDef['filter.'.$type.'.label'];
                }
                
                if (!$label and @$fieldDef['filter.label']) {
                    $label = $fieldDef['filter.label'];
                }
                
                if (!$label) {
                    $label = $fieldDef['widget']['label'];
                }
                
                $description = null;
                if (@$fieldDef['filter.'.$type.'.description']) {
                    $description = $fieldDef['filter.'.$type.'.description'];
                }
                if (!$description and @$fieldDef['filter.description']) {
                    $description = $fieldDef['filter.description'];
                }
                
                if (!$description) {
                    $description = '';
                }
                
                $options = null;
                $currentValue = null;
                $searchPrefix = null;
                $searchSuffix = null;
                $maxValueLen = 20;
                $currentMinValue = null;
                $currentMaxValue = null;
                $minIcon = @$fieldDef['filter.min.icon'];
                $maxIcon = @$fieldDef['filter.max.icon'];
                if ($type == 'filter') {
                    $options = [];
                    $col = $fieldDef['name'];
        			$orderBy = "`$col`";
                    if (@$fieldDef['filter.sort']) {
                        $orderBy = $fieldDef['filter.sort'];
                    }
                    
                    $selectedValues = [];
                    if (@$query[$col]) {
                        $vals = explode(' OR ', $query[$col]);
                        foreach ($vals as $val) {
                            if (!$val) {
                                continue;
                            }
                            if ($val{0} === '=') {
                                $val = substr($val, 1);
                            }
                            $selectedValues[] = $val;
                        }
                       
                    }
                    if (count($selectedValues) > 0) {
                        $currentValue = '';
                        foreach ($selectedValues as $idx=>$selVal) {
                            if (strlen($currentValue) >= $maxValueLen) {
                                $currentValue .= ', and '. (count($selectedValues)-$idx).' other';
                                break;
                            }
                            if ($idx == 2) {
                                $currentValue .= ', and ' . (count($selectedValues)-$idx).' other';
                                break;
                            }
                            
                            if ($idx > 0) {
                                $currentValue .= ', ';
                            }
                            $currentValue .= $selVal;
                        }
                        if (strlen($currentValue) > $maxValueLen + 15) {
                            $parts = explode(', ', $currentValue);
                            foreach ($parts as $idx=>$part) {
                                if ($idx == count($parts)-1 and strpos($part,'and ') === 0) {
                                    // This is the last part that is just saying 'and 1 other'
                                    break;
                                }
                                if (strlen($part) > 10) {
                                    $parts[$idx] = substr($part, 0, 5).'...'.substr($part, strlen($part)-5);
                                }
                            }
                            $currentValue = implode(', ', $parts);
                        }
                    }
                    
                    
                    
                    $res = df_query("select `$col`, count(*) as `num` " . 
                        $qb->_from() . " " . 
                            $qb->_secure( $qb->_where(array($col=>null)) ) . 
                                " group by `$col` order by $orderBy", null, true);
        			foreach ($res as $row){
                        if (!$row['num']) {
                            continue;
                        }
        				if ( isset($valuelist) and isset($valuelist[$row[$col]]) ){
        					$val = $valuelist[$row[$col]];
        				} else {
        					$val = $row[$col];
        				}
				        if (!$val) {
				            continue;
				        }
                        
        				$options[] = [
        				    'key' => $row[$col],
                            'value' => $val,
                            'count' => $row['num'],
                            'selected' => in_array($row[$col], $selectedValues),
        				];
        			}
                    
                } 
                else if ($type == 'text') {
                    $queryVal = @$query[$fieldDef['name']];
                    if ($queryVal) {
                        $currentValue = $queryVal;
                        if ($currentValue{0} == '=') {
                            $searchPrefix = $currentValue{0};
                            $currentValue = substr($currentValue, 1);
                        } else if ($currentValue{0} == '~') {
                            if (strpos($currentValue, '~%') === 0) {
                                $searchPrefix = '~%';
                                $currentValue = substr($currentValue, 2);
                            } else if ($currentValue{strlen($currentValue)-1} == '%') {
                                $searchPrefix = '~';
                                $searchSuffix = '%';
                                $currentValue = substr($currentValue, 1, strlen($currentValue)-2);
                            }
                        }
                    }
                } else if ($type == 'range' or $type == 'min' or $type == 'max') {
                    $currentValue = @$query[$fieldDef['name']];
                    
                    if ($currentValue and $currentValue{0} == '<') {
                        $currentMaxValue = substr($currentValue, 1);
                        if ($currentMaxValue and $currentMaxValue{0} == '=') {
                            $currentMaxValue = substr($currentMaxValue, 1);
                        }
                    } else if ($currentValue and $currentValue{0} == '>') {
                        $currentMinValue = substr($currentValue, 1);
                        if ($currentMinValue and $currentMinValue{0} == '=') {
                            $currentMinValue = substr($currentMinValue, 1);
                        }
                    } else if ($currentValue and strpos($currentValue, '..') !== false) {
                        list($currentMinValue, $currentMaxValue) = explode('..', $currentValue);
                    }
                    
                }
                $actions[] = [
                    'fieldDef' => $fieldDef,
                    'name' => $fieldDef['name'].'-'.$type,
                    'label' => $label,
                    'description' => $description,
                    'type' => $type,
                    'options' => $options,
                    'value' => $currentValue,
                    'searchPrefix' => $searchPrefix,
                    'searchSuffix' => $searchSuffix,
                    'currentMinValue' => $currentMinValue,
                    'currentMaxValue' => $currentMaxValue,
                    'minIcon' => $minIcon,
                    'maxIcon' => $maxIcon,
                    'maxPlaceholder' => @$fieldDef['filter.max.placeholder'],
                    'minPlaceholder' => @$fieldDef['filter.min.placeholder'],
                    'placeholder' => @$fieldDef['filter.placeholder']
                ];
            }
            
            
            
            
            
        }
        
        df_display(['searchFields' => $actions], 'xataface/actions/mobile_filter_dialog.html');
        
    }
    
    function isFilterable($fieldDef) {
        return @$fieldDef['filter'];
    }
    
    function isFilterableSet($fieldDef) {
        return isset($fieldDef['filter']);
    }
    

    
    
}
?>