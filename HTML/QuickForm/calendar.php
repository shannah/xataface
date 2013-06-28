<?php
/*******************************************************************************
 * File:	HTML/QuickForm/calendar.php
 * Author:	Steve Hannah <shannah@sfu.ca>
 * Created: March 10, 2006
 * Description:
 * 	HMTL Quickform calendar widget.  This is essentially a wrapper to use the 
 * DynArch jscalendar widget - a really cool calendar widget.
 *
 ******************************************************************************/


require_once 'HTML/QuickForm/text.php';
require_once 'jscalendar/calendar.php';

$GLOBALS['HTML_QuickForm_calendar'] = array(
	'jscalendar_BasePath' 		=> ( isset($GLOBALS['HTML_QuickForm_calendar']['jscalendar_BasePath']) ? $GLOBALS['HTML_QuickForm_calendar']['jscalendar_BasePath'] : './lib/jscalendar'));


/**
 * HTML Class for a calendar widget.
 * 
 * @author       Steve Hannah <shannah@sfu.ca>
 * @version      0.1.0
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_calendar extends HTML_QuickForm_text {
	
	var $_basePath = '.';
	var $calendar;
	function HTML_QuickForm_calendar($elementName=null, $elementLabel=null, $attributes=null, $properties=null)
    {

        parent::HTML_QuickForm_input($elementName, $elementLabel, $attributes);
        $this->_type = 'calendar';
        
        // Default language is english
        if ( !@$properties['lang'] ) $properties['lang'] = 'en';
        
        // Default theme is windows 2k.  This is the name of a css file (without the .css extension)
        if ( !@$properties['theme'] ) $properties['theme'] = 'calendar-win2k-2';
        
        // Whether to load the stripped javascript files (i.e., the versions with spaces and whitespace stripped out).
        // for faster loading
        if ( !isset($properties['stripped']) ) $properties['stripped'] = false;
        
        // Default show Monday first (first day = 1)
        if (!isset($properties['firstDay'])) $properties['firstDay'] = 1;
        
        // Whether or not to show the time also
        if ( !isset($properties['showsTime']) ) $properties['showsTime'] = true;
        
        // Whether or not to show others (not sure what this means ... check jscalendar docs
        if ( !isset($properties['showOthers']) ) $properties['showOthers'] = true;
        
        // The Format to be placed in the input field.
        if ( !isset( $properties['ifFormat']) ) $properties['ifFormat'] = '%Y-%m-%d %I:%M %P';
        
        // The time format
        if ( !isset( $properties['timeFormat']) ) $properties['timeFormat'] = '12';
        
        
        foreach (array_keys($properties) as $key){
        	$this->setProperty($key, $properties[$key]);
        }
        
        $this->calendar = new DHTML_Calendar($GLOBALS['HTML_QuickForm_calendar']['jscalendar_BasePath'], 
        						$this->getProperty('lang'), 
        						$this->getProperty('theme'), 
        						$this->getProperty('stripped')
        						);
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
        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        } else {
        
        	if ( !defined('HTML_QuickForm_calendar_js_loaded') ){
        		// Load the javascript files if they haven't been loaded yet
        		define('HTML_QuickForm_calendar_js_loaded', true);
        		$this->calendar->load_files();
        	}
        	
        	$properties = $this->getProperties();
        	$attributes = $this->getAttributes();
        	ob_start();
        	$this->calendar->make_input_field(
		    // calendar options go here; see the documentation and/or calendar-setup.js
		    $properties,
		    // field attributes go here
		    $attributes);
		    $out = ob_get_contents();
		    ob_end_clean();
           
        	return $out;
        }
        	
        
    } //end func toHtml
    
    
    function getFrozenHtml(){
    	return $this->getValue();
    }
    
    
	
	

}
