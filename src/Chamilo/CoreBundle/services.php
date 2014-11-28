<?php
/* For licensing terms, see /license.txt */

/**
 * This file includes all the services that are loaded via
 * the ServiceProviderInterface
 * @package chamilo.services
 */
use Doctrine\Common\Persistence\AbstractManagerRegistry;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmExtension;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntityValidator;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use FranMoreno\Silex\Provider\PagerfantaServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\SecurityServiceProvider;
use MediaAlchemyst\Alchemyst;
use MediaAlchemyst\MediaAlchemystServiceProvider;
use MediaVorus\MediaVorusServiceProvider;
use FFMpeg\FFMpegServiceProvider;
use PHPExiftool\PHPExiftoolServiceProvider;
use Knp\Provider\ConsoleServiceProvider;

use Chamilo\CoreBundle\Component\Auth\LoginSuccessHandler;
use Chamilo\CoreBundle\Component\Auth\LogoutSuccessHandler;
use Chamilo\CoreBundle\Component\Auth\LoginListener;
use Chamilo\CoreBundle\Component\Editor\Connector;
use Chamilo\CoreBundle\Component\Validator\ConstraintValidatorFactory;
use Chamilo\CoreBundle\Component\Mail\MailGenerator;
use Chamilo\CoreBundle\Component\DataFilesystem\DataFilesystem;

use Chamilo\CoreBundle\Framework\PageController;
use Chamilo\CoreBundle\Framework\Template;

if (isset($app->getConfiguration()->services->mediaalchemyst)) {
    $unoconv = null;
    if (isset($app->getConfiguration()['services']['unoconv']['unoconv.binaries'])) {
        $unoconv = $app->getConfiguration()['services']['unoconv']['unoconv.binaries'];
    }
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
            'unoconv.binaries'             => $unoconv,
            'unoconv.timeout'              => 60,
            //'exiftool.reader'              => '/path/to/custom/exiftool.reader',
            //'exiftool.writer'              => '/path/to/custom/exiftool.writer'
        ),
        //'media-alchemyst.logger' => $logger,  // A PSR Logger
    ));
}

$app->register(new ConsoleServiceProvider(), array(
    'console.name'              => 'Chamilo',
    'console.version'           => '1.0.0',
    'console.project_directory' => __DIR__.'/..'
));


// Registering Password encoder.
$app['security.encoder.digest'] = $app->share(function($app) {
    // use the sha1 algorithm
    // don't base64 encode the password
    // use only 1 iteration
    return new MessageDigestPasswordEncoder(
        $app->getConfiguration()->password_encryption,
        false,
        1
    );
});

// What to do when login success?
$app['security.authentication.success_handler.secured'] = $app->share(function($app) {
    return new LoginSuccessHandler($app['url_generator'], $app['security']);
});

// What to do when logout?
$app['security.authentication.logout_handler.secured'] = $app->share(function($app) {
    return new LogoutSuccessHandler($app['url_generator'], $app['security']);
});

// What to do when switch user?
$app['security.authentication_listener.switch_user.secured'] = $app->share(function($app) {
    return new LoginListener();
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


$app['view_path'] = $app['path.base'].'src/Chamilo/Resources/views/';

// Setting Twig as a service provider.

$app->register(
    new Silex\Provider\TwigServiceProvider(),
    array(
        'twig.path' => array(
            $app['view_path'],
            $app['path.base'].'plugin' //plugin folder
        ),
        // twitter bootstrap form twig templates
        'twig.form.templates' => array(
            'form_div_layout.html.twig',
            'default/form/form_custom_template.tpl'
        ),
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

// Setting Twig options.
$app['twig'] = $app->share($app->extend('twig', function (\Twig_Environment $twig) {
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
}));

// Developer tools.
if (is_writable($app['path.temp'])) {
    if ($app->getConfiguration()->show_profiler) {
        // Adding Symfony2 web profiler (memory, time, logs, etc)
        $app->register(
            $p = new Silex\Provider\WebProfilerServiceProvider(),
            array(
                'profiler.cache_dir' => $app['profiler.cache_dir'],
            )
        );
        $app->mount('/_profiler', $p);

        // Better PHP errors
        //$app->register(new Whoops\Provider\Silex\WhoopsServiceProvider);

        /*$app['xhprof.location'] = '/var/www/xhprof';
        $app['xhprof.host'] = 'http://localhost/xhprof/xhprof_html/index.php';
        $app->register(new \Oziks\Provider\XHProfServiceProvider());*/
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

// Use Symfony2 filesystem instead of custom scripts.
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
            $template = new Template(
                $app,
                $app['database'],
                $app['security'],
                $app['translator'],
                $app['url_generator']
            );
            return $template;
        });

        Display::setUrlGenerator($app['url_generator']);

        $app['html_editor'] = $app->share(function($app) {
            $editor = new Chamilo\CoreBundle\Component\Editor\CkEditor\CkEditor(
                $app['translator'],
                $app['url_generator'],
                $app['template'],
                $app['course']
            );
            $editor->setJavascriptToInclude();

            return $editor;
            /*return new Chamilo\CoreBundle\Component\Editor\TinyMce\TinyMce(
                $app['translator'], $app['url_generator']
            );*/
        });

        $app['editor_connector'] = $app->share(function ($app) {
            $token = $app['security']->getToken();
            $user = $token->getUser();

            return new Connector(
                $app['orm.em'],
                $app['paths'],
                $app['url_generator'],
                $app['translator'],
                $app['security'],
                $user,
                $app['course']
            );
        });

        // Paths
        // @todo
        $app['paths'] = $app->share(function () use ($app) {
            return array(
                'root_sys' => $app['path.base'],
                'sys_root' => $app['path.base'], // just an alias
                'sys_data_path' => $app['path.data'],
                'sys_config_path' => $app['path.config'],
                'path.temp' => $app['path.temp'],
                'sys_log_path' => $app['path.logs']
            );
        });

        $app['course'] = $app->share(function () use ($app) {
            $request = $app['request'];
            $session = $request->getSession();
            $courseCode = $request->get('course');

            if (empty($courseCode)) {
                $courseCode = $session->get('_cid');
            }

            if (!empty($courseCode)) {
                // Converting /courses/XXX/ to a Entity/Course object.
                return $app['orm.em']->getRepository('Chamilo\CoreBundle\Entity\Course')->findOneByCode($courseCode);
                //$app['template']->assign('course', $course);
                return $course;
            }
            return null;
        });

        $app['course_session'] = $app->share(function () use ($app) {
            $request = $app['request'];
            $session = $request->getSession();
            $sessionId = $request->get('id_session');
            if (empty($sessionId)) {
                $sessionId = $session->get('id_session');
            }
            if (!empty($sessionId)) {
                return $app['orm.em']->getRepository('Chamilo\CoreBundle\Entity\Session')->findOneById($sessionId);
//                $app['template']->assign('course_session', $courseSession);
                return $courseSession;
            }
            return null;
        });

        // Chamilo data filesystem.
        $app['chamilo.filesystem'] = $app->share(function () use ($app) {
            $mediaConverter = null;
            if (isset($app->getConfiguration()->services->mediaalchemyst)) {
                $mediaConverter = $app['media-alchemyst'];
            }
            $filesystem = new DataFilesystem(
                $app['paths'],
                $app['filesystem'],
                $app['editor_connector'],
                $mediaConverter
            );
            return $filesystem;
        });

        // Page controller class.
        $app['page_controller'] = $app->share(function () use ($app) {
            $pageController = new PageController($app);
            return $pageController;
        });

        // Mail template generator.
        $app['mail_generator'] = $app->share(function () use ($app) {
            $mailGenerator = new MailGenerator($app['twig'], $app['mailer']);
            return $mailGenerator;
        });

        // Setting up name conventions
        $conventions = require_once $app['path.base'].'main/inc/lib/internationalization_database/name_order_conventions.php';
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

        $app['exporter'] = $app->share(function () use ($app) {
            return new Chamilo\CoreBundle\Framework\Exporter();
        });

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
/*$app['pages.controller'] = $app->share(
    function () use ($app) {
        return new PagesController($app['pages.repository']);
    }
);*/

$controllers = array(
    'index.controller' => array(
        'IndexController',
        '/'
    ),
    'legacy.controller' => array(
        'LegacyController',
        false
    ),
    // Admin group
    'admin.controller' => array(
        'Admin\AdminController',
        '/admin/'
    ),
    'branch.controller' => array(
        'Admin\Administrator\BranchController',
        '/admin/administrator/branch'
    ),
    'jury.controller' => array(
        'Admin\Administrator\JuryController',
        '/admin/administrator/jury'
    ),
    'question_manager.controller' => array(
        'Admin\QuestionManager\QuestionManagerController',
        '/admin/questionmanager'
    ),
    'question_score.controller' => array(
        'Admin\Administrator\QuestionScoreController',
        '/admin/administrator/question_scores'
    ),
    'question_score_name.controller' => array(
        'Admin\Administrator\QuestionScoreNameController',
        '/admin/administrator/question_score_names'
    ),
    'role.controller' => array(
        'Admin\Administrator\RoleController',
        '/admin/administrator/roles'
    ),
    'upgrade.controller' => array(
        'Admin\Administrator\UpgradeController',
        '/admin/administrator/upgrade'
    ),
    // App group
    'session_tree.controller' => array(
        'App\SessionPath\SessionTreeController',
        'app/session_path/tree'
    ),
    'session_path.controller' => array(
        'App\SessionPath\SessionPathController',
        'app/session_path'
    ),
    'model_ajax.controller' => array(
        'App\ModelAjax\ModelAjaxController',
        '/ajax'
    ),
    'editor.controller' => array(
        'App\Editor\EditorController',
        '/editor/'
    ),
    'news.controller' => array(
        'App\News\NewsController',
        '/app/news/'
    ),
    'certificate.controller' => array(
        'App\Certificate\CertificateController',
        '/app/certificates/'
    ),
    // Tools (Course Tools)
    'course_home.controller' => array(
        'Tool\CourseHome\CourseHomeController',
        'courses/{courseCode}/'
    ),
    'introduction.controller' => array(
        'Tool\Introduction\IntroductionController',
        'courses/{courseCode}/introduction'
    ),
    'learnpath.controller' => array(
        'Tool\LearningPath\LearningPathController',
        'courses/{courseCode}/learning_path'
    ),
    'exercise_manager.controller' => array(
        'Tool\Exercise\ExerciseController',
        '/courses/{courseCode}/exercise2'
    ),
    'curriculum.controller' => array(
        'Tool\Curriculum\CurriculumController',
        '/courses/{courseCode}/curriculum'
    ),
    'curriculum_category.controller' => array(
        'Tool\Curriculum\CurriculumCategoryController',
        '/courses/{courseCode}/curriculum/category'
    ),
    'curriculum_item.controller' => array(
        'Tool\Curriculum\CurriculumItemController',
        '/courses/{courseCode}/curriculum/item'
    ),
    'curriculum_user.controller' => array(
        'Tool\Curriculum\CurriculumUserController',
        '/courses/{courseCode}/curriculum/user'
    ),
    // Users (Social)
    'profile.controller' => array(
        'User\ProfileController',
        '/user/'
    ),
    'user.controller' => array(
        'User\UserController',
    )
);

$app['controller_array'] = $controllers;

foreach ($controllers as $name => $controllerInfo) {
    $class = 'Chamilo\CoreBundle\Controller\\' . $controllerInfo[0];
    $app[$name] = $app->share(
        function () use ($app, $class) {
            return new $class($app);
        }
    );
}

/*if (isset($app['configuration']['unoconv.binaries'])) {
    $app->register(new Unoconv\UnoconvServiceProvider(), array(
        'unoconv.configuration' => array(
            'unoconv.binaries' => $_configuration['services']['unoconv']['unoconv.binaries'],
            'timeout'          => 42,
        ),
        'unoconv.logger'  => $app->share(function () use ($app) {
            return $app['monolog']; // use Monolog service provider
        }),
    ));
}
*/

/*
$app->register(
    new Chamilo\CoreBundle\Provider\BootstrapSilexProvider(),
    array(

    )
);*/
