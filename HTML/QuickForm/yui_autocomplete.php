<?php

import('HTML/QuickForm/text.php');

class HTML_QuickForm_yui_autocomplete extends HTML_QuickForm_text {
	var $options=array();
	var $vocabularyName=null;
	var $datasourceUrl=null;
	var $resultNode=null;
	var $queryKeyNode=null;
	var $additionalNodes=array();
	var $scriptQueryParam='-search';
	
	
	function HTML_QuickForm_yui_autocomplete($elementName=null, $elementLabel=null, $attributes=null, $properties=null){
		parent::HTML_QuickForm_text($elementName, $elementLabel, $attributes);
		$this->updateAttributes(array(
			'df:cloneable'=>1, 
			'onfocus'=>'buildYUIAutocomplete(this)',
			'onblur'=>'updateYUIVocabulary(this)',
			'autocomplete'=>'Off',
			'id'=>$elementName)
		);
		
	
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
        	ob_start();
        	
        	
        	if ( !defined('HTML_QuickForm_yui_autocomplete_js_loaded') ){
        		// Load the javascript files if they haven't been loaded yet
        		define('HTML_QuickForm_yui_autocomplete_js_loaded', true);
        		
        		
        		
					
        		
        		$yahoo_dom_event_src = DATAFACE_URL.'/HTML/QuickForm/yui_autocomplete/yahoo-dom-event.js';
        		$autocomplete_src = DATAFACE_URL.'/HTML/QuickForm/yui_autocomplete/autocomplete-min.js';
        		$df_autocomplete_src = DATAFACE_URL.'/HTML/QuickForm/yui_autocomplete/df_yui_autocomplete.js';
        		
        		echo <<<END
  				<script type="text/javascript" src="$yahoo_dom_event_src"></script> 
END;

				if ( isset($this->datasourceUrl ) ){
					// Since a datasource URL has been provided, we must be using
					// AJAX for autocompletion

					$xhr_src = DATAFACE_URL.'/HTML/QuickForm/yui_autocomplete/connection-min.js';
					echo <<<END
					<script type="text/javascript" src="$xhr_src"></script>
END;
				}
				echo <<<END
  				<script type="text/javascript" src="$autocomplete_src"></script>
  				<script language="javascript" src="$df_autocomplete_src"></script>
END;
    		}
    		
    		if ( $this->vocabularyName and !defined('HTML_QuickForm_yui_autocomplete_js_valuelists_'.$this->vocabularyName.'_loaded')){
    			define('HTML_QuickForm_yui_autocomplete_js_valuelists_'.$this->vocabularyName.'_loaded',1);
    			// We must be using in-memory arrays for the vocabulary
    			import('Services/JSON.php');
    			$json = new Services_JSON();
    			$js_options = $json->encode(array_values($this->options));
    			$valuelistName = $this->vocabularyName;
    			echo <<<END
    			<script language="javascript">
    			if ( !window.DATAFACE  ) window.DATAFACE = {};
    			if ( window.DATAFACE.VALUELISTS == null) window.DATAFACE.VALUELISTS = {};
    			if ( window.DATAFACE.VALUELISTS['$valuelistName'] == null ) window.DATAFACE.VALUELISTS['$valuelistName'] = $js_options;
    			</script>
END;
    		}
    		echo '<div>'.parent::toHtml().'</div>';
    	
		    $out = ob_get_contents();
		    ob_end_clean();
           
        	return $out;
        }
        	
        
    } //end func toHtml
    
    
    function getFrozenHtml(){
    	return $this->getValue();
    }
	
	
}
