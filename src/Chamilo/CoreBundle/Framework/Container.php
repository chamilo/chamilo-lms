<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Framework;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Templating\Helper\CoreAssetsHelper;
use Chamilo\CoreBundle\Component\Editor\Editor;

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
    public static $configuration;
    public static $urlGenerator;
    public static $security;
    public static $translator;

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
     * @return RoleHierarchy
     */
    public static function getRoles()
    {
        return self::$roles;
    }

    /**
     * @return string
     */
    public static function getLogDir()
    {
        return self::$logDir;
    }

    /**
     * @return string
     */
    public static function getTempDir()
    {
        return self::$tempDir;
    }

    /**
     * @return string
     */
    public static function getRootDir()
    {
        return self::$rootDir;
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
        return self::$twig;
    }

    /**
     * @return Editor
     */
    public static function getHtmlEditor()
    {
        return self::$htmlEditor;
    }

    /**
     * @return UrlGeneratorInterface
     */
    public static function getUrlGenerator()
    {
        return self::$urlGenerator;
    }

    /**
     * @return SessionInterface;
     */
    public static function getSession()
    {
        return self::$session;
    }

    /**
     * @param SessionInterface $session
     */
    public static function setSession($session)
    {
        self::$session = $session;
    }

    /**
     * @return SecurityContextInterface
     */
    public static function getSecurity()
    {
        return self::$security;
    }

    /**
     * @return Translator
     */
    public static function getTranslator()
    {
        return self::$translator;
    }

    /**
     * @return CoreAssetsHelper
     */
    public static function getAsset()
    {
        return self::$assets;
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
        return self::$container->get('fos_user.user_manager');
    }
}
