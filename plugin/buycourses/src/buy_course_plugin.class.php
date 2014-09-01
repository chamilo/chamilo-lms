<?php
/* For license terms, see /license.txt */
/**
 * Description of buy_courses_plugin
 * @package chamilo.plugin.buycourses
 * @author Jose Angel Ruiz    <jaruiz@nosolored.com>
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
        parent::__construct('1.0', 'Jose Angel Ruiz - NoSoloRed (original author), Francis Gonzales and Yannick Warnier - BeezNest (integration)', array('paypal_enable' => 'boolean', 'transfer_enable' => 'boolean', 'unregistered_users_enable' => 'boolean'));
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
        $table = Database::get_main_table(TABLE_BUY_COURSE);
        $sql = "DROP TABLE IF EXISTS $table";
        Database::query($sql);
   
        $table = Database::get_main_table(TABLE_BUY_COURSE_COUNTRY);
        $sql = "DROP TABLE IF EXISTS $table";
        Database::query($sql);

        $table = Database::get_main_table(TABLE_BUY_COURSE_PAYPAL);
        $sql = "DROP TABLE IF EXISTS $table";
        Database::query($sql);

        $table = Database::get_main_table(TABLE_BUY_COURSE_TRANSFER);
        $sql = "DROP TABLE IF EXISTS $table";
        Database::query($sql);

        $table = Database::get_main_table(TABLE_BUY_COURSE_TEMPORAL);
        $sql = "DROP TABLE IF EXISTS $table";
        Database::query($sql);

        $table = Database::get_main_table(TABLE_BUY_COURSE_SALE);
        $sql = "DROP TABLE IF EXISTS $table";
        Database::query($sql);
    }
}
