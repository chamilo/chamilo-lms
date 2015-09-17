<?php
/* For licensing terms, see /license.txt */

/**
 * Header utility functions.
 *
 * @license see /license.txt
 * @author Laurent Opprecht <laurent@opprecht.info> for the Univesity of Geneva
 * @deprecated
 */
class Header
{
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
}
