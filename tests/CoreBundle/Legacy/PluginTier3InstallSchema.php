<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

/*
 * Standalone script (run by PluginTier3SchemaTrait in a child process, in the "test" environment, outside PHPUnit and
 * outside the DAMA transaction): runs the installer of the plugin named in argv[1] (H5pImport, CardGame or
 * NotebookTeacher) so its table exists. DDL cannot run inside a test (it breaks the DAMA savepoint). Idempotent.
 */

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\Kernel;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImport;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImportLibrary;
use Chamilo\PluginBundle\H5pImport\Entity\H5pImportResults;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

$root = dirname(__DIR__, 3);

require $root.'/vendor/autoload.php';

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
(new Dotenv())->bootEnv($root.'/.env');

$kernel = new Kernel('test', true);
$kernel->boot();
$container = $kernel->getContainer()->get('test.service_container');
Container::setContainer($container);
Container::setLegacyServices($container);
$session = new Session(new MockArraySessionStorage());
Container::setSession($session);
$request = Request::create('/plugin/index.php');
$request->setSession($session);
$request->setLocale('en_US');
Container::setRequest($request);

$name = $argv[1] ?? '';
$plugins = [
    'H5pImport' => ['H5pImport/H5pImportPlugin.php', 'H5pImportPlugin'],
    'CardGame' => ['CardGame/CardGame.php', 'CardGame'],
    'NotebookTeacher' => ['NotebookTeacher/src/NotebookTeacherPlugin.php', 'NotebookTeacherPlugin'],
];
if (!isset($plugins[$name])) {
    fwrite(\STDERR, "Unknown plugin $name\n");

    exit(1);
}

require_once api_get_path(SYS_PLUGIN_PATH).$plugins[$name][0];
if ('NotebookTeacher' === $name) {
    // install() also installs course fields in every course; only the table is needed.
    require_once api_get_path(SYS_PLUGIN_PATH).'NotebookTeacher/database.php';
} elseif ('H5pImport' === $name) {
    // H5pImportPlugin::install() hands its entities to SchemaTool::createSchema(), and the PdoSessionHandlerSchemaListener
    // (config/services.yaml) adds the php_session table to every schema it generates: on a database that already has it
    // (every real one) the installer dies with "Table 'php_session' already exists". Create the plugin tables only.
    $em = Container::getEntityManager();
    $metadata = [
        $em->getClassMetadata(H5pImport::class),
        $em->getClassMetadata(H5pImportLibrary::class),
        $em->getClassMetadata(H5pImportResults::class),
    ];
    foreach ((new SchemaTool($em))->getCreateSchemaSql($metadata) as $sql) {
        if (!str_contains($sql, 'php_session')) {
            $em->getConnection()->executeStatement($sql);
        }
    }
} else {
    $plugins[$name][1]::create()->install();
}
echo "$name schema ready\n";
