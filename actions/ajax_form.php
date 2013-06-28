<?php

class dataface_actions_ajax_form {

	function handle(&$params){

		$app = Dataface_Application::getInstance();
		header('Content-type: text/html; charset='.$app->_conf['oe']);
		$record =& $app->getRecord();
		$query =& $app->getQuery();
		
		if ( isset($_REQUEST['-form-id']) ) $formid = $_REQUEST['-form-id'];
		else $formid = 'ajax-form-'.rand();
		
		// First let's figure out what kind of form this is
		$form_type = @$_REQUEST['-form-type'];
		$form = null;
		
		if ( isset($_REQUEST['-fields']) ){
			$fields = explode(',', $_REQUEST['-fields']);
		} else {
			$fields = null;
		}
		
		switch ($form_type){
			case 'new':
				
				$form = df_create_new_record_form($query['-table'], $fields);
				$form->_build();
				break;
			
			case 'edit':
				$form = df_create_edit_record_form($query['-table'], $fields);
				break;
				
			case 'new_related_record':
				$form = df_create_new_related_record_form($record, $query['-relationship'], $fields);
				break;
				
			case 'existing_related_record':
				$form = df_create_existing_related_record_form($record, $query['-relationship']);
				break;
				
			case 'composite':
				import('Dataface/CompositeForm.php');
				$form = new Dataface_CompositeForm($fields);
				$form->build();
				break;
				
			default:
				@include_once('forms/'.$form_type.'.php');
				if ( !class_exists('forms_'.$form_type) ){
					return PEAR::raiseError('Could not find form of type "'.$form_type.'".', DATAFACE_E_ERROR);
				}
				$classname = 'forms_'.$form_type;
				$form = new $classname($fields);
				break;
		
		}
		
		
		// We want the form to be submitted to the embedded iframe
		$form->updateAttributes(array('target'=>$formid.'-target', 'accept-charset'=>$app->_conf['ie']));
		$formparams = preg_grep('/^-[^\-].*/', array_keys($query));
		foreach ( $formparams as $param){
			$form->addElement('hidden',$param);
			$form->setDefaults(array($param=>$query[$param]));
		}
		$form->addElement('hidden', '-form-id');
		$form->setDefaults(array('-form-id'=>$formid));
		
		// Now that we have our form, we can do our thing with it.
		if ( $form->validate() ){
			/*
			 *
			 * The form was submitted and it validated ok.  We now process it (ie: save its contents).
			 *
			 */
			$app->clearMessages();
			$result = $form->process( array( &$form, 'save') );
			$success = true;
			$response =& Dataface_Application::getResponse();
			
			if ( !$result ){
				trigger_error("Error occurred in save: ".mysql_error( $app->db()).Dataface_Error::printStackTrace(), E_USER_ERROR);
				exit;
			} else if ( PEAR::isError($result) && !Dataface_Error::isNotice($result) ){

				if ( Dataface_Error::isDuplicateEntry($result) ){
					return $result;
					
				} else {

					trigger_error($result->toString(). Dataface_Error::printStackTrace(), E_USER_ERROR);
					exit;
				}
			} else if ( Dataface_Error::isNotice($result) ){
				$app->addError($result);
				$success = false;
			}
			
			
			if ( $success ){
				import('Dataface/Utilities.php');
				Dataface_Utilities::fireEvent('after_action_ajax_form');
				
				$msg = implode("\n", $app->getMessages());
				//$msg =@$response['--msg'];
				$msg = urlencode(
					Dataface_LanguageTool::translate(
						/* i18n id */
						'Record successfully saved',
						/* Default success message */
						"Record successfully saved.<br>"
					).$msg
				);
				// We need to output the success content.
				// This could be in any of the following formats:
				//	1. HTML --- actually not yet.. let's just do JSON
				//	2. JSON
				//	3. XML --- not yet.. just JSON for now.
				
				$targetid = @$_REQUEST['-target-id'];
				
				// This should:
				// 1. Get the target element.
				// 2. Go through the element's subtree and replace
				// 		values that have been changed.  How do we know what
				// 		values have been changed.
				// 
				if ( method_exists($form, 'htmlValues') ){
					if ( method_exists($form, 'changedFields') ){
						$changed_fields = $form->changedFields();
					} else {
						$changed_fields = null;
					}
					
					// Convert the values to JSON
					$changed_values = $form->htmlValues($changed_fields);
					import('Services/JSON.php');
					$json = new Services_JSON();
					$changed_values_json = $json->encode($changed_values);
					
				} else {
					$changed_values_json = '{}';
				}

				echo <<<END
<html><body><script language="javascript"><!--
	
	//self.onload =  function(){
		//parent.handleEditableResponse('$targetid', $changed_values_json);
		var targetel = parent.document.getElementById('$targetid');
		targetel.handleResponse('$targetid', $changed_values_json);
		targetel.onclick=parent.makeEditable;
		targetel.onmouseover=targetel.old_onmouseover;
		targetel.edit_form.parentNode.removeChild(targetel.edit_form);
	
	//}
	
	
//--></script></body></html>
END;
				exit;
						
			}
		}
		
		import('Dataface/FormTool.php');
		$formTool = new Dataface_FormTool();
		ob_start();
		if (is_array($fields) and (count($fields) == 1) and (strpos($fields[0], '#') !== false) ){
			$singleField = $fields[0];
		} else {
			$singleField = false;
		}
		$formTool->display($form, null, $singleField);
		$out = ob_get_contents();
		ob_end_clean();
			
		echo <<<END
		
		<div id="{$formid}-wrapper">
			<iframe id="{$formid}-target" name="{$formid}-target" style="width:0px; height:0px; border: 0px"></iframe>
			$out
		</div>
END;
		if ($form->isSubmitted()){
			// The form has already been submitted so we must be displaying some
			// errors.  We need to remove this stuff from inside the iframe
			// that we are going to be inside of, and place them on the page
			// in the correct place
			echo <<<END
<script language="javascript"><!--
var targetel = parent.document.getElementById('{$formid}-wrapper');
var sourceel = document.getElementById('{$formid}-wrapper');
targetel.innerHTML = sourceel.innerHTML;
//--></script>
END;
		}
		
		exit;
		
	}
}
?>
