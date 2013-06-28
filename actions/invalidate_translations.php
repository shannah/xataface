<?php
/**
 * An action that marks a new canonical version for the current record and 
 * invalidates all of its translations so that they will be queued for 
 * retranslation.
 *
 * @author Steve Hannah <shannah@sfu.ca>
 * @created Jan. 17, 2007
 */
class dataface_actions_invalidate_translations {


	function handle(&$params){
	
		$app =& Dataface_Application::getInstance();
		if ( !isset($_POST['--confirm_invalidate']) ){
			return PEAR::raiseError("Cannot invalidate translations with a GET request.  Please provide the POST parameter '--confirm_invalidate'");
		}
		
		$record =& $app->getRecord();
		if ( !$record ){
			return PEAR::raiseError("Attempt to invalidate translations on null record.  No record could be found to match the query parameters.");
		}
		
		import('Dataface/TranslationTool.php');
		$tt = new Dataface_TranslationTool();
		$res = $tt->markNewCanonicalVersion($record, basename($app->_conf['default_language']));
		if ( PEAR::isError($res) ){
			return $res;
		}
		
		$query =& $app->getQuery();
		if ( isset($query['--redirect']) ){
			$app->redirect($query['--redirect'].'&--msg='.urlencode("Translations successfully invalidated."));
		} else {
			$app->redirect($record->getURL('-action=edit').'&--msg='.urlencode('Translations successfully invalidated.'));
		}
	}
}

?>
