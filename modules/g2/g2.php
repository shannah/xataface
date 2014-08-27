<?php
class modules_g2 {
	/**
	 * @brief The base URL to the datepicker module.  This will be correct whether it is in the 
	 * application modules directory or the xataface modules directory.
	 *
	 * @see getBaseURL()
	 */
	private $baseURL = null;
	/**
	 * @brief Returns the base URL to this module's directory.  Useful for including
	 * Javascripts and CSS.
	 *
	 */
	public function getBaseURL(){
		if ( !isset($this->baseURL) ){
			$this->baseURL = Dataface_ModuleTool::getInstance()->getModuleURL(__FILE__);
		}
		return $this->baseURL;
	}
	
	
	public function __construct(){
		$app = Dataface_Application::getInstance();
		$s = DIRECTORY_SEPARATOR;
		
		if ( isset($app->version) and $app->version >= 2 ){
			$app->registerEventListener('SkinTool.ready', array($this, 'registerSkin'), true);
		} else {
			df_register_skin('g2', dirname(__FILE__).$s.'templates');
		}
		$app->registerEventListener('filterTemplateContext', array($this, 'filterTemplateContext'));
		$jt = Dataface_JavascriptTool::getInstance();
		$jt->ignore('jquery.packed.js');
		$jt->ignore('jquery-ui.min.js');
		$jt->ignore('xatajax.core.js');
		$jt->ignore('xatajax.util.js');
		$jt->ignore('xataface/lang.js');
		$jt->ignoreCss('jquery-ui/jquery-ui.css');
		$app->addHeadContent('<script src="'.htmlspecialchars(DATAFACE_URL.'/js/xataface/lang.js').'"></script>');
		$app->addHeadContent('<script src="'.htmlspecialchars(DATAFACE_URL.'/modules/XataJax/js/jquery.packed.js').'"></script>');
		$app->addHeadContent('<script src="'.htmlspecialchars(DATAFACE_URL.'/modules/XataJax/js/jquery-ui.min.js').'"></script>');
		$app->addHeadContent('<script src="'.htmlspecialchars(DATAFACE_URL.'/modules/XataJax/js/xatajax.core.js').'"></script>');
		$app->addHeadContent('<script src="'.htmlspecialchars(DATAFACE_URL.'/modules/XataJax/js/xatajax.util.js').'"></script>');
		$app->addHeadContent('<link rel="stylesheet" type="text/css" href="'.htmlspecialchars(DATAFACE_URL.'/modules/XataJax/css/jquery-ui/jquery-ui.css').'"/>');
		$app->addHeadContent('<link rel="stylesheet" type="text/css" href="'.htmlspecialchars($this->getBaseURL().'/css/xataface-global.css').'"/>');
		$jt->addPath(dirname(__FILE__).$s.'js', $this->getBaseURL().'/js');
		$ct = Dataface_CSSTool::getInstance();
		$ct->addPath(dirname(__FILE__).$s.'css', $this->getBaseURL().'/css');
		$jt->import('xataface/modules/g2/global.js');
		$app->addJSStrings(array(
		    'themes.g2.VIEW_SEARCH_RESULTS' => df_translate('themes.g2.VIEW_SEARCH_RESULTS', 'View Search Results'),
		    'themes.g2.SEARCH_RESULTS' => df_translate('themes.g2.SEARCH_RESULTS', 'Search Results')
		));
		
		// Let's create the actions for our tables.
		
		$app = Dataface_Application::getInstance();
		$order = -1000;
		$prefix = 'top_left_menu_actions_';
		$at = Dataface_ActionTool::getInstance();
		foreach ($app->_conf['_tables'] as $k=>$v){
			$nav = $app->getNavItem($k, $v);
			if ( $nav ){
				$action = array(
					'name' => $prefix.$k,
					'label' => $nav['label'],
					'description' => @$nav['description'],
					'selected' => @$nav['selected'],
					'url' => @$nav['href'],
					'category' => 'top_left_menu_bar',
					'order' => $order++
				);
				
				if ( @$nav['permission'] ){
					$action['permission'] = $nav['permission'];
				}
				if ( @$nav['condition'] ){
					$action['condition'] = $nav['condition'];
				}
				$at->addAction($prefix.$k, $action);
			}
		
		}
		
		
		
		
	}
	
	public function registerSkin(){
		$s = DIRECTORY_SEPARATOR;
		df_register_skin('g2', dirname(__FILE__).$s.'templates');
	}
	
	
	/**
	 * We just want this to run before the left column is rendered.. We're not using it for output
	 */
	public function block__before_left_column(){
		
		// Let's create the actions for our tables.
		
		$app = Dataface_Application::getInstance();
		$query = $app->getQuery();
		$order = -1000;
		$at = Dataface_ActionTool::getInstance();
		$table = Dataface_Table::loadTable($query['-table']);
		$search = $this->getCurrentSearch();
		if ( @$query['-search'] ) $search['-search'] = $query['-search'];
		
		$showAll = array(
			'name' => 'show_all_records',
			'label' => df_translate('actions.show_all_records.label', 'All '.$table->getLabel()),
			'description' => df_translate('actions.show_all_records.description', 'Show all records in '.$table->getLabel()),
			'selected' => !$search and (@$query['-mode'] == 'list'),
			'url' => DATAFACE_SITE_HREF.'?-table='.htmlspecialchars($table->tablename),
			'category' => 'table_quicklinks',
			'order' => -1000
		);
		$at->addAction('show_all_records', $showAll);
		
		// We want to get the last search results
		/*
		$thisSearch = $search;
		if ( !$search ){
			$thisSearch = @$_SESSION['xf_last_search_query'];
			if ( $thisSearch ){
				$thisSearch = @$thisSearch[$query['-table']];
			}
		} else {
			$_SESSION['xf_last_search_query'][$query['-table']] = $search;
		}
		
		if ( $thisSearch){
			
			$searchResults = array(
				'name' => 'search_results',
				'label' => 'Search Results',
				'description' => 'Search results',
				'selected' => $search,
				'url' => $app->url($thisSearch),
				'category' => 'table_quicklinks',
				'order' => -999
			);
			$at->addAction('search_results', $searchResults);
		}
		*/
				
		
		
		
		
	}
	
	public function filterTemplateContext($event){
		
		$event->context['G2'] = $this;
	}
	
	public function getSearchParameters(){
		
		$query = Dataface_Application::getInstance()->getQuery();
		$table = Dataface_Table::loadTable($query['-table']);
		if ( PEAR::isError($table) ){
			throw new Exception($table->getMessage(), $table->getCode());
		}
		$out = array();
		foreach ($query as $k=>$v){
			if ( $v and $table->hasField($k) ){
				$fld =& $table->getField($k);
				$out[$fld['widget']['label']] = $v;
				unset($fld);
			}
		}
		
		if ( @$query['-search'] ) $out['Keywords'] = $query['-search'];
		return $out;
	}
	
	public function getCurrentSearch(){
		
		$query = Dataface_Application::getInstance()->getQuery();
		$table = Dataface_Table::loadTable($query['-table']);
		if ( PEAR::isError($table) ){
			throw new Exception($table->getMessage(), $table->getCode());
		}
		$out = array();
		foreach ($query as $k=>$v){
			if ( $v and $table->hasField($k) ){
				$fld =& $table->getField($k);
				$out[$k] = $v;
				unset($fld);
			}
		}
		if ( @$query['-search']){
			$out['-search'] = $query['-search'];
		}
		return $out;
	}
	
	
	function block__head(){
		$app = Dataface_Application::getInstance();
		$query = $app->getQuery();
		$record = $app->getRecord();
		$out = array();
		$out[] = '<meta id="xf-meta-tablename" name="xf-tablename" content="'.htmlspecialchars($query['-table']).'"/>';
		
		if ( $record and $query['-mode'] == 'browse' ){
			$out[] = '<meta id="xf-meta-record-id" name="xf-record-id" content="'.htmlspecialchars($record->getId()).'"/>';
			$out[] = '<meta id="xf-meta-record-title" name="xf-record-title" content="'.htmlspecialchars($record->getTitle()).'"/>';
			
		} else if ($query['-mode'] == 'list' ){
			$currSearch = $this->getCurrentSearch();
			
			if ( $currSearch ){
				$out[] = '<meta id="xf-meta-search-query" name="xf-search-query" content="'.htmlspecialchars(http_build_query($currSearch)).'"/>';
				
			}
		}
		echo implode("\n", $out);
		
		
	}
	
	function get_property($key, $default=null){
		$app =& Dataface_Application::getInstance();
		if ( @$app->_conf['modules_g2'] and isset($app->_conf['modules_g2'][$key]) ){
			return $app->_conf['modules_g2'][$key];
		} else {
			return $default;
		}
	}
}