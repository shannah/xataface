<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp (shannah@sfu.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */
import( 'HTML/QuickForm.php');
import('Smarty/Smarty.class.php');
import('Dataface/Globals.php');
import('Dataface/QueryBuilder.php');


/**
 * @ingroup formsAPI
 */
class Dataface_DeleteForm extends HTML_QuickForm {
	
	var $_tablename;
	var $_db;
	var $_query;
	var $_table;
	var $_isBuilt;
	
	
	function Dataface_DeleteForm( $tablename, $db, $query){
		$this->_tablename = $tablename;
		$this->_table =& Dataface_Table::loadTable($tablename);
		$this->_db = $db;
		$this->_query = $query;
		$this->_isBuilt = false;
		
		if ( !isset( $this->_query['-cursor'] ) ){
			$this->_query['-cursor'] = 0;
		}
		
		parent::HTML_QuickForm('deleteForm');
		
	
	}
	
	function _build(){
		
		if ( !$this->_isBuilt ){
			$b = new Dataface_QueryBuilder($this->_tablename, $this->_query);
		
			$this->addElement('hidden','-action');
			$this->setConstants( array( "-action"=>'delete') );
			$this->_isBuilt = true;
			if ( isset( $this->_query['-delete-one'] ) ){
				$this->addElement('hidden','-delete-one');
				$this->setDefaults( array( '-delete-one'=>1) );
				$q = array('-skip'=>$this->_query['-cursor'], '-limit'=>1);
				$sql = $b->select('', $q);
				$res = mysql_query($sql, $this->_db);
				if ( !$res ){
					throw new Exception( df_translate('scripts.Dataface.DeleteForm._build.ERROR_TRYING_TO_FETCH',"Error trying to fetch element to be deleted.: ").mysql_error($this->_db), E_USER_ERROR);

				}
				if ( mysql_num_rows($res)==0 ) {
					throw new Exception( df_translate('scripts.Dataface.DeleteForm._build.ERROR_NO_RECORD_SELECTED',"No record is currently selected so no record can be deleted."), E_USER_ERROR);

				} else {
					$row = mysql_fetch_array($res);
					$fields =& $this->_table->keys();
					
					$keys = array_keys($fields);
					
					
					
					foreach ($keys as $key){
						$this->addElement('hidden',$key);
						$this->setDefaults(array( $key=>$row[$key]) );
						
					}
				}
			} else {
				
			
				foreach ($this->_query as $key=>$value){
					$this->addElement('hidden', $key);
					$this->setConstants(array($key=>$value));
				}
			}
			$this->removeElement('-submit');
			$this->addElement('submit','-submit',df_translate('scripts.Dataface.DeleteForm._build.LABEL_DELETE','Delete'), array('id'=>'delete_submit_button'));
			
			$this->addFormRule(array(&$this, 'checkPermissions'));
		}
	
	
	}
	
	function display(){
		$this->_build();
		$showform = true;
		$b = new Dataface_QueryBuilder($this->_tablename, $this->_query);
		if ( isset( $this->_query['-delete-one'] ) ){
			$q = array('-skip'=>$this->_query['-cursor'], '-limit'=>1);
			$sql = $b->select('', $q);
			$res = mysql_query($sql, $this->_db);
			if ( !$res ){
				throw new Exception( df_translate('scripts.Dataface.DeleteForm._build.ERROR_TRYING_TO_FETCH',"Error trying to fetch element to be deleted.: ").mysql_error($this->_db), E_USER_ERROR);

			}
			if ( mysql_num_rows($res)==0 ) {
				$msg = df_translate('scripts.Dataface.DeleteForm._build.ERROR_NO_RECORD_SELECTED',"No record is currently selected so no record can be deleted.");
				$showform = false;
			} else {
				$row = mysql_fetch_array($res);
				$rowRec = new Dataface_Record($this->_tablename, $row);
				$displayCol = $rowRec->getTitle();
				
				$msg = df_translate('scripts.Dataface.DeleteForm.display.ARE_YOU_SURE',"Are you sure you want to delete this record: &quot;$displayCol&quot;?",array('displayCol'=>$displayCol));
			}
			
		} else if ( isset($this->_query['-delete-found']) ) {
			$q = $b->select_num_rows();
			$res = mysql_query($q, $this->_db);
			if ( !$res ){
				throw new Exception( df_translate('scripts.Dataface.DeleteForm.display.ERROR_ESTIMATING',"Error estimating number of rows that will be deleted: "). mysql_error($this->_db), E_USER_ERROR);

			}
			
			list( $num ) = mysql_fetch_row($res);
			if ( $num <= 0 ){
				$msg = df_translate('scripts.Dataface.DeleteForm.display.ERROR_NO_RECORDS_FOUND',"There are no records in the current found set so no records can be deleted.");
				$showform = false;
			} else {
				$msg = df_translate('scripts.Dataface.DeleteForm.display.ARE_YOU_SURE_MULTIPLE',"Are you sure you want to delete the found records.  $num records will be deleted.",array('num'=>$num));
			}
		} else {
			$msg = df_translate('scripts.Dataface.DeleteForm.display.ERROR_GET_VARS',"Error: You must specify either '-delete-one' or '-delete-found' in GET vars.");
			$showform=false;
		}
		
		if ( $showform ){
			ob_start();
			parent::display();
			$form = ob_get_contents();
			ob_end_clean();
		} else {
			$form = '';
		}
		
		$context = array('msg'=>'foo'.$msg, 'form'=>$form);
		import('Dataface/SkinTool.php');
		$skinTool =& Dataface_SkinTool::getInstance();
		//$smarty = new Smarty;
		//$smarty->template_dir = $GLOBALS['Dataface_Globals_Templates'];
		//$smarty->compile_dir = $GLOBALS['Dataface_Globals_Templates_c'];
		//$smarty->assign($context);
		//$smarty->display('Dataface_DeleteForm.html');
		$skinTool->display($context, 'Dataface_DeleteForm.html');
			
			
			
	}
	
	function delete($values){
		require_once 'Dataface/IO.php';
		
		$query = $this->_buildDeleteQuery($values);
		if ( PEAR::isError($query) ) return $query;
		$io = new Dataface_IO($this->_tablename);
		
		$it =& df_get_records($this->_tablename, $query);
		$warnings = array();
		while ( $it->hasNext() ){
			$record =& $it->next();
			$res = $io->delete($record);
			if ( PEAR::isError($res) && Dataface_Error::isError($res) ){
				// this is a serious error... kill it
				return $res;
			} else if ( PEAR::isError($res) ){
				// this is a warning or a notice
				$warnings[] = $res;
			} 
			unset($record);
		}
		
		if ( count($warnings) > 0 ){
			return $warnings;
		}
		return true;
		
	
	}
	
	/**
	 * Builds the query that is used to delete records.
	 */
	function _buildDeleteQuery($values = array()){
		$query = array();
		if ( isset( $values['-delete-one'])  ){
			$keys = array_keys( $this->_table->keys() );
			foreach ($keys as $key){
				if ( !isset( $values[$key] ) ){
					return PEAR::raiseError(
						Dataface_LanguageTool::translate(
							/* i18n id */
							'Missing key while trying to delete record',
							/* default error message */
							'Attempt to delete single record when not all keys were specified.  Missing key \''.$key.'\'',
							/* i18n parameters */
							array('key'=>$key)
						),
						DATAFACE_E_MISSING_KEY
					);
				}
				$val = $values[$key];
				
				if ( $val{0} != '=' ) $val = '='.$val;
				$query[$key] = $val;
			}
		} else {
			$query['-limit'] = 9999;//isset($values['-limit']) ? $values['-limit'] : 1;
			$query['-skip'] = 0;//isset($values['-skip']) ? $values['-skip'] : 0;
			if ( isset( $values['-search'] ) ){
				$query['-search'] = $values['-search'];
			}
			if ( isset( $values['-sort'] ) ){
				$query['-sort'] = $values['-sort'];
			}
			foreach ($values as $key=>$value){
				if ( strpos($key, '-')===0 ) continue;
				$query[$key] = $value;
			}
		}
		
		return $query;
		
	}
	
	/**
	 * Validates the input to make sure that the delete can take place.
	 */
	function checkPermissions(){
		$errors = array();
		if ( $this->isSubmitted() ){
			$errCounter = 1;
			import('Dataface/PermissionsTool.php');
			import('dataface-public-api.php');
			$query = $this->_buildDeleteQuery($this->exportValues());
			if ( PEAR::isError($query) ){
				$errors[$errCounter++] = $query->getMessage();
			}
			$records =& df_get_records_array($this->_tablename, $query);
			if ( PEAR::isError($records) ){
				$errors[$errCounter++] = $query->getMessage();
				// we attach this error to the '-submit' field because I don't know how to attach it to the form.
			}
			if ( !is_array($records) ){
				$errors[$errCounter++] = df_translate('scripts.Dataface.DeleteForm.display.ERROR_NO_RECORDS_FOUND',"No records matched the query, so no records can be deleted.");	
			}
			 else {
				foreach ( array_keys($records) as $index ){
					if ( !Dataface_PermissionsTool::delete($records[$index]) ) {
						$errors[$errCounter++] = df_translate('scripts.Dataface.DeleteForm.checkPermissions.ERROR_PERMISSION_DENIED',"Permission Denied: You do not have permission to delete this record (".$records[$index]->getTitle().")",array('title'=>$records[$index]->getTitle()));
						// we attach this error to the '-submit' field because I don't know how to attach it to the form.
					}	
				}
			}
		}
		if ( count($errors) > 0 ) {
			
			return $errors;
		}
		return true;
		
		
	}
	
	function validate(){
		$this->_build();
		return parent::validate();
	}
		
		



}
