<?php
/**
 * @brief Action to export a record or a set of records in JSON format.
 *
 * This action takes the standard Xataface URL conventions to select records.
 *
 * @see <a href="http://xataface.com/wiki/URL_Conventions">Xataface URL Conventions</a>
 *
 * This action will always return a JSON array.  If no record is found then it will be an 
 * empty array.  If only one record is selected, then it will be an array with one item.
 * Etc...
 *
 * In addition to the standard Xataface URL conventions the following parameters may be used.
 *
 * <table>
 *	<tr>
 *		<th>Parameter</th><th>Description</th><th>Required</th><th>Default Value</th>
 *	</tr>
 *	<tr>
 *		<td>-mode</td>
 *		<td>Whether to retrieve the current set specified (i.e. based on -skip and -limit params)
 *			- or to return the current record (i.e. based on the -cursor param).  Possible values
 *			include:
 *			<ul>
 *				<li>list - To indicate a list.</li>
 *				<li>browse - To just get the current record.</li>
 *			</ul>
 *		</td>
 *		<td>No</td>
 *		<td>list</td>
 *	</tr>
 *	<tr>
 *		<td>--displayMethod</td>
 *		<td>The method to use to render the field content.  Xataface includes different 
 *			methods to get field values.  The most basic is Dataface_Record::val() which
 *			just returns the value as it is stored (e.g. dates are stored as an associative
 *			array, integers are ints, etc...  The next step up is Dataface_Record::display()
 *			which renders the value as a string, and obeys permissions.  The highest level
 * 			is htmlValue() that prepares output for HTML display.
 *		</td>
 *		<td>No</td>
 *		<td>val</td>
 *	</tr>
 *	<tr>
 *		<td>--fields</td>
 *		<td>A list of fields to be included separated by spaces.</td>
 *		<td>No</td>
 *		<td>All native and grafted fields in the table.  Does not include calculated fields.</td>
 *	</tr>
 * </table>
 *
 * @section export_json_permissions Permissions
 *
 * Both the 'view' and the 'export_json' permissions must be granted in order for the field
 * value to be included in output.  If the 'export_json' permission is not granted, the field
 * will be completely omitted.  If the export_json permission is granted but the view permission
 * is not granted, then the field will be displayed as 'NO ACCESS'.
 *
 * @section export_json_examples Examples
 *
 * Given a table "my_groups" with 3 records:
 *
 * @par With no parameters:
 *
 * URL: index.php?-table=my_groups&-action=export_json
 * Output:
 * @code
 * [
 *		{"group_id":"1","group_name":"shannah","access_level":"3"},
 *		{"group_id":"3","group_name":"FCAT Group Test","access_level":"3"},
 *		{"group_id":"4","group_name":"Faculty of Education","access_level":"3"}
 * ]
 * @endcode
 *
 * @par Using the --fields Parameter
 *
 * Using the --fields parameter to to only retrieve the group_id and group_name.
 * 
 * URL: index.php?-table=my_groups&-action=export_json&--fields=group_id%20group_name
 * Output:
 * @code
 * [
 *	{"group_id":"1","group_name":"shannah"},
 *	{"group_id":"3","group_name":"FCAT Group Test"},
 *	{"group_id":"4","group_name":"Faculty of Education"}
 * ]
 * @endcode
 *
 * @par Using the --displayMethod Parameter
 * 
 * Using the --displayMethod parameter to tell Xataface to render values with the 
 * Dataface_Record::display() method.  This will result in the access_level field displaying
 * the human readable value (because it uses a vocabulary) instead of the access level integer.
 *
 * URL: index.php?-table=my_groups&-action=export_json&--displayMethod=display
 * Output:
 * @code
 * [
 *	{"group_id":"1","group_name":"shannah","access_level":"Manager"},
 *	{"group_id":"3","group_name":"FCAT Group Test","access_level":"Manager"},
 *	{"group_id":"4","group_name":"Faculty of Education","access_level":"Manager"}
 * ]
 * @endcode
 *
 * 
 * @par Using the -mode Parameter
 * 
 * Using the -mode Parameter to cause only a single record to be returned based
 * on the -cursor parameter.
 *
 * URL: index.php?-table=my_groups&-action=export_json&-mode=browse&-cursor=1
 * Output:
 * @code
 * [{"group_id":"3","group_name":"FCAT Group Test","access_level":"3"}]
 * @endcode
 *
 * @par Using the -recordid Parameter
 *
 * Using the -recordid parameter (supported by Xataface) to select a single
 * record by the Xataface Record ID.
 *
 * URL: index.php?-table=my_groups&-action=export_json&-mode=browse&-recordid=my_groups%3Fgroup_id%3D3
 * Output: 
 * @code
 * [{"group_id":"3","group_name":"FCAT Group Test","access_level":"3"}]
 * @endcode
 *
 * @attention For this to work you also need to specify the -mode=browse attribute
 * so that Xataface knows that you intend to return the current record rather than the
 * full found set.  Otherwise it will return the set as if it has no search parameters.
 *
 * @section export_json_javascript_api Javascript Wrapper API
 *
 * @see <a href="http://xataface.com/dox/core/latest/jsdoc/symbols/xataface.IO.html#.load">xataface.IO.load()</a> javascript 
 * function for information on the javascript way of loading records from this action. 
 *
 */			
class dataface_actions_export_json {
	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		$query =& $app->getQuery();
		
		$records = df_get_selected_records($query);
		if ( !$records ){
			if ( $query['-mode'] == 'list' ){
				$records = df_get_records_array($query['-table'], $query);
			} else {
				$records = array( $app->getRecord() );
			}
		}
		
		$jsonProfile = 'basic';
		if ( @$query['--profile'] ){
		    $jsonProfile = $query['--profile'];
		}
		
		$displayMethod = 'val';
		if ( @$query['--displayMethod'] == 'display' ){
			$displayMethod = 'display';
		} else if ( @$query['--displayMethod'] == 'htmlValue' ){
			$displayMethod = 'htmlValue';
		}
		
		$out = array();
		if ( isset( $query['--fields'] ) ){
			$fields = explode(' ', $query['--fields']);
		} else {
			$fields = null;
		}
		
		
		foreach ($records as $record){
			if ( !$record->checkPermission('export_json')  ){
				continue;
			}
			
			$del = $record->table()->getDelegate();
			$row = null;
			if ( isset($del) and method_exists($del, 'export_json') ){
			    $row = $del->export_json($record, $jsonProfile, $records);
			} 
			if ( !isset($row) ){
			
                if ( !is_array($fields) ){
                    $fields = array_keys($record->table()->fields(false,true));
                }
                if ( is_array($fields) ){
                    $allowed_fields = array();
                    foreach ($fields as $field ){
                        if ( !$record->checkPermission('export_json', array('field'=>$field) ) ){
                            continue;
                        }
                        $allowed_fields[] = $field;
                    }
                } 
                
                $row = array();
                
                foreach ( $allowed_fields as $fld ){
                    $row[$fld] = $record->$displayMethod($fld);
                }
            }
            
            if ( isset($del) and method_exists($del, 'filter_json') ){
                $del->filter_json($record, $row, $jsonProfile, $records);
            }
            
			$out[] = $row;
		}
		
		if ( @$query['--single'] ){
			if ( count($out) > 0 ){
				$out = $out[0];
			} 
		}
		
		if ( @$query['--var'] ){
			$out = array(
				'code' => 200,
				$query['--var'] => $out
			);
			
			if ( @$query['--stats'] ){
			    $queryTool = Dataface_QueryTool::$lastIterated;
			    if ( isset($queryTool) ){
			        $out['metaData'] = array(
			            'limit' => $queryTool->limit(),
			            'found' => $queryTool->found(),
			            'skip' => $queryTool->start()
			        );
			    } else {
			        $out['metaData'] = array(
                        'limit' => count($records),
                        'skip' => 0,
                        'found' => count($records)
                    );
			    }
			}
		}
		
		//import('Services/JSON.php');
		//$json = new Services_JSON;
		$enc_out = json_encode($out);
		header('Content-type: application/json; charset='.$app->_conf['oe']);
		header('Connection: close');
		echo $enc_out;
		exit;
	}
}
