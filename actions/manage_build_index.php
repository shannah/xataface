<?php

class dataface_actions_manage_build_index {
	function handle(&$params){
		session_write_close();
		set_time_limit(0);
		import('Dataface/Index.php');
		$index = new Dataface_Index();
		if ( @$_POST['--build-index'] ){
		
			
			if ( is_array($_POST['--tables']) ){
				$tables = $_POST['--tables'];
			} else if ( !empty($_POST['--tables']) ){
				$tables = array($_POST['--tables']);
			} else {
				$tables = null;
			}
			
			if ( @$_POST['--clear'] ){
				$clear = true;
			} else {
				$clear = false;
			}
			

			
			$index->buildIndex($tables, '*', $clear);
			$app =& Dataface_Application::getInstance();
			$this->redirect($app->url('').'&--msg='.urlencode('Successfully indexed database'));

		}
		
		$tables = array_keys(Dataface_Table::getTableModificationTimes());
		$count = 0;
		
		$indexable = array();
		foreach ( $tables as $key=>$table ){
			if ( preg_match('/^dataface__/', $table) ){
				continue;
			}
			if ( preg_match('/^_/', $table) ){
				continue;
			}
			
			if ( $index->isTableIndexable($table) ){
				$indexable[] = $table;
				//unset($tables[$key]);
			}
			
		}
		
		$tables = $indexable;
		
		df_display(array('tables'=>$tables), 'manage_build_index.html');
		
	}
}
