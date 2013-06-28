<?php
$tests = array(
array(
'sql' => 'update dogmeat set horse=2 dog=\'forty\' where moose <> \'howdydoo\'',
'expect' => 'Parse error: Expected "where" or "," on line 1
update dogmeat set horse=2 dog=\'forty\' where moose <> \'howdydoo\'
                           ^ found: "dog"'

),
//array(
//'sql' => 'update dogmeat set horse=2, dog=\'forty\' where moose != \'howdydoo\'',
//'expect' => 'Parse error: Expected an operator on line 1
//update dogmeat set horse=2, dog=\'forty\' where moose != \'howdydoo\'
//                                                    ^ found: "!="'
//
//),
array(
'sql' => 'update dogmeat set horse=2, dog=\'forty\' where moose <> \'howdydoo\'',
'expect' => array(
        'command' => 'update',
        'table_names' => array(
            0 => 'dogmeat'
            ),
        'column_names' => array(
            0 => 'horse',
            1 => 'dog'
            ),
        'values' => array(
            0 => array(
                'value' => 2,
                'type' => 'int_val'
                ),
            1 => array(
                'value' => 'forty',
                'type' => 'text_val'
                )
            ),
        'where_clause' => array(
            'arg_1' => array(
                'value' => 'moose',
                'type' => 'ident'
                ),
            'op' => '<>',
            'arg_2' => array(
                'value' => 'howdydoo',
                'type' => 'text_val'
                )
            )
        )
),
array(
'sql' => 'update table1 set col=1 where not col = 2',
'expect' => array(
        'command' => 'update',
        'table_names' => array(
            0 => 'table1'
            ),
        'column_names' => array(
            0 => 'col'
            ),
        'values' => array(
            0 => array(
                'value' => 1,
                'type' => 'int_val'
                )
            ),
        'where_clause' => array(
            'neg' => true,
            'arg_1' => array(
                'value' => 'col',
                'type' => 'ident'
                ),
            'op' => '=',
            'arg_2' => array(
                'value' => 2,
                'type' => 'int_val'
                )
            )
        )
),
array(
'sql' => 'update table2 set col=1 where col > 2 and col <> 4',
'expect' => array(
        'command' => 'update',
        'table_names' => array(
            0 => 'table2'
            ),
        'column_names' => array(
            0 => 'col'
            ),
        'values' => array(
            0 => array(
                'value' => 1,
                'type' => 'int_val'
                )
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'col',
                    'type' => 'ident'
                    ),
                'op' => '>',
                'arg_2' => array(
                    'value' => 2,
                    'type' => 'int_val'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => 'col',
                    'type' => 'ident'
                    ),
                'op' => '<>',
                'arg_2' => array(
                    'value' => 4,
                    'type' => 'int_val'
                    )
                )
            )
        )
),
array(
'sql' => 'update table2 set col=1 where col > 2 and col <> 4 or dog="Hello"',
'expect' => array(
        'command' => 'update',
        'table_names' => array(
            0 => 'table2'
            ),
        'column_names' => array(
            0 => 'col'
            ),
        'values' => array(
            0 => array(
                'value' => 1,
                'type' => 'int_val'
                )
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'col',
                    'type' => 'ident'
                    ),
                'op' => '>',
                'arg_2' => array(
                    'value' => 2,
                    'type' => 'int_val'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'arg_1' => array(
                        'value' => 'col',
                        'type' => 'ident'
                        ),
                    'op' => '<>',
                    'arg_2' => array(
                        'value' => 4,
                        'type' => 'int_val'
                        )
                    ),
                'op' => 'or',
                'arg_2' => array(
                    'arg_1' => array(
                        'value' => 'dog',
                        'type' => 'ident'
                        ),
                    'op' => '=',
                    'arg_2' => array(
                        'value' => 'Hello',
                        'type' => 'text_val'
                        )
                    )
                )
            )
        )
),
array(
'sql' => 'update table3 set col=1 where col > 2 and col < 30',
'expected_compiled' => 'update table3 set col = 1 where col > 2 and col < 30',
'expect' => array(
        'command' => 'update',
        'table_names' => array(
            0 => 'table3'
            ),
        'column_names' => array(
            0 => 'col'
            ),
        'values' => array(
            0 => array(
                'value' => 1,
                'type' => 'int_val'
                )
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'col',
                    'type' => 'ident'
                    ),
                'op' => '>',
                'arg_2' => array(
                    'value' => 2,
                    'type' => 'int_val'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => 'col',
                    'type' => 'ident'
                    ),
                'op' => '<',
                'arg_2' => array(
                    'value' => 30,
                    'type' => 'int_val'
                    )
                )
            )
        )
),
array(
'sql' => 'update table3 set col=1 where col > 2 and col < 30 limit 1',
'expected_compiled' => 'update table3 set col = 1 where col > 2 and col < 30 limit 1',
'expect' => array(
        'command' => 'update',
        'table_names' => array(
            0 => 'table3'
            ),
        'column_names' => array(
            0 => 'col'
            ),
        'values' => array(
            0 => array(
                'value' => 1,
                'type' => 'int_val'
                )
            ),
        'limit_clause' => array(
        	'start'=>0,
        	'length'=>1
        	),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'col',
                    'type' => 'ident'
                    ),
                'op' => '>',
                'arg_2' => array(
                    'value' => 2,
                    'type' => 'int_val'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'value' => 'col',
                    'type' => 'ident'
                    ),
                'op' => '<',
                'arg_2' => array(
                    'value' => 30,
                    'type' => 'int_val'
                    )
                )
            )
        ),
        
)
);
?>
