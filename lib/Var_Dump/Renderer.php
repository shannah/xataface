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
// $Id: Renderer.php,v 1.1 2006/01/12 22:50:50 sjhannah Exp $

require_once 'Var_Dump/Renderer/Common.php';

/**
 * A loader class for the renderers.
 *
 * @package Var_Dump
 * @category PHP
 * @author Frederic Poeydomenge <fpoeydomenge at free dot fr>
 */

class Var_Dump_Renderer
{

    /**
     * Attempt to return a concrete Var_Dump_Renderer instance.
     *
     * @param string $mode Name of the renderer.
     * @param array $options Parameters for the rendering.
     * @access public
     */
    function & factory($mode, $options)
    {
        @include_once 'Var_Dump/Renderer/' . $mode . '.php';
        $className = 'Var_Dump_Renderer_' . $mode;
        if (class_exists($className)) {
            $obj = & new $className($options);
        } else {
            include_once 'PEAR.php';
            PEAR::raiseError('Var_Dump: renderer "' . $mode . '" not found', TRUE);
            return NULL;
        }
        return $obj;
    }

}

?>
