<?php
/**
 * @ingroup widgetsAPI
 */
class Dataface_FormTool_file {
	function pushValue(&$record, &$field, &$form, &$element, &$metaValues){
		// The widget is a file upload widget
		$formTool =& Dataface_FormTool::getInstance();
		$formFieldName = $element->getName();
		$table =& $record->_table;
		$app =& Dataface_Application::getInstance();
		if ( $element->isUploadedFile() ){
			$cachePath = $app->_conf['cache_dir'].'/'.basename($app->_conf['_database']['name']).'-'.basename($table->tablename).'-'.basename($field['name']).'-';
			
			$cachedFiles = glob($cachePath.'*');
			foreach ($cachedFiles as $cachedFile){
				@unlink($cachedFile);
			}
			// Need to delete the cache for this field
			
			// a file has been uploaded
			$val = $element->getValue();
				// eg: array('tmp_name'=>'/path/to/uploaded/file', 'name'=>'filename.txt', 'type'=>'image/gif').
			if ( PEAR::isError($val) ){
				$val->addUserInfo(
					df_translate(
						'scripts.Dataface.QuickForm.pushValue.ERROR_GETTING_ELEMENT_VALUE',
						"Error getting element value for element $field[name] in QuickForm::pushField ",
						array('fieldname'=>$field['name'],'line'=>0,'file'=>'')
						)
					);
				throw new Exception($val->toString(), E_USER_ERROR);
				return $val;
			}
			
			
			
			if ( $table->isContainer($field['name']) ){
				$src = $record->getContainerSource($field['name']);
				if ( strlen($record->strval($field['name']) ) > 0  // if there is already a valud specified in this field.
					and file_exists($src)	// if the old file exists
					and is_file($src)  // make sure that it is only a file we are deleting
					and !is_dir($src)  // don't accidentally delete a directory
				){
					// delete the old file.
					if ( !is_writable($src) ){
						throw new Exception("Could not save field '".$field['name']."' because there are insufficient permissions to delete the old file '".$src."'.  Please check the permissions on the directory '".dirname($src)."' to make sure that it is writable by the web server.", E_USER_ERROR);
					}
					@unlink( $src);
				}
				
				// Make sure that the file does not already exist by that name in the destination directory.
				$savepath = $field['savepath'];
				$filename = basename($val['name']);	// we use basename to guard against maliciously named files.
				$filename = str_replace(chr(32), "_", $filename);
				$matches = array();
				if ( preg_match('/^(.*)\.([^\.]+)$/', $filename, $matches) ){
					$extension = $matches[2];
					$filebase = $matches[1];
				} else {
					$extension = '';
					$filebase = $filename;
				}
				while ( file_exists( $savepath.'/'.$filename) ){
					$matches = array();
					if ( preg_match('/(.*)-{0,1}(\d+)$/', $filebase, $matches) ){
						$filebase = $matches[1];
						$fileindex = intval($matches[2]);
					}
					else {
						$fileindex = 0;
						// We should just leave the filebase the same.
						//$filebase = $filename;
						
					}
					if ( $filebase{strlen($filebase)-1} == '-' ) $filebase = substr($filebase,0, strlen($filebase)-1);
					$fileindex++;
					$filebase = $filebase.'-'.$fileindex;
					$filename = $filebase.'.'.$extension;
				}
				
				if (!is_writable( $field['savepath']) ){
					throw new Exception(
						df_translate(
							'scripts.Dataface.QuickForm.pushValue.ERROR_INSUFFICIENT_DIRECTORY_PERMISSIONS',
							"Could not save field '".$field['name']."' because there are insufficient permissions to save the file to the save directory '".$field['savepath']."'. Please Check the permissions on the directory '".$field['savepath']."' to make sure that it is writable by the web server.",
							array('fieldname'=>$field['name'], 'savepath'=>$field['savepath'])
							), E_USER_ERROR);
				}
				
				move_uploaded_file($val['tmp_name'], $field['savepath'].'/'.$filename);
				chmod($field['savepath'].'/'.$filename, 0744);
					
				$out = $filename;
				
			
			} else {
				if ( file_exists($val['tmp_name']) ){
					if ( !@$app->_conf['multilingual_content'] ){
						// THis is a bit of a hack.  If we are using multilingual
						// content, then Dataface_DB will parse every query
						// before sending it to the database.  It is better if
						// that query is short - so we only pass the whole value
						// if we are not parsing the query.
						$out = file_get_contents($val['tmp_name']);
					} else {
						// If we are parsing the query, then we will just store
						// the path to the blob.
						$out = $val['tmp_name'];
					}
				} else {
					$out = null;
				}
			}
			
			if ( is_array( $metaValues ) ){
				if ( isset( $field['filename'] ) ){
					// store the file name in another field if one is specified
					$metaValues[$field['filename']] = $val['name'];
					
				}
				if ( isset( $field['mimetype'] ) ){
					// store the file mimetype in another field if one is specified
					$metaValues[$field['mimetype']] = $val['type'];
					
				}
			}
			
			return $out;
			
				
		}
		
		if ( $table->isContainer($field['name']) ){
			return $record->val($field['name']);
		}
		return null;

	}
	
	function pullValue(&$record, &$field, &$form, &$element){
		/*
		 * 
		 * We don't bother pulling the values of file widgets because it would take too long.
		 *
		 */
		
		$widget =& $field['widget'];
		$formFieldName = $element->getName();
		
		$val = null;
		if ( $widget['type'] == 'webcam' ) $val = $record->getValueAsString($field['name']);
		if ( $record->getLength($field['name']) > 0 ){
			// there is already a file set, let's add a preview to it
			if ( $record->isImage($field['name']) ){
				$element->setProperty('image_preview', df_absolute_url($record->q($field['name'])));
			}
			$element->setProperty('preview', df_absolute_url($record->q($field['name'])));
			//echo "Adding preview for field '$fieldname':".$record->qq($fieldname);
		} else {
			//echo "No data in field '$fieldname'";
		}
		
		return $val;
	}
	
}
