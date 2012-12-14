<?php

require_once dirname(__FILE__).'/../../main/inc/global.inc.php';

$config = new \Doctrine\ORM\Configuration();
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
//$driverImpl = $config->newDefaultAnnotationDriver(array("/var/www/chamilo10/chamilo10/tests/doctrine_console/mapping"));
//$config->setMetadataDriverImpl($driverImpl);
//AnnotationRegistry::registerFile("Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php");

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

//To generate entities:
/*
 *
 * cd /var/www/chamilo11
 * Delete old mapping
 * sudo rm -R tests/doctrine_console/mapping tests/doctrine_console/generated/
 * First creating the mapping from the DB
 * cd /var/www/chamilo11
 * php5 tests/doctrine_console/doctrine.php orm:convert-mapping --from-database annotation tests/doctrine_console/mapping --namespace "Entity"
 * Generate entities
 * php5 tests/doctrine_console/doctrine.php orm:generate-entities   --generate-annotations="true"   tests/doctrine_console/generated
 *
 * mkdir main/inc/Entity
 *
 * sudo cp -R tests/doctrine_console/generated/* main/inc/Entity
 *
 * For tests
 * php5 tests/doctrine_console/doctrine.php orm:generate-entities   --generate-annotations="true"   main/inc/Entity
 *
 * Then autoload classes with composer
 * sudo php5 composer.phar update or sudo composer.phar update
 *
 *
*/

/*
$reader = new AnnotationReader();
$driverImpl = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, array($entity['path']));
$config->setMetadataDriverImpl($driverImpl);
*/