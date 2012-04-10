<?php

/**
 * Description of 
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class RssPlugin extends Plugin
{

    /**
     *
     * @return RssPlugin 
     */
    static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    function get_block_title()
    {
        return $this->get('block_title');
    }

    function get_rss()
    {
        return $this->get('rss');
    }

    protected function __construct()
    {
        parent::__construct('1.1', 'Laurent Opprecht', array('block_title' => 'text', 'rss' => 'text'));
    }

}