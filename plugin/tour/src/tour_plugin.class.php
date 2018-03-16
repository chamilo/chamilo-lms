<?php
/* For licensing terms, see /license.txt */
/**
 * The Tour class allows a guided tour in HTML5 of the Chamilo interface.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 *
 * @package chamilo.plugin.tour
 */
class Tour extends Plugin
{
    /**
     * Class constructor.
     */
    protected function __construct()
    {
        $parameters = [
            'show_tour' => 'boolean',
            'theme' => 'text',
        ];

        parent::__construct('1.0', 'Angel Fernando Quiroz Campos', $parameters);
    }

    /**
     * Instance the plugin.
     *
     * @staticvar null $result
     *
     * @return Tour
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
        $this->installDatabase();
    }

    /**
     * Uninstall the plugin.
     */
    public function uninstall()
    {
        $this->unistallDatabase();
    }

    /**
     * Check whether the tour should be displayed to the user.
     *
     * @param string $currentPageClass The class of the current page
     * @param int    $userId           The user id
     *
     * @return bool If the user has seen the tour return false, otherwise return true
     */
    public function checkTourForUser($currentPageClass, $userId)
    {
        $pluginTourLogTable = Database::get_main_table(TABLE_TOUR_LOG);

        $checkResult = Database::select('count(1) as qty', $pluginTourLogTable, [
                    'where' => [
                        "page_class = '?' AND " => $currentPageClass,
                        "user_id = ?" => intval($userId),
                    ], ], 'first');

        if ($checkResult !== false) {
            if ($checkResult['qty'] > 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set the tour as seen.
     *
     * @param string $currentPageClass The class of the current page
     * @param int    $userId           The user id
     */
    public function saveCompletedTour($currentPageClass, $userId)
    {
        $pluginTourLogTable = Database::get_main_table(TABLE_TOUR_LOG);

        Database::insert($pluginTourLogTable, [
            'page_class' => $currentPageClass,
            'user_id' => intval($userId),
            'visualization_datetime' => api_get_utc_datetime(),
        ]);
    }

    /**
     * Get the configuration to show the tour in pages.
     *
     * @return array The config data
     */
    public function getTourConfig()
    {
        $pluginPath = api_get_path(SYS_PLUGIN_PATH).'tour/';
        $jsonContent = file_get_contents($pluginPath.'config/tour.json');
        $jsonData = json_decode($jsonContent, true);

        return $jsonData;
    }

    /**
     * Create the database tables for the plugin.
     */
    private function installDatabase()
    {
        $pluginTourLogTable = Database::get_main_table(TABLE_TOUR_LOG);

        $sql = "CREATE TABLE IF NOT EXISTS $pluginTourLogTable ("
                ."id int UNSIGNED NOT NULL AUTO_INCREMENT, "
                ."page_class varchar(255) NOT NULL, "
                ."user_id int UNSIGNED NOT NULL, "
                ."visualization_datetime datetime NOT NULL, "
                ."PRIMARY KEY PK_tour_log (id))";

        Database::query($sql);
    }

    /**
     * Drop the database tables for the plugin.
     */
    private function unistallDatabase()
    {
        $pluginTourLogTable = Database::get_main_table(TABLE_TOUR_LOG);

        $sql = "DROP TABLE IF EXISTS $pluginTourLogTable";

        Database::query($sql);
    }
}
