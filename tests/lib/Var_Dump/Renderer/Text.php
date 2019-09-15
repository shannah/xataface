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
// $Id: Text.php,v 1.1 2006/01/12 22:50:50 sjhannah Exp $

require_once 'Var_Dump/Renderer/Common.php';

/**
 * A concrete renderer for Var_Dump
 *
 * Returns a text-only representation of a variable
 *
 * @package Var_Dump
 * @category PHP
 * @author Frederic Poeydomenge <fpoeydomenge at free dot fr>
 */

class Var_Dump_Renderer_Text extends Var_Dump_Renderer_Common
{

    /**
     * Default configuration options.
     *
     * Valid configuration options are :
     *     show_container  : bool,    Show the root Element or not
     *     show_eol        : string,  String to insert before a newline, or false
     *     mode            : string,  Can be one of the following displaying modes
     *       'compact' = no keys alignment
     *       'normal'  = keys alignment, proportional spacing
     *       'wide'    = keys alignment, wider spacing
     *     offset          : integer, Offset between the start of a group and the content
     *     opening         : string,  Opening character
     *     closing         : string,  Closing character
     *     operator        : string,  Operator symbol
     *     is_html         : bool,    Do we need to df_escape() the texts
     *     before_text     : string,  Text to insert before the text
     *     after_text      : string,  Text to insert after the text
     *     before_num_key  : string,  Text to insert before a numerical key
     *     after_num_key   : string,  Text to insert after a numerical key
     *     before_str_key  : string,  Text to insert before a string key
     *     after_str_key   : string,  Text to insert after a string key
     *     before_operator : string,  Text to insert before the operator
     *     after_operator  : string,  Text to insert after the operator
     *     before_type     : string,  Text to insert before a type
     *     after_type      : string,  Text to insert after a type
     *     before_value    : string,  Text to insert before a value
     *     after_value     : string,  Text to insert after a value
     *
     * @var    array
     * @access public
     */
    var $defaultOptions = array(
        'show_container'  => TRUE,
        'show_eol'        => FALSE,
        'mode'            => 'compact',
        'offset'          => 2,
        'opening'         => '{',
        'closing'         => '}',
        'operator'        => ' => ',
        'is_html'         => FALSE,
        'before_text'     => '',
        'after_text'      => '',
        'before_num_key'  => '',
        'after_num_key'   => '',
        'before_str_key'  => '',
        'after_str_key'   => '',
        'before_operator' => '',
        'after_operator'  => '',
        'before_type'     => '',
        'after_type'      => '',
        'before_value'    => '',
        'after_value'     => ''
    );

    /**
     * Class constructor.
     *
     * @param array $options Parameters for the rendering.
     * @access public
     */
    function Var_Dump_Renderer_Text($options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Returns the string representation of a variable.
     *
     * @return string The string representation of the variable.
     * @access public
     */
    function toString()
    {
        $parent = array();
        $stackOffset = array(0);
        $offset = 0;
        $txt = $this->options['before_text'];
        $counter = count($this->family);
        for ($c = 0 ; $c < $counter ; $c++) {
            switch ($this->family[$c]) {
                case VAR_DUMP_START_GROUP :
                    if (! empty($parent)) {
                        $offset = end($stackOffset)
                            + $this->keyLen[end($parent)]
                            + $this->_len($this->options['operator']);
                        array_push($stackOffset, $offset);
                    }
                    array_push($parent, $c);
                    if ($this->options['show_container'] or $this->depth[$c] > 0) {
                        $txt .= $this->value[$c] . ' ' . $this->options['opening'] . "\n";
                    }
                    break;
                case VAR_DUMP_FINISH_GROUP :
                    if ($this->depth[$c] > 0) {
                        $offset = $this->depth[$c] * $this->options['offset'];
                        if ($this->options['mode'] == 'wide') {
                            $offset += end($stackOffset);
                        }
                        if (!$this->options['show_container']) {
                            $offset -= $this->options['offset'];
                        }
                        $txt .= str_repeat(' ', $offset);
                    }
                    if ($this->options['show_container'] or $this->depth[$c] > 0) {
                        $txt .= $this->options['closing'] . "\n";
                    }
                    array_pop($parent);
                    array_pop($stackOffset);
                    break;
                case VAR_DUMP_START_ELEMENT_NUM :
                case VAR_DUMP_START_ELEMENT_STR :
                    if ($this->depth[$c] > 0) {
                        $offset = $this->depth[$c] * $this->options['offset'];
                        if ($this->options['mode'] == 'wide') {
                            $offset += end($stackOffset);
                        }
                        if (! $this->options['show_container']) {
                            $offset -= $this->options['offset'];
                        }
                        $txt .= str_repeat(' ', $offset);
                    }
                    if ($this->options['mode'] == 'compact') {
                        $txt .= $this->_getStartElement($c);
                        $offset += $this->_len($this->value[$c]);
                    } else {
                        $txt .= sprintf(
                            '%-' . $this->keyLen[end($parent)] . 's',
                            $this->_getStartElement($c)
                        );
                        $offset += $this->keyLen[end($parent)];
                    }
                    $txt .= $this->_getOperator();
                    if ($this->family[$c]==VAR_DUMP_START_ELEMENT_NUM) {
                        $offset +=
                            $this->_len($this->options['before_num_key']) +
                            $this->_len($this->options['after_num_key']);
                    }
                    if ($this->family[$c]==VAR_DUMP_START_ELEMENT_STR) {
                        $offset +=
                            $this->_len($this->options['before_str_key']) +
                            $this->_len($this->options['after_str_key']);
                    }
                    $offset +=
                        $this->_len($this->options['before_operator']) +
                        $this->_len($this->options['operator']) +
                        $this->_len($this->options['after_operator']) +
                        $this->_len($this->options['before_type']) +
                        $this->_len($this->options['after_type']);
                    break;
                case VAR_DUMP_FINISH_ELEMENT :
                    $txt .= $this->_getFinishElement($c) . "\n";
                    break;
                case VAR_DUMP_FINISH_STRING :
                    // offset is the value set during the previous pass
                    // in VAR_DUMP_START_ELEMENT_*
                    $txt .= preg_replace(
                        '/(?<=\n)^/m',
                        $this->options['after_value'] .
                            str_repeat(' ', $offset + $this->_len($this->type[$c]) + 1) .
                            $this->options['before_value'],
                        $this->_getFinishElement($c)
                    ) . "\n";
                    break;
            }
        }
        $txt .= $this->options['after_text'];
        return rtrim($txt);
    }

    /**
     * Returns the lenght of the shift (string without tags).
     *
     * @param string $string The string.
     * @return integer Length of the shift.
     * @access private
     */
    function _len($string)
    {
        if ($this->options['is_html']) {
            return strlen(strip_tags($string));
        } else {
            return strlen($string);
        }
    }

    /**
     * Returns the operator symbol.
     *
     * @return string The operator symbol.
     * @access private
     */
    function _getOperator()
    {
        $txt = $this->options['before_operator'];
        if ($this->options['is_html']) {
            $txt .= df_escape($this->options['operator']);
        } else {
            $txt .= $this->options['operator'];
        }
        $txt .= $this->options['after_operator'];
        return $txt;
    }

    /**
     * Returns the key of the element.
     *
     * @param integer $c Index of the element.
     * @return string The key of the element.
     * @access private
     */
    function _getStartElement($c)
    {
        $comp = ($this->family[$c] == VAR_DUMP_START_ELEMENT_NUM) ? 'num' : 'str';
        $txt = $this->options['before_' . $comp . '_key'];
        if ($this->options['is_html']) {
            $txt .= df_escape($this->value[$c]);
        } else {
            $txt .= $this->value[$c];
        }
        $txt .= $this->options['after_' . $comp . '_key'];
        return $txt;
    }

    /**
     * Returns the value of the element.
     *
     * @param integer $c Index of the element.
     * @return string The value of the element.
     * @access private
     */
    function _getFinishElement($c)
    {
        $txt = $this->options['before_type'];
        if ($this->options['is_html']) {
            $txt .= df_escape($this->type[$c]);
        } else {
            $txt .= $this->type[$c];
        }
        $txt .= $this->options['after_type'];
        if (! is_null($this->value[$c])) {
            $txt .= ' ' . $this->options['before_value'];
            if ($this->options['is_html']) {
                $string = df_escape($this->value[$c]);
            } else {
                $string = $this->value[$c];
            }
            if ($this->options['show_eol'] !== FALSE) {
                $string = str_replace(
                    "\n",
                    $this->options['show_eol'] . "\n",
                    $string
                );
            }
            $txt .= $string . $this->options['after_value'];
        }
        return $txt;
    }

}

?>
