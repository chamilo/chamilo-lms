<?php
/* For licensing terms, see /license.txt */

/**
 * This file includes all the services that are loaded via the ServiceProviderInterface
 *
 * @package chamilo.services
 */

// Needed to use the "entity" option in symfony forms
use Doctrine\Common\Persistence\AbstractManagerRegistry;
use FranMoreno\Silex\Provider\PagerfantaServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\MediaAlchemystServiceProvider;
use MediaVorus\MediaVorusServiceProvider;
use FFMpeg\FFMpegServiceProvider;
use PHPExiftool\PHPExiftoolServiceProvider;

// Flint
$app->register(new Flint\Provider\ConfigServiceProvider());
$app['root_dir'] = $app['root_sys'];

$app->register(new Flint\Provider\RoutingServiceProvider(), array(
    'routing.resource' => $app['sys_config_path'].'routing.yml',
    'routing.options' => array(
        //'cache_dir' => $app['debug'] == true ? null : $app['sys_temp_path']
        //'cache_dir' => $app['sys_temp_path']
    ),
));

if (isset($app['configuration']['services']['media-alchemyst'])) {
    $app->register(new MediaAlchemystServiceProvider());

    $app->register(new PHPExiftoolServiceProvider());
    $app->register(new FFMpegServiceProvider());

    $app->register(new MediaVorusServiceProvider(), array(
        'media-alchemyst.configuration' => array(
            'ffmpeg.threads'               => 4,
            'ffmpeg.ffmpeg.timeout'        => 3600,
            'ffmpeg.ffprobe.timeout'       => 60,
            'ffmpeg.ffmpeg.binaries'       => '/path/to/custom/ffmpeg',
            'ffmpeg.ffprobe.binaries'      => '/path/to/custom/ffprobe',
            'imagine.driver'               => 'imagick',
            'gs.timeout'                   => 60,
            'gs.binaries'                  => '/path/to/custom/gs',
            'mp4box.timeout'               => 60,
            'mp4box.binaries'              => '/path/to/custom/MP4Box',
            'swftools.timeout'             => 60,
            'swftools.pdf2swf.binaries'    => '/path/to/custom/pdf2swf',
            'swftools.swfrender.binaries'  => '/path/to/custom/swfrender',
            'swftools.swfextract.binaries' => '/path/to/custom/swfextract',
            'unoconv.binaries'             => $app['configuration']['services']['media-alchemyst']['unoconv_path'],
            'unoconv.timeout'              => 60,
            //'exiftool.reader'              => '/path/to/custom/exiftool.reader',
            //'exiftool.writer'              => '/path/to/custom/exiftool.writer'
        ),
        //'media-alchemyst.logger' => $logger,  // A PSR Logger
    ));


}


use Knp\Provider\ConsoleServiceProvider;

$app->register(new ConsoleServiceProvider(), array(
    'console.name'              => 'Chamilo',
    'console.version'           => '1.0.0',
    'console.project_directory' => __DIR__.'/..'
));

// Monolog.
if (is_writable($app['sys_temp_path'])) {

    /**
     *  Adding Monolog service provider.
     *  Examples:
     *  $app['monolog']->addDebug('Testing the Monolog logging.');
     *  $app['monolog']->addInfo('Testing the Monolog logging.');
     *  $app['monolog']->addError('Testing the Monolog logging.');
     */
    if ($app['debug']) {
        $app->register(
            new Silex\Provider\MonologServiceProvider(),
            array(
                'monolog.logfile' => $app['chamilo.log'],
                'monolog.name' => 'chamilo',
            )
        );
    }
}

//Setting HttpCacheService provider in order to use do: $app['http_cache']->run();
/*
$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir' => $app['http_cache.cache_dir'].'/',
));*/

class SecurityServiceProvider extends \Silex\Provider\SecurityServiceProvider
{
    public function addFakeRoute($method, $pattern, $name)
    {
        // Dont do anything otherwise the closures will be dumped and that leads to fatal errors.
    }
}

$app->register(new SecurityServiceProvider, array(
    'security.firewalls' => array(
        'login' => array(
            'pattern' => '^/login$',
            'anonymous' => true
        ),
        'secured' => array(
            'pattern' => '^/.*$',
            'form'    => array(
                'login_path' => '/login',
                'check_path' => '/secured/login_check',
                'default_target_path' => '/userportal',
                'username_parameter' => 'username',
                'password_parameter' => 'password',
            ),
            'logout' => array(
                'logout_path' => '/secured/logout',
                'target' => '/'
            ),
            'users' => $app->share(function() use ($app) {
                return $app['orm.em']->getRepository('Entity\User');
            }),
            'switch_user' => true,
            'anonymous' => true
        )
    )
));

// Registering Password encoder.
$app['security.encoder.digest'] = $app->share(function($app) {
    // use the sha1 algorithm
    // don't base64 encode the password
    // use only 1 iteration
    return new MessageDigestPasswordEncoder($app['configuration']['password_encryption'], false, 1);
});

// What to do when login success?
$app['security.authentication.success_handler.secured'] = $app->share(function($app) {
    return new ChamiloLMS\Component\Auth\LoginSuccessHandler($app['url_generator'], $app['security']);
});

// What to do when logout?
$app['security.authentication.logout_handler.secured'] = $app->share(function($app) {
    return new ChamiloLMS\Component\Auth\LogoutSuccessHandler($app['url_generator'], $app['security']);
});

// What to do when switch user?
$app['security.authentication_listener.switch_user.secured'] = $app->share(function($app) {
    return new ChamiloLMS\Component\Auth\LoginListener();
});

// Role hierarchy
$app['security.role_hierarchy'] = array(
    // the admin that belongs to the portal #1 can affect all portals
    'ROLE_GLOBAL_ADMIN' => array('ROLE_ADMIN', 'ROLE_ALLOWED_TO_SWITCH'),
    // the default admin
    'ROLE_ADMIN' => array(
        'ROLE_QUESTION_MANAGER',
        'ROLE_SESSION_MANAGER',
        'ROLE_TEACHER',
        'ROLE_DIRECTOR',
        'ROLE_JURY_PRESIDENT'
    ),
    'ROLE_RRHH' => array('ROLE_TEACHER'),
    'ROLE_TEACHER' => array('ROLE_STUDENT'),
    'ROLE_QUESTION_MANAGER' => array('ROLE_STUDENT', 'ROLE_QUESTION_MANAGER'),
    'ROLE_SESSION_MANAGER' => array('ROLE_STUDENT', 'ROLE_SESSION_MANAGER', 'ROLE_ALLOWED_TO_SWITCH'),
    'ROLE_STUDENT' => array('ROLE_STUDENT'),
    'ROLE_ANONYMOUS' => array('ROLE_ANONYMOUS')
);

// Role rules
$app['security.access_rules'] = array(
    array('^/admin/administrator', 'ROLE_ADMIN'),
    array('^/main/admin/.*', 'ROLE_ADMIN'),
    array('^/admin/questionmanager', 'ROLE_QUESTION_MANAGER'),
    array('^/main/auth/inscription.php', 'IS_AUTHENTICATED_ANONYMOUSLY'),
    array('^/main/auth/lostPassword.php', 'IS_AUTHENTICATED_ANONYMOUSLY'),
    array('^/courses/.*/curriculum/category', 'ROLE_TEACHER'),
    array('^/courses/.*/curriculum/item', 'ROLE_TEACHER'),
    array('^/courses/.*/curriculum/user', 'ROLE_STUDENT'),
    array('^/courses/.*/curriculum', 'ROLE_STUDENT'),
    //array('^/main/.*', array('ROLE_STUDENT')),
);

// Roles that have an admin toolbar
$app['allow_admin_toolbar'] = array(
    'ROLE_ADMIN',
    'ROLE_QUESTION_MANAGER',
    'ROLE_SESSION_MANAGER'
);

use SilexOpauth\OpauthExtension;

$strategies = isset($_configuration['strategies']) ? $_configuration['strategies'] : null;

if (!empty($strategies)) {
    $app['opauth'] = array(
        'login' => '/auth/login',
        'callback' => '/auth/callback',
        'config' => array(
            'security_salt' => $_configuration['security_key'],
            'Strategy' => array(
                $strategies
            )
        )
    );
    $app->register(new OpauthExtension());
}



/*
$app['security.access_manager'] = $app->share(function($app) {
    return new AccessDecisionManager($app['security.voters'], 'unanimous');
});*/

// Setting Controllers as services provider.
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

// Implements Symfony2 translator.
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale' => 'en',
    'locale_fallback' => 'en',
    'translator.domains' => array()
));

// Validator provider.
$app->register(new Silex\Provider\ValidatorServiceProvider());

// Form provider
$app->register(new Silex\Provider\FormServiceProvider(), array(
    'form.secret' => sha1(__DIR__)
));

// URL generator provider
//$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

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

    /**
     * @param string $alias
     * @return string|void
     * @throws BadMethodCallException
     */
    public function getAliasNamespace($alias)
    {
        throw new \BadMethodCallException('Namespace aliases not supported.');
    }

    public function setContainer(Application $container)
    {
        $this->container = $container;
    }
}

// Setting up the Manager registry
$app['manager_registry'] = $app->share(function() use ($app) {
    $managerRegistry = new ManagerRegistry(null, array('db'), array('orm.em'), null, null, $app['orm.proxies_namespace']);
    $managerRegistry->setContainer($app);
    return $managerRegistry;
});

// Needed to use the "entity" option in Symfony forms
$app['form.extensions'] = $app->share($app->extend('form.extensions', function ($extensions, $app) {
    $extensions[] = new \Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension($app['manager_registry']);
    return $extensions;
}));

// Needed to use the "UniqueEntity" validator
$app['validator.validator_factory'] = $app->share(function ($app) {
    $uniqueValidator = new Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator($app['manager_registry']);
    $factory = new ChamiloLMS\Component\Validator\ConstraintValidatorFactory();
    $factory->addInstance('doctrine.orm.validator.unique', $uniqueValidator);
    return $factory;
});

// Setting Doctrine service provider (DBAL)
if (isset($app['configuration']['main_database'])) {

    /* The database connection can be overwritten if you set $_configuration['db.options']
       in configuration.php like this : */
    $dbPort = isset($app['configuration']['db_port']) ? $app['configuration']['db_port'] : 3306;
    $dbDriver = isset($app['configuration']['db_driver']) ? $app['configuration']['db_driver'] : 'pdo_mysql';
    $host = $app['configuration']['db_host'];

    // Accepts that db_host can have a port part like: localhost:6666;

    $hostParts = explode(':', $app['configuration']['db_host']);
    if (isset($hostParts[1]) && !empty($hostParts[1])) {
        $dbPort = $hostParts[1];
        $host = str_replace(':'.$dbPort, '', $app['configuration']['db_host']);
    }

    $defaultDatabaseOptions = array(
        'db_read' => array(
            'driver' => $dbDriver,
            'host' => $host,
            'port' => $dbPort,
            'dbname' => $app['configuration']['main_database'],
            'user' => $app['configuration']['db_user'],
            'password' => $app['configuration']['db_password'],
            'charset'   => 'utf8',
            //'priority' => '1'
        ),
        'db_write' => array(
            'driver' => $dbDriver,
            'host' => $host,
            'port' => $dbPort,
            'dbname' => $app['configuration']['main_database'],
            'user' => $app['configuration']['db_user'],
            'password' => $app['configuration']['db_password'],
            'charset'   => 'utf8',
            //'priority' => '2'
        ),
    );

    // Could be set in the $_configuration array
    if (isset($app['configuration']['db.options'])) {
        $defaultDatabaseOptions = $app['configuration']['db.options'];
    }

    $app->register(
        new Silex\Provider\DoctrineServiceProvider(),
        array(
            'dbs.options' => $defaultDatabaseOptions
        )
    );

    $mappings = array(
        array(
            /* If true, only simple notations like @Entity will work.
            If false, more advanced notations and aliasing via use will work.
            (Example: use Doctrine\ORM\Mapping AS ORM, @ORM\Entity)*/
            'use_simple_annotation_reader' => false,
            'type' => 'annotation',
            'namespace' => 'Entity',
            'path' => api_get_path(INCLUDE_PATH).'Entity',
            // 'orm.default_cache' =>
        ),
        array(
            'use_simple_annotation_reader' => false,
            'type' => 'annotation',
            'namespace' => 'Gedmo',
            'path' => api_get_path(SYS_PATH).'vendors/gedmo/doctrine-extensions/lib/Gedmo',
        )
    );

    // Setting Doctrine ORM.
    $app->register(
        new Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider,
        array(
            // Doctrine2 ORM cache
            /*'orm.default_cache' => 'apc', // array, apc, xcache, memcache, memcached
            'metadata_cache' => 'apc',
            'result_cache' => 'apc',*/
            // Proxies
            'orm.auto_generate_proxies' => true,
            'orm.proxies_dir' => $app['db.orm.proxies_dir'],
            'orm.proxies_namespace' => 'Doctrine\ORM\Proxy\Proxy',
            'orm.ems.default' => 'db_read',
            'orm.ems.options' => array(
               'db_read' => array(
                   'connection' => 'db_read',
                   'mappings' => $mappings,
               ),
               'db_write' => array(
                   'connection' => 'db_write',
                   'mappings' => $mappings,
               ),
            ),
        )
    );
}

// Setting Twig as a service provider.
$app->register(
    new Silex\Provider\TwigServiceProvider(),
    array(
        'twig.path' => array(
            $app['sys_root'].'main/template', //template folder
            $app['sys_root'].'plugin' //plugin folder
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
        $twig->addFilter('display_page_subheader', new Twig_Filter_Function('Display::page_subheader_and_translate'));
        $twig->addFilter('icon', new Twig_Filter_Function('Template::get_icon_path'));
        $twig->addFilter('format_date', new Twig_Filter_Function('Template::format_date'));

        return $twig;
    })
);

// Developer tools.

if (is_writable($app['sys_temp_path'])) {
    if ($app['show_profiler']) {
        // Adding Symfony2 web profiler (memory, time, logs, etc)
        $app->register(
            $p = new Silex\Provider\WebProfilerServiceProvider(),
            array(
                'profiler.cache_dir' => $app['profiler.cache_dir'],
            )
        );
        $app->mount('/_profiler', $p);

        // PHP errors for cool kids
        //$app->register(new Whoops\Provider\Silex\WhoopsServiceProvider);
    }
}

// Pagerfanta settings (Pagination using Doctrine2, arrays, etc)

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

// Prompts Doctrine SQL queries using Monolog.

$app['dbal_logger'] = $app->share(function() {
    //return new Doctrine\DBAL\Logging\DebugStack();
});

if ($app['debug']) {
    /*$logger = new Doctrine\DBAL\Logging\DebugStack();
    $app['db.config']->setSQLLogger($logger);
    $app->after(function() use ($app, $logger) {
        // Log all queries as DEBUG.
        $app['monolog']->addDebug('---- Doctrine SQL queries ----');
        foreach ($logger->queries as $query) {
            $app['monolog']->addDebug(
                $query['sql'],
                array(
                    'params' => $query['params'],
                    'types'  => $query['types'],
                    'executionMS' => $query['executionMS']
                )
            );
        }
        $app['monolog']->addDebug('---- End Doctrine SQL queries ----');
    });*/
}

// Email service provider.
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

// Mailer
$app['mailer'] = $app->share(function ($app) {
    return new \Swift_Mailer($app['swiftmailer.transport']);
});

// Assetic service provider.

if ($app['assetic.enabled']) {

    $app->register(new SilexAssetic\AsseticServiceProvider(), array(
        'assetic.options' => array(
            'debug'            => $app['debug'],
            'auto_dump_assets' => $app['assetic.auto_dump_assets'],
        )
    ));

    // Less filter
    $app['assetic.filter_manager'] = $app->share(
        $app->extend('assetic.filter_manager', function($fm, $app) {
            $fm->set('lessphp', new Assetic\Filter\LessphpFilter());

            return $fm;
        })
    );

    $app['assetic.asset_manager'] = $app->share(
        $app->extend('assetic.asset_manager', function($am, $app) {
            $am->set('styles', new Assetic\Asset\AssetCache(
                new Assetic\Asset\GlobAsset(
                    $app['assetic.input.path_to_css'],
                    array($app['assetic.filter_manager']->get('lessphp'))
                ),
                new Assetic\Cache\FilesystemCache($app['assetic.path_to_cache'])
            ));

            $am->get('styles')->setTargetPath($app['assetic.output.path_to_css']);
            $am->set('scripts', new Assetic\Asset\AssetCache(
                new Assetic\Asset\GlobAsset($app['assetic.input.path_to_js']),
                new Assetic\Cache\FilesystemCache($app['assetic.path_to_cache'])
            ));
            $am->get('scripts')->setTargetPath($app['assetic.output.path_to_js']);

            return $am;
        })
    );
}


// Gaufrette service provider (to manage files/dirs) (not used yet)
/*
use Bt51\Silex\Provider\GaufretteServiceProvider\GaufretteServiceProvider;
$app->register(new GaufretteServiceProvider(), array(
    'gaufrette.adapter.class' => 'Local',
    'gaufrette.options' => array(api_get_path(SYS_DATA_PATH))
));
*/

// Use Symfony2 filesystem instead of custom scripts
$app->register(new Neutron\Silex\Provider\FilesystemServiceProvider());

/** Chamilo service provider. */

class ChamiloServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        // Database.
        $app['database'] = $app->share(function () use ($app) {
            $db = new Database($app['db'], $app['dbs']);
            return $db;
        });

        $database = $app['database'];

        // Template class
        $app['template'] = $app->share(function () use ($app) {
            $template = new Template($app, $app['database'], $app['security'], $app['translator']);
            return $template;
        });

        // Paths
        $app['paths'] = $app->share(function () use ($app) {
            return array(
                //'root_web' => $app['root_web'],
                'root_sys' => $app['root_sys'],
                'sys_root' => $app['root_sys'], // just an alias
                'sys_data_path' => $app['sys_data_path'],
                'sys_config_path' => $app['sys_config_path'],
                'sys_temp_path' => $app['sys_temp_path'],
                'sys_log_path' => $app['sys_log_path']
            );
        });

        // Chamilo data filesystem.
        $app['chamilo.filesystem'] = $app->share(function () use ($app) {
            $filesystem = new ChamiloLMS\Component\DataFilesystem\DataFilesystem($app['paths'], $app['filesystem']);
            return $filesystem;
        });

        // Page controller class.
        $app['page_controller'] = $app->share(function () use ($app) {
            $pageController = new PageController($app);
            return $pageController;
        });

        // Mail template generator.
        $app['mail_generator'] = $app->share(function () use ($app) {
            $mailGenerator = new ChamiloLMS\Component\Mail\MailGenerator($app['twig'], $app['mailer']);
            return $mailGenerator;
        });


        // Setting up name conventions
        $conventions = require_once $app['sys_root'].'main/inc/lib/internationalization_database/name_order_conventions.php';
        if (isset($configuration['name_order_conventions']) && !empty($configuration['name_order_conventions'])) {
            $conventions = array_merge($conventions, $configuration['name_order_conventions']);
        }
        $search1 = array('FIRST_NAME', 'LAST_NAME', 'TITLE');
        $replacement1 = array('%F', '%L', '%T');
        $search2 = array('first_name', 'last_name', 'title');
        $replacement2 = array('%f', '%l', '%t');
        $keyConventions = array_keys($conventions);
        foreach ($keyConventions as $key) {
            $conventions[$key]['format'] = str_replace($search1, $replacement1, $conventions[$key]['format']);
            $conventions[$key]['format'] = _api_validate_person_name_format(
                _api_clean_person_name(
                    str_replace('%', ' %', str_ireplace($search2, $replacement2, $conventions[$key]['format']))
                )
            );
            $conventions[$key]['sort_by'] = strtolower($conventions[$key]['sort_by']) != 'last_name' ? true : false;
        }
        $app['name_order_conventions'] = $conventions;
    }

    /**
     * @param Application $app
     */
    public function boot(Application $app)
    {

    }
}

// Registering Chamilo service provider.
$app->register(new ChamiloServiceProvider(), array());


// Controller as services definitions.
$app['pages.controller'] = $app->share(
    function () use ($app) {
        return new PagesController($app['pages.repository']);
    }
);

$app['index.controller'] = $app->share(
    function () use ($app) {
        $controller = new ChamiloLMS\Controller\IndexController($app);
        return $controller;
    }
);

$app['legacy.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\LegacyController($app);
    }
);

$app['userPortal.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\UserPortalController($app);
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

$app['course_home.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\CourseHomeController();
    }
);

$app['introduction_tool.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\IntroductionToolController();
    }
);

$app['certificate.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\CertificateController();
    }
);

$app['profile.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\User\ProfileController($app);
    }
);

$app['user.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\User\UserController($app);
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
        return new ChamiloLMS\Controller\ExerciseController($app);
    }
);

$app['admin.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\Admin\AdminController($app);
    }
);

$app['role.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\Admin\Administrator\RoleController($app);
    }
);

$app['question_score.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\Admin\Administrator\QuestionScoreController($app);
    }
);

$app['question_score_name.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\Admin\Administrator\QuestionScoreNameController($app);
    }
);

$app['model_ajax.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\ModelAjaxController();
    }
);

// Curriculum tool

$app['curriculum.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\Tool\Curriculum\CurriculumController($app);
    }
);

$app['curriculum_category.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\Tool\Curriculum\CurriculumCategoryController($app);
    }
);

$app['curriculum_item.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\Tool\Curriculum\CurriculumItemController($app);
    }
);

$app['curriculum_user.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\Tool\Curriculum\CurriculumUserController($app);
    }
);

$app['upgrade.controller'] = $app->share(
    function () use ($app) {
        return new ChamiloLMS\Controller\Admin\Administrator\UpgradeController($app);
    }
);

$app['html_editor'] = $app->share(function($app) {
    return new ChamiloLMS\Component\Editor\CkEditor\CkEditor($app['translator'], $app['url_generator']);
    //return new ChamiloLMS\Component\Editor\TinyMce\TinyMce($app['translator'], $app['url_generator']);

});