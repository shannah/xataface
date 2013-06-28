<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2004 Brent Cook                                        |
// +----------------------------------------------------------------------+
// | This library is free software; you can redistribute it and/or        |
// | modify it under the terms of the GNU Lesser General Public           |
// | License as published by the Free Software Foundation; either         |
// | version 2.1 of the License, or (at your option) any later version.   |
// |                                                                      |
// | This library is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU    |
// | Lesser General Public License for more details.                      |
// |                                                                      |
// | You should have received a copy of the GNU Lesser General Public     |
// | License along with this library; if not, write to the Free Software  |
// | Foundation, Inc., 59 Temple Place, Suite 330,Boston,MA 02111-1307 USA|
// +----------------------------------------------------------------------+
// | Authors: Brent Cook <busterbcook@yahoo.com>                          |
// |          Jason Pell <jasonpell@hotmail.com>                          |
// |          Lauren Matheson <inan@canada.com>                           |
// |          John Griffin <jgriffin316@netscape.net> 					  |
// |          Steve Hannah <shannah@sfu.ca>								  |
// +----------------------------------------------------------------------+
//
// $Id: Parser.php,v 1.7 2006/05/05 00:13:45 sjhannah Exp $
//

require_once 'PEAR.php';
require_once 'SQL/Lexer.php';

/**
 * A sql parser
 *
 * @author  Brent Cook <busterbcook@yahoo.com>
 * @version 0.5
 * @access  public
 * @package SQL_Parser
 */
class SQL_Parser
{
    var $lexer;
    var $token;
    var $tokText;

// symbol definitions
    var $functions = array();
    var $types = array();
    var $symbols = array();
    var $constants = array();
    var $operators = array();
    var $expression_operators = array();
    var $synonyms = array();
    var $reserved = array();
    var $units = array();
    var $dialect;
    
    /**
     * A flag to see if there has been an interval that has not been closed
     * by a unit.
     */
    var $openInterval = false;


    var $dialects = array("ANSI", "MySQL");

// {{{ function SQL_Parser($string = null)
    function SQL_Parser($string = null, $dialect = "MySQL", $useCache = true) {
        $this->dialect = $dialect;
        if ( $useCache ) {
        	$parser =& SQL_Parser::loadParser($dialect);
        	$this->cloneDialect($parser);
        }
        else {
        	$this->setDialect($dialect);
        }

        if (is_string($string)) {
            $this->lexer = new Lexer($string, 1);
            $this->lexer->symbols =& $this->symbols;

        }
    }
// }}}

	/**
	 * Loads a cached parser for a particular dialect.
	 */
	function &loadParser($dialect){
		static $parsers = 0;
		if ( !$parsers ) $parsers = array();
		if ( !isset($parsers[$dialect]) ){
			$parsers[$dialect] = new SQL_Parser(null, $dialect, false);
		}
		return $parsers[$dialect];
	
	}
	
	/**
	 * Copies the dialect from another parser.
	 */
	function cloneDialect(&$parser){
		$this->types =& $parser->types;
		$this->functions =& $parser->functions;
		$this->operators =& $parser->operators;
		$this->constants =& $parser->constants;
		$this->expression_operators =& $parser->expression_operators;
		$this->commands =& $parser->commands;
		$this->synonyms =& $parser->synonyms;
		$this->quantifiers =& $parser->quantifiers;
		$this->reserved =& $parser->reserved;
		$this->units =& $parser->units;
		$this->symbols =& $parser->symbols;
		
	}

// {{{ function setDialect($dialect)
    function setDialect($dialect) {
        if (in_array($dialect, $this->dialects)) {
            include 'SQL/Dialect_'.$dialect.'.php';
            $this->types = array_flip($dialect['types']);
            $this->constants = array_flip($dialect['constants']);
            $this->functions = array_flip($dialect['functions']);
            $this->operators = array_flip($dialect['operators']);
            $this->expression_operators = array_flip($dialect['expression_operators']);
            $this->commands = array_flip($dialect['commands']);
            $this->synonyms = $dialect['synonyms'];
            $this->quantifiers = array_flip($dialect['quantifiers']);
            $this->reserved = array_flip($dialect['reserved']);
            $this->units = array_flip($dialect['units']);
            $this->symbols = array_merge(
            	$this->types,
                $this->functions,
                $this->operators,
                $this->commands,
                $this->reserved,
                $this->units,
                $this->constants,
                array_flip($dialect['conjunctions']));
        } else {
            return $this->raiseError('Unknown SQL dialect:'.$dialect);
        }
    }
// }}}
 
// {{{ getParams(&$values, &$types)
    function getParams(&$values, &$types) {
        $values = array();
        $types = array();
        while ($this->token != ')') {
            $this->getTok();
            if ( $this->isFunc() ){
            	$val = $this->parseFunctionOpts();
            	if ( PEAR::isError($val) ){
            		
            		return $val;
            	}
            	
            	$values[] = $val;
            	$types[] = 'function';
            	$this->lexer->pushBack();
            	//$this->lexer->pushBack();
            	$this->getTok();
            	//$this->getTok();

            }
            else if ($this->isVal() || ($this->token == 'ident')) {
                $values[] = $this->tokText;
                $types[] = $this->token;
            } elseif ($this->token == ')') {
                return false;
            } else {
            	
                return $this->raiseError('Expected a value');
            }
            $this->getTok();
            if (($this->token != ',') && ($this->token != ')')) {
            	return $this->raiseError('Expected , or )');
            }
        }
    }
// }}}

    // {{{ raiseError($message)
    function raiseError($message) {
    	//require_once '../Dataface/Error.php';
        $end = 0;
        if ($this->lexer->string != '') {
            while (($this->lexer->lineBegin+$end < $this->lexer->stringLen)
               && ($this->lexer->string{$this->lexer->lineBegin+$end} != "\n")){
                ++$end;
            }
        }
        
        $message = 'Parse error: '.$message.' on line '.
            ($this->lexer->lineNo+1)."\n";
        $message .= substr($this->lexer->string, $this->lexer->lineBegin, $end)."\n";
        $length = is_null($this->token) ? 0 : strlen($this->tokText);
        $message .= str_repeat(' ', abs($this->lexer->tokPtr - 
                               $this->lexer->lineBegin - $length))."^";
        $message .= ' found: "'.$this->tokText.'"';//. Dataface_Error::printStackTrace();

        return PEAR::raiseError($message);
    }
    // }}}

    // {{{ isType()
    function isType() {
        return isset($this->types[$this->token]);
    }
    // }}}

    // {{{ isVal()
    function isVal($tok=-1) {
    	if ( $tok === -1 ){
    		$tok = $this->token;
    	}
       return (($tok == 'real_val') ||
               ($tok == 'int_val') ||
               ($tok == 'text_val') ||
               ($tok == 'null'));
    }
    // }}}

    // {{{ isFunc()
    function isFunc() {
        return isset($this->functions[$this->token]);
    }
    // }}}


   

    // {{{ isCommand()
    function isCommand() {
        return isset($this->commands[$this->token]);
    }
    // }}}
    
    function isUnit(){
    	return isset($this->units[$this->token]);
    }

    // {{{ isReserved()
    function isReserved() {
        return isset($this->symbols[$this->token]);
    }
    
    function isConstant(){
    	return isset($this->constants[$this->token]);
    }
    // }}}

    // {{{ isOperator()
    function isOperator() {
        return isset($this->operators[$this->token]);
    }
    
    function isExpressionOperator(){
    	return isset($this->expression_operators[$this->token]);
    }
    
    // }}}
    
    // {{{ isQuantifier()
    function isQuantifier(){
    	return isset($this->quantifiers[$this->tokText]);
    }
	// }}}
	
    // {{{ getTok()
    function getTok() {
        $this->token = $this->lexer->lex();
        $this->tokText = $this->lexer->tokText;
        // deal with case where the token may be identified as a function but is actually an identifier
        if ( $this->token == 'interval' ){
        	$this->openInterval = true;
        }
        
        if ( $this->openInterval and isset($this->units[$this->token]) ){
        	// just leave it be... this a unit
        	$this->openInterval = false;
        } else if ( !isset($this->constants[$this->token]) and isset( $this->functions[$this->token]) and
        	 !isset( $this->reserved[$this->token]) ){
        	 
        	$nextTok = $this->lexer->lex();
        	$this->lexer->pushBack();
        	if ( $nextTok != '(' ){
        		//$this->tokText = $this->token;
        		$this->token = 'ident';
        	}	
        }
        

    }
    // }}}

    // {{{ &parseFieldOptions()
    function parseFieldOptions()
    {
        // parse field options
        $namedConstraint = false;
        $options = array();
        while (($this->token != ',') && ($this->token != ')') &&
                ($this->token != null)) {
            $option = $this->token;
            $haveValue = true;
            switch ($option) {
                case 'constraint':
                    $this->getTok();
                    if ($this->token = 'ident') {
                        $constraintName = $this->tokText;
                        $namedConstraint = true;
                        $haveValue = false;
                    } else {
                        return $this->raiseError('Expected a constraint name');
                    }
                    break;
                case 'default':
                    $this->getTok();
                    if ($this->isVal()) {
                        $constraintOpts = array('type'=>'default_value',
                                                'value'=>$this->tokText);
                    } elseif ($this->isFunc()) {
                        $results = $this->parseFunctionOpts();
                        if (PEAR::isError($results)) {
                            return $results;
                        }
                        $results['type'] = 'default_function';
                        $constraintOpts = $results;
                    } else {
                        return $this->raiseError('Expected default value');
                    }
                    break;
                case 'primary':
                    $this->getTok();
                    if ($this->token == 'key') {
                        $constraintOpts = array('type'=>'primary_key',
                                                'value'=>true);
                    } else {
                        return $this->raiseError('Expected "key"');
                    }
                    break;
                case 'not':
                    $this->getTok();
                    if ($this->token == 'null') {
                        $constraintOpts = array('type'=>'not_null',
                                                'value' => true);
                    } else {
                        return $this->raiseError('Expected "null"');
                    }
                    break;
                case 'check':
                    $this->getTok();
                    if ($this->token != '(') {
                        return $this->raiseError('Expected (');
                    }
                    $results = $this->parseSearchClause();
                    if (PEAR::isError($results)) {
                        return $results;
                    }
                    $results['type'] = 'check';
                    $constraintOpts = $results;
                    if ($this->token != ')') {
                        return $this->raiseError('Expected )');
                    }
                    break;
                case 'unique':
                    $this->getTok();
                    if ($this->token != '(') {
                        return $this->raiseError('Expected (');
                    }
                    $constraintOpts = array('type'=>'unique');
                    $this->getTok();
                    while ($this->token != ')') {
                        if ($this->token != 'ident') {
                            return $this->raiseError('Expected an identifier');
                        }
                        $constraintOpts['column_names'][] = $this->tokText;
                        $this->getTok();
                        if (($this->token != ')') && ($this->token != ',')) {
                            return $this->raiseError('Expected ) or ,');
                        }
                    }
                    if ($this->token != ')') {
                        return $this->raiseError('Expected )');
                    }
                    break;
                case 'month': case 'year': case 'day': case 'hour':
                case 'minute': case 'second':
                    $intervals = array(
                                    array('month'=>0,
                                          'year'=>1),
                                    array('second'=>0,
                                          'minute'=>1,
                                          'hour'=>2,
                                          'day'=>3));
                    foreach ($intervals as $class) {
                        if (isset($class[$option])) {
                            $constraintOpts = array('quantum_1'=>$this->token);
                            $this->getTok();
                            if ($this->token == 'to') {
                                $this->getTok();
                                if (!isset($class[$this->token])) {
                                    return $this->raiseError(
                                        'Expected interval quanta');
                                }
                                if ($class[$this->token] >=
                                    $class[$constraintOpts['quantum_1']]) {
                                    return $this->raiseError($this->token.
                                        ' is not smaller than '.
                                        $constraintOpts['quantum_1']);
                                } 
                                $constraintOpts['quantum_2'] = $this->token;
                            } else {
                                $this->lexer->unget();
                            }
                            break;
                        }
                    }
                    if (!isset($constraintOpts['quantum_1'])) {
                        return $this->raiseError('Expected interval quanta');
                    }
                    $constraintOpts['type'] = 'values';
                    break;
                case 'null':
                    $haveValue = false;
                    break;
                default:
                    return $this->raiseError('Unexpected token '
                                        .$this->tokText);
            }
            if ($haveValue) {
                if ($namedConstraint) {
                    $options['constraints'][$constraintName] = $constraintOpts;
                    $namedConstraint = false;
                } else {
                    $options['constraints'][] = $constraintOpts;
                }
            }
            $this->getTok();
        }
        return $options;
    }
    // }}}

    // {{{ parseSearchClause()
    function parseSearchClause($subSearch = false)
    {
        $clause = array();
        // parse the first argument
        $this->getTok();
        if ($this->token == 'not') {
            $clause['neg'] = true;
            $this->getTok();
        }
        if ( $this->token == 'exists' ){
        	$clause['exists'] = true;
        	$this->getTok();
        }

        $foundSubclause = false;
        
        if ($this->token == '(') {
        	$this->getTok();
        	if ( $this->token == 'select' ){
        		$clause['arg_1']['value'] = $this->parseSelect(true);
        		if ( PEAR::isError($clause['arg_1']['value']) ){
        			return $clause['arg_1']['value'];
        		}
        		$clause['arg_1']['type'] = 'subquery';
        	} else {
        		$this->lexer->pushBack();
        		$clause['arg_1']['value'] = $this->parseSearchClause(true);
				if ( PEAR::isError($clause['arg_1']['value']) ){
					return $clause['arg_1']['value'];
				}
				$clause['arg_1']['type'] = 'subclause';
				
        	}
        	if ($this->token != ')') {
				return $this->raiseError('Expected ")"');
			}
            
            $foundSubclause = true;
        } else if ($this->token == 'match'){
        	$clause['arg_1']['type'] = 'match';
        	// this is a match clause
        	$func = $this->parseFunctionOpts();
        	$clause['arg_1']['value'] = $func['args'];
        	$this->getTok();
        	if ( $this->token != 'against'){
        		return $this->raiseError('Expected against');
        	}
        	$this->getTok();
        	if ( $this->token != '(') {
        		return $this->raiseError('Expected open bracket after against in match clause');
        	}
        	$this->getTok();
        	if ( $this->token != 'text_val' ){
        		return $this->raiseError('Expected text value in against clause');
        	}
        	
        	$clause['arg_1']['against'] = $this->tokText;
        	
        	
        	$this->getTok();
        	if ( $this->token == 'in' ){
        		$this->getTok();
        		if ( $this->token == 'boolean' ){
        			$this->getTok();
        			if ( $this->token == 'mode' ){
        				$clause['arg_1']['boolean_mode'] = true;
        				$this->getTok();
        			} else {
        				return $this->raiseError('Expected Mode in against clause');
        			}
        		} else {
        			return $this->triggerError('Expected Boolean in against clause.');
        		}
        	} else if ( $this->token == ')' ){
        		// do nothing.. this is what we want
        	} 
        	if ( $this->token != ')'){
        		return $this->triggerError('Expected closing bracket after against clause.');
        	}
        	
        	$foundSubclause = true;
        
        } else if ($this->isFunc()){
        	 $result = $this->parseFunctionOpts();
        	 if ( PEAR::isError($result) ){
        	 	return $result;
        	 }
        	 $clause['arg_1']['value'] = $result;
        	 $clause['arg_1']['type'] = 'function';
        
        } else if ($this->isReserved()) {
            return $this->raiseError('Expected a column name or value');
        } else {
            $clause['arg_1']['value'] = $this->tokText;
            $clause['arg_1']['type'] = $this->token;
        }

        // parse the operator
        if (!$foundSubclause) {
            $this->getTok();
            //if (!$this->isOperator()) {
            //    return $this->raiseError('Expected an operator');
            //}
            if ( $this->isOperator() ){
				$clause['op'] = $this->token;
	
				$this->getTok();
				
				if ( $clause['op'] == 'is' ){
					// parse for 'is' operator
					if ($this->token == 'not') {
						$clause['neg'] = true;
						$this->getTok();
					}
					if ($this->token != 'null') {
						return $this->raiseError('Expected "null"');
					}
					$clause['arg_2']['value'] = '';
					$clause['arg_2']['type'] = $this->token;
				} else {
					if ($clause['op'] == 'not' ){
						// parse for 'not in' operator
						//if ($this->token != 'in') {
						//    return $this->raiseError('Expected "in"');
						//}
						$clause['op'] = $this->token;
						$clause['neg'] = true;
						$this->getTok();
						
					
					}
					switch ($clause['op']) {
						
						case 'in':
							// parse for 'in' operator 
							if ($this->token != '(') {
								return $this->raiseError('Expected "("');
							}
		
							// read the subset
							$this->getTok();
							// is this a subselect?
							if ($this->token == 'select') {
								$clause['arg_2']['value'] = $this->parseSelect(true);
								$clause['arg_2']['type'] = 'command';
							} else {
								$this->lexer->pushBack();
								// parse the set
								$result = $this->getParams($clause['arg_2']['value'],
														$clause['arg_2']['type']);
								if (PEAR::isError($result)) {
									return $result;
								}
							}
							if ($this->token != ')') {
								return $this->raiseError('Expected ")"');
							}
							break;
						case 'and': case 'or': case '||': case '&&':
							$this->lexer->unget();
							break;
						default:
							// parse for in-fix binary operators
							//if ($this->isReserved()) {
							//    return $this->raiseError('Expected a column name or value');
							//}
							if ($this->token == '(') {
								$this->getTok();
								if ( $this->token == 'select' ){
									$clause['arg_2']['value'] = $this->parseSelect(true);
									if ( PEAR::isError($clause['arg_2']['value']) ){
										return $clause['arg_2']['value'];
									}
									$clause['arg_2']['type'] = 'subquery';
								} else {
									$this->lexer->pushBack();
									$clause['arg_2']['value'] = $this->parseSearchClause(true);
									if ( PEAR::isError($clause['arg_2']['value']) ){
										return $clause['arg_2']['value'];
									}
									$clause['arg_2']['type'] = 'subclause';
									 $this->getTok();
								}
							   
								if ($this->token != ')') {
									return $this->raiseError('Expected ")"');
								}
							} else if ( $this->isFunc() ){
								$clause['arg_2']['value'] = $this->parseFunctionOpts();
								if ( PEAR::isError($clause['arg_2']['value']) ){
									return $clause['arg_2']['value'];
								}
								$clause['arg_2']['type'] = 'function';
							} else {
								$clause['arg_2']['value'] = $this->tokText;
								$clause['arg_2']['type'] = $this->token;
							}
					}
				}
			}
        }

        $this->getTok();
        if (in_array($this->token, array('and','or','||','&&'))){//($this->token == 'and') || ($this->token == 'or')) {
            $op = $this->token;
            $subClause = $this->parseSearchClause($subSearch);
            if (PEAR::isError($subClause)) {
                return $subClause;
            } else {
                $clause = array('arg_1' => $clause,
                                'op' => $op,
                                'arg_2' => $subClause);
            }
        } else {
            $this->lexer->unget();
        }
        return $clause;
    }
    // }}}

    // {{{ parseFieldList()
    function parseFieldList()
    {
        $this->getTok();
        if ($this->token != '(') {
            return $this->raiseError('Expected (');
        }

        $fields = array();
        while (1) {
            // parse field identifier
            $this->getTok();
            if ($this->token == 'ident') {
                $name = $this->tokText;
            } elseif ($this->token == ')') {
                return $fields;
            } else {
                return $this->raiseError('Expected identifier');
            }

            // parse field type
            $this->getTok();
            if ($this->isType($this->token)) {
                $type = $this->token;
            } else {
                return $this->raiseError('Expected a valid type');
            }

            $this->getTok();
            // handle special case two-word types
            if ($this->token == 'precision') {
                // double precision == double
                if ($type == 'double') {
                    return $this->raiseError('Unexpected token');
                }
                $this->getTok();
            } elseif ($this->token == 'varying') {
                // character varying() == varchar()
                if ($type == 'character') {
                    $type == 'varchar';
                    $this->getTok();
                } else {
                    return $this->raiseError('Unexpected token');
                }
            }
            $fields[$name]['type'] = $this->synonyms[$type];
            // parse type parameters
            if ($this->token == '(') {
                $results = $this->getParams($values, $types);
                if (PEAR::isError($results)) {
                    return $results;
                }
                switch ($fields[$name]['type']) {
                    case 'numeric':
                        if (isset($values[1])) {
                            if ($types[1] != 'int_val') {
                                return $this->raiseError('Expected an integer');
                            }
                            $fields[$name]['decimals'] = $values[1];
                        }
                    case 'float':
                        if ($types[0] != 'int_val') {
                            return $this->raiseError('Expected an integer');
                        }
                        $fields[$name]['length'] = $values[0];
                        break;
                    case 'char': case 'varchar':
                    case 'integer': case 'int':
                        if (sizeof($values) != 1) {
                            return $this->raiseError('Expected 1 parameter');
                        }
                        if ($types[0] != 'int_val') {
                            return $this->raiseError('Expected an integer');
                        }
                        $fields[$name]['length'] = $values[0];
                        break;
                    case 'set': case 'enum':
                        if (!sizeof($values)) {
                            return $this->raiseError('Expected a domain');
                        }
                        $fields[$name]['domain'] = $values;
                        break;
                    default:
                        if (sizeof($values)) {
                            return $this->raiseError('Unexpected )');
                        }
                }
                $this->getTok();
            }

            $options = $this->parseFieldOptions();
            if (PEAR::isError($options)) {
                return $options;
            }

            $fields[$name] += $options;

            if ($this->token == ')') {
                return $fields;
            } elseif (is_null($this->token)) {
                return $this->raiseError('Expected )');
            }
        }
    }
    // }}}
    
    
    function parseExpression($firstParam = null){
    
    	$opts = array();
    	if ( isset($firstParam) ) $opts[] = $firstParam;
    	$breakers = array('from','as',',','where',')');
    	while (!in_array($this->token, $breakers) and !$this->isOperator()){
			if ( $this->isExpressionOperator() ){
				$opts[] = array('type'=>'operator', 'value'=>$this->tokText);
				$this->getTok();
			}
			else if ( $this->isVal() ){
				$val = $this->tokText;
				$type = $this->token;
				$this->getTok();
				
				$coldef = array('type'=>$type , 'value'=>$val);
				$opts[] = $coldef;
				
			
				
			
			} else if ($this->token == 'ident' || $this->token == '*' ) {
				// The current token is an identifier.
				//  We needed to include checks for '*' explicitly because the lexer recognizes it as a reserved word.
				//  We need to check for open parenthesis because function names without open parenthesis
				//  are considered to be identifiers - and hence fall into this category as well.
				
				$prevTok = $this->token;
				$prevTokText = $this->tokText;
				$this->getTok();
				
				
				$columnTable = ''; // temp default value for column table until we get it sorted out.

				if ($prevTok == 'ident') {
					$colType = 'ident';
					
					$columnName = $prevTokText;
					if ( $columnName{strlen($columnName)-1} == '.') {
						$columnName .= '*';
						$colType = 'glob';
					}
					if ( strpos($columnName, '.') !== false ){
						// In introduce $columnTable2 and $columnName2 to be used in
						// the alternate 'columns' data structure.
						list($columnTable2, $columnName2) = explode('.',$columnName);
					} else {
						$columnTable2 = '';
						$columnName2 = $columnName;
					}
				
				} else if ($prevTok == '*' ){
					$colType = 'glob';
					$columnName = $prevTok;
					$columnName2 = $columnName;
					$columnTable2 = '';
				} else {
					return $this->raiseError('Expected column name');
				}

				
				
				$opts[] = array('type'=>$colType, 'table'=>$columnTable2, 'value'=>$columnName2, 'alias'=>'');
					
				if (!in_array($this->token, $breakers)) {
					$this->getTok();
				}
				
			} elseif ($this->isFunc()) {
				$result = $this->parseFunctionOpts();
				if (PEAR::isError($result)) {
					return $result;
				}
				//$opts[] = $result;
				$this->getTok();

				
				$opts[] = array('type'=>'func', 'table'=>'', 'value'=>$result, 'alias'=> '');
			   
			} else if ( $this->token == '(' ){
				$this->getTok();
				if ( $this->token == 'select' ){
					$result = $this->parseSelect(true);
					if ( PEAR::isError($result) ) return $result;
					//$this->getTok();
					$this->getTok();
					
					//else return $this->raiseError("Unexpected token '".$this->token.'"');
					//else $this->lexer->pushBack();
					
					$opts[] = array('type'=>'subselect', 'table'=>'', 'value'=>$result, 'alias'=>$columnAlias);
				} else {	
					$opt  = $this->parseExpression();
					if ( PEAR::isError($opt) ) return $opt;
					$opts[] = $opt;
					$this->getTok();
				}
			} else {
					return $this->raiseError('Unexpected token "'.$this->token.'"');
			}
		}
		
		if ( count($opts) == 1 ){
			return $opts[0];
		} else {
			return array('type'=>'expression', 'value'=>$opts);
		}
	
    
    }

    // {{{ parseFunctionOpts()
    function parseFunctionOpts()
    {
        $function = $this->token;
        $opts['name'] = $function;
        $this->getTok();
        if ($this->token != '(') {
            return $this->raiseError('Expected "("');
        }
      	$opts['args'] = array();
		while ( true ){
			$this->getTok();
			if ( $this->token == ')' ){
				$this->lexer->pushBack();
				break;
				
			} else if ( $this->token == 'interval' ){
				$arg = array();
				$arg['type'] = 'interval';
				
				$this->getTok();
				if ( $this->isVal() ){
					$arg['value'] = $this->tokText;
					$arg['expression_type'] = $this->token;
					$this->getTok();
					if ( $this->isUnit() ){
						$arg['unit'] = $this->token;
					}
				}
				
				if ( !isset( $arg['value'] ) || !isset( $arg['unit'] ) ){
					return $this->raiseError("Expected an expression and unit for the interval");
				}
				$opts['args'][] =& $arg;
				unset($arg);
				
				
			} else if ( $this->isQuantifier() ){
				$arg = array();
				$arg['quantifier'] = $this->tokText;
				$this->getTok();
				if ( $this->token == 'ident' ){
					$arg['type'] = 'ident';
					$arg['value'] = $this->tokText;
					$opts['args'][] =& $arg;
					unset($arg);
				} else if ( $this->isFunc() ){
					$arg['type'] = 'function';
					$arg['value'] = $this->parseFunctionOpts();
					if ( PEAR::isError($arg['value']) ) {
						return $arg['value'];
					}
					$opts['args'][] =& $arg;
					unset($arg);
				} else {
					return $this->raiseError("Expected column, alias, or function name");
				}
			} else if ( $this->token == 'ident' || $this->token == '*'){
				if ( $this->tokText == 'CURRENT_DATE' ){
					//print_r($this->lexer->symbols);
					//echo $this->token.':'.$this->tokText;
					
					//echo "Recognizing CURRENT_DATE is ident";exit;
				}
				$arg = array();
				$arg['type'] = 'ident';
				$arg['value'] = $this->tokText;
				$this->getTok();
				if ( $this->isExpressionOperator() ){
					$arg = $this->parseExpression($arg);
					if ( PEAR::isError($arg) ) return $arg;
					$this->lexer->pushBack();
				} else {
					$this->lexer->pushBack();
				}
				$opts['args'][] =& $arg;
				unset($arg);
			} else if ($this->isConstant() ){
				$arg = array();
				$arg['type'] = 'constant';
				$arg['value'] = $this->token;
				$this->getTok();
				if ( $this->isExpressionOperator() ){
					$arg = $this->parseExpression($arg);
					if ( PEAR::isError($arg) ) return $arg;
					$this->lexer->pushBack();
				} else {
					$this->lexer->pushBack();
				}
				$opts['args'][] =& $arg;
				unset($arg);
			} else if ( $this->isFunc() ){
				$arg = array();
				$arg['type'] = 'function';
				$arg['value'] =  $this->parseFunctionOpts();
				if ( PEAR::isError($arg['value']) ){
					return $arg['value'];
				}
				$this->getTok();
				if ( $this->isExpressionOperator() ){
					$arg = $this->parseExpression($arg);
					if ( PEAR::isError($arg) ) return $arg;
					$this->lexer->pushBack();
				}
				else $this->lexer->pushBack();
				
				$opts['args'][] =& $arg;
				unset($arg);
			} else if ( $this->token == '(' ){
				$this->getTok();
				$arg = $this->parseExpression();
				$this->getTok();
				if ( $this->isExpressionOperator() ){
					$arg = $this->parseExpression($arg);
					if ( PEAR::isError($arg) ) return $arg;
					$this->lexer->pushBack();
				} else $this->lexer->pushBack();
				$opts['args'][] = & $arg;
				unset($arg);
			
			} else if ( $this->token == ','){
				continue;
			
			} else if ( $this->token) {
				$arg = array();
				$arg['type'] = $this->token;
				$arg['value'] = $this->tokText;
				//echo '['.$arg['type'].'/'.$this->tokText.']';
				$this->getTok();
				if ( $this->isExpressionOperator() ){
					$arg = $this->parseExpression($arg);
					if ( PEAR::isError($arg) ) return $arg;
					$this->lexer->pushBack();
				}
				else $this->lexer->pushBack();
				
				$opts['args'][] =& $arg;
				unset($arg);
			} else {
				return PEAR::raiseError("Expecting token but found ".$this->tokText);
			}
				
			
		}
                
        $this->getTok();
        if ($this->token != ')') {
            return $this->raiseError('Expected ")"');
        }
 
        // check for an alias
        $this->getTok();
        if ($this->token == ',' || $this->token == 'from' || $this->isOperator()) {
            $this->lexer->pushBack();
        } elseif ($this->token == 'as') {
            $this->getTok();
            if ($this->token == 'ident' ) {
                $opts['alias'] = $this->tokText;
            } else {
                return $this->raiseError('Expected column alias');
            }
        } else {
            if ($this->token == 'ident' ) {
                $opts['alias'] = $this->tokText;
            } else {
                //return $this->raiseError('Expected column alias, from or comma');
                $this->lexer->pushBack();
            }
        }
        return $opts;
    }
    // }}}
    
    function parseLimit(){
    	$this->getTok();
		if ($this->token != 'int_val') {
			return $this->raiseError('Expected an integer value');
		}
		$length = $this->tokText;
		$start = 0;
		$this->getTok();
		if ($this->token == ',') {
			$this->getTok();
			if ($this->token != 'int_val') {
				return $this->raiseError('Expected an integer value');
			}
			$start = $length;
			$length = $this->tokText;
			$this->getTok();
			
		}
		return array('start'=>$start, 'length'=>$length);
	
    }

    // {{{ parseCreate()
    function parseCreate() {
        $this->getTok();
        switch ($this->token) {
            case 'table':
                $tree = array('command' => 'create_table');
                $this->getTok();
                if ($this->token == 'ident') {
                    $tree['table_names'][] = $this->tokText;
                    $fields = $this->parseFieldList();
                    if (PEAR::isError($fields)) {
                        return $fields;
                    }
                    $tree['column_defs'] = $fields;
//                    $tree['column_names'] = array_keys($fields);
                } else {
                    return $this->raiseError('Expected table name');
                }
                break;
            case 'index':
                $tree = array('command' => 'create_index');
                break;
            case 'constraint':
                $tree = array('command' => 'create_constraint');
                break;
            case 'sequence':
                $tree = array('command' => 'create_sequence');
                break;
            default:
                return $this->raiseError('Unknown object to create');
        }
        return $tree;
    }
    // }}}

    // {{{ parseInsert()
    function parseInsert() {
        $this->getTok();
        if ($this->token == 'into') {
            $tree = array('command' => 'insert');
            $this->getTok();
            if ($this->token == 'ident') {
                $tree['table_names'][] = $this->tokText;
                $this->getTok();
            } else {
                return $this->raiseError('Expected table name');
            }
            if ($this->token == '(') {
                $results = $this->getParams($values, $types);
                if (PEAR::isError($results)) {
                	
                    return $results;
                } else {
                    if (sizeof($values)) {
                        $tree['column_names'] = $values;
                    }
                }
                $this->getTok();
                unset($values); unset($types);
            }
            if ($this->token == 'values') {
                $this->getTok();
                $results = $this->getParams($values, $types);
                
                
                if (PEAR::isError($results)) {
                	
                	
                    return $results;
                } else {
                    if (isset($tree['column_defs']) && 
                        (sizeof($tree['column_defs']) != sizeof($values))) {
                        
                        return $this->raiseError('field/value mismatch');
                    }
                    
                    if (sizeof($values)) {
                    	
                        foreach ($values as $key=>$value) {
                            $values[$key] = array('value'=>$value,
                                                    'type'=>$types[$key]);
                        }
                        $tree['values'] = $values;
                    } else {
                    	
                        return $this->raiseError('No fields to insert');
                    }
                }
                unset($values); unset($types);
            } else {
                return $this->raiseError('Expected "values"');
            }
        } else {
            return $this->raiseError('Expected "into"');
        }
        return $tree;
    }
    // }}}

    // {{{ parseUpdate()
    function parseUpdate() {
        $this->getTok();
        if ($this->token == 'ident') {
            $tree = array('command' => 'update');
            $tree['table_names'][] = $this->tokText;
        } else {
            return $this->raiseError('Expected table name');
        }
        $this->getTok();
        if ($this->token != 'set') {
            return $this->raiseError('Expected "set"');
        }
        while (true) {
            $this->getTok();
            if ($this->token != 'ident') {
                return $this->raiseError('Expected a column name');
            }
            $tree['column_names'][] = $this->tokText;
            $this->getTok();
            if ($this->token != '=') {
                return $this->raiseError('Expected =');
            }
            $this->getTok();
            if (!$this->isVal($this->token) and !$this->isFunc($this->token)) {
                return $this->raiseError('Expected a value or function');
            }
            if ( $this->isVal($this->token) ){
            	$tree['values'][] = array('value'=>$this->tokText,
                                      	'type'=>$this->token);
            } else if ( $this->isFunc($this->token) ){
            	$tree['values'][] = array('value' => $this->parseFunctionOpts(),
            							  'type' => $this->token);
            }	
            $this->getTok();
            if ($this->token == 'where' || $this->token == 'limit') {
            	break;
             
            } elseif ($this->token != ',') {
                return $this->raiseError('Expected "where" or ","');
            }
        }
        
        while (true){
        	if ($this->token == 'where' ){
				$clause = $this->parseSearchClause();
				if (PEAR::isError($clause)) {
					return $clause;
				}
				$tree['where_clause'] = $clause;
			} else if ( $this->token == 'limit' ){
				$clause = $this->parseLimit();
				$tree['limit_clause'] = $clause;
				
             } else {
             	break;
             }
        	//$this->getTok();
        }
        return $tree;
    }
    // }}}

    // {{{ parseDelete()
    function parseDelete() {
        $this->getTok();
        if ($this->token != 'from') {
            return $this->raiseError('Expected "from"');
        }
        $tree = array('command' => 'delete');
        $this->getTok();
        if ($this->token != 'ident') {
            return $this->raiseError('Expected a table name');
        }
        $tree['table_names'][] = $this->tokText;
        $this->getTok();
        if ($this->token != 'where') {
            return $this->raiseError('Expected "where"');
        }
        $clause = $this->parseSearchClause();
        if (PEAR::isError($clause)) {
            return $clause;
        }
        $tree['where_clause'] = $clause;
        return $tree;
    }
    // }}}

    // {{{ parseDrop()
    function parseDrop() {
        $this->getTok();
        switch ($this->token) {
            case 'table':
                $tree = array('command' => 'drop_table');
                $this->getTok();
                if ($this->token != 'ident') {
                    return $this->raiseError('Expected a table name');
                }
                $tree['table_names'][] = $this->tokText;
                $this->getTok();
                if (($this->token == 'restrict') ||
                    ($this->token == 'cascade')) {
                    $tree['drop_behavior'] = $this->token;
                }
                $this->getTok();
                if (!is_null($this->token)) {
                    return $this->raiseError('Unexpected token');
                }
                return $tree;
                break;
            case 'index':
                $tree = array('command' => 'drop_index');
                break;
            case 'constraint':
                $tree = array('command' => 'drop_constraint');
                break;
            case 'sequence':
                $tree = array('command' => 'drop_sequence');
                break;
            default:
                return $this->raiseError('Unknown object to drop');
        }
        return $tree;
    }
    // }}}

    // {{{ parseSelect()
    function parseSelect($subSelect = false) {
        $tree = array('command' => 'select');
        $this->getTok();
        if (($this->token == 'distinct') || ($this->token == 'all')) {
            $tree['set_quantifier'] = $this->token;
            $this->getTok();
        }
       
       if ($this->token == 'ident' || $this->isFunc() || $this->token == '*' || $this->token == '(' || $this->isVal()) {
       		while ($this->token != 'from') {
            	//$this->getTok();
            	//$nextTok = $this->token;	// get next token to see if it is an open parenthesis
            	//$this->lexer->pushBack();
            	/*
            	if ($this->isVal() ){
            		$val = $this->tokText;
            		$type = $this->token;
            		$this->getTok();
            		if ($this->token == 'as') {
                        $this->getTok();
                        if ($this->token == 'ident' ) {
                            $columnAlias = $this->tokText;
                        } else {
                            return $this->raiseError('Expected column alias');
                        }
                    } elseif ($this->token == 'ident') {
                        $columnAlias = $this->tokText;
                    } else {
                        $columnAlias = '';
                    }
                    
                    $coldef = array('type'=>$type , 'value'=>$val, 'alias'=>$columnAlias);
                    if ( $this->isExpressionOperator() ){
                    	$coldef = $this->parseExpression($coldef);
                    	if ( PEAR::isError($colddef) ) return $colddef;
                    }
                    $tree['columns'][] = $coldef;
            		
            	
            	}
            	
            	else */if ($this->token == 'ident' || $this->token == '*' || $this->isVal() ) {
            		// The current token is an identifier.
            		//  We needed to include checks for '*' explicitly because the lexer recognizes it as a reserved word.
            		//  We need to check for open parenthesis because function names without open parenthesis
            		//  are considered to be identifiers - and hence fall into this category as well.
            		
                    $prevTok = $this->token;
                    $prevTokText = $this->tokText;
                    $this->getTok();
                    
                    
                    $columnTable = ''; // temp default value for column table until we get it sorted out.

                    if ($prevTok == 'ident') {
                    	$colType = 'ident';
                    	
                        $columnName = $prevTokText;
                        if ( $columnName{strlen($columnName)-1} == '.') {
                        	$columnName .= '*';
                        	$colType = 'glob';
                        	$this->getTok();
                        }
                        if ( strpos($columnName, '.') !== false ){
                        	// In introduce $columnTable2 and $columnName2 to be used in
                        	// the alternate 'columns' data structure.
                        	list($columnTable2, $columnName2) = explode('.',$columnName);
                        } else {
                        	$columnTable2 = '';
                        	$columnName2 = $columnName;
                        }
                    
                    } else if ($prevTok == '*' ){
                    	$colType = 'glob';
                    	$columnName = $prevTok;
                    	$columnName2 = $columnName;
                    	$columnTable2 = '';
                    	
                    } else if ( $this->isVal($prevTok) ){
                    	$colType = $prevTok;
                    	$columnName = $prevTokText;
                    	$columnName2 = $columnName;
                    	$columnTable2 = '';
                    
                    } else {
                        return $this->raiseError('Expected column name');
                    }
                    
                    
                    // This part added to try to be able to handle 
                    // arithmetic expressions.
                    $coldef = null;
                    if ( $this->isExpressionOperator() ){
                    	// If the next token is an expression operator - we need
                    	// to parse the expression.
                    	$arg = array('type'=>$colType, 'table'=>$columnTable2, 'value'=>$columnName2, 'alias'=>'');
                    	$coldef = $this->parseExpression($arg);
                    	if ( PEAR::isError($coldef) ){
                    		return $coldef;
                    	}
                    	/*
                    	$colType = 'expression';
                    	$columnName2 = $arg;
                    	$columnTable2 = '';
                    	$columnName = '';
                    	$columnTable = '';
                    	*/
                    	
                    	
                    	
                    }
                    

					if ($this->token == 'as') {
                        $this->getTok();
                        if ($this->token == 'ident' ) {
                            $columnAlias = $this->tokText;
                        } else {
                            return $this->raiseError('Expected column alias');
                        }
                    } elseif ($this->token == 'ident') {
                        $columnAlias = $this->tokText;
                        
                    }  else {
                        $columnAlias = '';
                    }
                    
					if ( !$coldef) $coldef = array('type'=>$colType, 'table'=>$columnTable2, 'value'=>$columnName2, 'alias'=>$columnAlias);
					$coldef['alias'] = $columnAlias;
					if ($this->isExpressionOperator() ){
						$coldef = $this->parseExpression($coldef);
						if ( PEAR::isError($coldef) ) return $coldef;
					}
					$tree['columns'][] = $coldef;
						// add alternate data structure to keep track of columns in a more consistent manner
						// added by Steve Hannah (shannah@sfu.ca) May 29, 2006 for Dataface
                    $tree['column_tables'][] = $columnTable;
                    $tree['column_names'][] = $columnName;
                    $tree['column_aliases'][] = $columnAlias;
                    if ($this->token != 'from') {
                        $this->getTok();
                    }
                    if ($this->token == ',') {
                        $this->getTok();
                    }
                } elseif ($this->isFunc()) {
                    $result = $this->parseFunctionOpts();
					if (PEAR::isError($result)) {
						return $result;
					}
					$tree['set_function'][] = $result;
					$this->getTok();

					if ($this->token == 'as') {
						$this->getTok();
						if ($this->token == 'ident' ) {
							$columnAlias = $this->tokText;
						} else {
							return $this->raiseError('Expected column alias');
						}
					} else if ( isset( $result['alias'] ) ){
						$columnAlias = $result['alias'];
					} else {
						$columnAlias = '';
					}
					$coldef = array('type'=>'func', 'table'=>'', 'value'=>$result, 'alias'=> $columnAlias);
					if ( $this->isExpressionOperator() ){
						$coldef = $this->parseExpression($coldef);
						if ( PEAR::isError($coldef) ){
							return $coldef;
						}
					}
					$tree['columns'][] = $coldef;
                   
                } else if ( $this->token == '(' ){
                	$this->getTok();
                	if ( $this->token == 'select' ){
                		$result = $this->parseSelect(true);
                		if ( PEAR::isError($result) ) return $result;
                		//$this->getTok();
                		$this->getTok();
                		if ( $this->token == 'as' ) $this->getTok();
                		$columnAlias = '';
                		if ( $this->token == 'ident' ){
                			$columnAlias = $this->tokText;
                			$this->getTok();
                		}
                		if ( $this->token == ',' ) $this->getTok();
                		//else return $this->raiseError("Unexpected token '".$this->token.'"');
                		//else $this->lexer->pushBack();
                		
                		$tree['columns'][] = array('type'=>'subselect', 'table'=>'', 'value'=>$result, 'alias'=>$columnAlias);
                			
                		
                	} else {
                		//$this->getTok();
                		$coldef = $this->parseExpression();
                		if ( PEAR::isError($coldef) ) return $coldef;
                		$this->getTok();
                		if ( $this->isExpressionOperator() ){
                			$coldef = $this->parseExpression($coldef);
                			if ( PEAR::isError($coldef) ) return $coldef;
                		}
                		if ( $this->token == 'as' ) $this->getTok();
                		$columnAlias = '';
                		if ( $this->token == 'ident' ){
                			$columnAlias = $this->tokText;
                			$this->getTok();
                		}
                		if ( $this->token == ',' ) $this->getTok();
                		$coldef['alias'] = $columnAlias;
                		
                		$tree['columns'][] = $coldef;
                		//return $this->raiseError("Only supports subquery expressions in field list");
                	}
                } elseif ($this->token == ',') {
                    $this->getTok();
                } else {
                        return $this->raiseError('Unexpected token "'.$this->token.'"');
                }
            }
        } else {
            return $this->raiseError('Expected columns or a set function');
        }
        if ($this->token != 'from') {
            return $this->raiseError('Expected "from"');
        }
        $this->getTok();
        while ($this->token == 'ident' or $this->token == '(') {
        	if ( $this->token == 'ident' ){
        		$tableType = 'ident';
        		$this->all_tables[] = $tree['table_names'][] = $tableName = $this->tokText;
        	} else {
        		 //must be a subselect.
				$this->getTok();
				if ( $this->token != 'select' ){
					return $this->raiseError('Expected "select" on line '.__LINE__.' of file '.__FILE__);
				}
				$tableType = 'subselect';
				$tableName = $this->parseSelect(true);
				
				
			}
			$this->getTok();
			if ($this->token == 'ident') {
				$tree['table_aliases'][] = $tableAlias = $this->tokText;
				$this->getTok();
			} elseif ($this->token == 'as') {
				$this->getTok();
				if ($this->token == 'ident') {
					$tree['table_aliases'][] = $tableAlias = $this->tokText;
				} else {
					return $this->raiseError('Expected table alias');
				}
				$this->getTok();
			} else {
				$tree['table_aliases'][] = $tableAlias = '';
			}
			$tree['tables'][] = array('type'=>$tableType, 'value'=>$tableName, 'alias'=>$tableAlias);
		
            
            if ($this->token == 'on') {
                $clause = $this->parseSearchClause();
                if (PEAR::isError($clause)) {
                    return $clause;
                }
                $tree['table_join_clause'][] = $clause;
            } else {
                $tree['table_join_clause'][] = '';
            }
            
            if ($this->token == ',') {
                $tree['table_join'][] = ',';
                $this->getTok();
            } elseif ($this->token == 'join') {
                $tree['table_join'][] = 'join';
                $this->getTok();
            } elseif (($this->token == 'cross') ||
                        ($this->token == 'inner')) {
                $join = $this->tokText;
                $this->getTok();
                if ($this->token != 'join') {
                    return $this->raiseError('Expected token "join"');
                }
                $tree['table_join'][] = $join.' join';
                $this->getTok();
            } elseif (($this->token == 'left') ||
                        ($this->token == 'right')) {
                $join = $this->tokText;
                $this->getTok();
                if ($this->token == 'join') {
                    $tree['table_join'][] = $join.' join';
                } elseif ($this->token == 'outer') {
                        $join .= ' outer';
                    $this->getTok();
                    if ($this->token == 'join') {
                        $tree['table_join'][] = $join.' join';
                    } else {
                        return $this->raiseError('Expected token "join"');
                    }
                } else {
                    return $this->raiseError('Expected token "outer" or "join"');
                }
                $this->getTok();
            } elseif ($this->token == 'natural') {
                $join = $this->tokText;
                $this->getTok();
                if ($this->token == 'join') {
                    $tree['table_join'][] = $join.' join';
                } elseif (($this->token == 'left') ||
                            ($this->token == 'right')) {
                        $join .= ' '.$this->token;
                    $this->getTok();
                    if ($this->token == 'join') {
                        $tree['table_join'][] = $join.' join';
                    } elseif ($this->token == 'outer') {
                        $join .= ' '.$this->token;
                        $this->getTok();
                        if ($this->token == 'join') {
                            $tree['table_join'][] = $join.' join';
                        } else {
                            return $this->raiseError('Expected token "join" or "outer"');
                        }
                    } else {
                        return $this->raiseError('Expected token "join" or "outer"');
                    }
                } else {
                    return $this->raiseError('Expected token "left", "right" or "join"');
                }
                $this->getTok();
            } elseif (($this->token == 'where') ||
                        ($this->token == 'order') ||
                        ($this->token == 'limit') ||
                        (is_null($this->token))) {
                break;
            }
        }
        while (!is_null($this->token) && (!$subSelect || $this->token != ')')
               && $this->token != ')') {
            switch ($this->token) {
                case 'where':
                    $clause = $this->parseSearchClause();
                    if (PEAR::isError($clause)) {
                        return $clause;
                    }
                    $tree['where_clause'] = $clause;
                    break;
                case 'having':
                	$clause = $this->parseSearchClause();
                	if ( PEAR::isError($clause) ){
                		return $clause;
                	}
                	$tree['having_clause'] = $clause;
                	break;
                case 'order':
                    $this->getTok();
                    if ($this->token != 'by') {
                        return $this->raiseError('Expected "by"');
                    }
                    $this->getTok();
                    while ($this->token == 'ident' or $this->isFunc()) {
                        //$col = $this->tokText;
                        $col = array();
                        if ( $this->token == 'ident' ){
                        	$col['value'] = $this->tokText;
                        	$col['type'] = 'ident';
                        } else if ( $this->isFunc() ){
                        	$col['value'] = $this->parseFunctionOpts();
                        	if ( PEAR::isError($col['value'] ) ){
                        		return $col['value'];
                        	}
                        	$col['type'] = 'function';
                        }
                        $this->getTok();
                        if (isset($this->synonyms[$this->token])) {
                            $col['order'] = $this->synonyms[$this->token];
                            if (($col['order'] != 'asc') && ($col['order'] != 'desc')) {
                                return $this->raiseError('Unexpected token');
                            }
                            $this->getTok();
                        } else {
                            $col['order'] = 'asc';
                        }
                        if ($this->token == ',') {
                            $this->getTok();
                        }
                        $tree['sort_order'][] = $col;
                    }
                    break;
                case 'limit':
                    
                    $tree['limit_clause'] = $this->parseLimit();
                  
                    break;
                case 'group':
                    $this->getTok();
                    if ($this->token != 'by') {
                        return $this->raiseError('Expected "by"');
                    }
                    $this->getTok();
                    while ($this->token == 'ident' or $this->isFunc() ) {
                        $col = array();
                        if ( $this->token == 'ident' ){
                        	$col['value'] = $this->tokText;
                        	$col['type'] = 'ident';
                        } else if ( $this->isFunc() ){
                        	$col['value'] = $this->parseFunctionOpts();
                        	if ( PEAR::isError($col['value'] ) ){
                        		return $col['value'];
                        	}
                        	$col['type'] = 'function';
                        }
                        //$col = $this->tokText;
                        //$this->getTok();
                        //if ($this->token == ',') {
                        //    $this->getTok();
                        //}
                        $this->getTok();
                        if ( $this->token == ',') $this->getTok();
                        $tree['group_by'][] = $col;
                    }
                    break;
                
                case ';':
                	return $tree;
                	
                default:
                    return $this->raiseError('Unexpected clause');
            }
        }
        return $tree;
    }
    // }}}

    // {{{ parse($string)
    function parse($string = null)
    {
        if (is_string($string)) {
            // Initialize the Lexer with a 3-level look-back buffer
            $this->lexer = new Lexer($string, 3);
            $this->lexer->symbols =& $this->symbols;
        } else {
            if (!is_object($this->lexer)) {
                return $this->raiseError('No initial string specified');
            }
        }

        // get query action
        $this->getTok();
        switch ($this->token) {
            case null:
                // null == end of string
                return $this->raiseError('Nothing to do');
            case 'select':
            	$this->all_tables=array();
                $ret = $this->parseSelect();
                if ( PEAR::isError($ret) ){
                	trigger_error('Failed parsing SQL query on select: '.$string.' . The Error was '.$ret->getMessage(), E_USER_ERROR);
                }	
                $ret['all_tables'] = $this->all_tables;
                return $ret;
            case 'update':
                return $this->parseUpdate();
            case 'insert':
                return $this->parseInsert();
            case 'delete':
                return $this->parseDelete();
            case 'create':
                return $this->parseCreate();
            case 'drop':
                return $this->parseDrop();
            default:
                return $this->raiseError('Unknown action :'.$this->token);
        }
    }
    // }}}
}
?>
