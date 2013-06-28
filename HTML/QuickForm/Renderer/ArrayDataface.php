<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | PHP version 4.0                                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2003 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Alexey Borzov <borz_off@cs.msu.su>                          |
// |          Adam Daniel <adaniel1@eesus.jnj.com>                        |
// |          Bertrand Mansion <bmansion@mamasam.com>                     |
// |          Thomas Schulz <ths@4bconsult.de>                            |
// +----------------------------------------------------------------------+
//
// $Id: Array.php,v 1.1.1.1 2005/11/29 19:21:56 sjhannah Exp $

require_once 'HTML/QuickForm/Renderer/Array.php';

/**
 * A concrete renderer for HTML_QuickForm, makes an array of form contents
 *
 * Based on old toArray() code.
 *
 * The form array structure is the following:
 * array(
 *   'frozen'           => 'whether the form is frozen',
 *   'javascript'       => 'javascript for client-side validation',
 *   'attributes'       => 'attributes for <form> tag',
 *   'requirednote      => 'note about the required elements',
 *   // if we set the option to collect hidden elements
 *   'hidden'           => 'collected html of all hidden elements',
 *   // if there were some validation errors:
 *   'errors' => array(
 *     '1st element name' => 'Error for the 1st element',
 *     ...
 *     'nth element name' => 'Error for the nth element'
 *   ),
 *   // if there are no headers in the form:
 *   'elements' => array(
 *     element_1,
 *     ...
 *     element_N
 *   )
 *   // if there are headers in the form:
 *   'sections' => array(
 *     array(
 *       'header'   => 'Header text for the first header',
 *       'name'     => 'Header name for the first header',
 *       'elements' => array(
 *          element_1,
 *          ...
 *          element_K1
 *       )
 *     ),
 *     ...
 *     array(
 *       'header'   => 'Header text for the Mth header',
 *       'name'     => 'Header name for the Mth header',
 *       'elements' => array(
 *          element_1,
 *          ...
 *          element_KM
 *       )
 *     )
 *   )
 * );
 *
 * where element_i is an array of the form:
 * array(
 *   'name'      => 'element name',
 *   'value'     => 'element value',
 *   'type'      => 'type of the element',
 *   'frozen'    => 'whether element is frozen',
 *   'label'     => 'label for the element',
 *   'required'  => 'whether element is required',
 *   'error'     => 'error associated with the element',
 *   'style'     => 'some information about element style (e.g. for Smarty)',
 *   // if element is not a group
 *   'html'      => 'HTML for the element'
 *   // if element is a group
 *   'separator' => 'separator for group elements',
 *   'elements'  => array(
 *     element_1,
 *     ...
 *     element_N
 *   )
 * );
 *
 * @access public
 */
class HTML_QuickForm_Renderer_ArrayDataface extends HTML_QuickForm_Renderer_Array
{

   /**
    * Constructor
    *
    * @param  bool    true: collect all hidden elements into string; false: process them as usual form elements
    * @param  bool    true: render an array of labels to many labels, $key 0 to 'label' and the oterh to "label_$key"
    * @access public
    */
    function HTML_QuickForm_Renderer_ArrayDataface($collectHidden = false, $staticLabels = false, $assoc = true)
    {
    	$this->HTML_QuickForm_Renderer_Array($collectHidden, $staticLabels, $assoc);
        
    } // end constructor


	function startForm(&$form){
	
		parent::startForm($form);
		foreach ( $form->_errors as $key=>$val){
			if ( is_int($key) ){
				$this->_ary['errors'][] = $val;
			}
		}
		if ( method_exists($form, 'getSubmitLabel') ){
			$this->_ary['submit_label'] = $form->getSubmitLabel();
		}
	}

   /**
    * Creates an array representing an element
    *
    * @access private
    * @param  object    An HTML_QuickForm_element object
    * @param  bool      Whether an element is required
    * @param  string    Error associated with the element
    * @return array
    */
    function _elementToArray(&$element, $required, $error)
    {
  		
  		$ret = array(
            'name'      => $element->getName(),
            'value'     => $element->getValue(),
            'type'      => $element->getType(),
            'frozen'    => $element->isFrozen(),
            'required'  => $required,
            'error'     => $error,
            'field'		=> $element->getFieldDef(),
            'properties'=> $element->getProperties()
        );
        //if ( !$ret['name']) {var_dump($element);}
        // render label(s)
        $labels = $element->getLabel();
        if (is_array($labels) && $this->_staticLabels) {
            foreach($labels as $key => $label) {
                $key = is_int($key)? $key + 1: $key;
                if (1 === $key) {
                    $ret['label'] = $label;
                } else {
                    $ret['label_' . $key] = $label;
                }
            }
        } else {
            $ret['label'] = $labels;
        }

        // set the style for the element
        
        if (isset($this->_elementStyles[$ret['name']])) {
            $ret['style'] = $this->_elementStyles[$ret['name']];
        }
        if ('group' == $ret['type']) {
            $ret['separator'] = $element->_separator;
            $ret['elements']  = array();
        } else {
            $ret['html']      = $element->toHtml();
        }
        
        if (!empty($error)) {
            $this->_ary['errors'][$ret['name']] = $error;
        }
        return $ret;
    }
    
    
     function renderHeader(&$header)
    {
        $this->_ary['sections'][$this->_sectionCount] = array(
            'header' => $header->toHtml(),
            'name'   => $header->getName(),
            'field' => $header->getFieldDef()
        );
        $this->_currentSection = $this->_sectionCount++;
    } // end func renderHeader
    
    


}
