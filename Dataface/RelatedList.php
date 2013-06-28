<?php

/* -------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp (shannah@sfu.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * -------------------------------------------------------------------------------
 */

/* * ****************************************************************************
 * File:		Dataface/ResultList.php
 * Author:		Steve Hannah
 * Created:	September 3, 2005
 * Description:
 * 	Handles creation and display of a result list from an SQL database.
 * 	
 * *************************************************************************** */

import('Dataface/Table.php');
import('Dataface/QueryBuilder.php');
import('Dataface/LinkTool.php');

class Dataface_RelatedList {

    var $_tablename;
    var $_relationship_name;
    var $_relationship;
    var $_db;
    var $_table;
    var $_record;
    var $_start;
    var $_limit;
    var $_where;
    var $hideActions = false;
    var $noLinks = false;

    function Dataface_RelatedList(&$record, $relname, $db = '') {
        if (!is_a($record, 'Dataface_Record')) {
            throw new Exception("In Dataface_RelatedList constructor, the first argument is expected to be an object of type 'Dataface_Record' but received '" . get_class($record));
        }
        $this->_record = & $record;
        $this->_tablename = $this->_record->_table->tablename;
        $this->_db = $db;
        $this->_relationship_name = $relname;


        $this->_table = & $this->_record->_table;
        $this->_relationship = & $this->_table->getRelationship($relname);

        $this->_start = isset($_REQUEST['-related:start']) ? $_REQUEST['-related:start'] : 0;
        $this->_limit = isset($_REQUEST['-related:limit']) ? $_REQUEST['-related:limit'] : 30;

        $app = & Dataface_Application::getInstance();
        $query = & $app->getQuery();
        if (isset($query['-related:search'])) {
            $rwhere = array();
            foreach ($this->_relationship->fields() as $rfield) {
                //list($garbage,$rfield) = explode('.', $rfield);
                $rwhere[] = '`' . str_replace('.', '`.`', $rfield) . '` LIKE \'%' . addslashes($query['-related:search']) . '%\'';
            }
            $rwhere = implode(' OR ', $rwhere);
        } else {
            $rwhere = 0;
        }
        $this->_where = $rwhere;
    }

    function _forwardButtonHtml() {
        $numRecords = $this->_record->numRelatedRecords($this->_relationship_name, $this->_where);
        if ($this->_start + $this->_limit >= $numRecords)
            return '';
        $query = array('-related:start' => $this->_start + $this->_limit, '-related:limit' => $this->_limit);
        $link = Dataface_LinkTool::buildLink($query);
        $out = '<a href="' . $link . '" title="Next ' . $this->_limit . ' Results"><img src="' . DATAFACE_URL . '/images/go-next.png" alt="Next" /></a>';
        if (($this->_start + (2 * $this->_limit)) < $numRecords) {
            $query['-related:start'] = $numRecords - ( ($numRecords - $this->_start) % $this->_limit) - 1;
            $link = Dataface_LinkTool::buildLink($query);
            $out .= '<a href="' . $link . '" title="Last"><img src="' . DATAFACE_URL . '/images/go-last.png" alt="Last" /></a>';
        }
        return $out;
    }

    function _backButtonHtml() {
        if ($this->_start <= 0)
            return '';
        $query = array('-related:start' => max(0, $this->_start - $this->_limit), '-related:limit' => $this->_limit);
        $link = Dataface_LinkTool::buildLink($query);
        $out = '<a href="' . $link . '" title="Previous ' . $this->_limit . ' Results"><img src="' . DATAFACE_URL . '/images/go-previous.png" alt="Previous" /></a>';

        if (($this->_start - $this->_limit) > 0) {
            $query['-related:start'] = 0;
            $out = '<a href="' . Dataface_LinkTool::buildLink($query) . '" title="First"><img src="' . DATAFACE_URL . '/images/go-first.png" alt="First" /></a>' . $out;
        }

        return $out;
    }

    function renderCell(&$record, $fieldname) {
        $del = & $record->_table->getDelegate();
        if (isset($del) and method_exists($del, $fieldname . '__renderCell')) {
            $method = $fieldname . '__renderCell';
            return $del->$method($record);
            //return call_user_func(array(&$del, $fieldname.'__renderCell'), $record); 
        }
        return null;
    }

    function toHtml() {
        $context = array();
        $context['relatedList'] = $this;
        $app = & Dataface_Application::getInstance();
        $context['app'] = & $app;

        $query = & $app->getQuery();
        $context['query'] = & $query;

        if (isset($query['-related:sort'])) {
            $sortcols = explode(',', trim($query['-related:sort']));
            $sort_columns = array();
            foreach ($sortcols as $sortcol) {
                $sortcol = trim($sortcol);
                if (strlen($sortcol) === 0)
                    continue;
                $sortcol = explode(' ', $sortcol);
                if (count($sortcol) > 1) {
                    $sort_columns[$sortcol[0]] = strtolower($sortcol[1]);
                } else {
                    $sort_columns[$sortcol[0]] = 'asc';
                }
                break;
            }
            unset($sortcols); // this was just a temp array so we get rid of it here
        } else {
            $sort_columns = array();
        }
        $context['sort_columns'] = & $sort_columns;

        $sort_columns_arr = array();
        foreach ($sort_columns as $colkey => $colorder) {
            $sort_columns_arr[] = '`' . $colkey . '`' . $colorder;
        }
        if (count($sort_columns_arr) > 0) {
            $sort_columns_str = implode(', ', $sort_columns_arr);
        } else {
            $sort_columns_str = 0;
        }



        unset($query);


        $skinTool = & Dataface_SkinTool::getInstance();
        $context['skinTool'] = & $skinTool;

        $resultController = & $skinTool->getResultController();
        $context['resultController'] = & $resultController;

        $s = & $this->_table;
        $r = & $this->_relationship->_schema;
        $fkeys = $this->_relationship->getForeignKeyValues();
        $default_order_column = $this->_relationship->getOrderColumn();

        //echo "Def order col = $default_order_column";
        ob_start();
        df_display(array('redirectUrl' => $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING']), 'Dataface_MoveUpForm.html');
        $moveUpForm = ob_get_contents();
        ob_end_clean();
        $context['moveUpForm'] = $moveUpForm;





        $records = & $this->_record->getRelatedRecords($this->_relationship_name, true, $this->_start, $this->_limit, $this->_where);

        if (PEAR::isError($records)) {
            $records->addUserInfo("Error retrieving records from relationship " . $this->_relationship_name);
            return $records;
        }
        $context['records'] = & $records;

        //echo "<br/><b>Now Showing</b> ".($this->_start+1)." to ".(min($this->_start + $this->_limit, $this->_record->numRelatedRecords($this->_relationship_name)));
        $perms = $this->_record->getPermissions(array('relationship' => $this->_relationship_name));
        $context['perms'] = $perms;
        $context['record_editable'] = Dataface_PermissionsTool::edit($this->_record);
        $context['can_add_new_related_record'] = @$perms['add new related record'];
        $context['can_add_existing_related_record'] = @$perms['add existing related record'];

        if (!$this->hideActions and ($context['record_editable'] or @$perms['add new related record'] or @$perms['add existing related record'])) {
            $query = array('-action' => 'new_related_record');
            $link = Dataface_LinkTool::buildLink($query);
            $context['new_related_record_query'] = $query;
            $context['new_related_record_link'] = $link;

            $domainTable = $this->_relationship->getDomainTable();
            //$context['domainTable'] =& $domainTable;
            $importTablename = $domainTable;
            if (!PEAR::isError($domainTable)) {
                //This relationship is many-to-many so we can add existing records to it.
                $query2 = array('-action' => 'existing_related_record');
                $context['existing_related_record_query'] = $query2;
                $link2 = Dataface_LinkTool::buildLink($query2);
                $context['existing_related_record_link'] = $link2;

                $destTables = $this->_relationship->getDestinationTables();
                $context['destTables'] = & $destTables;
                $importTablename = $destTables[0]->tablename;
                $context['importTablename'] = $importTablename;
            }
            if (!PEAR::isError($importTablename)) {
                $importTable = & Dataface_Table::loadTable($importTablename);
                $context['importTable'] = & $importTable;
                $query3 = array('-action' => 'import');
                $context['import_related_records_query'] = & $query3;
                $link3 = Dataface_LinkTool::buildLink($query3);
                $context['import_related_records_link'] = $link3;
            }
        }

        $imgIcon = DATAFACE_URL . '/images/search_icon.gif';
        $searchSrc = DATAFACE_URL . '/js/Dataface/RelatedList/search.js';
        $relname = $this->_relationship_name;
        $context['relationship_label'] = $this->_relationship->getLabel();
        $context['relname'] = $relname;
        $context['relationship_name'] = $this->_relationship_name;
        $context['searchSrc'] = $searchSrc;
        $context['imgIcon'] = $imgIcon;


        if (!$this->hideActions) {
            $num_related_records = $this->_record->numRelatedRecords($this->_relationship_name, $this->_where);
            $now_showing_start = $this->_start + 1;
            $now_showing_finish = min($this->_start + $this->_limit, $this->_record->numRelatedRecords($this->_relationship_name, $this->_where));

            $stats_context = array(
                'num_related_records' => $num_related_records,
                'now_showing_start' => $now_showing_start,
                'now_showing_finish' => $now_showing_finish,
                'relationship_name' => $this->_relationship_name,
                'limit_field' => $resultController->limitField('related:'),
                'back_link' => $this->_backButtonHtml(),
                'next_link' => $this->_forwardButtonHtml()
            );

            import('Dataface/ActionTool.php');
            $at = & Dataface_ActionTool::getInstance();
            $actions = $at->getActions(array(
                'category' => 'related_list_actions'
                    )
            );

            $context['related_list_actions'] = $actions;
            foreach ($stats_context as $k => $v)
                $context[$k] = $v;
        }


        import('Dataface/ActionTool.php');
        $at = & Dataface_ActionTool::getInstance();
        $selected_actions = $at->getActions(array('category' => 'selected_related_result_actions'));
        $context['selected_actions'] = $selected_actions;

        if ($this->_relationship->_schema['list']['type'] == 'treetable') {
            import('Dataface/TreeTable.php');
            $treetable = new Dataface_TreeTable($this->_record, $this->_relationship->getName());
            $context['treetable'] = $treetable->toHtml();
        } else {
            echo $moveUpForm;
            if (!$this->hideActions and $this->_where) {

                $filterQuery = & $app->getQuery();
                $context['filterQuery'] = & $filterQuery;
            }
            if (count($records) > 0) {

                ob_start();
                echo '
                        <table class="listing relatedList relatedList--' . $this->_tablename . ' relatedList--' . $this->_tablename . '--' . $this->_relationship_name . '" id="relatedList">
                        <thead>
                        <tr>';

                if (count($selected_actions) > 0) {
                    echo '<th>';
                    if (!$this->hideActions) {
                        echo '<input type="checkbox" onchange="toggleSelectedRows(this,\'relatedList\');">';
                    }
                    echo '</th>';
                }
                $cols = array_keys(current($records));



                $col_tables = array();
                $table_keys = array();
                $localFields = $this->_record->table()->fields();
                $usedColumns = array();
                foreach ($cols as $key) {
                    if ($key == $default_order_column)
                        continue;
                    if (is_int($key))
                        continue;
                    if (isset($sort_columns[$key])) {
                        $class = 'sorted-column-' . $sort_columns[$key];
                        $query = array();
                        $qs_columns = $sort_columns;
                        unset($qs_columns[$key]);
                        $sort_query = $key . ' ' . ($sort_columns[$key] == 'desc' ? 'asc' : 'desc');
                        foreach ($qs_columns as $qcolkey => $qcolvalue) {
                            $sort_query .= ', ' . $qcolkey . ' ' . $qcolvalue;
                        }
                    } else {
                        $class = 'unsorted-column';
                        $sort_query = $key . ' asc';
                        foreach ($sort_columns as $scolkey => $scolvalue) {
                            $sort_query .= ', ' . $scolkey . ' ' . $scolvalue;
                        }
                    }
                    $sq = array('-related:sort' => $sort_query);
                    $link = Dataface_LinkTool::buildLink($sq);

                    $fullpath = $this->_relationship_name . '.' . $key;

                    $field = & $s->getField($fullpath);
                    if (isset($this->_relationship->_schema['visibility'][$key]) and $this->_relationship->_schema['visibility'][$key] == 'hidden')
                        continue;
                    if ($field['visibility']['list'] != 'visible')
                        continue;
                    if ($s->isBlob($fullpath) or $s->isPassword($fullpath))
                        continue;
                    if (isset($localFields[$key]) and !isset($this->_relationship->_schema['visibility'][$key]))
                        continue;
                    if (PEAR::isError($field)) {
                        $field->addUserInfo("Error getting field info for field $key in RelatedList::toHtml() ");
                        return $field;
                    }
                    $usedColumns[] = $key;

                    $label = $field['widget']['label'];
                    if (isset($field['column']) and @$field['column']['label']) {
                        $label = $field['column']['label'];
                    }

                    $legend = '';
                    if (@$field['column'] and @$field['column']['legend']) {
                        $legend = '<span class="column-legend">' . df_escape($field['column']['legend']) . '</span>';
                    }
                    if (!$this->noLinks) {
                        echo '<th><a href="' . df_escape($link) . '">' . df_escape($field['widget']['label']) . "</a> $legend</th>\n";
                    } else {
                        echo '<th>' . $field['widget']['label'] . '</th>';
                    }
                    if (!isset($col_tables[$key]))
                        $col_tables[$key] = $field['tablename'];
                    if (!isset($table_keys[$col_tables[$key]])) {
                        $table_table = & Dataface_Table::loadTable($field['tablename']);
                        $table_keys[$col_tables[$key]] = array_keys($table_table->keys());
                        unset($table_table);
                    }
                    unset($field);
                }
                echo "</tr>
					</thead>
					<tbody id=\"relatedList-body\">
					";

                $limit = min($this->_limit, $this->_record->numRelatedRecords($this->_relationship_name, $this->_where) - $this->_start);
                $relatedTable = $this->_relationship->getDomainTable();
                if (PEAR::isError($relatedTable)) {
                    $relatedTable = reset($r['selected_tables']);
                }
                $relatedTable = Dataface_Table::loadTable($relatedTable);

                $relatedKeys = array_keys($relatedTable->keys());
                foreach (array_keys($relatedKeys) as $i) {
                    $relatedKeys[$i] = $this->_relationship_name . "." . $relatedKeys[$i];
                }

                $fullpaths = array();
                $fields_index = array();
                foreach ($usedColumns as $key) {
                    $fullpaths[$key] = $this->_relationship_name . '.' . $key;
                    $fields_index[$key] = & $s->getField($fullpaths[$key]);
                }

                $evenRow = false;


                for ($i = $this->_start; $i < ($this->_start + $limit); $i++) {
                    $rowClass = $evenRow ? 'even' : 'odd';
                    $evenRow = !$evenRow;

                    if ($default_order_column and @$perms['reorder_related_records']) {
                        $style = 'cursor:move';
                        // A variable that will be used below in javascript to decide
                        // whether to make the table sortable or not
                        $sortable_js = 'true';
                    } else {
                        $style = '';
                        $sortable_js = 'false';
                    }
                    $context['sortable_js'] = $sortable_js;


                    unset($rrec);
                    $rrec = $this->_record->getRelatedRecord($this->_relationship_name, $i, $this->_where, $sort_columns_str); //new Dataface_RelatedRecord($this->_record, $this->_relationship_name, $this->_record->getValues($fullpaths, $i, 0, $sort_columns_str));
                    $rrecid = $rrec->getId();

                    echo "<tr class=\"listing $rowClass\" style=\"$style\" id=\"row_$rrecid\">";
                    if (count($selected_actions) > 0) {
                        echo '
						<td class="' . $rowClass . ' viewableColumn" nowrap>';
                        if (!$this->hideActions) {
                            echo '<input xf-record-id="' . df_escape($rrecid) . '" class="rowSelectorCheckbox" id="rowSelectorCheckbox:' . df_escape($rrecid) . '" type="checkbox">';
                        }
                        echo '</td>';
                    }


                    $link_queries = array();
                    foreach ($usedColumns as $key) {
                        if (is_int($key))
                            continue;

                        $fullpath = $fullpaths[$key];
                        unset($field);
                        $field = & $fields_index[$key]; //$s->getField($fullpath);
                        $srcRecord = & $rrec->toRecord($field['tablename']);
                        $link = $srcRecord->getURL('-action=browse&-portal-context=' . urlencode($rrecid));
                        $srcRecordId = $srcRecord->getId();

                        //$val = $this->_record->preview($fullpath, $i,255, $this->_where, $sort_columns_str);
                        if (  $srcRecord->table()->isContainer($field['name']) or $srcRecord->table()->isBlob($field['name']) ){
                            $val =  $rrec->htmlValue($key, array('class'=>'blob-preview'));
                                    //$rrec->htmlValue($key);
                        } else {
                            $val = strip_tags($rrec->display($key));
                        }
                        $title = "";

                        if ($key == $default_order_column) {
                            unset($field);
                            unset($srcRecord);
                            continue;
                        } else {
                            if ($val != 'NO ACCESS') {
                                $accessClass = 'viewableColumn';
                            } else {

                                $accessClass = '';
                            }
                            $cellClass = 'resultListCell resultListCell--' . $key;
                            $cellClass .= ' ' . $srcRecord->table()->getType($key);
                            $renderVal = $this->renderCell($srcRecord, $field['Field']);
                            if (isset($renderVal))
                                $val = $renderVal;
                            else if ($link and !@$field['noLinkFromListView'] and !$this->noLinks and $rrec->checkPermission('link', array('field' => $key)))
                                $val = "<a href=\"" . df_escape($link) . "\" title=\"" . df_escape($title) . "\" data-xf-related-record-id=\"" . df_escape($srcRecordId) . "\" class=\"xf-related-record-link\">" . $val . "</a>";
                            echo "<td class=\"$cellClass $rowClass $accessClass\">$val</td>\n";
                            unset($srcRecord);
                        }
                    }
                    echo "</tr>\n";
                }

                echo "</tbody>
					</table>";

                $related_table_html = ob_get_contents();
                $context['related_table_html'] = $related_table_html;
                ob_end_clean();

                if (!$this->hideActions) {
                    ob_start();

                    echo '<form id="result_list_selected_items_form" method="post">';
                    $app = & Dataface_Application::getInstance();
                    $q = & $app->getQuery();
                    foreach ($q as $key => $val) {
                        if (strlen($key) > 1 and $key{0} == '-' and $key{1} == '-') {
                            continue;
                        }
                        echo '<input type="hidden" name="' . $key . '" value="' . df_escape($val) . '">';
                    }
                    echo '<input type="hidden" name="--selected-ids" id="--selected-ids">';
                    echo '<input type="hidden" name="-from" id="-from" value="' . $query['-action'] . '">';
                    echo '</form>';
                    $selected_actions_form = ob_get_contents();
                    $context['selected_actions_form'] = $selected_actions_form;
                    ob_end_clean();




                    // This bit of javascript goes through all of the columns and removes all columns that 
                    // don't have any accessible information for this query.  (i.e. any columns for which
                    // each row's value is 'NO ACCESS' is removed
                    $prototype_url = DATAFACE_URL . '/js/scriptaculous/lib/prototype.js';
                    $context['prototype_url'] = $prototype_url;
                    $scriptaculous_url = DATAFACE_URL . '/js/scriptaculous/src/scriptaculous.js';
                    $context['scriptaculous_url'] = $scriptaculous_url;
                    $effects_url = DATAFACE_URL . '/js/scriptaculous/src/effects.js';
                    $context['effects_url'] = $effects_url;
                    $dragdrop_url = DATAFACE_URL . '/js/scriptaculous/src/dragdrop.js';
                    $context['dragdrop_url'] = $dragdrop_url;
                    $thisRecordID = $this->_record->getId();
                    $context['thisRecordID'] = $thisRecordID;
                }
            }
        }

        ob_start();
        df_display($context, 'xataface/RelatedList/list.html');
        $out = ob_get_contents();
        ob_end_clean();

        return $out;
    }

}

