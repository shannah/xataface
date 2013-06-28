<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Frederic Poeydomenge <fpoeydomenge at free dot fr>          |
// +----------------------------------------------------------------------+
//
// $Id: Table.php,v 1.1 2006/01/12 22:50:50 sjhannah Exp $

require_once 'Var_Dump/Renderer/Common.php';

/**
 * A concrete renderer for Var_Dump
 *
 * Returns a table-based representation of a variable in HTML
 *
 * @package Var_Dump
 * @category PHP
 * @author Frederic Poeydomenge <fpoeydomenge at free dot fr>
 */

class Var_Dump_Renderer_Table extends Var_Dump_Renderer_Common
{

    /**
     * Default configuration options.
     *
     * Valid configuration options are :
     *     show_caption     : bool,    Show the caption or not
     *     show_eol         : string,  String to insert before a newline, or false
     *     before_num_key   : string,  Text to insert before a numerical key
     *     after_num_key    : string,  Text to insert after a numerical key
     *     before_str_key   : string,  Text to insert before a string key
     *     after_str_key    : string,  Text to insert after a string key
     *     before_type      : string,  Text to insert before a type
     *     after_type       : string,  Text to insert after a type
     *     before_value     : string,  Text to insert before a value
     *     after_value      : string,  Text to insert after a value
     *     start_table      : string,  Text to insert before the table
     *     end_table        : string,  Text to insert after the table
     *     start_tr         : string,  Text to insert before a row
     *     end_tr           : string,  Text to insert after a row
     *     start_tr_alt     : string,  Text to insert after an alternate row
     *     end_tr_alt       : string,  Text to insert after an alternate row
     *     start_td_key     : string,  Text to insert before the key cell
     *     end_td_key       : string,  Text to insert after the key cell
     *     start_td_type    : string,  Text to insert before the type cell
     *     end_td_type      : string,  Text to insert after the type cell
     *     start_td_value   : string,  Text to insert before the value cell
     *     end_td_value     : string,  Text to insert after the value cell
     *     start_td_colspan : string,  Text to insert before a group cell
     *     end_td_colspan   : string,  Text to insert after a group cell
     *     start_caption    : string,  Text to insert before the caption
     *     end_caption      : string,  Text to insert after the caption
     *
     * @var array
     * @access public
     */
    var $defaultOptions = array(
        'show_caption'     => TRUE,
        'show_eol'         => FALSE,
        'before_num_key'   => '',
        'after_num_key'    => '',
        'before_str_key'   => '',
        'after_str_key'    => '',
        'before_type'      => '',
        'after_type'       => '',
        'before_value'     => '',
        'after_value'      => '',
        'start_table'      => '<table>',
        'end_table'        => '</table>',
        'start_tr'         => '<tr>',
        'end_tr'           => '</tr>',
        'start_tr_alt'     => '<tr>',
        'end_tr_alt'       => '</tr>',
        'start_td_key'     => '<td>',
        'end_td_key'       => '</td>',
        'start_td_type'    => '<td>',
        'end_td_type'      => '</td>',
        'start_td_value'   => '<td>',
        'end_td_value'     => '</td>',
        'start_td_colspan' => '<td colspan="2">',
        'end_td_colspan'   => '</td>',
        'start_caption'    => '<caption>',
        'end_caption'      => '</caption>'
    );

    /**
     * Class constructor.
     *
     * @param array $options Parameters for the rendering.
     * @access public
     */
    function Var_Dump_Renderer_Table($options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Returns the string representation of a variable
     *
     * @return string The string representation of the variable.
     * @access public
     */
    function toString()
    {
        if (count($this->family) == 1) {
            return $this->_toString_Single();
        } else {
            return $this->_toString_Array();
        }
    }

    /**
     * Returns the string representation of a single variable
     *
     * @return string The string representation of a single variable.
     * @access private
     */
    function _toString_Single()
    {
        $string = df_escape($this->value[0]);
        if ($this->options['show_eol'] !== FALSE) {
            $string = str_replace(
                "\n",
                $this->options['show_eol'] . "\n",
                $string
            );
        }
        return
            $this->options['start_table'] .
            $this->options['start_tr'] .
            $this->options['start_td_type'] .
            $this->options['before_type'] .
            df_escape($this->type[0]) .
            $this->options['after_type'] .
            $this->options['end_td_type'] .
            $this->options['start_td_value'] .
            $this->options['before_value'] .
            nl2br($string) .
            $this->options['after_value'] .
            $this->options['end_td_value'] .
            $this->options['end_tr'] .
            $this->options['end_table'];
    }

    /**
     * Returns the string representation of a multiple variable
     *
     * @return string The string representation of a multiple variable.
     * @access private
     */
    function _toString_Array()
    {
        $txt = '';
        $stack = array(0);
        $counter = count($this->family);
        for ($c = 0 ; $c < $counter ; $c++) {
            switch ($this->family[$c]) {
                case VAR_DUMP_START_GROUP :
                    array_push($stack, 0);
                    if ($this->depth[$c] > 0) {
                        $txt .= $this->options['start_td_colspan'];
                    }
                    $txt .= $this->options['start_table'];
                    if ($this->options['show_caption']) {
                        $txt .=
                            $this->options['start_caption'] .
                            df_escape($this->value[$c]) .
                            $this->options['end_caption'];
                    }
                    break;
                case VAR_DUMP_FINISH_GROUP :
                    array_pop($stack);
                    $txt .= $this->options['end_table'];
                    if ($this->depth[$c] > 0) {
                        $txt .=
                            $this->options['end_td_colspan'] .
                            $this->options['end_tr'];
                    }
                    break;
                case VAR_DUMP_START_ELEMENT_NUM :
                case VAR_DUMP_START_ELEMENT_STR :
                    array_push($stack, 1 - array_pop($stack));
                    $tr = (end($stack) == 1) ? 'start_tr' : 'start_tr_alt';
                    $comp = ($this->family[$c] == VAR_DUMP_START_ELEMENT_NUM) ? 'num' : 'str';
                    $txt .=
                        $this->options[$tr] .
                        $this->options['start_td_key'] .
                        $this->options['before_'.$comp.'_key'] .
                        df_escape($this->value[$c]) .
                        $this->options['after_'.$comp.'_key'] .
                        $this->options['end_td_key'];
                    break;
                case VAR_DUMP_FINISH_ELEMENT :
                case VAR_DUMP_FINISH_STRING :
                    $etr = (end($stack) == 1) ? 'end_tr' : 'end_tr_alt';
                    if (!is_null($this->value[$c])) {
                        $string = df_escape($this->value[$c]);
                        if ($this->options['show_eol'] !== FALSE) {
                            $string = str_replace(
                                "\n",
                                $this->options['show_eol'] . "\n",
                                $string
                            );
                        }
                        $txt .=
                            $this->options['start_td_type'] .
                            $this->options['before_type'] .
                            df_escape($this->type[$c]) .
                            $this->options['after_type'] .
                            $this->options['end_td_type'] .
                            $this->options['start_td_value'] .
                            $this->options['before_value'] .
                            nl2br($string) .
                            $this->options['after_value'] .
                            $this->options['end_td_value'] .
                            $this->options[$etr];
                    } else {
                        $txt .=
                            $this->options['start_td_colspan'] .
                            $this->options['before_type'] .
                            df_escape($this->type[$c]) .
                            $this->options['after_type'] .
                            $this->options['end_td_colspan'] .
                            $this->options[$etr];
                    }
                    break;
            }
        }
        return $txt;
    }

}

?>
