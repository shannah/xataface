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


require_once 'HTML/QuickForm/file.php';


/**
 * HTML Class for a select list with times at a specified interval.
 * 
 * @author       Steve Hannah <shannah@sfu.ca>
 * @version      0.1.0
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_webcam extends HTML_QuickForm_file {
	

	function HTML_QuickForm_webcam($elementName=null, $elementLabel=null, $attributes=null, $properties=array())
    {
    	if ( isset($elementName) ){
			
			parent::HTML_QuickForm_file($elementName, $elementLabel,  $attributes); 
		}
		
    } //end constructor
    
    
    function toHtml(){
    	$field =& $this->getFieldDef();
    	
    
     	$ID = 'temp'.rand(1,1000000); //$_REQUEST['ID'];//get current user's ID whose video will be recorded
     	if ( $this->record and $this->record->getValue('id') ){
     		$ID = $this->record->getValue('id');
     	}
    	$value = $ID;
    	
//get ray directory
$ray_dir = "http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']);
if ( $ray_dir{strlen($ray_dir)-1} != '/' ) $ray_dir .= '/';
$ray_xml = $ray_dir."get_xml.php";
    	$out = <<<END
   
<div class="help">If you have a web camera you can press the <em>Record</em> button below to record your piece. <a href="troubleshooting.php">Troubleshooting instructions</a>.</div> 
<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0" width="348" height="248" id="ray_recorder" align="middle">
<param name="allowScriptAccess" value="always" />
<param name="movie" value="{$ray_dir}ray_recorder.swf" />
<param name="quality" value="high" />
<param name="wmode" value="TRANSPARENT" />
<param name="FlashVars" value="ID={$ID}&url={$ray_xml}" />
<embed
        src="{$ray_dir}ray_recorder.swf"
        WMODE="TRANSPARENT"
        quality="high"
        bgcolor="#ffffff"
        width="348" height="248"
        name="ray_recorder"
        align="middle"
        allowScriptAccess="always"
        type="application/x-shockwave-flash"
        FlashVars="ID={$ID}&url={$ray_xml}"
        pluginspage="http://www.macromedia.com/go/getflashplayer" />
</object><br/>
<div class="help">Or upload a movie file from your camera, phone, or computer...</div>
<input name="{$this->getName()}__webcam__" type="hidden" value="{$value}" />
END;
		return $out . parent::toHtml();

    
    }
    
	
	

}

