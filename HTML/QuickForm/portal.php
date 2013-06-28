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


require_once 'HTML/QuickForm/input.php';
require_once 'Dataface/dhtmlxGrid/activegrid.php';

//$GLOBALS['HTML_QuickForm_portal'] = array(
//	'jscalendar_BasePath' 		=> ( isset($GLOBALS['HTML_QuickForm_calendar']['jscalendar_BasePath']) ? $GLOBALS['HTML_QuickForm_calendar']['jscalendar_BasePath'] : './lib/jscalendar'));


/**
 * HTML Class for a portal widget.
 * 
 * @author       Steve Hannah <shannah@sfu.ca>
 * @version      0.1.0
 * @since        PHP4.04pl1
 * @access       public
 */
class HTML_QuickForm_portal extends HTML_QuickForm_input {
	
	var $grid;
	function HTML_QuickForm_portal($elementName=null, $elementLabel=null, $attributes=null)
    {

        parent::HTML_QuickForm_input($elementName, $elementLabel, $attributes);
        $this->_type = 'portal';
        
    }
    
    function init_portal($properties=null){
    	$elementName = $this->getName();
		
        if ( isset( $_POST[$elementName] ) ){
        	
        	$this->grid = Dataface_dhtmlXGrid_activegrid::getGrid($_POST[$elementName]);

        } else {
			// Let's get the name of the relationship that this element should edit
			if ( isset($properties['relationship']) ) $relationship = $properties['relationship'];
			else $relationship = $elementName;
			
			// first let's get the source record of the relationship
			$record =& $properties['record'];
			if ( !isset($record) ){
				$records = array();
			} else {
				$records =& $record->getRelatedRecordObjects($relationship);
			}
			$r =& $record->_table->getRelationship($relationship);
				
			// Let's see if some columns were specified.
			if ( isset($properties['columns']) ){
				$flds = explode(',',$properties['columns']);
				
			} else {
				$flds_temp = $r->fields();
				$flds = array();
				foreach ($flds_temp as $fld) {
					if ( strpos($fld,'.') !== false ){
						list($dummy, $flds[]) = explode('.',$fld);
					} else {
						$flds[] = $fld;
					}
				}
			}
			
			$colnames = array();
			foreach ($flds as $fld){
				//list($dummy, $fld) = explode('.',$fld);
				
				$tbl =& $r->getTable($fld);
				$fld_data =& $tbl->getField($fld);
				
				if ( strcasecmp($fld_data['visibility']['list'],'hidden') and 
					 !in_array($fld_data['widget']['type'], array('static','hidden','portal','table','file','webcam'))){
					$colnames[$fld] =& $fld_data;
					$colnames[$fld]['table'] = $tbl->tablename;
				}
				unset($tbl);
				unset($fld_data);
			}
			
			
			
			
			$this->grid = new Dataface_dhtmlXGrid_activegrid(array('-records'=>&$records,'-columns'=>&$colnames, '-parent_id'=>$record->getId(), '-relationship'=>$relationship));
			$this->grid->relationship = $relationship;
			$this->grid->parent_id = $record->getId();
		}
    } //end constructor
    
    
    
    /**
     * Returns the HTML Grid element.
     * 
     * @since     1.0
     * @access    public
     * @return    string
     */
    function toHtml()
    {
        return <<<END
        	<input type="hidden" value="{$this->grid->id}" name="{$this->getName()}"/>
        	{$this->grid->toHTML()}
END;
        
    } //end func toHtml
    
    
    function commit(){
    	$this->grid->commit();
    	$this->grid->removeGrid($this->grid->id);
    }
    
    
    
	
	

}

