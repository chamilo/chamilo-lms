<?php
/* For licensing terms, see /license.txt */

/**
 * Class Block
 * This file contains class used parent class for blocks plugins
 * Parent class for controller Blocks from dashboard plugin
 * @author Christian Fasanando <christian1827@gmail.com>
 * @package chamilo.dashboard
 */
class Block
{
    /**
     * Constructor
     */
    public function __construct()
    {

    }

    /**
     * Display small blocks, @todo it will be implemented for next version
     */
    public function display_small()
    {

    }

    /**
     * Display larges blocks, @todo it will be implemented for next version
     */
    public function display_large()
    {

    }

    public function get_block_path()
    {
        $result = get_class($this);

        return $result;
    }
}
