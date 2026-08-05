<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

class MaintenanceModePlugin extends Plugin
{
    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Chamilo',
            []
        );
    }

    public static function create(): self
    {
        static $instance = null;

        return $instance ??= new self();
    }
}
