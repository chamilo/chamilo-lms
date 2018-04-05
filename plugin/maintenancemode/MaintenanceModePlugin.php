<?php
/* For licensing terms, see /license.txt */

/**
 * Class MaintenanceModePlugin.
 */
class MaintenanceModePlugin extends Plugin
{
    protected function __construct()
    {
        parent::__construct(
            '0.1',
            'Julio Montoya',
            [
                'tool_enable' => 'boolean',
            ]
        );
    }

    /**
     * @return EditHtaccessPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }
}
