<?php

/**
 * Description of buy_courses_plugin
 *
 * @copyright (c) 2013 Nosolored
 * @author Jose Angel Ruiz    <jaruiz@nosolored.com>
 */
class Buy_CoursesPlugin extends Plugin
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
        parent::__construct('1.0', 'Jose Angel Ruiz, Francis Gonzales', array('paypal_enable' => 'boolean', 'transference_enable' => 'boolean', 'unregistered_users_enable' => 'boolean'));
    }

    /**
     * This method creates the tables required to this plugin
     */
    function install()
    {
        require_once api_get_path(SYS_PLUGIN_PATH) . 'buy_courses/database.php';
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

        $table = Database::get_main_table(TABLE_BUY_COURSE_TRANSFERENCE);
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