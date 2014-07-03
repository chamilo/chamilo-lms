<?php

$config = new \Doctrine\ORM\Configuration();
$config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;

$sysPath = __DIR__."/";

AnnotationRegistry::registerFile($sysPath."vendor/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php");
$reader = new AnnotationReader();

$driverImpl = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, array($sysPath."tests/doctrine_console/mapping"));

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

use Doctrine\ORM\Tools\Console\ConsoleRunner;
return ConsoleRunner::createHelperSet($em);
