<?php
/* For license terms, see /license.txt */
/**
 * Description of buy_courses_plugin
 * @package chamilo.plugin.buycourses
 * @author Jose Angel Ruiz    <jaruiz@nosolored.com>
 * @author Imanol Losada      <imanol.losada@beeznest.com>
 * @author Alex Aragón      <alex.aragon@beeznest.com>
 */
/**
 * Plugin class for the BuyCourses plugin
 */
class BuyCoursesPlugin extends Plugin
{
    /**
     *
     * @return StaticPlugin
     */
    static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Jose Angel Ruiz - NoSoloRed (original author),
            Francis Gonzales and Yannick Warnier - BeezNest (integration),
            Alex Aragón - BeezNest (Design icons and css styles),
            Imanol Losada - BeezNest (introduction of sessions purchase)',
            array(
                'show_main_menu_tab' => 'boolean',
                'include_sessions' => 'boolean',
                'paypal_enable' => 'boolean',
                'transfer_enable' => 'boolean',
                'unregistered_users_enable' => 'boolean'
            )
        );
    }

    /**
     * This method creates the tables required to this plugin
     */
    function install()
    {
        require_once api_get_path(SYS_PLUGIN_PATH) . 'buycourses/database.php';
    }

    /**
     * This method drops the plugin tables
     */
    function uninstall()
    {
        $tablesToBeDeleted = array(
            TABLE_BUY_SESSION,
            TABLE_BUY_SESSION_COURSE,
            TABLE_BUY_SESSION_TEMPORARY,
            TABLE_BUY_SESSION_SALE,
            TABLE_BUY_COURSE,
            TABLE_BUY_COURSE_COUNTRY,
            TABLE_BUY_COURSE_PAYPAL,
            TABLE_BUY_COURSE_TRANSFER,
            TABLE_BUY_COURSE_TEMPORAL,
            TABLE_BUY_COURSE_SALE
        );
        foreach ($tablesToBeDeleted as $tableToBeDeleted) {
            $table = Database::get_main_table($tableToBeDeleted);
            $sql = "DROP TABLE IF EXISTS $tableToBeDeleted";
            Database::query($sql);
        }
        $this->manageTab(false);
    }
}
