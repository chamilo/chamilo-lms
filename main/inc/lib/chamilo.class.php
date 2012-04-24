<?php

/**
 * Description of chamilo
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class Chamilo
{

    /**
     * Returns a full url from local/absolute path and parameters.
     * Append the root as required for relative urls.
     * 
     * @param string $path
     * @param array $params
     * @return string 
     */
    public static function url($path = '', $params = array(), $html = true)
    {
        return Uri::url($path, $params, $html);
    }

    /**
     * Application web root
     */
    public static function www()
    {
        return Uri::www();
    }
    
    /**
     * File system root for Chamilo
     * 
     * @return string
     */
    public static function root()
    {
        return api_get_path(SYS_PATH);
    }
    
    public static function path($path = '')
    {
        $root = self::root();
        if(empty($path))
        {
            return $root;
        }
        return $root . $path;
    }

}