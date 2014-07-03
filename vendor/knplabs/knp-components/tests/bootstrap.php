<?php

/**
 * This is bootstrap for phpUnit unit tests,
 * use README.md for more details
 */

if (!class_exists('PHPUnit_Framework_TestCase') ||
    version_compare(PHPUnit_Runner_Version::id(), '3.5') < 0
) {
    die('PHPUnit framework is required, at least 3.5 version');
}

if (!class_exists('PHPUnit_Framework_MockObject_MockBuilder')) {
    die('PHPUnit MockObject plugin is required, at least 1.0.8 version');
}

define('TESTS_PATH', __DIR__);
define('VENDOR_PATH', realpath(__DIR__ . '/../vendor'));

$classLoaderFile = VENDOR_PATH . '/Symfony/Component/ClassLoader/UniversalClassLoader.php';
if (!file_exists($classLoaderFile)) {
    die('cannot find vendor, run: php bin/vendors.php');
}
require_once $classLoaderFile;
$loader = new Symfony\Component\ClassLoader\UniversalClassLoader;
$loader->registerNamespaces(array(
    'Symfony'                    => VENDOR_PATH,
    'Knp'                        => __DIR__.'/../src',
    'Test'                       => __DIR__,
    'Doctrine\\Common'           => VENDOR_PATH.'/doctrine-common/lib',
    'Doctrine\\DBAL'             => VENDOR_PATH.'/doctrine-dbal/lib',
    'Doctrine\\ORM'              => VENDOR_PATH.'/doctrine-orm/lib',
    'Doctrine\\MongoDB'          => VENDOR_PATH.'/doctrine-mongodb/lib',
    'Doctrine\\ODM\\MongoDB'     => VENDOR_PATH.'/doctrine-mongodb-odm/lib',
));
$loader->register();

\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(
    VENDOR_PATH.'/doctrine-orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php'
);

\Doctrine\Common\Annotations\AnnotationRegistry::registerFile(
    VENDOR_PATH.'/doctrine-mongodb-odm/lib/Doctrine/ODM/MongoDB/Mapping/Annotations/DoctrineAnnotations.php'
);

$reader = new \Doctrine\Common\Annotations\AnnotationReader();
$reader = new \Doctrine\Common\Annotations\CachedReader($reader, new \Doctrine\Common\Cache\ArrayCache());
$_ENV['annotation_reader'] = $reader;

