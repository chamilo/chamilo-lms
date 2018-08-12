<?php
/**
 * This is bootstrap for phpUnit unit tests,
 * use README.md for more details
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @package Gedmo.Tests
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

if (!class_exists('PHPUnit_Framework_TestCase') ||
    version_compare(PHPUnit_Runner_Version::id(), '3.6') < 0
) {
    die('PHPUnit framework is required, at least 3.6 version');
}

if (!class_exists('PHPUnit_Framework_MockObject_MockBuilder')) {
    die('PHPUnit MockObject plugin is required, at least 1.0.8 version');
}

define("DB_ENGINE", getenv("DB") ?: "pgsql");
define('DB_HOST', getenv("DB_HOST") ?: 'localhost');
define('DB_NAME', getenv("DB_NAME") ?: 'orm_behaviors_test');
define("DB_USER", getenv("DB_USER") ?: null);
define("DB_PASSWD", getenv("DB_PASSWD") ?: null);

define('TESTS_PATH', __DIR__);
define('TESTS_TEMP_DIR', __DIR__.'/temp');
define('VENDOR_PATH', realpath(__DIR__ . '/../vendor'));

$loader = require(VENDOR_PATH.'/autoload.php');
$loader->add('BehaviorFixtures', __DIR__.'/fixtures');

Doctrine\Common\Annotations\AnnotationRegistry::registerFile(
    VENDOR_PATH.'/doctrine/orm/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php'
);

$reader = new \Doctrine\Common\Annotations\AnnotationReader();
$reader = new \Doctrine\Common\Annotations\CachedReader($reader, new \Doctrine\Common\Cache\ArrayCache());
$_ENV['annotation_reader'] = $reader;
