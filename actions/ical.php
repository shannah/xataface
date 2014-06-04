<?php
/**
 * @file actions/ica.php
 *
 * An action to produce a iCal feed for the current result set.
 *
 * @author Stephane Mourey <stephane.mourey@impossible-exil.info>
 * @created June. 6, 2014
 * @licence http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
 *
 * @defgroup actions
 * Some action info
 */

class dataface_actions_ical {

	/**
	 * @ingroup actions
	 */
	function handle(&$params){
		import('Dataface/IcalTool.php');
		$app =& Dataface_Application::getInstance();
		$ft = new Dataface_IcalTool();


		$query = $app->getQuery();
		if ( @$query['-relationship'] ){
			$record =& $app->getRecord();
			$perms = $record->getPermissions(array('relationship'=>$query['-relationship']));
			if ( !@$perms['related records feed'] ) return Dataface_Error::permissionDenied('You don\'t have permission to view this relationship.');


		}

		header('Content-type: text/calendar; charset='.$app->_conf['oe']);
		/** May have to use the next line in a way or another to be compatible with Google Agenda
		 * @see http://stackoverflow.com/questions/1463480/how-can-i-use-php-to-dynamically-publish-an-ical-file-to-be-read-by-google-calen
		* header('Content-Disposition: inline; filename=calendar.ics'); */

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
			$format = 'ical';
		}
		echo $ft->getFeedIcal($query,$format);
		exit;
	}
}

?>
