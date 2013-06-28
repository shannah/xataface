<?php
/**
 *  File: HTML/QuickForm/Renderer/Dataface.php
 *  Author: Steve Hannah <shannah@sfu.ca>
 *  Created: Oct. 16, 2005
 *  
 *  Description:
 *  -------------
 *A QuickForm renderer for use with Dataface_QuickForm.  This extends the Default
 * renderer with ability to include field descriptions and labels on grouped elements.
 */
 
require_once 'Dataface/SkinTool.php';
//require_once 'Dataface/Table.php';
//require_once 'Dataface/TableTool.php';
//require_once 'Dataface/Record.php';
require_once 'HTML/QuickForm/Renderer/Default.php';

define('DATAFACE_RENDERER_FIELD_NOT_FOUND',2005);

class HTML_QuickForm_Renderer_Dataface extends HTML_QuickForm_Renderer_Default {
	var $_skinTool;
	var $elementTemplate = "Dataface_QuickForm_element.html";
	var $groupElementTemplate = "Dataface_Quickform_groupelement.html";

	/**
	 * Maps element names to field names.
	 */
	var $_fieldMap = array();
	
	
	function HTML_QuickForm_Renderer_Dataface(&$form){
		$this->_skinTool =& Dataface_SkinTool::getInstance();
		parent::HTML_QuickForm_Renderer_Default();
		$this->setRequiredNoteTemplate('');
	}
	
	function addField($elementname, $fieldname){
		$this->_fieldMap[$elementname] = $fieldname;
	}
	
	function renderElement(&$element, $required, $error)
    {
    	$context = array( "field"=>$element->getFieldDef(), "element"=>$element->toHtml(), "required"=>$required, "error"=>$error, "frozen"=>$element->isFrozen());
    	$context['properties'] =& $element->getProperties();

    	
    	
    	
    	
    	
    	if ( !$this->_inGroup ){
    		ob_start();
    		$this->_skinTool->display($context, $this->elementTemplate);
    		$html = ob_get_contents();
    		ob_end_clean();
    		$this->_html .= $html;
    	} else {
    		
    		ob_start();
    		$this->_skinTool->display($context, $this->groupElementTemplate);
    		$html = ob_get_contents();
    		ob_end_clean();
    		$this->_groupElements[] =& $html;
    	}
    } // end func renderElement
    
     function startGroup(&$group, $required, $error)
    {
        $name = $group->getName();
        if ( isset( $this->_groupWraps[$name] ) )
        	$this->_groupTemplate        = $this->_groupWraps[$name] ; // $this->_prepareTemplate($name, $group->getLabel(), $required, $error);
        else 
        	$this->_groupTemplate		= '';
        $this->_groupElementTemplate = empty($this->_groupTemplates[$name])? '': $this->_groupTemplates[$name];
        $this->_groupWrap            = empty($this->_groupWraps[$name])? '': $this->_groupWraps[$name];
        $this->_groupElements        = array();
        $this->_inGroup              = true;
     
    } // end func startGroup
    
    function finishGroup(&$group)
    {
        $separator = $group->_separator;
        if (is_array($separator)) {
            $count = count($separator);
            $html  = '';
            for ($i = 0; $i < count($this->_groupElements); $i++) {
                $html .= (0 == $i? '': $separator[($i - 1) % $count]) . $this->_groupElements[$i];
            }
        } else {
            if (is_null($separator)) {
                //$separator = '&nbsp;';
                $separator='';
            }
            
            $html = implode((string)$separator, $this->_groupElements);
        }
        if (!empty($this->_groupWrap)) {
            $html = str_replace('{content}', $html, $this->_groupWrap);
        }
        //echo "<h1>html</h1>".$html;
        $this->_html   .= $html; //str_replace('{element}', $html, $this->_groupTemplate);
        $this->_inGroup = false;
    } // end func finishGroup

}


