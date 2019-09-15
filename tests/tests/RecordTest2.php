<?php
$_SERVER['PHP_SELF'] = __FILE__;
require_once 'BaseTest.php';
require_once 'Dataface/Record.php';
require_once 'dataface-public-api.php';


class RecordTest2 extends BaseTest {

	
	
	function RecordTest2($name = 'RecordTest2'){
		$this->BaseTest($name);
		//parent::BaseTest();
	}
	
	
	
	function test_table(){
		
		$record =& df_get_record('People', array('PersonID'=>1));
		$this->assertEquals(Dataface_Table::loadTable('People'), $record->table());
	
	}
	
	
	function test_getRelationshipRange(){
		// don't remember what this does, so can't test it yet.
		
	}
	
	function test_setRelationshipRange(){
		// don't remember what this does, so can't test it yet.
	}
	
	
	function test_parseString(){
	
		$record =& df_get_record('People', array('PersonID'=>1));
		$this->assertEquals("Angelia Darla Jacobs", $record->val('Name'));
		$this->assertEquals("Angelia Darla Jacobs", $record->parseString('$Name'));
		$this->assertEquals("Her name is Angelia Darla Jacobs", $record->parseString('Her name is $Name'));
		//$this->assertEquals("Her name is \$Name", $record->parseString('Her name is \$Name'));
			// Not great support yet.. will work on above case later.
		
		// Make sure that this works with related fields. Should return first record
		// in relationship.
		$this->assertEquals("Refereed Journal", $record->parseString('$Publications.PubType'));
	}
	
	function test__relatedRecordLoaded(){
		
		$record =& df_get_record('People', array('PersonID'=>1));
		$blocksize = DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE;
		
		// Test default (no where or sort clause)
		$this->assertTrue( !$record->_relatedRecordLoaded('Publications', 1) );
		$record->getValue('Publications.BiblioString',1);
		$this->assertTrue( $record->_relatedRecordLoaded('Publications', 1) );
		
		$this->assertTrue( !$record->_relatedRecordLoaded('Publications', $blocksize+1) );
		$record->getValue('Publications.BiblioString',31) ;
		$this->assertTrue( $record->_relatedRecordLoaded('Publications',$blocksize+1) );
		
		// Test with where clause
		$this->assertTrue( !$record->_relatedRecordLoaded('Publications', 0, 'PublicationID>1'));
		$record->getValue('Publications.BiblioString',0,'PublicationID>1');
		$this->assertTrue( $record->_relatedRecordLoaded('Publications',0,'PublicationID>1'));
		
		
		$this->assertTrue( !$record->_relatedRecordLoaded('Publications',$blocksize+1,'PublicationID>1'));
		$record->getValue('Publications.BiblioString',31, 'PublicationID>1');
		$this->assertTrue( $record->_relatedRecordLoaded('Publications',$blocksize+1,'PublicationID>1'));
		
		
		// Test with where and sort clause
		$this->assertTrue( !$record->_relatedRecordLoaded('Publications',0,'PublicationID>1','BiblioString'));
		$record->getValue('Publications.BiblioString',0,'PublicationID>1','BiblioString');
		$this->assertTrue( $record->_relatedRecordLoaded('Publications', 0, 'PublicationID>1', 'BiblioString'));
		
		$this->assertTrue( !$record->_relatedRecordLoaded('Publications',$blocksize+1,'PublicationID>1', 'BiblioString'));
		$record->getValue('Publications.BiblioString',$blocksize+1, 'PublicationID>1', 'BiblioString');
		$this->assertTrue( $record->_relatedRecordLoaded('Publications',$blocksize+1,'PublicationID>1', 'BiblioString'));
	
	}
	
	function test__translateRangeToBlocks(){
		$record =& df_get_record('People', array('PersonID'=>1));
		$this->assertEquals(array(0,0), $record->_translateRangeToBlocks(1,1));
		$this->assertEquals(array(0,1), $record->_translateRangeToBlocks(1,31));
		
	}
	
	function test__relatedRecordBlockLoaded(){
		$record =& df_get_record('People', array('PersonID'=>1));
		$blocksize = DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE;
		
		// Test default (with no where or sort clauses
		$this->assertTrue( !$record->_relatedRecordBlockLoaded('Publications', 0));
		$record->getValue('Publications.BiblioString',0);
		$this->assertTrue( $record->_relatedRecordBlockLoaded('Publications', 0));
		
		$this->assertTrue( !$record->_relatedRecordBlockLoaded('Publications', 1));
		$record->getValue('Publications.BiblioString',$blocksize+1);
		$this->assertTrue( $record->_relatedRecordBlockLoaded('Publications', 1));
		
		
	}
	
	function test__loadRelatedRecordBlock(){
		$record =& df_get_record('People', array('PersonID'=>1));
		$blocksize = DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE;
		
		// test default (no where or sort clauses)
		$this->assertTrue( !$record->_relatedRecordBlockLoaded('Publications', 5));
		$record->_loadRelatedRecordBlock('Publications', 5);
		$this->assertTrue( $record->_relatedRecordBlockLoaded('Publications', 5));
		
		$this->assertTrue( !$record->_relatedRecordBlockLoaded('Publications', 6));
		$record->_loadRelatedRecordBlock('Publications',6);
		$this->assertTrue( $record->_relatedRecordBlockLoaded('Publications',6));
		
		// test with where clause
		
		$this->assertTrue( !$record->_relatedRecordBlockLoaded('Publications', 5, 'PublicationID>1') );
		$record->_loadRelatedRecordBlock('Publications',5, 'PublicationID>1');
		$this->assertTrue( $record->_relatedRecordBlockLoaded('Publications',5, 'PublicationID>1'));
		
		$this->assertTrue( !$record->_relatedRecordBlockLoaded('Publications', 6, 'PublicationID>1'));
		$record->_loadRelatedRecordBlock('Publications',6, 'PublicationID>1');
		$this->assertTrue( $record->_relatedRecordBlockLoaded('Publications',6,'PublicationID>1'));
		
		// test with where and sort clauses
		
		$this->assertTrue( !$record->_relatedRecordBlockLoaded('Publications', 5, 'PublicationID>1', 'BiblioString'));
		$record->_loadRelatedRecordBlock('Publications',5,'PublicationID>1','BiblioString');
		$this->assertTrue( $record->_relatedRecordBlockLoaded('Publications', 5, 'PublicationID>1', 'BiblioString'));
		
		
		
	}
	
	function test_numRelatedRecords(){
		$record =& df_get_record('People', array('PersonID'=>1));
		
		$this->assertEquals(191, $record->numRelatedRecords('Publications'));
		//$this->assertEquals(190, $record->numRelatedRecords('Publications', 'PublicationID>1'));
			// currently the above case does not work.  Will need to change it so that it does.
			// i am reluctant to parse the SQL however as that would add even more overhead.
	}
	
	function test_getRelatedRecords(){
		$record =& df_get_record('People', array('PersonID'=>1));
		
		// Test default.  Should only return one block of publications (the block size 
		//  is usually set around 30 or 50.
		$pubs = $record->getRelatedRecords('Publications');
		$this->assertEquals(DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE, sizeof($pubs));
		
		// Test returning all publications.
		$pubs = $record->getRelatedRecords('Publications', 'all');
		$this->assertEquals(191, sizeof($pubs));
		
		// Test returning only a single publication at a specified index.
		$pub = $record->getRelatedRecords('Publications', false, 1);
		$this->assertEquals('Book Chapter', $pub['PubType']);
		
		// Test returning publications from a range using 2nd and 3rd params for the range.
		$pubs = $record->getRelatedRecords('Publications', 5, 10);
		$this->assertEquals(10, sizeof($pubs));
		
		// Test using where clause as the 2nd parameter
		$pubs = $record->getRelatedRecords('Publications', 'PublicationID>1');
		$this->assertEquals(2, $pubs[0]['PublicationID']);
		$this->assertEquals(190, sizeof($pubs));
		
		// Test using where clause and sort clause as 2nd and 3rd params respectively
		$pubs = $record->getRelatedRecords('Publications', 'PublicationID>1','Bibliostring');
		$this->assertEquals('Abiteboul, S., Fredrick P. Brooks, J., Sasaki, Z., and Ritchie, D. Towards the analysis of the partition table. In POT MOBICOM (July 2004).', $pubs[0]['BiblioString']);
		$this->assertEquals(190, sizeof($pubs));
		
	
	}
	
	function test_getRelationshipIterator(){
		
		$record =& df_get_record('People', array('PersonID'=>1));
		
		$it = $record->getRelationshipIterator('Publications');
		$i=0;
		while ($it->hasNext()){
			$i++;
			$rec = $it->next();
			$this->assertTrue( strlen($rec->val('BiblioString') ) > 0 );
		}
		$this->assertEquals(DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE,$i);
		
		$it = $record->getRelationshipIterator('Publications','all');
		$i=0;
		while ($it->hasNext() ){
			$i++;
			$rec = $it->next();
			$this->assertTrue( strlen($rec->val('BiblioString') ) > 0 );
			unset($rec);
		}
		$this->assertEquals(191, $i);
		
		// Test with where clause
		$it = $record->getRelationshipIterator('Publications','all',null,'PublicationID>1');
		$i=0;
		while ($it->hasNext() ){
			$i++;
			$rec = $it->next();
			$this->assertTrue( strlen($rec->val('BiblioString') ) > 0 );
			unset($rec);
		}
		$this->assertEquals(190, $i);
		
		// Test with where clause and sort
		
		$it = $record->getRelationshipIterator('Publications','all',null,'PublicationID>1', 'BiblioString');
		$rec = $it->next();
		$this->assertEquals('Abiteboul, S., Fredrick P. Brooks, J., Sasaki, Z., and Ritchie, D. Towards the analysis of the partition table. In POT MOBICOM (July 2004).',$rec->val('BiblioString'));
		
		$i=1;
		while ($it->hasNext() ){
			$i++;
			$rec = $it->next();
			$this->assertTrue( strlen($rec->val('BiblioString') ) > 0 );
			unset($rec);
		}
		$this->assertEquals(190, $i);
		
	}
	
	function test_getRelatedRecordObjects(){
		
		$record =& df_get_record('People',array('PersonID'=>1));
		
		$pubs = $record->getRelatedRecordObjects('Publications');
		$this->assertEquals(DATAFACE_RECORD_RELATED_RECORD_BLOCKSIZE, sizeof($pubs));
		
		foreach ($pubs as $pub){
			$this->assertTrue(is_a($pub, 'Dataface_RelatedRecord'));
		}
		
		$pubs = $record->getRelatedRecordObjects('Publications', 'all');
		$this->assertEquals(191, sizeof($pubs));
		
		foreach ($pubs as $pub){
			$this->assertTrue(is_a($pub, 'Dataface_RelatedRecord'));
		}
		
		$pubs = $record->getRelatedRecordObjects('Publications', 'all', null, 'PublicationID>1');
		$this->assertEquals(190, sizeof($pubs));
		
		foreach ($pubs as $pub){
			$this->assertTrue(is_a($pub, 'Dataface_RelatedRecord'));
		}
		
		$pubs = $record->getRelatedRecordObjects('Publications','all',null,0,'PubType,BiblioString');
		$this->assertEquals(191, sizeof($pubs));
		
		
	}
	
	function test_setValue(){
		
		$record =& df_get_record('People', array('PersonID'=>1));
		
		$this->assertEquals('Angelia Darla Jacobs', $record->val('Name'));
		
		$record->setValue('Name','John Doe');
		$this->assertEquals('John Doe', $record->val('Name'));
		
	
	}
	
	function test_getContainerSource(){
		
		$record =& df_get_record('People', array('PersonID'=>1));
		
		$this->assertEquals(realpath('.').'/photos/Angelia_Darla_Jacobs.jpg', $record->getContainerSource('Photo'));
	
	}
	
	
	function test_setMetaDataValue(){
		
		$this->assertTrue(false, "Test Not written yet");
	}
	
	function test_getLength(){
		$record =& df_get_record('People',array('PersonID'=>1));
		$this->assertEquals(20, $record->getLength('Name'));
		
		$this->assertEquals(107, $record->getLength('Publications.BiblioString', 10));
		
		$this->assertEquals(107, $record->getLength('Publications.BiblioString', 10, 'PublicationID>1'));
		
		$this->assertEquals(107, $record->getLength('Publications.BiblioString', 10, 'PublicationID>1','BiblioString'));
	}
		
	
}

?>
