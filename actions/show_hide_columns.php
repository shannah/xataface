<?php
class dataface_actions_show_hide_columns {
	function handle($params){
		try {
			if ( @$_POST ){
				$this->do_post();
			} else {
				$this->do_get();
			}
		} catch ( Exception $ex){
			error_log(__FILE__.'['.__LINE__.']:'.$ex->getMessage());
			if ( @$_REQUEST['--format'] === 'json' ){
				if ( $ex->getCode() === 400 ){
					$this->json_out(array(
						'code' => 400,
						'message' => 'You don\'t have permission to hide and show columns.'
					));
				} else {
					$this->json_out(array(
						'code' => 500,
						'message' => 'An error occurred while updating the column preferences.  See server error log for details.'
						
					));
				}
			} else {
				if ( $ex->getCode() === 400 ){
					return Dataface_Error::permissionDenied();
				} else {
					throw $ex;
				}
			}
		}
	}
	
	
	function do_post(){
		if (!@$_POST['--data'] ){
			throw new Exception("No data received");
		}
		
		$data = json_decode($_POST['--data'], true);
		if ( !isset($data['fields']) ){
			throw new Exception("No fields specified");
		}
		$fields = $data['fields'];
		$app = Dataface_Application::getInstance();
		$query = $app->getQuery();
		$table_name = $query['-table'];
		$table = Dataface_Table::loadTable($table_name);
		
		$table_perms = $table->getPermissions();
		if ( !@$table_perms['show hide columns'] ){
			throw new Exception("You don't have permission to alter column visibility.");
		}
		$config_tool = Dataface_ConfigTool::getInstance();
		$user_config = $config_tool->loadUserConfig();
		$config_path = 'tables/'.$table_name.'/fields.ini';
		if ( !@$user_config->{$config_path} ){
			$user_config->{$config_path} = new StdClass;
		}
		$user_table_config = @$user_config->{$config_path};
		
		$visibilities = array(
			'visible',
			'hidden'
		);
		
		$opt_types = array('list', 'find', 'browse', 'csv', 'rss', 'xml');
		
		$errors = array();
	
		foreach ( $fields as $field_name => $field_opts ){
			if ( is_array($field_opts) ){
				if ( !isset($user_table_config->{$field_name}) ){
					$user_table_config->{$field_name} = new StdClass;
				}
				if ( !isset($user_table_config->{$field_name}->visibility) ){
					$user_table_config->{$field_name}->visibility = new StdClass;
				}
				$field_perms = $table->getPermissions(array('field'=>$field_name));
				if ( !@$field_perms['show hide columns'] ){
					$errors[] = 'You don\'t have permission to alter column visibility for field '.$field_name;
					continue;
				}
				$visibility_config = $user_table_config->{$field_name}->visibility;
				foreach ( $field_opts as $opt_type => $opt_visibility ){
					if ( !in_array($opt_visibility, $visibilities) ){
						$errors[] = 'Invalid visibility for field '.$field_name.'.  Expecting visible or hidden but received '.$opt_visibility.'.';
						continue;
					}
					if ( !in_array($opt_type, $opt_types) ){
						$errors[] = 'Invalid option type for field '.$field_name.'.  Expecting one of {'.implode(', ', $opt_types).'} but received '.$opt_type.'.';
						continue;
					}
					$visibility_config->{$opt_type} = $opt_visibility;
				}
			}	
		}
		
		$res = $config_tool->writeUserConfig();
		if ( !$res ){
			throw new Exception("Failed to save the user config for columns.");
		}
		
		if ( count($errors) === 0 ){
			$this->json_out(array(
				'code' => 200,
				'message' => 'Successfully saved settings.  Reload page to see effects.'
			));
		} else {
			$this->json_out(array(
				'code' => 201,
				'message' => 'Saved settings but with warnings.',
				'errors' => $errors
			));
		}
		
	}
	
	function do_get(){
		$app = Dataface_Application::getInstance();
		$query = $app->getQuery();
		$table_name = $query['-table'];
		$table = Dataface_Table::loadTable($table_name);
		if ( !class_exists('Dataface_AuthenticationTool') or !Dataface_AuthenticationTool::getInstance()->isLoggedIn() ){
			throw new Exception("You must be logged in to alter user preferences like showing and hiding columns.");
		}
		$table_perms = $table->getPermissions();
		if ( !@$table_perms['show hide columns'] ){
			throw new Exception("You don't have permission to alter column visibility.");
		}
		
		$fields = array();
		foreach ( $table->fields(false, true, true) as $field_name => $field_config ){
			$field_perms = $table->getPermissions(array('field' => $field_name));
			if ( !@$field_perms['show hide columns'] ){
				continue;
			}
			$fields[] = $field_config;
		}
		
		$visibility_types = array('list','browse','find');
		if ( isset($query['--visibility-types']) ){
			$visibility_types = explode(' ', $query['--visibility-types']);
		}
		
		$context = array(
			'fields' => $fields,
			'record' => $app->getRecord(),
			'table_name' => $table_name,
			'visibility_types' => $visibility_types,
			'self' => $this
		);
		
		Dataface_JavascriptTool::getInstance()
			->import('xataface/actions/show_hide_columns.js');
		
		if ( @$query['--format'] == 'iframe' ){
			df_display($context, 'xataface/actions/show_hide_columns_iframe.html');
		} else {
			df_display($context, 'xataface/actions/show_hide_columns_page.html');
		}
	}
	
	function is_checked($field_name, $visibility_type){
		$app = Dataface_Application::getInstance();
		$query = $app->getQuery();
		$table_name = $query['-table'];
		$table = Dataface_Table::loadTable($table_name);
		$field = $table->getField($field_name);
		if ( PEAR::isError($field) ){
			return false;
		}
		return @$field['visibility'][$visibility_type] !== 'hidden'; 
	}
	
	function json_out($data){
		header('Content-type: application/json; charset="'.Dataface_Application::getInstance()->_conf['oe'].'"');
		echo json_encode($data);
	}
}