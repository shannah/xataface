<?php
/**
 * Tool for helping to produce RSS feeds.
 *
 * @author Steve Hannah <shannah@sfu.ca>
 * @created Feb. 17, 2007
 *
 */
class Dataface_FeedTool {


	function buildFeedItemData(&$record){
		$delegate =& $record->_table->getDelegate();
		if ( isset($delegate) and method_exists($delegate, 'getFeedItem') ){
			$res = $delegate->getFeedItem($record);
		} else {
			$res = array();
		}
		if ( !isset($res['title']) ) $res['title'] = $this->getItemTitle($record);
		if ( !isset($res['description']) ) $res['description'] = $this->getItemDescription($record);
		if ( !isset($res['link']) ) $res['link'] = $this->getItemLink($record);
		if ( !isset($res['date']) ) $res['date'] = $this->getItemDate($record);
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
			if ( isset($delegate) and method_exists($delegate,'getRelatedFeed') ){
				$feed = $delegate->getRelatedFeed($record, $query['-relationship']);
			} else if ( isset($appDelegate) and method_exists($appDelegate, 'getRelatedFeed') ){
				$feed = $appDelegate->getRelatedFeed($record, $query['-relationship']);
			} else {
				$feed = array();
			}
			
			if ( !isset($feed['title']) ) $feed['title'] =$this->getRelatedTitle($record, $query['-relationship']);
			if ( !isset($feed['description']) )  $feed['description'] = $this->getRelatedDescription($record, $query['-relationship']);
			if ( !isset($feed['link']) ) $feed['link'] = $this->getRelatedLink($record, $query['-relationship']);
			if ( !isset($feed['syndicationURL']) ) $feed['syndicationURL'] = $this->getRelatedSyndicationURL($record, $query['-relationship']);
			
			return $feed;
			
		} else {
			
			if ( isset($delegate) and method_exists($delegate, 'getFeed') ){
				$feed = $delegate->getFeed($query);
			} else if ( isset($appDelegate) and method_exists($appDelegate,'getFeed') ){
				$feed = $appDelegate->getFeed($query);
			} else {
				$feed = array();
			}
			
			if ( !isset($feed['title']) ) $feed['title'] = $this->getTitle($query);
			if ( !isset($feed['description']) ) $feed['description'] = $this->getDescription($query);
			if ( !isset($feed['link']) ) $feed['link'] = $this->getLink($query);
			if ( !isset($feed['syndicationURL']) ) $feed['syndicationURL'] = $this->getSyndicationURL($query);
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
	function getSyndicationURL($query){
		$url = $this->getParsedConfigSetting('syndicationURL', $query);
		if ( !$url ) $url = $this->getParsedConfigSetting('link', $query);
		return $url;
	}
	function getRelatedSyndicationURL($record, $relationshipName){
		return $record->getURL('-relationship='.urlencode($relationshipName).'&-action=related_records_list');
	}
	
	function getItemLink(&$record){
		return $record->getPublicLink();
	}
	
	function getItemDescription(&$record){
		$delegate =& $record->_table->getDelegate();
		if ( isset($delegate) and method_exists($delegate, 'getRSSDescription') ){
			return $delegate->getRSSDescription($record);
		} else {
			$out = '<table><thead><tr><th>Field</th><th>Value</th></tr></thead>';
			$out .= '<tbody>';
			foreach ( $record->_table->fields() as $field){
				if ( !$record->checkPermission('view') ) continue;
				if ( @$field['visibility']['feed'] == 'hidden' ) continue;
				if ( $disp = @$record->val($field['name']) ){
					$out .= '<tr><td valign="top">'.df_escape($field['widget']['label']).'</td>';
					$out .= '<td valign="top">'.@$record->htmlValue($field['name']).'</td></tr>';
				}
			}
			$out .= '</tbody></table>';
			return $out;
		
		}
		
		//return $record->getDescription();
	}
	
	function getItemTitle(&$record){
		return $record->getTitle();
	}
	
	function getItemDate(&$record){
		$mod = $record->getLastModified();
		if ( !$mod ){
			$mod = $record->getCreated();
		}
		return $mod;
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
	
	function createFeedItem(&$record){
		$data = $this->buildFeedItemData($record);
		$item = new FeedItem(); 
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
		if ( !isset($app->_conf['_feed']) ){
			$app->_conf['_feed'] = array();
		}
		return $app->_conf['_feed'];
	}
	
	function createFeed($query=null){
		import('feedcreator.class.php');
		$app =& Dataface_Application::getInstance();
		if ( !isset($query) ){
			$query = $app->getQuery();
		}
		$feed_data = $this->buildFeedData($query);
		
		$rss = new UniversalFeedCreator(); 
		$rss->encoding = $app->_conf['oe'];
		//$rss->useCached(); // use cached version if age<1 hour
		$rss->title = $feed_data['title']; 
		$rss->description = $feed_data['description'];
		
		//optional
		//$rss->descriptionTruncSize = 500;
		//$rss->descriptionHtmlSyndicated = true;
		
		$rss->link = htmlentities($feed_data['link']);
		$rss->syndicationURL = htmlentities($feed_data['syndicationURL']);
		
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
			if ( !$record->checkPermission('view in rss') ) continue;
			$item = $this->createFeedItem($record);
			$del =& $record->_table->getDelegate();
			if ( isset($del) and method_exists($del, 'canAddToFeed') and !$del->canAddToFeed($record, $rss) ){
				unset($del);
				continue;
			}
			unset($del);
			$rss->addItem($item);
			unset($item);
		}
		
		return $rss;

	}
	
	function getFeedXML($query, $format='RSS2.0'){
		$feed = $this->createFeed($query);
		return $feed->createFeed($format);
	}	
	
	

}
