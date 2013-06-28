<?php
/*******************************************************************************
 * File:	HTML/QuickForm/htmlarea.php
 * Author:	Steve Hannah <shannah@sfu.ca>
 * Created: September 1, 2005
 * Description:
 * 	HMTL Quickform widget to edit HTML.  Uses the FCKEditor
 ******************************************************************************/
require_once 'HTML/QuickForm/element.php';	
	
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['optionalelement'] = array('HTML/QuickForm/optionalelement.php','HTML_QuickForm_optionalelement');


/**
 * HTML class for a textarea type field
 * 
 * @author       Steve Hannah <shannah@sfu.ca>
 * @version      0.1.0
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_optionalelement extends HTML_QuickForm_element {

	/**
	 * The fields that there are to choose from to add to the form.
	 */
	var $fields = array();
	
	/**
	 * The name of this field.
	var $name = '';
	
	/**
	 * Indicates whether fields created with this element should be placed in a subgroup or placed
	 * directly onto the form at root level.
	 */
	var $subgroup = false;
	
	/**
	 * The value of this field.
	 */
	var $value;
	
	
	function HTML_QuickForm_optionalelement($elementName=null, $elementLabel=null, $attributes=null)
    {
        $this->HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->name = $elementName;
        
        $this->_type = 'optionalelement';
       
    } //end constructor
    
    
    function addField(&$element){
    	$this->fields[] =& $element;
    	$element->setName($this->getName().'['.$element->getName().']');
    }
    
    
    function setName($name){
    	$this->name = $name;
    
    }
    
    function getName(){
    	return $this->name;
    }
    
    
    function setValue($value){
    	//echo "Setting: ";print_r($value);
    	if ( is_array($value) ){
			$this->value = array();
			foreach (array_keys($value) as $key){
			
				if ($key === 'prototype') continue;
				$this->value[] = $value[$key];
			}
		} else {
			$this->value = array();
			foreach ($this->fields as $col){
				$this->value[$col->getName()] = '';
			}
		}
		//echo "Setted: "; print_r($this->value);
		
    }
    
    function getValue(){
    	
    	return $this->value;
    }
   
    
    
    
    
    
    /**
     * Returns the textarea element in HTML
     * 
     * @since     1.0
     * @access    public
     * @return    string
     */
    function toHtml()
    {
    	if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        } else {
        	if ( !defined( 'HTML_QuickForm_optionalelement_exists' ) ){
        		// import necessary javascript functions for the table
        		ob_start();
        		include dirname(__FILE__).'/optionalelement.js';
        		$js = ob_get_contents();
        		ob_end_clean();
				define('HTML_QuickForm_optionalelement_exists',true);
			} else {
				$js = '';
			}
			
        	
        	$strHtml = '';
        	
        	$strHtml .= '<table id="'.$this->getName().'" class="listing">
        				  <thead>
        				  <tr>
        				  		<th>Key</th>
        				  		<th>Value</th>
        				  </tr>
        				  </thead>
        				  <tbody>';
        				  
        	foreach (array_keys($this->fields) as $fieldkey){
        		$strHtml .= '<tr style="display:none" class="optionalField__'.$this->fields[$fieldkey]->getName().'"><th>'.$this->fields[$fieldkey]->getLabel().'</th><td>'.$this->fields[$fieldkey]->toHtml().'</td></tr>';
        	
        	}
        	
        	$strHtml .= '
        				  
        				  <tr class="addRow"><th>Add Value for :</th><td>
        				  	<select id="'.$this->getName().'_add">
        				  		';
        	foreach ( array_keys($this->fields) as $fieldname){
        		$strHtml .= '<option value="'.$this->fields[$fieldname]->getName().'">'.$this->fields[$fieldname]->getLabel().'</option>
        					';
        	}
        	
        	$strHtml .= '
        				  	
        				  	</select><input type="button" onclick="addOptionalField(\''.$this->getName().'\');" value="Add"/></td>
        				  
        				  ';
        				  
        				  
        	
        	$strHtml .= '</tr>
        				</tbody></table>
        				
        				';
        	
        	return (empty($js)? '': "<script type=\"text/javascript\">\n//<![CDATA[\n" . $js . "//]]>\n</script>") .
               $strHtml;
        }
        	
        
    } //end func toHtml
    
    
    /**
     * Returns the value of field without HTML tags
     * 
     * @since     1.0
     * @access    public
     * @return    string
     */
    function getFrozenHtml()
    {
      
		$strHtml = '';
		
		return $strHtml;
    } //end func getFrozenHtml
	
	

}
