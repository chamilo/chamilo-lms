<?php

/**
 * Provides access to various HTTP request elements: GET, POST, FILE, etc paramaters.
 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Request
{

    public static function get($key, $default = null)
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }
    
    public static function post($key, $default = null)
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
   
    /**
     *
     * @return RequestServer
     */
    static function server()
    {
        return RequestServer::instance();
    }

    static function file($key, $default = null)
    {
        return isset($_FILES[$key]) ? $_FILES[$key] : $default;
    }

    static function environment($key, $default = null)
    {
        return isset($_ENV[$key]) ? $_ENV[$key] : $default;
    }

}