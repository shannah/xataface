<?php
require_once 'BaseTest.php';
require_once 'SQL/Parser.php';
require_once 'SQL/Compiler.php';
require_once 'SQL/Parser/wrapper.php';


class Parser_wrapperTest extends BaseTest {

	var $tests;
	
	
	function Parser_wrapperTest($name = 'Parser_wrapperTest'){
		$this->BaseTest($name);
		//parent::BaseTest();
		
$this->tests = array(
array(
'sql' => 'select * from dog where cat <> 4',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'table_names' => array(
            0 => 'dog'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'cat',
                'type' => 'ident'
                ),
            'op' => '<>',
            'arg_2' => array(
                'value' => 4,
                'type' => 'int_val'
                )
            )
        )
),
array(
'sql' => 'select legs, hairy from dog',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'legs',
            1 => 'hairy'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
        'table_names' => array(
            0 => 'dog'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            )
        )
),
array(
'sql' => 'select max(length) from dog',
'expect' => array(
        'command' => 'select',
        'set_function' => array(
            0 => array(
                'name' => 'max',
                'arg' => 'length'
                )
            ),
        'table_names' => array(
            0 => 'dog'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            )
        )
),
array(
'sql' => 'select count(distinct country) from publishers',
'expect' => array(
        'command' => 'select',
        'set_function' => array(
            0 => array(
                'name' => 'count',
                'distinct' => true,
                'arg' => array(
                    0 => 'country'
                    )
                )
            ),
        'table_names' => array(
            0 => 'publishers'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            )
        )
),
array(
'sql' => 'select one, two from hairy where two <> 4 and one = 2',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'one',
            1 => 'two'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
        'table_names' => array(
            0 => 'hairy'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'two',
                    'type' => 'ident'
                    ),
                'op' => '<>',
                'arg_2' => array(
                    'value' => 4,
                    'type' => 'int_val'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => 'one',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 2,
                    'type' => 'int_val'
                    )
                )
            )
        )
),
array(
'sql' => 'select one, two from hairy where two <> 4 and one = 2 order by two',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'one',
            1 => 'two'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
        'table_names' => array(
            0 => 'hairy'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'two',
                    'type' => 'ident'
                    ),
                'op' => '<>',
                'arg_2' => array(
                    'value' => 4,
                    'type' => 'int_val'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => 'one',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 2,
                    'type' => 'int_val'
                    )
                )
            ),
        'sort_order' => array(
            'two' => 'asc'
            )
        )
),
array(
'sql' => 'select one, two from hairy where two <> 4 and one = 2 limit 4 order by two ascending, dog descending',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'one',
            1 => 'two'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
        'table_names' => array(
            0 => 'hairy'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'two',
                    'type' => 'ident'
                    ),
                'op' => '<>',
                'arg_2' => array(
                    'value' => 4,
                    'type' => 'int_val'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => 'one',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 2,
                    'type' => 'int_val'
                    )
                )
            ),
        'limit_clause' => array(
            'start' => 0,
            'length' => 4
            ),
        'sort_order' => array(
            'two' => 'asc',
            'dog' => 'desc'
            )
        )
),
array(
'sql' => 'select foo.a from foo',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'foo.a'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'table_names' => array(
            0 => 'foo'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            )
        )
),
array(
'sql' => 'select a as b, min(a) as baz from foo',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'a'
            ),
        'column_aliases' => array(
            0 => 'b'
            ),
        'set_function' => array(
            0 => array(
                'name' => 'min',
                'arg' => 'a',
                'alias' => 'baz'
                )
            ),
        'table_names' => array(
            0 => 'foo'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            )
        )
),
array(
'sql' => 'select a from foo as bar',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'a'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'table_names' => array(
            0 => 'foo'
            ),
        'table_aliases' => array(
            0 => 'bar'
            ),
        'table_join_clause' => array(
            0 => ''
            )
        )
),
array(
'sql' => 'select * from person where surname is not null and firstname = \'jason\'',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'table_names' => array(
            0 => 'person'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'surname',
                    'type' => 'ident'
                    ),
                'op' => 'is',
                'neg' => true,
                'arg_2' => array(
                    'value' => '',
                    'type' => 'null'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => 'firstname',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 'jason',
                    'type' => 'text_val'
                    )
                )
            )
        )
),
array(
'sql' => 'select * from person where surname is null',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'table_names' => array(
            0 => 'person'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'surname',
                'type' => 'ident'
                ),
            'op' => 'is',
            'arg_2' => array(
                'value' => '',
                'type' => 'null'
                )
            )
        )
),
array(
'sql' => 'select * from person where surname = \'\' and firstname = \'jason\'',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'table_names' => array(
            0 => 'person'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'surname',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => '',
                    'type' => 'text_val'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => 'firstname',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 'jason',
                    'type' => 'text_val'
                    )
                )
            )
        )
),
array(
'sql' => 'select table_1.id, table_2.name from table_1, table_2 where table_2.table_1_id = table_1.id',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'table_1.id',
            1 => 'table_2.name'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
        'table_names' => array(
            0 => 'table_1',
            1 => 'table_2'
            ),
        'table_aliases' => array(
            0 => '',
            1 => ''
            ),
        'table_join_clause' => array(
            0 => '',
            1 => ''
            ),
        'table_join' => array(
            0 => ','
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'table_2.table_1_id',
                'type' => 'ident'
                ),
            'op' => '=',
            'arg_2' => array(
                'value' => 'table_1.id',
                'type' => 'ident'
                )
            )
        )
),
array(
'sql' => 'select a from table_1 where a not in (select b from table_2)',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'a'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'table_names' => array(
            0 => 'table_1'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'a',
                'type' => 'ident'
                ),
            'op' => 'in',
            'neg' => true,
            'arg_2' => array(
                'value' => array(
                    'command' => 'select',
                    'column_tables' => array(
                        0 => ''
                        ),
                    'column_names' => array(
                        0 => 'b'
                        ),
                    'column_aliases' => array(
                        0 => ''
                        ),
                    'table_names' => array(
                        0 => 'table_2'
                        ),
                    'table_aliases' => array(
                        0 => ''
                        ),
                    'table_join_clause' => array(
                        0 => ''
                        )
                    ),
                'type' => 'command'
                )
            )
        )
),
array(
'sql' => 'select a from table_1 where a in (select b from table_2 where c not in (select d from table_3))',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'a'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'table_names' => array(
            0 => 'table_1'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'a',
                'type' => 'ident'
                ),
            'op' => 'in',
            'arg_2' => array(
                'value' => array(
                    'command' => 'select',
                    'column_tables' => array(
                        0 => ''
                        ),
                    'column_names' => array(
                        0 => 'b'
                        ),
                    'column_aliases' => array(
                        0 => ''
                        ),
                    'table_names' => array(
                        0 => 'table_2'
                        ),
                    'table_aliases' => array(
                        0 => ''
                        ),
                    'table_join_clause' => array(
                        0 => ''
                        ),
                    'where_clause' => array(
                        'arg_1' => array(
                            'value' => 'c',
                            'type' => 'ident'
                            ),
                        'op' => 'in',
                        'neg' => true,
                        'arg_2' => array(
                            'value' => array(
                                'command' => 'select',
                                'column_tables' => array(
                                    0 => ''
                                    ),
                                'column_names' => array(
                                    0 => 'd'
                                    ),
                                'column_aliases' => array(
                                    0 => ''
                                    ),
                                'table_names' => array(
                                    0 => 'table_3'
                                    ),
                                'table_aliases' => array(
                                    0 => ''
                                    ),
                                'table_join_clause' => array(
                                    0 => ''
                                    )
                                ),
                            'type' => 'command'
                            )
                        )
                    ),
                'type' => 'command'
                )
            )
        )
),
array(
'sql' => 'select a from table_1 where a in (1, 2, 3)',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'a'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'table_names' => array(
            0 => 'table_1'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'a',
                'type' => 'ident'
                ),
            'op' => 'in',
            'arg_2' => array(
                'value' => array(
                    0 => 1,
                    1 => 2,
                    2 => 3
                    ),
                'type' => array(
                    0 => 'int_val',
                    1 => 'int_val',
                    2 => 'int_val'
                    )
                )
            )
        )
),
array(
'sql' => 'select count(child_table.name) from parent_table ,child_table where parent_table.id = child_table.id',
'expect' => array(
        'command' => 'select',
        'set_function' => array(
            0 => array(
                'name' => 'count',
                'arg' => array(
                    0 => 'child_table.name'
                    )
                )
            ),
        'table_names' => array(
            0 => 'parent_table',
            1 => 'child_table'
            ),
        'table_aliases' => array(
            0 => '',
            1 => ''
            ),
        'table_join_clause' => array(
            0 => '',
            1 => ''
            ),
        'table_join' => array(
            0 => ','
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'parent_table.id',
                'type' => 'ident'
                ),
            'op' => '=',
            'arg_2' => array(
                'value' => 'child_table.id',
                'type' => 'ident'
                )
            )
        )
),
array(
'sql' => 'select parent_table.name, count(child_table.name) from parent_table ,child_table where parent_table.id = child_table.id group by parent_table.name',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'parent_table.name'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'set_function' => array(
            0 => array(
                'name' => 'count',
                'arg' => array(
                    0 => 'child_table.name'
                    )
                )
            ),
        'table_names' => array(
            0 => 'parent_table',
            1 => 'child_table'
            ),
        'table_aliases' => array(
            0 => '',
            1 => ''
            ),
        'table_join_clause' => array(
            0 => '',
            1 => ''
            ),
        'table_join' => array(
            0 => ','
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'parent_table.id',
                'type' => 'ident'
                ),
            'op' => '=',
            'arg_2' => array(
                'value' => 'child_table.id',
                'type' => 'ident'
                )
            ),
        'group_by' => array(
            0 => 'parent_table.name'
            )
        )
),
array(
'sql' => 'select * from cats where furry = 1 group by name, type',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'table_names' => array(
            0 => 'cats'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'furry',
                'type' => 'ident'
                ),
            'op' => '=',
            'arg_2' => array(
                'value' => 1,
                'type' => 'int_val'
                )
            ),
        'group_by' => array(
            0 => 'name',
            1 => 'type'
            )
        )
),
array(
'sql' => 'select a, max(b) as x, sum(c) as y, min(d) as z from e',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'a'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'set_function' => array(
            0 => array(
                'name' => 'max',
                'arg' => 'b',
                'alias' => 'x'
                ),
            1 => array(
                'name' => 'sum',
                'arg' => 'c',
                'alias' => 'y'
                ),
            2 => array(
                'name' => 'min',
                'arg' => 'd',
                'alias' => 'z'
                )
            ),
        'table_names' => array(
            0 => 'e'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            )
        )
),
array(
'sql' => 'select clients_translation.id_clients_prefix, clients_translation.rule_number,
       clients_translation.pattern, clients_translation.rule
       from clients, clients_prefix, clients_translation
       where (clients.id_softswitch = 5)
         and (clients.id_clients = clients_prefix.id_clients)
         and clients.enable=\'y\'
         and clients.unused=\'n\'
         and (clients_translation.id_clients_prefix = clients_prefix.id_clients_prefix)
         order by clients_translation.id_clients_prefix,clients_translation.rule_number',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => '',
            2 => '',
            3 => ''
            ),
        'column_names' => array(
            0 => 'clients_translation.id_clients_prefix',
            1 => 'clients_translation.rule_number',
            2 => 'clients_translation.pattern',
            3 => 'clients_translation.rule'
            ),
        'column_aliases' => array(
            0 => '',
            1 => '',
            2 => '',
            3 => ''
            ),
        'table_names' => array(
            0 => 'clients',
            1 => 'clients_prefix',
            2 => 'clients_translation'
            ),
        'table_aliases' => array(
            0 => '',
            1 => '',
            2 => ''
            ),
        'table_join_clause' => array(
            0 => '',
            1 => '',
            2 => ''
            ),
        'table_join' => array(
            0 => ',',
            1 => ','
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => array(
                        'arg_1' => array(
                            'value' => 'clients.id_softswitch',
                            'type' => 'ident'
                            ),
                        'op' => '=',
                        'arg_2' => array(
                            'value' => 5,
                            'type' => 'int_val'
                            )
                        ),
                    'type' => 'subclause'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'arg_1' => array(
                        'value' => array(
                            'arg_1' => array(
                                'value' => 'clients.id_clients',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => 'clients_prefix.id_clients',
                                'type' => 'ident'
                                )
                            ),
                        'type' => 'subclause'
                        )
                    ),
                'op' => 'and',
                'arg_2' => array(
                    'arg_1' => array(
                        'arg_1' => array(
                            'value' => 'clients.enable',
                            'type' => 'ident'
                            ),
                        'op' => '=',
                        'arg_2' => array(
                            'value' => 'y',
                            'type' => 'text_val'
                            )
                        ),
                    'op' => 'and',
                    'arg_2' => array(
                        'arg_1' => array(
                            'arg_1' => array(
                                'value' => 'clients.unused',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => 'n',
                                'type' => 'text_val'
                                )
                            ),
                        'op' => 'and',
                        'arg_2' => array(
                            'arg_1' => array(
                                'value' => array(
                                    'arg_1' => array(
                                        'value' => 'clients_translation.id_clients_prefix',
                                        'type' => 'ident'
                                        ),
                                    'op' => '=',
                                    'arg_2' => array(
                                        'value' => 'clients_prefix.id_clients_prefix',
                                        'type' => 'ident'
                                        )
                                    ),
                                'type' => 'subclause'
                                )
                            )
                        )
                    )
                )
            ),
        'sort_order' => array(
            'clients_translation.id_clients_prefix' => 'asc',
            'clients_translation.rule_number' => 'asc'
            )
        )
),
array(
'sql' => 'SELECT column1,column2
FROM table1
WHERE (column1=\'1\' AND column2=\'1\') OR (column3=\'1\' AND column4=\'1\')',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'column1',
            1 => 'column2'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
        'table_names' => array(
            0 => 'table1'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => array(
                        'arg_1' => array(
                            'arg_1' => array(
                                'value' => 'column1',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => '1',
                                'type' => 'text_val'
                                )
                            ),
                        'op' => 'and',
                        'arg_2' => array(
                            'arg_1' => array(
                                'value' => 'column2',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => '1',
                                'type' => 'text_val'
                                )
                            )
                        ),
                    'type' => 'subclause'
                    )
                ),
            'op' => 'or',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => array(
                        'arg_1' => array(
                            'arg_1' => array(
                                'value' => 'column3',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => '1',
                                'type' => 'text_val'
                                )
                            ),
                        'op' => 'and',
                        'arg_2' => array(
                            'arg_1' => array(
                                'value' => 'column4',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => '1',
                                'type' => 'text_val'
                                )
                            )
                        ),
                    'type' => 'subclause'
                    )
                )
            )
        )
),
array(
'sql' => '-- Test Comment',
'expect' => 'Parse error: Nothing to do on line 1
-- Test Comment
                ^ found: "*end of input*"'

),
array(
'sql' => '# Test Comment',
'expect' => 'Parse error: Nothing to do on line 1
# Test Comment
               ^ found: "*end of input*"'

),
array(
'sql' => 'SELECT name FROM people WHERE id > 1 AND (name = \'arjan\' OR name = \'john\')',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => ''
            ),
        'column_names' => array(
            0 => 'name'
            ),
        'column_aliases' => array(
            0 => ''
            ),
        'table_names' => array(
            0 => 'people'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'id',
                    'type' => 'ident'
                    ),
                'op' => '>',
                'arg_2' => array(
                    'value' => 1,
                    'type' => 'int_val'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => array(
                        'arg_1' => array(
                            'arg_1' => array(
                                'value' => 'name',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => 'arjan',
                                'type' => 'text_val'
                                )
                            ),
                        'op' => 'or',
                        'arg_2' => array(
                            'arg_1' => array(
                                'value' => 'name',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => 'john',
                                'type' => 'text_val'
                                )
                            )
                        ),
                    'type' => 'subclause'
                    )
                )
            )
        )
),
array(
'sql' => 'select * from test where (field1 = \'x\' and field2 <>\'y\') or field3 = \'z\'',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'table_names' => array(
            0 => 'test'
            ),
        'table_aliases' => array(
            0 => ''
            ),
        'table_join_clause' => array(
            0 => ''
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => array(
                        'arg_1' => array(
                            'arg_1' => array(
                                'value' => 'field1',
                                'type' => 'ident'
                                ),
                            'op' => '=',
                            'arg_2' => array(
                                'value' => 'x',
                                'type' => 'text_val'
                                )
                            ),
                        'op' => 'and',
                        'arg_2' => array(
                            'arg_1' => array(
                                'value' => 'field2',
                                'type' => 'ident'
                                ),
                            'op' => '<>',
                            'arg_2' => array(
                                'value' => 'y',
                                'type' => 'text_val'
                                )
                            )
                        ),
                    'type' => 'subclause'
                    )
                ),
            'op' => 'or',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => 'field3',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 'z',
                    'type' => 'text_val'
                    )
                )
            )
        )
),
array(
'sql' => 'select a, d from b inner join c on b.a = c.a',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'a',
            1 => 'd'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
        'table_names' => array(
            0 => 'b',
            1 => 'c'
            ),
        'table_aliases' => array(
            0 => '',
            1 => ''
            ),
        'table_join_clause' => array(
            0 => '',
            1 => array(
                'arg_1' => array(
                    'value' => 'b.a',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 'c.a',
                    'type' => 'ident'
                    )
                )
            ),
        'table_join' => array(
            0 => 'inner join'
            )
        )
),
array(
'sql' => 'select a, d from b inner join c on b.a = c.a left outer join q on r < m',
'expect' => array(
        'command' => 'select',
        'column_tables' => array(
            0 => '',
            1 => ''
            ),
        'column_names' => array(
            0 => 'a',
            1 => 'd'
            ),
        'column_aliases' => array(
            0 => '',
            1 => ''
            ),
        'table_names' => array(
            0 => 'b',
            1 => 'c',
            2 => 'q'
            ),
        'table_aliases' => array(
            0 => '',
            1 => '',
            2 => ''
            ),
        'table_join_clause' => array(
            0 => '',
            1 => array(
                'arg_1' => array(
                    'value' => 'b.a',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 'c.a',
                    'type' => 'ident'
                    )
                ),
            2 => array(
                'arg_1' => array(
                    'value' => 'r',
                    'type' => 'ident'
                    ),
                'op' => '<',
                'arg_2' => array(
                    'value' => 'm',
                    'type' => 'ident'
                    )
                )
            ),
        'table_join' => array(
            0 => 'inner join',
            1 => 'left outer join'
            )
        )
),
);

	}
	
	
	function test_remove_where_clause(){
		$test = $this->tests[0];
		$parser = new SQL_Parser();
		$compiler = new SQL_Compiler();
		$parsed = $parser->parse($test['sql']);
		$wrapper = new SQL_Parser_wrapper($parsed);
		
		$to_be_removed = array(
            'arg_1' => array(
                'value' => 'cat',
                'type' => 'ident'
                ),
            'op' => '<>',
            'arg_2' => array(
                'value' => 4,
                'type' => 'int_val'
                )
            );
        
        $wrapper->removeWhereClause($to_be_removed);
        $this->assertTrue( !isset( $parsed['where_clause'] ) );
        $sql = $compiler->compile($parsed);
        $this->assertEquals( "select * from dog", $sql);
        
        //-------
        
        $parsed = $parser->parse('select one, two from hairy where two <> 4 and one = 2');
        $wrapper = new SQL_Parser_wrapper($parsed);
        
        $to_be_removed = array( 'arg_1' => array(
                    'value' => 'two',
                    'type' => 'ident'
                    ),
                'op' => '<>',
                'arg_2' => array(
                    'value' => 4,
                    'type' => 'int_val'
                    )
                );
        
        $wrapper->removeWhereClause($to_be_removed);
        
        $expected = array(
                'arg_1' => array(
                    'value' => 'one',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 2,
                    'type' => 'int_val'
                    )
                );
                
        $this->assertEquals( $expected, $parsed['where_clause']);
       
        
		
		
	
	
	}
	
	function test_find_where_clause_by_table(){
	
		$test = $this->tests[0];
		$parser = new SQL_Parser();
		$compiler = new SQL_Compiler();
		$parsed = $parser->parse($test['sql']);
		$wrapper = new SQL_Parser_wrapper($parsed);
		$found = $wrapper->findWhereClausesWithTable('dog');
		$this->assertEquals(array(), $found);
		
		
		$sql = 'select clients_translation.id_clients_prefix, clients_translation.rule_number,
			   clients_translation.pattern, clients_translation.rule
			   from clients, clients_prefix, clients_translation
			   where (clients.id_softswitch = 5)
				 and (clients.id_clients = clients_prefix.id_clients)
				 and clients.enable=\'y\'
				 and clients.unused=\'n\'
				 and (clients_translation.id_clients_prefix = clients_prefix.id_clients_prefix)
				 order by clients_translation.id_clients_prefix,clients_translation.rule_number';
		$parsed= $parser->parse($sql);
		$wrapper = new SQL_Parser_wrapper($parsed);
		$found = $wrapper->findWhereClausesWithTable('clients');
		
		$found_expected = array(
			array(
								'arg_1' => array(
									'value' => 'clients.id_softswitch',
									'type' => 'ident'
									),
								'op' => '=',
								'arg_2' => array(
									'value' => 5,
									'type' => 'int_val'
									)
								),
			array(
									'arg_1' => array(
										'value' => 'clients.id_clients',
										'type' => 'ident'
										),
									'op' => '=',
									'arg_2' => array(
										'value' => 'clients_prefix.id_clients',
										'type' => 'ident'
										)
									),
			array(
								'arg_1' => array(
									'value' => 'clients.enable',
									'type' => 'ident'
									),
								'op' => '=',
								'arg_2' => array(
									'value' => 'y',
									'type' => 'text_val'
									)
								),
			 array(
									'arg_1' => array(
										'value' => 'clients.unused',
										'type' => 'ident'
										),
									'op' => '=',
									'arg_2' => array(
										'value' => 'n',
										'type' => 'text_val'
										)
									)
			);
			
		$this->assertEquals($found_expected, $found);
		
		
				 
		$expected_parsed = array(
				'command' => 'select',
				'column_tables' => array(
					0 => '',
					1 => '',
					2 => '',
					3 => ''
					),
				'column_names' => array(
					0 => 'clients_translation.id_clients_prefix',
					1 => 'clients_translation.rule_number',
					2 => 'clients_translation.pattern',
					3 => 'clients_translation.rule'
					),
				'column_aliases' => array(
					0 => '',
					1 => '',
					2 => '',
					3 => ''
					),
				'table_names' => array(
					0 => 'clients',
					1 => 'clients_prefix',
					2 => 'clients_translation'
					),
				'table_aliases' => array(
					0 => '',
					1 => '',
					2 => ''
					),
				'table_join_clause' => array(
					0 => '',
					1 => '',
					2 => ''
					),
				'table_join' => array(
					0 => ',',
					1 => ','
					),
				'where_clause' => array(
					'arg_1' => array(
						'arg_1' => array(
							'value' => array(
								'arg_1' => array(
									'value' => 'clients.id_softswitch',
									'type' => 'ident'
									),
								'op' => '=',
								'arg_2' => array(
									'value' => 5,
									'type' => 'int_val'
									)
								),
							'type' => 'subclause'
							)
						),
					'op' => 'and',
					'arg_2' => array(
						'arg_1' => array(
							'arg_1' => array(
								'value' => array(
									'arg_1' => array(
										'value' => 'clients.id_clients',
										'type' => 'ident'
										),
									'op' => '=',
									'arg_2' => array(
										'value' => 'clients_prefix.id_clients',
										'type' => 'ident'
										)
									),
								'type' => 'subclause'
								)
							),
						'op' => 'and',
						'arg_2' => array(
							'arg_1' => array(
								'arg_1' => array(
									'value' => 'clients.enable',
									'type' => 'ident'
									),
								'op' => '=',
								'arg_2' => array(
									'value' => 'y',
									'type' => 'text_val'
									)
								),
							'op' => 'and',
							'arg_2' => array(
								'arg_1' => array(
									'arg_1' => array(
										'value' => 'clients.unused',
										'type' => 'ident'
										),
									'op' => '=',
									'arg_2' => array(
										'value' => 'n',
										'type' => 'text_val'
										)
									),
								'op' => 'and',
								'arg_2' => array(
									'arg_1' => array(
										'value' => array(
											'arg_1' => array(
												'value' => 'clients_translation.id_clients_prefix',
												'type' => 'ident'
												),
											'op' => '=',
											'arg_2' => array(
												'value' => 'clients_prefix.id_clients_prefix',
												'type' => 'ident'
												)
											),
										'type' => 'subclause'
										)
									)
								)
							)
						)
					),
				'sort_order' => array(
					'clients_translation.id_clients_prefix' => 'asc',
					'clients_translation.rule_number' => 'asc'
					)
				);
		//);
		
		
	
		
	
	}
	
	
	function test_remove_where_clauses_with_table(){
		$parser = new SQL_Parser();
		$sql = 'select clients_translation.id_clients_prefix, clients_translation.rule_number,
			   clients_translation.pattern, clients_translation.rule
			   from clients, clients_prefix, clients_translation
			   where (clients.id_softswitch = 5)
				 and (clients.id_clients = clients_prefix.id_clients)
				 and clients.enable=\'y\'
				 and clients.unused=\'n\'
				 and (clients_translation.id_clients_prefix = clients_prefix.id_clients_prefix)
				 order by clients_translation.id_clients_prefix,clients_translation.rule_number';
		$parsed= $parser->parse($sql);
		$wrapper = new SQL_Parser_wrapper($parsed);
		
		$wrapper->removeWhereClausesWithTable('clients_prefix');
		$found = $wrapper->findWhereClausesWithTable('clients_prefix');
		
		
		$expected = array(
					'arg_1' => array(
						'value' => array(
							'arg_1' => array(
								'value' => 'clients.id_softswitch',
								'type' => 'ident'
							),
							'op' => '=',
							'arg_2' => array(
								'value' => 5,
								'type' => 'int_val'
							)
						),
						'type' => 'subclause'
						
					),
					'op' => 'and',
					'arg_2' => array(
						'arg_1' => array(
							'arg_1' => array(
								'value' => 'clients.enable',
								'type' => 'ident'
								),
							'op' => '=',
							'arg_2' => array(
								'value' => 'y',
								'type' => 'text_val'
								)
							),
						'op' => 'and',
						'arg_2' => array(
							'arg_1' => array(
								'value' => 'clients.unused',
								'type' => 'ident'
								),
							'op' => '=',
							'arg_2' => array(
								'value' => 'n',
								'type' => 'text_val'
								)
							)
							
						)
					);
					
		$this->assertEquals($expected, $parsed['where_clause']);
		$compiler = new SQL_Compiler();
		
		
	
	}
	
	function test_find_where_clause_by_pattern(){
		$parser = new SQL_Parser();
		$compiler = new SQL_Compiler();
		$sql = 'select clients_translation.id_clients_prefix, clients_translation.rule_number,
			   clients_translation.pattern, clients_translation.rule
			   from clients, clients_prefix, clients_translation
			   where (clients.id_softswitch = 5)
				 and (clients.id_clients = clients_prefix.id_clients)
				 and clients.enable=\'y\'
				 and clients.unused=\'n\'
				 and (clients_translation.id_clients_prefix = clients_prefix.id_clients_prefix)
				 order by clients_translation.id_clients_prefix,clients_translation.rule_number';
		$parsed= $parser->parse($sql);
		$wrapper = new SQL_Parser_wrapper($parsed);
		
		$found = $wrapper->findWhereClausesWithPattern('/y/');
		$expected = array( array(
							'arg_1' => array(
								'value' => 'clients.enable',
								'type' => 'ident'
								),
							'op' => '=',
							'arg_2' => array(
								'value' => 'y',
								'type' => 'text_val'
								)
							));
		$this->assertEquals($expected, $found);
	
	}
	
	function test_add_column(){
		$parser = new SQL_Parser();
		$compiler = new SQL_Compiler();
		$sql = 'select foo, bar from Table1';
		$parsed = $parser->parse($sql);
		$wrapper = new SQL_Parser_wrapper($parsed);
		$wrapper->addColumn('testColumn', 'a');
		$this->assertEquals("select foo, bar, testColumn as a from Table1", $compiler->compile($parsed));
		
	
	
	}
	
	
	function test_unresolve_column_name(){
		$parser = new SQL_Parser();
		$compiler = new SQL_Compiler();
		$sql = 'select foo, bar from Table1';
		$parsed = $parser->parse($sql);
		$wrapper = new SQL_Parser_wrapper($parsed);
		
		/*
		 * Try a simple column name to make sure that it is unchanged.
		 */
		$this->assertEquals("a", $wrapper->unresolveColumnName("a"));
		
		/*
		 * Try one with an invalid tablename.  Should give an error.
		 */
		$out = $wrapper->unresolveColumnName("b.a");
		$this->assertTrue( is_a( $out, "PEAR_Error") );
		
		
		$sql = 'select * from Table1 as a, Table2 as b';
		$parsed = $parser->parse($sql);
		$wrapper = new SQL_Parser_wrapper($parsed);
		
		$this->assertEquals('a.foo', $wrapper->unresolveColumnName("Table1.foo"));
		$this->assertEquals('b.bar', $wrapper->unresolveColumnName("Table2.bar"));
		$this->assertEquals('a.foo', $wrapper->unresolveColumnName("a.foo"));
		$this->assertEquals('b.bar', $wrapper->unresolveColumnName("b.bar"));
		$this->assertEquals('id', $wrapper->unresolveColumnName("id"));
		
		
	
	}
	
	
	function test_get_table_alias(){
		$parser = new SQL_Parser();
		$compiler = new SQL_Compiler();
		$sql = 'select foo, bar from Table1';
		$parsed = $parser->parse($sql);
		$wrapper = new SQL_Parser_wrapper($parsed);
		$wrapper->addColumn('testColumn', 'a');
		$this->assertEquals('Table1', $wrapper->getTableAlias('Table1'));
		
		$err = $wrapper->getTableAlias('TableNonExistent');
		$this->assertTrue( is_a($err, 'PEAR_Error') );
		
		$sql = 'select a.foo, b.bar from Table1 as a inner join Table2 as b on a.foo=b.bar';
		$parsed = $parser->parse($sql);
		//print_r($parsed);
		$wrapper = new SQL_Parser_wrapper($parsed);
		$this->assertEquals('a', $wrapper->getTableAlias('Table1'));
		$this->assertEquals('b', $wrapper->getTableAlias('Table2'));
	
	
	}
	
	function test_add_meta_data_column(){
		$parser = new SQL_Parser(null, 'MySQL');
		$sql = "SELECT a from Foo";
		$parsed = $parser->parse($sql);
		$wrapper = new SQL_Parser_wrapper($parsed);
		$wrapper->addMetaDataColumn("a", true);
		$compiler = new SQL_Compiler();
		$sql = $compiler->compile($parsed);
		$this->assertEquals("select a, length(a) as __a_length from Foo", $sql);
		
		$wrapper->addMetaDataColumn("Foo.b", true);
		$sql = $compiler->compile($parsed);
		$this->assertEquals("select a, length(a) as __a_length, length(Foo.b) as __Foo_b_length from Foo", $sql);
		
		$sql = "SELECT a from Foo";
		$parsed = $parser->parse($sql);
		unset($wrapper);
		$wrapper = new SQL_Parser_wrapper($parsed);
		$wrapper->addMetaDataColumn("Foo.b");
		$sql = $compiler->compile($parsed);
		$this->assertEquals("select a, length(Foo.b) as __b_length from Foo", $sql);
		
		$sql = "SELECT a from Foo as f";
		$parsed = $parser->parse($sql);
		$wrapper = new SQL_Parser_wrapper($parsed);
		$wrapper->addMetaDataColumn("Foo.b");
		$sql = $compiler->compile($parsed);
		$this->assertEquals("select a, length(f.b) as __b_length from Foo as f", $sql);
		
		
		
	}
	
	function test_add_meta_data_columns(){
		$parser = new SQL_Parser(null, 'MySQL');
		$compiler = new SQL_Compiler();
		$sql = "SELECT a,b,c from Foo";
		$parsed = $parser->parse($sql);
		$wrapper = new SQL_Parser_wrapper($parsed);
		$wrapper->addMetaDataColumns();
		$sql = $compiler->compile($parsed);
		$this->assertEquals("select a, b, c, length(a) as __a_length, length(b) as __b_length, length(c) as __c_length from Foo", $sql);
		
		// make sure that doing it twice doesn't do anything
		$wrapper->addMetaDataColumns();
		$sql = $compiler->compile($parsed);
		$this->assertEquals("select a, b, c, length(a) as __a_length, length(b) as __b_length, length(c) as __c_length from Foo", $sql);
	
	}
	
	
	function test_pack_tables(){
	
		$parser = new SQL_Parser();
		$compiler = new SQL_Compiler();
		$tests = array(
			array(
				"sql"=> "select * from Courses, Students",
				"exempt"=> array("Students"),
				"expected"=>"select * from Students"
			),
			array(
				"sql"=>"select * from Courses inner join Students on Courses.id=Students.id",
				"exempt"=>array(),
				"expected"=>"select * from Courses inner join Students on Courses.id = Students.id"
			),
			array(
				"sql"=>"select Students.id, Students.name from Courses inner join Students, Employees",
				"exempt"=>array(),
				"expected"=>"select Students.id, Students.name from Students"
			),
			array(
				"sql"=>"select Students.id, Students.name from Courses inner join Students on Courses.id=Students.courseid",
				"exempt"=>array(),
				"expected"=>"select Students.id, Students.name from Courses inner join Students on Courses.id = Students.courseid"
			)
		);
		
		foreach ($tests as $test){
			$parsed = $parser->parse($test['sql']);
			$wrapper = new SQL_Parser_wrapper($parsed);
			//print_r($parsed);
			$wrapper->packTables($test['exempt']);
			//print_r($parsed);
			$actual = $compiler->compile($parsed);
			$this->assertEquals($test['expected'], $actual);
		}
	
	
	}
	
	function test_unresolve_where_clause_columns(){
		$parser = new SQL_Parser(null, 'MySQL');
		$compiler = new SQL_Compiler();
		$sql = "SELECT a,b,c from Foo f inner join Bar b where (Foo.a='5' and Bar.b='6') or (Foo.c='7' and Bar.d='8')";
		$parsed = $parser->parse($sql);
		$wrapper = new SQL_Parser_wrapper($parsed, 'MySQL');
		$wrapper->unresolveWhereClauseColumns($parsed['where_clause']);
		$this->assertEquals("select a, b, c from Foo as f inner join Bar as b where (f.a = '5' and b.b = '6') or (f.c = '7' and b.d = '8')", $compiler->compile($parsed));
		//print_r($parsed);
	
	}
	
	
	function test_add_where_clause(){
	
		$parser = new SQL_Parser(null, 'MySQL');
		$compiler = new SQL_Compiler();
		$sql = "SELECT a,b,c from Foo";
		$parsed = $parser->parse($sql);
		$wrapper = new SQL_Parser_wrapper($parsed, 'MySQL');
		$wrapper->addWhereClause("c=5");
		$sqlout = $compiler->compile($parsed);
		$this->assertEquals("select a, b, c from Foo where c = 5", $sqlout);
		
		$wrapper->addWhereClause("d='five hundred'");
		$sqlout = $compiler->compile($parsed);
		$this->assertEquals("select a, b, c from Foo where c = 5 and d = 'five hundred'", $sqlout);
		
		$wrapper->addWhereClause("Foo.c=60");
		$this->assertEquals("select a, b, c from Foo where (c = 5 and d = 'five hundred') and Foo.c = 60", $compiler->compile($parsed));
		
		$sql = "SELECT a,b,c from Foo f inner join Bar b where (f.a='5' and b.b='6')";
		$parsed = $parser->parse($sql);
		$wrapper = new SQL_Parser_wrapper($parsed, 'MySQL');
		$wrapper->addWhereClause("Foo.c='7' and Bar.d='8'", 'or');
		//$wrapper->unresolveWhereClauseColumns($parsed['where_clause']);
		$this->assertEquals("select a, b, c from Foo as f inner join Bar as b where (f.a = '5' and b.b = '6') or (f.c = '7' and b.d = '8')", $compiler->compile($parsed));
		
		//print_r($parser->parse("SELECT * FROM Foo order by Bar.c"));
		//print_r($parsed);
	
	}
	
	
	function test_set_sort_clause(){
		$parser = new SQL_Parser(null, 'MySQL');
		$compiler = new SQL_Compiler();
		$sql = "SELECT a,b,c from Foo";
		$parsed = $parser->parse($sql);
		$wrapper = new SQL_Parser_wrapper($parsed, 'MySQL');
		$wrapper->setSortClause("c");
		$this->assertEquals("select a, b, c from Foo order by c asc",  $compiler->compile($parsed));
		$wrapper->setSortClause("b");
		$this->assertEquals("select a, b, c from Foo order by b asc", $compiler->compile($parsed));
		$wrapper->setSortClause("b desc");
		$this->assertEquals("select a, b, c from Foo order by b desc", $compiler->compile($parsed));
		$wrapper->setSortClause("b desc, c");
		$this->assertEquals("select a, b, c from Foo order by b desc, c asc", $compiler->compile($parsed));
		$wrapper->addSortClause("d");
		$this->assertEquals("select a, b, c from Foo order by b desc, c asc, d asc", $compiler->compile($parsed));
	}
	
	function test_misc(){
		$parser = new SQL_Parser(null, 'MySQL');
		$compiler = new SQL_Compiler();
		print_r($parser->parse("select foo.a from foo"));
		print_r($parser->parse("select a, b, c from foo"));
		print_r($parser->parse("SELECT F.a as column1, B.b as column2 FROM Foo F inner join Bar B on F.c=B.c where column1 = 'val1' and column2 = 'val2'"));
		$res = $parser->parse("SELECT f.a from Foo f where conv('a',16,2) = '2005'");
		if ( PEAR::isError($res) ){
			echo $res->toString().Dataface_Error::printStackTrace();
		} else {
			print_r($res);
		}
		$res = $parser->parse("SELECT * from Publications where Expires > NOW()");
		if ( PEAR::isError($res) ) {
		
		
			echo $res->toString().Dataface_Error::printStackTrace();
		} else {
			print_r($res);
			echo $compiler->compile($res);
		}
	
	
	
	}
	
}

?>
