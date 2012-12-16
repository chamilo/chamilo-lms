<?php

require_once dirname(__FILE__).'/../../main/inc/global.inc.php';

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

cd /var/www/chamilo11


Delete old mappings

sudo rm -R tests/doctrine_console/mapping tests/doctrine_console/generated/

First creating the mapping from the DB

sudo mkdir tests/doctrine_console/mapping

You can add a Namespace if you want to with: --namespace "Entity"
sudo php5 tests/doctrine_console/doctrine.php orm:convert-mapping --from-database annotation tests/doctrine_console/mapping  --namespace "Entity"

Generate entities

mkdir tests/doctrine_console/generated

sudo php5 tests/doctrine_console/doctrine.php orm:generate-entities   --generate-annotations="true"   tests/doctrine_console/generated

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


*/


/*
$reader = new AnnotationReader();
$driverImpl = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, array($entity['path']));
$config->setMetadataDriverImpl($driverImpl);
*/