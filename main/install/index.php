<?php
/* For licensing terms, see /license.txt */

/**
 * Chamilo installation
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
/** @var \Knp\Provider\ConsoleServiceProvider\ConsoleApplication $console */
$console = $app['console'];
$console->add(new ChamiloLMS\Command\Database\InstallCommand());
$console->add(new \Doctrine\DBAL\Tools\Console\Command\RunSqlCommand());
$console->add(new \Doctrine\DBAL\Tools\Console\Command\ImportCommand());
$console->add(new \Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand());


//$console->run();
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
        ->add('send', 'submit')
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
        ->add('send', 'submit')
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

    $builder->add('send', 'submit');
    $form = $builder->getForm();

    if ('POST' == $request->getMethod()) {
        $form->bind($request);

        if ($form->isValid()) {
            $parameters = $form->getData();
            $config = new \Doctrine\DBAL\Configuration();
            $conn = \Doctrine\DBAL\DriverManager::getConnection($parameters, $config);

            try {
                $connect = $conn->connect();
                $sm = $conn->getSchemaManager();
                $databases = $sm->listDatabases();
                if (in_array($parameters['database'], $databases)) {
                    $app['session']->getFlashBag()->add('warning', 'The database is %s being used');
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
    $request = $app['request'];

    /** @var \ChamiloLMS\Command\Database\InstallCommand $command */
    $command = $app['console']->get('chamilo:install');

    $builder  = $app['form.factory']->createBuilder('form');

    $data = $command->getPortalSettingsParams();
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

    $builder->add('send', 'submit');
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
    $builder->add('send', 'submit');
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
            ->add('send', 'submit', array('label' => 'Continue'))
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
