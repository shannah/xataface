<?php
$tests = array(
array(
'sql' => 'delete from dog where cat = 4 and horse <> "dead meat" or mouse = \'furry\'',
'expect' => array(
        'command' => 'delete',
        'table_names' => array(
            0 => 'dog'
            ),
        'where_clause' => array(
            'arg_1' => array(
                'arg_1' => array(
                    'value' => 'cat',
                    'type' => 'ident'
                    ),
                'op' => '=',
                'arg_2' => array(
                    'value' => 4,
                    'type' => 'int_val'
                    )
                ),
            'op' => 'and',
            'arg_2' => array(
                'arg_1' => array(
                    'arg_1' => array(
                        'value' => 'horse',
                        'type' => 'ident'
                        ),
                    'op' => '<>',
                    'arg_2' => array(
                        'value' => 'dead meat',
                        'type' => 'text_val'
                        )
                    ),
                'op' => 'or',
                'arg_2' => array(
                    'arg_1' => array(
                        'value' => 'mouse',
                        'type' => 'ident'
                        ),
                    'op' => '=',
                    'arg_2' => array(
                        'value' => 'furry',
                        'type' => 'text_val'
                        )
                    )
                )
            )
        )
),
array(
'sql' => 'delete from',
'expect' => 'Parse error: Expected a table name on line 1
delete from
            ^ found: "*end of input*"'

),
array(
'sql' => 'delete from cat',
'expect' => 'Parse error: Expected "where" on line 1
delete from cat
                ^ found: "*end of input*"'

),
array(
'sql' => 'delete from where cat = 53',
'expect' => 'Parse error: Expected a table name on line 1
delete from where cat = 53
            ^ found: "where"'

),
array(
'sql' => 'delete from dog where mouse is happy',
'expect' => 'Parse error: Expected "null" on line 1
delete from dog where mouse is happy
                               ^ found: "happy"'

),
);
?>
