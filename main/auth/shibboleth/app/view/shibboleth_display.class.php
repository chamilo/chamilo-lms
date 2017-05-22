<?php

namespace Shibboleth;

use \Display;

/**
 * Utility display functions tailored for the Shibboleth pluging.
 * 
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info>, Nicolas Rod for the University of Geneva
 */
class ShibbolethDisplay
{

    /**
     *
     * @return ShibbolethDisplay 
     */
    public static function instance()
    {
        static $result = false;
        if (empty($result))
        {
            $result = new self();
        }
        return $result;
    }

    /**
     * @param string $message
     */
    public function error_page($message)
    {
        $page_title = get_lang('ShibbolethLogin');

        Display :: display_header($page_title);
        echo Display::return_message($message, 'error');
        Display :: display_footer();
        die;
    }
    
    /**
     * @param string $message
     */
    public function message_page($message, $title = '')
    {
        $title = $title ? $title : get_lang('ShibbolethLogin');

        Display::display_header($title);
        echo Display::return_message($message, 'confirm');
        Display::display_footer();
        die;
    }
    
    public function page($content, $title = '')
    {
        $title = $title ? $title : get_lang('ShibbolethLogin');

        Display :: display_header($title);
        echo $content;
        Display :: display_footer();
        die;
    }

}
