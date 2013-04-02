<?php

/* For licensing terms, see /license.txt */

$config = new \Doctrine\ORM\Configuration();
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Yaml\Parser;

$sysPath = __DIR__."../../";

AnnotationRegistry::registerFile($sysPath."vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php");
$reader = new AnnotationReader();

$driverImpl = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, array($sysPath."tests/doctrine_console/mapping"));

$config->setMetadataDriverImpl($driverImpl);
$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');

$courseList = CourseManager::get_real_course_list();

$configurationPath = $sysPath.'main/inc/conf/';
$newConfigurationFile = $configurationPath.'configuration.yml';

//Including configuration.php
$configurationFile = $configurationPath.'configuration.php';

if (is_file($configurationFile) && file_exists($configurationFile)) {
    require $configurationFile;
}

//Merge with the configuration.yml file, if exits
if (is_file($newConfigurationFile) && file_exists($newConfigurationFile)) {
    $yaml = new Parser();
    $_configurationYML = $yaml->parse(file_get_contents($newConfigurationFile));
    if (!empty($_configurationYML)) {
        if (isset($_configuration)) {
            $_configuration = array_merge($_configuration, $_configurationYML);
        } else {
            $_configuration = $_configurationYML;
        }
    }
}

$app['chamilo.log'] = $app['cache.path'].'chamilo-cli.log';

// Loading db connections

$connectionOptions = array();

if (!empty($courseList)) {

    $dbPrefix = isset($_configuration['db_prefix']) && !empty($_configuration['db_prefix']) ? $_configuration['db_prefix'].$_configuration['db_glue'] : null;
    foreach ($courseList as $course) {
        $connectionOptions['_chamilo_course_'.$course['db_name']] = array(
            'driver'    => 'pdo_mysql',
            'dbname'    => $dbPrefix.$course['db_name'],
            'user'      => $_configuration['db_user'],
            'password'  => $_configuration['db_password'],
            'host'      => $_configuration['db_host'],
        );
    }
}

if (isset($_configuration['main_database'])) {
    $connectionOptions['main_database'] = array(
        'driver'    => 'pdo_mysql',
        'dbname'    => $_configuration['main_database'],
        'user'      => $_configuration['db_user'],
        'password'  => $_configuration['db_password'],
        'host'      => $_configuration['db_host'],
    );
}

if (isset($_configuration['statistics_database'])) {
    $connectionOptions['statistics_database'] = array(
        'driver'    => 'pdo_mysql',
        'dbname'    => $_configuration['statistics_database'],
        'user'      => $_configuration['db_user'],
        'password'  => $_configuration['db_password'],
        'host'      => $_configuration['db_host'],
    );
} else {
    if (isset($_configuration['main_database'])) {
        $connectionOptions['statistics_database'] = $connectionOptions['main_database'];
    }
}

if (isset($_configuration['user_personal_database'])) {
    $connectionOptions['user_personal_database'] = array(
        'driver'    => 'pdo_mysql',
        'dbname'    => $_configuration['user_personal_database'],
        'user'      => $_configuration['db_user'],
        'password'  => $_configuration['db_password'],
        'host'      => $_configuration['db_host'],
    );
} else {
    if (isset($_configuration['main_database'])) {
        $connectionOptions['user_personal_database'] = $connectionOptions['main_database'];
    }
}

$defaultConnection = array(
    'driver' => 'pdo_mysql'
);

if (isset($_configuration['main_database'])) {
    $defaultConnection = array(
        'driver'    => 'pdo_mysql',
        'dbname'    => $_configuration['main_database'],
        'user'      => $_configuration['db_user'],
        'password'  => $_configuration['db_password'],
        'host'      => $_configuration['db_host'],
    );
}

$em = \Doctrine\ORM\EntityManager::create($defaultConnection, $config);

//Fixes some errors
$platform = $em->getConnection()->getDatabasePlatform();
$platform->registerDoctrineTypeMapping('enum', 'string');
$platform->registerDoctrineTypeMapping('set', 'string');

$helpers = array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em),
    'configuration' => new \Chash\Helpers\ConfigurationHelper()
);

use Doctrine\DBAL\DriverManager;
$multipleEM = array();
foreach ($connectionOptions as $name => $connection) {
    $em = \Doctrine\ORM\EntityManager::create($connection, $config);
    //$helpers[$name] = new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em);
    $helpers[$name] = new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection());
}

/*
To generate doctrine2 entities you must:

cd /var/www/chamilo11/tests/doctrine_console

Delete old mappings/entities

sudo rm -R mapping generated repository

Creating the mapping from the DB

sudo mkdir mapping generated repository

You can add a Namespace if you want to with: --namespace "Entity"
sudo php5 doctrine.php orm:convert-mapping --force --from-database --namespace "Entity" annotation mapping


1. Generate entities

sudo php5 doctrine.php orm:generate-entities   --generate-annotations="true"   generated

Validate schema
sudo php5 doctrine.php orm:validate-schema -v

Move generated files in a chamilo folder:

sudo rm -R main/inc/Entity/*
mkdir main/inc/Entity

cp -R tests/doctrine_console/generated/* main/inc/Entity

fixes \ORM bug see http://redgreenrefactor.blogsite.org/php/code-first-approaching-php-with-doctrine-2-2-1-and-composer/
cd main/inc/Entity

sed -i 's/@ORM\\/@/g' *.php

For tests
php5 tests/doctrine_console/doctrine.php orm:generate-entities   --generate-annotations="true"   main/inc/Entity

Then autoload classes with composer
sudo php5 composer.phar update or sudo composer.phar update

2. Migrations

a. Generate empty migration file
cd /var/www/chamilo11/tests/doctrine_console

php5 doctrine.php migrations:generate

b. Check status

php5 doctrine.php migrations:status

c. Check sql
php5 doctrine.php migrations:migrate --dry-run

d. execute migration
php5 doctrine.php migrations:migrate

e. Revert migrations
php5 doctrine.php  migrations:migrate 0


http://docs.doctrine-project.org/projects/doctrine-migrations/en/latest/reference/managing_migrations.html

*/

