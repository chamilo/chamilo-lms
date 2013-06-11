<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo installation
 * This script could be loaded via browser using the URL: main/install/index.php
 * or via CM
 *
 * @package chamilo.install
 */

require_once __DIR__.'/../../vendor/autoload.php';
require_once 'install.lib.php';
require_once '../inc/lib/main_api.lib.php';

error_reporting(-1);

use Symfony\Component\Translation\Loader\YamlFileLoader;
use Silex\Application;
use Symfony\Component\Console\Output\Output;

class BufferedOutput extends Output
{
    public function doWrite($message, $newline)
    {
        //$this->buffer .= $message. ($newline ? PHP_EOL: '');
        $this->buffer .= $message. '<br />';
    }

    public function getBuffer()
    {
        return $this->buffer;
    }
}

$app = new Application();

$app['root_sys'] = dirname(dirname(__DIR__)).'/';

// Registering services

$app['debug'] = true;
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new Silex\Provider\FormServiceProvider());
$app->register(new Silex\Provider\SessionServiceProvider());
$app->register(new Silex\Provider\DoctrineServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider());
$app['translator'] = $app->share($app->extend('translator', function($translator, $app) {
    /*$translator->addLoader('yaml', new YamlFileLoader());
    $translator->addResource('yaml', __DIR__.'/lang/fr.yml', 'fr');
    $translator->addResource('yaml', __DIR__.'/lang/en.yml', 'en');
    $translator->addResource('yaml', __DIR__.'/lang/es.yml', 'es');*/

    return $translator;
}));

//$app->register(new Whoops\Provider\Silex\WhoopsServiceProvider);

$app->register(
    new Silex\Provider\TwigServiceProvider(),
    array(
        'twig.path' => array(
            'templates'
        ),
        // twitter bootstrap form twig templates
        //'twig.form.templates' => array('form_div_layout.html.twig', '../template/default/form/form_custom_template.tpl'),
        'twig.options' => array(
            'debug' => $app['debug'],
            'charset' => 'utf-8',
            'strict_variables' => false,
            'autoescape' => true,
            'cache' => $app['debug'] ? false : $app['twig.cache.path'],
            'optimizations' => -1, // turn on optimizations with -1
        )
    )
);

use Knp\Provider\ConsoleServiceProvider;

$app->register(new ConsoleServiceProvider(), array(
    'console.name'              => 'Chamilo',
    'console.version'           => '1.0.0',
    'console.project_directory' => __DIR__.'/..'
));

function get_lang($variable) {
    global $app;
    return $app['translator']->trans($variable);
}


// Adding commands
/** @var Knp\Console\Application $console */
$console = $app['console'];

$console->addCommands(
    array(
        // DBAL Commands
        new \Doctrine\DBAL\Tools\Console\Command\RunSqlCommand(),
        new \Doctrine\DBAL\Tools\Console\Command\ImportCommand(),


        // Migrations Commands
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\DiffCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\ExecuteCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\GenerateCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\StatusCommand(),
        new \Doctrine\DBAL\Migrations\Tools\Console\Command\VersionCommand(),

        // Chamilo commands
        new ChamiloLMS\Command\Database\UpgradeCommand(),
        new ChamiloLMS\Command\Database\InstallCommand(),
        new ChamiloLMS\Command\Database\StatusCommand(),
        new ChamiloLMS\Command\Database\SetupCommand(),

        // Chash commands
        new Chash\Command\Database\RunSQLCommand(),
        new Chash\Command\Database\DumpCommand(),
        new Chash\Command\Database\RestoreCommand(),
        new Chash\Command\Database\SQLCountCommand(),
        new Chash\Command\Database\FullBackupCommand(),
        new Chash\Command\Database\DropDatabaseCommand(),
        new Chash\Command\Files\CleanTempFolderCommand(),
        new Chash\Command\Files\CleanConfigFiles(),
        new Chash\Command\Translation\ExportLanguageCommand(),
        new Chash\Command\Translation\ImportLanguageCommand()
    )
);

$helpers = array(
    'configuration' => new Chash\Helpers\ConfigurationHelper()
);

$helperSet = $console->getHelperSet();
foreach ($helpers as $name => $helper) {
    $helperSet->set($helper, $name);
}

/*
    // Chamilo commands
    new ChamiloLMS\Command\Database\UpgradeCommand(),
    new ChamiloLMS\Command\Database\InstallCommand(),
    new ChamiloLMS\Command\Database\StatusCommand(),
    new ChamiloLMS\Command\Database\SetupCommand(),

    // Chash commands
    new Chash\Command\Database\RunSQLCommand(),
    new Chash\Command\Database\DumpCommand(),
    new Chash\Command\Database\RestoreCommand(),
    new Chash\Command\Database\SQLCountCommand(),
    new Chash\Command\Database\FullBackupCommand(),
    new Chash\Command\Database\DropDatabaseCommand(),
    new Chash\Command\Files\CleanTempFolderCommand(),
    new Chash\Command\Files\CleanConfigFiles(),
    new Chash\Command\Translation\ExportLanguageCommand(),
    new Chash\Command\Translation\ImportLanguageCommand()
        */

// Controllers

$app->match('/', function() use($app) {
    $languages = array(
        'english' => 'english',
        'spanish' =>  'spanish',
        'french' => 'french'
    );
    $request = $app['request'];

    $form = $app['form.factory']->createBuilder('form')
        ->add('languages', 'choice', array(
            'choices'   => $languages,
            'required'  => true,
        ))
        ->add('continue', 'submit')
        ->getForm();

     if ('POST' == $request->getMethod()) {
         $url = $app['url_generator']->generate('requirements');
         return $app->redirect($url);
     }

    return $app['twig']->render('index.tpl', array('form' => $form->createView()));
})->bind('welcome');

$app->match('/requirements', function() use($app) {
    $request = $app['request'];
    $form = $app['form.factory']->createBuilder('form')
        ->add('continue', 'submit')
        ->getForm();

    $req = display_requirements($app, 'new');

    if ('POST' == $request->getMethod()) {
         $url = $app['url_generator']->generate('check-database');
         return $app->redirect($url);
    }
    return $app['twig']->render(
        'requirements.tpl',
        array(
            'form' => $form->createView(),
            'requirements' => $req
        )
    );
})->bind('requirements');


$app->match('/check-database', function() use($app) {
    $request = $app['request'];

    $command = $app['console']->get('chamilo:install');
    $data = $command->getDatabaseSettingsParams();

    $builder  = $app['form.factory']->createBuilder('form');
    foreach ($data as $key => $value) {
        $value['attributes'] = isset($value['attributes']) && is_array($value['attributes']) ? $value['attributes'] : array();
        $builder->add($key, $value['type'], $value['attributes']);
    }

    /*$builder->add('create_database', 'choice', array(
            'choices' => array('1' => 'Yes', '0' => 'No'),
            'data' => 'Yes'
        )
    );*/

    $builder->add('check', 'submit');
    $form = $builder->getForm();

    if ('POST' == $request->getMethod()) {
        $form->bind($request);

        if ($form->isValid()) {
            $parameters = $form->getData();

            /** @var \ChamiloLMS\Command\Database\InstallCommand $command */
            $command = $app['console']->get('chamilo:install');
            $command->setDatabaseSettings($parameters);

            $connection = $command->getUserAccessConnectionToHost();

            try {
                $connect = $connection->connect();
                $sm = $connection->getSchemaManager();
                $databases = $sm->listDatabases();

                if (in_array($parameters['dbname'], $databases)) {
                    $message = $app['translator']->trans(
                        'The database %s being used and is going to be deleted!!',
                        array('%s' => $parameters['dbname'])
                    );
                    $app['session']->getFlashBag()->add('warning', $message);
                } else {
                    $message = $app['translator']->trans(
                        'A database %s is going to be created',
                        array('%s' => $parameters['dbname'])
                    );
                    $app['session']->getFlashBag()->add('warning', $message);
                }

                $app['session']->getFlashBag()->add('success', 'Connection ok!');
                $app['session']->set('database_settings', $parameters);
                $url = $app['url_generator']->generate('portal-settings');
                return $app->redirect($url);
            } catch (Exception $e) {
                $app['session']->getFlashBag()->add('success', 'Connection error !'.$e->getMessage());
            }

            // do something with the data

            // redirect somewhere
            //return $app->redirect('...');
        }
    }

    return $app['twig']->render('check-database.tpl', array('form' => $form->createView()));

})->bind('check-database');

$app->match('/portal-settings', function() use($app) {
    /** @var Request $request */
    $request = $app['request'];

    /** @var \ChamiloLMS\Command\Database\InstallCommand $command */
    $command = $app['console']->get('chamilo:install');

    $builder  = $app['form.factory']->createBuilder('form');

    $data = $command->getPortalSettingsParams();
    $data['institution_url']['attributes']['data'] = str_replace('main/install/', '', $request->getUriForPath('/'));
    $permissionNewDir = $app['session']->get('permissions_for_new_directories');

    if ($permissionNewDir) {
        $data['permissions_for_new_directories']['attributes']['data'] = $permissionNewDir;
    }

    $permissionNewFiles = $app['session']->get('permissions_for_new_files');
    if ($permissionNewFiles) {
        $data['permissions_for_new_files']['attributes']['data'] = $permissionNewFiles;
    }

    foreach ($data as $key => $value) {
        $value['attributes'] = isset($value['attributes']) && is_array($value['attributes']) ? $value['attributes'] : array();
        $builder->add($key, $value['type'], $value['attributes']);
    }

    $builder->add('continue', 'submit');
    $form = $builder->getForm();

    if ('POST' == $request->getMethod()) {
        $form->bind($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $app['session']->set('portal_settings', $data);
            $url = $app['url_generator']->generate('admin-settings');
            return $app->redirect($url);
        }
    }
    return $app['twig']->render('settings.tpl', array('form' => $form->createView()));

})->bind('portal-settings');


$app->match('/admin-settings', function() use($app) {
    $request = $app['request'];

    /** @var \ChamiloLMS\Command\Database\InstallCommand $command */
    $command = $app['console']->get('chamilo:install');

    $data = $command->getAdminSettingsParams();
    $builder  = $app['form.factory']->createBuilder('form', $data);
    foreach ($data as $key => $value) {
        $builder->add($key, $value['type'], $value['attributes']);
    }
    $builder->add('continue', 'submit');
    $form = $builder->getForm();

    if ('POST' == $request->getMethod()) {
        $form->bind($request);

        if ($form->isValid()) {
            $data = $form->getData();
            $app['session']->set('admin_settings', $data);
            $url = $app['url_generator']->generate('resume');
            return $app->redirect($url);
        }
    }
    return $app['twig']->render('settings.tpl', array('form' => $form->createView()));

})->bind('admin-settings');

$app->match('/resume', function() use($app) {
    $request = $app['request'];
    $data = array();
    $portalSettings = $app['session']->get('portal_settings');
    $databaseSettings = $app['session']->get('database_settings');
    $adminSettings = $app['session']->get('admin_settings');

    if (!empty($portalSettings) && !empty($databaseSettings) && !empty($adminSettings)) {

        $form = $app['form.factory']->createBuilder('form', $data)
            ->add('install', 'submit', array('label' => 'Continue'))
            ->getForm();

        if ('POST' == $request->getMethod()) {
             $url = $app['url_generator']->generate('installing');
             return $app->redirect($url);
        }

        return $app['twig']->render(
            'resume.tpl',
            array(
                'form' => $form->createView(),
                'portal_settings' => $portalSettings,
                'database_settings' => $databaseSettings,
                'admin_settings' => $adminSettings
            )
        );
    } else {

        $url = $app['url_generator']->generate('check-database');
        return $app->redirect($url);
    }
})->bind('resume');

$app->match('/installing', function() use($app) {

    $portalSettings = $app['session']->get('portal_settings');
    $adminSettings = $app['session']->get('admin_settings');
    $databaseSettings = $app['session']->get('database_settings');

    /** @var \ChamiloLMS\Command\Database\InstallCommand $command */
    $command = $app['console']->get('chamilo:install');

    $def = $command->getDefinition();
    $input = new Symfony\Component\Console\Input\ArrayInput(
        array(
            'name',
            'path' => realpath(__DIR__.'/../../').'/',
            'version' => '1.10.0'
        ),
        $def
    );

    $output = new BufferedOutput();

    $command->setPortalSettings($portalSettings);
    $command->setDatabaseSettings($databaseSettings);
    $command->setAdminSettings($adminSettings);

    $result = $command->run($input, $output);

    if ($result == 0) {
        $output = $output->getBuffer();
        $app['session']->getFlashBag()->add('success', 'Installation finished');
        $app['session']->set('output', $output);
        $url = $app['url_generator']->generate('finish');
        return $app->redirect($url);
    } else {
        $app['session']->getFlashBag()->add('error', 'There was an error during installation');
        $url = $app['url_generator']->generate('check-database');
        return $app->redirect($url);
    }
})->bind('installing');

$app->get('/finish', function() use($app) {
    $output = $app['session']->get('output');
    return $app['twig']->render('finish.tpl', array('output' => $output));
})->bind('finish');


$app->error(function (\Exception $e, $code) {
    switch ($code) {
        case 404:
            $message = 'The requested page could not be found.';
            break;
        default:
            $message = 'We are sorry, but something went terribly wrong.';
    }

    return new Response($message);
});

if (PHP_SAPI == 'cli') {
    $console->run();
} else {
    $app->run();
}
