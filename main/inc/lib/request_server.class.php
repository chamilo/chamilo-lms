<?php

/**
 * Provides access to the $_SERVER variable. Useful to have autocompletion working. 
 * Access through the Request class.
 * 
 * Example:
 * 
 *  Request :: server()-> request_uri()
 * 
 * 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva 
 */
class RequestServer
{

    public static function instance()
    {
        static $result = null;
        if (empty($result))
        {
            $result = new self();
        }
        return $result;
    }

    function get($key, $default = null)
    {
        return isset($_SERVER[$key]) ? $_SERVER[$key] : null;
    }

    function request_time()
    {
        return isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : null;
    }

    function http_host()
    {
        return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
    }

    function http_user_agent()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    }

    function http_accept()
    {
        return isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : null;
    }

    function http_accept_language()
    {
        return isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null;
    }

    function http_accept_encoding()
    {
        return isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : null;
    }

    function http_connection()
    {
        return isset($_SERVER['HTTP_CONNECTION']) ? $_SERVER['HTTP_CONNECTION'] : null;
    }

    function http_cookie()
    {
        return isset($_SERVER['HTTP_COOKIE']) ? $_SERVER['HTTP_COOKIE'] : null;
    }

    function http_cache_control()
    {
        return isset($_SERVER['HTTP_CACHE_CONTROL']) ? $_SERVER['HTTP_CACHE_CONTROL'] : null;
    }

    function path()
    {
        return isset($_SERVER['PATH']) ? $_SERVER['PATH'] : null;
    }

    function systemroot()
    {
        return isset($_SERVER['SystemRoot']) ? $_SERVER['SystemRoot'] : null;
    }

    function comspec()
    {
        return isset($_SERVER['COMSPEC']) ? $_SERVER['COMSPEC'] : null;
    }

    function pathext()
    {
        return isset($_SERVER['PATHEXT']) ? $_SERVER['PATHEXT'] : null;
    }

    function windir()
    {
        return isset($_SERVER['WINDIR']) ? $_SERVER['WINDIR'] : null;
    }

    function server_signature()
    {
        return isset($_SERVER['SERVER_SIGNATURE']) ? $_SERVER['SERVER_SIGNATURE'] : null;
    }

    function server_software()
    {
        return isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : null;
    }

    function server_name()
    {
        return isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;
    }

    function server_addr()
    {
        return isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : null;
    }

    function server_port()
    {
        return isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : null;
    }

    function remote_addr()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }

    function document_root()
    {
        return isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : null;
    }

    function server_admin()
    {
        return isset($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : null;
    }

    function script_filename()
    {
        return isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : null;
    }

    function remote_port()
    {
        return isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : null;
    }

    function gateway_interface()
    {
        return isset($_SERVER['GATEWAY_INTERFACE']) ? $_SERVER['GATEWAY_INTERFACE'] : null;
    }

    function server_protocol()
    {
        return isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : null;
    }

    function request_method()
    {
        return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
    }

    function query_string()
    {
        return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
    }

    function request_uri()
    {
        return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
    }

    function script_name()
    {
        return isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : null;
    }

    function php_self()
    {
        return isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : null;
    }

}