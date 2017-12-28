<?php
/* For licensing terms, see /license.txt */

/**
 * Class URLUtils
 * This class contains some utilities to work with urls
 *
 * @package chamilo.library
 */
class URLUtils
{
    
    /**
     * Construct
     */
    private function __construct()
    {
    }

    /**
     * Gets the wellformed URLs regular expression in order to use it on forms' verifications
     *
     * @author Aquilino Blanco Cores <aqblanco@gmail.com>
     * @return the wellformed URLs regular expressions string
     */
    public static function getWellformedUrlRegex()
    {
        return '/\(?((http|https|ftp):\/\/)(?:((?:[^\W\s]|\.|-|[:]{1})+)@{1})?((?:www.)?(?:[^\W\s]|\.|-)+[\.][^\W\s]{2,4}|localhost(?=\/)|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(?::(\d*))?([\/]?[^\s\?]*[\/]{1})*(?:\/?([^\s\n\?\[\]\{\}\#]*(?:(?=\.)){1}|[^\s\n\?\[\]\{\}\.\#]*)?([\.]{1}[^\s\?\#]*)?)?(?:\?{1}([^\s\n\#\[\]]*))?([\#][^\s\n]*)?\)?/i';
    }
    
    /**
     * Gets the files hosting sites' whitelist
     *
     * @author Aquilino Blanco Cores <aqblanco@gmail.com>
     * @return array the sites list.
     */
    public static function getFileHostingsWL()
    {
        return array(
            "asuswebstorage.com",
            "dropbox.com",
            "dropboxusercontent.com",
            "fileserve.com",
            "drive.google.com",
            "icloud.com",
            "mediafire.com",
            "mega.nz",
            "onedrive.live.com",
            "slideshare.net",
            "scribd.com",
            "wetransfer.com",
            "box.com",
            "livefilestore.com" // OneDrive
        );
    }
}
