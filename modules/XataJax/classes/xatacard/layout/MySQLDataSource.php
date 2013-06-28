<?php
class xatacard_layout_MySQLDataSource implements xatacard_layout_DataSource {
	
	public static $RECORDS_TABLE = 'dataface__xatacard_records';

	

	public static function createRecordsTable(){
	
		$sql = sprintf(
			"create table `%s` (
				id int(11) not null auto_increment,
				schema_id int(11) not null,
				`base_record_id_hash` varchar(32) not null,
				`base_record_id` text not null,
				`version_hash` varchar(32) not null,
				`lang` varchar(2) not null,
				`record_data` text not null,
				primary key (`id`),
				key (`schema_id`, `base_record_id_hash`, `version_hash`)
			)",
			self::$RECORDS_TABLE
		);
		
		$res = mysql_query($sql, df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
		true;
	
	}
	
	/*
	public static function createFieldsTable(){
		$sql = sprintf(
			"create table `%s` (
				id int(11) not null auto_increment,
				record_id int(11) not null,
				field_hash varchar(32) not null,
				source_record_id text not null,
				source_record_version int(11) not null default 0,
				field_value 
	
	}
	*/
	
	public function buildRecord(xatacard_layout_Schema $schema, Dataface_Record $rec){
		//Now we populate the record
		$out = new xatacard_layout_Record();
		$out->setSchema($schema);
		$out->setDataSource($this);
		
		$recordCache = array();
		$recordCache[$rec->getId()] = $rec;
		
		$recordData = array(
			'records'=> array(),
			'fields'=> array()
		);
		
		$recordIdToIndex = array();
		
		
		foreach ($schema->getFields() as $key=>$value){
			if ( strpos($key, '/') !== false ){
				// this is a first class property of the record so we can just get it.
				$index = count($recordData['records']);
				$recordData['records'][$index] = array(
					'id'=>$rec->getId(),
					'version' => $rec->getVersion()
				);
				$recordIdToIndex[$rec->getId()] = $index;
				$recordData['fields'][$key] = $recordIdToIndex[$rec->getId()];
				$out->setValue($key, $rec->getValue($key));
			} else {
				// this is a related record's property that we need to get from the related
				// records.

				$path = explode('/', $key);
				
				$crec = $rec; // As we're going to be going through related records
								// we need to mark the current record we are dealing 
								/// with
				
				$fieldName = array_pop($path);
				
				// A flag to indicate if we should break the outer loop 
				// after the inner loop finishes
				$shouldSkip = false;
				
				foreach ($path as $part){
					$index = 0;
					if ( preg_match('/^(.*)\[(\d+\)]$/', $part, $matches) ){
						$index = intval($matches[2]);
						$part = $matches[1];
					}
					$related = $crec->getRelatedRecord($part, $index);
					if ( PEAR::isError($related) ){
						throw new Exception(sprintf(
							"MySQL datasource failed to load record because there was an error retrieving a column from a related record.  The field '%s' could not be retrieved because the following error: %s",
							$key,
							$related->getMessage()
						));
					}
					
					if ( !$related ){
						// No related record could be found to satisfy this path.
						// This doesn't constitute an error.  It just means that the
						// value should be blank.
						$out->setValue($key, null);
						$shouldSkip = true;
						break;
						
					}
					unset($crec);
					if ( @$this->recordCache[$related->getId()] ){
						$crec = $this->recordCache[$related->getId()];
					} else {
						$crec = $related->toRecord();
						$this->recordCache[$related->getId()] = $crec;
						$index = count($recordData['records']);
						$recordData['records'][$index] = array(
							'id'=>$crec->getId(),
							'version' => $rec->getVersion()
						);
						$recordIdToIndex[$crec->getId()] = $index;
					}
					unset($related);
					
				}
				
				if ( $shouldSkip ){
					// Something occurred in the inner loop to 
					// complete this step so we should skip the rest 
					// of this stuff.
					continue;
				}
				
				if ( !$crec ){
					// This should never happen
					throw new Exception(sprintf(
						"MySQL datasource failed to load record because there was an error retrieving a related record required for one of the fields.  The field '%s' could not be retrieved.",
						$key
					));
				}
				$recordData['fields'][$key] = $recordIdToIndex[$crec->getId()];
				
				$out->setValue($key, $crec->val($fieldName));
				
			
			}
			
			
			
		}
		
		$cacheKeys = array_keys($recordCache);
		sort($cacheKeys);
		$versionstring = array();
		foreach ($cacheKeys as $k){
			$versionstring[] = $k.':'.$recordCache[$k]->getVersion();
		}
		$versionstring = implode(' ', $versionstring);
		$versionhash = md5($versionstring);
		$res = $this->query(sprintf(
			"select `id` from `%s` where 
				schema_id=%d and 
				base_record_id_hash='%s' and 
				version_hash='%s' and
				`lang`='%s'",
				str_replace('`','',self::$RECORDS_TABLE),
				intval($schema->getId()),
				addslashes(md5($rec->getId())),
				addslashes($versionhash),
				addslashes($rec->lang)
			));
		if ( mysql_num_rows($res) >= 0 ){
			$row = mysql_fetch_row($res);
			$out->setId($row[0]);
		} else{
			$res = $this->query(sprintf(
				"insert into `%s` (schema_id, base_record_id_hash, base_record_id, version_hash, `lang`, `record_data`)
				values (
					%d, '%s', '%s', '%s', '%s'
				)",
				intval($schema->getId()),
				addslashes(md5($rec->getId())),
				addslashes($rec->getid()),
				addslashes($versionhash),
				addslashes($rec->lang),
				addslashes(json_encode($recordData))
			));
			$out->setId(mysql_insert_id(df_db()));
		}
				
		$out->clearSnapshot();
		return $out;
	
	}
	
	public function loadRecord( xatacard_layout_Schema $schema, array $query ){
	
		if ( isset($query['__id__']) ){
			$id = $query['__id__'];
			$res = $this->query(sprintf(
				"select schema_id, base_record_id from `%s` where `id`=%d",
				str_replace('`', '',self::$RECORDS_TABLE),
				intval($id)
			));
			if (mysql_num_rows($res) == 0 ){
				return null;
			} else {
				$row = mysql_fetch_assoc($res);
				if ( $row['schema_id'] != $schema->getId() ){
					throw new Exception(sprintf(
						"The record with id %d failed to load because it uses a different schema than expected.  Expected schema id %d but found %d",
						intval($id),
						intval($schema->getId()),
						intval($row['schema_id'])
					));
				}
				$rec = df_get_record_by_id($row['base_record_id']);
				if ( !$rec ) return null;
				if ( PEAR::isError($rec) ){
					throw new Exception(sprintf(
						"Failed to load record is %d because there was problem loading its base record ('%s'): %s",
						intval($id),
						$row['base_record_id'],
						$rec->getMessage()
					));
				}
				return $this->buildRecord($schema, $rec);
			}
			
				
		}
		
		$tablename = $schema->getProperty('table');
		if ( !$tablename ){
			throw new Exception(sprintf(
				"MySQL datasource cannot load a record from schema '%s' because the schema does not specify a table",
				$schema->getLabel()
			));
		}
		
		$rec = df_get_record($tablename, $query);
		if ( PEAR::isError($rec) ){
			throw new Exception(sprintf(
				"MySQL datasource failed to load a record for the given query because an error occurred: %s",
				$rec->toString()
			));
		}
		
		if ( !$rec ) return null;
		
		return $this->buildRecord($schema, $rec);
		
		
			
		
		
	
	}
	public function newRecord( xatacard_layout_Schema $schema, array $values);
	public function loadRecords( xatacard_layout_Schema $schema, $query){
		$tablename = $schema->getProperty('table');
		if ( !$tablename ){
			throw new Exception(sprintf(
				"MySQL datasource cannot load a records from schema '%s' because the schema does not specify a table",
				$schema->getLabel()
			));
		}
		
		$queryTool = new Dataface_QueryTool($tablename, df_db(), $query);
		
		$res = $queryTool->loadSet('', true, true, false); // preview should be disabled... we need full records
		if ( PEAR::isError($res) ){
			throw new Exception(sprintf(
				"MySQL datasource failed to load records: %s",
				$res->getMessage()
			));
		}
		
		$out = new xatacard_layout_RecordSet();
		$out->setSchema($schema);
		$out->setDatasource($this);
		$out->setFound($queryTool->found());
		$out->setCardinality($queryTool->cardinality());
		$out->setStart($queryTool->start());
		$out->setEnd($queryTool->end());
		$out->setLimit($queryTool->limit());
		
		
		$records = $queryTool->getRecordsArray();
		
		if ( PEAR::isError($records) ){
			throw new Exception(sprintf(
				"MySQL datasource cannot load records from schema '%s' because an error occurred in the query: %s",
				$schema->getLabel(),
				$records->getMessage()
			));
		}
		
		foreach ($records as $rec){
			$out->addRecord($this->buildRecord($schema, $rec));
		}
		return $out;
		
		
		
	
	}
	
	public function save(xatacard_layout_Record $record){
	
		
	
		
	}
	public function delete(xatacard_layout_Record $record);

}
