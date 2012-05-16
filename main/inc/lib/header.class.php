<?php

/**
 * Header utility functions.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Header
{

    public static function content_type($mime_type, $charset = '')
    {
        if (empty($mime_type))
        {
            return;
        }
        $type = $charset ?  "$mime_type;charset=$charset" : $mime_type;
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

}