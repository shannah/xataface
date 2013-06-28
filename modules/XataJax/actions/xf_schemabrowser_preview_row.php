<?php
/*
 * Xataface Schema Browser Widget
 * Copyright (C) 2012  Steve Hannah <steve@weblite.ca>
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Library General Public
 * License as published by the Free Software Foundation; either
 * version 2 of the License, or (at your option) any later version.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Library General Public License for more details.
 * 
 * You should have received a copy of the GNU Library General Public
 * License along with this library; if not, write to the
 * Free Software Foundation, Inc., 51 Franklin St, Fifth Floor,
 * Boston, MA  02110-1301, USA.
 *
 */
 /**
 * @brief HTTP handler to preview the data for a particular row of a table.
 *
 * This is used by the field browser to show hints to the user of what data is stored
 * in each field.
 *
 * @see @ref insertingfield
 *
 * @section permissions Permissions
 *
 * In order to view a row, the user must be granted both the @e view and <em>export_json</em>
 * permissions for the record/row that is being requested.  If they are not, then an error
 * will be returned instead of the usual output.
 *
 *
 * @section preview_report_rest_api REST API
 *
 * @subsection post_parameters POST Parameters
 *
 * <table>
 * <tr><th>Parameter Name</th><th>Parameter Description</th><th>Example Input</th></tr>
 * <tr>
 *	<td>@c -table</td>
 *	<td>The name of the table for which to return the schema</td>
 *  <td>transactions</td>
 * </tr>
 * <tr>
 *	<td>@c -action=xf_schemabrowser_preview_row</td>
 *	<td>Specifies this action</td>
 *  <td>N/A</td>
 * </tr>
 * </table>
 *
 * @note Any request parameter conforming to 
 * <a href="http://xataface.com/wiki/URL_Conventions">Xataface's URL conventions</a>
 * may be used to  help specify the result set that should be returned.
 *
 * @subsection returntype Return Type
 * 
 * This handler will return a text/json response containing a JSON data structure:
 * @code
 * {
 *    "code":200,
 *    "values":{
 *       "{$tool_id}":"1",
 *       "{$facility_id}":"Nanofabrication",
 *       "{$tool_name}":"Clean Room",
 *       "{$external_url}":"",
 *       "{$short_name}":"",
 *       "{$tool_type_id}":"Clean Room Access"
 *    }
 * }
 * @endcode
 *
 * @subsection erroroutput Error Return Type
 *
 * If an error occurs, then this action will simply return a JSON data structure with the 
 * following keys:
 * 
 * -# @b code - The Error code
 * -# @b message - The error message
 */
class actions_xf_schemabrowser_preview_row {
	function handle($params){
	
		$app = Dataface_Application::getInstance();
		try {
			$record = $app->getRecord();
			
			if ( !$record ){
				throw new Exception("No record found.", 404);
				
			}
			
			if (!$record->checkPermission('view') or !$record->checkPermission('export_json') ){
				throw new Exception("You don't have permission to perform this function.", 400);
			}
			
			$out = array();
			$table = $record->table();
			foreach ($table->fields(false, true) as $fld){
				$out['{$'.$fld['name'].'}'] = $record->htmlValue($fld['name']);
			}
			
			foreach ($table->delegateFields() as $fld){
				$out['{$'.$fld['name'].'}'] = $record->htmlValue($fld['name']);
			}
			
			foreach ($table->relationships() as $rname=>$r){
				foreach ($r->fields(true) as $fullField){
					$fld = $r->getField($fullField);
					$fldPath = $rname.'.'.$fld['name'];
					//echo $fldPath;
					$out['{$'.$fldPath.'}'] = $record->htmlValue($fldPath);
				}
			}
			
			$this->out(array(
				'code'=>200,
				'values'=>$out
			));
			exit;
			
			
		} catch (Exception $ex){
			$this->out(array(
				'code'=>$ex->getCode(),
				'message'=>$ex->getMessage()
			));
			exit;
		
		}
		
		
		
		
		
	}
	
	function out($params){
		header('Content-type:text/json; charset="'.Dataface_Application::getInstance()->_conf['oe'].'"');
		echo json_encode($params);
	}
}
