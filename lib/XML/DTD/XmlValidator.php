<?php
//
// +----------------------------------------------------------------------+
// | DTD_XML_Validator class                                              |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 Tomas Von Veschler Cox                            |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.02 of the PHP license,      |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors:    Tomas V.V.Cox <cox@idecnet.com>                          |
// |                                                                      |
// +----------------------------------------------------------------------+
//
// $Id: XmlValidator.php,v 1.1.1.1 2005/11/29 19:21:56 sjhannah Exp $
//
//
// TODO:
//   - Give better error messages :-)
//   - Implement error codes and better error reporting
//   - Add support for //XXX Missing .. (you may find them arround the code)
//

require_once 'XML/DTD.php';
require_once 'XML/Tree.php';

/**
 * XML_DTD_XmlValidator
 * 
 * Usage:
 * 
 * <code>
 * $validator = XML_DTD_XmlValidator;
 * // This will check if the xml is well formed
 * // and will validate it against its DTD
 * if (!$validator->isValid($dtd_file, $xml_file)) {
 *   die($validator->getMessage());
 * }
 * </code>
 * 
 * @package XML_DTD
 * @category XML
 * @author Tomas V.V.Cox <cox@idecnet.com> 
 * @copyright Copyright (c) 2003
 * @version $Id: XmlValidator.php,v 1.1.1.1 2005/11/29 19:21:56 sjhannah Exp $
 * @access public 
*/
class XML_DTD_XmlValidator
{

    var $dtd = array();
    var $_errors = false;

    /**
     * XML_DTD_XmlValidator::isValid()
     *
     * Checks an XML file against its DTD
     *  
     * @param string $dtd_file The DTD file name
     * @param string $xml_file The XML file
     * @return bool True if the XML conforms the definition
     **/
    function isValid($dtd_file, $xml_file)
    {
        $xml_tree =& new XML_Tree($xml_file);
        $nodes = $xml_tree->getTreeFromFile();
        if (PEAR::isError($nodes)) {
            $this->_errors($nodes->getMessage());
            return false;
        }
        $dtd_parser =& new XML_DTD_Parser;
        $this->dtd = @$dtd_parser->parse($dtd_file);
        $this->_runTree($nodes);
        return ($this->_errors) ? false : true;
    }

    /**
     * XML_DTD_XmlValidator::_runTree()
     * 
     * Runs recursively over the XML_Tree tree of objects
     * validating each of its nodes
     * 
     * @param object $node an XML_Tree_Node type object
     * @return null
     * @access private
     **/
    function _runTree(&$node)
    {
        //echo "Parsing node: $node->name\n";
        $children = array();
        $lines    = array();

        // Get the list of children under the parent node
        foreach ($node->children as $child) {
            // a text node
            if (!strlen($child->name)) {
                $children[] = '#PCDATA';
            } else {
                $children[] = $child->name;
            }
            $lines[]    = $child->lineno;
        }

        $this->_validateNode($node, $children, $lines);
        // Recursively run the tree
        foreach ($node->children as $child) {
            if (strlen($child->name)) {
                $this->_runTree($child);
            }
        }
    }

    /**
     * XML_DTD_XmlValidator::_validateNode()
     * 
     * Validate a XML_Tree_Node: allowed childs, allowed content
     * and allowed attributes
     * 
     * @param object $node an XML_Tree_Node type object
     * @param array  $children the list of children
     * @param array  $linenos  linenumbers of the children
     * @return null
     * @access private
     **/
    function _validateNode($node, $children, $linenos)
    {
        $name = $node->name;
        $lineno = $node->lineno;
        if (!$this->dtd->elementIsDeclared($name)) {
            $this->_errors("No declaration for tag <$name> in DTD", $lineno);
            // We don't run over the childs of undeclared elements
            // contrary of what xmllint does
            return;
        }

        //
        // Children validation
        //
        $dtd_children = $this->dtd->getChildren($name);
        do {
            // There are children when no children allowed
            if (count($children) && !count($dtd_children)) {
                $this->_errors("No children allowed under <$name>", $lineno);
                break;
            }
            // Search for children names not allowed
            $was_error = false;
            $i = 0;
            foreach ($children as $child) {
                if (!in_array($child, $dtd_children)) {
                    $this->_errors("<$child> not allowed under <$name>", $linenos[$i]);
                    $was_error = true;
                }
                $i++;
            }
            // Validate the order of the children
            if (!$was_error && count($dtd_children)) {
                $children_list = implode(',', $children);
                $regex = $this->dtd->getPcreRegex($name);
                if (!preg_match('/^'.$regex.'$/', $children_list)) {
                    $dtd_regex = $this->dtd->getDTDRegex($name);
                    $this->_errors("In element <$name> the children list found:\n'$children_list', ".
                                   "does not conform the DTD definition: '$dtd_regex'", $lineno);
                }
            }
        } while (false);

        //
        // Content Validation
        //
        $node_content = $node->content;
        $dtd_content  = $this->dtd->getContent($name);
        if (strlen($node_content)) {
            if ($dtd_content == null) {
                $this->_errors("No content allowed for tag <$name>", $lineno);
            } elseif ($dtd_content == 'EMPTY') {
                $this->_errors("No content allowed for tag <$name />, declared as 'EMPTY'", $lineno);
            }
        }
        // XXX Missing validate #PCDATA or ANY

        //
        // Attributes validation
        //
        $atts = $this->dtd->getAttributes($name);
        $node_atts = $node->attributes;
        foreach ($atts as $attname => $attvalue) {
            $opts    = $attvalue['opts'];
            $default = $attvalue['defaults'];
            if ($default == '#REQUIRED' && !isset($node_atts[$attname])) {
                $this->_errors("Missing required '$attname' attribute in <$name>", $lineno);
            }
            if ($default == '#FIXED') {
                if (isset($node_atts[$attname]) && $node_atts[$attname] != $attvalue['fixed_value']) {
                    $this->_errors("The value '{$node_atts[$attname]}' for attribute '$attname' ".
                                   "in <$name> can only be '{$attvalue['fixed_value']}'", $lineno);
                }
            }
            if (isset($node_atts[$attname])) {
                $node_val = $node_atts[$attname];
                // Enumerated type validation
                if (is_array($opts)) {
                    if (!in_array($node_val, $opts)) {
                        $this->_errors("'$node_val' value for attribute '$attname' under <$name> ".
                                       "can only be: '". implode(', ', $opts) . "'", $lineno);
                    }
                }
                unset($node_atts[$attname]);
            }
        }
        // XXX Missing NMTOKEN, ID

        // If there are still attributes those are not declared in DTD
        if (count($node_atts) > 0) {
            $this->_errors("The attributes: '" . implode(', ', array_keys($node_atts)) .
                           "' are not declared in DTD for tag <$name>", $lineno);
        }
    }

    /**
     * XML_DTD_XmlValidator::_errors()
     * 
     * Stores errors
     * 
     * @param string  $str     the error message to append
     * @param integer $lineno  the line number where the tag is declared
     * @return null
     * @access private
     **/
    function _errors($str, $lineno = null)
    {
        if (is_null($lineno)) {
            $this->_errors .= "$str\n";
        } else {
            $this->_errors .= "line $lineno: $str\n";
        }
    }

    /**
     * XML_DTD_XmlValidator::getMessage()
     *
     * Gets all the errors the validator found in the
     * conformity of the xml document
     *  
     * @return string the error message 
     **/
    function getMessage()
    {
        return $this->_errors;
    }

}
?>
