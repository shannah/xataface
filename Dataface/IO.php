<?php
/*-------------------------------------------------------------------------------
 * Xataface Web Application Framework
 * Copyright (C) 2005-2008 Web Lite Solutions Corp (shannah@sfu.ca)
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *-------------------------------------------------------------------------------
 */
/**
 * File: IO.php
 * Author: Steve Hannah <shannah@sfu.ca>
 * Created: October 4, 2005
 * Description:
 *  Handles IO (input/output) to and from the database.
 *
 * Usage:
 * -------
 *
 * $io = new Dataface_IO('Appointments');
 * $record = new Dataface_Record('Appointments', array());
 * $io->read(array('MemberID'=>2), $record);
 *		// read Appointment with MemberID=2 into the Dataface_Record object $record.
 *
 * $record->setValue('Description', 'A new description for the appointment');
 * $io->write($record);
 *		// update the changes to the database.
 */
 
import( 'Dataface/QueryBuilder.php');
import('Dataface/DB.php');
define('Dataface_IO_READ_ERROR', 1001);
define('Dataface_IO_WRITE_ERROR', 1002);
define('Dataface_IO_NOT_FOUND_ERROR', 1003);
define('Dataface_IO_TOO_MANY_ROWS', 1004);
define('Dataface_IO_NO_TABLES_SELECTED', 1005);
define('MYSQL_ER_DUP_KEY', 1022);
define('MYSQL_ER_DUP_ENTRY', 1062);
define('MYSQL_ER_ROW_IS_REFERENCED', 1217);
define('MYSQL_ER_ROW_IS_REFERENCED_2', 1451);
define('MYSQL_ER_NO_REFERENCED_ROW', 1216);
define('MYSQL_ER_NO_REFERENCED_ROW_2', 1452);


class Dataface_IO {
	var $_table;
	var $_serializer;
	var $insertIds = array();
	var $lang;
	var $dbObj;
	var $parentIO=-1;
	var $fireTriggers=true;
	
	// Placeholder for the version number when recordExists is called
	// it will place the version number of the existing record in this
	// placeholder.
	var $lastVersionNumber = null;
	
	/**
	 * An optional alther table that this object can work on.
	 * This is handy in case records have to read from and written to
	 * delete or import tables.
	 */
	var $_altTablename = null;
	
	function Dataface_IO($tablename, $db=null, $altTablename=null){
		$app =& Dataface_Application::getInstance();
		$this->lang = $app->_conf['lang'];
		$this->_table =& Dataface_Table::loadTable($tablename, $db);
		$this->_serializer = new Dataface_Serializer($tablename);
		$this->_altTablename = $altTablename;
		$this->dbObj =& Dataface_DB::getInstance();
	}
	
	function __destruct(){
		unset($this->_table);
		unset($this->dbObj);
		unset($this->_serializer);
		
		if ( isset($this->parentIO) and $this->parentIO != -1 ){
			$this->parentIO->__destruct();
			unset($this->parentIO);
		}
	}
	
	function &getParentIO(){
		if ( $this->parentIO == -1 ){
			if ( isset($this->_altTablename) and $this->_altTablename != $this->_table->tablename) {
				$null = null;
				return $null;
			}
				// There is no clear parent table if an alternate table name is set.
				
			$parentTable =& $this->_table->getParent();
			if ( isset($parentTable) ){
				$this->parentIO = new Dataface_IO($parentTable->tablename, null, null);
				$this->parentIO->lang = $this->lang;
				$this->parentIO->fireTriggers = false;
			} else {
				$this->parentIO = null;
			}
			
		}
		return $this->parentIO;
	}
	
	/**
	 * Loads a record given an ID.  The ID resembles a URL that describes a 
	 * record based on the table, relationship, and keys of the record.
	 * E.g.:  table/relationship?key1=val1&key2=val2&relationship::key1=val3
	 *
	 * @param string $recordid The record id of the record to load.
	 * @param mixed A Dataface_Record object if the id refers to a record.  A
	 *		Dataface_RelatedRecord object if the id refers to a related record.
	 *		Or null if no such record is found.
	 *
	 * @since 0.6.1
	 */
	function &loadRecordById($recordid){
		$rec =& Dataface_IO::getByID($recordid);
		return $rec;

	}
	
	
	/**
	 * Converts a record id to a query array.  A record id is a string of the form
	 * tablename/relationshipname?key1=val1&key2=val2&relationshipname::key1=val3&relationshipname::key2=val4
	 * @param string $recordid The record id to be converted.
	 * @return array Associative array of query parameters.
	 * @since 0.6.1
	 */
	function recordid2query($recordid){
		$query = array();
		list($base,$qstr) = explode('?', $recordid);
		if ( strpos($base,'/') !== false ){
			list($query['-table'],$query['-relationship']) = explode('/',$base);
		} else {
			$query['-table'] = $base;
		}
		$params = explode('&', $qstr);
		foreach ( $params as $param){
			list($key,$value) = explode('=', $param);
			$query[urldecode($key)] = '='.urldecode($value);
		}
		return $query;
		
	}
	


	/**
	 * Reads result of query into the table.
	 *
	 * @param mixed $query Either an array of query parameters or a record id string
	 *			identifying a record to load.
	 *
	 * @param &$record The record object into which to load our results.
	 *
	 * @param string $tablename An optional table name from which the record could be read.
	 *				For example, a record may be read form an import table rather than
	 *				the real table. (or a deleted table).
	 *
	 */
	function read($query='', &$record, $tablename=null){
		$app =& Dataface_Application::getInstance();
		if ( !is_a($record, "Dataface_Record") ){
			throw new Exception(
				df_translate(
					'scripts.Dataface.IO.read.ERROR_PARAMETER_2',
					"Dataface_IO::read() requires second parameter to be of type 'Dataface_Record' but received '".get_class($record)."\n<br>",
					array('class'=>get_class($record))
					), E_USER_ERROR);
		}
		
		if ( is_string($query) and !empty($query) ){
			// If the query is actually a record id string, then we convert it
			// to a normal query.
			$query = $this->recordid2query($query);
		}
		
		if ( $tablename === null and $this->_altTablename !== null ){
			$tablename = $this->_altTablename;
		}
		
	
		$qb = new Dataface_QueryBuilder($this->_table->tablename);
		$qb->selectMetaData = true;
		$query['-limit'] = 1;
		if ( @$query['-cursor']>0 ) $query['-skip'] = $query['-cursor'];
		$sql = $qb->select('', $query, false, $this->tablename($tablename));
		$res = $this->dbObj->query($sql, $this->_table->db, $this->lang, true /* as_array */);
		if ( (!is_array($res) and !$res) || PEAR::isError($res) ){
			$app->refreshSchemas($this->_table->tablename);
			$res = $this->dbObj->query($sql, $this->_table->db, $this->lang, true /* as array */);
			if ( (!is_array($res) and !$res) || PEAR::isError($res) ){
				if ( PEAR::isError($res) ) return $res;
				return PEAR::raiseError(
					Dataface_LanguageTool::translate(
						/* i18n id */
						"Error reading record",
						/* default error message */
						"Error reading table '".
						$this->_table->tablename.
						"' from the database: ".
						mysql_error($this->_table->db),
						/* i18n parameters */
					
						array('table'=>$this->_table->tablename, 
							'mysql_error'=>mysql_error(), 
							'line'=>0, 
							'file'=>'_',
							'sql'=>$sql
						)
					),
						
					DATAFACE_E_READ_FAILED
				);
			}
		}
		
		//if ( mysql_num_rows($res) == 0 ){
		if ( count($res) == 0 ){
			return PEAR::raiseError(
				Dataface_LanguageTool::translate(
					/* i18n id */
					"No records found",
					/* default error message */
					"Record for table '".
					$this->_table->tablename.
					"' could not be found.",
					/* i18n parameters */
					array('table'=>$this->_table->tablename, 'sql'=>$sql)
				),
				DATAFACE_E_READ_FAILED
			);
		}
		
		//$row = mysql_fetch_assoc($res);
		$row = $res[0];
		//mysql_free_result($res);
		$record->setValues($row);
		$record->setSnapshot();
			// clear all flags that may have been previously set to indicate that the data is old or needs to be updated.
		
		
	
	}
	
	
	
	
	/**
	 * Deletes a record from the database.
	 * @param Dataface_Record $record Dataface_Record object to be deleted.
	 * @param boolean $secure Whether to check permissions.
	 * @returns mixed true if successful, or PEAR_Error if failed.
	 */
	function delete(&$record, $secure=false){
		if ( $secure && !$record->checkPermission('delete') ){
			// Use security to check to see if we are allowed to delete this 
			// record.
			return Dataface_Error::permissionDenied(
				df_translate(
					'scripts.Dataface.IO.delete.PERMISSION_DENIED',
					'Could not delete record "'.$record->getTitle().'" from table "'.$record->_table->tablename.'" because you have insufficient permissions.',
					array('title'=>$record->getTitle(), 'table'=>$record->_table->tablename)
					)
				);
		}
		
		
		$builder = new Dataface_QueryBuilder($this->_table->tablename);
		
		if ( $this->fireTriggers ){
			$res = $this->fireBeforeDelete($record);
			if ( PEAR::isError($res) ) return $res;
		}
		
		
		
		// do the deleting
		$keys =& $record->_table->keys();
		if ( !$keys || count($keys) == 0 ){
			throw new Exception(
				df_translate(
					'scripts.Dataface.IO.delete.ERROR_NO_PRIMARY_KEY',
					'Could not delete record from table "'.$record->_table->tablename.'" because no primary key was defined.',
					array('tablename'=>$record->_table->tablename)
					)
				);

		}
		$query = array();
		foreach ( array_keys($keys) as $key ){
			if ( !$record->strval($key) ){
				return PEAR::raiseError(
					Dataface_LanguageTool::translate(
						/* i18n id */
						'Could not delete record because missing keys',
						/* default error message */
						'Could not delete record '.
						$record->getTitle().
						' because not all of the keys were included.',
						/* i18n parameters */
						array('title'=>$record->getTitle(), 'key'=>$key)
					),
					DATAFACE_E_DELETE_FAILED
				);
			}
			$query[$key] = '='.$record->strval($key);
		}
		
		$sql = $builder->delete($query);
		if ( PEAR::isError($sql) ) return $sql;
		
		//$res = mysql_query($sql);
		$res = $this->dbObj->query($sql, null, $this->lang);
		if ( !$res || PEAR::isError($res)){
			if ( PEAR::isError($res) ) $msg = $res->getMessage();
			else $msg = mysql_error(df_db());
			return PEAR::raiseError(
				
				Dataface_LanguageTool::translate(
					/* i18n id */
					'Failed to delete record. SQL error',
					/* default error message */
					'Failed to delete record '.
					$record->getTitle().
					' because of an sql error. '.mysql_error(df_db()),
					/* i18n parameters */
					array('title'=>$record->getTitle(), 'sql'=>$sql, 'mysql_error'=>$msg)
				),
				DATAFACE_E_DELETE_FAILED
			);
		}
		
		$parentIO =& $this->getParentIO();
		if ( isset($parentIO) ){
			$parentRecord =& $record->getParentRecord();
			if ( isset($parentRecord) ){
				$res = $parentIO->delete($parentRecord, $secure);
				if ( PEAR::isError($res) ) return $res;
			}
		}
		
		if ( $this->fireTriggers ){
			$res2 = $this->fireAfterDelete($record);
			if ( PEAR::isError($res2) ) return $res2;
		}
		self::touchTable($this->_table->tablename);
		return $res;
	
	}
	
	function saveTransients(Dataface_Record $record, $keys=null, $tablename=null, $secure=false){
		$app = Dataface_Application::getInstance();
		// Now we take care of the transient relationship fields.
		// Transient relationship fields aren't actually stored in the record
		// itself, they are stored as related records.
		foreach ( $record->_table->transientFields() as $tfield ){
			if ( !isset($tfield['relationship']) ) continue;
			if ( !$record->valueChanged($tfield['name']) ) continue;
			
			$trelationship =& $record->_table->getRelationship($tfield['relationship']);
			
			if ( !$trelationship or PEAR::isError($trelationship) ){
				// We couldn't find the specified relationship.
				//$record->vetoSecurity = $oldVeto;
				return $trelationship;
			}
				
			$orderCol = $trelationship->getOrderColumn();
			if ( PEAR::isError($orderCol) ) $orderCol = null;
				
			$tval = $record->getValue($tfield['name']);
			if ( $tfield['widget']['type'] == 'grid' ){

				$tval_existing = array();
				$tval_new = array();
				$tval_new_existing = array();
				$torder = 0;
				foreach ($tval as $trow){
					if ( !is_array($trow) ) continue;
					$trow['__order__'] = $torder++;
					if ( isset($trow['__id__']) and preg_match('/^new:/', $trow['__id__']) ){
						$tval_new_existing[] = $trow;
					}
					else if ( isset($trow['__id__']) and $trow['__id__'] != 'new'   ){
						$tval_existing[$trow['__id__']] = $trow;
					} else if ( isset($trow['__id__']) and $trow['__id__'] == 'new'){
						$tval_new[] = $trow;
					} 
				}
	
				// The transient field was loaded so we can go about saving the
				// changes/
				$trecords =& $record->getRelatedRecordObjects($tfield['relationship'], 'all');
				if ( !is_array($trecords) or PEAR::isError($trecords) ){
					error_log('Failed to get related records for record '.$record->getId().' in its relationship '.$tfield['relationship']);
					unset($tval);
					unset($orderCol);
					unset($tval_new);
					unset($torder);
					unset($trelationship);
					unset($tval_existing);
					continue;
				}
				
				
				// Update the existing records in the relationship.
				// We use the __id__ parameter in each row for this.
				//echo "About to save related records";
				foreach ($trecords as $trec){
					$tid = $trec->getId();
					
					if ( isset($tval_existing[$tid]) ){
						$tmp = new Dataface_RelatedRecord($trec->_record, $tfield['relationship'], $trec->getValues());
						
						$tmp->setValues($tval_existing[$tid]);
						$changed = false;
						foreach ( $tval_existing[$tid] as $k1=>$v1 ){
							if ( $tmp->isDirty($k1) ){
								$changed = true;
								break;
							}
						}
						
						if ( $changed ){
							$trec->setValues($tval_existing[$tid]);
							if ( $orderCol ) $trec->setValue( $orderCol, $tval_existing[$tid]['__order__']);
							//echo "Saving ";print_r($trec->vals());
							$res_t = $trec->save($this->lang, $secure);
							
							if ( PEAR::isError($res_t) ){
								return $res_t;
								error_log('Failed to save related record '.$trec->getId().' while saving transient field '.$tfield['name'].' in record '.$record->getId().'. The error returned was : '.$res_t->getMessage());
								
							}
						} else {
							if ( $orderCol and $record->checkPermission('reorder_related_records', array('relationship'=>$tfield['relationship'])) ){
								$trec->setValue( $orderCol, $tval_existing[$tid]['__order__']);
								$res_t = $trec->save($this->lang, false); // we don't need this to be secure
								if ( PEAR::isError($res_t) ){
									return $res_t;
									error_log('Failed to save related record '.$trec->getId().' while saving transient field '.$tfield['name'].' in record '.$record->getId().'. The error returned was : '.$res_t->getMessage());
								
								}
							}
						}
						
						unset($tmp);
					} else {
						
						
					}
					unset($trec);
					unset($tid);
					unset($res_t);
					
				}

				
				// Now add new records  (specified by __id__ field being 'new'
				
				foreach ($tval_new as $tval_to_add){
					$temp_rrecord = new Dataface_RelatedRecord( $record, $tfield['relationship'], array());
					
					
					$temp_rrecord->setValues($tval_to_add);
					if ( $orderCol ) $temp_rrecord->setValue( $orderCol, $tval_to_add['__order__']);
					$res_t = $this->addRelatedRecord($temp_rrecord, $secure);
					if ( PEAR::isError($res_t) ){
						error_log('Failed to save related record '.$temp_rrecord->getId().' while saving transient field '.$tfield['name'].' in record '.$record->getId().'. The error returned was : '.$res_t->getMessage());
					}
					unset($temp_rrecord);
					unset($res_t);
					
					
				}
				
				// Now add new existing records  (specified by __id__ field being 'new:<recordid>'
				
				foreach ($tval_new_existing as $tval_to_add){
					$tid = preg_replace('/^new:/', '', $tval_to_add['__id__']);
					$temp_record = df_get_record_by_id($tid);
					if ( PEAR::isError($temp_record) ){
						return $temp_record;
					}
					if ( !$temp_record){
						return PEAR::raiseError("Failed to load existing record with ID $tid.");
					}
					$temp_rrecord = new Dataface_RelatedRecord( $record, $tfield['relationship'], $temp_record->vals());
					
					
					$temp_rrecord->setValues($tval_to_add);
					if ( $orderCol ) $temp_rrecord->setValue( $orderCol, $tval_to_add['__order__']);
					$res_t = $this->addExistingRelatedRecord($temp_rrecord, $secure);
					if ( PEAR::isError($res_t) ){
						error_log('Failed to save related record '.$temp_rrecord->getId().' while saving transient field '.$tfield['name'].' in record '.$record->getId().'. The error returned was : '.$res_t->getMessage());
					}
					unset($temp_rrecord);
					unset($res_t);
					
					
				}
	
				// Now we delete the records that were deleted
				// we use the __deleted__ field.
				
				if ( isset($tval['__deleted__']) and is_array($tval['__deleted__']) and $trelationship->supportsRemove() ){
					$tdelete_record = ($trelationship->isOneToMany() and !$trelationship->supportsAddExisting());
						// If it supports add existing, then we shouldn't delete the entire record.  Just remove it
						// from the relationship.
					
					foreach ( $tval['__deleted__'] as $del_id ){
						if ($del_id == 'new' ) continue;
						$drec = Dataface_IO::getByID($del_id);
						if ( PEAR::isError($drec) or !$drec ){
							unset($drec);
							continue;
						}
						
						$mres = $this->removeRelatedRecord($drec, $tdelete_record, $secure);
						if ( PEAR::isError($mres) ){
							throw new Exception($mres->getMessage());
						}
						unset($drec);
					}
				}
				
				unset($trecords);
				
			} else if ( $tfield['widget']['type'] == 'checkbox' ){
				
				// Load existing records in the relationship
				$texisting =& $record->getRelatedRecordObjects($tfield['relationship'], 'all');
				if ( !is_array($texisting) or PEAR::isError($texisting) ){
					error_log('Failed to get related records for record '.$record->getId().' in its relationship '.$tfield['relationship']);
					unset($tval);
					unset($orderCol);
					unset($tval_new);
					unset($torder);
					unset($trelationship);
					unset($tval_existing);
					continue;
				}
				$texistingIds = array();
				foreach ($texisting as $terec){
					$texistingIds[] = $terec->getId();
				}
				
				// Load currently checked records
				$tchecked = array();
				$tcheckedRecords = array();
				$tcheckedIds = array();
				$tcheckedId2ValsMap = array();
				foreach ( $tval as $trkey=>$trval){
					// $trval is in the form key1=val1&size=key2=val2
					parse_str($trval, $trquery);
					$trRecord = new Dataface_RelatedRecord($record, $tfield['relationship'],$trquery);
					$trRecords[] =& $trRecord;
					$tcheckedIds[] = $tid = $trRecord->getId();
					$checkedId2ValsMap[$tid] = $trquery;
					unset($trRecord);
					unset($trquery);
					
				}
				
				// Now we have existing ids in $texistingIds
				// and checked ids in $tcheckedIds
				
				// See which records we need to have removed
				$tremoves = array_diff($texistingIds, $tcheckedIds);
				$tadds = array_diff($tcheckedIds, $texistingIds);
				
				foreach ($tremoves as $tid){
					$trec = df_get_record_by_id($tid);
					$res = $this->removeRelatedRecord($trec, false, $secure);
					if ( PEAR::isError($res) ) return $res;
					unset($trec);
				}
				foreach ($tadds as $tid){
					$trecvals = $checkedId2ValsMap[$tid];
					$trec = new Dataface_RelatedRecord($record, $tfield['relationship'], $trecvals);
					
					$res = $this->addExistingRelatedRecord($trec, $secure);
					if ( PEAR::isError($res) ) return $res;
					unset($trec, $trecvals);
				}
				
				unset($tadds);
				unset($tremoves);
				unset($tcheckedIds, $tcheckedId2ValsMap);
				unset($tcheckedRecords);
				unset($tchecked);
				unset($texistingIds);
				unset($texisting);
				
				
				
			}
			unset($tval);
			unset($trelationship);
		
		}
	
	}
	
	
	/**
	 * Writes the values in the table to the database.
	 *
	 * @param tablename An optional tablename in case this record is not being placed in
	 * 		the standard table.  For example, the record could be placed into an import
	 * 		table.
	 * @param array $keys Optional array of keys to look up record to write to.
	 * @param string $tablename The name of the table to write to, if not this table.
	 *							This is useful for writing to import tables or other 
	 *							tables with identical schema.
	 * @param boolean $secure Whether to check permissions or not.
	 * @param boolean $forceNew If true, it forces an insert rather than an update.
	 */
	function write(&$record, $keys=null, $tablename=null, $secure=false, $forceNew=false){
		// The vetoSecurity flag allows us to make changes to a record without
		// the fields being filtered for security checks when they are saved.
		// Since we may want to change or add values to a record in the 
		// beforeSave type triggers, and we probably don't want these changes
		// checked by security, we should use this flag to make all changes
		// in these triggers immune to security checks.
		// We return the veto setting to its former state after this method 
		// finishes.
		//$oldVeto = $record->vetoSecurity;
		//$record->vetoSecurity = true;
		//$parentRecord =& $record->getParentRecord();
		$app =& Dataface_Application::getInstance();
		//$parentIO =& $this->getParentIO();
		
		if ( !is_a($record, "Dataface_Record") ){
			throw new Exception(
				df_translate(
					'scripts.Dataface.IO.write.ERROR_PARAMETER_1',
					"Dataface_IO::write() requires first parameter to be of type 'Dataface_Record' but received '".get_class($record)."\n<br>",
					array('class'=>get_class($record))
					), E_USER_ERROR);
		}
		if ( $tablename === null and $this->_altTablename !== null ){
			$tablename = $this->_altTablename;
		}
		
		if ( $this->fireTriggers ){
			$res = $this->fireBeforeSave($record);
			if (PEAR::isError($res) ) {
				//$record->vetoSecurity = $oldVeto;
				return $res;
			}
		}
			
		
		if ( !$forceNew and $this->recordExists($record, $keys, $this->tablename($tablename)) ){
			$res = $this->_update($record, $keys, $this->tablename($tablename), $secure);
		} else {
			
			$res = $this->_insert($record, $this->tablename($tablename), $secure);
			
		}
		
		if ( PEAR::isError($res) ){
			if ( Dataface_Error::isDuplicateEntry($res) ){
				/*
				 * Duplicate entries we will propogate up so that the application can decide what to do.
				 */
				//$record->vetoSecurity = $oldVeto;
				return $res;
			}
			$res->addUserInfo(
				df_translate(
					'scripts.Dataface.IO.write.ERROR_SAVING',
					"Error while saving record of table '".$this->_table->tablename."' in Dataface_IO::write() ",
					array('tablename'=>$this->_table->tablename,'line'=>0,'file'=>'_')
					)
				);
			//$record->vetoSecurity = $oldVeto;
			return $res;
		}
		
		$res = $this->saveTransients($record, $keys, $tablename, $secure);
		if ( PEAR::isError($res) ){
			return $res;
		}
		
		
		
		if ( $this->fireTriggers ){
			$res2 = $this->fireAfterSave($record);
			if ( PEAR::isError($res2) ){
				//$record->vetoSecurity = $oldVeto;
				return $res2;
			}
		}
		if ( isset($app->_conf['history']) and ( @$app->_conf['history']['enabled'] || !isset($app->_conf['history']['enabled']))){
			
			// History is enabled ... let's save this record in our history.
			import('Dataface/HistoryTool.php');
			$historyTool = new Dataface_HistoryTool();
			$historyTool->logRecord($record, $this->getHistoryComments($record), $this->lang);
		}
		
		
		if ( isset($app->_conf['_index'])  and @$app->_conf['_index'][$record->table()->tablename]){
			// If indexing is enabled, we index the record so that it is 
			// searchable by natural language searching.
			// The Dataface_Index class takes care of whether or not this 
			// record should be indexed.
			import('Dataface/Index.php');
			$index = new Dataface_Index();
			$index->indexRecord($record);
		} 
		// It seems to me that we should be setting a new snapshot at this point.
		//$record->clearSnapshot();
		$record->setSnapshot();
		self::touchTable($this->_table->tablename);
		self::touchRecord($record);
		//$record->vetoSecurity = $oldVeto;
		return $res;
	}
	
	
	static function touchRecord(Dataface_Record $record=null){
		if ( !isset($record) ) return;
		$id = $record->getId();
		$hash = md5($id);
		$sql = "replace into dataface__record_mtimes 
				(recordhash, recordid, mtime) values 
				('".addslashes($hash)."','".addslashes($id)."','".time()."')";
		try {
			$res = df_q($sql);
		} catch ( Exception $ex){
			self::createRecordMtimes();
		}
	}
	
	static function createRecordMtimes(){
	    $res = df_q("create table if not exists dataface__record_mtimes (
				recordhash varchar(32) not null primary key,
				recordid varchar(255) not null,
				mtime int(11) not null)");
        //$res = df_q($sql);
	}
	
	function getHistoryComments(&$record){
		$del =& $this->_table->getDelegate();
		if ( isset($del) and method_exists($del, 'getHistoryComments') ){
			return $del->getHistoryComments($record);
		}
		$app =& Dataface_Application::getInstance();
		$appdel =& $app->getDelegate();
		if ( isset($appdel) and method_exists($appdel, 'getHistoryComments') ){
			return $appdel->getHistoryComments($record);
		}
		return '';
	}
	
	
	
	/**
	 * Returns true if the record currently represented in the Table already exists 
	 * in the database.
	 *
	 * @param tablename Alternative table where records may be stored.  This is useful if we are reading form import or delete tables.
	 *
	 */
	function recordExists(&$record, $keys = null, $tablename=null){
		$this->lastVersionNumber = null;
		if ( !is_a($record, "Dataface_Record") ){
			throw new Exception(
				df_translate(
					'scripts.Dataface.IO.recordExists.ERROR_PARAMETER_1',
					"In Dataface_IO::recordExists() the first argument is expected to be either a 'Dataface_Record' object or an array of key values, but received neither.\n<br>"
					), E_USER_ERROR);
		}
		if ( $tablename === null and $this->_altTablename !== null ){
			$tablename = $this->_altTablename;
		}
		
		$tempRecordCreated = false;
		if ( $record->snapshotExists() ){
			$tempRecord = new Dataface_Record($record->_table->tablename, $record->getSnapshot());
			$tempRecordCreated = true;
		} else {
			$tempRecord =& $record;
		}
		
		if ( $keys == null ){
			// Had to put in userialize(serialize(...)) because getValues() returns by reference
			// and we don't want to change actual values.
			$query = unserialize(serialize($tempRecord->getValues( array_keys($record->_table->keys()))));
		} else {
			$query = $keys;
		}
		
		
		$table_keys = array_keys($this->_table->keys());
		
		foreach ( $table_keys as $key){
			if ( !isset( $query[$key] ) or !$query[$key] ) {
				
				return false;
			}
		}
		
		foreach ( array_keys($query) as $key){
			//$query[$key] = '='.$this->_serializer->serialize($key, $tempRecord->getValue($key) );
			$query[$key] = $this->_serializer->serialize($key, $tempRecord->getValue($key) );
			
		}
		if ( $tempRecordCreated ) $tempRecord->__destruct();
		
		//$qb = new Dataface_QueryBuilder($this->_table->tablename, $query);
		//$sql = $qb->select_num_rows(array(), $this->tablename($tablename));
		if ( $record->table()->isVersioned() ){
			$versionField = "`".$record->table()->getVersionField()."`";
		} else {
			$versionField = "NULL";
		}
		$sql = "select `".$table_keys[0]."`, $versionField from `".$this->tablename($tablename)."` where ";
		$where = array();
		foreach ($query as $key=>$val){
			$where[] = '`'.$key.'`="'.addslashes($val).'"';
		}
		$sql .= implode(' AND ', $where).' limit 1';
		
		$res = df_q($sql, $this->_table->db);
		$num = mysql_num_rows($res);
		$row = mysql_fetch_row($res);
		@mysql_free_result($res);
		if ( $num === 1 ){
			// We have the correct number...
			// let's check the version
			$this->lastVersionNumber = intval($row[1]);
			return true;
		}
		if ( $num > 1 ){
			
			$err = PEAR::raiseError(
				Dataface_LanguageTool::translate(
					/* i18n id */
					'recordExists failure. Too many rows returned.',
					/* default error message */
					"Test for existence of record in recordExists() returned $rows records.  
					It should have max 1 record.  
					The query must be incorrect.  
					The query used was '$sql'. ",
					/* i18n parameters */
					array('table'=>$this->_table->tablename, 'line'=>0, 'file'=>'_','sql'=>$sql)
				),
				DATAFACE_E_IO_ERROR
			);
			throw new Exception($err->toString(), E_USER_ERROR);
		}
		return false;
		
	
	}
	
	
	/**
	 * @param tablename An optional tablename to update.  This is useful if we are working from an update or delete table.
	 */
	function _update(&$record, $keys=null, $tablename=null, $secure=false  ){
	
		
		if ( $secure && !$record->checkPermission('edit') ){
			// Use security to check to see if we are allowed to delete this 
			// record.
			return Dataface_Error::permissionDenied(
				df_translate(
					'scripts.Dataface.IO._update.PERMISSION_DENIED',
					'Could not update record "'.$record->getTitle().'" from table "'.$record->_table->tablename.'" because you have insufficient permissions.',
					array('title'=>$record->getTitle(), 'table'=>$record->_table->tablename)
					)
				);
		}
		if ( $secure ){
			foreach ( array_keys($record->_table->fields()) as $fieldname ){
				if ( $record->valueChanged($fieldname) and !@$record->vetoFields[$fieldname] and !$record->checkPermission('edit', array('field'=>$fieldname)) ){
					$field = $record->table()->getField($fieldname);
					if ( @$field['timestamp'] and $field['timestamp'] == 'update' ){
						// Since timestamps are just updated automatically,
						// we don't need to perform any permissions on it
						continue;
					}
					// If this field's change doesn't have veto power and its value has changed,
					// we must make sure that the user has edit permission on this field.
					return Dataface_Error::permissionDenied(
						df_translate(
							'scripts.Dataface.IO._update.PERMISSION_DENIED_FIELD',
							'Could not update record "'.$record->getTitle().'" in table "'.$record->_table->tablename.'" because you do not have permission to modify the "'.$fieldname.'" column.',
							array('title'=>$record->getTitle(), 'table'=>$record->_table->tablename, 'field'=>$fieldname)
							)
						);
				}
			}
		
		}
		
		// Step 1: Validate that the record already exists
		if ( !is_a($record, 'Dataface_Record') ){
			throw new Exception(
				df_translate(
					'scripts.Dataface.IO._update.ERROR_PARAMETER_1',
					"In Dataface_IO::_update() the first argument is expected to be an object of type 'Dataface_Record' but received '".get_class($record)."'.\n<br>",
					array('class'=>get_class($record))
					), E_USER_ERROR);
		}
		if ( $tablename === null and $this->_altTablename !== null ){
			$tablename = $this->_altTablename;
		}
	
		$exists = $this->recordExists($record, $keys, $this->tablename($tablename));
		if ( PEAR::isError($exists) ){
			$exists->addUserInfo(
				df_translate(
					'scripts.Dataface.IO._update.ERROR_INCOMPLETE_INFORMATION',
					"Attempt to update record with incomplete information.",
					array('line'=>0,'file'=>'_')
					)
				);
			return $exists;
		}
		if ( !$exists ){
			return PEAR::raiseError(
				df_translate(
					'scripts.Dataface.IO._update.ERROR_RECORD_DOESNT_EXIST',
					"Attempt to update record that doesn't exist in _update() ",
					array('line'=>0,'file'=>"_")
				), DATAFACE_E_NO_RESULTS);
		}
		
		if ( $record->table()->isVersioned()){
			$currVersion = intval($record->getVersion());
			$dbVersion = intval($this->lastVersionNumber);
			if ( $currVersion !== $dbVersion ){
				return PEAR::raiseError(
					df_translate(
						'scripts.Dataface.IO._update.ERROR_RECORD_VERSION_MISMATCH',
						"Attempt to update record with a different version than the database version.  Current version is $currVersion.  DB Version is $dbVersion",
						array()
					), DATAFACE_E_VERSION_MISMATCH
				);
			}
		}
		// Step 2: Load objects that we will need
		$s =& $this->_table;
		$delegate =& $s->getDelegate();
		$qb = new Dataface_QueryBuilder($this->_table->tablename, $keys);
		
		if ( $record->recordChanged(true) ){
			if ( $this->fireTriggers ){
				$res = $this->fireBeforeUpdate($record);
				if ( PEAR::isError($res) ) return $res;
			}
		}
		
		
		$parentIO =& $this->getParentIO();
		
		if ( isset($parentIO) ){
		
			$parentRecord =& $record->getParentRecord();
			
			$res = $parentIO->write($parentRecord, $parentRecord->snapshotKeys());
			if ( PEAR::isError($res) ) return $res;
			
		}
		
		
		
		// we only want to update changed values
		$sql = $qb->update($record,$keys, $this->tablename($tablename));
		
		if ( PEAR::isError($sql) ){
			$sql->addUserInfo(
				df_translate(
					'scripts.Dataface.IO._update.ERROR_GENERATING_SQL',
					"Error generating sql for update in IO::_update()",
					array('line'=>0,'file'=>"_")
					)
				);
			return $sql;
		}
		if ( strlen($sql) > 0 ){
		
			
			
			//$res = mysql_query($sql, $s->db);
			$res =$this->dbObj->query($sql, $s->db, $this->lang);
			if ( !$res || PEAR::isError($res) ){
				
			    if ( in_array(mysql_errno($this->_table->db), array(MYSQL_ER_DUP_KEY,MYSQL_ER_DUP_ENTRY)) ){
					/*
					 * This is a duplicate entry.  We will handle this as an exception rather than an error because
					 * cases may arise in a database application when a duplicate entry will happen and the application
					 * will want to handle it in a graceful way.  Eg: If the user is entering a username that is the same
					 * as an existing name.  We don't want an ugle FATAL error to be thrown here.  Rather we want to 
					 * notify the application that it is a duplicate entry.
					 */
					return Dataface_Error::duplicateEntry(
						df_translate(
							'scripts.Dataface.IO._update.ERROR_DUPLICATE_ENTRY',
							"Duplicate entry into table '".$s->tablename,
							array('tablename'=>$s->tablename)
							) /* i18n parameters */
						);
				}
				throw new Exception(
					df_translate(
						'scripts.Dataface.IO._update.SQL_ERROR',
						"Failed to update due to sql error: ")
					.mysql_error($s->db), E_USER_ERROR);
			}
			
			//$record->clearFlags();
			if ( $record->table()->isVersioned() ){
				$versionField = $record->table()->getVersionField();
				$record->setValue($versionField, $record->getVersion()+1);
			}
				
			if ( $this->fireTriggers ){
				$res2 = $this->fireAfterUpdate($record);
				if ( PEAR::isError($res2) ) return $res2;
			}
			
			
		}
		
		
		return true;
		
		
	
	}
	
	/**
	 * @param tablename Optional tablename where record can be inserted.  Should have same schema as the main table.
	 */
	function _insert(&$record, $tablename=null, $secure=false){
		if ( $secure && !$record->checkPermission('new') ){
			// Use security to check to see if we are allowed to delete this 
			// record.
			return Dataface_Error::permissionDenied(
				df_translate(
					'scripts.Dataface.IO._insert.PERMISSION_DENIED',
					'Could not insert record "'.$record->getTitle().'" from table "'.$record->_table->tablename.'" because you have insufficient permissions.',
					array('title'=>$record->getTitle(), 'table'=>$record->_table->tablename)
					)
				);
		}
		if ( $secure ){
			foreach ( array_keys($record->_table->fields()) as $fieldname ){
				if ( $record->valueChanged($fieldname) and !@$record->vetoFields[$fieldname] and !$record->checkPermission('new', array('field'=>$fieldname)) ){
					// If this field was changed and the field doesn't have veto power, then
					// we must subject the change to a security check - the user must havce
					// edit permission to perform the change.
					
					if ( @$field['timestamp'] ){
						// Since timestamps are just updated automatically,
						// we don't need to perform any permissions on it
						continue;
					}
					
					return Dataface_Error::permissionDenied(
						df_translate(
							'scripts.Dataface.IO._insert.PERMISSION_DENIED_FIELD',
							'Could not insert record "'.$record->getTitle().'" into table "'.$record->_table->tablename.'" because you do not have permission to modify the "'.$fieldname.'" column.',
							array('title'=>$record->getTitle(), 'table'=>$record->_table->tablename, 'field'=>$fieldname)
							)
						);
				}
			}
		
		}
	
		if ( $tablename === null and $this->_altTablename !== null ){
			$tablename = $this->_altTablename;
		}
		$s =& $this->_table;
		$delegate =& $s->getDelegate();
		
		if ( $this->fireTriggers ){
			$res = $this->fireBeforeInsert($record);
			if ( PEAR::isError($res) ) return $res;
		}
		
		
		
		$parentIO =& $this->getParentIO();
		if ( isset($parentIO) ){
			$parentRecord =& $record->getParentRecord();
			$res = $parentIO->write($parentRecord, $parentRecord->snapshotKeys());
			if ( PEAR::isError($res) ) return $res;
			unset($parentRecord);
		}
		
		$qb = new Dataface_QueryBuilder($s->tablename);
		$sql = $qb->insert($record, $this->tablename($tablename));
		if ( PEAR::isError($sql) ){
			
			throw new Exception(
				df_translate(
					'scripts.Dataface.IO._insert.ERROR_GENERATING_SQL',
					"Error generating sql for insert in IO::_insert()")
				, E_USER_ERROR);
			//return $sql;
		}
		

		//$res = mysql_query($sql, $s->db);
		$res = $this->dbObj->query($sql, $s->db, $this->lang);
		if ( !$res || PEAR::isError($res)){
			if ( in_array(mysql_errno($this->_table->db), array(MYSQL_ER_DUP_KEY,MYSQL_ER_DUP_ENTRY)) ){
				/*
				 * This is a duplicate entry.  We will handle this as an exception rather than an error because
				 * cases may arise in a database application when a duplicate entry will happen and the application
				 * will want to handle it in a graceful way.  Eg: If the user is entering a username that is the same
				 * as an existing name.  We don't want an ugle FATAL error to be thrown here.  Rather we want to 
				 * notify the application that it is a duplicate entry.
				 */
				return Dataface_Error::duplicateEntry(
					Dataface_LanguageTool::translate(
						/* i18n id */
						"Failed to insert record because of duplicate entry",
						/* Default error message */
						"Duplicate entry into table '".$s->tablename,
						/* i18n parameters */
						array('table'=>$s->tablename)
					)
				);
			}
			throw new Exception(
				df_translate(
					'scripts.Dataface.IO._insert.ERROR_INSERTING_RECORD',
					"Error inserting record: ")
				.(PEAR::isError($res)?$res->getMessage():mysql_error(df_db())).": SQL: $sql", E_USER_ERROR);
		}
		$id = df_insert_id($s->db);
		$this->insertIds[$this->_table->tablename] = $id;
		
		/*
		 * Now update the record to contain the proper id.
		 */
		$autoIncrementField = $s->getAutoIncrementField();
		if ( $autoIncrementField !== null ){
			$record->setValue($autoIncrementField, $id);
		}
		
		
		if ( $this->fireTriggers ){
			$res2 = $this->fireAfterInsert($record);
			if ( PEAR::isError($res2) ) return $res2;
		}
		
		return true;
	
	}
	
	
	function _writeRelationship($relname, $record){
		$s =& $this->_table;
		$rel =& $s->getRelationship($relname);
		
		if ( PEAR::isError($rel) ){
			$rel->addUserInfo(
				df_translate(
					'scripts.Dataface.IO._writeRelationship.ERROR_OBTAINING_RELATIONSHIP',
					"Error obtaining relationship $relname in IO::_writeRelationship()",
					array('relname'=>$relname,'line'=>0,'file'=>"_")
					)
				);
			return $rel;
		}
		
		$tables =& $rel['selected_tables'];
		$columns =& $rel['columns'];
		
		if ( count($tables) == 0 ){
			return PEAR::raiseError(
				Dataface_LanguageTool::translate(
					/* i18n id */
					"Failed to write relationship because not table was selected", 
					/* default error message */
					"Error writing relationship '$relname'.  No tables were selected",
					/* i18n parameters */
					array('relationship'=>$relname)
				),
				DATAFACE_E_NO_TABLE_SPECIFIED
			);
		}
		
		$records =& $record->getRelatedRecords($relname);
		$record_keys = array_keys($records);
		if ( PEAR::isError( $records) ){
			$records->addUserInfo(
				df_translate(
					'scripts.Dataface.IO._writeRelationship.ERROR_GETTING_RELATED_RECORDS',
					"Error getting related records in IO::_writeRelationship()",
					array('line'=>0,'file'=>"_")
					)
				);
			return $records;
		}
		
		
		
		foreach ($tables as $table){
			
			$rs =& Dataface_Table::loadTable($table, $s->db);
			$keys = array_keys($rs->keys());
			$cols = array();
			foreach ($columns as $column){
				if ( preg_match('/^'.$table.'\.(\w+)/', $column, $matches) ){
					$cols[] = $matches[1];
				}
			}
			
			
			foreach ($record_keys as $record_key){
				$changed = false;
					// flag whether this record has been changed
				$update_cols = array();
					// store the columns that have been changed and require update
					
				foreach ( $cols as $column ){
					// check each column to see if it has been changed
					if ( $s->valueChanged($relname.'.'.$column, $record_key) ){
						
						$changed = true;
						$update_cols[] = $column;
					} else {
						
					}
				}
				if ( !$changed ) continue;
					// if this record has not been changed with respect to the 
					// columns of the current table, then we ignore it.
					
				$sql = "UPDATE `$table` ";
				$set = '';
				foreach ( $update_cols as $column ){
					$set .= "SET $column = '".addslashes($rs->getSerializedValue($column, $records[$record_key][$column]) )."',";
				}
				$set = trim(substr( $set, 0, strlen($set)-1));
				
				$where = 'WHERE ';
				foreach ($keys as $key){
					$where .= "`$key` = '".addslashes($rs->getSerializedValue($key, $records[$record_key][$key]) )."' AND ";
				}
				$where = trim(substr($where, 0, strlen($where)-5));
				
				if ( strlen($where)>0 ) $where = ' '.$where;
				if ( strlen($set)>0 ) $set = ' '.$set;
				
				$sql = $sql.$set.$where.' LIMIT 1';
				
				//$res = mysql_query($sql, $s->db);
				$res = $this->dbObj->query($sql, $s->db, $this->lang);
				if ( !$res || PEAR::isError($res) ){
					throw new Exception( 
						df_translate(
							'scripts.Dataface.IO._writeRelationship.ERROR_UPDATING_DATABASE',
							"Error updating database with query '$sql': ".mysql_error($s->db),
							array('sql'=>$sql,'mysql_error'=>mysql_error($s->db))
							)
						, E_USER_ERROR);
				}
			}
			
			unset($rs);
		}
	}
	
	
	/**
	 * Takes an array of SQL query strings and performs them sequentially.
	 * Will replace special value "__Tablename__auto_increment__" with the insert_id
	 * from the table "Tablename" if one of the provided sql queries inserts a record
	 * into Tablename.
	 *
	 * @param $sql An associative array [Table name] -> [SQL Query] of sql statements
	 * 				to be executed.
	 */
	function performSQL($sql){
	
		$ids = array();
		$queue = $sql;
		$names = array_keys($sql);
		$tables = implode('|', $names );
		$skips = 0; // keep track of number of consecutive times we skip an iteration so we know when we have reached
					// a deadlock.
		
		if ( func_num_args() >= 2 ){
			$duplicates =& func_get_arg(1);
			if ( !is_array($duplicates) ){
				throw new Exception(
					df_translate(
						'scripts.Dataface.IO.performSQL.ERROR_PARAMETER_2',
						"In Dataface_IO::performSQL() 2nd argument is expected to be an array but received '".get_class($duplicates)."'.",
						array('class'=>get_class($duplicates))
						)
					, E_USER_ERROR);
			}
		} else {
			$duplicates = array();
		}
		$queryAttempts = array();
		$numQueries = count($queue);
		while (count($queue) > 0 and $skips < $numQueries){
			$current_query = array_shift($queue);
			$current_table = array_shift($names);
			if ( !isset($queryAttempts[$current_query]) ) $queryAttempts[$current_query] = 1;
			else $queryAttempts[$current_query]++;
			
			$matches = array();
			if ( preg_match('/__('.$tables.')__auto_increment__/', $current_query, $matches) ){
				$table = $matches[1];
				if ( isset($ids[$table]) ){
					$current_query = preg_replace('/__'.$table.'__auto_increment__/', $ids[$table], $current_query);
				} else {
					array_push($queue, $current_query);
					array_push($names, $current_table);
					$skips++;
					continue;
				}
			}
			

			//$res = mysql_query($current_query, $this->_table->db);
			$res = $this->dbObj->query($current_query, $this->_table->db, $this->lang);
			if ( !$res || PEAR::isError($res) ){
				if ( in_array(mysql_errno($this->_table->db), array(MYSQL_ER_DUP_KEY,MYSQL_ER_DUP_ENTRY)) ){
					/*
					 * This is a duplicate record (ie: it already exists)
					 */
					$duplicates[] = $current_table;
				} else if ( $queryAttempts[$current_query] < 3 and in_array(mysql_errno($this->_table->db), array(MYSQL_ER_NO_REFERENCED_ROW, MYSQL_ER_NO_REFERENCED_ROW_2, MYSQL_ER_ROW_IS_REFERENCED_2)) ){
					/**
					 * There is a foreign key constraint that is preventing us from inserting
					 * this row.  Perhaps we are just adding this row in the wrong order.
					 * Let's re-add it to the queue.
					 */
					 array_push($queue, $current_query);
					 array_push($names, $current_table);
					 
				
				} else {
					if ( in_array(mysql_errno($this->_table->db), array(MYSQL_ER_NO_REFERENCED_ROW, MYSQL_ER_NO_REFERENCED_ROW_2)) ){
						/*
							THis failed due to a foreign key constraint. 
						*/
						$err = PEAR::raiseError(
							sprintf(
								df_translate(
								'scripts.Dataface.IO.performSQL.ERROR_FOREIGN_KEY',
								'Failed to save record because a foreign key constraint failed: %s'
								),
								mysql_error(df_db())
							),
								
							DATAFACE_E_NOTICE
						);
						error_log($err->toString());
						return $err;
					}
				
					$err = PEAR::raiseError(DATAFACE_TABLE_SQL_ERROR, null,null,null, 
						df_translate(
							'scripts.Dataface.IO.performSQL.ERROR_PERFORMING_QUERY',
							"Error performing query '$current_query'",
							array('line'=>0,'file'=>'_','current_query'=>$current_query)
							)
						.mysql_errno($this->_table->db).': '.mysql_error($this->_table->db));
					throw new Exception($err->toString(), E_USER_ERROR);
				}
			}
			$ids[$current_table] = df_insert_id();
			self::touchTable($current_table);
			$skips = 0;
		}
		$this->insertids = $ids;
		
		return true;
					
	
	}
	
	
	/**
	 * Adds a new record to a relationships.
	 * @param $record A Dataface_RelatedRecord object to be added.
	 */
	function addRelatedRecord(&$record, $secure=false){
		if ( $secure && !$record->_record->checkPermission('add new related record', array('relationship'=>$record->_relationshipName) ) ){
			// Use security to check to see if we are allowed to delete this 
			// record.
			return Dataface_Error::permissionDenied(
				df_translate(
					'scripts.Dataface.IO.addRelatedRecord.PERMISSION_DENIED',
					'Could not add record "'.$record->getTitle().'" to relationship "'.$record->_relationshipName.'" of record "'.$record->_record->getTitle().'" because you have insufficient permissions.',
					array('title'=>$record->getTitle(), 'relationship'=>$record->_relationshipName, 'parent'=>$record->_record->getTitle())
					)
				);
		}
		
	
		$queryBuilder = new Dataface_QueryBuilder($this->_table->tablename);
		
		// Fire the "before events"
		if ( $this->fireTriggers ){
			$res = $this->fireBeforeAddRelatedRecord($record);
			if ( PEAR::isError($res) ) return $res;
		}
		
		if ( $this->fireTriggers ){
			$res = $this->fireBeforeAddNewRelatedRecord($record);
			if ( PEAR::isError($res) ) return $res;
		}
		
		
			
		
		
		
		
		// It makes sense for us to fire beforeSave, afterSave, beforeInsert, and afterInsert
		// events here for the records that are being inserted.  To do this we will need to extract
		// Dataface_Record objects for all of the tables that will have records inserted.
		$drecords =  $record->toRecords();
			// $drecords is an array of Dataface_Record objects
		
		foreach ( array_keys($drecords) as $recordIndex){
			$rio = new Dataface_IO($drecords[$recordIndex]->_table->tablename);
			
			$drec_snapshot = $drecords[$recordIndex]->strvals();
			
			$res = $rio->fireBeforeSave($drecords[$recordIndex]);
			if (PEAR::isError($res) ) return $res;
			$res = $rio->fireBeforeInsert($drecords[$recordIndex]);
			if ( PEAR::isError($res) ) return $res;
			
			$drec_post_snapshot = $drecords[$recordIndex]->strvals();

			foreach ( $drec_snapshot as $ss_key=>$ss_val ){

				if ( $drec_post_snapshot[$ss_key] != $ss_val ){

					$record->setValue($ss_key,$drec_post_snapshot[$ss_key]);
				}
			}

			unset($drec_snapshot);
			unset($drec_post_snapshot);
			unset($rio);
		}
		
		//$sql = Dataface_QueryBuilder::addRelatedRecord($record);
		$sql = $queryBuilder->addRelatedRecord($record);
		if ( PEAR::isError($sql) ){
			$sql->addUserInfo(
				df_translate(
					'scripts.Dataface.IO.addRelatedRecord.ERROR_GENERATING_SQL',
					"Error generating sql in ShortRelatedRecordForm::save()",
					array('line'=>0,'file'=>"_")
					)
				);
			return $sql;
		}
		
		// Actually add the record
		$res = $this->performSQL($sql);
		if ( PEAR::isError($res) ){
			return $res;
		}
		
		$rfields = array_keys($record->vals());
		// Just for completeness we will fire afterSave and afterInsert events for
		// all records being inserted.
		foreach ( array_keys($drecords) as $recordIndex){
			$currentRecord =& $drecords[$recordIndex];
			if ( isset($this->insertids[ $currentRecord->_table->tablename ] ) ){
				$idfield = $currentRecord->_table->getAutoIncrementField();
				if ( $idfield ){
					$currentRecord->setValue($idfield, $this->insertids[ $currentRecord->_table->tablename ]);
					if ( in_array($idfield, $rfields) ){
						$record->setValue($idfield, $this->insertids[ $currentRecord->_table->tablename ]);
					}
				}
				
				unset($idfield);
			}
			unset($currentRecord);
			$rio = new Dataface_IO($drecords[$recordIndex]->_table->tablename);
			
			$res = $rio->saveTransients($drecords[$recordIndex], null, null, true);
			if ( PEAR::isError($res) ){
				return $res;
			}
			
			$res = $rio->fireAfterInsert($drecords[$recordIndex]);
			if (PEAR::isError($res) ) return $res;
			$res = $rio->fireAfterSave($drecords[$recordIndex]);
			if ( PEAR::isError($res) ) return $res;
			
			unset($rio);
		}
		
		
		// Fire the "after" events
		if ( $this->fireTriggers ){
			$res2 = $this->fireAfterAddNewRelatedRecord($record);
			if ( PEAR::isError($res2) ) return $res2;
			
			$res2 = $this->fireAfterAddRelatedRecord($record);
			if ( PEAR::isError($res2) ) return $res2;
		}
		
		return $res;
	}
	
	/**
	 * Adds an existing record to a relationship.
	 * @param $record a Dataface_RelatedRecord object to be added.
	 */
	function addExistingRelatedRecord(&$record, $secure=false){
		if ( $secure && !$record->_record->checkPermission('add existing related record', array('relationship'=>$record->_relationshipName) ) ){
			// Use security to check to see if we are allowed to delete this 
			// record.
			return Dataface_Error::permissionDenied(
				df_translate(
					'scripts.Dataface.IO.addExistingRelatedRecord.PERMISSION_DENIED',
					'Could not add record "'.$record->getTitle().'" to relationship "'.$record->_relationshipName.'" of record "'.$record->_record->getTitle().'" because you have insufficient permissions.',
					array('title'=>$record->getTitle(), 'relationship'=>$record->_relationshipName, 'parent'=>$record->_record->getTitle())
					)
				);
		}
		
		$builder = new Dataface_QueryBuilder($this->_table->tablename);
		
		//We are often missing the values from the domain table so we will load them
		//here
		$domainRec = $record->toRecord($record->_relationship->getDomainTable());
		$domainRec2 = df_get_record_by_id($domainRec->getId());
		//$record->setValues(array_merge($domainRec2->vals(), $record->vals()));
		foreach ($domainRec2->vals() as $dreckey=>$drecval){
			if ( !$record->val($dreckey) ) $record->setValue($dreckey, $drecval);
		}
		// fire the "before" events
		if ( $this->fireTriggers ){
			$res =$this->fireBeforeAddRelatedRecord($record);
			if ( PEAR::isError($res) ) return $res;
			
			$res = $this->fireBeforeAddExistingRelatedRecord($record);
			if ( PEAR::isError($res) ) return $res;
		}
			
		
		
		
		
		// It makes sense for us to fire beforeSave, afterSave, beforeInsert, and afterInsert
		// events here for the records that are being inserted.  To do this we will need to extract
		// Dataface_Record objects for all of the tables that will have records inserted.  In this
		// case we are not updated any records because relationships are created by adding a record
		// to the join table.  This means that we are also NOT adding a record to the domain table.
		// i.e., we should only fire these events for the join table.
		$drecords =  $record->toRecords();
			// $drecords is an array of Dataface_Record objects
		
		if ( count($drecords) > 1 ){
			// If there is only one record then it is for the domain table - which we don't actually 
			// change.
			foreach ( array_keys($drecords) as $recordIndex){
				$currentRecord =& $drecords[$recordIndex];
				if ( isset($this->insertids[ $currentRecord->_table->tablename ] ) ){
					$idfield =& $currentRecord->_table->getAutoIncrementField();
					if ( $idfield ){
						$currentRecord->setValue($idfield, $this->insertids[ $currentRecord->_table->tablename ]);
					}
					unset($idfield);
				}
				unset($currentRecord);
				if ( $drecords[$recordIndex]->_table->tablename === $record->_relationship->getDomainTable() ) continue;
					// We don't do anything for the domain table because it is not being updated.
					
				$rio = new Dataface_IO($drecords[$recordIndex]->_table->tablename);
				
				$drec_snapshot = $drecords[$recordIndex]->strvals();
				
				$res = $rio->fireBeforeSave($drecords[$recordIndex]);
				if (PEAR::isError($res) ) return $res;
				$res = $rio->fireBeforeInsert($drecords[$recordIndex]);
				if ( PEAR::isError($res) ) return $res;
				
				$drec_post_snapshot = $drecords[$recordIndex]->strvals();
				
				foreach ( $drec_post_snapshot as $ss_key=>$ss_val ){
					if ( $drec_snapshot[$ss_key] != $ss_val ){
						$drecords[$recordIndex]->setValue($ss_key,$ss_val);
					}
				}
				
				unset($drec_post_snapshot);
				unset($drec_snapshot);
				unset($rio);
			}
		} 
		
		
		if ( count($drecords) > 1 ){
			$sql = $builder->addExistingRelatedRecord($record);
			if ( PEAR::isError($sql) ){
				return $sql;
			}
			// Actually add the related record
			$res = $this->performSQL($sql);
			if ( PEAR::isError( $res) ) return $res;
			
			// If there is only one record then it is for the domain table - which we don't actually 
			// change.
			foreach ( array_keys($drecords) as $recordIndex){
			
				if ( $drecords[$recordIndex]->_table->tablename === $record->_relationship->getDomainTable() ) continue;
					// We don't do anything for the domain table because it is not being updated.
					
				$rio = new Dataface_IO($drecords[$recordIndex]->_table->tablename);
				
				$res = $rio->fireAfterInsert($drecords[$recordIndex]);
				if (PEAR::isError($res) ) return $res;
				$res = $rio->fireAfterSave($drecords[$recordIndex]);
				if ( PEAR::isError($res) ) return $res;
				
				unset($rio);
			}
		} else {
			
		
			// This is a one to many relationship.  We will handle this case
			// only when the foreign key is currently null.  Otherwise we return
			// and error.
			$fkeys = $record->_relationship->getForeignKeyValues();
			$fkeyvals = $record->getForeignKeyValues();
			if ( isset($fkeys[$domainRec2->_table->tablename]) ){
				$drecid = $domainRec2->getId();
				unset($domainRec2);
				$domainRec2 = df_get_record_by_id($drecid);
				if ( !$domainRec2 ){
					return PEAR::raiseError("Tried to get record with id $drecid but it doesn't exist");
					
				} else if ( PEAR::isError($domainRec2) ){
					return $domainRec2;
				}
				foreach ( array_keys($fkeys[$domainRec2->_table->tablename]) as $fkey){
					//echo $fkey;

					if ( $domainRec2->val($fkey) ){
						return PEAR::raiseError("Could not add existing related record '".$domainRec2->getTitle()."' because it can only belong to a single relationship and it already belongs to one.");
						
					} else {
						
						$domainRec2->setValue($fkey, $fkeyvals[$domainRec2->_table->tablename][$fkey]);
					}
				}

				$res = $domainRec2->save($secure);
				if ( PEAR::raiseError($res) ) return $res;
			} else {
				return PEAR::raiseError("Failed to add existing record because the domain table doesn't have any foreign keys in it.");
			}
			
			
		}
		
		// Fire the "after" events
		if ( $this->fireTriggers ){
			$res2 = $this->fireAfterAddExistingRelatedRecord($record);
			if ( PEAR::isError( $res2 ) ) return $res2;
			
			$res2 = $this->fireAfterAddRelatedRecord($record);
			if ( PEAR::isError( $res2 ) ) return $res2;
		}
			
		return $res;
	
	}
	
	/**
	 * Removes the given related record from its relationship.
	 *
	 * @param Dataface_RelatedRecord &$related_record The related record to be removed.
	 * @param boolean $delete If true then the record will also be deleted from 
	 * 	the database.
	 * @since 0.6.1
	 */
	function removeRelatedRecord(&$related_record, $delete=false, $secure=false){
		if ( $secure && !$related_record->_record->checkPermission('remove related record', array('relationship'=>$related_record->_relationshipName) ) ){
			// Use security to check to see if we are allowed to delete this 
			// record.

			return Dataface_Error::permissionDenied(
				df_translate(
					'scripts.Dataface.IO.removeRelatedRecord.PERMISSION_DENIED',
					'Could not remove record "'.$related_record->getTitle().'" from relationship "'.$related_record->_relationshipName.'" of record "'.$related_record->_record->getTitle().'" because you have insufficient permissions.',
					array('title'=>$related_record->getTitle(), 'relationship'=>$related_record->_relationshipName, 'parent'=>$related_record->_record->getTitle())
					)
				);
		}
		
		$res = $this->fireEvent('beforeRemoveRelatedRecord', $related_record);
		if ( PEAR::isError($res) ) return $res;
		/*
		 * First we need to find out which table is the domain table.  The domain table
		 * is the table that actually contains the records of interest.  The rest of
		 * the tables are referred to as 'join' tables.
		 */
		$domainTable = $related_record->_relationship->getDomainTable();
		if ( PEAR::isError($domainTable) ){
			/*
			 * Dataface_Relationship::getDomainTable() throws an error if there are 
			 * no join tables.  We account for that by explicitly setting the domain
			 * table to the first table in the list.
			 */
			$domainTable = $related_record->_relationship->_schema['selected_tables'][0];
		}
		/*
		 * Next we construct an IO object to write to the domain table.
		 */
		$domainIO = new Dataface_IO($domainTable);
		
		$domainTable =& Dataface_Table::loadTable($domainTable);
			// reference to the Domain table Dataface_Table object.
		
		/*
		 * Begin building queries.
		 */
		$query = array();
			// query array to build the query to delete the record.
		$absVals = array();
			// same as query array except the keys are absolute field names (ie: Tablename.Fieldname)
		$currKeyNames = array_keys($domainTable->keys());
			// Names of key fields in the domain table
		foreach ($currKeyNames as $keyName){
			$query[$keyName] = $related_record->strval($keyName);
			$absVals[$domainTable->tablename.'.'.$keyName] = $query[$keyName];
		}
		
		
		$fkeys = $related_record->_relationship->getForeignKeyValues($absVals, null, $related_record->_record);
		$warnings = array();
		$confirmations = array();
		foreach ( array_keys($fkeys) as $currTable){
			// For each table in the relationship we go through and delete its record.
			$io = new Dataface_IO($currTable);
				
			$record = new Dataface_Record($currTable, array());
			$res = $io->read($fkeys[$currTable], $record);
			//patch for Innodb foreign keys with ON DELELE CASCADE
			// Contributed by Optik
			if (!$io->recordExists($record,null,$currTable)){
				$warnings[] = df_translate(
					'scripts.Dataface.IO.removeRelatedRecord.ERROR_RECORD_DOESNT_EXIST',
					"Failed to delete entry for record '".$record->getTitle()."' in table '$currTable' because record doesn't exist.",
					array('title'=>$record->getTitle(), 'currTable'=>$currTable)
					);
				unset($record);
				unset($io);
				continue;
			}
			// -- end patch for Innodb foreign keys
			if ( $currTable == $domainTable->tablename and !$delete ){
				// Unless we have specified that we want the domain table record
				// deleted, we leave it alone!
				
				
				
				// If this is a one to many we'll try to just set the foreign key to null
				if ( count($fkeys) == 1 ){
					
					if (($currTable == $domainTable->tablename) and $secure and !$related_record->_record->checkPermission('remove related record', array('relationship'=>$related_record->_relationshipName)) ){
						$useSecurity = true;
						
					} else {
						$useSecurity = false;
					}
				
					$myfkeys = $related_record->_relationship->getForeignKeyValues();
					foreach ( $myfkeys[$currTable] as $colName=>$colVal ){
						$record->setValue($colName, null);
						
					}
					//exit;
					
					$res = $record->save(null, $useSecurity);
					if ( PEAR::isError($res) && Dataface_Error::isError($res) ){
						//$this->logError($res);
						return $res;
					} else if ( PEAR::isError($res) ){
						$warnings[] = $res;
						
					} else {
					
						$confirmations[] = df_translate(
						'Successfully removed record',
						"Successfully removed entry for record '".$record->getTitle()."' in table '$currTable'",
						array('title'=>$record->getTitle(), 'table'=>$currTable)
						);
						
					}
							
					
				}
				
				unset($record);
				unset($io);
				continue;
			}
			
			// Let's figure out whether we need to use security for deleting this
			// record.
			// If security is on, and it is the domain table, and the user doesn't
			// have the 'delete related record' permission  then we need to use
			// security
			if (($currTable == $domainTable->tablename) and $secure and !$related_record->_record->checkPermission('delete related record', array('relationship'=>$related_record->_relationshipName)) ){
				$useSecurity = true;
				
			} else {
				$useSecurity = false;
			}

			$res = $io->delete($record, $useSecurity);
			
			if ( PEAR::isError($res) && Dataface_Error::isError($res) ){
				//$this->logError($res);
				return $res;
			} else if ( PEAR::isError($res) ){
				$warnings[] = $res;
			}
			else {
				$confirmations[] = df_translate(
					'Successfully deleted record',
					"Successfully deleted entry for record '".$record->getTitle()."' in table '$currTable'",
					array('title'=>$record->getTitle(), 'table'=>$currTable)
					);
			}
			$record->__destruct();
			unset($record);
			unset($b);
			unset($io);
		
		}
		$res = $this->fireEvent('afterRemoveRelatedRecord', $related_record);
		if ( PEAR::isError($res) ) return $res;
		if (count($warnings)>0 ) return PEAR::raiseError(@implode("\n",$warnings), DATAFACE_E_WARNING);
		if (count($confirmations)==0) return false;
		return true;
		
	}
	
	/**
	 * Copies a record from a relationship in one parent record to another.
	 * Copies are a little bit difficult to define in a relational database,
	 * but, this copy uses a few rules to make it more clear.
	 * <p><b><em>Note that this method is not implemented yet.. it will throw 
	 *		and error if called.</em></b></p>
	 * <ul>
	 * <li>A deep copy will recursively perform deep copies of records in 
	 *		one-to-many relationships, and maintain links to records in
	 *		many-to-many relationships.</li>
	 * <li>A shallow copy (default behavior) maintains links to records
	 *	in many-to-many relationships and recursively performs shallow copies
	 *	of all records in "children" relationships.</li>
	 * </ul>
	 *
	 * @param Dataface_RelatedRecord &$sourceRecord The record that is being copied.
	 * @param Dataface_Record &$destParent The record that will be the parent of the
	 *	copied record.  I.e. this is the destination of the copy.
	 * @param string $destRelationship The name of the relationship into which the
	 *	record is to be copied.  If this parameter is left null, it will automatically
	 *	use the relationship specified as a "children" relationship.  If no "children"
	 *	relationship can be found, then an error will be thrown.
	 * @param boolean $deepCopy If true then a deep copy will be performed.  Otherwise
	 *	the default behavior is to perform a shallow copy.
	 * @return mixed Returns a PEAR_Error object if the copy fails.
	 */
	function copy(&$sourceRecord, &$destParent, $destRelationship=null, $deepCopy=false){
		throw new Exception("The method ".__METHOD__." is not implemented yet.", E_USER_ERROR);
	}
	

	
	// Event handlers.
	
	/**
	 * Calls the beforeSave() method in the delegate class.
	 * @param $record Dataface_Record object that is being saved.
	 */
	function fireBeforeSave(&$record){
		return $this->fireEvent('beforeSave', $record);
	}
	
	/**
	 * Calls the afterSave() method in the delegate class.
	 * @param $record Dataface_Record object that is being saved.
	 */
	function fireAfterSave(&$record){
		return $this->fireEvent('afterSave', $record);
	}
	
	/**
	 * Calls the beforeUpdate() method in the delegate class.
	 * @param $record Dataface_Record object that is being updated.
	 */
	function fireBeforeUpdate(&$record){
		return $this->fireEvent('beforeUpdate', $record);
	}
	
	
	/**
	 * Calls the afterUpdate() method in the delegate class.
	 * @param $record Dataface_Record object that is being updated.
	 */
	function fireAfterUpdate(&$record){
		return $this->fireEvent('afterUpdate', $record);
	}
	
	/**
	 * Calls the beforeInsert() method in the delegate class.
	 * @param $record Dataface_Record object that is being inserted.
	 */
	function fireBeforeInsert(&$record){
		return $this->fireEvent('beforeInsert', $record);
	}
	
	/**
	 * Calls the afterInsert() method in the delegate class.
	 * @param $record Dataface_Record object that is being inserted.
	 */
	function fireAfterInsert(&$record){
		return $this->fireEvent('afterInsert', $record);
	}
	
	/**
	 * Calls the beforeAddRelatedRecord() method in the delegate class.
	 * @param $record Dataface_RelatedRecord object that is being added.
	 */
	function fireBeforeAddRelatedRecord(&$record){
		return $this->fireEvent('beforeAddRelatedRecord', $record);
	}
	
	/**
	 * Calls the afterAddRelatedRecord() method in the delegate class.
	 * @param $record Dataface_RelatedRecord object that is being added.
	 */
	function fireAfterAddRelatedRecord(&$record){
		return $this->fireEvent('afterAddRelatedRecord', $record);
	
	}
	
	/**
	 * Calls the beforeAddNewRelatedRecord() method in the delegate class.
	 * @param $record Dataface_RelatedRecord object that is being added.
	 */
	function fireBeforeAddNewRelatedRecord(&$record){
		return $this->fireEvent('beforeAddNewRelatedRecord', $record);
	}
	
	/**
	 * Calls the afterAddNewRelatedRecord() method in the delegate class.
	 * @param $record Dataface_RelatedRecord object that is being added.
	 */
	function fireAfterAddNewRelatedRecord(&$record){
		return $this->fireEvent('afterAddNewRelatedRecord', $record);
	}
	
	/**
	 * Calls the beforeAddExistingRelatedRecord() method in the delegate class.
	 * @param $record Dataface_RelatedRecord object that is being added.
	 */
	function fireBeforeAddExistingRelatedRecord(&$record){
		return $this->fireEvent('beforeAddExistingRelatedRecord', $record);
	}
	
	/**
	 * Calls the afterAddExistingRelatedRecord() method in the delegate class.
	 * @param $record Dataface_RelatedRecord object that is being added.
	 */
	function fireAfterAddExistingRelatedRecord(&$record){
		return $this->fireEvent('afterAddExistingRelatedRecord', $record);
	}
	
	/**
	 * Calls the beforeDelete() method in the delegate class.
	 * @param $record Dataface_Record object to be deleted.
	 */
	function fireBeforeDelete(&$record){
		return $this->fireEvent('beforeDelete', $record);
	}
	
	/**
	 * Calls the afterDelete method in the delegate class.
	 * @param $record Dataface_Record object to be deleted.
	 */
	function fireAfterDelete(&$record){
		return $this->fireEvent('afterDelete', $record);
	}
	
	/**
	 * Fires an event (a method of the delegate class).
	 * @param $name The name of the event (also the name of the method in the delegate class.
	 * @param $record Either a Dataface_Record or Dataface_RelatedRecord object depending on context.
	 */
	function fireEvent($name, &$record, $bubble=true){
		$oldVeto = $record->vetoSecurity;
		$record->vetoSecurity = true;
		$delegate =& $this->_table->getDelegate();
		if ( $delegate !== null and method_exists($delegate,$name) ){
			$res = $delegate->$name($record);
			if ( PEAR::isError( $res ) ){
				$res->addUserInfo(
					df_translate(
						'scripts.Dataface.IO.fireEvent.ERROR_WHILE_FIRING',
						"Error while firing event '$name' on table '".$this->_table->tablename."' in Dataface_IO::write() ",
						array('name'=>$name,'tablename'=>$this->_table->tablename, 'line'=>0,'file'=>"_")
						)
					);
				$record->vetoSecurity = $oldVeto;
				return $res;
			}
		}
		
		$parentIO =& $this->getParentIO();
		if ( isset($parentIO) ){
			$parentIO->fireEvent($name, $record, false);
		}
		
		if ( $bubble ){
			$app =& Dataface_Application::getInstance();
			$res = $app->fireEvent($name, array(&$record, &$this));
			if ( PEAR::isError($res) ) {
				$record->vetoSecurity = $oldVeto;
				return $res;
			}
		}
		
		return true;
	
	}
	
	
	
	
	
	/**
	 * A convenience method that returns the given parameter (if it is not null) or
	 * $this->_tablename if the parameter $tablename is null.  This is handy when
	 * we want to pass an alternate tablename to select(), etc... if it is supplied.
	 *
	 */
	function tablename($tablename=null){
		if ( $tablename !== null ) return $tablename;
		return $this->_table->tablename;
		
	}
	
	
	/**
	 * 
	 * Imports data into the supplied record's relationship.  This makes use of this table's delegate
	 * file to handle the importing.
	 *
	 * @param 	$record 			A Dataface_Record object whose relationship is to have records added to it.
	 * @type Dataface_Record | null
	 *
	 * @param 	$data 				Either raw data that is to be imported, or the name of an Import table from which
	 * 							data is to be imported.
	 * @type Raw | string
	 * 
	 * @param	$importFilter		The name of the import filter that should be used.
	 * @type string
	 * 
	 * @param	$relationshipName	The name of the relationship where these records should be added.
	 * @type string
	 * 
	 * @param $commit				A boolean value indicating whether this import should be committed to the 
	 *								database.  If this is false, then the records will not actually be imported.  They
	 *								will merely be stored in an import table.  This must be explicitly set to true
	 *								for the import to succeed.
	 * @type boolean
	 *
	 * @param defaultValues			Array of default values of the form [Abs fieldname] -> [field value], where 'Abs fieldname'
	 *								is the absolute field name (ie: tablename.fieldname).  All imported records will attain
	 *								these default values.
	 * @type array([string] -> [mixed])
	 *
	 * @return						Case 1: The import succeeds.
	 *									Case 1.1: if commit = false return Import Table name where data is stored.
	 *									Case 1.2: If commit = true return array of Dataface_Record objects that were inserted.
	 *								Case 2: The import failed
	 *									return PEAR_Error object.
	 *
	 *
	 * Usage:
	 * -------
	 * $data = '<phonelist>
	 *				<listentry>
	 *					<name>John Smith</name><number>555-555-5555</number>
	 *				</listentry>
	 *				<listentry>
	 *					<name>Susan Moore</name><number>444-444-4444</number>
	 *				</listentry>
	 *			</phonelist>';
	 * 
	 * 		// assume that we have an import filter called 'XML_Filter' that can import the above data.
	 * 
	 * $directory = new Dataface_Record('Directory', array('Name'=>'SFU Directory'));
	 * 		// assume that the Directory table has a relationship called 'phonelist' and we want to 
	 *		// import the above data into this relationship.
	 *
	 * $io = new Dataface_IO('Directory');
	 * $importTableName = $io->importData(	$directory,		// The record that owns the relationship where imported records will be added
	 *										$data, 			// The raw data to import
	 *										'XML_Filter', 	// The name of the impot
	 *										'phonelist'
	 *									);
	 *		// Since we didn't set the $commit flag, the data has been imported into an import table
	 *		// whose name is stored now in $importTableName.
	 *
	 *  //
	 *  // Now suppose we have confirmed that the import is what we want to do and we are ready to import
	 *	// the data into the database.
	 * $records = $io->importData($directory, $importTableName, null, 'phonelist', true );
	 *
	 * echo $records[0]->val('name'); 	// should output 'John Smith'
	 * echo $records[0]->val('number'); // should output '555-555-5555'
	 * echo $records[1]->val('name'); 	// should output 'Susan Moore'
	 * echo $records[1]->val('number'); // should output '444-444-4444'
	 * 
	 *  // note that at this point the records in $records are already persisted to the database
	 *
	 */
	function importData( &$record, $data, $importFilter=null, $relationshipName=null, $commit=false, $defaultValues=array()){
		if ( $relationshipName === null ){
			
			/*
			 * No relationship is specified so our import table is just the current table.
			 */
			$table =& $this->_table;
			
		} else {
			/*
			 * A relationship is specified so we are actually importing the records into the
			 * domain table of the relationship.
			 */
			
			$relationship =& $this->_table->getRelationship($relationshipName);
			$tablename = $relationship->getDomainTable();
			if ( PEAR::isError($tablename) ){
				/*
				 * This relationship does not have a domain table.. so we will just take the destination table.
				 */
				$destinationTables =& $relationship->getDestinationTables();
				if ( count($destinationTables) <= 0 ){
					throw new Exception(
						df_translate(
							'scripts.Dataface.IO.importData.ERROR_NO_DESTINATION_TABLES',
							"Error occurred while attempting to parse import data into a table.  The relationship '".$relationship->getName()."' of table '".$this->_table->tablename."' has not destination tables listed.  It should have at least one.\n",
							array('relationship'=>$relationship->getName(), 'table'=>$this->_table->tablename)
							)
						, E_USER_ERROR);
				}
				$tablename = $destinationTables[0]->tablename;
				
			}
			
			if ( PEAR::isError($tablename) ){
				throw new Exception($tablename->toString(), E_USER_ERROR);
			}
			$table =& Dataface_Table::loadTable($tablename);
			$rel_io = new Dataface_IO($tablename);
			$io =& $rel_io;
		}
		
		if ( !$commit ){
			// If data is provided, we must parse it and prepare it for 
			// import
			$records = $table->parseImportData($data, $importFilter, $defaultValues);
			if ( PEAR::isError($records) ){
				/*
				 * The import didn't work with the specified import filter, so we will
				 * try the other filters.
				 */
				$records = $table->parseImportData($data, null, $defaultValues);
			}
			
			if ( PEAR::isError($records) ){
				/*
				 * Apparently we have failed to import the data, so let's just 
				 * return the errors.
				 */
				return $records;
			}
			
			// Now we will load the values of the records into an array
			// so that we can store it in the session
			$importData = array(
				'table' => $table->tablename,
				'relationship' => $relationshipName,
				'defaults' => $defaultValues,
				'importFilter' => $importFilter,
				'record' => null,
				'rows' => array()
				);
			if ( isset($record) ) $importData['record'] = $record->getId();
			
			foreach ($records as $r){
				if ( is_a($r, 'Dataface_ImportRecord') ){
					// The current record is actually an ImportRecord
					$importData['rows'][] = $r->toArray();
				} else {
					$importData['rows'][] = $r->vals(array_keys($r->_table->fields(false,true)));
					unset($r);
				}
			}
			
			$dumpFile = tempnam(sys_get_temp_dir(), 'dataface_import');
			$handle = fopen($dumpFile, "w");
			if ( !$handle ){
				throw new Exception("Could not write import data to dump file $dumpFile", E_USER_ERROR);
			}
			fwrite($handle, serialize($importData));
			fclose($handle);
			
			$_SESSION['__dataface__import_data__'] =  $dumpFile;

			return $dumpFile;
			
		}
		
		if ( !@$_SESSION['__dataface__import_data__'] ){
			throw new Exception("No import data to import", E_USER_ERROR);
		}
		
		$dumpFile = $_SESSION['__dataface__import_data__'];
		$importData = unserialize(file_get_contents($dumpFile));
		
		
		if ( $importData['table'] != $table->tablename ){
			return PEAR::raiseError("Unexpected table name in import data.  Expected ".$table->tablename." but received ".$importData['table']);
			
		}
		
		$inserted = array();
		$i=0;
		foreach ( $importData['rows'] as $row ){
			if ( isset($row['__CLASS__']) and isset($row['__CLASSPATH__']) ){
				// This row is an import record - not merely a Dataface_Record
				// object so it provides its own logic to import the records.
				import($row['__CLASSPATH__']);
				$class = $row['__CLASS__'];
				$importRecord = new $class($row);
				$res = $importRecord->commit($record, $relationshipName);
				if ( PEAR::isError($res) ){
					return $res;
				}
			} else {
				$values = array();
				foreach (array_keys($row) as $key){
					if ( !is_int($key) ){
						$values[$key] = $row[$key];
					}
				}
				if ( $relationshipName === null ){
					/*
					 * These records are not being added to a relationship.  They are just being added directly
					 * into the table.
					 */
					 
					$defaults = array();
					// for absolute field name keys for default values, we will strip out the table name.
					foreach (array_keys($defaultValues) as $key){
						if ( strpos($key,'.') !== false ){
							list($tablename, $fieldname) = explode('.', $key);
							if ( $tablename == $this->_table->tablename ){
								$defaults[$fieldname] = $defaultValues[$key];
							} else {
								continue;
							}
						} else {
							$defaults[$key] = $defaultValues[$key];
						}
					}
					
					$values = array_merge($defaults, $values);
					$insrecord = new Dataface_Record($this->_table->tablename, $values);
					$inserted[] =& $insrecord;
					$this->write($insrecord);
					$insrecord->__destruct();
					unset($insrecord);
				} else {
					/*
					 * The records are being added to a relationship so we need to make sure that we add the appropriate
					 * entries to the "join" tables as well.
					 */
					foreach (array_keys($values) as $key){
						$values[$table->tablename.'.'.$key] = $values[$key];
						unset($values[$key]);
					}
					
					$values = array_merge( $defaultValues, $values);
					
					/*
					 * Let's check if all of the keys are set.  If they are then the record already exists.. we
					 * just need to update the record.
					 *
					 */
					$rvalues = array();
					foreach ( $values as $valkey=>$valval){
						if ( strpos($valkey,'.') !== false ){
							list($tablename,$fieldname) = explode('.',$valkey);
							if ( $tablename == $table->tablename ){
								$rvalues[$fieldname] = $valval;
							}
						}
					}
					$rrecord = new Dataface_Record( $table->tablename, array());
				
					$rrecord->setValues($rvalues);
						// we set the values in a separate call because we want to be able to do an update
						// and setting values in the constructer sets the snapshot (ie: it will think that
						// no values have changed.
					
					if ( $io->recordExists($rrecord)){
						/*
						 * The record already exists, so we update it and then add it to the relationship.
						 *
						 */
						if ( Dataface_PermissionsTool::edit($rrecord) ){
							/*
							 * We only edit the record if we have permission to do so.
							 */
						
							$result = $io->write($rrecord);
							if ( PEAR::isError($result) ){
								throw new Exception($result->toString(), E_USER_ERROR);
							}
						}
						$relatedRecord = new Dataface_RelatedRecord( $record, $relationshipName, $values);
						$inserted[] =& $relatedRecord;
						$qb = new Dataface_QueryBuilder($this->_table->tablename);
						$sql = $qb->addExistingRelatedRecord($relatedRecord);
						
						$res2 = $this->performSQL($sql);
					
						unset($relatedRecord);
			
						
					} else {
					
						$relatedRecord = new Dataface_RelatedRecord( $record, $relationshipName, $values);
						$inserted[] =& $relatedRecord;
						$qb = new Dataface_QueryBuilder($this->_table->tablename);
						$sql = $qb->addRelatedRecord($relatedRecord);
						
						$res2 = $this->performSQL($sql);
						
						unset($relatedRecord);
					}
					
					unset($rrecord);
					
					
				}
			}
		
			unset($row);
		}
		
		
		@unlink($dumpFile);
		unset($_SESSION['__dataface__import_data__']);
		
		return $inserted;
		
		
	
	
	}
	
	
	/**
	 * Returns a record or record value given it's unique URI.
	 * @param string $uri The URI of the data we wish to retrieve.
	 * The URI must be of one of the following forms:
	 * tablename?key1=val1&keyn=valn#fieldname
	 * tablename?key1=val1&keyn=valn
	 * tablename/relationshipname?key1=val1&keyn=valn&relationshipname::relatedkey=relatedval#fieldname
	 * tablename/relationshipname?key1=val1&keyn=valn&relationshipname::relatedkey=relatedval
	 * 
	 * Where url encoding is used as in normal HTTP urls.  If a field is specified (after the '#')
	 *
	 * @param string $filter The name of a filter to pass the data through.  This
	 * 		is only applicable when a field name is specified.  Possible filters 
	 *		include: 
	 *			strval - Returns the string value of the field. (aka stringValue, getValueAsString)
	 *			display - Returns the display value of the field. (This substitutes valuelist values)
	 *			htmlValue - Returns the html value of the field.
	 *			preview - Returns the preview value of the field (usually this limits
	 *					  the length of the output and strips any HTML.
	 *
	 * @returns mixed Either a Dataface_Record object, a Dataface_RelatedRecord object
	 *				of a value as stored in the object.  The output depends on 
	 *				the input.  If it receives invalid input, it will return a PEAR_Error
	 *				object.
	 *
	 * Example usage:
	 *
	 * <code>
	 * // Get record from Users table with UserID=10
	 * $user =& Dataface_IO::getByID('Users?UserID=10');
	 * 		// Dataface_Record object
	 * 
	 * // get birthdate of user with UserID=10
	 * $birthdate =& Dataface_IO::getByID('Users?UserID=10#birthdate');
	 *		// array('year'=>'1978','month'=>'12','day'=>'27', ...)
	 *
	 * // get related record from jobs relationship of user with UserID=10
	 * // where the jobtitle is "cook"
	 * $job =& Dataface_IO::getByID('Users?UserID=10&jobs::jobtitle=cook");
	 * 		// Dataface_RelatedRecord object
	 * 
	 * // Get the employers name of the cook job
	 * $employername = Dataface_IO::getByID('Users?UserID=10&jobs::jobtitle=cook#employername');
	 *		// String
	 *
	 * // Add filter, so we get the HTML value of the bio field rather than just 
	 * // the raw value.
	 * $bio = Dataface_IO::getByID('Users?UserID=10#bio', 'htmlValue');
	 *
	 * </code>
	 */
	static function &getByID($uri, $filter=null){
		if ( strpos($uri, '?') === false ) return PEAR::raiseError("Invalid record id: ".$uri);
		$uri_parts = df_parse_uri($uri);
		if ( PEAR::isError($uri_parts) ) return $uri_parts;
		if ( !isset($uri_parts['relationship']) ){
			// This is just requesting a normal record.
			
			// Check to see if this is to be a new record or an existing record
			if ( @$uri_parts['action'] and ( $uri_parts['action'] == 'new' ) ){
				$record = new Dataface_Record($uri_parts['table'], array());
				$record->setValues($uri_parts['query']);
				return $record;
			}
			
			foreach ($uri_parts['query'] as $ukey=>$uval){
				if ( $uval and $uval{0}!='=' ) $uval = '='.$uval;
				$uri_parts['query'][$ukey]=$uval;
			}
			// At this point we are sure that this is requesting an existing record
			$record =& df_get_record($uri_parts['table'], $uri_parts['query']);
			
			if ( isset($uri_parts['field']) ){
				if ( isset($filter) and method_exists($record, $filter) ){
					$val =& $record->$filter($uri_parts['field']);
					return $val;
				} else {
					$val =& $record->val($uri_parts['field']);
					return $val;
				}
			}
			else return $record;
		
		} else {
			// This is requesting a related record.
			
			$record =& df_get_record($uri_parts['table'], $uri_parts['query']);
			if ( !$record ) return PEAR::raiseError("Could not find any records matching the query");
			
			// Check to see if we are creating a new record
			if ( @$uri_parts['action'] and ( $uri_parts['action'] == 'new' ) ){
				$related_record = new Dataface_RelatedRecord($record, $uri_parts['relationship']);
				$related_record->setValues( $uri_parts['query']);
				return $related_record;
			}
			
			
			// At this point we can be sure that we are requesting an existing record.
			$related_records =& $record->getRelatedRecordObjects($uri_parts['relationship'], 0,1, $uri_parts['related_where']);
			if ( count($related_records) == 0 ){
			
				return PEAR::raiseError("Could not find any related records matching the query: ".$uri_parts['related_where']);
			}
			if ( isset($uri_parts['field']) ) {
				if ( isset($filter) and method_exists($related_records[0], $filter) ){
					$val =& $related_records[0]->$filter($uri_parts['field']);
					return $val;
				} else {
					$val =& $related_records[0]->val($uri_parts['field']);
					return $val;
				}
			}
			else return $related_records[0];
		
		}
	}
	
	
	/**
	 * Sets a value by ID.
	 */
	static function setByID($uri, $value){
		
		@list($uri, $fieldname) = explode('#', $uri);
		$record =& Dataface_IO::getByID($uri);
		
		if ( PEAR::isError($record) ) return $record;
		if ( !is_object($record) ) return PEAR::raiseError("Could not find record matching '$uri'.");
		
		if ( isset($fieldname) ){
			$res = $record->setValue($fieldname, $value);
		} else {
			$res = $record->setValues($value);
		}
		if ( PEAR::isError($res) ) return $res;
		
		$res = $record->save();
		return $res;
	}
	
	
	static function createModificationTimesTable(){
		$sql = "create table dataface__mtimes (
			`name` varchar(255) not null primary key,
			`mtime` int(11)
		)";
		$res = mysql_query($sql, df_db());
		if ( !$res ) throw new Exception(mysql_error(df_db()));
	}
	
	static function touchTable($table){
		$sql = "replace into dataface__mtimes (`name`,`mtime`) values ('".addslashes($table)."','".addslashes(time())."')";
		$res = mysql_query($sql, df_db());
		if ( !$res ){
			self::createModificationTimesTable();
			$res = mysql_query($sql, df_db());
			if ( !$res ) throw new Exception(mysql_error(df_db()));
		}
	}
	
	
				
				


}
