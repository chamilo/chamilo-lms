<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Framework;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Templating\Helper\CoreAssetsHelper;
use Chamilo\CoreBundle\Component\Editor\Editor;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class Container
 * This class is a way to access Symfony2 services in legacy Chamilo code.
 * @package Chamilo\CoreBundle\Framework
 */
class Container
{
    /**
     * @var ContainerInterface
     */
    public static $container;
    public static $session;
    public static $request;
    public static $configuration;
    public static $environment;
    public static $urlGenerator;
    public static $checker;
    public static $translator;
    public static $mailer;
    public static $template;

    public static $rootDir;
    public static $logDir;
    public static $tempDir;
    public static $dataDir;
    public static $courseDir;
    public static $configDir;
    public static $assets;
    public static $htmlEditor;
    public static $twig;
    public static $roles;
    /** @var  string  */
    public static $legacyTemplate = '@ChamiloTheme/Layout/layout_one_col.html.twig';

    /**
     * @param ContainerInterface $container
     */
    public static function setContainer($container)
    {
        self::$container = $container;
    }

    /**
     * @return string
     */
    public static function getConfigDir()
    {
        return self::$configDir;
    }

    /**
     * @param string $parameter
     *
     * @return mixed
     */
    public static function getParameter($parameter)
    {
        if (self::$container->hasParameter($parameter)) {
            return self::$container->getParameter($parameter);
        }

        return false;
    }

    /**
     * @return string
     */
    public static function getEnvironment()
    {
        return self::$container->get('kernel')->getEnvironment();
    }

    /**
     * @return RoleHierarchy
     */
    public static function getRoles()
    {
        return self::$container->get('security.role_hierarchy');
    }

    /**
     * @return string
     */
    public static function getLogDir()
    {
        return self::$container->get('kernel')->getLogDir();
    }

    /**
     * @return string
     */
    public static function getTempDir()
    {
        return self::$container->get('kernel')->getCacheDir().'/';
    }

    /**
     * @return string
     */
    public static function getRootDir()
    {
        return self::$container->get('kernel')->getRealRootDir();
    }

    /**
     * @return string
     */
    public static function getUrlAppend()
    {
        return self::$container->get('kernel')->getUrlAppend();
    }

    /**
     * @return string
     */
    public static function isInstalled()
    {
        return self::$container->get('kernel')->isInstalled();
    }

    /**
     * @return string
     */
    public static function getDataDir()
    {
        return self::$dataDir;
    }

    /**
     * @return string
     */
    public static function getCourseDir()
    {
        return self::$courseDir;
    }

    /**
     * @return \Twig_Environment
     */
    public static function getTwig()
    {
        return self::$container->get('twig');
    }

    /**
     * @return \Symfony\Bundle\TwigBundle\TwigEngine
     */
    public static function getTemplating()
    {
        return self::$container->get('templating');
    }

    /**
     * @return Editor
     */
    public static function getHtmlEditor()
    {
        return self::$container->get('chamilo_core.html_editor');
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Routing\Router
     */
    public static function getUrlGenerator()
    {
        return self::$container->get('router');
    }

    /**
     * @return object|Request
     */
    public static function getRequest()
    {
        if (!empty(self::$request)) {
            return self::$request;
        }
        return self::$container->get('request');
    }

    public static function setRequest($request)
    {
        self::$request = $request;
    }

    /**
     * @return Session
     */
    public static function getSession()
    {
        return self::$container->get('session');
    }

    /**
     * @return AuthorizationChecker
     */
    public static function getAuthorizationChecker()
    {
        return self::$container->get('security.authorization_checker');
    }

    /**
     * @return object|\Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage
     */
    public static function getTokenStorage()
    {
        return self::$container->get('security.token_storage');
    }

    /**
     * @return TranslatorInterface
     */
    public static function getTranslator()
    {
        return self::$container->get('translator.default');
    }

    /**
     * @return CoreAssetsHelper
     */
    public static function getAsset()
    {
        return self::$container->get('templating.helper.assets');
    }

    /**
     * @return \Swift_Mailer
     */
    public static function getMailer()
    {
       return self::$container->get('mailer');
    }

    /**
     * @return \Elao\WebProfilerExtraBundle\TwigProfilerEngine
     */
    public static function getTemplate()
    {
        return self::$container->get('templating');
    }

    /**
     * @return \Chamilo\SettingsBundle\Manager\SettingsManager
     */
    public static function getSettingsManager()
    {
        return self::$container->get('chamilo.settings.manager');
    }

    /**
     * @return \Chamilo\CourseBundle\Manager\SettingsManager
     */
    public static function getCourseSettingsManager()
    {
        return self::$container->get('chamilo_course.settings.manager');
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public static function getEntityManager()
    {
        return \Database::getManager();
    }

    /**
     * @return \Sonata\UserBundle\Entity\UserManager
     */
    public static function getUserManager()
    {
        //return self::$container->get('sonata.user.user_manager');
        return self::$container->get('sonata.user.user_manager');
    }

    /**
     * @return \Sonata\UserBundle\Entity\GroupManager
     */
    public static function getGroupManager()
    {
        return self::$container->get('fos_user.group_manager');
    }

    /**
     * @return \Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher
     */
    public static function getEventDispatcher()
    {
        return self::$container->get('event_dispatcher');
    }

    /**
     * @return \Symfony\Component\Form\FormFactory
     */
    public static function getFormFactory()
    {
        return self::$container->get('form.factory');
    }

    /**
     * @param string $message
     * @param string $type error|success|warning|danger
     */
    public static function addFlash($message, $type = 'success')
    {
        $session = self::getSession();
        $session->getFlashBag()->add($type, $message);
    }

    /**
     * @return object|\Symfony\Cmf\Component\Routing\ChainRouter
     */
    public static function getRouter()
    {
        return self::$container->get('router');
    }

    /**
     * @return \Chamilo\CourseBundle\ToolChain
     */
    public static function getToolChain()
    {
        return self::$container->get('chamilo_course.tool_chain');
    }

}
