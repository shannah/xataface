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
// $Id: Var_Dump.php,v 1.1 2006/01/13 21:31:04 sjhannah Exp $

require_once 'Var_Dump/Renderer.php';

/**
 * Wrapper for the var_dump function.
 *
 * " The var_dump function displays structured information about expressions
 * that includes its type and value. Arrays are explored recursively
 * with values indented to show structure. "
 *
 * The Var_Dump class captures the output of the var_dump function,
 * by using output control functions, and then uses external renderer
 * classes for displaying the result in various graphical ways :
 * simple text, HTML/XHTML text, HTML/XHTML table, XML, ...
 *
 * @package Var_Dump
 * @category PHP
 * @author Frederic Poeydomenge <fpoeydomenge at free dot fr>
 */

define ('VAR_DUMP_START_GROUP',          1);
define ('VAR_DUMP_FINISH_GROUP',         2);
define ('VAR_DUMP_START_ELEMENT_NUM',    3);
define ('VAR_DUMP_START_ELEMENT_STR',    4);
define ('VAR_DUMP_FINISH_ELEMENT',       5);
define ('VAR_DUMP_FINISH_STRING',        6);

define ('VAR_DUMP_TYPE_ARRAY',           1);
define ('VAR_DUMP_TYPE_OBJECT',          2);

define ('VAR_DUMP_PREG_MATCH',           0);
define ('VAR_DUMP_PREG_SPACES',          1);
define ('VAR_DUMP_PREG_KEY_QUOTE',       2);
define ('VAR_DUMP_PREG_KEY',             3);
define ('VAR_DUMP_PREG_STRING_TYPE',     4);
define ('VAR_DUMP_PREG_STRING_LENGTH',   5);
define ('VAR_DUMP_PREG_STRING_VALUE',    6);
define ('VAR_DUMP_PREG_VALUE',           7);
define ('VAR_DUMP_PREG_VALUE_REFERENCE', 8);
define ('VAR_DUMP_PREG_VALUE_TYPE',      9);
define ('VAR_DUMP_PREG_VALUE_COMPL',    10);
define ('VAR_DUMP_PREG_VALUE_RESOURCE', 11);
define ('VAR_DUMP_PREG_ARRAY_END',      12);
define ('VAR_DUMP_PREG_ARRAY_START',    13);
define ('VAR_DUMP_PREG_ARRAY_TYPE',     14);
define ('VAR_DUMP_PREG_ARRAY_COUNT',    15);
define ('VAR_DUMP_PREG_STRING_COMPL',   16);

class Var_Dump
{

    /**
     * Default configuration options.
     *
     * @var array
     * @access public
     */
    var $defaultOptions = array(
        'display_mode' => 'XHTML_Text', // Display mode.
        'ignore_list'  => NULL          // List of ignored class names.
    );

    /**
     * Run-time configuration options.
     *
     * @var array
     * @access public
     */
    var $options = array();

    /**
     * Rendering object.
     *
     * @var object
     * @access public
     */
    var $renderer = NULL;

    /**
     * Rendering configuration options.
     *
     * See Var_Dump/Renderer/*.php for the complete list of options
     *
     * @var array
     * @access public
     */
    var $rendererOptions = array();

    /**
     * Class constructor.
     *
     * The factory approach must be used in relationship with the
     * toString() method.
     * See Var_Dump/Renderer/*.php for the complete list of options
     *
     * @see Var_Dump::toString()
     * @param mixed $options String (display mode) or array (Global parameters).
     * @param array $rendererOptions Parameters for the rendering.
     * @access public
     */
    function Var_Dump($options = array(), $rendererOptions = array())
    {

        if (! is_null($options)) {
            if (is_string($options)) {
                $options = array(
                    'display_mode' => $options
                );
            }
            $this->options = array_merge (
                $this->defaultOptions,
                $options
            );
        }

        if (! is_null($rendererOptions) and is_array($rendererOptions)) {
            $this->rendererOptions = $rendererOptions;
            $this->renderer = & Var_Dump_Renderer::factory(
                $this->options['display_mode'],
                $this->rendererOptions
            );
        }

    }

    /**
     * Attempt to return a concrete Var_Dump instance.
     *
     * The factory approach must be used in relationship with the
     * toString() method.
     * See Var_Dump/Renderer/*.php for the complete list of options
     *
     * @see Var_Dump::toString()
     * @param mixed $options String (display mode) or array (Global parameters).
     * @param array $rendererOptions Parameters for the rendering.
     * @access public
     */
    function & factory($options = array(), $rendererOptions = array())
    {
        $obj = & new Var_Dump($options, $rendererOptions);
        return $obj;
    }

    /**
     * Uses a renderer object to return the string representation of a variable.
     *
     * @param mixed $expression The variable to parse.
     * @return string The string representation of the variable.
     * @access public
     */
    function toString($expression)
    {

        if (is_null($this->renderer)) {
            return '';
        }

        $family = array(); // element family
        $depth  = array(); // element depth
        $type   = array(); // element type
        $value  = array(); // element value

        // When xdebug is loaded, disable the custom fancy var_dump() function,
        // that is not compatible with the regexp parsing below, by forcing
        // the "html_errors" configuration option to "off"

        if (extension_loaded('xdebug')) {
            ini_set('html_errors', '0');
        }

        // Captures the output of the var_dump function,
        // by using output control functions.

        ob_start();
        var_dump($expression);
        $variable = ob_get_contents();
        ob_end_clean();

        // When xdebug is loaded, restore the value of the
        // "html_errors" configuration option

        if (extension_loaded('xdebug')) {
            ini_restore('html_errors');
        }

        // Regexp that parses the output of the var_dump function.
        // The numbers between square brackets [] are the reference
        // of the captured subpattern, and correspond to the entries
        // in the resulting $matches array.

        preg_match_all(
            '!^
              (\s*)                                 # 2 spaces for each depth level
              (?:                                   #
                (?:\[("?)(.*?)\\2\]=>)              # Key [2-3]
                  |                                 #   or
                (?:(&?string\((\d+)\))\s+"(.*))     # String [4-6]
                  |                                 #   or
                (                                   # Value [7-11]
                  (&?)                              #   - reference [8]
                  (bool|int|float|resource|         #   - type [9]
                  NULL|\*RECURSION\*|UNKNOWN:0)     #
                  (?:\((.*?)\))?                    #   - complement [10]
                  (?:\sof\stype\s\((.*?)\))?        #   - resource [11]
                )                                   #
                  |                                 #   or
                (})                                 # End of array/object [12]
                  |                                 #   or
                (?:(&?(array|object)\((.+)\).*)\ {) # Start of array/object [13-15]
                  |                                 #   or
                (.*)                                # String (additional lines) [16]
              )                                     #
            $!Smx',
            $variable,
            $matches,
            PREG_SET_ORDER
        );

        // Used to keep the maxLen of the keys for each nested variable.

        $stackLen = array();
        $keyLen = array();
        $maxLen = 0;

        // Used when matching a string, to count the remaining
        // number of chars before the end of the string.

        $countdown = 0;

        // Loop through the matches of the previously defined regexp.

        reset($matches);
        while (list($key, $match) = each($matches)) {

            $count = count($match) - 1;

            // Find which alternative has been matched in the regexp,
            // by looking at the number of elements in the $match array.

            switch ($count) {

                // Key
                //=====
                // - Compute the maxLen of the keys at the actual depth

                case VAR_DUMP_PREG_KEY:
                    $len = strlen($match[VAR_DUMP_PREG_KEY]);
                    if ($len > $maxLen) {
                        $maxLen = $len;
                    }
                    if (empty($match[VAR_DUMP_PREG_KEY_QUOTE])) {
                        $family[] = VAR_DUMP_START_ELEMENT_NUM;
                    } else {
                        $family[] = VAR_DUMP_START_ELEMENT_STR;
                    }
                    $depth[] = strlen($match[VAR_DUMP_PREG_SPACES]) >> 1;
                    $type[]  = NULL;
                    $value[] = $match[VAR_DUMP_PREG_KEY];
                    break;

                // String
                //========
                // - Set the countdown (remaining number of chars before eol) =
                //   len of the string - matched len + 1 (final ")

                case VAR_DUMP_PREG_STRING_TYPE:
                case VAR_DUMP_PREG_STRING_LENGTH:
                case VAR_DUMP_PREG_STRING_VALUE:
                    $countdown =
                        $match[VAR_DUMP_PREG_STRING_LENGTH]
                        - strlen($match[VAR_DUMP_PREG_STRING_VALUE])
                        + 1;
                    $family[] = VAR_DUMP_FINISH_STRING;
                    $depth[] = strlen($match[VAR_DUMP_PREG_SPACES]) >> 1;
                    $type[] = $match[VAR_DUMP_PREG_STRING_TYPE];
                    if ($countdown == 0) {
                        $value[] = substr($match[VAR_DUMP_PREG_STRING_VALUE], 0, -1);
                    } else {
                        $value[] = $match[VAR_DUMP_PREG_STRING_VALUE];
                    }
                    break;

                // String (additional lines)
                //===========================
                // - Compute new countdown value
                // - Pop value off the end of the array, and concatenate new value
                // - Last additional line : remove trailing "
                // - Push new value onto the end of array

                case VAR_DUMP_PREG_STRING_COMPL:
                    if ($countdown > 0) {
                        $countdown -= strlen($match[VAR_DUMP_PREG_MATCH]) + 1;
                        $new_value =
                            array_pop($value) . "\n" .
                            $match[VAR_DUMP_PREG_STRING_COMPL];
                        if ($countdown == 0) {
                            $new_value = substr($new_value, 0, -1);
                        }
                        array_push($value, $new_value);
                    }
                    break;

                // Value
                //=======

                case VAR_DUMP_PREG_VALUE:
                case VAR_DUMP_PREG_VALUE_REFERENCE:
                case VAR_DUMP_PREG_VALUE_TYPE:
                case VAR_DUMP_PREG_VALUE_COMPL:
                case VAR_DUMP_PREG_VALUE_RESOURCE:
                    $family[] = VAR_DUMP_FINISH_ELEMENT;
                    $depth[] = strlen($match[VAR_DUMP_PREG_SPACES]) >> 1;
                    switch ($match[VAR_DUMP_PREG_VALUE_TYPE]) {
                        case 'bool':
                        case 'int':
                        case 'float':
                            $type[] =
                                $match[VAR_DUMP_PREG_VALUE_REFERENCE] .
                                $match[VAR_DUMP_PREG_VALUE_TYPE];
                            $value[] = $match[VAR_DUMP_PREG_VALUE_COMPL];
                            break;
                        case 'resource':
                            $type[] =
                                $match[VAR_DUMP_PREG_VALUE_REFERENCE] .
                                $match[VAR_DUMP_PREG_VALUE_TYPE] .
                                '(' . $match[VAR_DUMP_PREG_VALUE_RESOURCE] . ')';
                            $value[] = $match[VAR_DUMP_PREG_VALUE_COMPL];
                            break;
                        default:
                            $type[] =
                                $match[VAR_DUMP_PREG_VALUE_REFERENCE] .
                                $match[VAR_DUMP_PREG_VALUE_TYPE];
                            $value[] = NULL;
                            break;
                    }
                    break;

                // End of array/object
                //=====================
                // - Pop the maxLen of the keys off the end of the stack
                // - If the last element on the stack is an array(0) or object(0),
                //   replace it by a standard element

                case VAR_DUMP_PREG_ARRAY_END:
                    $oldLen = array_pop($stackLen);
                    $keyLen[$oldLen[0]] = $maxLen;
                    $maxLen = $oldLen[1];
                    if (
                        ($family[count($family) - 1] == VAR_DUMP_START_GROUP)
                            and
                        ($type[count($type) - 1] === 0)
                    ) {
                        $family[count($family) - 1] = VAR_DUMP_FINISH_ELEMENT;
                        $type[count($type) - 1] = $value[count($value) - 1];
                        $value[count($value) - 1] = NULL;
                    } else {
                        $family[] = VAR_DUMP_FINISH_GROUP;
                        $depth[] = strlen($match[VAR_DUMP_PREG_SPACES]) >> 1;
                        $type[] = NULL;
                        $value[] = $match[VAR_DUMP_PREG_ARRAY_END];
                                        }
                    break;

                // Start of array/object
                //=======================
                // - If object is in the "ignore_list", jump at the end of it
                // - Else process it normally :
                //   - Push the maxLen of the keys onto the end of the stack
                //   - Initialize new maxLen to 0

                case VAR_DUMP_PREG_ARRAY_START:
                case VAR_DUMP_PREG_ARRAY_TYPE:
                case VAR_DUMP_PREG_ARRAY_COUNT:
                    
                    $parse = TRUE;

                    // If object is in the "ignore_list", jump at the end of it.

                    if ($match[VAR_DUMP_PREG_ARRAY_TYPE] == 'object') {
                        $infos = $match[VAR_DUMP_PREG_ARRAY_COUNT];
                        $class_name = substr($infos, 0, strpos($infos, ')'));
                        if (
                            ! is_null($this->options['ignore_list'])
                                and
                            in_array($class_name, $this->options['ignore_list'])
                        ) {
                            $family[] = VAR_DUMP_FINISH_STRING;
                            $depth[] = strlen($match[VAR_DUMP_PREG_SPACES]) >> 1;
                            $type[] = 'object(' . $class_name . ')';
                            $value[] = 'Not parsed.';
                            while ($parse) {
                                list($dummy, $each) = each($matches);
                                if (
                                    $match[VAR_DUMP_PREG_SPACES] == $each[VAR_DUMP_PREG_SPACES]
                                        and
                                    (count($each) - 1) == VAR_DUMP_PREG_ARRAY_END
                                ) {
                                    $parse = FALSE;
                                }
                            };
                        }
                    }

                    // If not, process it normally.

                    if ($parse) {

                        array_push($stackLen, array(count($family), $maxLen));
                        $maxLen = 0;
                        $family[] = VAR_DUMP_START_GROUP;
                        $depth[] = strlen($match[VAR_DUMP_PREG_SPACES]) >> 1;
                        $type[] = (int) $match[VAR_DUMP_PREG_ARRAY_COUNT];
                        $value[] = $match[VAR_DUMP_PREG_ARRAY_START];

                    }

                    break;

            }

        }

        $this->renderer->initialize($family, $depth, $type, $value, $keyLen);

        return $this->renderer->toString();

    }

    /**
     * Attempt to return a concrete singleton Var_Dump instance.
     *
     * The singleton approach must be used in relationship with the
     * displayInit() and display() methods.
     * See Var_Dump/Renderer/*.php for the complete list of options
     *
     * @see Var_Dump::display(), Var_Dump::displayInit()
     * @return object Var_Dump instance
     * @access public
     */
    function & singleton()
    {
        static $instance;
        if (! isset($instance)) {
            $instance = new Var_Dump(array(), array(), array());
        }
        return $instance;
    }

    /**
     * Initialise the singleton object used by the display() method.
     *
     * @see Var_Dump::singleton(), Var_Dump::display()
     * @param mixed $options String (display mode) or array (Global parameters).
     * @param array $rendererOptions Parameters for the rendering.
     * @access public
     */
    function displayInit($options = array(), $rendererOptions = array())
    {
        $displayInit = & Var_Dump::singleton();
        $displayInit->Var_Dump($options, $rendererOptions);
    }

    /**
     * Outputs or returns a string representation of a variable.
     *
     * @see Var_Dump::singleton(), Var_Dump::displayInit()
     * @param mixed $expression The variable to parse.
     * @param bool $return Whether the variable should be echoed or returned.
     * @param mixed $options String (display mode) or array (Global parameters).
     * @param array $rendererOptions Parameters for the rendering.
     * @return string If returned, the string representation of the variable.
     * @access public
     */
    function display($expression, $return = FALSE, $options = NULL, $rendererOptions = NULL)
    {
        $display = & Var_Dump::singleton();
        if (! is_null($options) or ! is_null($rendererOptions)) {
            if (is_null($rendererOptions)) {
                $rendererOptions = array();
            }
            $display->Var_Dump($options, $rendererOptions);
        }
        if ($return === TRUE) {
            return $display->toString($expression);
        } else {
            echo $display->toString($expression);
        }
    }

}

?>
