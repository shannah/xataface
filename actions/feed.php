<?php
/**
 * @file actions/feed.php
 *
 * An action to produce a feed for the current result set.  This will work with
 * RSS, Atom.
 *
 * @defgroup actions
 * Some action info
 */
 
class dataface_actions_feed {

	/**
	 * @ingroup actions
	 */
	function handle(&$params){
		import('Dataface/FeedTool.php');
		$app =& Dataface_Application::getInstance();
		$ft = new Dataface_FeedTool();
		
		
		$query = $app->getQuery();
		if ( @$query['-relationship'] ){
			$record =& $app->getRecord();
			$perms = $record->getPermissions(array('relationship'=>$query['-relationship']));
			if ( !@$perms['related records feed'] ) return Dataface_Error::permissionDenied('You don\'t have permission to view this relationship.');

		
		}
		
		header("Content-Type: application/xml; charset=".$app->_conf['oe']);
		$conf = $ft->getConfig();
		
		$query['-skip'] = 0;
		if ( !isset($query['-sort']) and !@$query['-relationship']){
			$table =& Dataface_Table::loadTable($query['-table']);
			$modifiedField = $table->getLastUpdatedField(true);
			if ( $modifiedField ){
				$query['-sort'] = $modifiedField.' desc';
			}
		}
		
		if ( !isset($query['-limit']) and !@$query['-relationship']){
			$default_limit = $conf['default_limit'];
			if ( !$default_limit ){
				$default_limit = 60;
			}
			$query['-limit'] = $default_limit;
		}
		
		if ( isset($query['--format']) ){
			$format = $query['--format'];
		} else {
			$format = 'RSS1.0';
		}
		echo $ft->getFeedXML($query,$format);
		exit;
	}
}

?>
