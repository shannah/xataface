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
// $Id: HTML4_Text.php,v 1.1 2006/01/12 22:50:50 sjhannah Exp $

require_once 'Var_Dump/Renderer/Text.php';

/**
 * A concrete renderer for Var_Dump
 *
 * Returns a text representation of a variable in HTML 4
 * Extends the 'Text' renderer, with just a predefined set of options,
 * that are empty by default. You can also directly call the 'Text' renderer
 * with the corresponding configuration options.
 *
 * @package Var_Dump
 * @category PHP
 * @author Frederic Poeydomenge <fpoeydomenge at free dot fr>
 */

class Var_Dump_Renderer_HTML4_Text extends Var_Dump_Renderer_Text
{

    /**
     * Class constructor.
     *
     * @param array $options Parameters for the rendering.
     * @access public
     */
    function Var_Dump_Renderer_HTML4_Text($options = array())
    {
        // See Var_Dump/Renderer/Text.php for the complete list of options
        $this->defaultOptions = array_merge(
            $this->defaultOptions,
            array(
                'is_html'      => TRUE,
                'before_text'  => '<pre>',
                'after_text'   => '</pre>',
                'before_type'  => '<font color="#006600">',
                'after_type'   => '</font>',
                'before_value' => '<font color="#339900">',
                'after_value'  => '</font>'
            )
        );
        $this->setOptions($options);
    }

}

?>
