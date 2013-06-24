<?php

return array(
    'db.options' => array(
        'driver' => 'pdo_mysql',
        'charset' => 'utf8',
    ),
    'myproject.test' => array(
        'param1' => '123',
        'param2' => '123',
        'param3' => array(
            'param2A' => '123',
            'param2B' => '123',
        ),
        'param4' => array(1, 2, 3),
     ),
     'test.noparent.key' => array(),
);
