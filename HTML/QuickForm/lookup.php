<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2009 Web Lite Solutions Corp (shannah@sfu.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */

/*******************************************************************************
 * File:	HTML/QuickForm/lookup.php
 * Author:	Steve Hannah <shannah@sfu.ca>
 * Created: July 13, 2009
 * Description:
 * 	HTML QuickForm lookup widget that allows users to click a "lookup" button and 
 * find the record id that they want to place in a field.
 *
 * @example
 * $el = $form->addElement('lookup', 'myfield','My Field');
 * $el->setProperties(array('table'=>'thelookuptable'));
 *
 *
 ******************************************************************************/


require_once 'HTML/QuickForm/text.php';


/**
 * HTML Class for a calendar widget.
 * 
 * @author       Steve Hannah <shannah@sfu.ca>
 * @version      0.1.0
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_lookup extends HTML_QuickForm_text {
	
	var $index;
	function HTML_QuickForm_lookup($elementName=null, $elementLabel=null, $attributes=null, $properties=null)
    {
		static $index=1;
		$this->index = $index++;
		$this->index_prefix = time();
		if ( !isset($attributes) ) $attributes = array();
		$class = @$attributes['class'];
		$class .= ' xf-lookup xf-lookup-'.$this->index_prefix.'-'.$this->index;
		$attributes['class'] = $class;
		$attributes['df:cloneable'] = 1;
        parent::HTML_QuickForm_input($elementName, $elementLabel, $attributes);
        $this->_type = 'lookup';
        
        	
        
    } //end constructor
    
    
    
    /**
     * Returns the textarea element in HTML
     * 
     * @since     1.0
     * @access    public
     * @return    string
     */
    function toHtml()
    {
    	
    	
        
		$out = '';
		
		if ( !defined('HTML_QuickForm_lookup_files_loaded') ){
			define('HTML_QuickForm_lookup_files_loaded',1);
			$jt = Dataface_JavascriptTool::getInstance();
			$jt->import('xataface/widgets/lookup.js');
			
			
		}
		$properties = $this->getProperties();
		if ( $this->_flagFrozen ){
			$properties['frozen'] = 1;
		}
		if ( !isset($properties['callback']) ){
			$properties['callback'] = 'function(out){}';
		}
		
		ob_start();
		$oldFrozen = $this->_flagFrozen;
		$this->_flagFrozen=0;
		$this->updateAttributes(array('data-xf-lookup-options'=>json_encode($properties)));
		echo parent::toHtml();
		$this->_flagFrozen=$oldFrozen;
		$out .=  ob_get_contents();
		ob_end_clean();
	   
	   $selector = null;
	   if ( $this->getName() ){
		  $selector = "input[name='".$this->getName()."']";
	   } else {
		  $selector = '.xf-lookup-'.$this->index_prefix.'-'.$this->index;
	   }
		
		
		/*
		$out  .= '
		<script type="text/javascript">
		jQuery(document).ready(function($){
			var options = '.json_encode($properties).';
			if ( !options.filters ) options.filters = {};
			options.dynFilters = {};
			$.each(options.filters, function(key,val){
				if ( val.indexOf("$")==0 ){
					options.dynFilters[key] = val.substr(1);
					delete options.filters[key];
				}
			});
			options.callback = '.$properties['callback'].';
			options.click = function(){
				$.each(options.dynFilters, function(key,val){
					delete options.filters[key];
					$("form *[name="+val+"]").each(function(){
						options.filters[key] = $(this).val();
					});
				});
				
			};
			$("'.$selector.'").RecordBrowserWidget(options);
		});
		</script>';
	   */
		return '<span style="white-space:nowrap">'.$out.'</span>';
        
        	
        
    } //end func toHtml
    
    
    //function getFrozenHtml(){
    //	return $this->getValue();
    //}
    
    
	
	

}
