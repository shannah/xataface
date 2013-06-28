<?php
require_once 'BaseTest.php';
require_once 'Dataface/QueryTranslator.php';


class QueryTranslator_Test extends BaseTest {

	
	
	function DB_Test($name = 'QueryTranslator_Test'){
		$this->BaseTest($name);
		Dataface_Application::getInstance();
		//$this->DB =& Dataface_DB::getInstance();
		//parent::BaseTest();
	}
	

	function testTranslateSelect(){
		$app =& Dataface_Application::getInstance();
		$translator = new Dataface_QueryTranslator('fr');
		$sql = 'select PersonID, Name, Position, Blurb from PeopleIntl limit 1';
		$tsql = $translator->translateQuery($sql);

	
		$this->assertEquals(
			array("select `PeopleIntl`.`PersonID`, `PeopleIntl`.`Name`, ifnull(`PeopleIntl__fr`.`Position`, `PeopleIntl`.`Position`) as `Position`, ifnull(`PeopleIntl__fr`.`Blurb`, `PeopleIntl`.`Blurb`) as `Blurb` from `PeopleIntl` left join `PeopleIntl_fr` as `PeopleIntl__fr` on `PeopleIntl`.`PersonID` = `PeopleIntl__fr`.`PersonID` limit 1"),
			$tsql
			);
			
		$res = mysql_query($tsql[0], $app->db());
		if ( !$res ) die(mysql_error($app->db()));
		$row = mysql_fetch_assoc($res);
		$this->assertEquals(
			array(
				'PersonID'=>'1',
				'Name'=> 'Angelia Darla Jacobs',
				'Position'=>'Default Position',
				'Blurb'=>'My French Blurb'
				),
			$row
			);
			
		// Now try translating the same query in english
		
		$translator = new Dataface_QueryTranslator('en');
		$tsql = $translator->translateQuery($sql);
		$this->assertEquals(
			array("select `PeopleIntl`.`PersonID`, `PeopleIntl`.`Name`, ifnull(`PeopleIntl__en`.`Position`, `PeopleIntl`.`Position`) as `Position`, ifnull(`PeopleIntl__en`.`Blurb`, `PeopleIntl`.`Blurb`) as `Blurb` from `PeopleIntl` left join `PeopleIntl_en` as `PeopleIntl__en` on `PeopleIntl`.`PersonID` = `PeopleIntl__en`.`PersonID` limit 1"),
			$tsql
			);
		$res = mysql_query($tsql[0], $app->db());
		if ( !$res ) die(mysql_error($app->db()));
		$row = mysql_fetch_assoc($res);
		$this->assertEquals(
			array(
				'PersonID'=>'1',
				'Name'=>'Angelia Darla Jacobs',
				'Position'=>'My English Position',
				'Blurb'=>'Default Blurb'
				),
			$row
			);
			
		
		// try a glob query
		$sql = 'select * from PeopleIntl limit 1';
		$translator = new Dataface_QueryTranslator('en');
		
		$tsql = $translator->translateQuery($sql);
		
		$res = mysql_query($tsql[0], $app->db());
		if ( !$res ) die(mysql_error($app->db()));
		$row = mysql_fetch_assoc($res);
		$this->assertEquals(
			array("select `PeopleIntl`.`PersonID`, `PeopleIntl`.`Name`, ifnull(`PeopleIntl__en`.`Position`, `PeopleIntl`.`Position`) as `Position`, ifnull(`PeopleIntl__en`.`Blurb`, `PeopleIntl`.`Blurb`) as `Blurb`, `PeopleIntl`.`Photo`, `PeopleIntl`.`Photo_mimetype` from `PeopleIntl` left join `PeopleIntl_en` as `PeopleIntl__en` on `PeopleIntl`.`PersonID` = `PeopleIntl__en`.`PersonID` limit 1"),
			$tsql
			);
		$this->assertEquals(
			array(
				'PersonID'=>'1',
				'Name'=>'Angelia Darla Jacobs',
				'Photo'=>'Angelia_Darla_Jacobs.jpg',
				'Photo_mimetype'=>null,
				'Position'=>'My English Position',
				'Blurb'=>'Default Blurb'
				),
			$row
			);
			
		
		// Now the same query with french translation
		$translator = new Dataface_QueryTranslator('fr');
		$tsql = $translator->translateQuery($sql);
		$res = mysql_query($tsql[0], $app->db());
		if ( !$res ) die(mysql_error($app->db()));
		$row = mysql_fetch_assoc($res);
		$this->assertEquals(
			array("select `PeopleIntl`.`PersonID`, `PeopleIntl`.`Name`, ifnull(`PeopleIntl__fr`.`Position`, `PeopleIntl`.`Position`) as `Position`, ifnull(`PeopleIntl__fr`.`Blurb`, `PeopleIntl`.`Blurb`) as `Blurb`, `PeopleIntl`.`Photo`, `PeopleIntl`.`Photo_mimetype` from `PeopleIntl` left join `PeopleIntl_fr` as `PeopleIntl__fr` on `PeopleIntl`.`PersonID` = `PeopleIntl__fr`.`PersonID` limit 1"),
			$tsql
			);
		$this->assertEquals(
			array(
				'PersonID'=>'1',
				'Name'=>'Angelia Darla Jacobs',
				'Photo'=>'Angelia_Darla_Jacobs.jpg',
				'Photo_mimetype'=>null,
				'Position'=>'Default Position',
				'Blurb'=>'My French Blurb'
				),
			$row
			);
		
		
		// try a simple query with a where clause
		$sql = 'select * from PeopleIntl where PersonID=\'1\' limit 1';
		$translator = new Dataface_QueryTranslator('en');
		$tsql = $translator->translateQuery($sql);
		$res = mysql_query($tsql[0], $app->db());
		if ( !$res ) die(mysql_error($app->db()));
		$row = mysql_fetch_assoc($res);
	
		$this->assertEquals(
			array("select `PeopleIntl`.`PersonID`, `PeopleIntl`.`Name`, ifnull(`PeopleIntl__en`.`Position`, `PeopleIntl`.`Position`) as `Position`, ifnull(`PeopleIntl__en`.`Blurb`, `PeopleIntl`.`Blurb`) as `Blurb`, `PeopleIntl`.`Photo`, `PeopleIntl`.`Photo_mimetype` from `PeopleIntl` left join `PeopleIntl_en` as `PeopleIntl__en` on `PeopleIntl`.`PersonID` = `PeopleIntl__en`.`PersonID` where `PeopleIntl`.`PersonID` = '1' limit 1"),
			$tsql
			);
		$this->assertEquals(
			array(
				'PersonID'=>'1',
				'Name'=>'Angelia Darla Jacobs',
				'Photo'=>'Angelia_Darla_Jacobs.jpg',
				'Photo_mimetype'=>null,
				'Position'=>'My English Position',
				'Blurb'=>'Default Blurb'
				),
			$row
			);
			
		
		// Try the same query in french
		$translator = new Dataface_QueryTranslator('fr');
		$tsql = $translator->translateQuery($sql);
		$res = mysql_query($tsql[0], $app->db());
		if ( !$res ) die(mysql_error($app->db()));
		$row = mysql_fetch_assoc($res);
	
		$this->assertEquals(
			array("select `PeopleIntl`.`PersonID`, `PeopleIntl`.`Name`, ifnull(`PeopleIntl__fr`.`Position`, `PeopleIntl`.`Position`) as `Position`, ifnull(`PeopleIntl__fr`.`Blurb`, `PeopleIntl`.`Blurb`) as `Blurb`, `PeopleIntl`.`Photo`, `PeopleIntl`.`Photo_mimetype` from `PeopleIntl` left join `PeopleIntl_fr` as `PeopleIntl__fr` on `PeopleIntl`.`PersonID` = `PeopleIntl__fr`.`PersonID` where `PeopleIntl`.`PersonID` = '1' limit 1"),
			$tsql
			);
		$this->assertEquals(
			array(
				'PersonID'=>'1',
				'Name'=>'Angelia Darla Jacobs',
				'Photo'=>'Angelia_Darla_Jacobs.jpg',
				'Photo_mimetype'=>null,
				'Position'=>'Default Position',
				'Blurb'=>'My French Blurb'
				),
			$row
			);
		
		// Now try a where clause on a translated field
		
		$sql = 'select PersonID, Name, Position from PeopleIntl where Position = \'My English Position\' limit 1';
		$translator = new Dataface_QueryTranslator('en');
		$tsql = $translator->translateQuery($sql);
		
		$res = mysql_query($tsql[0], $app->db());
		if ( !$res ) die(mysql_error($app->db()));
		$row = mysql_fetch_assoc($res);

		$this->assertEquals(
			array("select `PeopleIntl`.`PersonID`, `PeopleIntl`.`Name`, ifnull(`PeopleIntl__en`.`Position`, `PeopleIntl`.`Position`) as `Position` from `PeopleIntl` left join `PeopleIntl_en` as `PeopleIntl__en` on `PeopleIntl`.`PersonID` = `PeopleIntl__en`.`PersonID` where ifnull(`PeopleIntl__en`.`Position`, `PeopleIntl`.`Position`) = 'My English Position' limit 1"),
			$tsql
			);
		$this->assertEquals(
			array(
				'PersonID'=>'1',
				'Name'=>'Angelia Darla Jacobs',
				'Position'=>'My English Position'
				),
			$row
			);
			
		
		// See if our parser can handle backticks:
		$sql = 'select `PeopleIntl`.`PersonID`, Name, Position from PeopleIntl where Position = \'My English Position\' limit 1';
		$translator = new Dataface_QueryTranslator('en');
		$tsql = $translator->translateQuery($sql);
		
		$res = mysql_query($tsql[0], $app->db());
		if ( !$res ) die(mysql_error($app->db()));
		$row = mysql_fetch_assoc($res);

		$this->assertEquals(
			array("select `PeopleIntl`.`PersonID`, `PeopleIntl`.`Name`, ifnull(`PeopleIntl__en`.`Position`, `PeopleIntl`.`Position`) as `Position` from `PeopleIntl` left join `PeopleIntl_en` as `PeopleIntl__en` on `PeopleIntl`.`PersonID` = `PeopleIntl__en`.`PersonID` where ifnull(`PeopleIntl__en`.`Position`, `PeopleIntl`.`Position`) = 'My English Position' limit 1"),
			$tsql
			);
		$this->assertEquals(
			array(
				'PersonID'=>'1',
				'Name'=>'Angelia Darla Jacobs',
				'Position'=>'My English Position'
				),
			$row
			);
			
		
		// Now for some set functions
		$sql = 'select count(*) from PeopleIntl';
		$translator = new Dataface_QueryTranslator('en');
		$tsql = $translator->translateQuery($sql);
		$res = mysql_query($tsql[0], $app->db());
		if ( !$res ) die(mysql_error($app->db()));
		$row = mysql_fetch_assoc($res);

		$this->assertEquals(
			array("select count(*) from `PeopleIntl`"),
			$tsql
			);
		$this->assertEquals(
			array(
				'count(*)'=>'250'
				),
			$row
			);
		
		// Try subselects
		$sql = 'select `PeopleIntl`.`PersonID`, Name, Position from PeopleIntl where Position = \'My English Position\' and Position in (select Position from PeopleIntl) limit 1';
		$translator = new Dataface_QueryTranslator('en');
		$tsql = $translator->translateQuery($sql);
				//print_r($translator->_data);
		//print_r($translator->_data_translated);
		$res = mysql_query($tsql[0], $app->db());
		if ( !$res ) die(mysql_error($app->db()));
		$row = mysql_fetch_assoc($res);

		$this->assertEquals(
			array("select `PeopleIntl`.`PersonID`, `PeopleIntl`.`Name`, ifnull(`PeopleIntl__en`.`Position`, `PeopleIntl`.`Position`) as `Position` from `PeopleIntl` left join `PeopleIntl_en` as `PeopleIntl__en` on `PeopleIntl`.`PersonID` = `PeopleIntl__en`.`PersonID` where ifnull(`PeopleIntl__en`.`Position`, `PeopleIntl`.`Position`) = 'My English Position' and ifnull(`PeopleIntl__en`.`Position`, `PeopleIntl`.`Position`) in (select ifnull(`PeopleIntl__en`.`Position`, `PeopleIntl`.`Position`) as `Position` from `PeopleIntl` left join `PeopleIntl_en` as `PeopleIntl__en` on `PeopleIntl`.`PersonID` = `PeopleIntl__en`.`PersonID`) limit 1"),
			$tsql
			);
		$this->assertEquals(
			array(
				'PersonID'=>'1',
				'Name'=>'Angelia Darla Jacobs',
				'Position'=>'My English Position'
				),
			$row
			);
			
	
		// Try order by clause
		
		$sql = 'select PersonID, Name, Position, Blurb from PeopleIntl limit 1 order by Position desc';
		$translator = new Dataface_QueryTranslator('fr');
		$tsql = $translator->translateQuery($sql);
		//print_r($tsql);exit;
		$this->assertEquals(
			array("select `PeopleIntl`.`PersonID`, `PeopleIntl`.`Name`, ifnull(`PeopleIntl__fr`.`Position`, `PeopleIntl`.`Position`) as `Position`, ifnull(`PeopleIntl__fr`.`Blurb`, `PeopleIntl`.`Blurb`) as `Blurb` from `PeopleIntl` left join `PeopleIntl_fr` as `PeopleIntl__fr` on `PeopleIntl`.`PersonID` = `PeopleIntl__fr`.`PersonID` order by ifnull(`PeopleIntl__fr`.`Position`, `PeopleIntl`.`Position`) desc limit 1"),
			$tsql
			);
		
			
		$res = mysql_query($tsql[0], $app->db());
		if ( !$res ) die(mysql_error($app->db()));
		$row = mysql_fetch_assoc($res);
		$this->assertEquals(
			array(
				'PersonID'=>'2',
				'Name'=> 'Antoinette Terri Mccoy',
				'Position'=>'My French Position',
				'Blurb'=>''
				),
			$row
			);
			
		/*
		// Try Match clause
		
		$sql = 'select PersonID, Name, Position, Blurb from PeopleIntl where match (Position) against (\'French\' in boolean mode) limit 1 order by Position desc';
		$translator = new Dataface_QueryTranslator('fr');
		$tsql = $translator->translateQuery($sql);
		//print_r($translator->_data_translated);exit;
		print_r($tsql);exit;
		$this->assertEquals(
			array("select `PeopleIntl`.`PersonID`, `PeopleIntl`.`Name`, ifnull(`PeopleIntl__fr`.`Position`, `PeopleIntl`.`Position`) as `Position`, ifnull(`PeopleIntl__fr`.`Blurb`, `PeopleIntl`.`Blurb`) as `Blurb` from `PeopleIntl` left join `PeopleIntl_fr` as `PeopleIntl__fr` on `PeopleIntl`.`PersonID` = `PeopleIntl__fr`.`PersonID` order by ifnull(`PeopleIntl__fr`.`Position`, `PeopleIntl`.`Position`) desc limit 1"),
			$tsql
			);
		
			
		$res = mysql_query($tsql[0], $app->db());
		if ( !$res ) die(mysql_error($app->db()));
		$row = mysql_fetch_assoc($res);
		$this->assertEquals(
			array(
				'PersonID'=>'2',
				'Name'=> 'Antoinette Terri Mccoy',
				'Position'=>'My French Position',
				'Blurb'=>''
				),
			$row
			);
			
		$sql = "SELECT COUNT(*) FROM `quiz` WHERE MATCH (`question`,`answer_a`,`answer_b`,`answer_c`,`answer_d`,`answer`) AGAINST ('Quelle' IN BOOLEAN MODE) AND MATCH (`question`,`answer_a`,`answer_b`,`answer_c`,`answer_d`,`answer`) AGAINST ('Quelle' IN BOOLEAN MODE)";
		$translator = new Dataface_QueryTranslator('fr');
		$tsql = $translator->translateQuery($sql);
		print_r($translator->_data_translated);
		//print_r($tsql);exit;
		*/
		
		// Try subselect in Tables list
		$sql = 'select 
					p1.PersonID, 
					p1.Name, 
					p1.Position, 
					p1.Blurb, 
					f.PersonID as FriendID, 
					f.Name as FriendName, 
					f.Position as FriendPosition 
				from 
					PeopleIntl p1
					left join 
						( select PersonID, Name, Position from PeopleIntl where PersonID=\'2\') as f
					on
						p1.PersonID = f.PersonID
				limit 1';
		$tsql = $translator->translateQuery($sql);
		print_r($translator->_data_translated);
		print_r($tsql);exit;
	
		$this->assertEquals(
			array("select `PeopleIntl`.`PersonID`, `PeopleIntl`.`Name`, ifnull(`PeopleIntl__fr`.`Position`, `PeopleIntl`.`Position`) as `Position`, ifnull(`PeopleIntl__fr`.`Blurb`, `PeopleIntl`.`Blurb`) as `Blurb` from `PeopleIntl` left join `PeopleIntl_fr` as `PeopleIntl__fr` on `PeopleIntl`.`PersonID` = `PeopleIntl__fr`.`PersonID` limit 1"),
			$tsql
			);
			
		$res = mysql_query($tsql[0], $app->db());
		if ( !$res ) die(mysql_error($app->db()));
		$row = mysql_fetch_assoc($res);
		$this->assertEquals(
			array(
				'PersonID'=>'1',
				'Name'=> 'Angelia Darla Jacobs',
				'Position'=>'Default Position',
				'Blurb'=>'My French Blurb'
				),
			$row
			);
		

		
	}
	
	function test_translateUpdateQuery(){
		$app =& Dataface_Application::getInstance();
		// Try to perform an update on a field that has a translation - and the translated
		// record already exists.
		$sql = 'update PeopleIntl set Position=\'New English Position\' WHERE PersonID=1 limit 1';
		$translator = new Dataface_QueryTranslator('en');
		$tsql = $translator->translateQuery($sql);
		//print_r($tsql);exit;
		$affected_rows = array();
		foreach ( $tsql as $q ){
			$res = mysql_query($q, $app->db());
			if ( !$res ){
				die( mysql_error($app->db()) );
			}
			$affected_rows[$q] = mysql_affected_rows($app->db());
		}


		$this->assertEquals(
			array("insert ignore into `PeopleIntl_en` (`PersonID`) values ('1')",
				"update `PeopleIntl_en` set `Position` = 'New English Position' where `PersonID` = 1 limit 1"),
			$tsql
			);
		$this->assertEquals(
			array(
				"insert ignore into `PeopleIntl_en` (`PersonID`) values ('1')" => 0,
				"update `PeopleIntl_en` set `Position` = 'New English Position' where `PersonID` = 1 limit 1" => 1
				),
			$affected_rows
			);
	
	
		// Try to perform an update on a field that has a translation - but the 
		// translated record does not yet exist
		$sql = 'update PeopleIntl set Position=\'New English Position\' WHERE PersonID=3 limit 1';
		$translator = new Dataface_QueryTranslator('en');
		$tsql = $translator->translateQuery($sql);
		//print_r($tsql);exit;
		$affected_rows = array();
		foreach ( $tsql as $q ){
			$res = mysql_query($q, $app->db());
			if ( !$res ){
				die( mysql_error($app->db()) );
			}
			$affected_rows[$q] = mysql_affected_rows($app->db());
		}


		$this->assertEquals(
			array("insert ignore into `PeopleIntl_en` (`PersonID`) values ('3')",
				"update `PeopleIntl_en` set `Position` = 'New English Position' where `PersonID` = 3 limit 1"),
			$tsql
			);
		$this->assertEquals(
			array(
				"insert ignore into `PeopleIntl_en` (`PersonID`) values ('3')" => 1,
				"update `PeopleIntl_en` set `Position` = 'New English Position' where `PersonID` = 3 limit 1" => 1
				),
			$affected_rows
			);
			
		// Try to update 2 fields - both with translation.
		$sql = 'update PeopleIntl set Position=\'New new English Position\', Blurb=\'A new improved english blurb\' WHERE PersonID=1 limit 1';
		$translator = new Dataface_QueryTranslator('en');
		$tsql = $translator->translateQuery($sql);
		//print_r($tsql);exit;
		$affected_rows = array();
		foreach ( $tsql as $q ){
			$res = mysql_query($q, $app->db());
			if ( !$res ){
				die( mysql_error($app->db()) );
			}
			$affected_rows[$q] = mysql_affected_rows($app->db());
		}


		$this->assertEquals(
			array("insert ignore into `PeopleIntl_en` (`PersonID`) values ('1')",
				"update `PeopleIntl_en` set `Position` = 'New new English Position', `Blurb` = 'A new improved english blurb' where `PersonID` = 1 limit 1"),
			$tsql
			);
		$this->assertEquals(
			array(
				"insert ignore into `PeopleIntl_en` (`PersonID`) values ('1')" => 0,
				"update `PeopleIntl_en` set `Position` = 'New new English Position', `Blurb` = 'A new improved english blurb' where `PersonID` = 1 limit 1" => 1
				),
			$affected_rows
			);
			
		
		// Try to update one field in the default table and one field in the 
		// translation table.
		$sql = 'update PeopleIntl set Position=\'Bettys English Position\', Name=\'Betty White\' WHERE PersonID=1 limit 1';
		$translator = new Dataface_QueryTranslator('en');
		$tsql = $translator->translateQuery($sql);
		//print_r($tsql);exit;
		$affected_rows = array();
		foreach ( $tsql as $q ){
			$res = mysql_query($q, $app->db());
			if ( !$res ){
				die( mysql_error($app->db()) );
			}
			$affected_rows[$q] = mysql_affected_rows($app->db());
		}


		$this->assertEquals(
			array("insert ignore into `PeopleIntl_en` (`PersonID`) values ('1')",
				"update `PeopleIntl` set `Name` = 'Betty White' where `PersonID` = 1 limit 1",
				"update `PeopleIntl_en` set `Position` = 'Bettys English Position' where `PersonID` = 1 limit 1"
				),
			$tsql
			);
		$this->assertEquals(
			array(
				"insert ignore into `PeopleIntl_en` (`PersonID`) values ('1')" => 0,
				"update `PeopleIntl` set `Name` = 'Betty White' where `PersonID` = 1 limit 1" => 1,
				"update `PeopleIntl_en` set `Position` = 'Bettys English Position' where `PersonID` = 1 limit 1" => 1
				
				),
			$affected_rows
			);
			
		// Try to update a key column
		// Try to update one field in the default table and one field in the 
		// translation table.
		$sql = 'update PeopleIntl set PersonID=5000 WHERE PersonID=1 limit 1';
		$translator = new Dataface_QueryTranslator('en');
		$tsql = $translator->translateQuery($sql);
		//print_r($tsql);exit;
		$affected_rows = array();
		foreach ( $tsql as $q ){
			$res = mysql_query($q, $app->db());
			if ( !$res ){
				die( mysql_error($app->db()) );
			}
			$affected_rows[$q] = mysql_affected_rows($app->db());
		}


		$this->assertEquals(
			array(
				"update `PeopleIntl` set `PersonID` = 5000 where `PersonID` = 1 limit 1",
				"update `PeopleIntl_en` set `PersonID` = 5000 where `PersonID` = 1 limit 1",
				"update `PeopleIntl_fr` set `PersonID` = 5000 where `PersonID` = 1 limit 1"
				),
			$tsql
			);
		$this->assertEquals(
			array(
				"update `PeopleIntl` set `PersonID` = 5000 where `PersonID` = 1 limit 1" => 1,
				"update `PeopleIntl_en` set `PersonID` = 5000 where `PersonID` = 1 limit 1" => 1,
				"update `PeopleIntl_fr` set `PersonID` = 5000 where `PersonID` = 1 limit 1" => 1
				),
			$affected_rows
			);
			
		
		
			
		
	}
	
	
	function test_translateInsertQuery(){
		$app =& Dataface_Application::getInstance();
		
		// Try to insert only values in the base table (no translations), to
		// make sure that it works.
		$sql = 'INSERT into PeopleIntl (`Name`) VALUES (\'Stan\')';
		$translator = new Dataface_QueryTranslator('en');
		$tsql = $translator->translateQuery($sql);
		//print_r($tsql);exit;
		$affected_rows = array();
		foreach ( $tsql as $q ){
			$res = mysql_query($q, $app->db());
			if ( !$res ){
				die( mysql_error($app->db()) );
			}
			$affected_rows[$q] = mysql_affected_rows($app->db());
		}


		$this->assertEquals(
			array(
				"INSERT into PeopleIntl (`Name`) VALUES ('Stan')"
				),
			$tsql
			);
		$this->assertEquals(
			array(
				"INSERT into PeopleIntl (`Name`) VALUES ('Stan')"=>1
				),
			$affected_rows
			);
			
		// Try to insert only values into both the base table and the 
		// translated table.
		$sql = 'INSERT into PeopleIntl (`Name`, `Position`) VALUES (\'Stanley\',\'Shop keeper\')';
		$translator = new Dataface_QueryTranslator('en');
		$tsql = $translator->translateQuery($sql);
		//print_r($tsql);exit;
		$affected_rows = array();
		foreach ( $tsql as $q ){
			$res = mysql_query($q, $app->db());
			if ( !$res ){
				die( mysql_error($app->db()) );
			}
			$affected_rows[$q] = mysql_affected_rows($app->db());
		}


		$this->assertEquals(
			array(
				"insert into `PeopleIntl` (`Name`, `Position`) values ('Stanley', 'Shop keeper')",
				"insert into `PeopleIntl_en` (`Position`) values ('Shop keeper')"
				),
			$tsql
			);
		$this->assertEquals(
			array(
				"insert into `PeopleIntl` (`Name`, `Position`) values ('Stanley', 'Shop keeper')" => 1,
				"insert into `PeopleIntl_en` (`Position`) values ('Shop keeper')" => 1
				),
			$affected_rows
			);
	}
	
	
	function test_translateDeleteQuery(){
		$app =& Dataface_Application::getInstance();
		// Try to insert only values into both the base table and the 
		// translated table.
		$sql = 'Delete FROM PeopleIntl where PersonID=5';
		$translator = new Dataface_QueryTranslator('en');
		$tsql = $translator->translateQuery($sql);
		//print_r($tsql);exit;
		$affected_rows = array();
		foreach ( $tsql as $q ){
			$res = mysql_query($q, $app->db());
			if ( !$res ){
				die( mysql_error($app->db()) );
			}
			$affected_rows[$q] = mysql_affected_rows($app->db());
		}


		$this->assertEquals(
			
			array(
				"delete from `PeopleIntl` where `PersonID` = 5",
				"delete from `PeopleIntl_en` where `PersonID` = 5",
				"delete from `PeopleIntl_fr` where `PersonID` = 5"
				),
			$tsql
			);
		$this->assertEquals(
			array(
				"delete from `PeopleIntl` where `PersonID` = 5" => 1,
				"delete from `PeopleIntl_en` where `PersonID` = 5" => 0,
				"delete from `PeopleIntl_fr` where `PersonID` = 5" => 0
				),
			$affected_rows
			);
		
	}
	
	

	
}

?>
