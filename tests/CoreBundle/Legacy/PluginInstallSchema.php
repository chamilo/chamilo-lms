<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

/*
 * Standalone script (run by PluginCharacterizationTestCase::setUpBeforeClass in a child process, in the "test"
 * environment, outside PHPUnit and outside the DAMA transaction): runs the install() of a legacy plugin so its
 * tables exist. DDL cannot run inside a test (it breaks the DAMA savepoint). The plugin installers only create
 * what is missing, so this is idempotent.
 *
 * Usage: php PluginInstallSchema.php <PluginDirectory> <relative/path/to/PluginClass.php> <ClassName>
 */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

$root = dirname(__DIR__, 3);

require $root.'/vendor/autoload.php';

[, $pluginDir, $classFile, $className] = $argv;

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
(new Dotenv())->bootEnv($root.'/.env');

$kernel = new Kernel('test', true);
$kernel->boot();
$container = $kernel->getContainer()->get('test.service_container');
Container::setContainer($container);
Container::setLegacyServices($container);
$session = new Session(new MockArraySessionStorage());
Container::setSession($session);
$request = Request::create('/plugin/'.$pluginDir.'/start.php');
$request->setSession($session);
$request->setLocale('en_US');
Container::setRequest($request);

require_once api_get_path(SYS_PLUGIN_PATH).$classFile;
$className::create()->install();
echo $pluginDir." schema ready\n";
