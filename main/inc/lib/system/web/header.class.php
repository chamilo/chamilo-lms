<?php

/**
 * Header utility functions.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Header
{

    public static function response_code($response_code)
    {
        if (function_exists('http_response_code')) {
            http_response_code($response_code);
            return;
        }

        switch ($response_code) {
            case 400:
                header("HTTP/1.0 $response_code Bad Request");
            case 401:
                header("HTTP/1.0 $response_code Unauthorized");
            case 402:
                header("HTTP/1.0 $response_code Payment Required");
            case 403:
                header("HTTP/1.0 $response_code Forbidden");
            case 404:
                header("HTTP/1.0 $response_code Not Found");
            default:
                header("HTTP/1.0 $response_code");
        }
    }

    public static function content_type($mime_type, $charset = '')
    {
        if (empty($mime_type)) {
            return;
        }
        $type = $charset ? "$mime_type;charset=$charset" : $mime_type;
        header('Content-type: ' . $type);
    }

    public static function content_type_xml()
    {
        header('Content-type: text/xml');
    }

    public static function content_type_javascript()
    {
        header('Content-type: application/javascript');
    }

    /**
     * Redirect the navigator to the specified url.
     * 
     * @param string $url 
     */
    public static function location($url)
    {
        header("Location: $url");
        exit;
    }

    public static function expires($timestamp)
    {
        $value = gmdate('D, d M Y H:i:s \G\M\T', $timestamp);
        header('Expires: ' . $value);
    }

    public static function cache_control($value)
    {
        header('Cache-Control: ' . $value);
    }

    public static function pragma($value)
    {
        header('Pragma: ' . $value);
    }

}