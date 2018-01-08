<?php
/* For licensing terms, see /license.txt */

/**
 * Class NoSearchIndex
 */
class NoSearchIndex extends Plugin
{
    public $addCourseTool = false;

    /**
     * @return $this
     */
    public static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    /**
     * NoSearchIndex constructor.
     */
    public function __construct()
    {
        parent::__construct(
            '0.1',
            'Julio Montoya',
            [
                'tool_enable' => 'boolean'
            ]
        );
    }
}
