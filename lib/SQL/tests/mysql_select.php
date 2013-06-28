<?php
$tests = array(
array(
'sql' => 'select * from `dog` where cat <> 4',
'expected_compiled' => 'select * from `dog` where `cat` <> 4',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'glob',
      'table' => '',
      'value' => '*',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => '*',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'dog',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'dog',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 'cat',
      'type' => 'ident',
    ),
    'op' => '<>',
    'arg_2' => 
    array (
      'value' => 4,
      'type' => 'int_val',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'dog',
  ),
)

),
array(
'sql' => 'select legs, hairy from dog',
'expected_compiled' => 'select `legs`, `hairy` from `dog`',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'legs',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'hairy',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
  ),
  'column_names' => 
  array (
    0 => 'legs',
    1 => 'hairy',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'table_names' => 
  array (
    0 => 'dog',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'dog',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'dog',
  ),
)

),
array(
'sql' => 'select max(`length`) from dog',
'expected_compiled' => 'select max(`length`) from `dog`',
'expect' => array (
  'command' => 'select',
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'max',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'length',
        ),
      ),
    ),
  ),
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'max',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'length',
          ),
        ),
      ),
      'alias' => '',
    ),
  ),
  'table_names' => 
  array (
    0 => 'dog',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'dog',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'dog',
  ),
)

),
array(
'sql' => 'select count(distinct country) from publishers',
'expected_compiled' => 'select count(distinct `country`) from `publishers`',
'expect' => array (
  'command' => 'select',
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'count',
      'args' => 
      array (
        0 => 
        array (
          'quantifier' => 'distinct',
          'type' => 'ident',
          'value' => 'country',
        ),
      ),
    ),
  ),
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'count',
        'args' => 
        array (
          0 => 
          array (
            'quantifier' => 'distinct',
            'type' => 'ident',
            'value' => 'country',
          ),
        ),
      ),
      'alias' => '',
    ),
  ),
  'table_names' => 
  array (
    0 => 'publishers',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'publishers',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'publishers',
  ),
)

),
array(
'sql' => 'select one, two from hairy where two <> 4 and one = 2',
'expected_compiled' => 'select `one`, `two` from `hairy` where `two` <> 4 and `one` = 2',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'one',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'two',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
  ),
  'column_names' => 
  array (
    0 => 'one',
    1 => 'two',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'table_names' => 
  array (
    0 => 'hairy',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'hairy',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'arg_1' => 
      array (
        'value' => 'two',
        'type' => 'ident',
      ),
      'op' => '<>',
      'arg_2' => 
      array (
        'value' => 4,
        'type' => 'int_val',
      ),
    ),
    'op' => 'and',
    'arg_2' => 
    array (
      'arg_1' => 
      array (
        'value' => 'one',
        'type' => 'ident',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => 2,
        'type' => 'int_val',
      ),
    ),
  ),
  'all_tables' => 
  array (
    0 => 'hairy',
  ),
)

),
array(
'sql' => 'select one, two from hairy where two <> 4 and one = 2 order by two',
'expected_compiled' => 'select `one`, `two` from `hairy` where `two` <> 4 and `one` = 2 order by `two` asc',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'one',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'two',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
  ),
  'column_names' => 
  array (
    0 => 'one',
    1 => 'two',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'table_names' => 
  array (
    0 => 'hairy',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'hairy',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'arg_1' => 
      array (
        'value' => 'two',
        'type' => 'ident',
      ),
      'op' => '<>',
      'arg_2' => 
      array (
        'value' => 4,
        'type' => 'int_val',
      ),
    ),
    'op' => 'and',
    'arg_2' => 
    array (
      'arg_1' => 
      array (
        'value' => 'one',
        'type' => 'ident',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => 2,
        'type' => 'int_val',
      ),
    ),
  ),
  'sort_order' => 
  array (
    0 => 
    array (
      'value' => 'two',
      'type' => 'ident',
      'order' => 'asc',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'hairy',
  ),
)

),
array(
'sql' => 'select one, two from hairy where two <> 4 and one = 2 limit 4 order by two ascending, dog descending',
'expected_compiled' => 'select `one`, `two` from `hairy` where `two` <> 4 and `one` = 2 order by `two` asc, `dog` desc limit 4',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'one',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'two',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
  ),
  'column_names' => 
  array (
    0 => 'one',
    1 => 'two',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'table_names' => 
  array (
    0 => 'hairy',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'hairy',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'arg_1' => 
      array (
        'value' => 'two',
        'type' => 'ident',
      ),
      'op' => '<>',
      'arg_2' => 
      array (
        'value' => 4,
        'type' => 'int_val',
      ),
    ),
    'op' => 'and',
    'arg_2' => 
    array (
      'arg_1' => 
      array (
        'value' => 'one',
        'type' => 'ident',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => 2,
        'type' => 'int_val',
      ),
    ),
  ),
  'limit_clause' => 
  array (
    'start' => 0,
    'length' => 4,
  ),
  'sort_order' => 
  array (
    0 => 
    array (
      'value' => 'two',
      'type' => 'ident',
      'order' => 'asc',
    ),
    1 => 
    array (
      'value' => 'dog',
      'type' => 'ident',
      'order' => 'desc',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'hairy',
  ),
)

),
array(
'sql' => 'select foo.a from foo',
'expected_compiled' => 'select `foo`.`a` from `foo`',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => 'foo',
      'value' => 'a',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'foo.a',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'foo',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'foo',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'foo',
  ),
)

),
array(
'sql' => 'select a as b, min(a) as baz from foo',
'expected_compiled' => 'select `a` as `b`, min(`a`) as `baz` from `foo`',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'a',
      'alias' => 'b',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'min',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'a',
          ),
        ),
        'alias' => 'baz',
      ),
      'alias' => 'baz',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'a',
  ),
  'column_aliases' => 
  array (
    0 => 'b',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'min',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'a',
        ),
      ),
      'alias' => 'baz',
    ),
  ),
  'table_names' => 
  array (
    0 => 'foo',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'foo',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'foo',
  ),
)

),
array(
'sql' => 'select a from foo as bar',
'expected_compiled' => 'select `a` from `foo` as `bar`',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'a',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'a',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'foo',
  ),
  'table_aliases' => 
  array (
    0 => 'bar',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'foo',
      'alias' => 'bar',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'foo',
  ),
)

),
array(
'sql' => 'select * from person where surname is not null and firstname = \'jason\'',
'expected_compiled' => 'select * from `person` where `surname` is not null and `firstname` = \'jason\'',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'glob',
      'table' => '',
      'value' => '*',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => '*',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'person',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'person',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'arg_1' => 
      array (
        'value' => 'surname',
        'type' => 'ident',
      ),
      'op' => 'is',
      'neg' => true,
      'arg_2' => 
      array (
        'value' => '',
        'type' => 'null',
      ),
    ),
    'op' => 'and',
    'arg_2' => 
    array (
      'arg_1' => 
      array (
        'value' => 'firstname',
        'type' => 'ident',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => 'jason',
        'type' => 'text_val',
      ),
    ),
  ),
  'all_tables' => 
  array (
    0 => 'person',
  ),
)

),
array(
'sql' => 'select * from person where surname is null',
'expected_compiled' => 'select * from `person` where `surname` is null',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'glob',
      'table' => '',
      'value' => '*',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => '*',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'person',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'person',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 'surname',
      'type' => 'ident',
    ),
    'op' => 'is',
    'arg_2' => 
    array (
      'value' => '',
      'type' => 'null',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'person',
  ),
)

),
array(
'sql' => 'select * from person where surname = \'\' and firstname = \'jason\'',
'expected_compiled' => 'select * from `person` where `surname` = \'\' and `firstname` = \'jason\'',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'glob',
      'table' => '',
      'value' => '*',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => '*',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'person',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'person',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'arg_1' => 
      array (
        'value' => 'surname',
        'type' => 'ident',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => '',
        'type' => 'text_val',
      ),
    ),
    'op' => 'and',
    'arg_2' => 
    array (
      'arg_1' => 
      array (
        'value' => 'firstname',
        'type' => 'ident',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => 'jason',
        'type' => 'text_val',
      ),
    ),
  ),
  'all_tables' => 
  array (
    0 => 'person',
  ),
)

),
array(
'sql' => 'select table_1.id, table_2.name from table_1, table_2 where table_2.table_1_id = table_1.id',
'expected_compiled' => 'select `table_1`.`id`, `table_2`.`name` from `table_1`, `table_2` where `table_2`.`table_1_id` = `table_1`.`id`',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => 'table_1',
      'value' => 'id',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'table' => 'table_2',
      'value' => 'name',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
  ),
  'column_names' => 
  array (
    0 => 'table_1.id',
    1 => 'table_2.name',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'table_names' => 
  array (
    0 => 'table_1',
    1 => 'table_2',
  ),
  'table_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'table_1',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'value' => 'table_2',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
    1 => '',
  ),
  'table_join' => 
  array (
    0 => ',',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 'table_2.table_1_id',
      'type' => 'ident',
    ),
    'op' => '=',
    'arg_2' => 
    array (
      'value' => 'table_1.id',
      'type' => 'ident',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'table_1',
    1 => 'table_2',
  ),
)

),
array(
'sql' => 'select a from table_1 where a not in (select b from table_2) limit 1',
'expected_compiled' => 'select `a` from `table_1` where `a` not in (select `b` from `table_2`) limit 1',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'a',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'a',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'table_1',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'table_1',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 'a',
      'type' => 'ident',
    ),
    'op' => 'in',
    'neg' => true,
    'arg_2' => 
    array (
      'value' => 
      array (
        'command' => 'select',
        'columns' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'table' => '',
            'value' => 'b',
            'alias' => '',
          ),
        ),
        'column_tables' => 
        array (
          0 => '',
        ),
        'column_names' => 
        array (
          0 => 'b',
        ),
        'column_aliases' => 
        array (
          0 => '',
        ),
        'table_names' => 
        array (
          0 => 'table_2',
        ),
        'table_aliases' => 
        array (
          0 => '',
        ),
        'tables' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'table_2',
            'alias' => '',
          ),
        ),
        'table_join_clause' => 
        array (
          0 => '',
        ),
      ),
      'type' => 'command',
    ),
  ),
  'limit_clause' => 
  array (
    'start' => 0,
    'length' => 1,
  ),
  'all_tables' => 
  array (
    0 => 'table_1',
    1 => 'table_2',
  ),
)

),
array(
'sql' => 'select a from table_1 where a in (select b from table_2 where c not in (select d from table_3))',
'expected_compiled' => 'select `a` from `table_1` where `a` in (select `b` from `table_2` where `c` not in (select `d` from `table_3`))',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'a',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'a',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'table_1',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'table_1',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 'a',
      'type' => 'ident',
    ),
    'op' => 'in',
    'arg_2' => 
    array (
      'value' => 
      array (
        'command' => 'select',
        'columns' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'table' => '',
            'value' => 'b',
            'alias' => '',
          ),
        ),
        'column_tables' => 
        array (
          0 => '',
        ),
        'column_names' => 
        array (
          0 => 'b',
        ),
        'column_aliases' => 
        array (
          0 => '',
        ),
        'table_names' => 
        array (
          0 => 'table_2',
        ),
        'table_aliases' => 
        array (
          0 => '',
        ),
        'tables' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'table_2',
            'alias' => '',
          ),
        ),
        'table_join_clause' => 
        array (
          0 => '',
        ),
        'where_clause' => 
        array (
          'arg_1' => 
          array (
            'value' => 'c',
            'type' => 'ident',
          ),
          'op' => 'in',
          'neg' => true,
          'arg_2' => 
          array (
            'value' => 
            array (
              'command' => 'select',
              'columns' => 
              array (
                0 => 
                array (
                  'type' => 'ident',
                  'table' => '',
                  'value' => 'd',
                  'alias' => '',
                ),
              ),
              'column_tables' => 
              array (
                0 => '',
              ),
              'column_names' => 
              array (
                0 => 'd',
              ),
              'column_aliases' => 
              array (
                0 => '',
              ),
              'table_names' => 
              array (
                0 => 'table_3',
              ),
              'table_aliases' => 
              array (
                0 => '',
              ),
              'tables' => 
              array (
                0 => 
                array (
                  'type' => 'ident',
                  'value' => 'table_3',
                  'alias' => '',
                ),
              ),
              'table_join_clause' => 
              array (
                0 => '',
              ),
            ),
            'type' => 'command',
          ),
        ),
      ),
      'type' => 'command',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'table_1',
    1 => 'table_2',
    2 => 'table_3',
  ),
)

),
array(
'sql' => 'select a from table_1 where a in (1, 2, 3)',
'expected_compiled' => 'select `a` from `table_1` where `a` in (1, 2, 3)',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'a',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'a',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'table_1',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'table_1',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 'a',
      'type' => 'ident',
    ),
    'op' => 'in',
    'arg_2' => 
    array (
      'value' => 
      array (
        0 => 1,
        1 => 2,
        2 => 3,
      ),
      'type' => 
      array (
        0 => 'int_val',
        1 => 'int_val',
        2 => 'int_val',
      ),
    ),
  ),
  'all_tables' => 
  array (
    0 => 'table_1',
  ),
)

),
array(
'sql' => 'select count(child_table.name) from parent_table ,child_table where parent_table.id = child_table.id',
'expected_compiled' => 'select count(`child_table`.`name`) from `parent_table`, `child_table` where `parent_table`.`id` = `child_table`.`id`',
'expect' => array (
  'command' => 'select',
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'count',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'child_table.name',
        ),
      ),
    ),
  ),
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'count',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'child_table.name',
          ),
        ),
      ),
      'alias' => '',
    ),
  ),
  'table_names' => 
  array (
    0 => 'parent_table',
    1 => 'child_table',
  ),
  'table_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'parent_table',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'value' => 'child_table',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
    1 => '',
  ),
  'table_join' => 
  array (
    0 => ',',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 'parent_table.id',
      'type' => 'ident',
    ),
    'op' => '=',
    'arg_2' => 
    array (
      'value' => 'child_table.id',
      'type' => 'ident',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'parent_table',
    1 => 'child_table',
  ),
)
),
array(
'sql' => 'select parent_table.name, count(child_table.name) from parent_table ,child_table where parent_table.id = child_table.id group by parent_table.name',
'expected_compiled' => 'select `parent_table`.`name`, count(`child_table`.`name`) from `parent_table`, `child_table` where `parent_table`.`id` = `child_table`.`id` group by `parent_table`.`name`',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => 'parent_table',
      'value' => 'name',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'count',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'child_table.name',
          ),
        ),
      ),
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'parent_table.name',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'count',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'child_table.name',
        ),
      ),
    ),
  ),
  'table_names' => 
  array (
    0 => 'parent_table',
    1 => 'child_table',
  ),
  'table_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'parent_table',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'value' => 'child_table',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
    1 => '',
  ),
  'table_join' => 
  array (
    0 => ',',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 'parent_table.id',
      'type' => 'ident',
    ),
    'op' => '=',
    'arg_2' => 
    array (
      'value' => 'child_table.id',
      'type' => 'ident',
    ),
  ),
  'group_by' => 
  array (
    0 => 
    array (
      'value' => 'parent_table.name',
      'type' => 'ident',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'parent_table',
    1 => 'child_table',
  ),
)


),
array(
'sql' => 'select * from cats where furry = 1 group by name, type',
'expected_compiled' => 'select * from `cats` where `furry` = 1 group by `name`, `type`',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'glob',
      'table' => '',
      'value' => '*',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => '*',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'cats',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'cats',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 'furry',
      'type' => 'ident',
    ),
    'op' => '=',
    'arg_2' => 
    array (
      'value' => 1,
      'type' => 'int_val',
    ),
  ),
  'group_by' => 
  array (
    0 => 
    array (
      'value' => 'name',
      'type' => 'ident',
    ),
    1 => 
    array (
      'value' => 'type',
      'type' => 'ident',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'cats',
  ),
)


),
array(
'sql' => 'select a, max(b) as x, sum(c) as y, min(d) as z from e',
'expected_compiled' => 'select `a`, max(`b`) as `x`, sum(`c`) as `y`, min(`d`) as `z` from `e`',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'a',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'max',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'b',
          ),
        ),
        'alias' => 'x',
      ),
      'alias' => 'x',
    ),
    2 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'sum',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'c',
          ),
        ),
        'alias' => 'y',
      ),
      'alias' => 'y',
    ),
    3 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'min',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'd',
          ),
        ),
        'alias' => 'z',
      ),
      'alias' => 'z',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'a',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'max',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'b',
        ),
      ),
      'alias' => 'x',
    ),
    1 => 
    array (
      'name' => 'sum',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'c',
        ),
      ),
      'alias' => 'y',
    ),
    2 => 
    array (
      'name' => 'min',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'd',
        ),
      ),
      'alias' => 'z',
    ),
  ),
  'table_names' => 
  array (
    0 => 'e',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'e',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'e',
  ),
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
'expected_compiled' => 'select `clients_translation`.`id_clients_prefix`, `clients_translation`.`rule_number`, `clients_translation`.`pattern`, `clients_translation`.`rule` from `clients`, `clients_prefix`, `clients_translation` where (`clients`.`id_softswitch` = 5) and (`clients`.`id_clients` = `clients_prefix`.`id_clients`) and `clients`.`enable` = \'y\' and `clients`.`unused` = \'n\' and (`clients_translation`.`id_clients_prefix` = `clients_prefix`.`id_clients_prefix`) order by `clients_translation`.`id_clients_prefix` asc, `clients_translation`.`rule_number` asc',
'expect' =>array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => 'clients_translation',
      'value' => 'id_clients_prefix',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'table' => 'clients_translation',
      'value' => 'rule_number',
      'alias' => '',
    ),
    2 => 
    array (
      'type' => 'ident',
      'table' => 'clients_translation',
      'value' => 'pattern',
      'alias' => '',
    ),
    3 => 
    array (
      'type' => 'ident',
      'table' => 'clients_translation',
      'value' => 'rule',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
    2 => '',
    3 => '',
  ),
  'column_names' => 
  array (
    0 => 'clients_translation.id_clients_prefix',
    1 => 'clients_translation.rule_number',
    2 => 'clients_translation.pattern',
    3 => 'clients_translation.rule',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
    2 => '',
    3 => '',
  ),
  'table_names' => 
  array (
    0 => 'clients',
    1 => 'clients_prefix',
    2 => 'clients_translation',
  ),
  'table_aliases' => 
  array (
    0 => '',
    1 => '',
    2 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'clients',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'value' => 'clients_prefix',
      'alias' => '',
    ),
    2 => 
    array (
      'type' => 'ident',
      'value' => 'clients_translation',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
    1 => '',
    2 => '',
  ),
  'table_join' => 
  array (
    0 => ',',
    1 => ',',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'arg_1' => 
      array (
        'value' => 
        array (
          'arg_1' => 
          array (
            'value' => 'clients.id_softswitch',
            'type' => 'ident',
          ),
          'op' => '=',
          'arg_2' => 
          array (
            'value' => 5,
            'type' => 'int_val',
          ),
        ),
        'type' => 'subclause',
      ),
    ),
    'op' => 'and',
    'arg_2' => 
    array (
      'arg_1' => 
      array (
        'arg_1' => 
        array (
          'value' => 
          array (
            'arg_1' => 
            array (
              'value' => 'clients.id_clients',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => 'clients_prefix.id_clients',
              'type' => 'ident',
            ),
          ),
          'type' => 'subclause',
        ),
      ),
      'op' => 'and',
      'arg_2' => 
      array (
        'arg_1' => 
        array (
          'arg_1' => 
          array (
            'value' => 'clients.enable',
            'type' => 'ident',
          ),
          'op' => '=',
          'arg_2' => 
          array (
            'value' => 'y',
            'type' => 'text_val',
          ),
        ),
        'op' => 'and',
        'arg_2' => 
        array (
          'arg_1' => 
          array (
            'arg_1' => 
            array (
              'value' => 'clients.unused',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => 'n',
              'type' => 'text_val',
            ),
          ),
          'op' => 'and',
          'arg_2' => 
          array (
            'arg_1' => 
            array (
              'value' => 
              array (
                'arg_1' => 
                array (
                  'value' => 'clients_translation.id_clients_prefix',
                  'type' => 'ident',
                ),
                'op' => '=',
                'arg_2' => 
                array (
                  'value' => 'clients_prefix.id_clients_prefix',
                  'type' => 'ident',
                ),
              ),
              'type' => 'subclause',
            ),
          ),
        ),
      ),
    ),
  ),
  'sort_order' => 
  array (
    0 => 
    array (
      'value' => 'clients_translation.id_clients_prefix',
      'type' => 'ident',
      'order' => 'asc',
    ),
    1 => 
    array (
      'value' => 'clients_translation.rule_number',
      'type' => 'ident',
      'order' => 'asc',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'clients',
    1 => 'clients_prefix',
    2 => 'clients_translation',
  ),
)

),
array(
'sql' => 'SELECT column1,column2
FROM table1
WHERE (column1=\'1\' AND column2=\'1\') OR (column3=\'1\' AND column4=\'1\')',
'expected_compiled' => 'select `column1`, `column2` from `table1` where (`column1` = \'1\' and `column2` = \'1\') or (`column3` = \'1\' and `column4` = \'1\')',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'column1',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'column2',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
  ),
  'column_names' => 
  array (
    0 => 'column1',
    1 => 'column2',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'table_names' => 
  array (
    0 => 'table1',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'table1',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'arg_1' => 
      array (
        'value' => 
        array (
          'arg_1' => 
          array (
            'arg_1' => 
            array (
              'value' => 'column1',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => '1',
              'type' => 'text_val',
            ),
          ),
          'op' => 'and',
          'arg_2' => 
          array (
            'arg_1' => 
            array (
              'value' => 'column2',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => '1',
              'type' => 'text_val',
            ),
          ),
        ),
        'type' => 'subclause',
      ),
    ),
    'op' => 'or',
    'arg_2' => 
    array (
      'arg_1' => 
      array (
        'value' => 
        array (
          'arg_1' => 
          array (
            'arg_1' => 
            array (
              'value' => 'column3',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => '1',
              'type' => 'text_val',
            ),
          ),
          'op' => 'and',
          'arg_2' => 
          array (
            'arg_1' => 
            array (
              'value' => 'column4',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => '1',
              'type' => 'text_val',
            ),
          ),
        ),
        'type' => 'subclause',
      ),
    ),
  ),
  'all_tables' => 
  array (
    0 => 'table1',
  ),
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
'expected_compiled' => 'select `name` from `people` where `id` > 1 and (`name` = \'arjan\' or `name` = \'john\')',

'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'name',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'name',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'people',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'people',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'arg_1' => 
      array (
        'value' => 'id',
        'type' => 'ident',
      ),
      'op' => '>',
      'arg_2' => 
      array (
        'value' => 1,
        'type' => 'int_val',
      ),
    ),
    'op' => 'and',
    'arg_2' => 
    array (
      'arg_1' => 
      array (
        'value' => 
        array (
          'arg_1' => 
          array (
            'arg_1' => 
            array (
              'value' => 'name',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => 'arjan',
              'type' => 'text_val',
            ),
          ),
          'op' => 'or',
          'arg_2' => 
          array (
            'arg_1' => 
            array (
              'value' => 'name',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => 'john',
              'type' => 'text_val',
            ),
          ),
        ),
        'type' => 'subclause',
      ),
    ),
  ),
  'all_tables' => 
  array (
    0 => 'people',
  ),
)

),
array(
'sql' => 'select * from test where (field1 = \'x\' and field2 <>\'y\') or field3 = \'z\'',
'expected_compiled' => 'select * from `test` where (`field1` = \'x\' and `field2` <> \'y\') or `field3` = \'z\'',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'glob',
      'table' => '',
      'value' => '*',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => '*',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'test',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'test',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'arg_1' => 
      array (
        'value' => 
        array (
          'arg_1' => 
          array (
            'arg_1' => 
            array (
              'value' => 'field1',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => 'x',
              'type' => 'text_val',
            ),
          ),
          'op' => 'and',
          'arg_2' => 
          array (
            'arg_1' => 
            array (
              'value' => 'field2',
              'type' => 'ident',
            ),
            'op' => '<>',
            'arg_2' => 
            array (
              'value' => 'y',
              'type' => 'text_val',
            ),
          ),
        ),
        'type' => 'subclause',
      ),
    ),
    'op' => 'or',
    'arg_2' => 
    array (
      'arg_1' => 
      array (
        'value' => 'field3',
        'type' => 'ident',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => 'z',
        'type' => 'text_val',
      ),
    ),
  ),
  'all_tables' => 
  array (
    0 => 'test',
  ),
)

),
array(
'sql' => 'select a, d from b inner join c on b.a = c.a',
'expected_compiled' => 'select `a`, `d` from `b` inner join `c` on `b`.`a` = `c`.`a`',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'a',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'd',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
  ),
  'column_names' => 
  array (
    0 => 'a',
    1 => 'd',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'table_names' => 
  array (
    0 => 'b',
    1 => 'c',
  ),
  'table_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'b',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'value' => 'c',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
    1 => 
    array (
      'arg_1' => 
      array (
        'value' => 'b.a',
        'type' => 'ident',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => 'c.a',
        'type' => 'ident',
      ),
    ),
  ),
  'table_join' => 
  array (
    0 => 'inner join',
  ),
  'all_tables' => 
  array (
    0 => 'b',
    1 => 'c',
  ),
)

),
array(
'sql' => 'select a, d from b inner join c on b.a = c.a left outer join q on r < m',
'expected_compiled' => 'select `a`, `d` from `b` inner join `c` on `b`.`a` = `c`.`a` left outer join `q` on `r` < `m`',

'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'a',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'd',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
  ),
  'column_names' => 
  array (
    0 => 'a',
    1 => 'd',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'table_names' => 
  array (
    0 => 'b',
    1 => 'c',
    2 => 'q',
  ),
  'table_aliases' => 
  array (
    0 => '',
    1 => '',
    2 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'b',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'value' => 'c',
      'alias' => '',
    ),
    2 => 
    array (
      'type' => 'ident',
      'value' => 'q',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
    1 => 
    array (
      'arg_1' => 
      array (
        'value' => 'b.a',
        'type' => 'ident',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => 'c.a',
        'type' => 'ident',
      ),
    ),
    2 => 
    array (
      'arg_1' => 
      array (
        'value' => 'r',
        'type' => 'ident',
      ),
      'op' => '<',
      'arg_2' => 
      array (
        'value' => 'm',
        'type' => 'ident',
      ),
    ),
  ),
  'table_join' => 
  array (
    0 => 'inner join',
    1 => 'left outer join',
  ),
  'all_tables' => 
  array (
    0 => 'b',
    1 => 'c',
    2 => 'q',
  ),
)

),
array(
'sql' => 'select a, length(a) as __a_length from Foo',
'expected_compiled' => 'select `a`, length(`a`) as `__a_length` from `Foo`',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'a',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'length',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'a',
          ),
        ),
        'alias' => '__a_length',
      ),
      'alias' => '__a_length',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'a',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'length',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'a',
        ),
      ),
      'alias' => '__a_length',
    ),
  ),
  'table_names' => 
  array (
    0 => 'Foo',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'Foo',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'Foo',
  ),
)

	
),
array(
'sql' => 'select a, length(a) as __a_length from Foo where abs(b)>c',
'expected_compiled' => 'select `a`, length(`a`) as `__a_length` from `Foo` where abs(`b`) > `c`',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'a',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'length',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'a',
          ),
        ),
        'alias' => '__a_length',
      ),
      'alias' => '__a_length',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'a',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'length',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'a',
        ),
      ),
      'alias' => '__a_length',
    ),
  ),
  'table_names' => 
  array (
    0 => 'Foo',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'Foo',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 
      array (
        'name' => 'abs',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'b',
          ),
        ),
      ),
      'type' => 'function',
    ),
    'op' => '>',
    'arg_2' => 
    array (
      'value' => 'c',
      'type' => 'ident',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'Foo',
  ),
)

	
),
array(
'sql' => 'select a, length(a) as __a_length from Foo where abs(length(b))>c',
'expected_compiled' => 'select `a`, length(`a`) as `__a_length` from `Foo` where abs(length(`b`)) > `c`',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'a',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'length',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'a',
          ),
        ),
        'alias' => '__a_length',
      ),
      'alias' => '__a_length',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'a',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'length',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'a',
        ),
      ),
      'alias' => '__a_length',
    ),
  ),
  'table_names' => 
  array (
    0 => 'Foo',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'Foo',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 
      array (
        'name' => 'abs',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'function',
            'value' => 
            array (
              'name' => 'length',
              'args' => 
              array (
                0 => 
                array (
                  'type' => 'ident',
                  'value' => 'b',
                ),
              ),
            ),
          ),
        ),
      ),
      'type' => 'function',
    ),
    'op' => '>',
    'arg_2' => 
    array (
      'value' => 'c',
      'type' => 'ident',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'Foo',
  ),
)
	
),
array(
'sql' => 'select a, length(a) as __a_length from Foo where abs(length(b))>abs(length(c))',
'expected_compiled' => 'select `a`, length(`a`) as `__a_length` from `Foo` where abs(length(`b`)) > abs(length(`c`))',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'a',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'length',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'a',
          ),
        ),
        'alias' => '__a_length',
      ),
      'alias' => '__a_length',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'a',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'length',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'a',
        ),
      ),
      'alias' => '__a_length',
    ),
  ),
  'table_names' => 
  array (
    0 => 'Foo',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'Foo',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 
      array (
        'name' => 'abs',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'function',
            'value' => 
            array (
              'name' => 'length',
              'args' => 
              array (
                0 => 
                array (
                  'type' => 'ident',
                  'value' => 'b',
                ),
              ),
            ),
          ),
        ),
      ),
      'type' => 'function',
    ),
    'op' => '>',
    'arg_2' => 
    array (
      'value' => 
      array (
        'name' => 'abs',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'function',
            'value' => 
            array (
              'name' => 'length',
              'args' => 
              array (
                0 => 
                array (
                  'type' => 'ident',
                  'value' => 'c',
                ),
              ),
            ),
          ),
        ),
      ),
      'type' => 'function',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'Foo',
  ),
)

	
),
array(
'sql' => 'select a, length(a) as __a_length from Foo where abs(length(b))>abs(length(c)) order by year(`date`)',
'expected_compiled' => 'select `a`, length(`a`) as `__a_length` from `Foo` where abs(length(`b`)) > abs(length(`c`)) order by year(`date`) asc',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'a',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'length',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'a',
          ),
        ),
        'alias' => '__a_length',
      ),
      'alias' => '__a_length',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'a',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'length',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'a',
        ),
      ),
      'alias' => '__a_length',
    ),
  ),
  'table_names' => 
  array (
    0 => 'Foo',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'Foo',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 
      array (
        'name' => 'abs',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'function',
            'value' => 
            array (
              'name' => 'length',
              'args' => 
              array (
                0 => 
                array (
                  'type' => 'ident',
                  'value' => 'b',
                ),
              ),
            ),
          ),
        ),
      ),
      'type' => 'function',
    ),
    'op' => '>',
    'arg_2' => 
    array (
      'value' => 
      array (
        'name' => 'abs',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'function',
            'value' => 
            array (
              'name' => 'length',
              'args' => 
              array (
                0 => 
                array (
                  'type' => 'ident',
                  'value' => 'c',
                ),
              ),
            ),
          ),
        ),
      ),
      'type' => 'function',
    ),
  ),
  'sort_order' => 
  array (
    0 => 
    array (
      'value' => 
      array (
        'name' => 'year',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'date',
          ),
        ),
      ),
      'type' => 'function',
      'order' => 'asc',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'Foo',
  ),
)

),
array(
'sql' => 'select name, institution from Degrees where Degrees.profileid=\'$id\'',
'expected_compiled' => 'select `name`, `institution` from `Degrees` where `Degrees`.`profileid` = \'$id\'',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'name',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'institution',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
  ),
  'column_names' => 
  array (
    0 => 'name',
    1 => 'institution',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'table_names' => 
  array (
    0 => 'Degrees',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'Degrees',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 'Degrees.profileid',
      'type' => 'ident',
    ),
    'op' => '=',
    'arg_2' => 
    array (
      'value' => '$id',
      'type' => 'text_val',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'Degrees',
  ),
)

),
array(
'sql' => 'select name, institution from Degrees where match (`Institution`) AGAINST ("Home")',
'expected_compiled' => 'select `name`, `institution` from `Degrees` where match (`Institution`) against (\'Home\')',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'name',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'institution',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
  ),
  'column_names' => 
  array (
    0 => 'name',
    1 => 'institution',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'table_names' => 
  array (
    0 => 'Degrees',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'Degrees',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'type' => 'match',
      'value' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'Institution',
        ),
      ),
      'against' => 'Home',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'Degrees',
  ),
)

),
array(
'sql' => 'select name, institution, pg.Name from Degrees left join (select * from Programs) as pg on pg.degreeid = Degrees.degreeid where match (`Institution`) AGAINST ("Home")',
'expected_compiled' => 'select `name`, `institution`, `pg`.`Name` from `Degrees` left join (select * from `Programs`) as `pg` on `pg`.`degreeid` = `Degrees`.`degreeid` where match (`Institution`) against (\'Home\')',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'name',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'institution',
      'alias' => '',
    ),
    2 => 
    array (
      'type' => 'ident',
      'table' => 'pg',
      'value' => 'Name',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
    2 => '',
  ),
  'column_names' => 
  array (
    0 => 'name',
    1 => 'institution',
    2 => 'pg.Name',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
    2 => '',
  ),
  'table_names' => 
  array (
    0 => 'Degrees',
  ),
  'table_aliases' => 
  array (
    0 => '',
    1 => 'pg',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'Degrees',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'subselect',
      'value' => 
      array (
        'command' => 'select',
        'columns' => 
        array (
          0 => 
          array (
            'type' => 'glob',
            'table' => '',
            'value' => '*',
            'alias' => '',
          ),
        ),
        'column_tables' => 
        array (
          0 => '',
        ),
        'column_names' => 
        array (
          0 => '*',
        ),
        'column_aliases' => 
        array (
          0 => '',
        ),
        'table_names' => 
        array (
          0 => 'Programs',
        ),
        'table_aliases' => 
        array (
          0 => '',
        ),
        'tables' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'Programs',
            'alias' => '',
          ),
        ),
        'table_join_clause' => 
        array (
          0 => '',
        ),
      ),
      'alias' => 'pg',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
    1 => 
    array (
      'arg_1' => 
      array (
        'value' => 'pg.degreeid',
        'type' => 'ident',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => 'Degrees.degreeid',
        'type' => 'ident',
      ),
    ),
  ),
  'table_join' => 
  array (
    0 => 'left join',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'type' => 'match',
      'value' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'Institution',
        ),
      ),
      'against' => 'Home',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'Degrees',
    1 => 'Programs',
  ),
)

),
array(
'sql' => 'select * from pages where date_sub(now(), interval 1 day) < ExpiryDate',
'expected_compiled' => 'select * from `pages` where date_sub(now(), interval 1 day) < `ExpiryDate`',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'glob',
      'table' => '',
      'value' => '*',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => '*',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'pages',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'pages',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 
      array (
        'name' => 'date_sub',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'function',
            'value' => 
            array (
              'name' => 'now',
              'args' => 
              array (
              ),
            ),
          ),
          1 => 
          array (
            'type' => 'interval',
            'value' => 1,
            'expression_type' => 'int_val',
            'unit' => 'day',
          ),
        ),
      ),
      'type' => 'function',
    ),
    'op' => '<',
    'arg_2' => 
    array (
      'value' => 'ExpiryDate',
      'type' => 'ident',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'pages',
  ),
)

),
array(
'sql' => 'SELECT q.questionSeries, (SELECT COUNT(tmp_ID) FROM demo_user_response d LEFT JOIN `questions` q ON d.question_id = q.question_id WHERE d.user_id = 1 ) as countAnswered FROM tblPDemoUserInfo q WHERE q.ID = 2',
'expected_compiled' => 'select `q`.`questionSeries`, (select count(`tmp_ID`) from `demo_user_response` as `d` LEFT join `questions` as `q` on `d`.`question_id` = `q`.`question_id` where `d`.`user_id` = 1) as `countAnswered` from `tblPDemoUserInfo` as `q` where `q`.`ID` = 2',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => 'q',
      'value' => 'questionSeries',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'subselect',
      'table' => '',
      'value' => 
      array (
        'command' => 'select',
        'set_function' => 
        array (
          0 => 
          array (
            'name' => 'count',
            'args' => 
            array (
              0 => 
              array (
                'type' => 'ident',
                'value' => 'tmp_ID',
              ),
            ),
          ),
        ),
        'columns' => 
        array (
          0 => 
          array (
            'type' => 'func',
            'table' => '',
            'value' => 
            array (
              'name' => 'count',
              'args' => 
              array (
                0 => 
                array (
                  'type' => 'ident',
                  'value' => 'tmp_ID',
                ),
              ),
            ),
            'alias' => '',
          ),
        ),
        'table_names' => 
        array (
          0 => 'demo_user_response',
          1 => 'questions',
        ),
        'table_aliases' => 
        array (
          0 => 'd',
          1 => 'q',
        ),
        'tables' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'demo_user_response',
            'alias' => 'd',
          ),
          1 => 
          array (
            'type' => 'ident',
            'value' => 'questions',
            'alias' => 'q',
          ),
        ),
        'table_join_clause' => 
        array (
          0 => '',
          1 => 
          array (
            'arg_1' => 
            array (
              'value' => 'd.question_id',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => 'q.question_id',
              'type' => 'ident',
            ),
          ),
        ),
        'table_join' => 
        array (
          0 => 'LEFT join',
        ),
        'where_clause' => 
        array (
          'arg_1' => 
          array (
            'value' => 'd.user_id',
            'type' => 'ident',
          ),
          'op' => '=',
          'arg_2' => 
          array (
            'value' => 1,
            'type' => 'int_val',
          ),
        ),
      ),
      'alias' => 'countAnswered',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'q.questionSeries',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'tblPDemoUserInfo',
  ),
  'table_aliases' => 
  array (
    0 => 'q',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'tblPDemoUserInfo',
      'alias' => 'q',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 'q.ID',
      'type' => 'ident',
    ),
    'op' => '=',
    'arg_2' => 
    array (
      'value' => 2,
      'type' => 'int_val',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'demo_user_response',
    1 => 'questions',
    2 => 'tblPDemoUserInfo',
  ),
)


),
array(
'sql' => 'SELECT avg( userVSScore ) as AVGSCOR, avg(maxVsScore) as AVGMAXSCOR , (avg( userVSScore )/avg(maxVsScore))*1 as avgVs FROM tblUserSectionsScore WHERE userId =2 AND year( scoreDate )=3 AND MONTH( scoreDate )=4 GROUP BY MONTH( scoreDate ),year( scoreDate ) ORDER BY YEAR(scoreDate), MONTH(scoreDate)',
'expected_compiled' => 'select avg(`userVSScore`) as `AVGSCOR`, avg(`maxVsScore`) as `AVGMAXSCOR`, ((avg(`userVSScore`)/avg(`maxVsScore`))*1) as `avgVs` from `tblUserSectionsScore` where `userId` = 2 and year(`scoreDate`) = 3 and month(`scoreDate`) = 4 group by month(`scoreDate`), year(`scoreDate`) order by year(`scoreDate`) asc, month(`scoreDate`) asc',
'expect' => array (
  'command' => 'select',
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'avg',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'userVSScore',
        ),
      ),
      'alias' => 'AVGSCOR',
    ),
    1 => 
    array (
      'name' => 'avg',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'maxVsScore',
        ),
      ),
      'alias' => 'AVGMAXSCOR',
    ),
  ),
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'avg',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'userVSScore',
          ),
        ),
        'alias' => 'AVGSCOR',
      ),
      'alias' => 'AVGSCOR',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'avg',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'maxVsScore',
          ),
        ),
        'alias' => 'AVGMAXSCOR',
      ),
      'alias' => 'AVGMAXSCOR',
    ),
    2 => 
    array (
      'type' => 'expression',
      'value' => 
      array (
        0 => 
        array (
          'type' => 'expression',
          'value' => 
          array (
            0 => 
            array (
              'type' => 'func',
              'table' => '',
              'value' => 
              array (
                'name' => 'avg',
                'args' => 
                array (
                  0 => 
                  array (
                    'type' => 'ident',
                    'value' => 'userVSScore',
                  ),
                ),
              ),
              'alias' => '',
            ),
            1 => 
            array (
              'type' => 'operator',
              'value' => '/',
            ),
            2 => 
            array (
              'type' => 'func',
              'table' => '',
              'value' => 
              array (
                'name' => 'avg',
                'args' => 
                array (
                  0 => 
                  array (
                    'type' => 'ident',
                    'value' => 'maxVsScore',
                  ),
                ),
              ),
              'alias' => '',
            ),
          ),
        ),
        1 => 
        array (
          'type' => 'operator',
          'value' => '*',
        ),
        2 => 
        array (
          'type' => 'int_val',
          'value' => 1,
        ),
      ),
      'alias' => 'avgVs',
    ),
  ),
  'table_names' => 
  array (
    0 => 'tblUserSectionsScore',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'tblUserSectionsScore',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'arg_1' => 
      array (
        'value' => 'userId',
        'type' => 'ident',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => 2,
        'type' => 'int_val',
      ),
    ),
    'op' => 'and',
    'arg_2' => 
    array (
      'arg_1' => 
      array (
        'arg_1' => 
        array (
          'value' => 
          array (
            'name' => 'year',
            'args' => 
            array (
              0 => 
              array (
                'type' => 'ident',
                'value' => 'scoreDate',
              ),
            ),
          ),
          'type' => 'function',
        ),
        'op' => '=',
        'arg_2' => 
        array (
          'value' => 3,
          'type' => 'int_val',
        ),
      ),
      'op' => 'and',
      'arg_2' => 
      array (
        'arg_1' => 
        array (
          'value' => 
          array (
            'name' => 'month',
            'args' => 
            array (
              0 => 
              array (
                'type' => 'ident',
                'value' => 'scoreDate',
              ),
            ),
          ),
          'type' => 'function',
        ),
        'op' => '=',
        'arg_2' => 
        array (
          'value' => 4,
          'type' => 'int_val',
        ),
      ),
    ),
  ),
  'group_by' => 
  array (
    0 => 
    array (
      'value' => 
      array (
        'name' => 'month',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'scoreDate',
          ),
        ),
      ),
      'type' => 'function',
    ),
    1 => 
    array (
      'value' => 
      array (
        'name' => 'year',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'scoreDate',
          ),
        ),
      ),
      'type' => 'function',
    ),
  ),
  'sort_order' => 
  array (
    0 => 
    array (
      'value' => 
      array (
        'name' => 'year',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'scoreDate',
          ),
        ),
      ),
      'type' => 'function',
      'order' => 'asc',
    ),
    1 => 
    array (
      'value' => 
      array (
        'name' => 'month',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'scoreDate',
          ),
        ),
      ),
      'type' => 'function',
      'order' => 'asc',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'tblUserSectionsScore',
  ),
)


),

array(
'sql' => "SELECT ROUND(((avg(userVSScore)/avg(maxVsScore))*1),2) AS totalVs FROM tblUserSectionsScore where userId IN(3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22) and DATE_FORMAT( scoreDate, '_1_' )='_2_' GROUP BY MONTH( scoreDate ), year( scoreDate ) ORDER BY YEAR(scoreDate), MONTH(scoreDate)",
'expected_compiled' => "select round(((avg(`userVSScore`)/avg(`maxVsScore`))*1), 2) as `totalVs` from `tblUserSectionsScore` where `userId` in (3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22) and date_format(`scoreDate`, '_1_') = '_2_' group by month(`scoreDate`), year(`scoreDate`) order by year(`scoreDate`) asc, month(`scoreDate`) asc",
'expect' => array (
  'command' => 'select',
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'round',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'expression',
          'value' => 
          array (
            0 => 
            array (
              'type' => 'expression',
              'value' => 
              array (
                0 => 
                array (
                  'type' => 'func',
                  'table' => '',
                  'value' => 
                  array (
                    'name' => 'avg',
                    'args' => 
                    array (
                      0 => 
                      array (
                        'type' => 'ident',
                        'value' => 'userVSScore',
                      ),
                    ),
                  ),
                  'alias' => '',
                ),
                1 => 
                array (
                  'type' => 'operator',
                  'value' => '/',
                ),
                2 => 
                array (
                  'type' => 'func',
                  'table' => '',
                  'value' => 
                  array (
                    'name' => 'avg',
                    'args' => 
                    array (
                      0 => 
                      array (
                        'type' => 'ident',
                        'value' => 'maxVsScore',
                      ),
                    ),
                  ),
                  'alias' => '',
                ),
              ),
            ),
            1 => 
            array (
              'type' => 'operator',
              'value' => '*',
            ),
            2 => 
            array (
              'type' => 'int_val',
              'value' => 1,
            ),
          ),
        ),
        1 => 
        array (
          'type' => 'int_val',
          'value' => 2,
        ),
      ),
      'alias' => 'totalVs',
    ),
  ),
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'round',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'expression',
            'value' => 
            array (
              0 => 
              array (
                'type' => 'expression',
                'value' => 
                array (
                  0 => 
                  array (
                    'type' => 'func',
                    'table' => '',
                    'value' => 
                    array (
                      'name' => 'avg',
                      'args' => 
                      array (
                        0 => 
                        array (
                          'type' => 'ident',
                          'value' => 'userVSScore',
                        ),
                      ),
                    ),
                    'alias' => '',
                  ),
                  1 => 
                  array (
                    'type' => 'operator',
                    'value' => '/',
                  ),
                  2 => 
                  array (
                    'type' => 'func',
                    'table' => '',
                    'value' => 
                    array (
                      'name' => 'avg',
                      'args' => 
                      array (
                        0 => 
                        array (
                          'type' => 'ident',
                          'value' => 'maxVsScore',
                        ),
                      ),
                    ),
                    'alias' => '',
                  ),
                ),
              ),
              1 => 
              array (
                'type' => 'operator',
                'value' => '*',
              ),
              2 => 
              array (
                'type' => 'int_val',
                'value' => 1,
              ),
            ),
          ),
          1 => 
          array (
            'type' => 'int_val',
            'value' => 2,
          ),
        ),
        'alias' => 'totalVs',
      ),
      'alias' => 'totalVs',
    ),
  ),
  'table_names' => 
  array (
    0 => 'tblUserSectionsScore',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'tblUserSectionsScore',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'arg_1' => 
      array (
        'value' => 'userId',
        'type' => 'ident',
      ),
      'op' => 'in',
      'arg_2' => 
      array (
        'value' => 
        array (
          0 => 3,
          1 => 4,
          2 => 5,
          3 => 6,
          4 => 7,
          5 => 8,
          6 => 9,
          7 => 10,
          8 => 11,
          9 => 12,
          10 => 13,
          11 => 14,
          12 => 15,
          13 => 16,
          14 => 17,
          15 => 18,
          16 => 19,
          17 => 20,
          18 => 21,
          19 => 22,
        ),
        'type' => 
        array (
          0 => 'int_val',
          1 => 'int_val',
          2 => 'int_val',
          3 => 'int_val',
          4 => 'int_val',
          5 => 'int_val',
          6 => 'int_val',
          7 => 'int_val',
          8 => 'int_val',
          9 => 'int_val',
          10 => 'int_val',
          11 => 'int_val',
          12 => 'int_val',
          13 => 'int_val',
          14 => 'int_val',
          15 => 'int_val',
          16 => 'int_val',
          17 => 'int_val',
          18 => 'int_val',
          19 => 'int_val',
        ),
      ),
    ),
    'op' => 'and',
    'arg_2' => 
    array (
      'arg_1' => 
      array (
        'value' => 
        array (
          'name' => 'date_format',
          'args' => 
          array (
            0 => 
            array (
              'type' => 'ident',
              'value' => 'scoreDate',
            ),
            1 => 
            array (
              'type' => 'text_val',
              'value' => '_1_',
            ),
          ),
        ),
        'type' => 'function',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => '_2_',
        'type' => 'text_val',
      ),
    ),
  ),
  'group_by' => 
  array (
    0 => 
    array (
      'value' => 
      array (
        'name' => 'month',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'scoreDate',
          ),
        ),
      ),
      'type' => 'function',
    ),
    1 => 
    array (
      'value' => 
      array (
        'name' => 'year',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'scoreDate',
          ),
        ),
      ),
      'type' => 'function',
    ),
  ),
  'sort_order' => 
  array (
    0 => 
    array (
      'value' => 
      array (
        'name' => 'year',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'scoreDate',
          ),
        ),
      ),
      'type' => 'function',
      'order' => 'asc',
    ),
    1 => 
    array (
      'value' => 
      array (
        'name' => 'month',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'scoreDate',
          ),
        ),
      ),
      'type' => 'function',
      'order' => 'asc',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'tblUserSectionsScore',
  ),
)


),


array(
'sql' => 'SELECT distinct usec.sectionId, usec.bActivate, s.section_desc FROM `tblUserSectionStatus` uss LEFT JOIN tblUserSections usec ON uss.userSectionId = usec.Id LEFT JOIN `sections` s ON usec.sectionId = s.section_id WHERE usec.processId = 8 AND usec.userId=142 AND usec.bActivate=1 AND uss.bCompleted=0 AND (MONTH(uss.sectionDate)= MONTH(CURRENT_DATE) and YEAR(uss.sectionDate)= YEAR(CURRENT_DATE)) ORDER BY s.section_desc',
'expected_compiled' => "select distinct `usec`.`sectionId`, `usec`.`bActivate`, `s`.`section_desc` from `tblUserSectionStatus` as `uss` LEFT join `tblUserSections` as `usec` on `uss`.`userSectionId` = `usec`.`Id` LEFT join `sections` as `s` on `usec`.`sectionId` = `s`.`section_id` where `usec`.`processId` = 8 and `usec`.`userId` = 142 and `usec`.`bActivate` = 1 and `uss`.`bCompleted` = 0 and (month(`uss`.`sectionDate`) = month(current_date) and year(`uss`.`sectionDate`) = year(current_date)) order by `s`.`section_desc` asc",
'expect' => array (
  'command' => 'select',
  'set_quantifier' => 'distinct',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => 'usec',
      'value' => 'sectionId',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'ident',
      'table' => 'usec',
      'value' => 'bActivate',
      'alias' => '',
    ),
    2 => 
    array (
      'type' => 'ident',
      'table' => 's',
      'value' => 'section_desc',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
    2 => '',
  ),
  'column_names' => 
  array (
    0 => 'usec.sectionId',
    1 => 'usec.bActivate',
    2 => 's.section_desc',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
    2 => '',
  ),
  'table_names' => 
  array (
    0 => 'tblUserSectionStatus',
    1 => 'tblUserSections',
    2 => 'sections',
  ),
  'table_aliases' => 
  array (
    0 => 'uss',
    1 => 'usec',
    2 => 's',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'tblUserSectionStatus',
      'alias' => 'uss',
    ),
    1 => 
    array (
      'type' => 'ident',
      'value' => 'tblUserSections',
      'alias' => 'usec',
    ),
    2 => 
    array (
      'type' => 'ident',
      'value' => 'sections',
      'alias' => 's',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
    1 => 
    array (
      'arg_1' => 
      array (
        'value' => 'uss.userSectionId',
        'type' => 'ident',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => 'usec.Id',
        'type' => 'ident',
      ),
    ),
    2 => 
    array (
      'arg_1' => 
      array (
        'value' => 'usec.sectionId',
        'type' => 'ident',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => 's.section_id',
        'type' => 'ident',
      ),
    ),
  ),
  'table_join' => 
  array (
    0 => 'LEFT join',
    1 => 'LEFT join',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'arg_1' => 
      array (
        'value' => 'usec.processId',
        'type' => 'ident',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => 8,
        'type' => 'int_val',
      ),
    ),
    'op' => 'and',
    'arg_2' => 
    array (
      'arg_1' => 
      array (
        'arg_1' => 
        array (
          'value' => 'usec.userId',
          'type' => 'ident',
        ),
        'op' => '=',
        'arg_2' => 
        array (
          'value' => 142,
          'type' => 'int_val',
        ),
      ),
      'op' => 'and',
      'arg_2' => 
      array (
        'arg_1' => 
        array (
          'arg_1' => 
          array (
            'value' => 'usec.bActivate',
            'type' => 'ident',
          ),
          'op' => '=',
          'arg_2' => 
          array (
            'value' => 1,
            'type' => 'int_val',
          ),
        ),
        'op' => 'and',
        'arg_2' => 
        array (
          'arg_1' => 
          array (
            'arg_1' => 
            array (
              'value' => 'uss.bCompleted',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => 0,
              'type' => 'int_val',
            ),
          ),
          'op' => 'and',
          'arg_2' => 
          array (
            'arg_1' => 
            array (
              'value' => 
              array (
                'arg_1' => 
                array (
                  'arg_1' => 
                  array (
                    'value' => 
                    array (
                      'name' => 'month',
                      'args' => 
                      array (
                        0 => 
                        array (
                          'type' => 'ident',
                          'value' => 'uss.sectionDate',
                        ),
                      ),
                    ),
                    'type' => 'function',
                  ),
                  'op' => '=',
                  'arg_2' => 
                  array (
                    'value' => 
                    array (
                      'name' => 'month',
                      'args' => 
                      array (
                        0 => 
                        array (
                          'type' => 'constant',
                          'value' => 'current_date',
                        ),
                      ),
                    ),
                    'type' => 'function',
                  ),
                ),
                'op' => 'and',
                'arg_2' => 
                array (
                  'arg_1' => 
                  array (
                    'value' => 
                    array (
                      'name' => 'year',
                      'args' => 
                      array (
                        0 => 
                        array (
                          'type' => 'ident',
                          'value' => 'uss.sectionDate',
                        ),
                      ),
                    ),
                    'type' => 'function',
                  ),
                  'op' => '=',
                  'arg_2' => 
                  array (
                    'value' => 
                    array (
                      'name' => 'year',
                      'args' => 
                      array (
                        0 => 
                        array (
                          'type' => 'constant',
                          'value' => 'current_date',
                        ),
                      ),
                    ),
                    'type' => 'function',
                  ),
                ),
              ),
              'type' => 'subclause',
            ),
          ),
        ),
      ),
    ),
  ),
  'sort_order' => 
  array (
    0 => 
    array (
      'value' => 's.section_desc',
      'type' => 'ident',
      'order' => 'asc',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'tblUserSectionStatus',
    1 => 'tblUserSections',
    2 => 'sections',
  ),
)


),

array(
'sql' => "SELECT question_id FROM `questions` WHERE section_id=1 AND (timing='_1_' || timing='_2_')",
'expected_compiled' => "select `question_id` from `questions` where `section_id` = 1 and (`timing` = '_1_' || `timing` = '_2_')",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'question_id',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'question_id',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'questions',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'questions',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'arg_1' => 
      array (
        'value' => 'section_id',
        'type' => 'ident',
      ),
      'op' => '=',
      'arg_2' => 
      array (
        'value' => 1,
        'type' => 'int_val',
      ),
    ),
    'op' => 'and',
    'arg_2' => 
    array (
      'arg_1' => 
      array (
        'value' => 
        array (
          'arg_1' => 
          array (
            'arg_1' => 
            array (
              'value' => 'timing',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => '_1_',
              'type' => 'text_val',
            ),
          ),
          'op' => '||',
          'arg_2' => 
          array (
            'arg_1' => 
            array (
              'value' => 'timing',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => '_2_',
              'type' => 'text_val',
            ),
          ),
        ),
        'type' => 'subclause',
      ),
    ),
  ),
  'all_tables' => 
  array (
    0 => 'questions',
  ),
)


),

array(
'sql' => 'SELECT Response FROM user_responses WHERE Response_ID = (select max(Response_ID) FROM user_responses WHERE Question_ID=1 AND User_ID = 2 AND MONTH(dateCreated)=3 AND YEAR(dateCreated)=4) Limit 5',
'expected_compiled' => 'select `Response` from `user_responses` where `Response_ID` = (select max(`Response_ID`) from `user_responses` where `Question_ID` = 1 and `User_ID` = 2 and month(`dateCreated`) = 3 and year(`dateCreated`) = 4) limit 5',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'Response',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'Response',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'user_responses',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'user_responses',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 'Response_ID',
      'type' => 'ident',
    ),
    'op' => '=',
    'arg_2' => 
    array (
      'value' => 
      array (
        'command' => 'select',
        'set_function' => 
        array (
          0 => 
          array (
            'name' => 'max',
            'args' => 
            array (
              0 => 
              array (
                'type' => 'ident',
                'value' => 'Response_ID',
              ),
            ),
          ),
        ),
        'columns' => 
        array (
          0 => 
          array (
            'type' => 'func',
            'table' => '',
            'value' => 
            array (
              'name' => 'max',
              'args' => 
              array (
                0 => 
                array (
                  'type' => 'ident',
                  'value' => 'Response_ID',
                ),
              ),
            ),
            'alias' => '',
          ),
        ),
        'table_names' => 
        array (
          0 => 'user_responses',
        ),
        'table_aliases' => 
        array (
          0 => '',
        ),
        'tables' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'user_responses',
            'alias' => '',
          ),
        ),
        'table_join_clause' => 
        array (
          0 => '',
        ),
        'where_clause' => 
        array (
          'arg_1' => 
          array (
            'arg_1' => 
            array (
              'value' => 'Question_ID',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => 1,
              'type' => 'int_val',
            ),
          ),
          'op' => 'and',
          'arg_2' => 
          array (
            'arg_1' => 
            array (
              'arg_1' => 
              array (
                'value' => 'User_ID',
                'type' => 'ident',
              ),
              'op' => '=',
              'arg_2' => 
              array (
                'value' => 2,
                'type' => 'int_val',
              ),
            ),
            'op' => 'and',
            'arg_2' => 
            array (
              'arg_1' => 
              array (
                'arg_1' => 
                array (
                  'value' => 
                  array (
                    'name' => 'month',
                    'args' => 
                    array (
                      0 => 
                      array (
                        'type' => 'ident',
                        'value' => 'dateCreated',
                      ),
                    ),
                  ),
                  'type' => 'function',
                ),
                'op' => '=',
                'arg_2' => 
                array (
                  'value' => 3,
                  'type' => 'int_val',
                ),
              ),
              'op' => 'and',
              'arg_2' => 
              array (
                'arg_1' => 
                array (
                  'value' => 
                  array (
                    'name' => 'year',
                    'args' => 
                    array (
                      0 => 
                      array (
                        'type' => 'ident',
                        'value' => 'dateCreated',
                      ),
                    ),
                  ),
                  'type' => 'function',
                ),
                'op' => '=',
                'arg_2' => 
                array (
                  'value' => 4,
                  'type' => 'int_val',
                ),
              ),
            ),
          ),
        ),
      ),
      'type' => 'subquery',
    ),
  ),
  'limit_clause' => 
  array (
    'start' => 0,
    'length' => 5,
  ),
  'all_tables' => 
  array (
    0 => 'user_responses',
    1 => 'user_responses',
  ),
)


),

array(
'sql' => "select s.* from students s",
'expected_compiled' => 'select `s`.* from `students` as `s`',
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'glob',
      'table' => 's',
      'value' => '*',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 's.*',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'students',
  ),
  'table_aliases' => 
  array (
    0 => 's',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'students',
      'alias' => 's',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'students',
  ),
)

),

array(
'sql' => "SELECT COUNT(*) FROM (SELECT abstracts.*, conference.ConferenceProceedingsTableofContentsURL FROM abstracts LEFT JOIN conference ON abstracts.AbstractConferenceID = conference.ConferenceID) as `abstracts` WHERE EXISTS (select `a`.`author_id`, `a`.`author_email`, `a`.`author_name`, `a`.`author_organization`, `a`.`author_url1`, `a`.`author_url2`, `a`.`conference_ids`, `a`.`course_ids`, `a`.`private`, convert_tz(`a`.`LastModified`,'SYSTEM','-07:00') as `LastModified`, `a`.`owner_id`, `aa`.`author_id`, `aa`.`abstract_id`, `aa`.`author_order` from (SELECT authors.*, users.email AS author_email FROM authors LEFT JOIN users ON authors.owner_id = users.user_id) as `a`, `authors_abstracts` as `aa` where `a`.`author_id` = `aa`.`author_id` and `aa`.`abstract_id` = `abstracts`.`AbstractID` AND `author_name` LIKE CONCAT('%','Steve Hannah','%'))",
'expected_compiled' => "select count(*) from (select `abstracts`.*, `conference`.`ConferenceProceedingsTableofContentsURL` from `abstracts` LEFT join `conference` on `abstracts`.`AbstractConferenceID` = `conference`.`ConferenceID`) as `abstracts` where exists (select `a`.`author_id`, `a`.`author_email`, `a`.`author_name`, `a`.`author_organization`, `a`.`author_url1`, `a`.`author_url2`, `a`.`conference_ids`, `a`.`course_ids`, `a`.`private`, convert_tz(`a`.`LastModified`, 'SYSTEM', '-07:00') as `LastModified`, `a`.`owner_id`, `aa`.`author_id`, `aa`.`abstract_id`, `aa`.`author_order` from (select `authors`.*, `users`.`email` as `author_email` from `authors` LEFT join `users` on `authors`.`owner_id` = `users`.`user_id`) as `a`, `authors_abstracts` as `aa` where `a`.`author_id` = `aa`.`author_id` and `aa`.`abstract_id` = `abstracts`.`AbstractID` and `author_name` like concat('%', 'Steve Hannah', '%'))",
'expect' => array (
  'command' => 'select',
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'count',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => '*',
        ),
      ),
    ),
  ),
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'count',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => '*',
          ),
        ),
      ),
      'alias' => '',
    ),
  ),
  'table_aliases' => 
  array (
    0 => 'abstracts',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'subselect',
      'value' => 
      array (
        'command' => 'select',
        'columns' => 
        array (
          0 => 
          array (
            'type' => 'glob',
            'table' => 'abstracts',
            'value' => '*',
            'alias' => '',
          ),
          1 => 
          array (
            'type' => 'ident',
            'table' => 'conference',
            'value' => 'ConferenceProceedingsTableofContentsURL',
            'alias' => '',
          ),
        ),
        'column_tables' => 
        array (
          0 => '',
          1 => '',
        ),
        'column_names' => 
        array (
          0 => 'abstracts.*',
          1 => 'conference.ConferenceProceedingsTableofContentsURL',
        ),
        'column_aliases' => 
        array (
          0 => '',
          1 => '',
        ),
        'table_names' => 
        array (
          0 => 'abstracts',
          1 => 'conference',
        ),
        'table_aliases' => 
        array (
          0 => '',
          1 => '',
        ),
        'tables' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'abstracts',
            'alias' => '',
          ),
          1 => 
          array (
            'type' => 'ident',
            'value' => 'conference',
            'alias' => '',
          ),
        ),
        'table_join_clause' => 
        array (
          0 => '',
          1 => 
          array (
            'arg_1' => 
            array (
              'value' => 'abstracts.AbstractConferenceID',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => 'conference.ConferenceID',
              'type' => 'ident',
            ),
          ),
        ),
        'table_join' => 
        array (
          0 => 'LEFT join',
        ),
      ),
      'alias' => 'abstracts',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'exists' => true,
    'arg_1' => 
    array (
      'value' => 
      array (
        'command' => 'select',
        'columns' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'table' => 'a',
            'value' => 'author_id',
            'alias' => '',
          ),
          1 => 
          array (
            'type' => 'ident',
            'table' => 'a',
            'value' => 'author_email',
            'alias' => '',
          ),
          2 => 
          array (
            'type' => 'ident',
            'table' => 'a',
            'value' => 'author_name',
            'alias' => '',
          ),
          3 => 
          array (
            'type' => 'ident',
            'table' => 'a',
            'value' => 'author_organization',
            'alias' => '',
          ),
          4 => 
          array (
            'type' => 'ident',
            'table' => 'a',
            'value' => 'author_url1',
            'alias' => '',
          ),
          5 => 
          array (
            'type' => 'ident',
            'table' => 'a',
            'value' => 'author_url2',
            'alias' => '',
          ),
          6 => 
          array (
            'type' => 'ident',
            'table' => 'a',
            'value' => 'conference_ids',
            'alias' => '',
          ),
          7 => 
          array (
            'type' => 'ident',
            'table' => 'a',
            'value' => 'course_ids',
            'alias' => '',
          ),
          8 => 
          array (
            'type' => 'ident',
            'table' => 'a',
            'value' => 'private',
            'alias' => '',
          ),
          9 => 
          array (
            'type' => 'func',
            'table' => '',
            'value' => 
            array (
              'name' => 'convert_tz',
              'args' => 
              array (
                0 => 
                array (
                  'type' => 'ident',
                  'value' => 'a.LastModified',
                ),
                1 => 
                array (
                  'type' => 'text_val',
                  'value' => 'SYSTEM',
                ),
                2 => 
                array (
                  'type' => 'text_val',
                  'value' => '-07:00',
                ),
              ),
              'alias' => 'LastModified',
            ),
            'alias' => 'LastModified',
          ),
          10 => 
          array (
            'type' => 'ident',
            'table' => 'a',
            'value' => 'owner_id',
            'alias' => '',
          ),
          11 => 
          array (
            'type' => 'ident',
            'table' => 'aa',
            'value' => 'author_id',
            'alias' => '',
          ),
          12 => 
          array (
            'type' => 'ident',
            'table' => 'aa',
            'value' => 'abstract_id',
            'alias' => '',
          ),
          13 => 
          array (
            'type' => 'ident',
            'table' => 'aa',
            'value' => 'author_order',
            'alias' => '',
          ),
        ),
        'column_tables' => 
        array (
          0 => '',
          1 => '',
          2 => '',
          3 => '',
          4 => '',
          5 => '',
          6 => '',
          7 => '',
          8 => '',
          9 => '',
          10 => '',
          11 => '',
          12 => '',
        ),
        'column_names' => 
        array (
          0 => 'a.author_id',
          1 => 'a.author_email',
          2 => 'a.author_name',
          3 => 'a.author_organization',
          4 => 'a.author_url1',
          5 => 'a.author_url2',
          6 => 'a.conference_ids',
          7 => 'a.course_ids',
          8 => 'a.private',
          9 => 'a.owner_id',
          10 => 'aa.author_id',
          11 => 'aa.abstract_id',
          12 => 'aa.author_order',
        ),
        'column_aliases' => 
        array (
          0 => '',
          1 => '',
          2 => '',
          3 => '',
          4 => '',
          5 => '',
          6 => '',
          7 => '',
          8 => '',
          9 => '',
          10 => '',
          11 => '',
          12 => '',
        ),
        'set_function' => 
        array (
          0 => 
          array (
            'name' => 'convert_tz',
            'args' => 
            array (
              0 => 
              array (
                'type' => 'ident',
                'value' => 'a.LastModified',
              ),
              1 => 
              array (
                'type' => 'text_val',
                'value' => 'SYSTEM',
              ),
              2 => 
              array (
                'type' => 'text_val',
                'value' => '-07:00',
              ),
            ),
            'alias' => 'LastModified',
          ),
        ),
        'table_aliases' => 
        array (
          0 => 'a',
          1 => 'aa',
        ),
        'tables' => 
        array (
          0 => 
          array (
            'type' => 'subselect',
            'value' => 
            array (
              'command' => 'select',
              'columns' => 
              array (
                0 => 
                array (
                  'type' => 'glob',
                  'table' => 'authors',
                  'value' => '*',
                  'alias' => '',
                ),
                1 => 
                array (
                  'type' => 'ident',
                  'table' => 'users',
                  'value' => 'email',
                  'alias' => 'author_email',
                ),
              ),
              'column_tables' => 
              array (
                0 => '',
                1 => '',
              ),
              'column_names' => 
              array (
                0 => 'authors.*',
                1 => 'users.email',
              ),
              'column_aliases' => 
              array (
                0 => '',
                1 => 'author_email',
              ),
              'table_names' => 
              array (
                0 => 'authors',
                1 => 'users',
              ),
              'table_aliases' => 
              array (
                0 => '',
                1 => '',
              ),
              'tables' => 
              array (
                0 => 
                array (
                  'type' => 'ident',
                  'value' => 'authors',
                  'alias' => '',
                ),
                1 => 
                array (
                  'type' => 'ident',
                  'value' => 'users',
                  'alias' => '',
                ),
              ),
              'table_join_clause' => 
              array (
                0 => '',
                1 => 
                array (
                  'arg_1' => 
                  array (
                    'value' => 'authors.owner_id',
                    'type' => 'ident',
                  ),
                  'op' => '=',
                  'arg_2' => 
                  array (
                    'value' => 'users.user_id',
                    'type' => 'ident',
                  ),
                ),
              ),
              'table_join' => 
              array (
                0 => 'LEFT join',
              ),
            ),
            'alias' => 'a',
          ),
          1 => 
          array (
            'type' => 'ident',
            'value' => 'authors_abstracts',
            'alias' => 'aa',
          ),
        ),
        'table_join_clause' => 
        array (
          0 => '',
          1 => '',
        ),
        'table_join' => 
        array (
          0 => ',',
        ),
        'table_names' => 
        array (
          0 => 'authors_abstracts',
        ),
        'where_clause' => 
        array (
          'arg_1' => 
          array (
            'arg_1' => 
            array (
              'value' => 'a.author_id',
              'type' => 'ident',
            ),
            'op' => '=',
            'arg_2' => 
            array (
              'value' => 'aa.author_id',
              'type' => 'ident',
            ),
          ),
          'op' => 'and',
          'arg_2' => 
          array (
            'arg_1' => 
            array (
              'arg_1' => 
              array (
                'value' => 'aa.abstract_id',
                'type' => 'ident',
              ),
              'op' => '=',
              'arg_2' => 
              array (
                'value' => 'abstracts.AbstractID',
                'type' => 'ident',
              ),
            ),
            'op' => 'and',
            'arg_2' => 
            array (
              'arg_1' => 
              array (
                'value' => 'author_name',
                'type' => 'ident',
              ),
              'op' => 'like',
              'arg_2' => 
              array (
                'value' => 
                array (
                  'name' => 'concat',
                  'args' => 
                  array (
                    0 => 
                    array (
                      'type' => 'text_val',
                      'value' => '%',
                    ),
                    1 => 
                    array (
                      'type' => 'text_val',
                      'value' => 'Steve Hannah',
                    ),
                    2 => 
                    array (
                      'type' => 'text_val',
                      'value' => '%',
                    ),
                  ),
                ),
                'type' => 'function',
              ),
            ),
          ),
        ),
      ),
      'type' => 'subquery',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'abstracts',
    1 => 'conference',
    2 => 'authors',
    3 => 'users',
    4 => 'authors_abstracts',
  ),
)


),


array(
'sql' => "select * from annuaire_enligne WHERE nom NOT REGEXP '[0-9]{4}' ORDER BY nom",
'expected_compiled' => "select * from `annuaire_enligne` where `nom` not regexp '[0-9]{4}' order by `nom` asc",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'glob',
      'table' => '',
      'value' => '*',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => '*',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'annuaire_enligne',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'annuaire_enligne',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 'nom',
      'type' => 'ident',
    ),
    'op' => 'regexp',
    'neg' => true,
    'arg_2' => 
    array (
      'value' => '[0-9]{4}',
      'type' => 'text_val',
    ),
  ),
  'sort_order' => 
  array (
    0 => 
    array (
      'value' => 'nom',
      'type' => 'ident',
      'order' => 'asc',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'annuaire_enligne',
  ),
)


),


array(
'sql' => "select person_id, concat(surname,', ',given_names) as fullname from people order by fullname",
'expected_compiled' => "select `person_id`, concat(`surname`, ', ', `given_names`) as `fullname` from `people` order by `fullname` asc",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'person_id',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'concat',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'surname',
          ),
          1 => 
          array (
            'type' => 'text_val',
            'value' => ', ',
          ),
          2 => 
          array (
            'type' => 'ident',
            'value' => 'given_names',
          ),
        ),
        'alias' => 'fullname',
      ),
      'alias' => 'fullname',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'person_id',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'concat',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'surname',
        ),
        1 => 
        array (
          'type' => 'text_val',
          'value' => ', ',
        ),
        2 => 
        array (
          'type' => 'ident',
          'value' => 'given_names',
        ),
      ),
      'alias' => 'fullname',
    ),
  ),
  'table_names' => 
  array (
    0 => 'people',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'people',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'sort_order' => 
  array (
    0 => 
    array (
      'value' => 'fullname',
      'type' => 'ident',
      'order' => 'asc',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'people',
  ),
)


),

array(
'sql' => "select dog, 1, cat from animals",
'expected_compiled' => "select `dog`, 1, `cat` from `animals`",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'dog',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'int_val',
      'table' => '',
      'value' => 1,
      'alias' => '',
    ),
    2 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'cat',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
    2 => '',
  ),
  'column_names' => 
  array (
    0 => 'dog',
    1 => 1,
    2 => 'cat',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
    2 => '',
  ),
  'table_names' => 
  array (
    0 => 'animals',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'animals',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'animals',
  ),
)



),

array(
'sql' => "select dog, 1 as foo, cat from animals",
'expected_compiled' => "select `dog`, 1 as `foo`, `cat` from `animals`",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'dog',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'int_val',
      'table' => '',
      'value' => 1,
      'alias' => 'foo',
    ),
    2 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'cat',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
    2 => '',
  ),
  'column_names' => 
  array (
    0 => 'dog',
    1 => 1,
    2 => 'cat',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => 'foo',
    2 => '',
  ),
  'table_names' => 
  array (
    0 => 'animals',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'animals',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'animals',
  ),
)




),

array(
'sql' => "select dog, 1+2 as foo, cat from animals",
'expected_compiled' => "select `dog`, (1+2) as `foo`, `cat` from `animals`",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'dog',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'expression',
      'value' => 
      array (
        0 => 
        array (
          'type' => 'int_val',
          'table' => '',
          'value' => 1,
          'alias' => '',
        ),
        1 => 
        array (
          'type' => 'operator',
          'value' => '+',
        ),
        2 => 
        array (
          'type' => 'int_val',
          'value' => 2,
        ),
      ),
      'alias' => 'foo',
    ),
    2 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'cat',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
    2 => '',
  ),
  'column_names' => 
  array (
    0 => 'dog',
    1 => 1,
    2 => 'cat',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => 'foo',
    2 => '',
  ),
  'table_names' => 
  array (
    0 => 'animals',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'animals',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'animals',
  ),
)





),


array(
'sql' => "select dog, foo+2 as foo, cat from animals",
'expected_compiled' => "select `dog`, (`foo`+2) as `foo`, `cat` from `animals`",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'dog',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'expression',
      'value' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'table' => '',
          'value' => 'foo',
          'alias' => '',
        ),
        1 => 
        array (
          'type' => 'operator',
          'value' => '+',
        ),
        2 => 
        array (
          'type' => 'int_val',
          'value' => 2,
        ),
      ),
      'alias' => 'foo',
    ),
    2 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'cat',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
    2 => '',
  ),
  'column_names' => 
  array (
    0 => 'dog',
    1 => 'foo',
    2 => 'cat',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => 'foo',
    2 => '',
  ),
  'table_names' => 
  array (
    0 => 'animals',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'animals',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'animals',
  ),
)






),

array(
'sql' => "select dog, ifnull(bar,1+2) as foo, cat from animals",
'expected_compiled' => "select `dog`, ifnull(`bar`, (1+2)) as `foo`, `cat` from `animals`",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'dog',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'ifnull',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'bar',
          ),
          1 => 
          array (
            'type' => 'expression',
            'value' => 
            array (
              0 => 
              array (
                'type' => 'int_val',
                'value' => 1,
              ),
              1 => 
              array (
                'type' => 'operator',
                'value' => '+',
              ),
              2 => 
              array (
                'type' => 'int_val',
                'value' => 2,
              ),
            ),
          ),
        ),
        'alias' => 'foo',
      ),
      'alias' => 'foo',
    ),
    2 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'cat',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
  ),
  'column_names' => 
  array (
    0 => 'dog',
    1 => 'cat',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'ifnull',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'bar',
        ),
        1 => 
        array (
          'type' => 'expression',
          'value' => 
          array (
            0 => 
            array (
              'type' => 'int_val',
              'value' => 1,
            ),
            1 => 
            array (
              'type' => 'operator',
              'value' => '+',
            ),
            2 => 
            array (
              'type' => 'int_val',
              'value' => 2,
            ),
          ),
        ),
      ),
      'alias' => 'foo',
    ),
  ),
  'table_names' => 
  array (
    0 => 'animals',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'animals',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'animals',
  ),
)





),



array(
'sql' => "select dog, ifnull(bar,1+foo) as foo, cat from animals",
'expected_compiled' => "select `dog`, ifnull(`bar`, (1+`foo`)) as `foo`, `cat` from `animals`",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'dog',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'ifnull',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'bar',
          ),
          1 => 
          array (
            'type' => 'expression',
            'value' => 
            array (
              0 => 
              array (
                'type' => 'int_val',
                'value' => 1,
              ),
              1 => 
              array (
                'type' => 'operator',
                'value' => '+',
              ),
              2 => 
              array (
                'type' => 'ident',
                'table' => '',
                'value' => 'foo',
                'alias' => '',
              ),
            ),
          ),
        ),
        'alias' => 'foo',
      ),
      'alias' => 'foo',
    ),
    2 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'cat',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
  ),
  'column_names' => 
  array (
    0 => 'dog',
    1 => 'cat',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'ifnull',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'bar',
        ),
        1 => 
        array (
          'type' => 'expression',
          'value' => 
          array (
            0 => 
            array (
              'type' => 'int_val',
              'value' => 1,
            ),
            1 => 
            array (
              'type' => 'operator',
              'value' => '+',
            ),
            2 => 
            array (
              'type' => 'ident',
              'table' => '',
              'value' => 'foo',
              'alias' => '',
            ),
          ),
        ),
      ),
      'alias' => 'foo',
    ),
  ),
  'table_names' => 
  array (
    0 => 'animals',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'animals',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'animals',
  ),
)






),

array(
'sql' => "select dog, ifnull(bar,foo+1) as foo, cat from animals",
'expected_compiled' => "select `dog`, ifnull(`bar`, (`foo`+1)) as `foo`, `cat` from `animals`",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'dog',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'ifnull',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'bar',
          ),
          1 => 
          array (
            'type' => 'expression',
            'value' => 
            array (
              0 => 
              array (
                'type' => 'ident',
                'value' => 'foo',
              ),
              1 => 
              array (
                'type' => 'operator',
                'value' => '+',
              ),
              2 => 
              array (
                'type' => 'int_val',
                'value' => 1,
              ),
            ),
          ),
        ),
        'alias' => 'foo',
      ),
      'alias' => 'foo',
    ),
    2 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'cat',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
  ),
  'column_names' => 
  array (
    0 => 'dog',
    1 => 'cat',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'ifnull',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'bar',
        ),
        1 => 
        array (
          'type' => 'expression',
          'value' => 
          array (
            0 => 
            array (
              'type' => 'ident',
              'value' => 'foo',
            ),
            1 => 
            array (
              'type' => 'operator',
              'value' => '+',
            ),
            2 => 
            array (
              'type' => 'int_val',
              'value' => 1,
            ),
          ),
        ),
      ),
      'alias' => 'foo',
    ),
  ),
  'table_names' => 
  array (
    0 => 'animals',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'animals',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'animals',
  ),
)




),

array(
'sql' => "select dog, ifnull(bar,length(foo)+1) as foo, cat from animals",
'expected_compiled' => "select `dog`, ifnull(`bar`, (length(`foo`)+1)) as `foo`, `cat` from `animals`",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'dog',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'ifnull',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'bar',
          ),
          1 => 
          array (
            'type' => 'expression',
            'value' => 
            array (
              0 => 
              array (
                'type' => 'function',
                'value' => 
                array (
                  'name' => 'length',
                  'args' => 
                  array (
                    0 => 
                    array (
                      'type' => 'ident',
                      'value' => 'foo',
                    ),
                  ),
                ),
              ),
              1 => 
              array (
                'type' => 'operator',
                'value' => '+',
              ),
              2 => 
              array (
                'type' => 'int_val',
                'value' => 1,
              ),
            ),
          ),
        ),
        'alias' => 'foo',
      ),
      'alias' => 'foo',
    ),
    2 => 
    array (
      'type' => 'ident',
      'table' => '',
      'value' => 'cat',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
    1 => '',
  ),
  'column_names' => 
  array (
    0 => 'dog',
    1 => 'cat',
  ),
  'column_aliases' => 
  array (
    0 => '',
    1 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'ifnull',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'bar',
        ),
        1 => 
        array (
          'type' => 'expression',
          'value' => 
          array (
            0 => 
            array (
              'type' => 'function',
              'value' => 
              array (
                'name' => 'length',
                'args' => 
                array (
                  0 => 
                  array (
                    'type' => 'ident',
                    'value' => 'foo',
                  ),
                ),
              ),
            ),
            1 => 
            array (
              'type' => 'operator',
              'value' => '+',
            ),
            2 => 
            array (
              'type' => 'int_val',
              'value' => 1,
            ),
          ),
        ),
      ),
      'alias' => 'foo',
    ),
  ),
  'table_names' => 
  array (
    0 => 'animals',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'animals',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'animals',
  ),
)




),


array(
'sql' => "SELECT * FROM articles WHERE article_format_id IN (1,2)",
'expected_compiled' => "select * from `articles` where `article_format_id` in (1, 2)",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'glob',
      'table' => '',
      'value' => '*',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => '*',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'articles',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'articles',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 'article_format_id',
      'type' => 'ident',
    ),
    'op' => 'in',
    'arg_2' => 
    array (
      'value' => 
      array (
        0 => 1,
        1 => 2,
      ),
      'type' => 
      array (
        0 => 'int_val',
        1 => 'int_val',
      ),
    ),
  ),
  'all_tables' => 
  array (
    0 => 'articles',
  ),
)





),



array(
'sql' => "SELECT p.*, TIMEDIFF(timeStop, timeStart) AS hours FROM pto p",
'expected_compiled' => "select `p`.*, timediff(`timeStop`, `timeStart`) as `hours` from `pto` as `p`",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'glob',
      'table' => 'p',
      'value' => '*',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'timediff',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'timeStop',
          ),
          1 => 
          array (
            'type' => 'ident',
            'value' => 'timeStart',
          ),
        ),
        'alias' => 'hours',
      ),
      'alias' => 'hours',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'p.*',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'timediff',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'timeStop',
        ),
        1 => 
        array (
          'type' => 'ident',
          'value' => 'timeStart',
        ),
      ),
      'alias' => 'hours',
    ),
  ),
  'table_names' => 
  array (
    0 => 'pto',
  ),
  'table_aliases' => 
  array (
    0 => 'p',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'pto',
      'alias' => 'p',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'pto',
  ),
)






),



array(
'sql' => "SELECT p.*, DATEDIFF(ptoDate,CURDATE()) AS hours FROM pto p",
'expected_compiled' => "select `p`.*, datediff(`ptoDate`, curdate()) as `hours` from `pto` as `p`",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'glob',
      'table' => 'p',
      'value' => '*',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'datediff',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'ident',
            'value' => 'ptoDate',
          ),
          1 => 
          array (
            'type' => 'function',
            'value' => 
            array (
              'name' => 'curdate',
              'args' => 
              array (
              ),
            ),
          ),
        ),
        'alias' => 'hours',
      ),
      'alias' => 'hours',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'p.*',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'datediff',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'ident',
          'value' => 'ptoDate',
        ),
        1 => 
        array (
          'type' => 'function',
          'value' => 
          array (
            'name' => 'curdate',
            'args' => 
            array (
            ),
          ),
        ),
      ),
      'alias' => 'hours',
    ),
  ),
  'table_names' => 
  array (
    0 => 'pto',
  ),
  'table_aliases' => 
  array (
    0 => 'p',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'pto',
      'alias' => 'p',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'pto',
  ),
)




),



array(
'sql' => "SELECT p.*, FORMAT(TIME_TO_SEC(TIMEDIFF(timeStop,timeStart))/3600,2) AS hours FROM pto p",
'expected_compiled' => "select `p`.*, format((time_to_sec(timediff(`timeStop`, `timeStart`))/3600), 2) as `hours` from `pto` as `p`",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'glob',
      'table' => 'p',
      'value' => '*',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'format',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'expression',
            'value' => 
            array (
              0 => 
              array (
                'type' => 'function',
                'value' => 
                array (
                  'name' => 'time_to_sec',
                  'args' => 
                  array (
                    0 => 
                    array (
                      'type' => 'function',
                      'value' => 
                      array (
                        'name' => 'timediff',
                        'args' => 
                        array (
                          0 => 
                          array (
                            'type' => 'ident',
                            'value' => 'timeStop',
                          ),
                          1 => 
                          array (
                            'type' => 'ident',
                            'value' => 'timeStart',
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              ),
              1 => 
              array (
                'type' => 'operator',
                'value' => '/',
              ),
              2 => 
              array (
                'type' => 'int_val',
                'value' => 3600,
              ),
            ),
          ),
          1 => 
          array (
            'type' => 'int_val',
            'value' => 2,
          ),
        ),
        'alias' => 'hours',
      ),
      'alias' => 'hours',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => 'p.*',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'format',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'expression',
          'value' => 
          array (
            0 => 
            array (
              'type' => 'function',
              'value' => 
              array (
                'name' => 'time_to_sec',
                'args' => 
                array (
                  0 => 
                  array (
                    'type' => 'function',
                    'value' => 
                    array (
                      'name' => 'timediff',
                      'args' => 
                      array (
                        0 => 
                        array (
                          'type' => 'ident',
                          'value' => 'timeStop',
                        ),
                        1 => 
                        array (
                          'type' => 'ident',
                          'value' => 'timeStart',
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ),
            1 => 
            array (
              'type' => 'operator',
              'value' => '/',
            ),
            2 => 
            array (
              'type' => 'int_val',
              'value' => 3600,
            ),
          ),
        ),
        1 => 
        array (
          'type' => 'int_val',
          'value' => 2,
        ),
      ),
      'alias' => 'hours',
    ),
  ),
  'table_names' => 
  array (
    0 => 'pto',
  ),
  'table_aliases' => 
  array (
    0 => 'p',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'pto',
      'alias' => 'p',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'pto',
  ),
)





),


array(
'sql' => "SELECT * , DATE_ADD( STR_TO_DATE( Datum, '%Y-%m-%d' ) , INTERVAL 2 year ) AS Ablaufdatum FROM TnMed pc",
'expected_compiled' => "select *, date_add(str_to_date(`Datum`, '%Y-%m-%d'), interval 2 year) as `Ablaufdatum` from `TnMed` as `pc`",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'glob',
      'table' => '',
      'value' => '*',
      'alias' => '',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'date_add',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'function',
            'value' => 
            array (
              'name' => 'str_to_date',
              'args' => 
              array (
                0 => 
                array (
                  'type' => 'ident',
                  'value' => 'Datum',
                ),
                1 => 
                array (
                  'type' => 'text_val',
                  'value' => '%Y-%m-%d',
                ),
              ),
            ),
          ),
          1 => 
          array (
            'type' => 'interval',
            'value' => 2,
            'expression_type' => 'int_val',
            'unit' => 'year',
          ),
        ),
        'alias' => 'Ablaufdatum',
      ),
      'alias' => 'Ablaufdatum',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => '*',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'date_add',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'function',
          'value' => 
          array (
            'name' => 'str_to_date',
            'args' => 
            array (
              0 => 
              array (
                'type' => 'ident',
                'value' => 'Datum',
              ),
              1 => 
              array (
                'type' => 'text_val',
                'value' => '%Y-%m-%d',
              ),
            ),
          ),
        ),
        1 => 
        array (
          'type' => 'interval',
          'value' => 2,
          'expression_type' => 'int_val',
          'unit' => 'year',
        ),
      ),
      'alias' => 'Ablaufdatum',
    ),
  ),
  'table_names' => 
  array (
    0 => 'TnMed',
  ),
  'table_aliases' => 
  array (
    0 => 'pc',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'TnMed',
      'alias' => 'pc',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'all_tables' => 
  array (
    0 => 'TnMed',
  ),
)





),

array(
'sql' => "select CONCAT_WS(':',a.cat_pid,a.cat_subid) as catid,CONCAT_WS(': ',b.cat_name,a.cat_name) as categoryname from options_subcategories as a,options_categories as b where b.cat_id=a.cat_pid order by categoryname",
'expected_compiled' => "select concat_ws(':', `a`.`cat_pid`, `a`.`cat_subid`) as `catid`, concat_ws(': ', `b`.`cat_name`, `a`.`cat_name`) as `categoryname` from `options_subcategories` as `a`, `options_categories` as `b` where `b`.`cat_id` = `a`.`cat_pid` order by `categoryname` asc",
'expect' => array (
  'command' => 'select',
  'set_function' => 
  array (
    0 => 
    array (
      'name' => 'concat_ws',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'text_val',
          'value' => ':',
        ),
        1 => 
        array (
          'type' => 'ident',
          'value' => 'a.cat_pid',
        ),
        2 => 
        array (
          'type' => 'ident',
          'value' => 'a.cat_subid',
        ),
      ),
      'alias' => 'catid',
    ),
    1 => 
    array (
      'name' => 'concat_ws',
      'args' => 
      array (
        0 => 
        array (
          'type' => 'text_val',
          'value' => ': ',
        ),
        1 => 
        array (
          'type' => 'ident',
          'value' => 'b.cat_name',
        ),
        2 => 
        array (
          'type' => 'ident',
          'value' => 'a.cat_name',
        ),
      ),
      'alias' => 'categoryname',
    ),
  ),
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'concat_ws',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'text_val',
            'value' => ':',
          ),
          1 => 
          array (
            'type' => 'ident',
            'value' => 'a.cat_pid',
          ),
          2 => 
          array (
            'type' => 'ident',
            'value' => 'a.cat_subid',
          ),
        ),
        'alias' => 'catid',
      ),
      'alias' => 'catid',
    ),
    1 => 
    array (
      'type' => 'func',
      'table' => '',
      'value' => 
      array (
        'name' => 'concat_ws',
        'args' => 
        array (
          0 => 
          array (
            'type' => 'text_val',
            'value' => ': ',
          ),
          1 => 
          array (
            'type' => 'ident',
            'value' => 'b.cat_name',
          ),
          2 => 
          array (
            'type' => 'ident',
            'value' => 'a.cat_name',
          ),
        ),
        'alias' => 'categoryname',
      ),
      'alias' => 'categoryname',
    ),
  ),
  'table_names' => 
  array (
    0 => 'options_subcategories',
    1 => 'options_categories',
  ),
  'table_aliases' => 
  array (
    0 => 'a',
    1 => 'b',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'options_subcategories',
      'alias' => 'a',
    ),
    1 => 
    array (
      'type' => 'ident',
      'value' => 'options_categories',
      'alias' => 'b',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
    1 => '',
  ),
  'table_join' => 
  array (
    0 => ',',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 'b.cat_id',
      'type' => 'ident',
    ),
    'op' => '=',
    'arg_2' => 
    array (
      'value' => 'a.cat_pid',
      'type' => 'ident',
    ),
  ),
  'sort_order' => 
  array (
    0 => 
    array (
      'value' => 'categoryname',
      'type' => 'ident',
      'order' => 'asc',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'options_subcategories',
    1 => 'options_categories',
  ),
)



),


array(
'sql' => "select * from foo where 1",
'expected_compiled' => "select * from `foo` where 1",
'expect' => array (
  'command' => 'select',
  'columns' => 
  array (
    0 => 
    array (
      'type' => 'glob',
      'table' => '',
      'value' => '*',
      'alias' => '',
    ),
  ),
  'column_tables' => 
  array (
    0 => '',
  ),
  'column_names' => 
  array (
    0 => '*',
  ),
  'column_aliases' => 
  array (
    0 => '',
  ),
  'table_names' => 
  array (
    0 => 'foo',
  ),
  'table_aliases' => 
  array (
    0 => '',
  ),
  'tables' => 
  array (
    0 => 
    array (
      'type' => 'ident',
      'value' => 'foo',
      'alias' => '',
    ),
  ),
  'table_join_clause' => 
  array (
    0 => '',
  ),
  'where_clause' => 
  array (
    'arg_1' => 
    array (
      'value' => 1,
      'type' => 'int_val',
    ),
  ),
  'all_tables' => 
  array (
    0 => 'foo',
  ),
)





),

);
