<?php
$tests = array(
array(
'sql' => 'select * from dog where cat <> 4',
'expected_compiled' => 'select * from dog where cat <> 4',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'column_aliases' => array(
        	0 => ''
        	),
        'column_tables' => array(
        	0 => ''
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
'expected_compiled' => 'select legs, hairy from dog',
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
'sql' => 'select max(`length`) from dog',
'expected_compiled' => 'select max(length) from dog',
'expect' => array(
        'command' => 'select',
        'set_function' => array(
            0 => array(
                'name' => 'max',
                'args' => array(
                	0 => array(
                		'type' => 'ident',
                		'value' => 'length'
                		)
                	)
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
'expected_compiled' => 'select count(distinct country) from publishers',
'expect' => array(
        'command' => 'select',
        'set_function' => array(
            0 => array(
                'name' => 'count',
                'args' => array(
                    0 => array(
                    	'quantifier' => 'distinct',
                    	'type' => 'ident',
                    	'value' => 'country'
         
                    	)
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
'expected_compiled' => 'select one, two from hairy where two <> 4 and one = 2',
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
'expected_compiled' => 'select one, two from hairy where two <> 4 and one = 2 order by two asc',
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
            0 => array(
            	'value' => 'two',
            	'type' => 'ident',
            	'order' => 'asc'
            	)
            )
        )
),
array(
'sql' => 'select one, two from hairy where two <> 4 and one = 2 limit 4 order by two ascending, dog descending',
'expected_compiled' => 'select one, two from hairy where two <> 4 and one = 2 order by two asc, dog desc limit 0,4',
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
        	0 => array(
        		'value' => 'two',
        		'type' => 'ident',
        		'order' => 'asc'
        		),
        	1 => array(
        		'value' => 'dog',
        		'type' => 'ident',
        		'order' => 'desc'
        		)
            )
        )
),
array(
'sql' => 'select foo.a from foo',
'expected_compiled' => 'select foo.a from foo',
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
'expected_compiled' => 'select a as b, min(a) as baz from foo',
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
                'args' => array(
                	0 => array(
                		'type' => 'ident',
                		'value' => 'a'
                		)
                	),
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
'expected_compiled' => 'select a from foo as bar',
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
'expected_compiled' => 'select * from person where surname is not null and firstname = \'jason\'',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'column_aliases' => array(
        	0 => ''
        	),
        'column_tables' => array(
        	0 => ''
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
'expected_compiled' => 'select * from person where surname is null',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'column_aliases' => array(
        	0 => ''
        	),
        'column_tables' => array(
        	0 => ''
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
'expected_compiled' => 'select * from person where surname = \'\' and firstname = \'jason\'',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'column_aliases' => array(
        	0 => ''
        	),
        'column_tables' => array(
        	0 => ''
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
'expected_compiled' => 'select table_1.id, table_2.name from table_1, table_2 where table_2.table_1_id = table_1.id',
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
'expected_compiled' => 'select a from table_1 where a not in (select b from table_2)',
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
'expected_compiled' => 'select a from table_1 where a in (select b from table_2 where c not in (select d from table_3))',
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
'expected_compiled' => 'select a from table_1 where a in (1, 2, 3)',
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
'expected_compiled' => 'select count(child_table.name) from parent_table, child_table where parent_table.id = child_table.id',
'expect' => array(
        'command' => 'select',
        'set_function' => array(
            0 => array(
                'name' => 'count',
                'args' => array(
                    0 => array(
                    	'type' => 'ident',
                    	'value' => 'child_table.name'
                    	)
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
'expected_compiled' => 'select parent_table.name, count(child_table.name) from parent_table, child_table where parent_table.id = child_table.id group by parent_table.name',
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
                'args' => array(
                    0 => array(
                    	'type' => 'ident',
                    	'value' => 'child_table.name'
                    	)
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
'expected_compiled' => 'select * from cats where furry = 1 group by name, type',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'column_aliases' => array(
        	0 => ''
        	),
        'column_tables' => array(
        	0 => ''
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
'expected_compiled' => 'select a, max(b) as x, sum(c) as y, min(d) as z from e',
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
                'args' => array(
                	0 => array(
                		'type' => 'ident',
                		'value' => 'b'
                		)
                	),
                'alias' => 'x'
                ),
            1 => array(
                'name' => 'sum',
                'args' => array(
                	0 => array(
                		'type' => 'ident',
                		'value' => 'c'
                		)
                	),
                'alias' => 'y'
                ),
            2 => array(
                'name' => 'min',
                'args' => array(
                	0 => array(
                		'type' => 'ident',
                		'value' => 'd'
                		)
                	),
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
'expected_compiled' => 'select clients_translation.id_clients_prefix, clients_translation.rule_number, clients_translation.pattern, clients_translation.rule from clients, clients_prefix, clients_translation where (clients.id_softswitch = 5) and (clients.id_clients = clients_prefix.id_clients) and clients.enable = \'y\' and clients.unused = \'n\' and (clients_translation.id_clients_prefix = clients_prefix.id_clients_prefix) order by clients_translation.id_clients_prefix asc, clients_translation.rule_number asc',
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
        	0 => array(
        		'value' => 'clients_translation.id_clients_prefix',
        		'type' => 'ident',
        		'order' => 'asc'
        		),
        	1 => array(
        		'value' => 'clients_translation.rule_number',
        		'type' => 'ident',
        		'order' => 'asc'
        		)
            
            )
        )
),
array(
'sql' => 'SELECT column1,column2
FROM table1
WHERE (column1=\'1\' AND column2=\'1\') OR (column3=\'1\' AND column4=\'1\')',
'expected_compiled' => 'select column1, column2 from table1 where (column1 = \'1\' and column2 = \'1\') or (column3 = \'1\' and column4 = \'1\')',
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
'expected_compiled' => 'select name from people where id > 1 and (name = \'arjan\' or name = \'john\')',

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
'expected_compiled' => 'select * from test where (field1 = \'x\' and field2 <> \'y\') or field3 = \'z\'',
'expect' => array(
        'command' => 'select',
        'column_names' => array(
            0 => '*'
            ),
        'column_aliases' => array(
        	0 => ''
        	),
        'column_tables' => array(
        	0 => ''
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
'expected_compiled' => 'select a, d from b inner join c on b.a = c.a',
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
'expected_compiled' => 'select a, d from b inner join c on b.a = c.a left outer join q on r < m',

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
array(
'sql' => 'select a, length(a) as __a_length from Foo',
'expected_compiled' => 'select a, length(a) as __a_length from Foo',
'expect' => array(
	'command' => 'select',
	'column_names' => array(
		0 => 'a'
		),
	'column_aliases' => array(
		0 => ''
		),
	'column_tables' => array(
		0 => ''
		),
	
	'set_function' => array(
		0 => array(
			'name' => 'length',
			'args' => array(
				0 => array(
					'type' => 'ident',
					'value' => 'a'
					)
				),
			
			'alias' => '__a_length'
			),
		),
	'table_names' => array(
		0 => 'Foo'
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
'sql' => 'select name, institution from Degrees where Degrees.profileid=\'$id\'',
'expected_compiled' => 'select name, institution from Degrees where Degrees.profileid = \'$id\'',
'expect' => array(
	'command' => 'select',
	'column_names' => array(
		0 => 'name',
		1 => 'institution'
		),
	'column_aliases' => array(
		0 => '',
		1 => ''
		),
	'column_tables' => array(
		0 => '',
		1 => ''
		),
	'table_names' => array(
		0 => 'Degrees'
		),
	'table_aliases' => array(
		0 => ''
		),
	'table_join_clause' => array(
		0 => ''
		),
	'where_clause' => array(
		'arg_1' => array(
			
			'value' => 'Degrees.profileid',
			'type' => 'ident'
			),
		'op' => '=',
		'arg_2' => array(
			
			'value' => '$id',
			'type' => 'text_val'
			)
		)
	)
)
);
?>
