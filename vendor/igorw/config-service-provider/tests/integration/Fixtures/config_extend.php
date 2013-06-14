<?php

return array(
    'db.options' => array(
        'host' => '127.0.0.1',
        'dbname' => 'mydatabase',
        'user' => 'root',
        'password' => NULL,
    ),
    'myproject.test' => array(
        'param2' => '456',
        'param3' => array(
            'param2B' => '456',
            'param2C' => '456',
        ),
        'param4' => array(4, 5, 6),
        'param5' => '456',
    ),
    'test.noparent.key' => array(
        'test' => array(1, 2, 3, 4),
    ),
);
