<?php
/**
 * This action searches through the related records of a particular
 * record and returns all matchines records.
 *
 * @author Steve Hannah <steve@weblite.ca>
 * @created March 12, 2008
 */


class dataface_actions_single_record_search {
	function handle(&$params){
		$app =& Dataface_Application::getInstance();
		$record =& $app->getRecord();
		$query =& $app->getQuery();
		if ( !isset($query['--subsearch']) ) $query['--subsearch'] = '';
		
		$results = array();
		
		foreach ( $record->_table->relationships() as $rname=>$r){
			$fields = $r->fields(true);
			$qstr = array();
			foreach ( $fields as $field ){
				//list($tname, $field) = explode('.', $field);
				$qstr[] = '`'.str_replace('`','',$field)."` LIKE '%".addslashes($query['--subsearch'])."%'";
			}
			$qstr = implode(' OR ', $qstr);
			$results[$rname] = $record->getRelatedRecordObjects($rname, 0, 10, $qstr);
			unset($r);
			unset($fields);
		}
		
		if ( @$query['--format'] == 'RSS2.0' ){
			$this->handleRSS($results);
		} else {
			df_display(array('results'=>&$results, 'queryString'=>$query['--subsearch']), 'Dataface_single_record_search.html');
		}
	}
	
	function handleRSS($results){
		$app =& Dataface_Application::getInstance();
		$record =& $app->getRecord();
		$query =& $app->getQuery();
		import('feedcreator.class.php');
		import('Dataface/FeedTool.php');
		$ft = new Dataface_FeedTool();
		$rss = new UniversalFeedCreator(); 
		$rss->encoding = $app->_conf['oe'];
		//$rss->useCached(); // use cached version if age<1 hour
		$del =& $record->_table->getDelegate();
		if ( !$del or !method_exists($del, 'getSingleRecordSearchFeed') ){
			$del =& $app->getDelegate();
		}
		if ( $del and method_exists($del, 'getSingleRecordSearchFeed') ){
			$feedInfo = $del->getSingleRecordSearchFeed($record, $query);
			if ( !$feedInfo ) $feedInfo = array();
		}
		if ( isset($feedInfo['title']) ) $rss->title = $feedInfo['title'];
		else $rss->title = $record->getTitle().'[ Search for "'.$query['--subsearch'].'"]';
		
		if ( isset($feedInfo['description']) ) $rss->description = $feedInfo['description'];
		else $rss->description = '';
		
		if ( isset($feedInfo['link']) ) $rss->link = $feedInfo['link'];
		else $rss->link = htmlentities(df_absolute_url($app->url('').'&--subsearch='.urlencode($query['--subsearch'])));
		
		$rss->syndicationURL = $rss->link;
		
		
		$records = array();
		
		foreach ($results as $result){
			foreach ($result as $rec){
				$records[] = $rec->toRecord();
				
			}
		}
		
		uasort($records, array($this, 'cmp_last_modified') );
		
		
		
		foreach ($records as $rec){
			if ( $rec->checkPermission('view') and $rec->checkPermission('view in rss') ){
				$rss->addItem($ft->createFeedItem($rec));
			}
				
		}
		if ( !$query['--subsearch'] ){
			$rss->addItem($ft->createFeedItem($record));
		}
		
		header("Content-Type: application/xml; charset=".$app->_conf['oe']);
		echo $rss->createFeed('RSS2.0');
		exit;
	}
	
	
	function cmp_last_modified($a,$b){
		$amod = $a->getLastModified();
		$bmod = $b->getLastModified();
		if ( $amod == $bmod ) return 0;
		else return ( ($amod < $bmod) ? 1:-1);
	}

}
