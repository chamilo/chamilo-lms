<?php

/**
 * Description of.
 *
 * @copyright (c) 2012 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author Laurent Opprecht <laurent@opprecht.info>
 */
class RssPlugin extends Plugin
{
    protected function __construct()
    {
        parent::__construct('1.1', 'Laurent Opprecht', ['block_title' => 'text', 'rss' => 'text']);
    }

    /**
     * @return RssPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function get_block_title()
    {
        return $this->get('block_title');
    }

    public function get_rss()
    {
        return $this->get('rss');
    }
}
