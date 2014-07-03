<?php
/* For licensing terms, see /license.txt */

$config = new \Doctrine\ORM\Configuration();
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Yaml\Parser;

$sysPath = __DIR__."/../../";
AnnotationRegistry::registerFile($sysPath."vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php");
$reader = new AnnotationReader();

$driverImpl = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver(
    $reader,
    array($sysPath."tests/doctrine_console/mapping")
);

$config->setMetadataDriverImpl($driverImpl);
$config->setProxyDir(__DIR__ . '/Proxies');
$config->setProxyNamespace('Proxies');

$defaultConnection = array(
    'driver'    => 'pdo_mysql',
    'dbname'    => 'chamilo',
    'user'      => 'root',
    'password'  => 'root',
    'host'      => 'localhost',
);

$em = \Doctrine\ORM\EntityManager::create($defaultConnection, $config);

//Fixes some errors
$platform = $em->getConnection()->getDatabasePlatform();
$platform->registerDoctrineTypeMapping('enum', 'string');
$platform->registerDoctrineTypeMapping('set', 'string');
 \Doctrine\DBAL\Types\Type::addType('json', 'Sonata\Doctrine\Types\JsonType');
$helpers = array(
    'db' => new \Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper($em->getConnection()),
    'em' => new \Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper($em),
    'configuration' => new \Chash\Helpers\ConfigurationHelper()
);

$em = \Doctrine\ORM\EntityManager::create($defaultConnection, $config);

use Doctrine\ORM\Tools\Console\ConsoleRunner;
return ConsoleRunner::createHelperSet($em);


/*
To generate doctrine2 entities you must:

cd /var/www/chamilo/tests/doctrine_console

Delete old mappings/entities

sudo rm -R mapping generated repository

Creating the mapping from the DB

sudo mkdir mapping generated repository

You can add a Namespace if you want to with: --namespace "Entity"
sudo php5 doctrine.php orm:convert-mapping --force --from-database --namespace "Entity" annotation mapping

with no namespace
sudo php5 doctrine.php orm:convert-mapping --force --from-database annotation mapping


1. Generate entities

sudo rm -R /var/www/chamilogits/tests/doctrine_console/mapping/Class.php


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

sudo php5 doctrine.php orm:generate-proxies

*/

