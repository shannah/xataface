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
		$errors = array();
		$visibilities = array(
			'visible',
			'hidden'
		);
		
		$opt_types = array('list', 'find', 'browse', 'csv', 'rss', 'xml');
		
		if ( isset($data['fields']) ){
		
			$fields = $data['fields'];
			$config_path = 'tables/'.$table_name.'/fields.ini';
			if ( !@$user_config->{$config_path} ){
				$user_config->{$config_path} = new StdClass;
			}
			$user_table_config = @$user_config->{$config_path};
			
			
			
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
		}
		
		// Now deal with the relationships
		if ( isset($data['relationships']) ){
			foreach ( $data['relationships'] as $relationship_data ){
				$config_path = 'tables/'.$table_name.'/relationships.ini';
				if ( !@$user_config->{$config_path} ){
					$user_config->{$config_path} = new StdClass;
				}
				$user_table_config = @$user_config->{$config_path};
				
				if ( isset($relationship_data['fields']) ){
					$relationship_name = $relationship_data['name'];
					if ( !$relationship_name ){
						throw new Exception("Expected name for relationship but did not receive one.");
						continue;
					}
					$relationship = $table->getRelationship($relationship_name);
					if ( PEAR::isError($relationship) or !isset($relationship) ){
						throw new Exception("Relationship ".$relationship_name." does not exist.");
					}
					foreach ( $relationship_data['fields'] as $field_name => $field_opts ){
						list($r_name, $r_field_name) = explode('.', $field_name);
						if ( $r_name !== $relationship_name ){
							throw new Exception("Relationship fields must have same root name as the relationship itself.");
							continue;
						}
						if ( !$relationship->hasField($r_field_name, true) ){
							throw new Exception("Relationship ".$relationship_name." has no such field ".$r_field_name);
						}
						
						if ( !isset($user_table_config->{$field_name}) ){
							$user_table_config->{$field_name} = new StdClass;
						}
						if ( !isset($user_table_config->{$field_name}->visibility) ){
							$user_table_config->{$field_name}->visibility = new StdClass;
						}
						$field_perms = $relationship->getPermissions(array('field'=>$r_field_name));
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
		if ( !@$query['--hide-local-fields'] ){
			foreach ( $table->fields(false, true, true) as $field_name => $field_config ){
				$field_perms = $table->getPermissions(array('field' => $field_name));
				if ( !@$field_perms['show hide columns'] ){
					continue;
				}
				$fields[] = $field_config;
			}
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
		
		if ( @$query['--relationships'] ){
			$relationships = array();
			$rnames = array();
			if ( $query['--relationships'] == '*' ){
				// it's all relationships
				$rnames = array_keys($table->relationships());
			} else {
				$rnames = explode(' ', $query['--relationships']);
			}
			
			foreach ( $rnames as $relationship_name ){
				$rperms = $table->getPermissions(array('relationship' => $relationship_name));
				if ( !@$rperms['show hide columns'] ){
					continue;
				}
				$relationship_fields = array();
				$relationship = $table->getRelationship($relationship_name);
				foreach ( $relationship->fields(true) as $field_name ){
					$fperms = $relationship->getPermissions(array('field' => $field_name));
					if ( !@$fperms['show hide columns'] ){
						continue;
					}
					$field_def = $relationship->getField($field_name);
					$relationship_fields[$relationship_name.'.'.$field_def['name']] = $field_def;
				}
				$relationships[] = array(
					'name' => $relationship_name,
					'label' => $relationship->getLabel(),
					'fields' => $relationship_fields
				);
			}
			$context['relationships'] = $relationships;
		}
		
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