<?php

/* For license terms, see /license.txt */

/**
 * Class TopLinksPlugin.
 */
class TopLinksPlugin extends Plugin
{
    /**
     * TopLinksPlugin constructor.
     */
    protected function __construct()
    {
        $settings = [
            'enable' => 'boolean',
        ];

        parent::__construct(
            '0.1',
            'Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>',
            $settings
        );
    }

    /**
     * @return \TopLinksPlugin
     */
    public static function create(): TopLinksPlugin
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminUrl()
    {
        $webPath = api_get_path(WEB_PLUGIN_PATH).$this->get_name();

        return "$webPath/admin.php";
    }
}
