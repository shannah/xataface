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
import(XFROOT.'Dataface/LanguageTool.php');
 
/**
 * A tool to manage actions within the application.
 */
class Dataface_ActionTool {

	//var $_actionsConfig;
	var $actions=array();
	var $tableActions=array();
	
	function __construct($conf=null){
		if ( $conf === null ){
			$this->_loadActionsINIFile(/*DATAFACE_PATH."/actions.ini"*/);
			//$this->_loadActionsINIFile(DATAFACE_SITE_PATH."/actions.ini");
		} else {
			$this->actions =& $conf;
		}
	
	}
		function Dataface_ActionTool($conf=null) { self::__construct($conf); }
	
	
	
	function _loadActionsINIFile(/*$path*/){
		
		import(XFROOT.'Dataface/ConfigTool.php');
		$configTool =& Dataface_ConfigTool::getInstance();
		$actions =& $configTool->loadConfig('actions', null);
		foreach ( array_keys($actions) as $key){
			$action =& $actions[$key];
			$action['name'] = $key;
			if ( !isset($action['order']) ) $action['order'] = 0;
			if ( !isset($action['id']) ) $action['id'] = $action['name'];
			if ( !isset($action['label']) ) $action['label'] = str_replace('_',' ',ucfirst($action['name']));
			if ( !isset($action['accessKey'])) $action['accessKey'] = substr($action['name'],0,1);
			//if ( !isset($action['label_i18n']) ) $action['label_i18n'] = 'action:'.$action['name'].' label';
			//if ( !isset($action['description_i18n'])) $action['description_i18n'] = 'action:'.$action['name'].' description';
			
			if ( isset($action['description']) ){
				$action['description'] = df_translate('actions.'.$action['name'].'.description', $action['description']);
			}
			if ( isset($action['label']) ){
				$action['label'] = df_translate('actions.'.$action['name'].'.label',$action['label']);
			}
			
			$this->actions[$key] =& $action;
			unset($action);
		}
		unset($temp);
		$this->actions =& $actions;
		
	}
	
	function _loadTableActions($tablename){
		import(XFROOT.'Dataface/Table.php');
		// Some actions are loaded from the table's actions.ini file and must be loaded before we return the actions.

		$table =& Dataface_Table::loadTable($tablename);
		if ( !$table->_actionsLoaded ){
			$params = array();
			$table->getActions($params);
		}
	}
	
	/**
	 * Returns a specified action without evaluating the permissions or condition fields.
	 * @param $params Associative array:
	 *			Options:  name => The name of the action to retrieve
	 *					  table => The name of the table on which the action is defined.
	 *  @returns Action associative array.
	 */
	function &getAction($params, $action=null){
		$app =& Dataface_Application::getInstance();
		$actions =& $this->actions;
		if ( !isset($action) ){
			if ( @$params['table'] ){
				$this->_loadTableActions($params['table']);
				unset($actions);
				if ( !isset($this->tableActions[$params['table']]) ){
					$this->tableActions[$params['table']] = array();
				}
				$actions =& $this->tableActions[$params['table']];
				
			}
			
			if ( !isset($params['name']) or !$params['name'] ){
				throw new Exception("ActionTool::getAction() requires 'name' parameter to be specified.", E_USER_ERROR);
			}
			if ( !isset( $actions[$params['name']] ) ) {
				$err =  PEAR::raiseError(
					Dataface_LanguageTool::translate(
						"No action found", /* i18n id */
						"No action found named '".$params['name']."'", /*default error message*/
						array('name'=>$params['name']) 	/* i18n parameters */
					)
				);
				return $err;
			}
			
			
			$action = $actions[$params['name']];
		}
		
			
		if ( isset($action['selected_condition']) ) {
			$action['selected'] = $app->testCondition($action['selected_condition'], $params);
		}
		
		
		//if ( isset($action['visible']) and !$action['visible']) continue;
			// Filter based on a condition
		foreach (array_keys($action) as $attribute){
			// Some entries may have variables that need to be evaluated.  We use Dataface_Application::eval()
			// to evaluate these entries. The eval method will replace variables such as $site_url, $site_href
			// $dataface_url with the appropriate real values.  Also if $params['record'] contains a 
			// Record object or a related record object its values are treated as php variables that can be 
			// replaced.  For example if a Profile record has fields 'ProfileID' and 'ProfileName' with
			// ProfileID=10 and ProfileName = 'John Smith', then:
			// $app->parseString('ID is ${ProfileID} and Name is ${ProfileName}') === 'ID is 10 and Name is John Smith'
			if ( preg_match('/condition/i',$attribute) ) continue;
			if ( isset($action[$attribute.'_condition']) and !$app->testCondition($action[$attribute.'_condition'], $params) ){
				$action[$attribute] = null;
			} else {
				$action[$attribute] = $app->parseString($action[$attribute], $params);
			}
		}
		return $action;
		
	}
	
	function countActions($params=array(), $actions=null) {
		if (is_string($params)) {
			$params = array('category' => $params);
		}
		return count($this->getActions($params, $actions));
	}

	/**
	 * Returns an array of all actions as specified by $params.
	 * $params must be an array.  It may contain the following options:
	 *		record => A reference to a record for which the actions apply (This may be a related record)
	 *		table => The name of a table on which the actions apply.
	 *		relationship => The name of a relationship on which the action is applied. (requires that table also be set - or may use dotted name)
	 *						to include the table name and the relationship name in one string.
	 *		category => The name of the category of actions to be retrieved.
	 */
	function getActions($params=array(), $actions=null){
		if ( !is_array($params) ){
			trigger_error("In Dataface_ActionTool::getActions(), expected parameter to be an array but received a scalar: ".$params.".".Dataface_Error::printStackTrace(), E_USER_ERROR);
		}
        if (@$params['category']) {
            $cats = $params['category'];
            if (is_string($cats)) {
                $pos = strpos($cats, '|');
                if ($pos !== false) {
                    $cats = array_map('trim', explode('|', $cats));
                }
                
            }
            if (is_array($cats)) {
                $out = [];
                foreach ($cats as $cat) {
                    $params['category'] = $cat;
                    $out = array_merge($out, $this->getActions($params, $actions));
                }
                return $out;
            }
        }
        
		$app =& Dataface_Application::getInstance();
		
		$out = array();
		
		$tablename = null;
		if ( isset($params['table']) ) $tablename = $params['table'];
		if ( isset($params['record']) and is_a($params['record'], 'Dataface_Record') ) $tablename = $params['record']->_table->tablename;
		else if ( isset($params['record']) and is_a($params['record'], 'Dataface_RelatedRecord')) $tablename = $params['record']->_record->_table->tablename;
		
		if ( isset( $params['record'] ) && is_a($params['record'], 'Dataface_Record') ){
				// we have received a record as a parameter... we can infer the table information
			$params['table'] = $params['record']->_table->tablename;
		}  else if ( isset($params['record']) && is_a($params['record'], 'Dataface_RelatedRecord') ){
			// we have recieved a related record object... we can infer both the table and relationship information.
			$temp =& $params['record']->getParent();
			$params['table'] = $temp->_table->tablename;
			unset($temp);
			
			$params['relationship'] = $params['record']->_relationshipName;
		}
		
		if ( @$params['relationship']){
			if ( strpos($params['relationship'], '.') !== false ){
				// if the relationship is specified in the form 'Tablename.RElationshipname' parse it.
				list($params['table'],$params['relationship']) = explode('.', $params['relationship']);
			}
		}
		
        if (@$params['category'] == '__relationships__') {
            // Special case.  The __relationships__ category will get the table's relationship as actions.
            if (!$tablename) {
                $query = $app->getQuery();
                $tablename = $query['-table'];
            }
            $table = Dataface_Table::loadTable($tablename);
            if (PEAR::isError($table)) {
                throw new Exception("Cannot find table: ".$tablename);
            }
            return $table->getRelationshipsAsActions([]);
        }
        if ($tablename === null) {
            $tablename = $app->getQuery()['-table'];
        }
		if ( $tablename !== null ){
			// Some actions are loaded from the table's actions.ini file and must be loaded before we return the actions.
			$table =& Dataface_Table::loadTable($tablename);
			if ( !$table->_actionsLoaded ){
				$tparams = array();
				$table->getActions($tparams, true);
			}
            $tableExcludes = $table->getAttribute('actions.exclude');
            if (isset($tableExcludes)) {
                if (is_string($tableExcludes)) {
                    $tableExcludes = explode(' ', $tableExcludes);
                    $table->setAttribute('actions.exclude', $tableExcludes);
                }
                if (!@$params['exclude']) {
                    $params['exclude'] = $tableExcludes;
                } else {
                    if (is_string($params['exclude'])) {
                        $params['exclude'] = explode(' ', $params['exclude']);
                    }
                    $params['exclude'] = array_merge($params['exclude'], $tableExcludes);
                }
            }
			unset($table);
		}
		
		
		if ( $actions === null ){
			if ( @$params['table'] ){
				if ( !isset($this->tableActions[$params['table']]) ){
					$this->tableActions[$params['table']] = array();
				}
				$actions = $this->tableActions[$params['table']];
			}
			else $actions = $this->actions;
		}
        $excludes = null;
        if (@$params['exclude']) {
            $excludes = $params['exclude'];
            if (is_string($excludes)) {
                $excludes = explode(' ', $excludes);
            }
        }
		foreach ( array_keys($actions) as $key ){
			if ( isset($action) ) unset($action);
			$action = $actions[$key];
			$action['atts'] = array();
			if ($excludes and in_array($action['name'], $excludes)) {
			    continue;
			}
			if ( @$params['name'] and @$params['name'] !== @$action['name']) continue;
			if ( @$params['id'] and @$params['id'] !== @$action['id']) continue;
			if ( @$params['withtags']) {
			    if (!@$action['tags'] or strpos($action['tags'], $params['withtags']) === false) {
			        continue;
			    }
			}
            if (@$params['with']) {
                $missingKey = false;
                foreach (explode(' ', $params['with']) as $withKey) {
                    if (!@$action[$withKey]) {
                        $missingKey = true;
                        break;
                    }
                }
                if ($missingKey) {
                    continue;
                }
            }
			if (@$params['sanstags']) {
			    if (@$action['tags'] and strpos($action['tags'], $params['sanstags']) !== false) {
			        continue;
			    }
			}
			if ( isset($params['category'])  and $params['category'] !== @$action['category']) continue;
				// make sure that the category matches
			
			if ( @$params['table'] /*&& @$action['table']*/ && !(@$action['table'] == @$params['table'] or @in_array(@$params['table'], @$action['table']) )) continue;
				// Filter actions by table
				
			if ( @$params['relationship'] && @$action['relationship'] && @$action['relationship'] != @$params['relationship']) continue;
				// Filter actions by relationship.
				
			if ( @$action['condition'] and !$app->testCondition($action['condition'], $params) ) {
				continue;
			}
			if ( isset($params['record']) ){
				if ( isset($action['permission']) and !$params['record']->checkPermission($action['permission']) ){
					continue;
				}
			} else {
				if ( isset( $action['permission'] ) and !$app->checkPermission($action['permission'])){
					continue;
				}
			}
			if ( @$action['selected_condition'] ) $action['selected'] = $app->testCondition($action['selected_condition'], $params);
			else {
				$query = $app->getQuery();
				if ( @$action['name'] == @$query['-action'] ) $action['selected'] = true;
			}
			
			if ( isset($action['visible']) and !$action['visible']) continue;
				// Filter based on a condition
			foreach (array_keys($action) as $attribute){
				// Some entries may have variables that need to be evaluated.  We use Dataface_Application::eval()
				// to evaluate these entries. The eval method will replace variables such as $site_url, $site_href
				// $dataface_url with the appropriate real values.  Also if $params['record'] contains a 
				// Record object or a related record object its values are treated as php variables that can be 
				// replaced.  For example if a Profile record has fields 'ProfileID' and 'ProfileName' with
				// ProfileID=10 and ProfileName = 'John Smith', then:
				// $app->parseString('ID is ${ProfileID} and Name is ${ProfileName}') === 'ID is 10 and Name is John Smith'
				//if ( strpos($attribute, 'condition') !== false) continue;
				if ( strstr($attribute, '_condition') === '_condition') continue;
				if ( is_array($action[$attribute]) ) continue;
                if ($attribute === 'condition') continue;
				if ( isset($action[$attribute.'_condition']) and !$app->testCondition($action[$attribute.'_condition'], $params) ){

					$action[$attribute] = null;
				} else {
					$action[$attribute] = $app->parseString($action[$attribute], $params);
				}
				if ( strpos($attribute, 'atts:') === 0 ){
					$attAtt = substr($attribute, 5);
					if (strstr($attAtt, '_condition') !== '_condition') {
						$action['atts'][$attAtt] = $action[$attribute];
					}
				}
			}
            $i18nTable = @$params['table'];
            if (!$i18nTable) {
                if (!@$query) {
                    $query = $app->getQuery();
                }
                $i18nTable = $query['-table'];
            }
            
            $keyBase = 'tables.'.$i18nTable.'.actions.'.$action['name'].'.';
            $action['label'] = df_translate($keyBase.'label', @$action['label']);
            if (@$action['label_prefix']) {
                $action['label'] = $action['label_prefix'] . $action['label'];
            }
            if (@$action['label_suffix']) {
                $action['label'] = $action['label'] . $action['label_suffix'];
            }
            $action['description'] = df_translate($keyBase.'description', @$action['description']);
            $action['materialIcon'] = df_translate($keyBase.'materialIcon', @$action['materialIcon']);
            if (@$action['ajax'] and !@$action['ajax_action']) {
                $action['ajax_action'] = $action['name'];
            }
            if (@$action['ajax_action']) {
        		xf_script('xataface/actions/ajax_action_client.js');
                $removeClass = 'undefined';
                if (@$action['ajax.on']) $removeClass = '\''.$action['ajax.on'].'\'';
                $addClass = 'undefined';
                if (@$action['ajax.off']) $addClass = '\''.$action['ajax.off'].'\'';
                $action['onclick'] = 'xataface.post(\''.$action['ajax_action'].'\',this, '.$removeClass.', '.$addClass.')';
                $action['url'] = 'javascript:void(0)';
                
                
            }
			
			$cssClass = @$action['class'];
			if ($cssClass) {
				if (strpos($cssClass, ' ')) {
					$cssClassArray = explode(' ', $cssClass);
				} else {
					$cssClassArray = [$cssClass];
				}
				
				$classChanged = false;
				foreach ($cssClassArray as $k=>$cssClass) {

					if (isset($action['class.'.$cssClass.'_condition'])) {
						$cond = $action['class.'.$cssClass.'_condition'];
						if (!$app->testCondition($cond, $params)) {
							unset($cssClassArray[$k]);
							$classChanged = true;
						}
					}
				}
				if ($classChanged) {
					$action['class'] = trim(implode(' ', $cssClassArray));
				}
				
			}
            
			$out[$key] =& $action;
			
			unset($action);
		}

		uasort($out, array(&$this, '_compareActions'));
		return $out;
	}
	
	/**
	 * Comparison function used for sorting actions.
	 */
	function _compareActions($a,$b){
		if ( @$a['order'] < @$b['order'] ) return -1;
		else return 1;
	}
	
	/**
	 * Adds an action to the action tool.
	 * @param $name The name of the action.
	 * @param $action An array representing the action.
	 */
	function addAction($name, $action){
		if ( @$action['table'] ){
			$this->tableActions[$action['table']][$name] = $action;
			$query = Dataface_Application::getInstance()->getQuery();
			if ( $query['-table'] == $action['table'] ){
				// Note:  For some reason this needs to be passed by value
				$this->actions[$name] = $this->tableActions[$action['table']][$name];
			}
		}
		else{
			$this->actions[$name] = $action;
		}
	}
	
	/**
	 * Removes the action with the specified name.
	 */
	function removeAction($name){
		$action = $this->getAction($name);
		if ( @$action['table'] ){
			unset($this->tableActions[$action['table']][$name]);
		}
		unset( $this->actions[$name] );
	}
	
	/**
	 * Returns a reference to the singleton ActionTool instance.
	 * @param $conf Optional configuration array with action definitions.
	 */
	public static function &getInstance($conf=null){
		static $instance = 0;
		if ( !$instance ){
			$instance = new Dataface_ActionTool($conf);
		}
		return $instance;
	}
	

}
