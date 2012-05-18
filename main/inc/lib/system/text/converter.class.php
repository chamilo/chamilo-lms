<?php

/**
 * Convert text. Used mostly to convert from one encoding to another.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class Converter
{
    
    /**
     * Identity converter. Returns the string with no transformations.
     *
     * @return Converter 
     */
    public static function identity()
    {
        static $result = null;
        if(empty($result))
        {
            $result = new self();
        }
        return $result;
    }
    
    
    function convert($string)
    {
        return $string;
    }
}