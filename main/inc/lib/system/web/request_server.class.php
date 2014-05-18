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
        if (empty($result)) {
            $result = new self();
        }
        return $result;
    }

    function get($key, $default = null)
    {
        return isset($_SERVER[$key]) ? $_SERVER[$key] : null;
    }

    /**
     * The timestamp of the start of the request. Available since PHP 5.1.0. 
     * 
     * @return string 
     */
    function request_time()
    {
        return isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : null;
    }

    /**
     * Contents of the Host: header from the current request, if there is one. 
     * 
     * @return string 
     */
    function http_host()
    {
        return isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
    }

    /**
     * Contents of the User-Agent: header from the current request, if there is one. This is a string denoting the user agent being which is accessing the page. A typical example is: Mozilla/4.5 [en] (X11; U; Linux 2.2.9 i586). Among other things, you can use this value with get_browser() to tailor your page's output to the capabilities of the user agent. 
     * 
     * @return string 
     */
    function http_user_agent()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
    }

    /**
     * Contents of the Accept: header from the current request, if there is one. 
     * 
     * @return string 
     */
    function http_accept()
    {
        return isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : null;
    }

    /**
     * Contents of the Accept-Language: header from the current request, if there is one. Example: 'en'. 
     * 
     * @return string 
     */
    function http_accept_language()
    {
        return isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : null;
    }

    /**
     * Contents of the Accept-Encoding: header from the current request, if there is one. Example: 'gzip'. 
     * 
     * @return string 
     */
    function http_accept_encoding()
    {
        return isset($_SERVER['HTTP_ACCEPT_ENCODING']) ? $_SERVER['HTTP_ACCEPT_ENCODING'] : null;
    }

    /**
     * Contents of the Connection: header from the current request, if there is one. Example: 'Keep-Alive'. 
     * 
     * @return string 
     */
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

    /**
     * Server identification string, given in the headers when responding to requests. 
     * 
     * @return string 
     */
    function server_software()
    {
        return isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : null;
    }

    /**
     * The name of the server host under which the current script is executing. If the script is running on a virtual host, this will be the value defined for that virtual host.
     * 
     * @return string
     */
    function server_name()
    {
        return isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;
    }

    /**
     * The IP address of the server under which the current script is executing. 
     * 
     * @return string 
     */
    function server_addr()
    {
        return isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : null;
    }

    function server_port()
    {
        return isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : null;
    }

    /**
     * The IP address from which the user is viewing the current page. 
     * 
     * @return string 
     */
    function remote_addr()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }

    /**
     * The document root directory under which the current script is executing, as defined in the server's configuration file. 
     * @return string 
     */
    function document_root()
    {
        return isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : null;
    }

    /**
     * The value given to the SERVER_ADMIN (for Apache) directive in the web server configuration file. If the script is running on a virtual host, this will be the value defined for that virtual host. 
     * 
     * @return string 
     */
    function server_admin()
    {
        return isset($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : null;
    }

    /**
     * The absolute pathname of the currently executing script.
     * 
     * Note:
     * 
     *  If a script is executed with the CLI, as a relative path, such as file.php or ../file.php, $_SERVER['SCRIPT_FILENAME'] will contain the relative path specified by the user. 
     * 
     * @return string 
     */
    function script_filename()
    {
        return isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : null;
    }

    /**
     * The port being used on the user's machine to communicate with the web server. 
     * 
     * @return string 
     */
    function remote_port()
    {
        return isset($_SERVER['REMOTE_PORT']) ? $_SERVER['REMOTE_PORT'] : null;
    }

    /**
     * What revision of the CGI specification the server is using; i.e. 'CGI/1.1'. 
     * 
     * @return string 
     */
    function gateway_interface()
    {
        return isset($_SERVER['GATEWAY_INTERFACE']) ? $_SERVER['GATEWAY_INTERFACE'] : null;
    }

    /**
     * Name and revision of the information protocol via which the page was requested; i.e. 'HTTP/1.0'; 
     * 
     * @return string 
     */
    function server_protocol()
    {
        return isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : null;
    }

    /**
     * Which request method was used to access the page; i.e. 'GET', 'HEAD', 'POST', 'PUT'.
     * 
     * Note:
     *  PHP script is terminated after sending headers (it means after producing any output without output buffering) if the request method was HEAD. 
     * 
     * @return string 
     */
    function request_method()
    {
        return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
    }

    /**
     * The query string, if any, via which the page was accessed. 
     * 
     * @return string 
     */
    function query_string()
    {
        return isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
    }

    /**
     * The URI which was given in order to access this page; for instance, '/index.html'. 
     * @return string 
     */
    function request_uri()
    {
        return isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
    }

    /**
     * Contains the current script's path. This is useful for pages which need to point to themselves. The __FILE__ constant contains the full path and filename of the current (i.e. included) file. 
     * 
     * @return string 
     */
    function script_name()
    {
        return isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : null;
    }

    /**
     * The filename of the currently executing script, relative to the document root. For instance, $_SERVER['PHP_SELF'] in a script at the address http://example.com/test.php/foo.bar would be /test.php/foo.bar. The __FILE__ constant contains the full path and filename of the current (i.e. included) file. If PHP is running as a command-line processor this variable contains the script name since PHP 4.3.0. Previously it was not available.
     * 
     * @return string
     */
    function php_self()
    {
        return isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : null;
    }

}