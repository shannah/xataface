<?php
require_once 'BaseTest.php';
require_once 'SQL/Parser.php';
require_once 'SQL/Compiler.php';
require_once 'SQL/Parser/wrapper.php';
require_once 'Dataface/DB.php';


class DB_Test extends BaseTest {

	var $DB;

	
	
	function DB_Test($name = 'DB_Test'){
		$this->BaseTest($name);
		Dataface_Application::getInstance();
		$this->DB =& Dataface_DB::getInstance();
		//parent::BaseTest();
	}
	

	function testTranslate(){
		
		//echo $this->DB->translate_query('select PubType, BiblioString from Publications', 'fr');
		//exit;
		$sql = 'select PubType, BiblioString from Publications limit 5';
		$start = microtime_float();
		$res = $this->DB->query($sql, $this->db) or die(mysql_error($this->db));
		while ($row = mysql_fetch_assoc($res) ){
			print_r($row);
		}
		$stop1 = microtime_float() - $start;
		
		$start = microtime_float();
		$res3 = $this->DB->query($sql, $this->db) or die(mysql_error($this->db));
		while ($row = mysql_fetch_assoc($res3) ){
			print_r($row);
		}
		$stop3 = microtime_float() - $start;
		
		$start = microtime_float();
		$res2 = mysql_query($sql, $this->db) or die(mysql_error($this->db));
		while ($row = mysql_fetch_assoc($res2) ){
			print_r($row);
		}
		$stop2 = microtime_float() - $start;
		
		echo "MySQL: $stop2 ; Translated: $stop1 ; Second translated: $stop3";
		
		$parser = new SQL_Parser(null, 'MySQL');
		//$sql = 'select IFNULL(f.PubType,d.PubType) as PubType, IFNULL(f.BiblioString,d.BiblioString) as BiblioString from Publications d left join Publications_fr f on d.PubID=f.PubID';
		$data = $parser->parse($sql);
		//print_r($data);
		$compiler = new SQL_Compiler();
		echo $compiler->compile($data);
		
	}
	
	function testprepareQuery(){
		$sql = "select PubType, BiblioString from Publications where PubID='1' AND Author='2'";
		$start = microtime_float();
		$out = $this->DB->prepareQuery($sql);
		$elapsed = microtime_float() - $start;
		
		echo "Prepare took approx $elapsed seconds";
		print_r($out);
		
		$sql = "select PubType, BiblioString from Publications where PubID='Rita\\'s calendar' AND Author='2'";
		$start = microtime_float();
		$out = $this->DB->prepareQuery($sql);
		$elapsed = microtime_float() - $start;
		
		echo "Prepare took approx $elapsed seconds";
		print_r($out);
		$sql = "select PubType, BiblioString from Publications where PubID='Rita\\\\\\'s calendar' AND Author='2'";
		$start = microtime_float();
		$out = $this->DB->prepareQuery($sql);
		$elapsed = microtime_float() - $start;
		
		echo "Prepare took approx $elapsed seconds";
		print_r($out);
	}
	
	function test_query(){
	
		$sql = "select PubType, BiblioString from Publications where PublicationID='1'";
		$res = $this->DB->query($sql);
		$this->assertTrue($res);
		if (! $res ){
			echo mysql_error();
		}
		
		$row = mysql_fetch_assoc($res);
		$this->assertEquals(
			array(
				'PubType'=>'Refereed Journal',
				'BiblioString'=>'Amit, H. Autonomous, metamorphic technology for B-Trees. In POT NOSSDAV (Dec. 1991).'
				),
			$row
			);
			
		$sql = "Update Publications set PubType = 'Experimental' where PublicationID='1'";
		$res = $this->DB->query($sql);
		$this->assertTrue($res);
		if (! $res ){
			echo mysql_error();
		}
		
		$this->assertTrue(mysql_affected_rows() === 1);
		
		$sql = "Insert into Publications (PubType) VALUES ('My new type')";
		$res = $this->DB->query($sql);
		$this->assertTrue($res);
		if (! $res ){
			echo mysql_error();
		}
		
		$this->assertTrue(mysql_affected_rows() === 1);
		
		
		
			
		
	}
	
	

	
}

?>
