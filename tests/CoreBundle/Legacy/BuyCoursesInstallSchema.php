<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

/*
 * Standalone script (run by BuyCoursesCharacterizationTestCase::setUpBeforeClass in a child process, in the
 * "test" environment, outside PHPUnit and outside the DAMA transaction): runs the BuyCourses plugin installer so
 * the plugin_buycourses_* tables exist. DDL cannot run inside a test (it breaks the DAMA savepoint).
 * Idempotent: the installer only creates what is missing.
 */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

$root = dirname(__DIR__, 3);
require $root.'/vendor/autoload.php';

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
(new Symfony\Component\Dotenv\Dotenv())->bootEnv($root.'/.env');

$kernel = new Kernel('test', true);
$kernel->boot();
$container = $kernel->getContainer()->get('test.service_container');
Container::setContainer($container);
Container::setLegacyServices($container);
$session = new Session(new MockArraySessionStorage());
Container::setSession($session);
$request = Request::create('/plugin/BuyCourses/src/index.php');
$request->setSession($session);
$request->setLocale('en_US');
Container::setRequest($request);

require_once api_get_path(SYS_PLUGIN_PATH).'BuyCourses/src/buy_course_plugin.class.php';
(new BuyCoursesPlugin())->install();
echo "BuyCourses schema ready\n";
