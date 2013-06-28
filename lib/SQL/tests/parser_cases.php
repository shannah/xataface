<?php
require_once 'SQL/Parser.php';
require_once 'SQL/Compiler.php';
require_once 'PHPUnit.php';
require_once 'Var_Dump.php';

class SqlParserTest extends PHPUnit_TestCase {
    // contains the object handle of the parser class
    var $parser;
    var $dumper;


    //constructor of the test suite
    function SqlParserTest($name) {
        $this->PHPUnit_TestCase($name);
    }

    function setUp() {
        $this->parser = new Sql_parser();
        $this->compiler = new SQL_Compiler();
        $this->compiler->version = 2;
        $this->dumper = new Var_Dump(
            array('displayMode'=> VAR_DUMP_DISPLAY_MODE_TEXT));
    }

    function tearDown() {
        unset($this->parser);
    }

    function runTests($tests) {
        foreach ($tests as $number=>$test) {
            $result = $this->parser->parse($test['sql']);
            $expected = $test['expect'];
            $message = "\nSQL: {$test['sql']}\n";
            if (PEAR::isError($result)) {
                $result = $result->getMessage();
                $message .= "\nError:\n".$result;
            } else {
                $message .= "\nExpected:\n".$this->dumper->display($expected);
                $message .= "\nResult:\n".$this->dumper->display($result);
                $message .= "\nOr As A PHP Array:\n".var_export($result, true);
            }
            $message .= "\n*********************\n";
            $this->assertEquals($expected, $result, $message, $number);
            
            // Now to test the compiler
            if ( isset( $test['expected_compiled'] ) and !PEAR::isError($result) ){
            	$compiled = $this->compiler->compile($result);
            	$message = "\nSQL: {$test['sql']}\n";
            	if ( PEAR::isError($compiled) ){
            		$compiled = $compiled->getMessage();
            		$message .= "\nError:\n".$compiled;
            	} else {
            		$message .= "\nParsing {$test['sql']}";
            		$message .= "\nExpected:\n".$this->dumper->display($test['expected_compiled']);
            		$message .= "\nResult:\n".$this->dumper->display($compiled);
            	}
            	$this->assertEquals($test['expected_compiled'], $compiled, $message, $number);
            }
            
        }
    }

    function old_testSelect() {
        include 'select.php';
        $this->runTests($tests);
    }

    function old_testUpdate() {
        include 'update.php';
        $this->runTests($tests);
    }

    function old_testInsert() {
        include 'insert.php';
        $this->runTests($tests);
    }

    function old_testDelete() {
        include 'delete.php';
        $this->runTests($tests);
    }

    function old_testDrop() {
        include 'drop.php';
        $this->runTests($tests);
    }

    function old_testCreate() {
        include 'create.php';
        $this->runTests($tests);
    }
    
    function testMySQLSelect(){
    	$this->parser = new SQL_Parser(null, 'MySQL');
    	$this->compiler =& SQL_Compiler::newInstance('mysql');
    	$this->compiler->version = 2;
    	if ( PEAR::isError($this->compiler) ){
    		trigger_error($this->compiler->getMessage());
    	}
    	echo "The compiler is a ".get_class($this->compiler);
    	include 'mysql_select.php';
    	$this->runTests($tests);
    }
}
