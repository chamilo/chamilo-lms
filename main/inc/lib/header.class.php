<?php

/**
 * Header utility functions.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 */
class Header
{

    public static function content_type($mime_type)
    {
        if (empty($mime_type))
        {
            return;
        }
        header('Content-type: ' . $mime_type);
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