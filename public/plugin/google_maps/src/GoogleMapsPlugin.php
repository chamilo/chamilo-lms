<?php
/* For licensing terms, see /license.txt */

/**
 * The google maps class allows to use.
 *
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 *
 * @package chamilo.plugin.google_maps
 */
class GoogleMapsPlugin extends Plugin
{
    public $javascriptIncluded;

    /**
     * Class constructor.
     */
    protected function __construct()
    {
        $parameters = [
            'enable_api' => 'boolean',
            'api_key' => 'text',
            'extra_field_name' => 'text',
        ];

        $this->javascriptIncluded = false;

        parent::__construct('1.0', 'José Loguercio Silva', $parameters);
    }

    /**
     * Get the plugin Name.
     *
     * @return string
     */
    public function get_name()
    {
        return 'google_maps';
    }

    /**
     * Instance the plugin.
     *
     * @staticvar null $result
     *
     * @return GoogleMapsPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install the plugin.
     */
    public function install()
    {
        return true;
    }

    /**
     * Uninstall the plugin.
     */
    public function uninstall()
    {
        return true;
    }
}
