<?php

/* For licensing terms, see /license.txt */

/**
 * Description of Tour
 * 
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.tour
 */
class Tour extends Plugin
{

    /**
     * Class constructor
     */
    protected function __construct()
    {
        $parameters = array(
            'show_tour' => 'boolean'
        );

        parent::__construct('1.0', 'Angel Fernando Quiroz Campos', $parameters);
    }

    /**
     * Instance the plugin
     * @staticvar null $result
     * @return type
     */
    static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    public function install()
    {
        $this->installDatabase();
    }

    public function uninstall()
    {
        $this->unistallDatabase();
    }

    private function installDatabase()
    {
        $pluginTourLogTable = Database::get_main_table(TABLE_TOUR_LOG);

        $sql = "CREATE TABLE IF NOT EXISTS $pluginTourLogTable ("
                . "id int UNSIGNED NOT NULL AUTO_INCREMENT, "
                . "page_class varchar(255) NOT NULL, "
                . "user_id int UNSIGNED NOT NULL, "
                . "visualization_datetime datetime NOT NULL, "
                . "PRIMARY KEY PK_tour_log (id))";

        Database::query($sql);
    }

    private function unistallDatabase()
    {
        $pluginTourLogTable = Database::get_main_table(TABLE_TOUR_LOG);

        $sql = "DROP TABLE IF EXISTS $pluginTourLogTable";

        Database::query($sql);
    }

    public function checkTourForUser($currentPageClass, $userId)
    {
        $pluginTourLogTable = Database::get_main_table(TABLE_TOUR_LOG);

        $checkResult = Database::select('count(1) as qty', $pluginTourLogTable, array(
                    'where' => array(
                        "page_class = '?' AND " => $currentPageClass,
                        "user_id = ?" => $userId
                    )), 'first');

        if ($checkResult != false) {
            if ($checkResult['qty'] > 0) {
                return false;
            }
        }

        return true;
    }

    public function saveCompletedTour($currentPageClass, $userId)
    {
        $pluginTourLogTable = Database::get_main_table(TABLE_TOUR_LOG);

        Database::insert($pluginTourLogTable, array(
            'page_class' => $currentPageClass,
            'user_id' => $userId,
            'visualization_datetime' => api_get_utc_datetime()
        ));
    }

}
