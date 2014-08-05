<?php
/**
 * Tool for helping to produce iCal feeds.
 *
 * @author Steve Hannah <shannah@sfu.ca>
 * @author Stephane Mourey <stephane.mourey@impossible-exil.info>
 * @created June. 6, 2014
 * @licence https://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License v2
 *
 */
class Dataface_IcalTool {


	function buildFeedItemData(&$record,$query=null){
		$app =& Dataface_Application::getInstance();

		$appDelegate =& $app->getDelegate();
		if ( !isset($query) ){

			$query = $app->getQuery();
		}


		import('Dataface/Ontology.php');
		Dataface_Ontology::registerType('Event', 'Dataface/Ontology/Event.php', 'Dataface_Ontology_Event');
		$ontology =& Dataface_Ontology::newOntology('Event', $query['-table']);
		$event = $ontology->newIndividual($record);

		$delegate =& $record->_table->getDelegate();
		if ( isset($delegate) and method_exists($delegate, 'getFeedItem') ){
			$res = $delegate->getFeedItem($record);
		} else {
			$res = array();
		}
		if ( !isset($res['title']) ) $res['title'] = $this->getItemTitle($record);
		if ( !isset($res['description']) ) $res['description'] = $this->getItemDescription($record);
		if ( !isset($res['link']) ) $res['link'] = $this->getItemLink($record);
		if ( !isset($res['date']) ) $res['date'] = $this->getItemDate($event,$ontology);
		if ( !isset($res['dateend']) ) $res['dateend'] = $this->getItemDateend($event,$ontology);
		if ( !isset($res['category']) ) $res['category'] = $this->getItemCategory($event,$ontology);
		if ( !isset($res['location']) ) $res['location'] = $this->getItemLocation($event,$ontology);
		if ( !isset($res['author']) ) $res['author'] = $this->getItemAuthor($record);
		if ( !isset($res['source']) ) $res['source'] = $this->getItemSource($record);

		return $res;

	}

	function buildFeedData($query=null){
		$app =& Dataface_Application::getInstance();

		$appDelegate =& $app->getDelegate();
		if ( !isset($query) ){

			$query = $app->getQuery();
		}
		$table =& Dataface_Table::loadTable($query['-table']);
		$delegate =& $table->getDelegate();

		if ( isset($query['-relationship']) ){
			// we are building a set of related records.
			$record =& $app->getRecord();
			if ( isset($delegate) and method_exists($delegate,'getRelatedIcal') ){
				$feed = $delegate->getRelatedIcal($record, $query['-relationship']);
			} else if ( isset($appDelegate) and method_exists($appDelegate, 'getRelatedIcal') ){
				$feed = $appDelegate->getRelatedIcal($record, $query['-relationship']);
			} else {
				$feed = array();
			}

			if ( !isset($feed['title']) ) $feed['title'] =$this->getRelatedTitle($record, $query['-relationship']);
			if ( !isset($feed['description']) )  $feed['description'] = $this->getRelatedDescription($record, $query['-relationship']);
			if ( !isset($feed['link']) ) $feed['link'] = $this->getRelatedLink($record, $query['-relationship']);

			return $feed;

		} else {

			if ( isset($delegate) and method_exists($delegate, 'getIcal') ){
				$feed = $delegate->getIcal($query);
			} else if ( isset($appDelegate) and method_exists($appDelegate,'getIcal') ){
				$feed = $appDelegate->getIcal($query);
			} else {
				$feed = array();
			}

			if ( !isset($feed['title']) ) $feed['title'] = $this->getTitle($query);
			if ( !isset($feed['description']) ) $feed['description'] = $this->getDescription($query);
			if ( !isset($feed['link']) ) $feed['link'] = $this->getLink($query);
			return $feed;


		}

	}

	function getParsedConfigSetting($name, $context=array()){
		$app =& Dataface_Application::getInstance();
		$conf =& $this->getConfig();
		if ( isset($conf[$name]) ) {
			return $app->parseString($conf[$name], $context);
		} else {
			return null;
		}
	}

	function getTitle($query){
		$title = $this->getParsedConfigSetting('title', $query);

		if ( !$title ){
			$table =& Dataface_Table::loadTable($query['-table']);
			$searchparams = preg_grep('/^-/', array_keys($query), PREG_GREP_INVERT);
			if ( count($searchparams) > 0 ){
				$temp = array();
				foreach ($searchparams as $key){
					$parts = explode('/', $key);
					if ( count($parts) > 1 and $table->hasRelationship($parts[0]) ){
						$temp[] = $parts[0].'/'.$parts[1].': '.$query[$key];
						continue;
					}
					if ( !$table->hasField($key) ) continue;
					$temp[] = $key.': '.$query[$key];
				}
				if ( count($temp) > 0 ){
					$search_params = '['.implode(', ', $temp).']';
				} else {
					$search_params = '';
				}
			} else {
				$search_params = '';
			}

			if ( @$query['-search'] ){
				$search_params = ' search for "'.$query['-search'].'" '.$search_params;
			}

			$app =& Dataface_Application::getInstance();


			$title= $app->getSiteTitle().' | '.$table->getLabel().' '.$search_params;
		}
		return $title;

	}
	function getRelatedTitle($record, $relationshipName){
		return ucwords($relationshipName)." of ".$record->getTitle();

	}
	function getDescription($query){
		$description = $this->getParsedConfigSetting('description', $query);
		if ( !$description ){
			$description = "Feed Description";
		}
	}
	function getRelatedDescription($record, $relationshipName){
		return "Related records for ".$record->getTitle();
	}
	function getLink($query){
		$link = $this->getParsedConfigSetting('link', $query);
		if ( !$link ){
			$app =& Dataface_Application::getInstance();
			if ( $query['-action'] == 'feed' ) $query['-action'] = 'list';
			$link = $app->url($query);
		}
		return $link;
	}
	function getRelatedLink($record, $relationshipName){
		return $record->getURL('-relationship='.urlencode($relationshipName).'&-action=related_records_list');
	}

	function getItemLink(&$record){
		return $record->getPublicLink();
	}

	function getItemDescription(&$record){
		$out="";
		$delegate =& $record->_table->getDelegate();
		if ( isset($delegate) and method_exists($delegate, 'getIcalDescription') ){
			return $delegate->getIcalDescription($record);
		} else {
			foreach ( $record->_table->fields() as $field){
				if ( !$record->checkPermission('view') ) continue;
				if ( @$field['visibility'] == 'hidden' ) continue;
				if ( @$field['visibility']['ical'] == 'hidden' ) continue;
				if ( $disp = @$record->val($field['name']) ){
					$out .= df_escape($field['widget']['label']).': ';
					$out .= @strip_tags($record->strval($field['name']))."\n";
				}
			}
			return $out;

		}

		//return $record->getDescription();
	}

	function getItemTitle(&$record){
		return $record->getTitle();
	}

	function getItemDate(&$event){
		$mod = $event->getValue('date');
		$mod2 = $event->getValue('start');
		if ($mod2 && $mod2 != $mod) return $mod2;
		return $mod;
	}

	function getItemDateend(&$event){
		return $event->getValue('end');
	}

	function getItemLocation(&$event){
		return $event->getValue('location');
	}

	function getItemCategory(&$event){
		return $event->getValue('category');
	}

	function getItemAuthor(&$record){
		$creator = $record->getCreator();
		if ( !$creator ){
			$creator = $this->getParsedConfigSetting('default_author');
		}
		if ( !$creator ) $creator = "Site administrator";
		return $creator;
	}

	function getItemSource(&$record){
		$delegate =& $record->_table->getDelegate();
		if ( isset($delegate) && method_exists($delegate, 'getFeedSource') ){
			return $delegate->getFeedSource($record);
		}
		$conf =& $this->getConfig();
		if ( isset($conf['source']) ){
			$app =& Dataface_Application::getInstance();
			return $app->parseString($conf['source'], $record);
		}
		return $_SERVER['HOST_URI'].DATAFACE_SITE_HREF;
	}

	function createFeedItem(&$record,$query=null){
		$data = $this->buildFeedItemData($record,$query);
		$item = new StdClass();
		$item->title = $data['title'];
		$item->link = $data['link'];
		$item->description = $data['description'];
		//optional
		//item->descriptionTruncSize = 500;
		//item->descriptionHtmlSyndicated = true;

		$item->date = $data['date'];

		$item->source = $data['source'];
		$item->author = $data['author'];
		return $item;
	}


	function &getConfig(){
		$app =& Dataface_Application::getInstance();
		if ( !isset($app->_conf['_ical']) ){
			$app->_conf['_ical'] = array();
		}
		return $app->_conf['_ical'];
	}

	function createFeed($query=null){
		$app =& Dataface_Application::getInstance();
		if ( !isset($query) ){
			$query = $app->getQuery();
		}
		$feed_data = $this->buildFeedData($query);

		import('iCalcreator-2.20.2/iCalcreator.class.php');

		$tz = isset($app->_conf['_ical']['timezone']) ? $app->_conf['_ical']['timezone'] : date_default_timezone_get();
		$icalConfig = array (
			"unique_id" => $feed_data['link'],
			"TZID" => $tz
		);

		$ical = new vcalendar( $icalConfig );
		$ical->setProperty( "method", "PUBLISH" );
		$ical->setProperty( "x-wr-calname", $feed_data['title'] );
		$ical->setProperty( "X-WR-CALDESC", $feed_data['description'] );
		$ical->setProperty( "X-WR-TIMEZONE", $tz );
		$xprops = array( "X-LIC-LOCATION" => $tz );
		iCalUtilityFunctions::createTimezone( $ical, $tz, $xprops );

		$ical->encoding = $app->_conf['oe'];

		if ( isset($query['-relationship']) ){
			// Do the related records thing
			$record =& $app->getRecord();
			$query['-related:start'] = 0;
			$rrecords =& df_get_related_records(array_merge($query, array('-related:limit'=>1))); //$record->getRelatedRecordObjects($query['-relationship'], 0,1);
			if ( count($rrecords) > 0 ){
				$testRec =& $rrecords[0]->toRecord();
				$lastUpdatedColumn = $testRec->_table->getLastUpdatedField();
				if ( $lastUpdatedColumn ){
					unset($rrecords);
					$query['-related:limit'] = 30;
					$query['-related:sort'] = $lastUpdatedColumn.' desc';
					$rrecords =& df_get_related_records($query);
					//$record->getRelatedRecordObjects($query['-relationship'], null,null, 0, $lastUpdatedColumn.' desc');

				} else {
					unset($rrecords);
					$query['-related:limit'] = 30;
					$rrecords =& df_get_related_records($query); //$record->getRelatedRecordObjects($query['-relationship']);

				}
				$records = array();
				foreach ($rrecords as $rrec){

					$dfRecord =& $rrec->toRecord();
					if ( $dfRecord->checkPermission('view', array('recordmask'=>array('view'=>1)  ) ) ){
						$records[] =& $dfRecord;
					}
					unset($dfRecord);
					unset($rrec);
				}
			} else {
				$records = array();
			}
			//trigger_error("Not implemented yet for related records", E_USER_ERROR);
		} else {
			$records =& df_get_records_array($query['-table'], $query);

		}


		foreach ($records as $record){
			if ( !$record->checkPermission('view') ) continue;
			if ( !$record->checkPermission('view in ical') ) continue;
			$item = $this->createFeedItem($record,$query);
			$del =& $record->_table->getDelegate();
			if ( isset($del) and method_exists($del, 'canAddToIcal') and !$del->canAddToIcal($record, $ical) ){
				unset($del);
				continue;
			}
			unset($del);
			$this->addItem($ical,$item);
			unset($item);
		}

		return $ical;

	}

	function addItem(&$ical,$item){
		$vevent = & $ical->newComponent("vevent");
		if (!array_key_exists('hours',$item)) $params['VALUE'] = "DATE";
		$vevent->setProperty( "dtstart", $item->date['year'], $item->date['month'], $item->date['day'], $item->date['hours'], $item->date['minutes'],$item->date['seconds'], $params);
		if (property_exists($item,"dateend")){
			$end = array(
				'year' => $item->dateend['year'],
				'month' => $item->dateend['month'],
				'day' => $item->dateend['day'],
			);
			if (array_key_exists('hours',$item)) $end['hour'] = $item->dateend['hours'];
			if (array_key_exists('minutes',$item)) $end['min'] = $item->dateend['minutes'];
			if (array_key_exists('seconds',$item)) $end['sec'] = $item->dateend['seconds'];
			$vevent->setProperty( "dtend", $end );
		}
		if (property_exists($item,"location")){
			$vevent->setProperty("LOCATION",$item->location);
		}
		$vevent->setProperty("summary",$item->title);
		$vevent->setProperty("description",$item->description);
	}

	function getFeedIcal($query, $format='ical'){
		$feed = $this->createFeed($query);
		return $feed->returnCalendar();
	}



}
