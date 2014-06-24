#!/usr/bin/env php
<?php

// dependent libraries for test environment

define('VENDOR_PATH', __DIR__ . '/../vendor');

if (!is_dir(VENDOR_PATH)) {
    mkdir(VENDOR_PATH, 0775, true);
}

$deps = array(
    array('Symfony/Component/ClassLoader', 'http://github.com/symfony/ClassLoader.git', 'v2.0.10'),
    array('Symfony/Component/EventDispatcher', 'http://github.com/symfony/EventDispatcher.git', 'v2.0.10'),

    // doctrine 2.3.x
    array('doctrine-orm', 'http://github.com/doctrine/doctrine2.git', '304acf0a1a'),
    array('doctrine-dbal', 'http://github.com/doctrine/dbal.git', 'fd45c6f6ba'),
    array('doctrine-common', 'http://github.com/doctrine/common.git', 'bb0aebbf23'),
    array('doctrine-mongodb', 'http://github.com/doctrine/mongodb.git', 'd7fdcff25b'),
    array('doctrine-mongodb-odm', 'http://github.com/doctrine/mongodb-odm.git', 'e8c0bfb975'),

    // doctrine 2.2.x
    /*array('doctrine-orm', 'http://github.com/doctrine/doctrine2.git', 'cfe1259400'),
    array('doctrine-dbal', 'http://github.com/doctrine/dbal.git', '5a827d7c18'),
    array('doctrine-common', 'http://github.com/doctrine/common.git', '06e9f72342'),
    array('doctrine-mongodb', 'http://github.com/doctrine/mongodb.git', 'e8e1e8e474'),
    array('doctrine-mongodb-odm', 'http://github.com/doctrine/mongodb-odm.git', '5a4076ec9c'),*/

    // doctrine 2.1.x
    /*array('doctrine-orm', 'http://github.com/doctrine/doctrine2.git', '550fcbc17fc9d927edf3'),
    array('doctrine-dbal', 'http://github.com/doctrine/dbal.git', 'eb80a3797e80fbaa024bb0a1ef01c3d81bb68a76'),
    array('doctrine-common', 'http://github.com/doctrine/common.git', '73b61b50782640358940'),
    array('doctrine-mongodb', 'http://github.com/doctrine/mongodb.git', '4109734e249a951f270c531999871bfe9eeed843'),
    array('doctrine-mongodb-odm', 'http://github.com/doctrine/mongodb-odm.git', '8fb97a4740c2c12a2a5a4e7d78f0717847c39691'),
*/
);

foreach ($deps as $dep) {
    list($name, $url, $rev) = $dep;

    echo "> Installing/Updating $name\n";

    $installDir = VENDOR_PATH.'/'.$name;
    if (!is_dir($installDir)) {
        system(sprintf('git clone %s %s', $url, $installDir));
    }

    system(sprintf('cd %s && git fetch origin && git reset --hard %s', $installDir, $rev));
}
