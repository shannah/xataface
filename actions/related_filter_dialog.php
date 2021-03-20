<?php
import(XFROOT.'xf/relationships/RelatedQueryTool.php');
use xf\relationships\RelatedQueryTool;

class dataface_actions_related_filter_dialog {
    function handle($params) {
        $app = Dataface_Application::getInstance();
        $query = $app->getQuery();
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
       
        
        
        
        $filterFields = [];
        // Try testing for explicit opt-in on sortable fields first.
        foreach ($relationship->fields(true) as $fieldName ) {
            $fieldDef = $relationship->getField($fieldName, true);
            if ($this->isFilterableSet($fieldDef) and $this->isFilterable($fieldDef)) {
                $perms = $record->getPermissions(['field' => $fieldName, 'relationship' => $relationship->getName()]);
                
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

            foreach ($relationship->fields(true) as $fieldName) {
                $fieldDef = $relationship->getField($fieldName, true);
                $t = $relationship->getTable($fieldName);
                $fieldName = $fieldDef['name'];
                //echo "$fieldName";
                //echo "isText={$table->isText($fieldName)}, isXML={$table->isXML($fieldName)},
                //isBlob={$table->isBlob($fieldName)}, isContainer={$table->isContainer($fieldName)},
                //vocabulary{$fieldDef['vocabulary']}, password={$table->isPassword($fieldName)},
                //isSortableSet={$this->isFilterableSet($fieldDef)}, isSortable={$this->isFilterable($fieldDef)}";
                if ((@$fieldDef['visibility'] and @$fieldDef['visibility']['find'] == 'hidden') or 
                        @$fieldDef['not_findable'] or
                        $t->isXML($fieldName) or 
                        @$t->isPassword($fieldName) or
                        ($this->isFilterableSet($fieldDef) and !$this->isFilterable($fieldDef))) {
                    continue;
                }
                $perms = $record->getPermissions(['field' => $fieldName, 'relationship' => $relationship->getName()]);
                if (@$perms['view'] and @$perms['find'] ) {
                    $filterFields[] = $fieldDef;
                }
            }
            
        }
        
       
        
        $actions = [];
        
        foreach ($filterFields as $fieldDef) {
            $type =  null;
            $t = Dataface_Table::loadTable($fieldDef['tablename']);
            
            $name = $fieldDef['name'];
            $text = $t->isText($name);
            $int = $t->isInt($name);
            $float = $t->isFloat($name);
            $date = $t->isDate($name);
            $time = $t->isTime($name);
            $vocabulary = @$fieldDef['vocabulary'];
            
            // The filter.vocabulary can refers to a valuelist that can supply
            // common filters that the user can choose by default.  If one is
            // supplied, then the default behaviour will be to use text or range
            // over 'filter' type, and the user will be presented with the options
            // in this filter vocabulary first - but with an 'other' option which 
            // will display the standard text or range fields.
            $filterVocabulary = @$fieldDef['filter.vocabulary'];
            $filterValuelist = null;
            if ($filterVocabulary) {
                $vals = $t->getValuelist($filterVocabulary);
                if ($vals) {
                    $filterValuelist = [];
                    $idx = 0;
                    foreach ($vals as $k=>$v) {
                        $filterValuelist[] = [
                            'name' => 'opt-'.($idx++),
                            'key' => $k,
                            'value' => $v,
                            'selected' => (@$query['-related:s:'.$name] and $query['-related:s:'.$name] === $k)
                        ];
                    }
                } else {
                    $filterVocabulary = null;
                    $filterValuelist = null;
                }
            }
            if (!$filterVocabulary and $date) {
                // This is a date field, so we'll add some sensible quicksearches like
                $filterVocabulary = 'xf_common_date_ranges';
                $vals = [];
                if ($time) {
                    $vals[''] = 'Any time';
                    $vals['>=-1 hour'] = 'Past hour';
                    $vals['>=-24 hour'] = 'Past 24 hours';
                    $vals['>=-7 day'] = 'Past week';
                    $vals['>=-1 month'] = 'Past month';
                    $vals['>=-1 year'] = 'Past year';
                    $vals['custom'] = 'Custom range...';
                } else {
                    $vals[''] = 'Any time';
                    $vals['today'] = 'Today';
                    $vals['>=-7 day'] = 'Past week';
                    $vals['>=-1 month'] = 'Past month';
                    $vals['>=-1 year'] = 'Past year';
                    $vals['custom'] = 'Custom range...';
                }
                $filterValuelist = [];
                $idx = 0;
                foreach ($vals as $k=>$v) {
                    $filterValuelist[] = [
                        'name' => 'opt-'.($idx++),
                        'key' => $k,
                        'value' => $v,
                        'selected' => (@$query['-related:s:'.$name] and $query['-related:s:'.$name] === $k)
                    ];
                }
                
            }
            
            

            $valuelist = null;
            
            if (@$fieldDef['filter.type']) {
                $type = @$fieldDef['filter.type'];
            }
            
            
            if ($vocabulary) {
                $valuelist = $t->getValuelist($vocabulary);
            }
            if (!$type) {
                if ($vocabulary and !$filterVocabulary) {
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
                    } else {
                        if ($filterVocabulary) {
                            $type = 'range';
                        } else if ($text or $date or $time) {
                            $type = 'text';
                        } else {
                            $type = 'filter';
                        }
                        
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
                $inputType = 'text';
                if ($date and $time) {
                    $inputType = 'datetime-local';
                } else if ($date) {
                    $inputType = 'date';
                } else if ($time) {
                    $inputType = 'time';
                }
                
                if (@$fieldDef['filter.input.type']) {
                    $inputType = $fieldDef['filter.input.type'];
                }
                $inputAttributes = [];
                $prefix = 'filter.input.';
                $prefixLen = strlen($prefix);
                
                foreach ($fieldDef as $k=>$v) {
                    if ($k === 'filter.input.type') {
                        continue;
                    }
                    if (substr($k, 0, $prefixLen) === $prefix) {
                        $inputAttributes[substr($k, $prefixLen)] = $v;
                    }
                }
                $minInputAttributes = [];
                $maxInputAttributes = [];
                $prefix = 'filter.max.input.';
                $prefixLen = strlen($prefix);
                foreach ($fieldDef as $k=>$v) {
                    if (substr($k, 0, $prefixLen) === $prefix) {
                        $maxInputAttributes[substr($k, $prefixLen)] = $v;
                    }
                }
                $prefix = 'filter.min.input.';
                $prefixLen = strlen($prefix);
                foreach ($fieldDef as $k=>$v) {
                    if (substr($k, 0, $prefixLen) === $prefix) {
                        $minInputAttributes[substr($k, $prefixLen)] = $v;
                    }
                }
                
                if ($type == 'filter') {
                    $options = [];
                    $col = $fieldDef['name'];
        			$orderBy = "`{$fieldDef['tablename']}`.`$col`";
                    if (@$fieldDef['filter.sort']) {
                        $orderBy = $fieldDef['filter.sort'];
                    }
                    
                    $selectedValues = [];
                    if (@$query['-related:s:'.$col]) {
                        $vals = explode(' OR ', $query['-related:s:'.$col]);
                        foreach ($vals as $val) {
                            if (!$val) {
                                continue;
                            }
                            if ($val[0] === '=') {
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
                    $qb = new RelatedQueryTool();
                    $qb->setIncludeLimits(false);
                    $qb->setIncludeOrderBy(false);
                    $filterSql = $qb->getSQL(['override_columns' => ['__COLUMN__']], ['-related:s:'.$col=>null]);
                    $filterSql = str_replace('`__COLUMN__`', "`{$fieldDef['tablename']}`.`$col`, count(*) as `num`", $filterSql);
                    $filterSql .= " group by `{$fieldDef['tablename']}`.`$col` order by $orderBy";
                    
                    
                    $res = df_query($filterSql);
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
                    $queryVal = @$query['-related:s:'.$fieldDef['name']];
                    if ($queryVal) {
                        $currentValue = $queryVal;
                        if ($currentValue[0] == '=') {
                            $searchPrefix = $currentValue[0];
                            $currentValue = substr($currentValue, 1);
                        } else if ($currentValue[0] == '~') {
                            if (strpos($currentValue, '~%') === 0) {
                                $searchPrefix = '~%';
                                $currentValue = substr($currentValue, 2);
                            } else if ($currentValue{strlen($currentValue)-1} == '%') {
                                $searchPrefix = '~';
                                $searchSuffix = '%';
                                $currentValue = substr($currentValue, 1, strlen($currentValue)-2);
                            }
                        }
                        if ($inputType == 'datetime-local') {
                            $currentValue = str_replace(' ', 'T', $currentValue);
                        }
                    }
                } else if ($type == 'range' or $type == 'min' or $type == 'max') {
                    $currentValue = @$query['-related:s:'.$fieldDef['name']];
                    
                    if ($currentValue and $currentValue[0] == '<') {
                        $currentMaxValue = substr($currentValue, 1);
                        if ($currentMaxValue and $currentMaxValue[0] == '=') {
                            $currentMaxValue = substr($currentMaxValue, 1);
                        }
                    } else if ($currentValue and $currentValue[0] == '>') {
                        $currentMinValue = substr($currentValue, 1);
                        if ($currentMinValue and $currentMinValue[0] == '=') {
                            $currentMinValue = substr($currentMinValue, 1);
                        }
                    } else if ($currentValue and strpos($currentValue, '..') !== false) {
                        list($currentMinValue, $currentMaxValue) = explode('..', $currentValue);
                    }
                    
                    if ($inputType == 'datetime-local') {
                        $currentMinValue = str_replace(' ', 'T', $currentMinValue);
                        $currentMaxValue = str_replace(' ', 'T', $currentMaxValue);
                    }
                    
                }
                
                $currentValueLabel = null;
                if ($currentValue and $filterValuelist) {
                    foreach ($filterValuelist as $opt) {
                        if ($opt['key'] == $currentValue) {
                            $currentValueLabel = $opt['value'];
                            break;
                        }
                    }
                }
                
                $actions[] = [
                    // Reference to field definition (i.e. from $table->getField($name))
                    'fieldDef' => $fieldDef,
                    
                    // The Action name.  
                    'name' => $fieldDef['name'].'-'.$type,
                    
                    // Label to display next to the filter option
                    'label' => $label,
                    
                    // Description (not used yet but could be a tool-tip)
                    'description' => $description,
                    
                    // The filter type. E.g. filter, text, range, min, max
                    'type' => $type,
                    
                    // The options available in this filter.  Includes 'key', 'value', 'count', and 'selected'
                    // properties.
                    'options' => $options,
                    
                    // Options provided by a filter valuelist.  Same properties as 'options' has.
                    'filterValuelist' => $filterValuelist,
                    
                    // Name of the filter vocabulary if it has one.  If this is non-empty/non-null,
                    // then 'filterValuelist' must be non-empty.
                    'filterVocabulary' => $filterVocabulary,
                    
                    // The current value of the filter.
                    'value' => $currentValue,
                    'valueLabel' => $currentValueLabel,
                    
                    // Search prefix.  E.g. '=', '>', '>=', etc...
                    'searchPrefix' => $searchPrefix,
                    
                    // Search suffix.  E.g. '%'
                    'searchSuffix' => $searchSuffix,
                    
                    // Current filter value for the 'min' field of the filter.
                    'currentMinValue' => $currentMinValue,
                    
                    // Current filter value for the 'max' field of the filter.
                    'currentMaxValue' => $currentMaxValue,
                    
                    // Icon to display in the 'min' field
                    'minIcon' => $minIcon,
                    
                    // Icon to display in the 'max' field.
                    'maxIcon' => $maxIcon,
                    
                    // Placeholder text for the max field.
                    'maxPlaceholder' => @$fieldDef['filter.max.placeholder'],
                    
                    // Placeholder text for the min field.
                    'minPlaceholder' => @$fieldDef['filter.min.placeholder'],
                    
                    // Placeholder text for the search field.
                    'placeholder' => @$fieldDef['filter.placeholder'],
                    
                    // The input type for the search and range fields.  E.g. text, date, datetime-local, etc..
                    'inputType' => $inputType,
                    
                    // Attributes used for the input field.  Associative array
                    'inputAttributes' => $inputAttributes,
                    
                    // Attributes used for the max input field.  Associative array
                    'minInputAttributes' => $minInputAttributes,
                    
                    // Attributes used for the min input field.  Associative array
                    'maxInputAttributes' => $maxInputAttributes
                ];
            }
            
            
            
            
            
        }
        
        df_display(['searchFields' => $actions, 'query' => @$query['-related:search'], 'relatedFilter' => true], 'xataface/actions/mobile_filter_dialog.html');
        
    }
    
    function isFilterable($fieldDef) {
        return @$fieldDef['filter'];
    }
    
    function isFilterableSet($fieldDef) {
        return isset($fieldDef['filter']);
    }
    

    
    
}
?>