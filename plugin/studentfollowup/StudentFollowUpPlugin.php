<?php
/* For licensing terms, see /license.txt */

/**
 * Class StudentFollowUpPlugin
 */
class StudentFollowUpPlugin extends Plugin
{
    /**
     * @return StudentFollowUpPlugin
     */
    public static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    /**
     * StudentFollowUpPlugin constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '0.1',
            'Julio Montoya',
            array(
                'tool_enable' => 'boolean'
            )
        );
    }

    /**
     *
     */
    public function install()
    {

    }

    /**
     *
     */
    public function uninstall()
    {

    }
}
