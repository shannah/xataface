<?php
$tests = array(
array(
'sql' => 'drop table dishes cascade',
'expect' => array(
        'command' => 'drop_table',
        'table_names' => array(
            0 => 'dishes'
            ),
        'drop_behavior' => 'cascade'
        )
),
array(
'sql' => 'drop table bondage restrict',
'expect' => array(
        'command' => 'drop_table',
        'table_names' => array(
            0 => 'bondage'
            ),
        'drop_behavior' => 'restrict'
        )
),
array(
'sql' => 'drop table play cascade restrict',
'expect' => 'Parse error: Unexpected token on line 1
drop table play cascade restrict
                        ^ found: "restrict"'

),
array(
'sql' => 'drop table cat where mouse = floor',
'expect' => 'Parse error: Unexpected token on line 1
drop table cat where mouse = floor
                     ^ found: "mouse"'

),
array(
'sql' => 'drop elephant',
'expect' => 'Parse error: Unknown object to drop on line 1
drop elephant
     ^ found: "elephant"'

),
);
?>
