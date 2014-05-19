<?php

/**
 * ChamiloSession class definition
  */
class ChamiloSession
{
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

    public static function getConfigDir()
    {
        return self::$configDir;
    }

    public static function getLogDir()
    {
        return self::$logDir;
    }

    public static function getTempDir()
    {
        return self::$tempDir;
    }

    public static function getRootDir()
    {
        return self::$rootDir;
    }

    public static function getDataDir()
    {
        return self::$dataDir;
    }

    public static function getCourseDir()
    {
        return self::$courseDir;
    }

    /**
     * @return Symfony\Component\Routing\Generator\UrlGeneratorInterface
     */
    public static function getUrlGenerator()
    {
        return self::$urlGenerator;
    }

    /**
     * @return Symfony\Component\HttpFoundation\Session\SessionInterface;
     */
    public static function getSession()
    {
        return self::$session;
    }

    /**
     * @return Symfony\Component\Security\Core\SecurityContextInterface
     */
    public static function getSecurity()
    {
        return self::$security;
    }

    /**
     * @return Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    public static function getTranslator()
    {
        return self::$translator;
    }

    /**
     * @param $session
     */
    public static function setSession($session)
    {
        self::$session = $session;
    }

    /**
     * @param $variable
     * @param null $default
     * @return null
     */
    public static function read($variable, $default = null)
    {
        $result = self::$session->get($variable);
        // check if the value exists in the $_SESSION array
        if (empty($result)) {
            return isset($_SESSION[$variable]) ? $_SESSION[$variable] : $default;
        } else {
            return $result;
        }
    }

    /**
     * @param $variable
     * @param $value
     */
    public static function write($variable, $value)
    {
        // Writing the session in 2 instances because
        $_SESSION[$variable] = $value;
        self::$session->set($variable, $value);
    }

    /**
     * @param $variable
     */
    public static function erase($variable)
    {
        $variable = (string) $variable;
        self::$session->remove($variable);

        if (isset($GLOBALS[$variable])) {
            unset($GLOBALS[$variable]);
        }
        if (isset($_SESSION[$variable])) {
            unset($_SESSION[$variable]);
        }
    }

    /**
     *
     */
    public static function clear()
    {
        self::$session->clear();
    }

    /**
     *
     */
    public static function destroy()
    {
        self::$session->invalidate();
    }
}
