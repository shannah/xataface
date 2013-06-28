<?php
$tests = array(
array(
'sql' => 'CREATE TABLE albums (
  name varchar(60),
  directory varchar(60),
  rating enum (1,2,3,4,5,6,7,8,9,10) NOT NULL,
  category set(\'sexy\',\'\\\'family time\\\'\',"outdoors",\'generic\',\'very weird\') NULL,
  description text NULL,
  id int default 200 PRIMARY KEY
)',
'expect' => array(
        'command' => 'create_table',
        'table_names' => array(
            0 => 'albums'
            ),
        'column_defs' => array(
            'name' => array(
                'type' => 'varchar',
                'length' => 60
                ),
            'directory' => array(
                'type' => 'varchar',
                'length' => 60
                ),
            'rating' => array(
                'type' => 'enum',
                'domain' => array(
                    0 => 1,
                    1 => 2,
                    2 => 3,
                    3 => 4,
                    4 => 5,
                    5 => 6,
                    6 => 7,
                    7 => 8,
                    8 => 9,
                    9 => 10
                    ),
                'constraints' => array(
                    0 => array(
                        'type' => 'not_null',
                        'value' => true
                        )
                    )
                ),
            'category' => array(
                'type' => 'set',
                'domain' => array(
                    0 => 'sexy',
                    1 => '\'family time\'',
                    2 => 'outdoors',
                    3 => 'generic',
                    4 => 'very weird'
                    )
                ),
            'description' => array(
                'type' => 'text'
                ),
            'id' => array(
                'type' => 'int',
                'constraints' => array(
                    0 => array(
                        'type' => 'default_value',
                        'value' => 200
                        ),
                    1 => array(
                        'type' => 'primary_key',
                        'value' => true
                        )
                    )
                )
            )
        )
),
array(
'sql' => 'CREATE TABLE photos (
  filename varchar(60) not NULL,
  name varchar(60) default "no name",
  album int,
  price float (4,2),
  description text default \'hello\',
  id int default 0 primary key not null,
)',
'expect' => array(
        'command' => 'create_table',
        'table_names' => array(
            0 => 'photos'
            ),
        'column_defs' => array(
            'filename' => array(
                'type' => 'varchar',
                'length' => 60,
                'constraints' => array(
                    0 => array(
                        'type' => 'not_null',
                        'value' => true
                        )
                    )
                ),
            'name' => array(
                'type' => 'varchar',
                'length' => 60,
                'constraints' => array(
                    0 => array(
                        'type' => 'default_value',
                        'value' => 'no name'
                        )
                    )
                ),
            'album' => array(
                'type' => 'int'
                ),
            'price' => array(
                'type' => 'float',
                'length' => 4
                ),
            'description' => array(
                'type' => 'text',
                'constraints' => array(
                    0 => array(
                        'type' => 'default_value',
                        'value' => 'hello'
                        )
                    )
                ),
            'id' => array(
                'type' => 'int',
                'constraints' => array(
                    0 => array(
                        'type' => 'default_value',
                        'value' => 0
                        ),
                    1 => array(
                        'type' => 'primary_key',
                        'value' => true
                        ),
                    2 => array(
                        'type' => 'not_null',
                        'value' => true
                        )
                    )
                )
            )
        )
),
array(
'sql' => 'create table brent (
    filename varchar(10),
    description varchar(20),
)',
'expect' => array(
        'command' => 'create_table',
        'table_names' => array(
            0 => 'brent'
            ),
        'column_defs' => array(
            'filename' => array(
                'type' => 'varchar',
                'length' => 10
                ),
            'description' => array(
                'type' => 'varchar',
                'length' => 20
                )
            )
        )
),
array(
'sql' => 'CREATE TABLE films ( 
             code      CHARACTER(5) CONSTRAINT firstkey PRIMARY KEY, 
             title     CHARACTER VARYING(40) NOT NULL, 
             did       DECIMAL(3) NOT NULL, 
             date_prod DATE, 
             kind      CHAR(10), 
             len       INTERVAL HOUR TO MINUTE
             CONSTRAINT production UNIQUE(date_prod)
)',
'expect' => array(
        'command' => 'create_table',
        'table_names' => array(
            0 => 'films'
            ),
        'column_defs' => array(
            'code' => array(
                'type' => 'char',
                'length' => 5,
                'constraints' => array(
                    'firstkey' => array(
                        'type' => 'primary_key',
                        'value' => true
                        )
                    )
                ),
            'title' => array(
                'type' => 'char',
                'length' => 40,
                'constraints' => array(
                    0 => array(
                        'type' => 'not_null',
                        'value' => true
                        )
                    )
                ),
            'did' => array(
                'type' => 'numeric',
                'length' => 3,
                'constraints' => array(
                    0 => array(
                        'type' => 'not_null',
                        'value' => true
                        )
                    )
                ),
            'date_prod' => array(
                'type' => 'date'
                ),
            'kind' => array(
                'type' => 'char',
                'length' => 10
                ),
            'len' => array(
                'type' => 'interval',
                'constraints' => array(
                    0 => array(
                        'quantum_1' => 'hour',
                        'quantum_2' => 'minute',
                        'type' => 'values'
                        ),
                    'production' => array(
                        'type' => 'unique',
                        'column_names' => array(
                            0 => 'date_prod'
                            )
                        )
                    )
                )
            )
        )
),
array(
'sql' => 'CREATE TABLE films ( 
             code      CHARACTER(5) CONSTRAINT firstkey PRIMARY KEY, 
             title     CHARACTER VARYING(40) NOT NULL, 
             did       DECIMAL(3) NOT NULL, 
             date_prod DATE, 
             kind      CHAR(10), 
             len       INTERVAL minute to hour
             CONSTRAINT production UNIQUE(date_prod)
)',
'expect' => 'Parse error: hour is not smaller than minute on line 7
             len       INTERVAL minute to hour
                                          ^ found: "hour"'

),
array(
'sql' => 'CREATE TABLE distributors ( 
             did      DECIMAL(3) PRIMARY KEY DEFAULT NEXTVAL(\'serial\'), 
             name     VARCHAR(40) NOT NULL CHECK (name <> \'\') 
             CONSTRAINT con1 CHECK (did > 100 AND name > \'\') 
)',
'expect' => array(
        'command' => 'create_table',
        'table_names' => array(
            0 => 'distributors'
            ),
        'column_defs' => array(
            'did' => array(
                'type' => 'numeric',
                'length' => 3,
                'constraints' => array(
                    0 => array(
                        'type' => 'primary_key',
                        'value' => true
                        ),
                    1 => array(
                        'name' => 'nextval',
                        'args' => array(
                        	0 => array(
                        		'type' => 'text_val',
                        		'value' => 'serial'
                        		)
                        	),
                        'type' => 'default_function'
                        )
                    )
                ),
            'name' => array(
                'type' => 'varchar',
                'length' => 40,
                'constraints' => array(
                    0 => array(
                        'type' => 'not_null',
                        'value' => true
                        ),
                    1 => array(
                        'arg_1' => array(
                            'value' => 'name',
                            'type' => 'ident'
                            ),
                        'op' => '<>',
                        'arg_2' => array(
                            'value' => '',
                            'type' => 'text_val'
                            ),
                        'type' => 'check'
                        ),
                    'con1' => array(
                        'arg_1' => array(
                            'arg_1' => array(
                                'value' => 'did',
                                'type' => 'ident'
                                ),
                            'op' => '>',
                            'arg_2' => array(
                                'value' => 100,
                                'type' => 'int_val'
                                )
                            ),
                        'op' => 'and',
                        'arg_2' => array(
                            'arg_1' => array(
                                'value' => 'name',
                                'type' => 'ident'
                                ),
                            'op' => '>',
                            'arg_2' => array(
                                'value' => '',
                                'type' => 'text_val'
                                )
                            ),
                        'type' => 'check'
                        )
                    )
                )
            )
        )
),
array(
'sql' => 'CREATE TABLE distributors ( 
            did      DECIMAL(3) PRIMARY KEY, 
            name     VARCHAR(40) 
)',
'expect' => array(
        'command' => 'create_table',
        'table_names' => array(
            0 => 'distributors'
            ),
        'column_defs' => array(
            'did' => array(
                'type' => 'numeric',
                'length' => 3,
                'constraints' => array(
                    0 => array(
                        'type' => 'primary_key',
                        'value' => true
                        )
                    )
                ),
            'name' => array(
                'type' => 'varchar',
                'length' => 40
                )
            )
        )
),
array(
'sql' => 'CREATE TABLE msgs ( user_id integer, msg_id integer, msg_text varchar, msg_title varchar(30), msg_date time)',
'expect' => array(
        'command' => 'create_table',
        'table_names' => array(
            0 => 'msgs'
            ),
        'column_defs' => array(
            'user_id' => array(
                'type' => 'int'
                ),
            'msg_id' => array(
                'type' => 'int'
                ),
            'msg_text' => array(
                'type' => 'varchar'
                ),
            'msg_title' => array(
                'type' => 'varchar',
                'length' => 30
                ),
            'msg_date' => array(
                'type' => 'time'
                )
            )
        )
),
array(
'sql' => 'create table nodefinitions',
'expect' => 'Parse error: Expected ( on line 1
create table nodefinitions
                           ^ found: "*end of input*"'

),
array(
'sql' => 'create dogfood',
'expect' => 'Parse error: Unknown object to create on line 1
create dogfood
       ^ found: "dogfood"'

),
array(
'sql' => 'create table dunce (name varchar',
'expect' => 'Parse error: Expected ) on line 1
create table dunce (name varchar
                                 ^ found: "*end of input*"'

),
array(
'sql' => 'create table dunce (name varchar(2,3))',
'expect' => 'Parse error: Expected 1 parameter on line 1
create table dunce (name varchar(2,3))
                                    ^ found: ")"'

),
array(
'sql' => 'create table dunce (enum)',
'expect' => 'Parse error: Expected identifier on line 1
create table dunce (enum)
                    ^ found: "enum"'

),
array(
'sql' => 'create table dunce (enum(23))',
'expect' => 'Parse error: Expected identifier on line 1
create table dunce (enum(23))
                    ^ found: "enum"'

),
);
?>
