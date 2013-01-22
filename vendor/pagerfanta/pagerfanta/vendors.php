<?php

set_time_limit(0);

if (!is_dir($vendorDir = __DIR__.'/vendor')) {
    mkdir($vendorDir, 0777, true);
}

if (isset($argv[1])) {
    $_SERVER['SYMFONY_VERSION'] = $argv[1];
}

$revs = array(
    'doctrine-common' => isset($_SERVER['DOCTRINE_COMMON_REV']) ? $_SERVER['DOCTRINE_COMMON_REV'] : 'origin/master',
    'doctrine-dbal'   => isset($_SERVER['DOCTRINE_DBAL_REV']) ? $_SERVER['DOCTRINE_DBAL_REV'] : 'origin/master',
    'doctrine-orm'    => isset($_SERVER['DOCTRINE_ORM_REV']) ? $_SERVER['DOCTRINE_ORM_REV'] : 'origin/master',
);

$deps = array(
    array('symfony', 'http://github.com/symfony/symfony', 'origin/master'),
    array('mandango', 'http://github.com/mandango/mandango', 'origin/master'),
    array('doctrine-common', 'http://github.com/doctrine/common', $revs['doctrine-common']),
    array('doctrine-dbal', 'http://github.com/doctrine/dbal', $revs['doctrine-dbal']),
    array('doctrine-orm', 'http://github.com/doctrine/doctrine2', $revs['doctrine-orm']),
    array('doctrine-mongodb', 'http://github.com/doctrine/mongodb', 'origin/master'),
    array('doctrine-mongodb-odm', 'http://github.com/doctrine/mongodb-odm', 'origin/master'),
    array('propel', 'http://github.com/propelorm/Propel', 'origin/master'),
    array('solarium', 'http://github.com/basdenooijer/solarium', 'origin/master'),
);

foreach ($deps as $dep) {
    if (3 === count($dep)) {
        list($name, $url, $rev) = $dep;
        $target = null;
    } else {
        list($name, $url, $rev, $target) = $dep;
    }

    if (null === $rev) {
        $rev = 'origin/master';
    }

    if (null !== $target) {
        $installDir = $vendorDir.'/'.$target;
    } else {
        $installDir = $vendorDir.'/'.$name;
    }

    $install = false;
    if (!is_dir($installDir)) {
        $install = true;
        echo "> Installing $name\n";

        system(sprintf('git clone %s %s', escapeshellarg($url), escapeshellarg($installDir)));
    }

    if (!$install) {
        echo "> Updating $name\n";
    }

    system(sprintf('cd %s && git fetch origin && git reset --hard %s', escapeshellarg($installDir), escapeshellarg($rev)));
}
