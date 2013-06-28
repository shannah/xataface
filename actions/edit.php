<?php
/********************************************************************************
 *
 *  Xataface Web Application Framework for PHP and MySQL
 *  Copyright (C) 2006  Steve Hannah <shannah@sfu.ca>
 *  
 *  This library is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU Lesser General Public
 *  License as published by the Free Software Foundation; either
 *  version 2.1 of the License, or (at your option) any later version.
 *  
 *  This library is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *  Lesser General Public License for more details.
 *  
 *  You should have received a copy of the GNU Lesser General Public
 *  License along with this library; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *===============================================================================
 */
/**
 * File dataface/actions/edit.php
 * Author: Steve Hannah <shannah@sfu.ca>
 * Created April 5, 2006
 *
 * Description:
 * 	A controller class to handle the 'edit' action.  The edit action is the action that
 *  allows the user to edit an existing record in the database.
 */
class dataface_actions_edit {
	function handle(&$params){
		import( 'Dataface/FormTool.php');
		import( 'Dataface/QuickForm.php');
		
				
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		$resultSet =& $app->getResultSet();
		
		$currentRecord =& $app->getRecord();
		$currentTable =& Dataface_Table::loadTable($query['-table']);
		if ( !isset($query['--tab']) and count($currentTable->tabs($currentRecord)) > 1 ){
			list($query['--tab']) = array_keys($currentTable->tabs($currentRecord));
		} else if ( count($currentTable->tabs($currentRecord)) <= 1 ){
			unset($query['--tab']);
		}
                
                $includedFields = null; // Null for all fields
                
                if ( @$query['-fields'] ){
                    $includedFields = explode(' ', $query['-fields']);
                }
		
		
		/*
		 *
		 * Create the quickform for the current record.
		 *
		 */
		//$form = new Dataface_QuickForm($query['-table'], $app->db(),  $query);
		$formTool =& Dataface_FormTool::getInstance();
		
		
		if ( $resultSet->found()> @$query['-cursor']){
			$form = $formTool->createRecordForm($currentRecord, false, @$query['--tab'], $query, $includedFields);
			/*
			 * There is either a result to edit, or we are creating a new record.
			 *
			 */
			 
			$res = $form->_build();
			if ( PEAR::isError($res) ){
				error_log($res->toString().implode("\n", $res->getBacktrace()));
				throw new Exception("An error occurred while building the edit form.  See error log for details.", E_USER_ERROR);
				
			
			}
			$formTool->decorateRecordForm($currentRecord, $form, false, @$query['--tab']);
			
			
			/*
			 *
			 * We need to add the current GET parameter flags (the GET vars starting with '-') so
			 * that the controller knows to pass control to this method again upon form submission.
			 *
			 */
			foreach ( $query as $key=>$value){
				if ( strpos($key,'-')===0 ){
					$form->addElement('hidden', $key);
					$form->setDefaults( array( $key=>$value) );
					
				}
			}
			
			/*
			 * Store the current query string (the portion after the '?') in the form, so we 
			 * can retrieve it after and redirect back to our original location.
			 */
			$form->addElement('hidden', '-query');
			$form->setDefaults( array( '-action'=>$query['-action'],'-query'=>$_SERVER['QUERY_STRING']) );
			
			
			/*
			 * 
			 * We have to deal with 3 cases.
			 * 	1) The form has not been submitted.
			 *	2) The form was submitted but didn't validate (ie: it had some bad input)
			 * 	3) The form was submitted and was validated.
			 *
			 * We deal with Case 3 first...
			 *
			 */
		
			if ( $formTool->validateRecordForm($currentRecord, $form, false, @$query['--tab']) ){
				/*
				 *
				 * The form was submitted and it validated ok.  We now process it (ie: save its contents).
				 *
				 */
				$app->clearMessages();
				$formTool->handleTabSubmit($currentRecord, $form, @$query['--tab']);
				if ( !isset($query['--tab']) ){
					// If we aren't using tabs we just do it the old way.
					// (If it ain't broke don't fix it
					
					$result = $form->process( array( &$form, 'save') );
				} else {
					// If we are using tabs, we will use the formtool's 
					// session aware saving function
					
					$result = $formTool->saveSession($currentRecord);
				}
				$success = true;
				$response =& Dataface_Application::getResponse();
				
				if ( !$result ){
					error_log("Error occurred in save: ".mysql_error( $app->db()).Dataface_Error::printStackTrace());
					throw new Exception("An error occurred while attempting to save the record.  See error log for details.", E_USER_ERROR);
				} else if ( PEAR::isError($result) && !Dataface_Error::isNotice($result) ){
					
					if ( Dataface_Error::isDuplicateEntry($result) ){
						$app->addError($result);
						$success = false;
						
					} else {
						error_log($result->toString(). implode("\n", $result->getBacktrace()));
						throw new Exception("An error occurred while attempting to save the record.  See error log for details.", E_USER_ERROR);
						
					}
				} else if ( Dataface_Error::isNotice($result) ){
					$app->addError($result);

					//$response['--msg'] = @$response['--msg'] ."\n".$result->getMessage();
					$success = false;
				}
				
				
				if ( $success ){
					
					if (@$query['-response'] == 'json' ){
						//header('Content-type: text/html; charset="'.$app->_conf['oe'].'"');
						$rvals = $currentRecord->strvals();
						$rvals['__title__'] = $currentRecord->getTitle();
						$rvals['__id__'] = $currentRecord->getId();
						echo df_escape(json_encode(array('response_code'=>200, 'record_data'=> $rvals, 'response_message'=>df_translate('Record Successfully Saved', 'Record Successfully Saved'))));
						return;
					}
					
					import('Dataface/Utilities.php');
					Dataface_Utilities::fireEvent('after_action_edit', array('record'=>$form->_record));
					/*
					 *
					 * The original query string will have the -new flag set.  We need to remove this 
					 * flag so that we don't redirect the user to create another new record.
					 *
					 */
					$vals = $form->exportValues();
					$vals['-query'] = preg_replace('/[&\?]-new=[^&]+/i', '', $vals['-query']);
					
					$_SESSION['--last_modified_record_url'] = $form->_record->getURL();
					$_SESSION['--last_modified_record_title'] = $form->_record->getTitle();
					
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
					
					if ( preg_match('/[&\?]-action=edit&/', $vals['-query']) and !$form->_record->checkPermission('edit') ){
						$vals['-query'] = preg_replace('/([&\?])-action=edit&/', '$1-action=view&', $vals['-query']);
					} else if ( preg_match('/[&\?]-action=edit$/', $vals['-query']) and !$form->_record->checkPermission('edit') ){
						$vals['-query'] = preg_replace('/([&\?])-action=edit$/', '$1-action=view', $vals['-query']);
					}
					$vals['-query'] = preg_replace('/&?--msg=[^&]*/', '', $vals['-query']);
					
					$link = $_SERVER['HOST_URI'].DATAFACE_SITE_HREF.'?'.$vals['-query'].'&--saved=1&--msg='.$msg;
					
					
					/*
					 *
					 * Redirect the user to the appropriate record.
					 *
					 */
					$app->redirect("$link");
				}
			}
			
			ob_start();
			$form->display();
			$out = ob_get_contents();
			ob_end_clean();
			
			if ( count($form->_errors) > 0 ){
				$app->clearMessages();
				$app->addError(PEAR::raiseError("Some errors occurred while processing this form: <ul><li>".implode('</li><li>', $form->_errors)."</li></ul>"));
			}
			$context = array('form'=>$out);
			
			
			// Now let's add the tabs to the context
			$context['tabs'] = $formTool->createHTMLTabs($currentRecord, $form, @$query['--tab']);
			
				 
		} else {
			// no records were found
			$context = array('form'=>'');
			
			if ( isset($_SESSION['--last_modified_record_url']) ){
				$lastModifiedURL = $_SESSION['--last_modified_record_url'];
				$lastModifiedTitle = $_SESSION['--last_modified_record_title'];
				unset($_SESSION['--last_modified_record_title']);
				unset($_SESSION['--last_modified_record_url']);
				$app->addMessage(
					df_translate(
						'Return to last modified record',
						'No records matched your request.  Click <a href="'.$lastModifiedURL.'">here</a> to return to <em>'.df_escape($lastModifiedTitle).'</em>.',
						array('lastModifiedURL'=>$lastModifiedURL,
							 'lastModifiedTitle'=>$lastModifiedTitle
							)
						)
					);
			} else {
				$app->addMessage(
					Dataface_LanguageTool::translate(
						'No records matched request',
						'No records matched your request'
						)
					);
			}
			
			$query['-template'] = 'Dataface_Main_Template.html';
		}
		
		
		if ( isset($query['-template']) ) $template = $query['-template'];
                else if ( @$query['-headless'] ) $template = 'Dataface_Edit_Record_headless.html';
		else if ( isset( $params['action']['template']) ) $template = $params['action']['template'];
                else $template = 'Dataface_Edit_Record.html';
		

		df_display($context, $template, true);
		
	}
	
}
?>
