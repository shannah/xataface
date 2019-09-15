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

require_once 'BaseTest.php';
require_once 'Dataface/QueryTool.php';

class QueryToolTest extends BaseTest {

	function QueryToolTest($name="QueryToolTest"){
		$this->BaseTest($name);
	}
	
	function test_query_tool_load_current(){

		$rd = new Dataface_QueryTool('Profiles', $this->table1->db, array());
		$s = $rd->loadCurrent();
		$this->assertTrue(is_a($s, 'Dataface_Record') ); // should load all columns except the blobs
		$this->assertEquals($s->getValue('id'),10);
		$this->assertEquals($s->getValue('fname'),'John');
		$this->assertEquals($s->getValueAsString('lastlogin'),'2005-06-30 00:00:00');
//		$this->assertTrue(!$s->hasValue('photo'), 'Photo should not have an associated value. Blobs should not be loaded by the result descriptor');
		$this->assertTrue($s->hasValue('photo_mimetype'), 'Photo mimetype should have a value.');
		$this->assertEquals($rd->found(), 3);
		$this->assertEquals($rd->cardinality(), 3);
		$this->assertEquals($rd->cursor(), 0);
		$this->assertEquals($rd->start(), 0);
		$this->assertEquals($rd->end(), 2);
		
		// now try to specify a cursor
		$rd2 = new Dataface_QueryTool('Profiles',$this->table1->db, array('-cursor'=>'1'));
		$s = $rd2->loadCurrent();
		$this->assertTrue(is_a($s, 'Dataface_Record'));
		$this->assertEquals($s->getValue('id'),11);
		$this->assertEquals($s->getValue('fname'),'Johnson');
		$this->assertEquals($s->getValueAsString('lastlogin'),'2005-06-30 00:00:00');
		$this->assertEquals($rd2->found(),3);
		$this->assertEquals($rd2->cardinality(),3);
		$this->assertEquals($rd2->cursor(),1);
		$this->assertEquals($rd2->start(),0);
		$this->assertEquals($rd2->end(),2);
		
		$rd2 = new Dataface_QueryTool('Profiles',$this->table1->db, array('-cursor'=>1, '-limit'=>2));
		$s = $rd2->loadCurrent();
		$this->assertTrue(is_a($s, 'Dataface_Record'));
		$this->assertEquals($s->getValue('id'),11);
		$this->assertEquals($s->getValue('fname'),'Johnson');
		$this->assertEquals($s->getValueAsString('lastlogin'),'2005-06-30 00:00:00');
		$this->assertEquals($rd2->found(),3);
		$this->assertEquals($rd2->cardinality(),3);
		$this->assertEquals($rd2->cursor(),1);
		$this->assertEquals($rd2->start(),0);
		$this->assertEquals($rd2->end(),1);
		
		
		$rd2 = new Dataface_QueryTool('Profiles',$this->table1->db, array('-cursor'=>1, '-limit'=>2, 'fname'=>'John'));
		$s = $rd2->loadCurrent();
		$this->assertTrue(is_a($s, 'Dataface_Record'));
		$this->assertEquals($s->getValue('id'),11);
		$this->assertEquals($s->getValue('fname'),'Johnson');
		$this->assertEquals($s->getValueAsString('lastlogin'),'2005-06-30 00:00:00');
		$this->assertEquals($rd2->found(),2);
		$this->assertEquals($rd2->cardinality(),3);
		$this->assertEquals($rd2->cursor(),1);
		$this->assertEquals($rd2->start(),0);
		$this->assertEquals($rd2->end(),1);
		
		$rd2 = new Dataface_QueryTool('Profiles',$this->table1->db, array('-cursor'=>0, '-limit'=>2, 'fname'=>'=John'));
		$s = $rd2->loadCurrent();
		$this->assertTrue(is_a($s, 'Dataface_Record'));
		$this->assertEquals($s->getValue('id'),10);
		$this->assertEquals($s->getValue('fname'),'John');
		$this->assertEquals($s->getValueAsString('lastlogin'),'2005-06-30 00:00:00');
		$this->assertEquals($rd2->found(),1);
		$this->assertEquals($rd2->cardinality(),3);
		$this->assertEquals($rd2->cursor(),0);
		$this->assertEquals($rd2->start(),0);
		$this->assertEquals($rd2->end(),0);
		
		
	
	}
	
	
	function test_query_tool_load_set(){
	
		$rd = new Dataface_QueryTool('Profiles', $this->table1->db, array());
		$res = $rd->loadSet();
		$this->assertTrue($res);
		$data =& $rd->data();
		$this->assertEquals(count($data), 3);
		
		$this->assertEquals($data[10]['fname'], 'John');
		$this->assertTrue( !isset( $data[10]['photo']) );
		$this->assertTrue( isset( $data[10]['__photo_length']));
		
	
	}
	
	
	function test_get_titles(){
		$qt = new Dataface_QueryTool('Profiles', $this->table1->db);
		$titles = $qt->getTitles();
		$this->assertEquals(
			array(
				'id=10'=>'John',
				'id=11'=>'Johnson',
				'id=12'=>'William'),
			$titles
		);
	
	
	}
}
?>
	
