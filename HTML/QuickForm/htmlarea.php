<?php
/*******************************************************************************
 * File:	HTML/QuickForm/htmlarea.php
 * Author:	Steve Hannah <shannah@sfu.ca>
 * Created: September 1, 2005
 * Description:
 * 	HMTL Quickform widget to edit HTML.  Uses the FCKEditor
 ******************************************************************************/


require_once 'HTML/QuickForm/textarea.php';


$GLOBALS['HTML_QuickForm_htmlarea'] = array(
	'FCKeditor_BasePath' 		=> ( isset($GLOBALS['HTML_QuickForm_htmlarea']['FCKeditor_BasePath']) ? $GLOBALS['HTML_QuickForm_htmlarea']['FCKeditor_BasePath'] : './lib/FCKeditor'),
	'TinyMCE_BasePath'			=> (isset($GLOBALS['HTML_QuickForm_htmlarea']['TinyMCE_BasePath']) ? $GLOBALS['HTML_QuickForm_htmlarea']['TinyMCE_BasePath'] : './lib/tiny_mce')
	);


/**
 * HTML class for a textarea type field
 * 
 * @author       Steve Hannah <shannah@sfu.ca>
 * @version      0.1.0
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_htmlarea extends HTML_QuickForm_textarea {
	
	var $_basePath = '.';
	
	// added to support multiple types of editors
	var $editorName = 'FCKEditor';
	var $tinyMCE_atts = array(
		'theme','plugins','language','ask','docs_language','debug','focus_alert','directionality','auto_resize','browsers','dialog_type','accessibility_warnings','accessibility_focus','event_elements','table_inline_editing','object_resizing','custom_shortcuts','convert_urls','relative_urls','remove_script_host','document_base_url','content_css','popups_css','editor_css','width','height','visual','visual_table_class','cleanup','valid_elements','extended_valid_elements','invalid_elements','verify_css_classes','verify_html','preformatted','encoding','cleanup_on_startup','fix_content_duplication','inline_styles','convert_newlines_to_brs','force_br_newlines','force_p_newlines','entities','entity_encoding','remove_linebreaks','convert_fonts_to_spans','font_size_classes','font_size_style_values','merge_styles_invalid_parents','force_hex_style_colors','apply_source_formatting','trim_span_elements','doctype','fix_list_elements','fix_table_elements','theme_advanced_toolbar_location','theme_advanced_resizing','theme_advanced_buttons1_add_before','theme_advanced_buttons1_add','theme_advanced_buttons2_add','theme_advanced_buttons2_add_before','theme_advanced_buttons3_add_before','theme_advanced_buttons3_add','theme_advanced_toolbar_location','theme_advanced_toolbar_align','theme_advanced_path_location'
		);
	var $wysiwygOptions = array();
	
	function HTML_QuickForm_htmlarea($elementName=null, $elementLabel=null, $attributes=null)
    {
        HTML_QuickForm_textarea::HTML_QuickForm_textarea($elementName, $elementLabel, $attributes);
        $this->_type = 'htmlarea';
    } //end constructor
    
    
    /**
     * Sets wysiwyg editor options.  Currently only supported by TinyMCE.
     */
    function setWysiwygOptions($atts){
    	foreach ($this->tinyMCE_atts as $option){
    		if ( isset( $atts[$option] ) ){
    			if ( is_array($atts[$option]) ){
    				$this->wysiwygOptions[$option] = implode(',',$atts[$option]);
    			} else {
    				$this->wysiwygOptions[$option] = $atts[$option];
    			}
    		}
    	}
    
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
        	switch (strtolower($this->editorName)){
        	
        		case 'nicedit':
        			ob_start();
        			if ( !defined('HTML_QuickForm_htmlarea_nicEdit_loaded') ){
        				define('HTML_QuickForm_htmlarea_nicEdit_loaded',1);
        				echo '<script language="javascript" type="text/javascript" src="'.DATAFACE_URL.'/js/nicEdit/nicEdit.js"></script>';
        			}
        			echo '<script language="javascript">
        			//<![CDATA[
        			bkLib.onDomLoaded(function(){
        				new nicEditor({fullPanel: true, iconsPath: \''.DATAFACE_URL.'/js/nicEdit/nicEditorIcons.gif\'}).panelInstance(\''.$this->getAttribute('id').'\');
        			});
        			//]]></script>';
        			$html = ob_get_contents();
        			ob_end_clean();
        			return $html."\n".parent::toHtml();
        	
        		case 'fckeditor':
        			require_once 'FCKeditor/fckeditor.php';
        			$editor = new FCKEditor($this->getName());
					$editor->BasePath = $GLOBALS['HTML_QuickForm_htmlarea']['FCKeditor_BasePath'];
					$editor->Value = $this->_value;
					$editor->Width = '100%';
					$editor->Height = '480';
					ob_start();
					$editor->Create();
					$html = ob_get_contents();
					ob_end_clean();
					return $html;
					
				case 'tinymce':
					ob_start();
					if ( !defined('HTML_QuickForm_htmlarea_TinyMCE_loaded') ){
						define('HTML_QuickForm_htmlarea_TinyMCE_loaded', true);
						echo '<script language="javascript" type="text/javascript" src="'.$GLOBALS['HTML_QuickForm_htmlarea']['TinyMCE_BasePath'].'/tiny_mce.js"></script>';
					}
					echo '
					
					<script language="javascript" type="text/javascript">
					tinyMCE.init({
						editor_selector : "mceEditor",
						mode : "exact",
						elements : "'.$this->getAttribute('id').'"';
					foreach ($this->wysiwygOptions as $key=>$value){
						echo ',
						'.$key.' : "'.$value.'"';
					}
					echo '
					});
					
					</script>
					';
					$out = ob_get_contents();
					ob_end_clean();
				
					// now we can just call textarea's tohtml method.
					return $out.parent::toHtml();

						
						
					
					
        	}
        	
        }
        	
        
    } //end func toHtml
    
    
        /**
     * Returns the value of field without HTML tags (in this case, value is changed to a mask)
     * 
     * @since     1.0
     * @access    public
     * @return    string
     */
    function getFrozenHtml()
    {
        $value = $this->getValue();
        $html = '<div style="border: 1px solid #ccc; padding: 1em">'.$value.'</div>';
        
        return $html . $this->_getPersistantData();
    } //end func getFrozenHtml
	
	

}
