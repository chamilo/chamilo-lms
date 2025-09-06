<?php

/* For licensing terms, see /license.txt */

/**
 * Class NoSearchIndex.
 */
class NoSearchIndex extends Plugin
{
    public $addCourseTool = false;

    /**
     * NoSearchIndex constructor.
     */
    public function __construct()
    {
        parent::__construct(
            '0.1',
            'Julio Montoya',
            [
            ]
        );
    }

    /**
     * @return $this
     */
    public static function create()
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function get_name()
    {
        return 'NoSearchIndex';
    }
}
