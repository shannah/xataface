<?php
/*******************************************************************************
 * File:	HTML/QuickForm/htmlarea.php
 * Author:	Steve Hannah <shannah@sfu.ca>
 * Created: September 1, 2005
 * Description:
 * 	HMTL Quickform widget to edit HTML.  Uses the FCKEditor
 ******************************************************************************/
require_once 'HTML/QuickForm/element.php';	
	
$GLOBALS['HTML_QUICKFORM_ELEMENT_TYPES']['table'] = array('HTML/QuickForm/table.php','HTML_QuickForm_table');


/**
 * HTML class for a textarea type field
 * 
 * @author       Steve Hannah <shannah@sfu.ca>
 * @version      0.1.0
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_table extends HTML_QuickForm_element {

	var $columnTemplates = array();
	var $name = '';
	var $value;
	
	
	function HTML_QuickForm_table($elementName=null, $elementLabel=null, $attributes=null)
    {
        $this->HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->name = $elementName;
        
        $this->_type = 'table';
       
    } //end constructor
    
    
    function addField(&$element){
    	$this->columnTemplates[] =& $element;
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
				if ( is_array($value[$key]) ){
					$empty = true;
					foreach ( $value[$key] as $cell){
						if ( !empty($cell) ){
							$empty = false;
							break;
						}
					}
					if ( $empty ) continue;
				}
				$this->value[] = $value[$key];
			}
		} else {
			$this->value = array();
			foreach ($this->columnTemplates as $col){
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
        	if ( !defined( 'HTML_QuickForm_table_exists' ) ){
        		// import necessary javascript functions for the table
        		ob_start();
        		include dirname(__FILE__).'/table.js';
        		$js = ob_get_contents();
        		ob_end_clean();
				define('HTML_QuickForm_table_exists',true);
			} else {
				$js = '';
			}
			
        	$values = $this->getValue();
        	//print_r($values);
        	
        	$i = 0;
        	$cols =& $this->columnTemplates;
        	//throw new Exception(get_class($cols).$cols[0]->getName(), E_USER_ERROR);
        	
        	if ( !is_array($values) ){
        		$values = array();
        		$values[0] = array();
        		foreach ($cols as $col){
        		
        			$values[0][$col->getName()] = null;
        		}
        	}
        	$strHtml = '';
        	/*
        	foreach ($values as $value){
        		$row = $i++;
        		
        		foreach ( $cols as $col){
        			$strHtml .= '<input type="hidden" name="'.$this->getName().'['.($i).']['.$col->getName().'_old]" 
        						value="'.$value[$col->getName()].'" />
        						';
        			
        			//throw new Exception($col->getName(),  E_USER_ERROR);
        		}
        	
        	}
        	*/
        	//$fielddef = $this->getFieldDef();
        	$strHtml .= '<table id="'.$this->getName().'">
        				  <thead>
        				  <tr><th><!--Delete button column --></th>
        				  ';
        	foreach ($cols as $col){
        		$fielddef = $col->getFieldDef();
        		$strHtml .= '<th valign="top">'.$col->getLabel().
        		'<div style="font-weight: normal; font-size: 80%" class="formHelp">'.df_translate( $fielddef['widget']['description_i18n'],$fielddef['widget']['description']).'</div>'.
        		'</th>
        		';
        	}
        	$strHtml .= '</tr>
        				</thead>
        				<tbody>
        				';
        	$i = 0;
        	$strHtml .= '<tr class="prototype" style="display:none">
        					<td><input type="button" value="Delete" onclick="deleteRow(event)" name="'.$this->getName().'[prototype][__delete]"/></td>';
        	
        	foreach ($cols as $col){
				$field = unserialize(serialize($col));
				$field->setName($this->getName().'[prototype]['.$field->getName().']');
				$strHtml .= '<td align="center">'.$field->toHtml().'</td>
				';
			}
			$strHtml .= "</tr>";
        	foreach ($values as $row=>$value){
        		//$row = $i++;
        		$strHtml .= '<tr>
        					<td><input type="button" value="Delete" onclick="deleteRow(event)" name="'.$this->getName().'['.$row.'][__delete]"/></td>
        		';
        		
        		foreach ($cols as $col){
        			$field = unserialize(serialize($col));
        			$fn = $field->getName();
        			$field->setName($this->getName().'['.($row).']['.$fn.']');
        			if ( is_array($value) )
        				$field->setValue($value[$fn]);
        			$strHtml .= '<td align="center">'.$field->toHtml().'</td>
        			';
        			unset($field);
        		}
        		$strHtml .= '</tr>
        		';
        	}
        	
        	$strHtml .= '</tbody></table>
        	';
        	
        	$strHtml .= '<input type="button" onclick="insertNewTableRow(\''.$this->getName().'\')" value="Add Row"/>';
        	
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
       if ( !defined( 'HTML_QuickForm_table_exists' ) ){
			// import necessary javascript functions for the table
			ob_start();
			include dirname(__FILE__).'/table.js';
			$js = ob_get_contents();
			ob_end_clean();
			define('HTML_QuickForm_table_exists',true);
		} else {
			$js = '';
		}
		
		$values = $this->getValue();
		
		$i = 0;
		$cols =& $this->columnTemplates;
		//throw new Exception(get_class($cols).$cols[0]->getName(), E_USER_ERROR);
		
		if ( !is_array($values) ){
			$values = array();
			$values[0] = array();
			foreach ($cols as $col){
			
				$values[0][$col->getName()] = null;
			}
		}
		$strHtml = '';
		//foreach ($values as $value){
		//	$row = $i++;
		//	
		//	foreach ( $cols as $col){
		//		$strHtml .= $value[$col->getName()];
		//		
		//		//throw new Exception($col->getName(),  E_USER_ERROR);
		//	}
		//
		//}
		$strHtml .= '<table id="'.$this->getName().'">
					  <thead>
					  <tr>
					  ';
		foreach ($cols as $col){
			$strHtml .= '<th>'.$col->getLabel().'</th>
			';
		}
		$strHtml .= '</tr>
					</thead>
					<tbody>
					';
		$i = 0;
	
		
		
		foreach ($values as $value){
			$row = $i++;
			$strHtml .= '<tr>
						
			';
			foreach ($cols as $col){
				$field = unserialize(serialize($col));
				$fn = $field->getName();
				$field->setName($this->getName().'['.($row).']['.$fn.']');
				$field->setValue($value[$fn]);
				$strHtml .= '<td>'.$field->getFrozenHtml().'</td>
				';
			}
			$strHtml .= '</tr>
			';
		}
		
		$strHtml .= '</tbody></table>
		';
		
		
		
		return $strHtml;
    } //end func getFrozenHtml
	
	

}

