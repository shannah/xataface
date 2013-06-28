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
// $Id: HTML4_Table.php,v 1.1 2006/01/12 22:50:50 sjhannah Exp $

require_once 'Var_Dump/Renderer/Table.php';

/**
 * A concrete renderer for Var_Dump
 *
 * Returns a table-based representation of a variable in HTML 4
 * Extends the 'Table' renderer, with just a predefined set of options,
 * that are empty by default. You can also directly call the 'Table' renderer
 * with the corresponding configuration options.
 *
 * @package Var_Dump
 * @category PHP
 * @author Frederic Poeydomenge <fpoeydomenge at free dot fr>
 */

class Var_Dump_Renderer_HTML4_Table extends Var_Dump_Renderer_Table
{

    /**
     * Class constructor.
     *
     * @param array $options Parameters for the rendering.
     * @access public
     */
    function Var_Dump_Renderer_HTML4_Table($options = array())
    {
        // See Var_Dump/Renderer/Table.php for the complete list of options
        $this->defaultOptions = array_merge(
            $this->defaultOptions,
            array(
                'before_num_key' => '<b>',
                'after_num_key'  => '</b>',
                'before_str_key' => '<b>',
                'after_str_key'  => '</b>',
                'before_type'    => '<font color="#000000">',
                'after_type'     => '</font>',
                'before_value'   => '<font color="#006600">',
                'after_value'    => '</font>',
                'start_table'    =>
                    '<table border="0" cellpadding="1" cellspacing="0"' .
                    ' bgcolor="black"><tr><td>' .
                    '<table border="0" cellpadding="4" cellspacing="0"' .
                    ' width="100%">',
                'end_table'      => '</table></td></tr></table>',
                'start_tr'       => '<tr valign="top" bgcolor="#F8F8F8">',
                'start_tr_alt'   => '<tr valign="top" bgcolor="#E8E8E8">',
                'start_td_key'   => '<td bgcolor="#CCCCCC">',
                'start_caption'  => '<caption style="color:white;background:#339900;">'
            )
        );
        $this->setOptions($options);
    }

}

?>
