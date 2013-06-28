<?php
class dataface_actions_search_index {
	
	function handle(&$params){
		import('Dataface/Index.php');
		$app =& Dataface_Application::getInstance();
		$q = $app->getQuery();
		if ( @$q['table'] ) $q['-table'] = $q['table'];
		else unset($q['-table']);

		$index = new Dataface_Index();
		$res = $index->find($q, true);
		
		$results =& $res['results'];
		
		foreach ($results as $id=>$result){
			$width = intval((floatval($result['relevance'])/10.0)*30.0);
			$results[$id]['relevance_bar'] = '<div style="border:1px solid #ccc; padding:1px margin:1px; background-color: #eaeaea; width:32px; height: 5px;"><div style="border: none; background-color: green; width: '.$width.'px; height:5px;"></div></div>';
		}
		
		
		df_display(array('results'=>&$results, 'metadata'=>&$res['metadata'], 'search_term'=>$q['-search']), 'Dataface_Search_Results.html');
	}

}
