<?php

class Mobidico extends Plugin
{
    public $isCoursePlugin = true;

    // When creating a new course this settings are added to the course
    public $course_settings = [];

    protected function __construct()
    {
        parent::__construct(
            'O.1',
            'Julio Montoya',
            [
                'tool_enable' => 'boolean',
                'api_key' => 'text',
                'mobidico_url' => 'text',
            ]
        );
    }

    /**
     * @return Mobidico|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function install()
    {
    }

    public function uninstall()
    {
    }
}
