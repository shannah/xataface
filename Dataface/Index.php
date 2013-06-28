<?php
/**
 * A class that indexes records in a dataface application.
 *
 * Usage:
 *
 * <h2>Building entire index</h2>
 * <code>
 * $index = new Dataface_Index();
 * 
 * $index->buildIndex(); // Clears the index and builds it anew
 * $index->buildIndex(array('people'));
 *		// indexes records in the people table only (but erases entire index
 * $index->buildIndex(array('people'), '*',false);
 *		// indexes reords in the people table for all languages, but doesn't
 *		// erase existing index for other tables.
 * 
 * </code>
 * <h2>Indexing single record</h2>
 * <code>
 * // Suppose we already have a record that we want to index.
 * $record =& df_get_record_by_id('people?personid=10');
 * $index->indexRecord($record);	// indexes record in all languages.
 * $index->indexRecord($record, null);  //indexes record in current language.
 * $index->indexRecord($record, 'en');  // indexes record in english only.
 * </code>
 *
 * <h2>Indexing based on a query or found set</h2>
 * <code>
 * $index->indexFoundRecords(array('-table'=>'people', 'personid'=>'>10'));
 * </code>
 *
 * <h2>Performing queries on the index</h2>
 * <code>
 * $results = $index->find(array('-search'=>"My search words"));
 * foreach ($results as $result){
 *		echo $result['record_id'];
 *		echo $result['record_url'];
 *		echo $result['record_title'];
 *		echo $result['record_description'];
 *		echo $result['relevance'];
 * }
 * </code>
 * 
 */
class Dataface_Index {

	/**
	 * Creates the index table.
	 */
	function createIndexTable(){
		$sql = "create table dataface__index (
			index_id int(11) not null auto_increment,
			`table` varchar(64) not null,
			record_id varchar(255) not null,
			record_url varchar(255) not null,
			record_title varchar(255) not null,
			record_description text,
			`lang` varchar(2) not null,
			`searchable_text` text,
			fulltext index `searchable_text_index` (`searchable_text`),
			unique key `record_key` (`record_id`,`lang`),
			primary key (`index_id`))";
		$res = mysql_query($sql, df_db());
		
		if ( !$res ){
			trigger_error(mysql_error(df_db()), E_USER_ERROR);
		}
	}
	
	
	/**
	 * Indexes a record so that it is searchable in the index.
	 * If the index table does not exist yet, this will create it.
	 * @param Dataface_Record &$record The record to be indexed.
	 * @param string $lang The 2-digit language code representing the language
	 *					   that this record is stored in.
	 */
	function indexRecord(&$record, $lang='*'){
		$app =& Dataface_Application::getInstance();
		if ( $lang == '*' ){
			// If the language specified is '*', that means we will
			// be indexing all languages.
			$this->indexRecord($record, $app->_conf['lang']);
			
			if ( is_array($app->_conf['languages']) ){
				import('Dataface/IO.php');
				$io = new Dataface_IO($record->_table->tablename);
				foreach ( array_keys($app->_conf['languages']) as $lang){
					if ( $lang == $app->_conf['lang'] ){
						continue;
					}
					$io->lang = $lang;
					$io->read($record->getId(), $record);
					$this->indexRecord($record, $lang);
				}
			}
			return true;
		}
		
		if ( !isset($lang) ) $lang = $app->_conf['lang'];
		
		$del =& $record->_table->getDelegate();
		if ( isset($del) and method_exists($del, 'getSearchableText') ){
			$searchable_text = $del->getSearchableText($record);
			if ( !is_string($searchable_text) ){
				// If this method returns anything other than a string, 
				// then we do not index the record... we just return false.
				return false;
			}
		} else {
			// The getSearchableText() method is not defined, so we will
			// just produce a concatenation of all text fields in the 
			// record and index those.
			$fields = $record->_table->getCharFields(true);
			$searchable_text = implode(', ', $record->strvals($fields));
		}
	
		$sql = "
			replace into dataface__index 
			(`record_id`,`table`,`record_url`,`record_title`,`record_description`,`lang`,`searchable_text`)
			values
			(
			'".addslashes($record->getId())."',
			'".addslashes($record->_table->tablename)."',
			'".addslashes($record->getPublicLink())."',
			'".addslashes($record->getTitle())."',
			'".addslashes(strip_tags($record->getDescription()))."',
			'".addslashes($lang)."',
			'".addslashes($searchable_text)."'
			)";
		if ( !@mysql_query($sql, df_db()) ){
			$this->createIndexTable();
			if ( !mysql_query($sql, df_db()) ){
				trigger_error(mysql_error(df_db()), E_USER_ERROR);
			}
		}
		
		
		return true;
	}
	
	function indexFoundRecords($query, $lang='*'){
		for ( $start = 0; $start >= 0; $start +=100 ){
			// We do it in chunks of 100
			
			$records = df_get_records_array($query['-table'], $query, $start, 100, false);
			if ( !$records or (count($records) == 0) or PEAR::isError($records) ) return true;
			
			foreach ($records as $record){
				
				$this->indexRecord($record, $lang);
			}
			unset($records);
		}
	}
	
	function buildIndex($tables=null, $lang='*', $clear=true){
		if ( $clear ){
			$sql = "delete from dataface__index";
			if ( !mysql_query($sql, df_db()) ){
				$this->createIndexTable();
			}
		}
		foreach ( $tables as $tablename ){
			if ( $this->isTableIndexable($tablename) ){
				$this->indexFoundRecords(array('-table'=>$tablename), $lang);
			}
		}
		$sql = "optimize table dataface__index";
		if ( !mysql_query($sql, df_db()) ){
			$this->createIndexTable();
			$sql = "optimize table dataface__index";
			mysql_query($sql, df_db());
		}
		

	}
	
	function isTableIndexable($tablename){
		$app =& Dataface_Application::getInstance();
		$indexableTables = @$app->_conf['_index'];
		if ( !is_array($indexableTables) ){
			$indexableTables = array();
		}
		
		if ( @$indexableTables['__default__'] ){
			$default = 1;
		} else {
			$default = 0;
		}
		
		$table =& Dataface_Table::loadTable($tablename);
		if ( $default ){
			if ( isset($table->_atts['__index__']) and $table->_atts['__index__'] == 0){
				return false;
			} else {
				return true;
			}
		} else {
			if ( @$indexableTables[$tablename] or @$table->_atts['__index__'] ){
				return true;
			} else {
				return false;
			}
		}
	}
	
	function _cmp_words_by_length($a,$b){
		if ( strlen($a) < strlen($b) ) return 1;
		else if ( strlen($b) < strlen($a) ) return -1;
		else return 0;
	}
	
	/**
	 * This will find , in relevance sorted order the records from the index.
	 * @param array $query  Query array.  Important parameters are '-search', '-skip', and '-limit'
	 * @returns array
	 */
	function find($query, $returnMetadata=false, $lang=null){
		if ( !$lang ) $lang = @Dataface_Application::getInstance()->_conf['lang'];
		if ( !$lang ) $lang = 'en';
		
		$select = "select record_id,`table`,record_url,record_title,record_description, `searchable_text`, `lang`,match(searchable_text) against ('".addslashes($query['-search'])."') as `relevance`";
		$sql = "
			
			from dataface__index
			where `lang`='".addslashes($lang)."' and 
			match(searchable_text)
			against ('".addslashes($query['-search'])."')";
			
		
		$countsql = "select count(record_id), `table` as num ".$sql." group by `table`";
		
		if ( isset($query['-table']) ){
			$sql .= " and `table` = '".addslashes($query['-table'])."'";
		}
		
		
		
		if ( !isset($query['-limit']) ){
			$query['-limit'] = 30;
		}
		
		
		
		if ( !isset($query['-skip']) ){
			$query['-skip'] = 0; 
		}
		$skip = intval($query['-skip']);
		$limit = intval($query['-limit']);
		$sql .= " limit $skip, $limit";
		$sql = $select.$sql;
		
		$res = @mysql_query($sql, df_db());
		if ( !$res ){
			$this->createIndexTable();
			$res = mysql_query($sql, df_db());
			if ( !$res ){
				trigger_error(mysql_error(df_db()), E_USER_ERROR);
			}
		}
		
		$out = array();
		$phrases = array();
		$words = explode(' ', str_replace('"', '', $query['-search']));
		if ( preg_match_all('/"([^"]+)"/', $query['-search'], $matches, PREG_PATTERN_ORDER) ){
			foreach ($matches[1] as $m){
				$phrases[] = $m;
			}
		}
		$numWords = count($words);
		if ( $numWords > 1 ){
			$words2 = array(implode(' ', $words));
			for ( $i=0; $i<$numWords; $i++){
				for ( $j=$i; $j<$numWords; $j++){
					
					$temp = $words;
					for ( $k=$i; $k<=$j; $k++ ){
						unset($temp[$k]);
					}
					$words2[] = implode(' ', $temp);
				}
			}
			$words = $words2;
		}
		
		usort($words, array($this, '_cmp_words_by_length'));
		
		while ( $row = mysql_fetch_assoc($res) ){
			$st = strip_tags($row['searchable_text']);
			$st = html_entity_decode($st, ENT_COMPAT, Dataface_Application::getInstance()->_conf['oe']);
			
			unset($row['searchable_text']);
			
			$summary = array();
			foreach ($phrases as $p){
				if ( preg_match_all('/.{0,50}'.preg_quote($p, '/').'.{0,50}/', $st, $matches, PREG_PATTERN_ORDER) ){
					//print_r($matches);
					foreach ($matches[0] as $m){
						$summary[] = $m;
						if ( count($summary) > 5 ) break;
					}
					//print_r($summary);
				}
			}
			
			if ( !$summary ){
				foreach ($words as $p){
					if ( !trim($p) ) continue;
					if ( preg_match_all('/.{0,50}'.preg_quote($p, '/').'.{0,50}/', $st, $matches, PREG_PATTERN_ORDER) ){
						foreach ($matches[0] as $m){
							$summary[] = $m;
							if ( count($summary) > 5 ) break;
						}
					}
				}
			}
			if ( $summary ){
				$row['record_description'] = '...' .implode(' ... ', $summary).' ...';
			}
			
			$out[] = $row;
			
		}
		@mysql_free_result($res);
		if ( $returnMetadata ){
			$app =& Dataface_Application::getInstance();
			$res = @mysql_query($countsql, df_db());
			if ( !$res ) trigger_error(mysql_error(df_db()), E_USER_ERROR);
			$found = 0;
			$total_found = 0;
			$tables_matches = array();

			while ($row = mysql_fetch_row($res) ){
				
				$label = @$app->_conf['table_labels'][$row[1]];
				if ( !$label ) $label = @$app->tables[$row[1]];
				if ( !$label ) $label = $row[1];
				$tables_matches[ $row[1] ] = array('found'=>$row[0], 'label'=>$label);
				$total_found += intval($row[0]);
				if ( !@$query['-table'] or $query['-table'] == $row[1]  )$found += intval($row[0]);
			}
			@mysql_free_result($res);
		
			$meta = array();
			$meta['found'] = $found;
			$meta['skip'] = $query['-skip'];
			$meta['limit'] = $query['-limit'];
			$meta['start'] = $query['-skip'];
			$meta['end'] = min($meta['start']+$meta['limit'], $meta['found']);
			$meta['tables'] = $tables_matches;
			$meta['table'] = $query['-table'];
			$meta['table_objects'] =& $table_objects;
			$meta['total_found'] = $total_found;
			return array('results'=>$out, 'metadata'=>@$meta);
		} else {
			
			return $out;
		}
		
	}
}
