<?php

/**
 *  Used to implement the loading of custom pages
 * 
 * @license see /license.txt
 * @author 2011, Jean-Karim Bockstael <jeankarim@cblue.be>
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class CustomPages
{
    const INDEX_LOGGED = 'index-logged';
    const INDEX_UNLOGGED = 'index-unlogged';
    const LOGGED_OUT = 'loggedout';
    const REGISTRATION_FEEDBACK = 'registration-feedback';
    const REGISTRATION = 'registration';
    const LOST_PASSWORD = 'lostpassword';

    /**
     * Returns true if custom pages are enabled. False otherwise.
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
     * @return string
     */
    public static function path($name = '')
    {
        return api_get_path(SYS_PATH) . 'custompages/' . $name;
    }

    /**
     * If enabled display a custom page and exist. Otherwise log error and returns.
     * 
     * @param string $page_name
     * @param array $content used to path data to the custom page
     */
    public static function display($page_name, $content = array())
    {
        if (!self::enabled()) {
            return false;
        }

        $file = self::path($page_name . '.php');
        if (file_exists($file)) {
            include($file);
            exit;
        } else {
            error_log('CustomPages::displayPage : could not read file ' . $file_name);
        }
    }

    /**
     * Does not look like this function is being used is being used 
     * 
     * @param type $url_id
     * @return string 
     */
    public static function getURLImages($url_id = null)
    {
        if (is_null($url_id)) {
            $url = 'http://' . $_SERVER['HTTP_HOST'] . '/';
            $url_id = UrlManager::get_url_id($url);
        }
        $url_images_dir = api_get_path(SYS_PATH) . 'custompages/url-images/';
        $images = array();
        for ($img_id = 1; $img_id <= 3; $img_id++) {
            if (file_exists($url_images_dir . $url_id . '_url_image_' . $img_id . '.png')) {
                $images[] = api_get_path(WEB_PATH) . 'custompages/url-images/' . $url_id . '_url_image_' . $img_id . '.png';
            }
        }
        return $images;
    }

}