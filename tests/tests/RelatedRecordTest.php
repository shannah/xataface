<?php
/********************************************************************************
 *
 *  Xataface Web Application Framework for PHP and MySQL
 *  Copyright (C) 2005  Steve Hannah <shannah@sfu.ca>
 *  
 *  This library is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU Lesser General Public
 *  License as published by the Free Software Foundation; either
 *  version 2.1 of the License, or (at your option) any later version.
 *  
 *  This library is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 *  Lesser General Public License for more details.
 *  
 *  You should have received a copy of the GNU Lesser General Public
 *  License along with this library; if not, write to the Free Software
 *  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 *===============================================================================
 */

$_SERVER['PHP_SELF'] = __FILE__;
require_once 'BaseTest.php';
require_once 'Dataface/Relationship.php';
require_once 'Dataface/Record.php';
require_once 'Dataface/RelatedRecord.php';
require_once 'Dataface/IO.php';


class RelatedRecordTest extends BaseTest {

	
	
	function RelatedRecordTest($name = 'RelatedRecordTest'){
		$this->BaseTest($name);
		//parent::BaseTest();
	}
	
	function test_values(){
		$record = new Dataface_Record('Profiles', array());
		$io = new Dataface_IO('Profiles');
		$io->read(array('id'=>10), $record);
		$this->assertEquals('10', $record->val('id'));
		$rr = new Dataface_RelatedRecord($record, 'appointments');
		$this->assertEquals(10, $rr->val('profileid'));
		$this->assertEquals(10, $rr->getValue('profileid'));
		
	
	}
	
	function test_toRecord(){
		require_once 'dataface-public-api.php';
		$record =& df_get_record('People', array('PersonID'=>1));
		$it =& $record->getRelationshipIterator('Publications');
		$pub =& $it->next();
		$this->assertTrue(is_a($pub, 'Dataface_RelatedRecord'));
		
		$pubRecord =& $pub->toRecord('Publications');
		$this->assertTrue(is_a($pubRecord, 'Dataface_Record'));
		$this->assertEquals(1, $pubRecord->val('PublicationID'));
		$this->assertEquals('Refereed Journal', $pubRecord->val('PubType'));
		$this->assertEquals('Amit, H. Autonomous, metamorphic technology for B-Trees. In POT NOSSDAV (Dec. 1991).',
							$pubRecord->val('BiblioString'));
							
		
		$pubRecord2 =& $pub->toRecord();	//should default to a publications record
		$this->assertTrue(is_a($pubRecord2, 'Dataface_Record'));
		$this->assertEquals(1, $pubRecord2->val('PublicationID'));
	
	}
	
	function test_toRecords(){
		require_once 'dataface-public-api.php';
		$record =& df_get_record('People', array('PersonID'=>1));
		$it =& $record->getRelationshipIterator('Publications');
		$pub =& $it->next();
		
		$this->assertTrue(is_a($pub, 'Dataface_RelatedRecord'));
		$pubRecords =& $pub->toRecords();
		$this->assertEquals(1, count($pubRecords));
		$this->assertTrue(is_array($pubRecords));
		$this->assertTrue(is_a($pubRecords[0], 'Dataface_Record'));
		$this->assertEquals(1, $pubRecords[0]->val('PublicationID'));
		$this->assertEquals('Refereed Journal', $pubRecords[0]->val('PubType'));
		$this->assertEquals('Amit, H. Autonomous, metamorphic technology for B-Trees. In POT NOSSDAV (Dec. 1991).',
							$pubRecords[0]->val('BiblioString'));
		
	
	
	}
	
	function test_testCondition(){
		require_once 'dataface-public-api.php';
		$record =& df_get_record('People', array('PersonID'=>1));
		$it =& $record->getRelationshipIterator('Publications');
		$pub =& $it->next();
		$this->assertTrue($pub->testCondition('$PublicationID == 1'));
		$this->assertTrue($pub->testCondition('$PubType == "Refereed Journal"'));
		$this->assertTrue(!$pub->testCondition('$PubType == "Journal"'));
	
	}
	
	
}
	
?>
