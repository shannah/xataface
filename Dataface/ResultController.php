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
/* * *****************************************************************************
 * File: 	Dataface/ResultController.php
 * Author: Steve Hannah <shannah@sfu.ca>
 * Description:
 * 	Allows for database controlling .  Forward back, etc.. functionality
 * 	
 * **************************************************************************** */

import('Dataface/QueryBuilder.php');
import('Dataface/Table.php');
import('Dataface/LinkTool.php');

$GLOBALS['Dataface_ResultController_limit'] = 20;
$GLOBALS['Dataface_ResultController_skip'] = 0;

class Dataface_ResultController {

    var $_baseUrl;
    var $_prevLink;
    var $_nextLink;
    var $_tablename;
    var $_queryBuilder;
    var $_fields;
    var $_table;
    var $_db;
    var $_records;
    var $_pos;
    var $_totalRecords;
    var $_query;
    var $_resultSet;
    var $type;

    /**
     * Number of records currently displayed by this query.
     */
    var $_displayedRecords;

    /**
     * The column to be displayed on the controller for navigation.
     */
    var $_titleColumn;
    var $_contentsList;

    function Dataface_ResultController($tablename, $db = '', $baseUrl = '', $query = '') {
        $this->_tablename = $tablename;
        $this->_db = $db;
        $this->_baseUrl = $baseUrl ? $baseUrl : $_SERVER['PHP_SELF'];
        $app = & Dataface_Application::getInstance();

        $this->_table = & Dataface_Table::loadTable($this->_tablename);
        $this->_fields = & $this->_table->fields();

        if (!$query) {
            $this->_query = & $app->getQuery();
            $query = & $this->_query;
            $this->_queryBuilder = new Dataface_QueryBuilder($this->_query['-table'], $query);
            $this->_resultSet = & $app->getResultSet();
        } else {

            if (!is_array($query)) {
                $query = array("-mode" => $app->_conf['default_mode']);
            }

            $this->_resultSet = & Dataface_QueryTool::loadResult($tablename, $db, $query);




            if (!isset($query['-limit'])) {
                $query['-limit'] = $GLOBALS['Dataface_ResultController_limit'];
            }
            if (!isset($query['-skip'])) {
                $query['-skip'] = $GLOBALS['Dataface_ResultController_skip'];
            }
            if (!isset($query['-cursor'])) {
                $query['-cursor'] = $query['-skip'];
            }
            if (!isset($query['-mode'])) {
                $query['-mode'] = $app->_conf['default_mode'];
            }

            $this->_queryBuilder = new Dataface_QueryBuilder($this->_tablename, $query);
            $this->_query = & $this->_queryBuilder->_query;
        }

        // set the title column
        foreach ($this->_fields as $field) {
            if (preg_match('/char/i', $field['Type'])) {
                $this->_titleColumn = $field['name'];
                break;
            }
        }

        if (!isset($this->_titleColumn)) {
            reset($this->_fields);
            $field = current($this->_fields);
            $this->_titleColumn = $field['name'];
        }

        // set the position
        $this->_pos = $query['-cursor'];


        $this->_displayedRecords = $query['-limit'];
    }

    function &getRecords() {
        if (!isset($this->_records)) {
            $this->_records = array();

            $sql = $this->_queryBuilder->select();

            $sqlStats = $this->_queryBuilder->select_num_rows();
            $db = & Dataface_DB::getInstance();
            $res = $db->query($sqlStats, $this->_table->db, null, true);
            //if ( !$res and !is_array($res) ){
            $this->_totalRecords = $res[0]['num'];
            //list($this->_totalRecords) = mysql_fetch_row( mysql_query( $sqlStats, $this->_db ) );
            //$res = mysql_query($sql, $this->_db);
            $res = $db->query($sql, $this->_table->db, null, true);

            if (!$res and !is_array($res)) {
                throw new Exception("An error occurred attempting to retrieve records from the database.: " . mysql_error($this->db), E_USER_ERROR);
            }
            $this->_displayedRecords = count($res);

            //while ( $row = mysql_fetch_array($res) ){
            foreach ($res as $row) {
                $this->_records[] = $row;
            }
        }

        return $this->_records;
    }

    function getCurrentPosition() {

        return $this->_pos;
    }

    function setBaseUrl($url) {
        $this->_baseUrl = $url;
    }

    function getBaseUrl() {
        if (!isset($this->_basUrl)) {
            $this->_baseUrl = $_SERVER['PHP_SELF'];
        }

        return $this->_baseUrl;
    }

    // Gets the number of records in this found set.
    function getTotalRecords() {
        if (!isset($this->_totalRecords)) {
            $db = & Dataface_DB::getInstance();
            $res = $db->query($this->_queryBuilder->select_num_rows(), $this->_table->db, null, true);
            $this->_totalRecords = $res[0]['num'];
            //list($this->_totalRecords) = mysql_fetch_row( mysql_query( $this->_queryBuilder->select_num_rows() ) );
        }

        return $this->_totalRecords;
    }

    function getDisplayedRecords() {
        return $this->_displayedRecords;
    }

    /**
     * Returns a list of the links to pages of results.
     */
    function getPageIndex(&$selected_url) {
        $totalRecords = $this->getTotalRecords();
        $pageNumber = 1;
        $contents = array();

        for ($i = 0; $i < $totalRecords; $i+=$this->_query['-limit']) {
            $query = /* array_merge($this->_query, */array("-skip" => $i, "-action" => "list")/* ) */;
            $link = $this->_buildLink($query);
            $contents[$link] = $pageNumber++;

            if ($this->_resultSet->start() >= $i and $this->_resultSet->start() < ($i + $this->_query['-limit'])) {
                $selected_url = $link;
            }
        }

        return $contents;
    }

    /**
     * Generate list of options to be displayed in the "Jump" menu.
     */
    function getContentsList($selected_url) {

        if (!isset($this->_contentsList)) {

            $this->_contentsList = array();
            if ($this->getTotalRecords() > 100) {
                // there are over 100 records in this found set.  It is unfeasible
                // to list them all in the jump menu, so instead we will list ranges
                // of records
                //$totalRecords = $this->getTotalRecords();
                //for ( $i=0; $i<$totalRecords; $i+=$this->_query['-limit']){
                //	$query = array_merge($this->_query, array("-skip"=>$i, "-action"=>"list"));
                //	$link = $this->_buildLink($query);
                //	$this->_contentsList[$link] = ($i+1)." to ".($i+$this->_query['-limit']);
                //	
                //	if ( $this->_resultSet->start() >= $i and $this->_resultSet->start() < ($i+$this->_query['-limit'])){
                //		$selected_url = $link;
                //	}
                //}
            } else {
                // There are less than 100 records.. Just list them individually
                //$sql = $this->_queryBuilder->select( 
                //		array($this->_titleColumn), 
                //		array('-skip'=>null, '-limit'=>null) 
                //	);
                //$res = mysql_query( 
                //	$sql, 
                //	$this->_db 
                //);
                $titles = $this->_resultSet->getTitles(false, true, false);
                $index = 0;
                //while ( $row = mysql_fetch_array($res) ){
                $len = count($titles);

                for ($index = 0; $index < $len; $index++) {
                    //$query = array( "-cursor"=>$index++, "-action"=>'browse');
                    $query = array("-cursor" => $index, "-action" => 'browse');
                    $query = array_merge($this->_query, $query);
                    foreach ($query as $key => $value) {
                        if ($value === null) {
                            unset($query[$key]);
                        }
                    }
                    $link = $this->_buildSetLink($query);

                    //$this->_contentsList[ $link ] = $row[0];
                    $this->_contentsList[$link] = $titles[$index];

                    //if ( $index-1 == $this->_query['-cursor'] ){
                    if ($index == $this->_query['-cursor']) {
                        $selected_url = $link;
                    }
                }
            }
        }



        return $this->_contentsList;
    }

    function _buildSetLink($query = array()) {
        return Dataface_LinkTool::buildSetLink($query);
    }

    /**
     * Can be used in static context as well.
     * @deprecated Use Dataface_LinkTool::buildLink() instead
     */
    function _buildLink($query = array()) {

        return Dataface_LinkTool::buildLink($query);
    }

    function jumpMenu() {
        $contents = $this->getContentsList($selected_url);
        if ($contents) {
            $html = '<select name="controller" class="jumpMenu" onchange="javascript:window.location=this.options[this.selectedIndex].value;">
				
				';
            $selected_url = '';
            $currentLink = Dataface_LinkTool::buildLink(array());
            $currentLink = preg_replace('/&?-limit=\d+/', '', $currentLink);
            // link sans limit for use in field to specify number of records to be displayed per page

            foreach ($contents as $key => $value) {

                $selected = $key == $selected_url ? "selected" : '';
                $html .= '
						<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
            }
            $html .= '</select>';
        } else {
            $html = '';
        }
        return $html;
    }

    function limitField($prefix = '') {
        $currentLink = Dataface_LinkTool::buildLink(array('-' . $prefix . 'limit' => null, '-' . $prefix . 'skip' => 0));
        if (!$prefix) {
            $limitval = $this->_resultSet->limit();
        } else if (isset($_GET['-' . $prefix . 'limit'])) {
            $limitval = $_GET['-' . $prefix . 'limit'];
        } else {
            $limitval = 30;
        }
        $limitField = '<input type="text" value="' . $limitval . '" onchange="window.location = \'' . $currentLink . '&-' . $prefix . 'limit=\'+this.value" size="3"/>';
        $displayStr = df_translate('display x records per page', 'Display %s records per page');
        return '('.sprintf($displayStr, $limitField).')';
        
    }

    function toHtml($prefix = '') {
        $app = & Dataface_Application::getInstance();
        $query = & $app->getQuery();
        if (!isset($this->type))
            $this->type = $query['-mode'];

        switch ($this->type) {
            case 'browse':
                return $this->browseHtml($prefix);
            default:
                return $this->listHtml($prefix);
        }
    }

    function listHtml($prefix = '') {
        $app = & Dataface_Application::getInstance();
        $rs = & $this->_resultSet;
        $pages = array();
        $start = $rs->start();
        $end = $rs->end();
        $limit = max($rs->limit(), 1);
        $found = $rs->found();

        // we show up to 5 pages on either side of the current position
        $pages_before = ceil(floatval($start) / floatval($limit));
        $pages_after = ceil(floatval($found - $end - 1) / floatval($limit));
        $curr_page = $pages_before + 1;
        $total_pages = $pages_before + $pages_after + 1;

        //$i = $curr_page;
        $i_start = $start;
        for ($i = $curr_page; $i > max(0, $curr_page - 5); $i--) {
            $pages[$i] = $app->url('-' . $prefix . 'limit=' . $limit . '&-' . $prefix . 'skip=' . max($i_start, 0));
            if ($this->_baseUrl)
                $pages[$i] = $this->_baseUrl . '?' . substr($pages[$i], strpos($pages[$i], '?') + 1);
            $i_start -= $limit;
        }
        //$i = $curr_page+1;
        $i_start = $start + $limit;
        for ($i = $curr_page + 1; $i <= min($total_pages, $curr_page + 5); $i++) {
            $pages[$i] = $app->url('-' . $prefix . 'limit=' . $limit . '&-' . $prefix . 'skip=' . $i_start);
            if ($this->_baseUrl)
                $pages[$i] = $this->_baseUrl . '?' . substr($pages[$i], strpos($pages[$i], '?') + 1);
            $i_start += $limit;
        }
        ksort($pages);

        $pages2 = array();
        if ($curr_page > 1) {
            $pages2[df_translate('scripts.GLOBAL.LABEL_PREV', 'Prev')] = $pages[$curr_page - 1];
        }

        foreach ($pages as $pageno => $pageval) {
            $pages2[$pageno] = $pageval;
        }

        if ($curr_page < $total_pages) {

            $pages2[df_translate('scripts.GLOBAL.LABEL_NEXT', 'Next')] = $pages[$curr_page + 1];
        }
        $out = array('<ul class="resultController">');
        $out[] = '<li class="rs-description">' . df_translate('scripts.GLOBAL.MESSAGE_FOUND', 'Found ' . $found . ' records', array('found' => $found)) . ' </li>';
        foreach ($pages2 as $pageno => $link) {
            if ($pageno == $curr_page)
                $selected = ' selected';
            else
                $selected = '';
            $out[] = '<li class="' . $selected . '"><a href="' . df_escape($link) . '">' . $pageno . '</a></li>';
        }
        $appurl = $app->url('');
        $appurl = preg_replace('/[&\?]' . preg_quote('-' . $prefix . 'limit=') . '[^&]*/', '', $appurl);
        $appurl = preg_replace('/[&\?]' . preg_quote('-' . $prefix . 'skip=') . '[^&]*/', '', $appurl);
        $urlprefix = ( $this->_baseUrl ? $this->_baseUrl . '?' . substr($appurl, strpos($appurl, '?') + 1) : $appurl);
        $out[] = '<li class="results-per-page"> ' . df_translate('scripts.GLOBAL.LABEL_SHOWING', 'Showing') . ' <input type="text" value="' . $limit . '" onchange="window.location = \'' . $urlprefix . '&-' . $prefix . 'limit=\'+this.value" size="3"/>' . df_translate('scripts.GLOBAL.MESSAGE_RESULTS_PER_PAGE', 'Results per page');
        $out[] = '</ul>';

        return implode("\n", $out);
    }

    function browseHtml($prefix) {



        $html = '
   			<div class="resultController">
   			
   			<div class="container"><div class="controllerJumpMenu">
   			<b>Jump: </b>';
        $html .= $this->jumpMenu() . '</div>
   			
   			</div>
   			
   			
   			<table class="forwardBackTable" width="100%" border="0" cellpadding="0" cellspacing="5"><tr><td width="33%" valign="top" align="left" bgcolor="#eaeaea">
   			' . $this->getPrevLinkHtml() . '
   			</td><td width="34%" valign="top" align="center">
   			
   			' . $this->getCurrentHtml();

        if ($this->_query['-mode'] == 'list') {
            $html .='<br/>' . $this->limitField();
        }

        $html .= '
			
   			</td><td width="33%" valign="top" align="right" bgcolor="#eaeaea">
   			
   			' . $this->getNextLinkHtml() . '
   			</td>
   			</tr>
   			</table>
   			</div>
   				';
        return $html;
    }

    function getLinkHtml($pos, $linkId = null, $imgURL = null, $imgAlt = "Go", $mode = null) {
        $mode = $mode ? $mode : $this->_query['-mode'];
        if ($pos < 0)
            return '';

        $url = $this->_buildSetLink(
                array('-cursor' => $pos)
        );

        if ($mode == 'browse') {
            $title = $pos . '';
        } else if ($mode == 'list') {
            $from = $pos;
            $to = min($pos + $this->_resultSet->limit(), $this->_resultSet->found());
            $title = "Records " . ($from + 1) . " to " . ($to);
            $url = $this->_buildLink(
                    array('-skip' => $from)
            );
        }
        if ($linkId !== null) {
            $id = " id=\"$linkId\"";
        } else {
            $id = '';
        }
        if (isset($imgURL)) {
            return '<a href="' . $url . '"' . $id . ' title="' . $title . '"><img src="' . $imgURL . '" alt="' . $imgAlt . '" /></a>';
        } else {
            return '<a href="' . $url . '"' . $id . ' title="' . $title . '">' . $imgAlt . '</a>';
        }
    }

    function getPrevLinkHtml($img = null, $mode = null) {
        if (!isset($img)) {
            $img = DATAFACE_URL . '/images/go-previous.png';
        }
        $mode = $mode ? $mode : $this->_query['-mode'];

        if ($mode == 'browse') {
            if ($this->_resultSet->cursor() <= 0)
                return '';
            return $this->getLinkHtml(
                            $this->_resultSet->cursor() - 1, 'prevLink', $img, '&lt;&lt;Back', $mode);
        } else if ($mode == 'list') {
            if ($this->_resultSet->start() <= 0)
                return '';
            return $this->getLinkHtml(
                            max(
                                    $this->_resultSet->start() - $this->_resultSet->limit(), 0
                            ), 'prevLink', $img, '&lt;&lt;Back', $mode);
        } else {
            return '';
        }
    }

    function getNextLinkHtml($img = null, $mode = null) {
        if (!isset($img))
            $img = DATAFACE_URL . '/images/go-next.png';
        $mode = $mode ? $mode : $this->_query['-mode'];
        if ($mode == 'browse' && $this->_resultSet->cursor() + 1 < $this->_resultSet->found()) {
            return $this->getLinkHtml(
                            $this->_resultSet->cursor() + 1, 'nextLink', $img, 'Next&gt;&gt;', $mode
            );
        } else if ($mode == 'list' and $this->_resultSet->end() + 1 < $this->_resultSet->found()) {
            return $this->getLinkHtml(
                            $this->_resultSet->end() + 1, 'nextLink', $img, 'Next&gt;&gt;', $mode
            );
        } else {
            return '';
        }
    }

    function getCurrentHtml() {

        if ($this->_query['-mode'] == 'list') {
            return "<b>Now Showing:</b> " . $this->getLinkHtml($this->_resultSet->start(), 'currentLink');
        } else if ($this->_query['-mode'] == 'browse') {

            return "<b>This Record: </b>" . $this->getLinkHtml($this->_resultSet->cursor(), 'currentLink');
        } else {
            return '';
        }
    }

    function getPageIndexHtml() {
        if ($this->_query['-mode'] == 'list') {
            $selected = '';
            $pages = $this->getPageIndex($selected);
            ob_start();
            echo '<ul class="page-index">';
            $count = 0;
            foreach ($pages as $link => $title) {
                if ($count++ > 8) {
                    echo '<li>...</li>
   					';
                    break;
                } else if ($link == $selected) {
                    echo '<li class="selected-page">' . $title . '</li>
   					';
                } else {
                    echo '<li><a href="' . $link . '">' . $title . '</a></li>
   					';
                }
            }
            echo '</ul>';
            $out = ob_get_contents();
            ob_end_clean();
            return $out;
        }
        return '';
    }

}
