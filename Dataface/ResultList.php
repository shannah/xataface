<?php
/*-------------------------------------------------------------------------------
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
 *-------------------------------------------------------------------------------
 */

/******************************************************************************
 * File:		Dataface/ResultList.php
 * Author:		Steve Hannah
 * Created:	September 3, 2005
 * Description:
 * 	Handles creation and display of a result list from an SQL database.
 * 	
 *****************************************************************************/
 
 import( XFROOT.'Dataface/Table.php');
import(XFROOT.'Dataface/QueryBuilder.php');
import(XFROOT.'Dataface/Record.php');
import(XFROOT.'Dataface/QueryTool.php');
/**
 *  Handles the creation and display of a result list from the Database.
 **/
 class Dataface_ResultList {
 	
 	var $_tablename;
 	var $_db;
 	var $_columns;
 	var $_query;
 	var $_table;
 	
 	var $_results;
 	var $_resultSet;
    // List style allows you to set the style of the list to override default.
    // Values: auto or mobile
    var $listStyle = 'auto';
 	
 	var $_filterCols = array();
 
 	function __construct( $tablename, $db='', $columns=array(), $query=array()){
 		$app =& Dataface_Application::getInstance();
 		$this->_tablename = $tablename;
 		if (empty($db) ) $db = $app->db();
 		$this->_db = $db;
 		$this->_columns = $columns;
 		if ( !is_array($columns) ) $this->_columns = array();
 		$this->_query = $query;
 		if( !is_array($query) ) $this->_query = array();
 		
 		$this->_table =& Dataface_Table::loadTable($tablename);
        $this->listStyle = $this->_table->getListStyle();
 		$fieldnames = array_keys($this->_table->fields(false,true));
 		$fields =& $this->_table->fields(false,true);
 		$sortFilters = false;
 		if ( count($this->_columns)==0 ){
 			
 			foreach ($fieldnames as $field){
 				if ( @$fields[$field]['filter'] ) $this->_filterCols[] = $field;
 				if ( $fields[$field]['visibility']['list'] != 'visible') continue;
 					if ( $this->_table->isPassword($field) ) continue;
 				if ( isset( $fields[$field] ) and !preg_match('/blob/i', $fields[$field]['Type']) ){
 					$this->_columns[] = $field;
 				}
 			}
 			
 			
 		} else {
 			
 		
 			foreach ($fieldnames as $field){
 				if ( @$fields[$field]['filter'] ) $this->_filterCols[] = $field;
 			}
 		}
        if (count($this->_filterCols) > 0) {
            uasort($this->_filterCols, function($aName, $bName) {
                $a =& $this->_table->getField($aName);
                $b =& $this->_table->getField($bName);
                $oa = 0;
                $ob = 0;
                
                if (isset($a['filter.order'])) {
                    $oa = $a['filter.order'];
                } else if (isset($a['order'])) {
                    $oa = $a['order'];
                }
                if (isset($b['filter.order'])) {
                    $ob = $b['filter.order'];
                } else if (isset($b['order'])) {
                    $ob = $b['order'];
                }
                if ($oa == $ob) {
                    return 0;
                }
                return ($oa < $ob) ? -1 : 1;
            });
        }
 		
 		
 		$this->_resultSet =& Dataface_QueryTool::loadResult($tablename, $db, $query);
 		
 	}
 	function Dataface_ResultList($tablename, $db='', $columns=array(), $query=array()) {
        self::__construct($tablename, $db, $columns, $query); 
    }
 	
 	function renderCell(&$record, $fieldname){
 		$del =& $record->_table->getDelegate();
 		if ( isset($del) and method_exists($del, $fieldname.'__renderCell') ){
 			$method = $fieldname.'__renderCell';
 			return $del->$method($record);
 			//return call_user_func(array(&$del, $fieldname.'__renderCell'), $record); 
 		}
        if ( $record->table()->isContainer($fieldname) or $record->table()->isBlob($fieldname) ){
            return $record->htmlValue($fieldname, 0, 0, 0, array('class'=>'blob-preview'));
        }
		$field =& $record->_table->getField($fieldname);
		$maxcols = 50;
		if ( @$field['list'] and @$field['list']['maxcols'] ){
		    $maxcols = intval($field['list']['maxcols']);
		}
 		$out = $record->preview($fieldname);
 		$fulltext = "";
 		if ( strlen($out) > $maxcols ){
 		    $fulltext = $out;
 		    $out = substr($out, 0, $maxcols).'...';
 		}
 		if ( $fulltext ){
            $fulltext = 'data-fulltext="'.df_escape($fulltext).'"';
        }
 		if ( !@$field['noEditInListView'] and 
                @$field['noLinkFromListView'] and 
                $record->checkPermission('edit', array('field'=>$fieldname) ) ) {
                    
 			$recid = $record->getId();
 			
 			$out = '<span df:showlink="1" df:id="'.$recid.'#'.$fieldname.'" class="df__editable" '.$fulltext.'>'.df_escape($out).'</span>';
 		} else {
 		    $out = '<span '.$fulltext.'>'.df_escape($out).'</span>';
 		}
 		return $out;
 	}
 	
 	function renderRowHeader($tablename=null){
 		if ( !isset($tablename) ) $tablename = $this->_table->tablename;
 		$del =& $this->_table->getDelegate();
 		if ( isset($del) and method_exists($del, 'renderRowHeader') ){
 			return $del->renderRowHeader($tablename);
 		}
 		$app =& Dataface_Application::getInstance();
 		$appdel =& $app->getDelegate();
 		if ( isset($appdel) and method_exists($appdel,'renderRowHeader') ){
 			return $appdel->renderRowHeader($tablename);
 		}
 		return null;
 	}
 	
    function getTfootContent(){
            if ( !isset($tablename) ) $tablename = $this->_table->tablename;
 		$del =& $this->_table->getDelegate();
 		if ( isset($del) and method_exists($del, 'renderRowFooterTemplate') ){
 			return $del->renderRowFooterTemplate($tablename);
 		}
 		$app =& Dataface_Application::getInstance();
 		$appdel =& $app->getDelegate();
 		if ( isset($appdel) and method_exists($appdel,'renderRowFooterTemplate') ){
 			return $appdel->renderRowFooterTemplate($tablename);
 		}
 		return '';
    }
        
 	function renderRow(&$record, $mode = 'desktop'){
 		$del =& $record->_table->getDelegate();
 		if ($mode == 'desktop') {
 		    if ( isset($del) and method_exists($del, 'renderRow') ){
                return $del->renderRow($record);
            }
            $app =& Dataface_Application::getInstance();
            $appdel =& $app->getDelegate();
            if ( isset($appdel) and method_exists($appdel,'renderRow') ){
                return $appdel->renderRow($record);
            }
            return null;
 		} else if ($mode == 'mobile') {
 		    if ( isset($del) and method_exists($del, 'renderRowMobile') ){
                return $del->renderRowMobile($record);
            }
            $app =& Dataface_Application::getInstance();
            $appdel =& $app->getDelegate();
            if ( isset($appdel) and method_exists($appdel,'renderRowMobile') ){
                return $appdel->renderRowMobile($record);
            }
            return null;
 		}
 		
 	}
    
    
 	
 	function &getResults(){
 		if ( !isset($this->_results) ){
 			/*
 			// It seems all dandy to only load the columns we need...but if the user
 			// is using a custom template we may need more columns.
 			// boo!!!
			$columns = array_unique(
				array_merge( 
					$this->_columns, 
					array_keys(
						$this->_table->keys()
					) 
				)
			);
			*/
			
			$this->_resultSet->loadSet(null/*$columns*/,true,false,true);
			$this->_results = new Dataface_RecordIterator($this->_tablename, $this->_resultSet->data());
			
		}
		return $this->_results;
 	
 	}
 	
 	private function print_actions($actions) {
 	    foreach ($actions as $action){
            $materialIcon = '';
            if (@$action['materialIcon']) {
                $materialIconStyle = @$action['materialIconStyle'] ? 
                    $action['materialIconStyle'] : 
                    '';
                $materialIcon = '<i class="material-icons '.df_escape($materialIconStyle).'">'.df_escape($action['materialIcon']).'</i>';
                //echo "MaterialICon $materialIcon";exit;
            }
        
            $url = $action['url'];
            $onclick = @$action['onclick'];
            if ($onclick) {
                $url = 'javascript:void(0);';
                $onclick = 'onclick="'.htmlspecialchars($onclick).'" ';
            }
            echo '<a href="'.df_escape($url).'" '.$onclick.
                'class="'.df_escape(@$action['class']).' '.
                    ((@$action['icon'] or $materialIcon)?'with-icon':'').'" '.
                        (@$action['icon']?' style="'.df_escape('background-image: url('.$action['icon'].')').'"':'').(@$action['target']?' target="'.df_escape($action['target']).'"':'').' title="'.df_escape(@$action['description']?$action['description']:$action['label']).'">'.$materialIcon.'<span>'.df_escape($action['label']).'</span></a> ';
        }
 	}
 	
 	function toHtml($mode = 'all'){
        
        xf_script('xataface/actions/list.js');
 	    import(XFROOT.'Dataface/ActionTool.php');
 	    $mobile = $mode == 'mobile';
 	    $desktop = $mode == 'desktop';
 	    $all = $mode == 'all';
 		$app =& Dataface_Application::getInstance();
 		$at =& Dataface_ActionTool::getInstance();
 		$query =& $app->getQuery();
 	    if ($all) {
            
            ob_start();
            
            
		    $actions = $at->getActions(['category'=>'list_settings']);

            if (@$actions['list_filter']) {

                // Change the label of the filter action to indicate if any filters are currently applied
                $filterCount = 0;
                foreach ($this->_table->fields(false, true, true) as $fieldDef) {
                    if (@$fieldDef['filter']) {
                        $queryVal = @$query[$fieldDef['name']];
                        if ($queryVal and trim($queryVal)) {
                            $filterCount++;
                        }
                    }
                }
                if ($filterCount > 0) {
                    $actions['list_filter']['label'] .= ' • '.$filterCount;
                }
            }
        
            $cls = (@$app->prefs['use_xataface2_result_filters'] ? 'mobile' : '');
        
		    echo '<div class="mobile-list-settings-wrapper '.$cls.'">';
	
            if ( count($actions)>0){
                echo ' <div class="mobile-list-settings">';
                $this->print_actions($actions);
                echo '</div>';
            }
	    
		    echo '</div>';
            
            $filtersHtml = ob_get_contents();
            ob_end_clean();
 	        //$this->toHtml('mobile');
            $template = $this->getTemplate();
            if ($template) {
                // When using a template, we only display the desktop version (it's up to the template to handle responsive)
                return $filtersHtml . $this->toHtml('desktop');
            } else {
                return $filtersHtml . $this->toHtml('desktop').$this->toHtml('mobile');
            }
 	        
 	    }
 	    
        
        
 		
        
        
        
 		if ( isset( $query['-sort']) ){
 			$sortcols = explode(',', trim($query['-sort']));
 			$sort_columns = array();
 			foreach ($sortcols as $sortcol){
 				$sortcol = trim($sortcol);
 				if (strlen($sortcol) === 0 ) continue;
 				$sortcol = explode(' ', $sortcol);
 				if ( count($sortcol) > 1 ){
 					$sort_columns[$sortcol[0]] = strtolower($sortcol[1]);
 				} else {
 					$sort_columns[$sortcol[0]] = 'asc';
 				}
 				break;
 			}
 			unset($sortcols);	// this was just a temp array so we get rid of it here
 		} else {
 			$sort_columns = array();
 		}
 		
 		// $sort_columns should now be of the form [ColumnName] -> [Direction]
 		// where Direction is "asc" or "desc"
 		
 		
 		if (true or $this->_resultSet->found() > 0 ) {
 		
 			
 			if  ($desktop and @$app->prefs['use_old_resultlist_controller'] and $this->_resultSet->found() > 0){
				ob_start();
				df_display(array(), 'Dataface_ResultListController.html');
				$controller = ob_get_contents();
				ob_end_clean();
			} else {
			    $controller = '';
			}
		
 			
			ob_start();
			//echo '<div style="clear: both"/>';
			if ( !defined('Dataface_ResultList_Javascript') ){
				define('Dataface_ResultList_Javascript',true);
				$jt = Dataface_JavascriptTool::getInstance();
				$jt->import('Dataface/ResultList.js');
				
				//echo '<script language="javascript" type="text/javascript" src="'.DATAFACE_URL.'/js/Dataface/ResultList.js"></script>';
			}
			
			if ( $desktop and !@$app->prefs['hide_result_filters'] and count($this->_filterCols) > 0 ){
                if (@$app->prefs['use_xataface2_result_filters']) {
                    echo $this->getResultFilters();
                } else {

                }
				
			}
			unset($query);
			
			if ( @$app->prefs['use_old_resultlist_controller'] and $this->_resultSet->found() > 0){
				echo '<div class="resultlist-controller" id="resultlist-controller-top">';
	
				echo $controller;
				echo "</div>";
			}
		
			
			
			$canSelect = false;
			if ( !@$app->prefs['disable_select_rows'] ){
				$canSelect = Dataface_PermissionsTool::checkPermission('select_rows',
							Dataface_PermissionsTool::getPermissions( $this->_table ));
			}
			
			
			$sq = $myq = $app->getQuery();
			foreach ($sq as $sqk=>$sqv ){
				if ( !$sqk or $sqk[0] == '-' ){
					unset($sq[$sqk]);
				}
			}
			if ( @$myq['-sort'] ) $sq['-sort'] = $myq['-sort'];
			if ( @$myq['-skip'] ) $sq['-skip'] = $myq['-skip'];
			if ( @$myq['-limit'] ) $sq['-limit'] = $myq['-limit'];
			
			
			$sq = json_encode($sq);
			$jt = Dataface_JavascriptTool::getInstance();

			$jt->import('list.js');
			$results =& $this->getResults();

            $template = $this->getTemplate();
            $templateParams = [];

			if ($desktop) {
                if (!$template) {
                    echo '
                        <table data-xataface-query="'.df_escape($sq).'" id="result_list" class="listing resultList resultList--'.$this->_tablename.' list-style-'.$this->listStyle.'">
                        <thead>
                        <tr>';
                    if ( $canSelect){
                        echo '<th><input type="checkbox" onchange="toggleSelectedRows(this,\'result_list\');"></th>';
                    }
            
                    if ( !@$app->prefs['disable_ajax_record_details']  ){
                        echo '	<th><!-- Expand record column --></th>
                        ';
                    }
                    echo '<th class="row-actions-header"></th>';
                
                }
               
                $perms = array();
            
            
                foreach ($this->_columns as $key){
                    $cursor=$this->_resultSet->start();
                    $results->reset();
                    $perms[$key] = false;
                    while ( $results->hasNext() ){
                        $record = $results->next();
                        if ( $record->checkPermission('list', array("field"=>$key)) ){
                            $perms[$key] = true;
                            break;
                        }
                    }
                }
            
                $numCols = 0;
            
                
                $rowHeaderHtml = isset($template) ? null : $this->renderRowHeader();
                if ( isset($rowHeaderHtml) ){
                    echo $rowHeaderHtml;
                } else {
                
                    $templateCols = [];
                
                
                    foreach ($this->_columns as $key ){
                        if ( in_array($key, $this->_columns) ){
                            $templateCol = [
                                
                                'name' => $key
                            ];
                            
                            //if ( !($perms[$key] =  Dataface_PermissionsTool::checkPermission('list', $this->_table, array('field'=>$key)) /*Dataface_PermissionsTool::view($this->_table, array('field'=>$key))*/) ) continue;
                            if ( !@$perms[$key] ) continue;
                            if ( isset($sort_columns[$key]) ){
                                $class = 'sorted-column-'.$sort_columns[$key];
                                $query = array();
                                $qs_columns = $sort_columns;
                                unset($qs_columns[$key]);
                                $sort_query = $key.' '.($sort_columns[$key] == 'desc' ? 'asc' : 'desc');
                                foreach ( $qs_columns as $qcolkey=> $qcolvalue){
                                    $sort_query .= ', '.$qcolkey.' '.$qcolvalue;
                                }
                            } else {
                                $class = 'unsorted-column';
                                $sort_query = $key.' asc';
                                foreach ( $sort_columns as $scolkey=>$scolvalue){
                                    $sort_query .= ', '.$scolkey.' '.$scolvalue;
                                }
                            
                            }
                            $sq = array('-sort'=>$sort_query);
                            $link = Dataface_LinkTool::buildLink($sq);
                            $templateCol['sortLink'] = $link;
                            $numCols++;
                            $label = $this->_table->getFieldProperty('column:label', $key);
                            $templateCol['label'] = $label;
                            $legend = $this->_table->getFieldProperty('column:legend', $key);
                            $templateCol['legend'] = $legend;
                            if ( $legend ){
                                $legend = '<span class="column-legend">'.df_escape($legend).'</span>';
                            }
                        
                            $colType = $this->_table->getType($key);
                            $templateCol['type'] = $colType;
                            $class .= ' coltype-'.$colType;
                            $cperms = $this->_table->getPermissions(array('field'=>$key));
                            $templateCol['permissions'] = $cperms;
                            if ( !$this->_table->isSearchable($key) or !@$cperms['find'] ){
                                $class .= ' unsearchable-column';
                                $templateCol['searchable'] = false;
                            } else {
                                $class .= ' searchable-column';
                                $templateCol['searchable'] = true;
                            }
                        
                            $class .= ' '.$this->getHeaderCellClass($key);
                        
                            if ( !$label ) $label = $this->_table->getFieldProperty('widget:label',$key);
                            $templateCol['label'] = $label;
                            $searchColumn = $this->_table->getDisplayField($key);
                            $templateCol['searchColumn'] = $searchColumn;
                            if (!$template) {
                                echo "<th data-column=\"$key\" data-search-column=\"$searchColumn\" class=\"$class\"><a class='sort-link' href=\"$link\"><i class='material-icons'>sort</i></a><span class='th-label'>".df_escape($label)."</span> $legend</th>";
                            }
                            
                            $templateCols[$key] = $templateCol;
                            
                        }
                    }
                }
                if (!$template) {
                    echo "</tr>
                        </thead>
                                        <tfoot style='display:none'>".$this->getTfootContent()."</tfoot>
                        <tbody>
                        ";
                }
                
            } // end if ($desktop)
            else if ($mobile) {
                echo '<div class="mobile mobile-listing resultList--'.$this->_tablename.' list-style-'.$this->listStyle.'" data-xataface-query="'.df_escape($sq).'">';
            }
	        $templateParams['columns'] = $templateCols;
			$templateRows = [];
            
			$cursor=$this->_resultSet->start();
			$results->reset();
			$baseQuery = array();
			foreach ( $_GET as $key=>$value){
				if ( strpos($key,'-') !== 0 ){
					$baseQuery[$key] = $value;
				}
			}
			$evenRow = false;
			while ($results->hasNext() ){
                $templateRow = [];
                
				$rowClass = $evenRow ? 'even' : 'odd';
				$evenRow = !$evenRow;
				$record =& $results->next();
				$recperms = $record->getPermissions();
				$templateRow['permissions'] = $recperms;
                
				if ( !@$recperms['view'] ){
					$cursor++;
					unset($record);
					continue;
				}
                $templateRow['record'] = $record;
				$rowClass .= ' '.$this->getRowClass($record);
				$status = $record->getStatus();
                $templateRow['status'] = $status;
                if ($status) {
                    $rowClass .= ' xf-record-status-'.$status;
                }
				
				
				$query = array_merge( $baseQuery, array( "-action"=>"browse", "-relationship"=>null, "-cursor"=>$cursor++) );
				
				if (  @$recperms['link'] ){
                    if (@$app->prefs['result_list_use_publiclink']) {
                        $link = $record->getPublicLink();
                    } else if ( @$app->prefs['result_list_use_geturl'] ){
						$link = $record->getURL('-action=view');
					} else {
						
						$link = Dataface_LinkTool::buildLink($query).'&-recordid='.urlencode($record->getId());
					}
				} else {
					$del =& $record->_table->getDelegate();
					if ( $del and method_exists($del, 'no_access_link') ){
						$link = $del->no_access_link($record);
					} else {
						$link = null;
					}
				}
                $templateRow['link'] = $link;
				$recordid = $record->getId();
				$templateRow['recordid'] = $recordid;
                $templateRow['class'] = $rowClass;
				if ($desktop) {
				
                    if (!$template) {
                        echo "<tr class=\"listing $rowClass\" xf-record-id=\"".df_escape($recordid)."\">";
                    }
                    if ( $canSelect ) {
                        $templatRow['selectable'] = true;
                        if (!$template) {
                            $permStr = array();
                            foreach ($recperms as $pk=>$pv){
                                if ( $pv ) $permStr[] = $pk;
                            }
                            $permStr = df_escape(implode(',', $permStr));
                            echo '<td class="checkbox-cell"><input class="rowSelectorCheckbox" xf-record-id="'.df_escape($recordid).'" id="rowSelectorCheckbox:'.df_escape($recordid).'" type="checkbox" data-xf-permissions="'.$permStr.'"></td>';
                        }
                        
                    } else {
                        $templateRow['selectable'] = false;
                    }
                
                
                
                
                    if ( !$template and !@$app->prefs['disable_ajax_record_details']  ){
                        echo '<td class="ajax-record-details-cell">';
                        echo '<script language="javascript" type="text/javascript"><!--
                                registerRecord(\''.addslashes($recordid).'\',  '.$record->toJS(array()).');
                                //--></script>
                                <img src="'.DATAFACE_URL.'/images/treeCollapsed.gif" onclick="resultList.showRecordDetails(this, \''.addslashes($recordid).'\')"/>';
                    
                    
                        echo '</td>';
                        unset($at, $actions);
                    }
                } else if ($mobile){
                    echo "<div class=\"mobile-listing-row $rowClass\" xf-record-id=\"".df_escape($recordid)."\" xf-record-id=\"".df_escape($recordid)."\">";
                
                }
				


				//print_r($actions);
				if ($desktop) {
                    $templateRow['actions_category'] = 'list_row_actions';
                    if (!$template) {
    				    $actions = $at->getActions(array('category'=>'list_row_actions', 'record'=>&$record));
    				    echo '<td class="row-actions-cell">';
				
                        if ( count($actions)>0){
                            echo ' <span class="row-actions">';
                            $this->print_actions($actions);
                            echo '</span>';
                        }
				    
    				    echo '</td>';
                    }
				    
				} 
				
				
				
				
				$rowContentHtml = isset($template) ? null : $this->renderRow($record, $mode);
				if ( isset($rowContentHtml) ){
					echo $rowContentHtml;
				} else if ($desktop) {
					//$expandTree=false; // flag to indicate when we added the expandTree button
					//if ( @$app->prefs['enable_ajax_record_details'] === 0 ){
					//	$expandTree = true;
					//}
					
                    $templateRowColumns = [];
                    
					foreach ($this->_columns as $key){
						$thisField =& $record->_table->getField($key);
						if ( !$perms[$key] ) continue;
						$templateRowColumn = [
						    'name' => $key
						]; 
						$val = $this->renderCell($record, $key);
                        $templateRowColumn['renderedValue'] = $val;
						if ( $record->checkPermission('edit', array('field'=>$key)) and !$record->_table->isMetaField($key)){
							$editable_class = 'df__editable_wrapper';
                            $templateRowColumn['editable'] = true;
						} else {
							$editable_class = '';
                            $templateRowColumn['editable'] = false;
						}
						
						if ( !@$thisField['noLinkFromListView'] and $link and $val ){
						    if (substr($val, 0, 3) != '<a ') {
						        // If the render cell value is already a link, then don't 
						        // re-wrap
							    $val = "<a rel='child' href=\"$link\" class=\"unmarked_link\">".$val."</a>";
							    $editable_class = '';
							}
						} else {
							
						}
						
						if ( @$thisField['noEditInListView'] ) {
						    $editable_class='';
                            $templateRowColumn['editable'] = false;
						}
						
						if (!$template) {
    						$cellClass = 'resultListCell resultListCell--'.$key;
    						$cellClass .= ' '.$record->table()->getType($key);
    						if ( !trim($val) ){
    						    $val = '&nbsp;';
    						}
    						echo "<td id=\"td-".rand()."\" class=\"field-content $cellClass $rowClass $editable_class\">$val</td>";
						}
						$templateRowColumns[$key] = $templateRowColumn;
						unset($thisField);
					}
                    $templateRow['columns'] = $templateRowColumns;
				} else if ($mobile) {
				    echo "<div class='mobile-row-content' >";
				    $logoField = $record->table()->getLogoField();
                    $rowStyle = $record->getTableAttribute('row_style');
                    $aOpen = '';
                    $aClose = '';
                    if ($link) {
                        $aOpen = '<a rel="child" href="'.df_escape($link).'">';
                        $aClose = '</a>';
                    }
				    if ($logoField and $record->val($logoField) and $record->checkPermission('view', array('field' => $logoField))) {
				        echo "<div class='mobile-logo'>$aOpen".$record->htmlValue($logoField)."$aClose</div>";
				    } else {
                        if ($rowStyle != 'external-link') {
                            echo "<div class='mobile-logo'>$aOpen<i class='material-icons'>description</i>$aClose</div>";
                        }
				        
				    }
                    $byLine = $record->getByLine();
                    if ($byLine) {
                        // getByLine returns HTML content so we don't escape it.
                        echo "<div class='mobile-byline'>".$byLine."</div>";
                    }
                    
                    
                    if ($rowStyle == 'external-link') {
                        $externalLink = $record->val('external_link');
                        if ($externalLink) {
                            echo '<div class="external-link-preview" data-href="'.htmlspecialchars($externalLink['url']).'">';
                            if (@$externalLink['cover_image']) {
                                echo '<img class="external-link-cover-image" src="'.htmlspecialchars($externalLink['cover_image']).'"/>';
                            }
                            if (@$externalLink['title']) {
                                echo '<span class="external-link-title">'.htmlspecialchars($externalLink['title']).'</span>';
                            }
                            
                            echo '<span class="external-link-host">'.htmlspecialchars(parse_url($externalLink['url'], PHP_URL_HOST)).'</span>';
                            echo '</div>';
                        }
                    } else {
    				    echo "<div class='mobile-title'>$aOpen".df_escape($record->getTitle())."$aClose</div>";
                    }
    				echo "<div class='mobile-description'>$aOpen".df_escape($record->getDescription())."$aClose</div>";
                    
                    
				    
                    $actions = $at->getActions(array('category'=>'list_row_actions', 'record'=>&$record));
                    if ( count($actions)>0){
                        echo ' <div class="mobile-row-actions">';
                        $this->print_actions($actions);
                        echo '</div>';
                    }
                    


				    echo "</div><!-- mobile-row-content -->";
				}
				
				if ($desktop) {
                    if (!$template) {
                        echo "</tr>";
                    }
				    
				} else if ($mobile) {
				    echo "</div><!-- mobile-listing -->";
				}
				
				if ($desktop) {
                    
                    if (!$template) {
    				    echo "<tr class=\"listing $rowClass\" style=\"display:none\" id=\"{$recordid}-row\">";
                        if ( $canSelect ){
                            echo "<td><!--placeholder for checkbox col --></td>";
                        }
                        echo '<td><!-- placeholder for actions --></td>';
                        echo "<td colspan=\"".($numCols+1)."\" id=\"{$recordid}-cell\"></td>
                              </tr>";
                    }
				    
				}
                $templateRow['record'] = $record;
				$templateRows[] = $templateRow;
				
				unset($record);
			}
			if ($desktop) {
                if (!$template) {
    			    if ( @$app->prefs['enable_resultlist_add_row'] ){
                        echo "<tr id=\"add-new-row\" df:table=\"".df_escape($this->_table->tablename)."\">";
                        if ( $canSelect ) $colspan=2;
                        else $colspan = 1;
                        echo "<td colspan=\"$colspan\"><script language=\"javascript\">require(DATAFACE_URL+'/js/addable.js')</script><a href=\"#\" onclick=\"df_addNew('add-new-row');return false;\">".df_translate('scripts.GLOBAL.LABEL_ADD_ROW', "Add Row")."</a></td>";
                        foreach ( $this->_columns as $key ){
                            echo "<td><span df:field=\"".df_escape($key)."\"></span></td>";
                        }
                        echo "</tr>";
                    }
                    echo "</tbody>
                        </table>";
                    if ( $canSelect and $this->_resultSet->found() > 0){
                        echo  '<form id="result_list_selected_items_form" method="post" action="'.df_absolute_url(DATAFACE_SITE_HREF).'">';
                        $app =& Dataface_Application::getInstance();
                        $q =& $app->getQuery();
                        foreach ( $q as $key=>$val){
                            if ( strlen($key)>1 and $key[0] == '-' and $key[1] == '-' ){
                                continue;
                            }
                            echo '<input type="hidden" name="'.urlencode($key).'" value="'.df_escape($val).'" />';
                        }
                        echo '<input type="hidden" name="--selected-ids" id="--selected-ids" />';
                        echo '<input type="hidden" name="-from" id="-from" value="'.$q['-action'].'" />';
                        echo '<input type="hidden" name="--redirect" value="'.base64_encode($app->url('')).'" />';
                        echo '</form>';
            
    
                    

                        $actions = $at->getActions(array('category'=>'selected_result_actions'));
                        if ( count($actions) > 0 and $this->listStyle != 'mobile'){
                            echo '<div id="selected-actions">'.df_translate('scripts.Dataface_ResultList.MESSAGE_WITH_SELECTED', "With Selected").': <ul class="selectedActionsMenu" id="result_list-selectedActionsMenu">';
                            foreach ($actions as $action){
                                $img = '';
                                if ( @$action['icon'] ){
                                    $img = '<img src="'.$action['icon'].'"/>';
                                }
                        
                                if ( !@$action['onclick'] and !$action['url'] ){
                                    $action['onclick'] = "return actOnSelected('result_list', '".@$action['name']."'".(@$action['confirm']?", function(){return confirm('".addslashes($action['confirm'])."');}":"").")";
                            
                                }
                        
                                echo <<<END
                                <li id="action-{$action['id']}"><a href="{$action['url']}" onclick="{$action['onclick']}" title="{$action['description']}">{$img}{$action['label']}</a></li>
END;
                            }
            
            
                            echo '</ul></div>';
                        }
                    }
        
                }
			    
                $templateParams['rows'] = $templateRows;
                if ($template) {
                    df_display($templateParams, $template);
                }
                
                if ( @$app->prefs['use_old_resultlist_controller'] and $this->_resultSet->found() > 0){
                    echo '<div class="resultlist-controller" id="resultlist-controller-bottom">';
    
                    echo $controller;
                    echo '</div>';
                }
			}
			
		
			
			$out = ob_get_contents();
			ob_end_clean();
		}
        
        if ($desktop) {
            // Just to prevent from running twice.
            // In actuality this is used for both mobile and desktop
			if ( @$app->prefs['use_old_resultlist_controller'] and $this->_resultSet->found() > 0){
				ob_start();
				df_display(array(), 'Dataface_ResultListController.html');
				$out .= ob_get_contents();
				ob_end_clean();
			} else {
				//$out = '';
			}
            ob_start();
            df_display([], 'xataface/actions/list/no_results_found.html');
            $out .= ob_get_contents();
            ob_end_clean();
        }
		

		$out = '<div class="resultlist-parent ' . ($this->_resultSet->found()>0?'non-empty':'empty').'">'.$out.'</div>';
		
 		
 		return $out;
 	}
 	
    private $template;
    
    function setTemplate($template) {
        $this->template = $template;
    }
    
    function getTemplate() {
        return $this->template;
    }
    
 	function getRowClass(&$record){
 		$del =& $this->_table->getDelegate();
 		if ( isset($del) and method_exists($del, 'css__tableRowClass') ){
 			return $del->css__tableRowClass($record);
 		}
 		return '';
 	}
 	
 	function getHeaderCellClass($col){
 		$del =& $this->_table->getDelegate();
 		if ( isset($del) and method_exists($del, 'css__tableHeaderCellClass') ){
 			return $del->css__tableHeaderCellClass($col);
 		}
 		return '';
 	}
 	
 	function getResultFilters(){
                if ( !$this->_filterCols ){
                    return '';
                }
 		ob_start();
 		$app =& Dataface_Application::getInstance();
 		$query =& $app->getQuery();

		echo '<div class="resultlist-filters">
		<h3 class="resultlist-filters-heading">'.df_translate('scripts.Dataface_ResultList.MESSAGE_FILTER_RESULTS', 'Filter Results').':</h3>
		<script language="javascript"><!--
		
	    function resultlist__updateAllFilters() {
			var currentURL = "'.$app->url('').'";
			var selects = document.querySelectorAll(\'.resultlist-filters select\');
			selects.forEach(function(select) {
				currentURL = resultlist__updateFilters(select.getAttribute(\'data-col\'), select, currentURL);
			});
			window.location = currentURL;
		}

		function resultlist__updateFilters(col,select, currentURL){
			var autoRedirect = currentURL ? false : true;
            var isMultiSelect = select.multiple;
			currentURL = currentURL || "'.$app->url('').'";
			var currentParts = currentURL.split("?");
			var currentQuery = "?"+currentParts[1];
			var value = "";
            if (isMultiSelect) {
                jQuery(select).val().forEach(function(v, idx) {
                    if (value) {
                        value += " OR =";
                    }
                    value += v;
                });
               
            } else {
                value = select.options[select.selectedIndex].value;
            }
			var regex = new RegExp(\'([?&])\'+col+\'={1,2}[^&]*\');
            
			if ( currentQuery.match(regex) ){
				if ( value ){
					prefix = "=";
				} else {
					prefix = "";
				}
				currentQuery = currentQuery.replace(regex, \'$1\'+col+\'=\'+prefix+encodeURIComponent(value));
			} else {
                if ( value) {
                    currentQuery += \'&\'+col+\'==\'+encodeURIComponent(value);
                }
			}
			currentQuery = currentQuery.replace(/([&\?])-skip=[^&]+/, "$1");
			if (autoRedirect) {
				window.location=currentParts[0]+currentQuery;
			} else {
				return currentParts[0]+currentQuery;
			}
		}
		//--></script>
		<ul>';

		$qb = new Dataface_QueryBuilder($this->_table->tablename, $query);
		$autoUpdateFilters = true;
		if (isset($app->prefs['auto_update_filters']) and !$app->prefs['auto_update_filters']) {
			$autoUpdateFilters = false;
		}
        $showRowCounts = true;
		if (isset($app->prefs['show_filter_counts']) and !$app->prefs['show_filter_counts']) {
			$showRowCounts = false;
		}
		foreach ( $this->_filterCols as $col ){
			$field =& $this->_table->getField($col);
			
			unset($vocab);
			if ( isset($field['vocabulary']) ){
				$vocab =& $this->_table->getValuelist($field['vocabulary']);
				
			} else {
				$vocab=null;
				
			}
			$onchange = 'onchange="resultlist__updateFilters(\''.addslashes($col).'\', this);"';
			if (!$autoUpdateFilters) {
				$onchange = '';
			}
            $isMultiSelect = false;
            $multiple = ' ';
            if (@$field['filter.multiple']) {
                $multiple = ' multiple ';
                $isMultiSelect = true;
            }
            
			echo '<li> '.df_escape($field['widget']['label']).' <select'.$multiple.'data-col="'.htmlspecialchars($col).'" '.$onchange.'><option value="">'.df_translate('scripts.GLOBAL.LABEL_ALL', 'All').'</option>';
			$orderBy = "`$col`";
            if (@$field['filter.sort']) {
                $orderBy = $field['filter.sort'];
            }
			$res = df_query("select `$col`, count(*) as `num` ".$qb->_from()." ".$qb->_secure( $qb->_where(array($col=>null)) )." group by `$col` order by $orderBy", null, true);
			if ( !$res and !is_array($res)) trigger_error(xf_db_error(df_db()), E_USER_ERROR);
			if ( @$query[$col] and $query[$col][0] == '=' ) $queryColVal = substr($query[$col],1);
			
			else $queryColVal = @$query[$col];
            
            $queryColVals = explode(' OR ', $queryColVal);
            foreach ($queryColVals as $k=>$v) {
                
                if ($v and $v[0] == '=') {
                    $queryColVals[$k] = substr($v, 1);
                }
            }
            
           
			
			//while ( $row = xf_db_fetch_assoc($res) ){
			foreach ($res as $row){
				if ( isset($vocab) and isset($vocab[$row[$col]]) ){
					$val = $vocab[$row[$col]];
				} else {
					$val = $row[$col];
				}
				$selected = '';
                
                if ($isMultiSelect and in_array($row[$col], $queryColVals)) {
                    $selected = ' selected';
                } else if (!$isMultiSelect and $queryColVal == $row[$col] ) {
                    $selected = ' selected';
                }

                $countStr = $showRowCounts ? (' ('.$row['num'].')') : '';
				echo '<option value="'.df_escape($row[$col]).'"'.$selected.'>'.df_escape($val.$countStr).'</option>';
				
			}
			//@xf_db_free_result($res);
			echo '</select></li>';
		}
		
		echo '</ul>';
		if (!$autoUpdateFilters) {
			echo '<div class="resultlist-filters-buttons"><button onclick="resultlist__updateAllFilters();"><i class="material-icons">update</i> Update</button></div>';
		}
		echo '</div>';
		$out = ob_get_contents();
		ob_end_clean();
		return $out;
	
 	
 	}
 	
 	

 	
 
 }
 
