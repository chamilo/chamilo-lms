<?php

/* For licensing terms, see /license.txt */

/**
 * Prevents search engines from indexing dynamic Chamilo pages while the plugin
 * is active for the current access URL.
 */
class NoSearchIndex extends Plugin
{
    public $addCourseTool = false;

    public function __construct()
    {
        parent::__construct(
            '0.2',
            'Chamilo',
            []
        );
    }

    public static function create(): self
    {
        static $result = null;

        return $result ?: $result = new self();
    }
}
