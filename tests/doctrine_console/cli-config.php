<?php
require_once dirname(__FILE__).'/../../main/inc/global.inc.php';
error_reporting(-1);

$config = new \Doctrine\ORM\Configuration();
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

AnnotationRegistry::registerFile(api_get_path(SYS_PATH)."vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php");
$reader = new AnnotationReader();

$driverImpl = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, array(api_get_path(SYS_PATH)."tests/doctrine_console/mapping"));
$config->setMetadataDriverImpl($driverImpl);
$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');

$connectionOptions = array(
    'driver'    => 'pdo_mysql',
    'dbname'    => $_configuration['main_database'],
    'user'      => $_configuration['db_user'],
    'password'  => $_configuration['db_password'],
    'host'      => $_configuration['db_host'],
);

$em = \Doctrine\ORM\EntityManager::create($connectionOptions, $config);

//Fixes some errors
$platform = $em->getConnection()->getDatabasePlatform();
$platform->registerDoctrineTypeMapping('enum', 'string');
$platform->registerDoctrineTypeMapping('set', 'string');

$helpers = array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em)
);

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

