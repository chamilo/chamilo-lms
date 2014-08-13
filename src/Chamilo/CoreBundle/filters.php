<?php
/* For licensing terms, see /license.txt */
use \ChamiloSession as Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Translation\Loader\PoFileLoader;
//use Symfony\Component\Translation\Loader\MoFileLoader;
use Symfony\Component\Translation\Dumper\MoFileDumper;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\MessageCatalogue;

use Chamilo\CoreBundle\Component\DataFilesystem\DataFilesystem;
use Application\Sonata\UserBundle\Entity\User;

/** Application Middlewares/Filters. */

/* A "before" middleware allows you to tweak the Request
 * before the controller is executed. */
/** @var Chamilo\CoreBundle\Framework\Application $app */
$app->before(
    function () use ($app) {
        /** @var Request $request */
        $request = $app['request'];

        // Checking configuration file. If does not exists redirect to the install folder.
        if (empty($app->getConfiguration())) {
            $url = str_replace('web', 'main/install', $request->getBasePath());
            return new RedirectResponse($url);
        }

        // Check data folder.
        if (!is_writable($app['path.data'])) {
            $app->abort(500, "data folder must be writable.");
        }

        // Checks temp folder permissions.
        if (!is_writable($app['path.temp'])) {
            $app->abort(500, "data/temp folder must be writable.");
        }

        //$this->app['new_breadcrumb'] = $this->getBreadcrumbs(null);

        // Checking that configuration is loaded.
        $configuration = $app->getConfigurationArray();
        if (!isset($configuration)) {
            $app->abort(500, '$configuration array must be set in the configuration.php file.');
        }

        // Starting the session for more info see: http://silex.sensiolabs.org/doc/providers/session.html
        $session = $request->getSession();
        $session->start();

        // Setting session obj
        Session::setSession($session);

        UserManager::setEntityManager($app['orm.em']);

        /** @var DataFilesystem $filesystem */
        $filesystem = $app['chamilo.filesystem'];

        if ($app['debug']) {
            // Creates data/temp folders for every request if debug is on.
            $filesystem->createFolders($app['temp.paths']->folders);
        }

        // If Assetic is enabled copy folders from theme inside "web/"
        if ($app['assetic.auto_dump_assets']) {
            $filesystem->copyFolders($app['temp.paths']->copyFolders);
        }

        // Check and modify the date of user in the track.e.online table
        Online::loginCheck(api_get_user_id());

        // Setting access_url id (multiple url feature)

        if (api_get_multiple_access_url()) {
            $_configuration = $app->getConfigurationArray();
            $_configuration['access_url'] = 1;
            $access_urls = api_get_access_urls();

            $protocol = $request->getScheme().'://';
            $request_url1 = $protocol.$_SERVER['SERVER_NAME'].'/';
            $request_url2 = $protocol.$_SERVER['HTTP_HOST'].'/';

            foreach ($access_urls as & $details) {
                if ($request_url1 == $details['url'] or $request_url2 == $details['url']) {
                    $_configuration['access_url'] = $details['id'];
                }
            }
            Session::write('url_id', $_configuration['access_url']);
            Session::write('url_info', api_get_current_access_url_info($_configuration['access_url']));
        } else {
            Session::write('url_id', 1);
        }

        // Loading portal settings from DB.
        $settingsRefreshInfo = api_get_settings_params_simple(array('variable = ?' => 'settings_latest_update'));
        $settingsLatestUpdate = $settingsRefreshInfo ? $settingsRefreshInfo['selected_value'] : null;

        $settings = Session::read('_setting');

        if (empty($settings)) {
            api_set_settings_and_plugins();
        } else {
            if (isset($settings['settings_latest_update']) && $settings['settings_latest_update'] != $settingsLatestUpdate) {
                api_set_settings_and_plugins();
            }
        }

        $app['plugins'] = Session::read('_plugins');

        // Default template style.
        $templateStyle = api_get_setting('template');
        $templateStyle = isset($templateStyle) && !empty($templateStyle) ? $templateStyle : 'default';
        if (!is_dir($app['path.base'].'main/template/'.$templateStyle)) {
            $templateStyle = 'default';
        }
        $app['template_style'] = $templateStyle;

        // Default layout.
        $app['default_layout'] = $app['template_style'].'/layout/layout_1_col.tpl';

        // Setting languages.
        $app['api_get_languages'] = api_get_languages();
        $app['language_interface'] = $language_interface = api_get_language_interface();

        // Reconfigure template now that we know the user.
        $app['template.hide_global_chat'] = !api_is_global_chat_enabled();

        /** Setting the course quota */
        // Default quota for the course documents folder
        $default_quota = api_get_setting('default_document_quotum');
        // Just in case the setting is not correctly set
        if (empty($default_quota)) {
            $default_quota = 100000000;
        }

        //define('DEFAULT_DOCUMENT_QUOTA', $default_quota);

        // Specification for usernames:
        // 1. ASCII-letters, digits, "." (dot), "_" (underscore) are acceptable, 40 characters maximum length.
        // 2. Empty username is formally valid, but it is reserved for the anonymous user.
        // 3. Checking the login_is_email portal setting in order to accept 100 chars maximum

        $default_username_length = 40;
        if (api_get_setting('login_is_email') == 'true') {
            $default_username_length = 100;
        }

        define('USERNAME_MAX_LENGTH', $default_username_length);

        $user = null;

        /** Security component. */
        /** @var SecurityContext $security */
        $security = $app['security'];

        if ($security->isGranted('IS_AUTHENTICATED_FULLY')) {

            // Checking token in order to get the current user.
            $token = $security->getToken();
            if (null !== $token) {
                /** @var User $user */
                $user = $token->getUser();
                $filesystem->createMyFilesFolder($user);
            }

            // For backward compatibility.
            $userInfo = api_get_user_info($user->getUserId());
            $userInfo['is_anonymous'] = false;

            Session::write('_user', $userInfo);
            $app['current_user'] = $userInfo;

            // Setting admin permissions.
            if ($security->isGranted('ROLE_ADMIN')) {
                Session::write('is_platformAdmin', true);
            }

            // Setting teachers permissions.
            if ($security->isGranted('ROLE_TEACHER')) {
                Session::write('is_allowedCreateCourse', true);
            }

        } else {
            Session::erase('_user');
            Session::erase('is_platformAdmin');
            Session::erase('is_allowedCreateCourse');
        }

        /** Translator component. */
        $app['translator.cache.enabled'] = false;

        // Platform language.
        $language = api_get_setting('platformLanguage');

        // From the login page.
        $requestLanguage = $request->get('language');
        if (!empty($requestLanguage)) {
            $language = $requestLanguage;
        }

        // From user.
        if ($user && $userInfo) {
            // @todo check why this does not works
            //$language = $user->getLanguage();
            $language = $userInfo['language'];
        }

        // From the course.
        $courseInfo = api_get_course_info();
        if ($courseInfo && !empty($courseInfo)) {
            $language = $courseInfo['language'];
        }

        // Setting language.
        if (!empty($language)) {
            $iso = api_get_language_isocode($language);
            /** @var Translator $translator */
            $translator = $app['translator'];
            $translator->setLocale($iso);
        }

        $app['language'] = $language;

        $app['translator'] = $app->share($app->extend('translator', function ($translator, $app) {
            $locale = $translator->getLocale();

            /** @var Translator $translator  */
            if ($app['translator.cache.enabled']) {
                //$phpFileDumper = new Symfony\Component\Translation\Dumper\PhpFileDumper();
                $dumper = new MoFileDumper();
                $catalogue = new MessageCatalogue($locale);
                $catalogue->add(array('foo' => 'bar'));
                $dumper->dump($catalogue, array('path' => $app['path.temp']));
            } else {
                $translationPath = $app['path.base'].'src/Chamilo/Resources/translations/';

                $translator->addLoader('pofile', new PoFileLoader());
                $file = $translationPath.$locale.'.po';
                if (file_exists($file)) {
                    $translator->addResource('pofile', $file, $locale);
                }
                $customFile = $translationPath.$locale.'.custom.po';
                if (file_exists($customFile)) {
                    $translator->addResource('pofile', $customFile, $locale);
                }

                // Validators
                $file = $app['path.base'].'vendor/symfony/validator/Symfony/Component/Validator/Resources/translations/validators.'.$locale.'.xlf';
                $translator->addLoader('xlf', new XliffFileLoader());
                if (file_exists($file)) {
                    $translator->addResource('xlf', $file, $locale, 'validators');
                }

                /*$translator->addLoader('mofile', new MoFileLoader());
                $filePath = api_get_path(SYS_PATH).'main/locale/'.$locale.'.mo';
                if (!file_exists($filePath)) {
                    $filePath = api_get_path(SYS_PATH).'main/locale/en.mo';
                }
                $translator->addResource('mofile', $filePath, $locale);*/
                return $translator;
            }
        }));

        // Check if we are inside a Chamilo course tool
        /*$isCourseTool = (strpos($request->getPathInfo(), 'courses/') === false) ? false : true;

        if (!$isCourseTool) {
            // @todo add a before in controller in order to load the courses and course_session object
            $isCourseTool = (strpos($request->getPathInfo(), 'editor/filemanager') === false) ? false : true;
            var_dump($isCourseTool);
            var_dump(api_get_course_id());exit;
        }*/

        $studentView = $request->get('isStudentView');
        if (!empty($studentView)) {
            if ($studentView == 'true') {
                $session->set('studentview', 'studentview');
            } else {
                $session->set('studentview', 'teacherview');
            }
        }
    }
);

/** An after application middleware allows you to tweak the Response before it is sent to the client */
$app->after(
    function (Request $request, Response $response) use ($app) {
    }
);

/** A "finish" application middleware allows you to execute tasks after the Response has been sent to
 * the client (like sending emails or logging) */
$app->finish(
    function (Request $request) use ($app) {

    }
);
