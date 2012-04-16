<?php

/**
 * Send a redirect to the user agent and exist
 * 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Redirect
{

    static function www()
    {
        static $result = false;
        if (empty($result))
        {
            $result = api_get_path('WEB_PATH');
        }
        return $result;
    }

    static function go($url = '')
    {
        if (empty($url))
        {
            Redirect::session_request_uri();
            $www = self::www();
            self::navigate($www);
        }

        $is_full_uri = (strpos($url, 'http') === 0);
        if ($is_full_uri)
        {
            self::navigate($url);
        }

        $url = self::www() . $url;
        self::navigate($url);
    }

    /**
     * Redirect to the session "request uri" if it exists. 
     */
    static function session_request_uri()
    {
//        if (api_is_anonymous())
//        {
//            return;
//        }
        $url = isset($_SESSION['request_uri']) ? $_SESSION['request_uri'] : '';
        unset($_SESSION['request_uri']);
        if ($url)
        {
            self::navigate($url);
        }
    }

    static function home()
    {
        $www = self::www();
        self::navigate($www);
    }

    static function user_home()
    {
        $www = self::www();
        self::navigate("$www/user_portal.php");
    }

    protected static function navigate($url)
    {
        session_write_close(); //should not be neeeded 
        header("Location: $url");
        exit;
    }

}