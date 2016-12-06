<?php
/* For licensing terms, see /license.txt */
/**
 * The google maps class allows to use
 * @author José Loguercio Silva <jose.loguercio@beeznest.com>
 * @package chamilo.plugin.google_maps
 */
class GoogleMaps extends Plugin
{
    /**
     * Class constructor
     */
    protected function __construct()
    {
        $parameters = array(
            'enable_api' => 'boolean',
            'api_key' => 'text'
        );

        parent::__construct('1.0', 'José Loguercio Silva', $parameters);
    }

    /**
     * Instance the plugin
     * @staticvar null $result
     * @return GoogleMaps
     */
    static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install the plugin
     * @return void
     */
    public function install()
    {
        $this->installDatabase();
    }

    /**
     * Uninstall the plugin
     * @return void
     */
    public function uninstall()
    {
        $this->unistallDatabase();
    }

    /**
     * Create the database tables for the plugin
     * @return void
     */
    private function installDatabase()
    {
        $pluginGoogleMapsLogTable = Database::get_main_table(TABLE_GOOGLE_MAPS);

        $sql = "CREATE TABLE IF NOT EXISTS $pluginGoogleMapsLogTable ("
                . "api_key varchar(255)";

        Database::query($sql);
    }

    /**
     * Drop the database tables for the plugin
     * @return void
     */
    private function unistallDatabase()
    {
        $pluginGoogleMapsLogTable = Database::get_main_table(TABLE_TOUR_LOG);

        $sql = "DROP TABLE IF EXISTS $pluginGoogleMapsLogTable";

        Database::query($sql);
    }
}
