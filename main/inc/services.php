<?php
/* For licensing terms, see /license.txt */

/**
 * This file includes all the services that are loaded via the ServiceProviderInterface
 *
 * @package chamilo.services
 */

use Silex\Application;
use Silex\ServiceProviderInterface;

// Monolog
if (is_writable($app['sys_temp_path'])) {

    /** Adding Monolog service provider Monolog  use examples
    $app['monolog']->addDebug('Testing the Monolog logging.');
    $app['monolog']->addInfo('Testing the Monolog logging.');
    $app['monolog']->addError('Testing the Monolog logging.');
     */

    $app->register(
        new Silex\Provider\MonologServiceProvider(),
        array(
            'monolog.logfile' => $app['chamilo.log'],
            'monolog.name' => 'chamilo',
        )
    );
}

//Setting HttpCacheService provider in order to use do: $app['http_cache']->run();
/*
$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir' => $app['http_cache.cache_dir'].'/',
));*/

// Session provider
//$app->register(new Silex\ProviderSessionServiceProvider());

/*
Implements a UserProvider to login users

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Doctrine\DBAL\Connection;

class UserProvider implements UserProviderInterface
{
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function loadUserByUsername($username)
    {
        $stmt = $this->conn->executeQuery('SELECT * FROM users WHERE username = ?', array(strtolower($username)));

        if (!$user = $stmt->fetch()) {
            throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
        }
        $roles = 'student';
        echo $user['username'];exit;
        return new User($user['username'], $user['password'], explode(',', $roles), true, true, true, true);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
        throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
    }

    public function supportsClass($class)
    {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }
}

$app->register(new Silex\Provider\SecurityServiceProvider(), array(
    'security.firewalls' => array(
        'secured' => array(
        'pattern' => '^/admin/',
        'form'    => array(
        'login_path' => '/login',
        'check_path' => '/admin/login_check'
    ),
    'logout' => array('path' => '/logout', 'target' => '/'),
    'users' => $app->share(function() use ($app) {
        return new UserProvider($app['db']);
    })
    )
    ),
    'security.role_hierarchy'=> array(
        'ROLE_ADMIN' => array('ROLE_EDITOR'),
        "ROLE_EDITOR" => array('ROLE_WRITER'),
        "ROLE_WRITER" => array('ROLE_USER'),
        "ROLE_USER" => array("ROLE_SUSCRIBER"),
)
));*/


// Setting Controllers as services provider
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

// Validator provider
$app->register(new Silex\Provider\ValidatorServiceProvider());

// Implements Symfony2 translator (needed when using forms in Twig)
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale' => 'en',
    'locale_fallback' => 'en'
));

// Handling po files

/*
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\Dumper\PoFileDumper;

$app['translator'] = $app->share($app->extend('translator', function($translator, $app) {
$translator->addLoader('pofile', new PoFileLoader());

$language = api_get_language_interface();
$iterator = new FilesystemIterator(api_get_path(SYS_PATH).'resources/locale/'.$language);
$filter = new RegexIterator($iterator, '/\.(po)$/');

foreach ($filter as $entry) {
//$domain = $entry->getBasename('.inc.po');
$locale = api_get_language_isocode($language); //'es_ES';
//$translator->addResource('pofile', $entry->getPathname(), $locale, $domain);
$translator->addResource('pofile', $entry->getPathname(), $locale, 'messages');
}
return $translator;
}));

//$app['translator.domains'] = array();
*/

// Form provider
$app->register(new Silex\Provider\FormServiceProvider());

// URL generator provider
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

/*
use Doctrine\Common\Persistence\AbstractManagerRegistry;
class ManagerRegistry extends AbstractManagerRegistry
{
    protected $container;

    protected function getService($name)
    {
        return $this->container[$name];
    }

    protected function resetService($name)
    {
        unset($this->container[$name]);
    }

    public function getAliasNamespace($alias)
    {
        throw new \BadMethodCallException('Namespace aliases not supported.');
    }

    public function setContainer(Application $container)
    {
        $this->container = $container;
    }
}

$app['form.extensions'] = $app->share($app->extend('form.extensions', function ($extensions, $app) {
    $managerRegistry = new ManagerRegistry(null, array(), array('orm.em'), null, null, $app['orm.proxies_namespace']);
    $managerRegistry->setContainer($app);
    $extensions[] = new \Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension($managerRegistry);
    return $extensions;
}));*/

// Setting Doctrine service provider (DBAL)
if (isset($app['configuration']['main_database'])) {

    $app->register(new Silex\Provider\DoctrineServiceProvider(), array(
        'db.options' => array(
            'driver' => 'pdo_mysql',
            'dbname' => $app['configuration']['main_database'],
            'user' => $app['configuration']['db_user'],
            'password' => $app['configuration']['db_password'],
            'host' => $app['configuration']['db_host'],
            'charset'   => 'utf8',
            /*'driverOptions' => array(
                1002 => 'SET NAMES utf8'
            )*/
        )
    ));

    // Setting Doctrine ORM
    $app->register(new Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider, array(
        'orm.auto_generate_proxies' => true,
        "orm.proxies_dir" => $app['db.orm.proxies_dir'],
        //'orm.proxies_namespace' => '\Doctrine\ORM\Proxy\Proxy',
        "orm.em.options" => array(
            "mappings" => array(
                array(
                    /* If true, only simple notations like @Entity will work.
                    If false, more advanced notations and aliasing via use will work.
                    (Example: use Doctrine\ORM\Mapping AS ORM, @ORM\Entity)*/
                    'use_simple_annotation_reader' => false,
                    "type" => "annotation",
                    "namespace" => "Entity",
                    "path" => api_get_path(INCLUDE_PATH).'Entity',
                ),
                array(
                    'use_simple_annotation_reader' => false,
                    "type" => "annotation",
                    "namespace" => "Gedmo",
                    "path" => api_get_path(SYS_PATH).'vendors/gedmo/doctrine-extensions/lib/Gedmo',
                )
            ),
        ),
    ));
}

// Setting Twig as a service provider
$app->register(
    new Silex\Provider\TwigServiceProvider(),
    array(
        'twig.path' => array(
            api_get_path(SYS_CODE_PATH).'template', //template folder
            api_get_path(SYS_PLUGIN_PATH) //plugin folder
        ),
        // twitter bootstrap form twig templates
        'twig.form.templates' => array('form_div_layout.html.twig', 'default/form/form_custom_template.tpl'),
        'twig.options' => array(
            'debug' => $app['debug'],
            'charset' => 'utf-8',
            'strict_variables' => false,
            'autoescape' => false,
            'cache' => $app['debug'] ? false : $app['twig.cache.path'],
            'optimizations' => -1, // turn on optimizations with -1
        )
    )
);

// Setting Twig options
$app['twig'] = $app->share(
    $app->extend('twig', function ($twig) {
        $twig->addFilter('get_lang', new Twig_Filter_Function('get_lang'));
        $twig->addFilter('get_path', new Twig_Filter_Function('api_get_path'));
        $twig->addFilter('get_setting', new Twig_Filter_Function('api_get_setting'));
        $twig->addFilter('var_dump', new Twig_Filter_Function('var_dump'));
        $twig->addFilter('return_message', new Twig_Filter_Function('Display::return_message_and_translate'));
        $twig->addFilter('display_page_header', new Twig_Filter_Function('Display::page_header_and_translate'));
        $twig->addFilter(
            'display_page_subheader',
            new Twig_Filter_Function('Display::page_subheader_and_translate')
        );
        $twig->addFilter('icon', new Twig_Filter_Function('Template::get_icon_path'));
        $twig->addFilter('format_date', new Twig_Filter_Function('Template::format_date'));

        return $twig;
    })
);


// Developer tools
if (is_writable($app['sys_temp_path'])) {
    if ($app['debug']) {
        // Adding symfony2 web profiler (memory, time, logs, etc)
        if (api_get_setting('allow_web_profiler') == 'true') {
            $app->register(
                $p = new Silex\Provider\WebProfilerServiceProvider(),
                array(
                    'profiler.cache_dir' => $app['profiler.cache_dir'],
                )
            );
            $app->mount('/_profiler', $p);
        }
        //$app->register(new Whoops\Provider\Silex\WhoopsServiceProvider);
        //}
    }
}


// Pagerfanta settings (Pagination using Doctrine2, arrays, etc)
use FranMoreno\Silex\Provider\PagerfantaServiceProvider;
$app->register(new PagerfantaServiceProvider());

// Custom route params see https://github.com/franmomu/silex-pagerfanta-provider/pull/2
//$app['pagerfanta.view.router.name']
//$app['pagerfanta.view.router.params']

$app['pagerfanta.view.options'] = array(
    'routeName'     => null,
    'routeParams'   => array(),
    'pageParameter' => '[page]',
    'proximity'     => 3,
    'next_message'  => '&raquo;',
    'prev_message'  => '&laquo;',
    'default_view'  => 'twitter_bootstrap' // the pagination style
);

// Registering Menu service provider (too gently creating menus with the URLgenerator provider)
$app->register(new \Knp\Menu\Silex\KnpMenuServiceProvider());

// @todo use a app['image_processor'] setting
define('IMAGE_PROCESSOR', 'gd'); // imagick or gd strings

// Setting the Imagine service provider to deal with image transformations used in social group.
$app->register(new Grom\Silex\ImagineServiceProvider(), array(
    'imagine.factory' => 'Gd'
));

// Prompts Doctrine SQL queries using monolog
if ($app['debug'] && isset($app['configuration']['main_database'])) {
    $logger = new Doctrine\DBAL\Logging\DebugStack();
    $app['db.config']->setSQLLogger($logger);

    $app->after(function() use ($app, $logger) {
        // Log all queries as DEBUG.
        foreach ($logger->queries as $query) {
            $app['monolog']->debug($query['sql'], array('params' =>$query['params'], 'types' => $query['types']));
        }
    });
}


// Email service provider
$app->register(new Silex\Provider\SwiftmailerServiceProvider(), array(
    'swiftmailer.options' => array(
        'host' => isset($platform_email['SMTP_HOST']) ? $platform_email['SMTP_HOST'] : null,
        'port' => isset($platform_email['SMTP_PORT']) ? $platform_email['SMTP_PORT'] : null,
        'username' => isset($platform_email['SMTP_USER']) ? $platform_email['SMTP_USER'] : null,
        'password' => isset($platform_email['SMTP_PASS']) ? $platform_email['SMTP_PASS'] : null,
        'encryption' => null,
        'auth_mode' => null
    )
));

//if (isset($platform_email['SMTP_MAILER']) && $platform_email['SMTP_MAILER'] == 'smtp') {
$app['mailer'] = $app->share(function ($app) {
    return new \Swift_Mailer($app['swiftmailer.transport']);
});

// Gaufrette service provider (to manage files/dirs) (not used yet)
/*
use Bt51\Silex\Provider\GaufretteServiceProvider\GaufretteServiceProvider;
$app->register(new GaufretteServiceProvider(), array(
    'gaufrette.adapter.class' => 'Local',
    'gaufrette.options' => array(api_get_path(SYS_DATA_PATH))
));
*/

// Use symfony2 filesystem instead of custom scripts
use Neutron\Silex\Provider\FilesystemServiceProvider;
$app->register(new FilesystemServiceProvider());

/** Chamilo service provider */

class ChamiloServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        // Template class
        $app['template'] = $app->share(function () use ($app) {
            $template = new Template($app);
            return $template;
        });

        // Chamilo data filesystem
        $app['chamilo.filesystem'] = $app->share(function () use ($app) {
            $filesystem = new ChamiloLMS\Component\DataFilesystem\DataFilesystem($app['sys_data_path']);
            return $filesystem;
        });

        // Page controller class
        $app['page_controller'] = $app->share(function () use ($app) {
            $pageController = new PageController($app);
            return $pageController;
        });
    }

    public function boot(Application $app)
    {
    }
}

// Registering Chamilo service provider
$app->register(new ChamiloServiceProvider(), array());

// Controller as services definitions see
$app['pages.controller'] = $app->share(
    function () use ($app) {
        return new PagesController($app['pages.repository']);
    }
);

$app['index.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\IndexController();
    }
);

$app['legacy.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\LegacyController();
    }
);

$app['userPortal.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\UserPortalController();
    }
);

$app['learnpath.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\LearnpathController();
    }
);

$app['course_home.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\CourseHomeController();
    }
);

$app['certificate.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\CertificateController();
    }
);

$app['user.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\UserController();
    }
);

$app['news.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\NewsController();
    }
);

$app['editor.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\EditorController();
    }
);

$app['question_manager.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\Admin\QuestionManager\QuestionManagerController();
    }
);

$app['exercise_manager.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\ExerciseController();
    }
);

$app['model_ajax.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\ModelAjaxController();
    }
);


