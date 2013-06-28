<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2011 Web Lite Solutions Corp (steve@weblite.ca)
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
 *
 * Synopsis
 * ==========
 *
 * An action to insert a new record.
 *
 * Credits
 * ========
 *
 * @author Steve Hannah <steve@weblite.ca>
 * @created May 1, 2011
 *
 * Rest API:
 * ---------
 *
 * POST >
 *		-table 			: Name of table to insert record into
 * 		<colname>			: <colval>   (Values to insert into columns)
 *
 * Response >
 *		Content-type: text/json
 *		{
 *			code: <response code>
 *			message: <response message>
 *			record: <record vals>
 *
 *	Where:
 *		<response code> = Integer Response code.
 *			Values:
 *				200 = Success
 *				Anything else = Failure
 *
 *		<response message> = A string describing the result of the response.
 *		<record vals> = A JSON object with the resulting column values in the record.
 *
 */
define('REST_INSERT_VALIDATION_ERROR', 501);
class dataface_actions_rest_insert {
	function handle($params){
		if ( !defined('DISABLE_reCAPTCHA') ) define('DISABLE_reCAPTCHA', 1);
		import('Dataface/QuickForm.php');
		Dataface_QuickForm::$TRACK_SUBMIT = false;
		$app = Dataface_Application::getInstance();
		$query = $app->getQuery();
		$errors = null;
		
		
		try {
		
			if ( !@$_POST['-table'] ){
				throw new Exception("No table specified");
			}
			
			$table = $_POST['-table'];

			
			$rec = new Dataface_Record($table, array());
			$tableObj = $rec->_table;
			
			$fields = array();
			if ( !$rec->checkPermission('new') ){
				throw new Exception("Failed to insert record.  Permission denied");
			}
			foreach ($_POST as $k=>$v){
				if ( $k{0} == '-' ) continue;
				$fields[] = $k;
				$rec->setValue($k, $v);
				if ( !$rec->checkPermission('new', array('field'=>$k) ) ){
					throw new Exception(sprintf("Failed to insert record because you do not have permission to insert data into the %s column", $k));
				}
			}
			
			
			
			$form = df_create_new_record_form($table, $fields);
			$form->_flagSubmitted = true;
			$res = $form->validate();
			if ( !$res ){
				$errors = $form->_errors;
				throw new Exception('Validation error', REST_INSERT_VALIDATION_ERROR);
			}
			
			
			
			
			
			$res = $rec->save(null, true);
			if ( PEAR::isError($res) ){
				throw new Exception("Failed to insert record due to a server error: ".$res->getMessage(), 500);
			}
			
			$out = array();
			$vals = $rec->strvals();
			foreach ($vals as $k=>$v){
				if ( $rec->checkPermission('view') ){
					$out[$k] = $v;
				}
			}
			
			$this->out(array(
				'code'=>200,
				'message'=>'Record successfully inserted',
				'record'=>$out
			));
			exit;
				
			
		} catch (Exception $ex){
			$this->out(array(
				'code'=>$ex->getCode(),
				'message'=>$ex->getMessage(),
				'errors'=>$errors
			));
			exit;
		
		}
	}
	
	function out($params){
		header('Content-type: application/json; charset="'.Dataface_Application::getInstance()->_conf['oe'].'"');
		echo json_encode($params);
	}
}
