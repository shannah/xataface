<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Copyright (c) 2003-2004 John Griffin                                 |
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
// | Authors: John Griffin <jgriffin316@netscape.net>                     |
// +----------------------------------------------------------------------+
//
// $Id: Compiler.php,v 1.7 2006/05/05 00:13:45 sjhannah Exp $
//

require_once 'PEAR.php';

/**
 * A SQL parse tree compiler.
 *
 * @author  John Griffin <jgriffin316@netscape.net>
 * @version 0.1
 * @access  public
 * @package SQL_Parser
 */
class SQL_Compiler {
    var $tree;
    var $version = 1;
    var $type;
    	// A flag to keep track of the version of this object.  A version of greater
    	// than 1 would indicate that this should use the new 'columns' and 'tables'
    	// arrays instead of the 'column_names', 'column_tables', etc.. arrays.

// {{{ function SQL_Compiler($array = null)
    function SQL_Compiler($array = null)
    {
        $this->tree = $array;
    }
// }}}

	// {{{ function compileFunction
	function compileFunction($arg, $writeAlias = true){
		if ( !isset( $arg['name']) ){
			$err = PEAR::raiseError("Expected function name.");
			trigger_error($err->toString(), E_USER_ERROR);
		}
		$out = $arg['name'].'('.$this->compileFunctionOpts($arg['args']).")";
		
		
		if ( isset($arg['alias']) and $writeAlias){
			$out .= " as ".$this->compileIdent($arg['alias']);
		}
		return $out;
	
	}
	
	function compileFunctionOpts($args){
		$found = false;
		$out = '';
		foreach ($args as $func_arg){
			$found = true;
			if ( isset($func_arg['quantifier']) ){
				$out .= $func_arg['quantifier'].' ';
			}
			if ( $func_arg['type'] == 'interval' ){
				$out .= 'interval '.$this->compileExpression($func_arg['expression_type'], $func_arg['value']).' '.$func_arg['unit'].', ';
			} else if ( $func_arg['type']  == 'ident' ){
				$out .= $this->compileIdent($func_arg['value']).', ';
			}
			else if ( in_array($func_arg['type'], array('int_val','real_val') )  ){
				$out .= $func_arg['value'].', ';
				
			} else if ( $func_arg['type'] == 'function'){
				$out .= $this->compileFunction($func_arg['value'], false).', ';
			} else if ( $func_arg['type'] == 'text_val' ){
				$out .= "'".$func_arg['value']."', ";
			} else if ( $func_arg['type'] == 'expression' ){
			
				$out .= $this->compileExpression($func_arg['type'], $func_arg['value']).", ";
			} else {
				$out .= $func_arg['value'].', ';
			}
		}
		if ( $found ){
			$out = substr($out, 0, strlen($out)-2);
		}
		return $out;
	}
	
	// }}}
	/**
	 * Compiles an identifier.  Different dialects may allow identifiers to be enclosed in quotes or back-ticks.
	 * For example, MySQL allows identifiers to be enclosed in back-ticks.  This will eliminate conflicts where
	 * table or column names are the same as function names, etc...
	 *
	 * @param $value The value of the identifier.
	 * @type string
	 * 
	 * @return String Modified identifier.
	 */
	function compileIdent($value){
		return $value;
	
	}

//    {{{ function getWhereValue ($arg)
    function getWhereValue ($arg)
    {
        switch ($arg['type']) {
            case 'ident':
            	$value = $this->compileIdent($arg['value']);
            	break;
            case 'real_val':
            case 'int_val':
                $value = $arg['value'];
                break;
            case 'text_val':
                $value = '\''.$arg['value'].'\'';
                break;
            case 'subclause':
                $value = '('.$this->compileSearchClause($arg['value']).')';
                if ( PEAR::isError($value) ){
                	return $value;
                }
                break;
            case 'subquery':
            	$subCompiler =& $this->newInstance($this->type);
				$subCompiler->version = $this->version;
				
				$value = $subCompiler->compile($arg['value']);
				if ( PEAR::isError($value) ) return $value;
				$value = '('.$value.')';
				unset($subCompiler);
				//if ( $this->tree['columns'][$i]['alias'] ){
				//	$column .= ' as '. $this->compileIdent($this->tree['columns'][$i]['alias']);
				//}
				break;
            case 'function':
            	$value = $this->compileFunction($arg['value'], false);
            	if ( PEAR::isError($value) ){
            		return $value;
            	}
            	break;
            case 'command':
            	eval('$compiler = new '.get_class($this).'();');
            	$compiler->version = $this->version;
            	$value = $compiler->compileSelect($arg['value']);
            	if ( PEAR::isError($value) ){
            		return $value;
            	}
            	break;
            	
            case 'match':
            	$value = 'match ('.$this->compileFunctionOpts($arg['value']).')';
            	$value .= ' against (\''.$arg['against'].'\'';
            	if ( isset($arg['boolean_mode']) and $arg['boolean_mode'] ){
            		$value .= ' in boolean mode';
            	}
            	$value .= ')';
            	break;
            	
            case 'against':
            	$value = 'against ('.$this->compileFunctionOpts($arg['value']).')';
            	break;
            default:
                return PEAR::raiseError('Unknown type: '.$arg['type']);
        }
        return $value;
    }
//    }}}

//    {{{ function getParams($arg)
    function getParams($arg)
    {
    	//echo "In Params: "; print_r($arg);
    	if ( is_array($arg['type'])){
    	
			for ($i = 0; $i < sizeof ($arg['type']); $i++) {
				switch ($arg['type'][$i]) {
					case 'ident':
						$value[] = $this->compileIdent($arg['value'][$i]);
						break;
					case 'real_val':
					case 'int_val':
						$value[] = $arg['value'][$i];
						break;
					case 'text_val':
						$value[] = '\''.$arg['value'][$i].'\'';
						break;
					case 'function':
						$val = $this->compileFunction($arg['value'][$i]);
						if ( PEAR::isError($val) ){
							return $val;
						}
						$value[] = $val;
					default:
						return PEAR::raiseError('Unknown type: '.$arg['type'][$i]);
				}
			}
		} else {
			// type is not an array -- the only type that I can think that it would
			// be is a command.
			if ( $arg['type'] == 'command' ){
				//echo "here";
				eval('$compiler = new '.get_class($this).'();');
				$compiler->version = $this->version;
				$val = $compiler->compile($arg['value']);
				if ( PEAR::isError($val) ){
					return $val;
				}
				$value[] = $val;
			
			} else {
				return PEAR::raiseError('Unknown type: '.$arg['type']);
			}
			
		}
        $value ='('.implode(', ', $value).')';
        return $value;
    }
//    }}}

//    {{{ function compileSearchClause
    function compileSearchClause($where_clause)
    {
        $value = '';
        if (isset ($where_clause['arg_1']['value'])) {
            $value = $this->getWhereValue ($where_clause['arg_1']);
            if (PEAR::isError($value)) {
                return $value;
            }
            $parts = array();
            if ( @$where_clause['neg'] and !isset($where_clause['arg_2']['value']) ) $parts[] = 'not';
            if ( @$where_clause['exists'] ) $parts[] = 'exists';
            $parts[] = trim($value);
            
            $sql = implode(' ', $parts);
        } else {
            $value = $this->compileSearchClause($where_clause['arg_1']);
            if (PEAR::isError($value)) {
                return $value;
            }
            if ( @$where_clause['neg'] and !isset($where_clause['arg_2']['value']) ) $parts[] = 'not';
            if ( @$where_clause['exists'] ) $parts[] = 'exists';
            $parts[] = trim($value);
            $sql = implode(' ', $parts);
        }
        if (isset ($where_clause['op'])) {
            if ($where_clause['op'] == 'in') {
            	
                $value = $this->getParams($where_clause['arg_2']);
                if (PEAR::isError($value)) {
                    return $value;
                }
                $value = ' '.$where_clause['op'].' '.$value;
                if ( isset($where_clause['neg']) ){
            		$value = ' not'.$value;
            	}
            	$sql .= $value;
            } elseif ($where_clause['op'] == 'is') {
                if (isset ($where_clause['neg'])) {
                    $value = 'not null';
                } else {
                    $value = 'null';
                }
                $sql .= ' is '.$value;
            } else {
                $sql .= (@$where_clause['neg']?' not':'').' '.$where_clause['op'].' ';
                if (isset ($where_clause['arg_2']['value'])) {
                    $value = $this->getWhereValue ($where_clause['arg_2']);
                    if (PEAR::isError($value)) {
                        return $value;
                    }
                    $sql .= $value;
                } else {
                    $value = $this->compileSearchClause($where_clause['arg_2']);
                    if (PEAR::isError($value)) {
                        return $value;
                    }
                    $sql .= $value;
                }
            }
        }
        return $sql;
    }
//    }}}

//    {{{ function compileSelect()

	

    function compileSelect()
    {
        // save the command and set quantifiers
        $sql = 'select ';
        if (isset($this->tree['set_quantifier'])) {
            $sql .= $this->tree['set_quantifier'].' ';
        }
        
        if ( $this->version <= 1 ){
        	// This object uses legacy settings with the old data structures.
			// save the column names and set functions
			for ($i = 0; $i < sizeof ($this->tree['column_names']); $i++) {
				$column = $this->compileIdent($this->tree['column_names'][$i]);
				// MOD START 051026 shannah@sfu.ca - Fix to get rid of Notice: Undefined index: column_aliases
				//if ($this->tree['column_aliases'][$i] != '') {
				if (isset( $this->tree['column_aliases'] ) and $this->tree['column_aliases'][$i] != '') {
				// MOD END 051026 shannah@sfu.ca
					$column .= ' as '.$this->compileIdent($this->tree['column_aliases'][$i]);
				}
				$column_names[] = $column;
			}
			// MOD ADD START PART 1 of 2 - 051026 shannah@sfu.ca - Fix to get rid of "Notice: Undefined index: set_function"
			if ( isset( $this->tree['set_function'] ) ){
			// MOD ADD END PART 1 of 2
				for ($i = 0; $i < sizeof ($this->tree['set_function']); $i++) {
					$column_names[] = $this->compileFunction($this->tree['set_function'][$i]);
					
				}
			// MOD ADD START PART 2 of 2 - 051026 shannah@sfu.ca
			}
			// MOD ADD END PART 2 or 2
			
		} else {
			// This object uses the new settings with new data structures.
			for ($i = 0; $i < sizeof ($this->tree['columns']); $i++) {
				switch ( $this->tree['columns'][$i]['type'] ){
					case 'ident':
						if ( $this->tree['columns'][$i]['table'] ){
							$column = $this->compileIdent($this->tree['columns'][$i]['table']).'.'.
										$this->compileIdent($this->tree['columns'][$i]['value']);
						} else {
							$column = $this->compileIdent($this->tree['columns'][$i]['value']);
						}
						
						if ( $this->tree['columns'][$i]['alias'] ){
							$column .= ' as '. $this->compileIdent($this->tree['columns'][$i]['alias']);
						}
						break;
						
					case 'func':
						$column = $this->compileFunction($this->tree['columns'][$i]['value']);
						break;
						
					case 'compiled_func':
						$column = $this->tree['columns'][$i]['value'];
						if ( $this->tree['columns'][$i]['alias'] ){
							$column .= ' as '. $this->compileIdent($this->tree['columns'][$i]['alias']);
						}
						break;
						
					case 'glob':
						if ( $this->tree['columns'][$i]['table'] ){
							$column = $this->compileIdent($this->tree['columns'][$i]['table']).'.'.
										$this->tree['columns'][$i]['value'];
						} else {
							$column = $this->tree['columns'][$i]['value'];
						}
					
						break;
						
					case 'subselect':
						$subCompiler =& $this->newInstance($this->type);
						$subCompiler->version = $this->version;
						
						$column = '('.$subCompiler->compile($this->tree['columns'][$i]['value']).')';
						unset($subCompiler);
						if ( $this->tree['columns'][$i]['alias'] ){
							$column .= ' as '. $this->compileIdent($this->tree['columns'][$i]['alias']);
						}
						break;
						
					case 'expression':
						$column = $this->compileExpression('expression', $this->tree['columns'][$i]['value']);
						if ( @$this->tree['columns'][$i]['alias'] ){
							$column .= ' as '. $this->compileIdent($this->tree['columns'][$i]['alias']);
						}
						
						break;
					
					// This section added to try handle literals in the select list
					case 'real_val':
					case 'int_val':
					case 'null':
						$column = $this->tree['columns'][$i]['value'];
						
						
						if ( $this->tree['columns'][$i]['alias'] ){
							$column .= ' as '. $this->compileIdent($this->tree['columns'][$i]['alias']);
						}
						break;
						
					case 'text_val':
						$column = '\''.$this->tree['columns'][$i]['value'].'\'';
						
						
						if ( $this->tree['columns'][$i]['alias'] ){
							$column .= ' as '. $this->compileIdent($this->tree['columns'][$i]['alias']);
						}
						break;
						
					default:
						return PEAR::raiseError("Unexpected column type '".$this->tree['columns'][$i]['type']);
						
						
				}
				$column_names[] = $column;
			}
		}
		if (isset($column_names)) {
			$sql .= implode (", ", $column_names);
		}
			
        
        // save the tables
        $sql .= ' from ';
        if ( $this->version <= 1 ){
        
			for ($i = 0; $i < sizeof ($this->tree['table_names']); $i++) {
				$sql .= $this->compileIdent($this->tree['table_names'][$i]);
				if ($this->tree['table_aliases'][$i] != '') {
					$sql .= ' as '.$this->compileIdent($this->tree['table_aliases'][$i]);
				}
				if ($this->tree['table_join_clause'][$i] != '') {
					$search_string = $this->compileSearchClause ($this->tree['table_join_clause'][$i]);
					if (PEAR::isError($search_string)) {
						return $search_string;
					}
					$sql .= ' on '.$search_string;
				}
				if (isset($this->tree['table_join'][$i])) {
					if ( $this->tree['table_join'][$i] != ',' ){
						$sql .= ' ';
					}
					$sql .= $this->tree['table_join'][$i].' ';
				}
			}
		} else {
			// This object is working with the new version of the data structure
			// that supports subselects.  i.e. it uses the 'tables' array instead
			// of 'table_names' and 'table_aliases'.
			for ($i = 0; $i < sizeof ($this->tree['tables']); $i++) {
				switch( $this->tree['tables'][$i]['type'] ){
					case 'ident':
						$sql .= $this->compileIdent($this->tree['tables'][$i]['value']);
						break;
						
					case 'subselect':
						$compiler = SQL_Compiler::newInstance($this->type);
						$compiler->version = $this->version;
						$temp = $compiler->compile($this->tree['tables'][$i]['value']);
						if ( PEAR::isError($temp) ){
							return $temp;
						}
						$sql .= '('.$temp.')';
						break;
					
					case 'compiled_subselect':
						$sql .= '('.$this->tree['tables'][$i]['value'].')';
						break;
						
					default:
						return $this->raiseError("Unexpected type ".$this->tree['tables'][$i]['type']." on line ".__LINE__." of file ".__FILE__);
					
				}
				
				if ($this->tree['tables'][$i]['alias'] != '') {
					$sql .= ' as '.$this->compileIdent($this->tree['tables'][$i]['alias']);
				}
				if ($this->tree['table_join_clause'][$i] != '') {
					$search_string = $this->compileSearchClause ($this->tree['table_join_clause'][$i]);
					if (PEAR::isError($search_string)) {
						return $search_string;
					}
					$sql .= ' on '.$search_string;
				}
				if (isset($this->tree['table_join'][$i])) {
					if ( $this->tree['table_join'][$i] != ',' ){
						$sql .= ' ';
					}
					$sql .= $this->tree['table_join'][$i].' ';
				}
			}
		}
        
        // save the where clause
        if (isset($this->tree['where_clause'])) {
            $search_string = $this->compileSearchClause ($this->tree['where_clause']);
            if (PEAR::isError($search_string)) {
                return $search_string;
            }
            $sql .= ' where '.$search_string;
        }
        
        
        // save the group by clause
        if (isset ($this->tree['group_by'])) {
        	$group_by = array();
        	foreach( $this->tree['group_by'] as $col ){
        		switch ($col['type']){
        			case 'ident': 
        				$group_by[] = $this->compileIdent($col['value']);
        				break;
        			case 'function':
        				$group_by[] = $this->compileFunction($col['value']);
        				break;
        			default:
        				return $this->raiseError("Unexpected type: ".$col['type']." in group by clause");
        		}
        	}
        	$sql .= ' group by '.implode(', ', $group_by);
        }
        
        // save the having clause
        if (isset($this->tree['having_clause'])) {
            $search_string = $this->compileSearchClause ($this->tree['having_clause']);
            if (PEAR::isError($search_string)) {
                return $search_string;
            }
            $sql .= ' having '.$search_string;
        }

        // save the order by clause
        if (isset ($this->tree['sort_order'])) {
            foreach ($this->tree['sort_order'] as $key=>$value) {
            	if ( $value['type'] == 'ident'){
                	$sort_order[] = $this->compileIdent($value['value']).' '.$value['order'];
                } else if ( $value['type'] == 'function' ){
                	$sort_order[] = $this->compileFunction($value['value'], false).' '.$value['order'];
                }
            }
            $sql .= ' order by '.implode(', ', $sort_order);
        }
        
        // save the limit clause
        if (isset ($this->tree['limit_clause'])) {
           // $sql .= ' limit '.(isset($this->tree['limit_clause']['start']) && $this->tree['limit_clause']['start'] ? $this->tree['limit_clause']['start'].',' : '').$this->tree['limit_clause']['length'];
        	$start = ($this->tree['limit_clause']['start'] ? $this->tree['limit_clause']['start'] : '0');
        	$sql .= ' limit '.($start ? $start.',' : '').$this->tree['limit_clause']['length'];
        }
        
        return $sql;
    }
//    }}}

//    {{{ function compileUpdate()
    function compileUpdate()
    {
    	$table_names = array_map(array(&$this, 'compileIdent'), $this->tree['table_names']);
    	
        $sql = 'update '.implode(', ', $table_names);
        
        // save the set clause
        for ($i = 0; $i < sizeof ($this->tree['column_names']); $i++) {
            $set_columns[] = $this->compileIdent($this->tree['column_names'][$i]).' = '.$this->getWhereValue($this->tree['values'][$i]);
        }
        $sql .= ' set '.implode (', ', $set_columns);
        
        // save the where clause
        if (isset($this->tree['where_clause'])) {
            $search_string = $this->compileSearchClause ($this->tree['where_clause']);
            if (PEAR::isError($search_string)) {
                return $search_string;
            }
            $sql .= ' where '.$search_string;
        }
        
        if ( isset($this->tree['limit_clause']) ){
        	$sql .= ' limit '.$this->tree['limit_clause']['length'];
        }	
        return $sql;
    }
//    }}}

//    {{{ function compileDelete()
    function compileDelete()
    {
    	$table_names = array_map(array(&$this, 'compileIdent'), $this->tree['table_names']);
        $sql = 'delete from '.implode(', ', $table_names);
        
        // save the where clause
        if (isset($this->tree['where_clause'])) {
            $search_string = $this->compileSearchClause ($this->tree['where_clause']);
            if (PEAR::isError($search_string)) {
                return $search_string;
            }
            $sql .= ' where '.$search_string;
        }
        return $sql;
    }
//    }}}

//    {{{ function compileInsert()
    function compileInsert()
    {
    	$column_names = array_map(array(&$this, 'compileIdent'), $this->tree['column_names']);
        $sql = 'insert into '.$this->compileIdent($this->tree['table_names'][0]).' ('.
                implode(', ', $column_names).') values (';
        for ($i = 0; $i < sizeof ($this->tree['values']); $i++) {
            $value = $this->getWhereValue ($this->tree['values'][$i]);
            if (PEAR::isError($value)) {
                return $value;
            }
            $value_array[] = $value;
        }
        $sql .= implode(', ', $value_array).')';
        return $sql;
    }
    
    function compileExpression($type, $val){
    	switch ($type){
    		case 'int_val':
    		case 'real_val':
    		case 'null':
    			return $val;
    		case 'text_val';
    			return "'".$val."'";
    		case 'ident':
    			return $this->compileIdent($val);
    		case 'function':
    		case 'func':
    			return $this->compileFunction($val);
    		case 'expression':
    			$out = '';
    			foreach ( $val as $part ){
    				$out .= $this->compileExpression($part['type'], $part['value']);
    			}
    			return '('.$out.')';
    		case 'operator':
    			return $val;
    		
    	}
    }
//    }}}

//    {{{ function compile($array = null)
    function compile($array = null)
    {
        $this->tree = $array;
        
        switch ($this->tree['command']) {
            case 'select':
                return $this->compileSelect();
                break;
            case 'update':
                return $this->compileUpdate();
                break;
            case 'delete':
                return $this->compileDelete();
                break;
            case 'insert':
                return $this->compileInsert();
                break;
            case 'create':
            case 'drop':
            case 'modify':
            default:
                return PEAR::raiseError('Unknown action: '.$this->tree['command']);
        }    // switch ($this->_tree["command"])

    }
//    }}}

//	{{{ function newInstance
	/**
	 * Creates a new SQL_Compiler instance of the appropriate type.
	 * Compiler classes are to be located in the SQL/Compiler directory and 
	 * simply named '<type>.php' where '<type>' can be replaced by the type
	 * of compiler in lower case letters.
	 */
	public static function &newInstance($type=null){
		if ( $type === null ){
			return new SQL_Compiler();
		} else {
			// we are requesting a particular type of compiler.
			$type = strtolower($type);
			if ( !preg_match('/^[0-9a-z_]+$/', $type) ){
				return PEAR::raiseError('Invalid type name specified: '.$type);
			}
			$class_name = 'SQL_Compiler_'.$type;
			//echo "Class name: ".$class_name;
			if ( !class_exists($class_name) ){
				if ( file_exists( dirname(__FILE__).'/Compiler/'.$type.'.php') ){
					//echo "File exists";
					require_once 'SQL/Compiler/'.$type.'.php';
				}
			}
			if ( !class_exists($class_name) ){
				 return PEAR::raiseError('Could not create compiler of type '.$type.' because no corresponding class exists in the SQL/Compiler directory');
			}
			
			$out = new $class_name();
			return $out;
			
		}
	}

}
?>
