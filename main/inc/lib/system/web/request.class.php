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
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
    }
    
    public static function has($key){
        return isset($_REQUEST[$key]);
    }
    
    /**
     * Returns true if the request is a GET request. False otherwise.
     * 
     * @return bool
     */
    public static function is_get()
    {
        $method = self::server()->request_method();
        $method = strtoupper($method);
        return $method == 'GET';
    }
    
    public static function post($key, $default = null)
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
    
    /**
     * Returns true if the request is a POST request. False otherwise.
     * 
     * @return bool
     */
    public static function is_post()
    {
        $method = self::server()->request_method();
        $method = strtoupper($method);
        return $method == 'POST';
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