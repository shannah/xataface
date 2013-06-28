<?php
/**
 * @author Steve Hannah <steve@weblite.ca>
 * Copyright (c) 2009 Steve Hannah, All rights reserved.
 * Created Feb. 4, 2009
 *
 * Deletes a file from a blob or container field.
 * Usage:
 * POST:
 *	-action=delete_file
 *	--field=<fieldname>
 *	[--format=(json|html)]
 *	[--redirect=<url to redirect to upon success>]
 *
 * Returns:
 * 	[--format=json]
 *		{success: (0|1), '--msg': "success message"}
 *	[--format!=json]
 *		Redirect to url specified by --redirect parameter.
 */
class dataface_actions_delete_file {
	function handle(&$params){
		if ( @$_POST['--field'] ){
			return $this->handlePost($params);
		} else {
			return $this->handleGet($params);
		}
	}
	
	function handleGet(&$params){
		return PEAR::raiseError("No implemented yet.  Please use this only via POST method.");
	}
	
	function handlePost(&$params){
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		if ( !@$_POST['--field'] ) return PEAR::raiseError('No field specified');
		$record =& $app->getRecord();
		
		if ( !$record ) return PEAR::raiseError('No record found');
		
		$fieldDef =& $record->_table->getField($_POST['--field']);
		if ( PEAR::isError($fieldDef) ) return $fieldDef;
		
		if ( !$record->checkPermission('edit', array('field'=>$fieldDef['Field'])) ){
			return Dataface_Error::permissionDenied('You don\'t have permission to edit this field.');
		}
		
		
		
		if ( $fieldDef['Type'] == 'container' ){
			$fileName = $record->val($fieldDef['Field']);
			if ( !$fileName ) return PEAR::raiseError("This record does not contain a file in the $fieldDef[Field] field.");
			
			// We need to delete the file from the file system.
			$path = $fieldDef['savepath'];
			$filePath = $path.'/'.basename($fileName);
			@unlink($filePath);
			
			$record->setValue($fieldDef['Field'], null);
			if ( @$fieldDef['mimetype'] ){
				$mimeTypeField =& $record->_table->getField($fieldDef['mimetype']);
				if ( !PEAR::isError($mimeTypeField) ){
					$record->setValue($fieldDef['mimetype'], null);
				}
			}
			$res = $record->save();
			if ( PEAR::isError($res) ) return $res;
						
		} else if ( $record->_table->isBlob($fieldDef['Field']) ){
			$record->setValue($fieldDef['Field'], 'dummy');
			$record->setValue($fieldDef['Field'], null);
			if ( @$fieldDef['mimetype'] ){
				$mimetypeField =& $record->_table->getField($fieldDef['mimetype']);
				if ( !PEAR::isError($mimetypeField) ){
					$record->setValue($fieldDef['mimetype'], null);
				}
			}
			
			if ( @$fieldDef['filename'] ){
				$filenameField =& $record->_table->getField($fieldDef['filename']);
				if ( !PEAR::isError($filenameField) ){
					$record->setValue($fieldDef['filename'], null);
				}
			}
			$res = $record->save();
			if ( PEAR::isError($res) ) return $res;
			
			
			
		}
		
		// Now that we have been successful, let's return a success reply.
		if ( @$query['--format'] == 'json' ){
			import('Services/JSON.php');
			$json = new Services_JSON;
			header('Content-type: application/json; charset='.$app->_conf['oe']);
			echo $json->encode(
				array(
					'success'=>1,
					'--msg' => 'Successfully deleted file'
				)
			);
			return;
		} else {
			$redirect = '';
			if ( !$redirect ) $redirect = @$query['-redirect'];
			if ( !$redirect ) $redirect = @$_SERVER['HTTP_REFERER'];
			if ( !$redirect ) $redirect = $record->getURL('-action=edit');
			
			if ( !$redirect or PEAR::isError($redirect) ){
				$redirect = DATAFACE_SITE_HREF;
			}
			
			if ( strpos($redirect, '?') === false ) $redirect .= '?';
			$redirect .= '&--msg='.urlencode("File successfully deleted.");
			$app->redirect("$redirect");
		}
		
	}
}
