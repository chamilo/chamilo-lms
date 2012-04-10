<?php

/**
 * Description of SearchCoursePlugin
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class SearchCoursePlugin extends Plugin
{

    /**
     *
     * @return SearchCoursePlugin 
     */
    static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }
    
    function get_name()
    {
        return 'search_course';
    }

    protected function __construct()
    {
        parent::__construct('1.1', 'Laurent Opprecht');
    }

}