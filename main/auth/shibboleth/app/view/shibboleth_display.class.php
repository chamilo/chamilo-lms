<?php

/**
 * Utility display functions tailored for the Shibboleth pluging.
 * 
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
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

    public function error_page($message)
    {
        $include_path = api_get_path(INCLUDE_PATH);
        require("$include_path/local.inc.php");
        $page_title = get_lang('page_title');

        Display :: display_header($page_title);
        Display :: display_error_message($message);
        Display :: display_footer();
        die;
    }
    
    public function message_page($message, $title = '')
    {
        $include_path = api_get_path(INCLUDE_PATH);
        require("$include_path/local.inc.php");
        $title = $title ? $title : get_lang('page_title');

        Display :: display_header($page_title);
        Display :: display_confirmation_message($message);
        Display :: display_footer();
        die;
    }
    
    public function page($content, $title = '')
    {
        $include_path = api_get_path(INCLUDE_PATH);
        require("$include_path/local.inc.php");
        $title = $title ? $title : get_lang('page_title');

        Display :: display_header($title);
        echo $content;
        Display :: display_footer();
        die;
    }

}