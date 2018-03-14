<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * Smarty {textformat}{/textformat} block plugin
 *
 * Type:     block function<br>
 * Name:     textformat<br>
 * Purpose:  format text a certain way with preset styles
 *           or custom wrap/indent settings<br>
 * @link http://smarty.php.net/manual/en/language.function.textformat.php {textformat}
 *       (Smarty online manual)
 * @param array
 * <pre>
 * Params:   style: string (email)
 *           indent: integer (0)
 *           wrap: integer (80)
 *           wrap_char string ("\n")
 *           indent_char: string (" ")
 *           wrap_boundary: boolean (true)
 * </pre>
 * @param string contents of the block
 * @param Smarty clever simulation of a method
 * @return string string $content re-formatted
 */
function smarty_block_collapsible_sidebar($params, $content, &$smarty)
{
   static $sidebar_index = 0;
   if (is_null($content)) {
        return;
    }
    
    
    $treeExpandedURL = df_absolute_url(DATAFACE_URL.'/images/treeExpanded.gif');
    $treeCollapsedURL = df_absolute_url(DATAFACE_URL.'/images/treeCollapsed.gif');
    
    if (isset($params['javascript_path']) ){
    	$jspath = $params['javascript_path'];
    } else if ( defined('DATAFACE_URL') ){
    	$jspath = DATAFACE_URL.'/js';
    } else {
    	$jspath = '';
    }
    $jspath = df_absolute_url($jspath);
    
    
    
    if ( !isset($params['heading']) ){
    	$heading = '';
    	
    } else {
    	$heading = $params['heading'];
    }
    
    if ( !isset($params['class']) ){
    	$clazz = $class = 'Dataface_collapsible_sidebar';
    } else {
    	$clazz = $class = $params['class'];
    }
    
    if ( isset($params['onexpand']) ){
    	$onexpand = $params['onexpand'];	
    } else {
    	$onexpand = '';
    }
    
    if ( isset($params['oncollapse']) ){
    	$oncollapse = $params['oncollapse'];
    } else {
    	$oncollapse = '';
    }
    
   
    
    if ( isset($params['id']) ) $section_name = $id = $params['id'];
    else $id = null;
    
    if ( isset($params['prefix'])  and isset($id) ){
    	$id = $params['prefix'].'_'.$id.'_'.($sidebar_index++);
    } else if ( isset($params['prefix'])){
    	$id = $params['prefix'].'_'.($sidebar_index++);
    } else {
    	$id = rand().'_'.($sidebar_index++);
    }
    
    
    $out = '';
    if ( !defined('SMARTY_BLOCK_COLLAPSIBLE_SIDEBAR_JS') ){
    	define('SMARTY_BLOCK_COLLAPSIBLE_SIDEBAR_JS',1);
    	
    	$js = <<< END
    	<script> if ( typeof(jQuery) == 'undefined' ){ document.writeln('<'+'script src="$jspath/jquery.packed.js"><'+'/script>');}</script>
    	
    	<script type="text/javascript"><!--

    		if ( typeof(Xataface) == 'undefined' ) Xataface = {};
    		if ( typeof(Xataface.blocks) == 'undefined' ) Xataface.blocks = {};
    		if ( typeof(Xataface.blocks.collapsible_sidebar) ) Xataface.blocks.collapsible_sidebar = {};
    		Xataface.blocks.collapsible_sidebar.toggleCallback = function(){
    			// this : dom element
    			jQuery(this).toggleClass('$class-closed');
    			jQuery(this).toggleClass('closed');
    			var img = jQuery(this).prev().find('img').get(0);
    			if ( img.src == '$treeExpandedURL' ) img.src = '$treeCollapsedURL';
    			else img.src = '$treeExpandedURL';
    			
    			if ( jQuery(this).hasClass('closed') ){
    				var collapseCallback = this.parentNode.getAttribute('oncollapse');
    				
					this.parentNode.oncollapse = function(){eval(collapseCallback);};
					this.parentNode.oncollapse();	
    			} else {
    				var expandCallback = this.parentNode.getAttribute('onexpand');
					this.parentNode.onexpand = function(){ eval(expandCallback);};
					this.parentNode.onexpand();
    			}
    		
    		};
    		jQuery(document).ready(function($){
    			var handles = jQuery('.expansion-handle');
    			for ( var i=0; i<handles.length; i++ ){
    				jQuery(handles[i]).click(function(){
    					jQuery(this).parent().next().slideToggle("slow", Xataface.blocks.collapsible_sidebar.toggleCallback);
    				});
    			}
    			
    		});
    	
    	//-->
		</script>
    	
    	
END;

		if ( class_exists('Dataface_Application') ){
			$app =& Dataface_Application::getInstance();
			$app->addHeadContent($js);

		} else {
			$out .= $js;
		}
    } 
    $links = '';
    
    
    if (isset($params['see_all']) ){
    	$links .= '<a href="'.$params['see_all'].'">see all</a>';
    }
    if ( !@empty($params['edit_url']) ){
    	$links .= '<a href="'.$params['edit_url'].'">edit</a>';
    }
    
    if (@$params['display'] == 'collapsed' ){
    	$expandImage = $treeCollapsedURL;
    } else {
    	$expandImage = $treeExpandedURL;
    }
    
    $expansionImage = "<img src=\"$expandImage\" style=\"cursor: pointer\" class=\"expansion-handle\" alt=\"Click to minimize this section\"> ";
    
    if ( isset($section_name) ){
    	$section_name = 'df:section_name="'.df_escape($section_name).'"';
    }
    
    if ( isset($params['movable']) ) {
    	$class .= ' movable-handle';
    	$out .= '<div class="movable" id="'.df_escape($id).'" '.$section_name.' oncollapse="'.df_escape($oncollapse).'" onexpand="'.df_escape($onexpand).'">';
    }
    
    if ( @$params['display'] == 'collapsed' ){
    	$class .= " $clazz-closed";
    }
    
    if ( @$params['hide_heading'] ){
    	$headingstyle = 'display: none';
    } else {
    	$headingstyle = '';
    }
    
    $out .= "<h3 class=\"$class\" style=\"padding-left:0; width:100%; $headingstyle\">$links"."$expansionImage $heading</h3>";
    if ( @$params['display'] == 'collapsed' ){
    	$style = 'style="display:none"';
    	$class = 'class="closed"';
    } else {
    	$style = '';
    	$class = '';
    }
    $out .= "<div $class $style>$content</div>";
    if ( isset($params['movable']) ) $out .= '</div>';

    return $out;
    

}

/* vim: set expandtab: */

?>
