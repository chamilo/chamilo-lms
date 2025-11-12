<?php

/* For licensing terms, see /license.txt */

/**
 * Clean deleted files plugin.
 *
 * @author Jose Angel Ruiz
 */
class CleanDeletedFilesPlugin extends Plugin
{
    public $isAdminPlugin = true;

    /**
     * Class constructor.
     */
    protected function __construct()
    {
        $version = '1.0';
        $author = 'José Angel Ruiz (NOSOLORED)';
        parent::__construct($version, $author, ['enabled' => 'boolean']);
        $this->isAdminPlugin = true;
    }

    /**
     * @return RedirectionPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }
}
