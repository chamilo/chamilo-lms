<?php
/* For licensing terms, see /license.txt */

/**
 *  Used to implement the loading of custom pages.
 *
 * @license see /license.txt
 * @author 2011, Jean-Karim Bockstael <jeankarim@cblue.be>
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class CustomPages
{
    public const INDEX_LOGGED = 'index-logged';
    public const INDEX_UNLOGGED = 'index-unlogged';
    public const LOGGED_OUT = 'loggedout';
    public const REGISTRATION_FEEDBACK = 'registration-feedback';
    public const REGISTRATION = 'registration';
    public const LOST_PASSWORD = 'lostpassword';

    /**
     * Returns true if custom pages are enabled. False otherwise.
     *
     * @return bool
     */
    public static function enabled()
    {
        return api_get_setting('use_custom_pages') == 'true';
    }

    /**
     * Returns the path to a custom page.
     *
     * @param string $name
     *
     * @return string
     */
    public static function path($name = '')
    {
        return api_get_path(SYS_PATH).'custompages/'.$name;
    }

    /**
     * If enabled display a custom page and exist. Otherwise log error and returns.
     *
     * @param string $pageName
     * @param array  $content  used to pass data to the custom page
     *
     * @return bool False if custom pages is not enabled or file could not be found. Void otherwise.
     */
    public static function display($pageName, $content = [])
    {
        if (!self::enabled()) {
            return false;
        }

        $file = self::path($pageName.'.php');
        // Only include file if it exists, otherwise do nothing
        if (file_exists($file)) {
            include $file;
            exit; //finish the execution here - do not return
        }

        return false;
    }

    /**
     * Does not look like this function is being used is being used.
     *
     * @param int $url_id
     *
     * @return array
     */
    public static function getURLImages($url_id = null)
    {
        if (is_null($url_id)) {
            $url = 'http://'.$_SERVER['HTTP_HOST'].'/';
            $url_id = UrlManager::get_url_id($url);
        }
        $url_images_dir = api_get_path(SYS_PATH).'custompages/url-images/';
        $images = [];
        for ($img_id = 1; $img_id <= 3; $img_id++) {
            if (file_exists($url_images_dir.$url_id.'_url_image_'.$img_id.'.png')) {
                $images[] = api_get_path(WEB_PATH).'custompages/url-images/'.$url_id.'_url_image_'.$img_id.'.png';
            }
        }

        return $images;
    }

    /**
     * Check if exists the file for custom page.
     *
     * @param string $pageName The name of custom page
     *
     * @return bool
     */
    public static function exists($pageName)
    {
        $fileName = self::path("$pageName.php");

        return file_exists($fileName);
    }
}
