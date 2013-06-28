<?php
require_once 'BaseTest.php';
require_once 'dataface-public-api.php';
require_once 'Dataface/TreeTable.php';

class TreeTableTest extends BaseTest {

	var $t;
	var $indexpage;

	function TreeTableTest($name = "TreeTableTest"){
		$this->BaseTest($name);
	}
	
	function setUp(){
		parent::setUp();
		mysql_query("CREATE TABLE `Pages` (
			`PageID` INT(11) auto_increment NOT NULL,
			`ParentID` INT(11),
			`ShortName` VARCHAR(32) NOT NULL,
			`Description` TEXT,
			PRIMARY KEY (`PageID`),
			UNIQUE (`ParentID`,`ShortName`))") or trigger_error(mysql_error().__LINE__);
		mysql_query(
			"INSERT INTO `Pages` (`PageID`,`ShortName`,`Description`)
			VALUES (1,'index_page','Main page')") or trigger_error(mysql_error().__LINE__);
		mysql_query(
			"INSERT INTO `Pages` (`ParentID`,`ShortName`,`Description`)
			VALUES 
			(1,'about','About us'),
			(1,'jobs','Now hiring'),
			(1,'products','About our products'),
			(1,'services','About our services'),
			(1,'contact','Contact us')") or trigger_error(mysql_error().__LINE__);
		mysql_query(
			"INSERT INTO `Pages` (`ParentID`,`ShortName`,`Description`)
			VALUES
			(2,'history','Our history'),
			(2,'future', 'The direction of the company'),
			(3,'application', 'Job application'),
			(3,'current_listing', 'Current job listings'),
			(4,'awards','Product awards'),
			(4,'downloads','Product downlaods'),
			(5,'consultation','Free consultation')") or trigger_error(mysql_error().__LINE__);
			
		$table =& Dataface_Table::loadTable('Pages');
		$r =& $table->relationships();
		if ( !isset($r['children']) ){
			$table->addRelationship('children', array(
				'__sql__' => 'select * from Pages where ParentID=\'$PageID\'',
				'meta:class'=>'children'
				)
			);
		}
		$this->indexpage =& df_get_record('Pages',array('PageID'=>1));
		$this->t = new Dataface_TreeTable($this->indexpage);
		
	}
	
	function test_getChildren(){
		$children =& $this->indexpage->getChildren();
		$childnames = array();
		foreach ($children as $child){
			$childnames[] = $child->strval('ShortName');
			
		}
		$this->assertEquals(
			array('about','contact','jobs','products','services'),
			$childnames
		);
	}
	
	function test_getRecordByRowId(){
		$row = $this->t->getRecordByRowId('1');
		$this->assertEquals('about', $row->strval('ShortName'));
		$row2 = $this->t->getRecordByRowId('3-1');
		$this->assertEquals('application', $row2->strval('ShortName'));
		
	}
	
	function test_getSubrows(){
		$rows = array();
		$this->t->getSubrows($rows,'', $this->indexpage);
		$this->assertEquals(
			array( 1,'1-1','1-2',2,3,'3-1','3-2',4,'4-1','4-2',5,'5-1' ),
			array_keys($rows)
		);
		$null = null;
		$rows2=array();
		$this->t->getSubrows($rows2,'1',$null);
		$this->assertEquals(
			array('1-1','1-2'),
			array_keys($rows2)
		);
		
		$row1 =& $rows[1];
		$this->assertTrue(is_a($row1, 'Dataface_Record'));
		$this->assertEquals(
			'about',
			$row1->strval('ShortName')
		);
	}
	
	function test_getSubrowsAsHtml(){
		echo $this->t->getSubrowsAsHtml('');
	}
	
	
	
	
	


}


?>
