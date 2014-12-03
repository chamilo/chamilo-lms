<?php
/* For licensing terms, see /license.txt */

/**
 * Class AdvancedSubscriptionPlugin
 * This script contains basic plugin functions
 * 
 * @package chamilo.plugin.advancedsubscription.lib
 * @author  Imanol Losada <imanol.losada@beeznest.com>
 */
class AdvancedSubscriptionPlugin extends Plugin
{
    //public $isCoursePlugin = true;

    /**
     * create (a singleton function that ensures AdvancedSubscriptionPlugin instance is
     * created only once. If it is already created, it returns the instance)
     * @return  object  AdvancedSubscriptionPlugin instance
     */
    public static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    /**
     * Constructor
     * @return  void
     */
    protected function __construct()
    {
        parent::__construct(
            '0.1',
            'Imanol Losada',
            array(
                'tool_enable' => 'boolean',
                'yearly_cost_limit' => 'text',
                'yearly_hours_limit' => 'text',
                'yearly_cost_unit_converter' => 'text',
                'courses_count_limit' => 'text',
                'course_session_credit_year_start_date' => 'text'
            )
        );
    }

    /**
     * install (installs the plugin)
     * @return  void
     */
    public function install()
    {

    }
    /**
     * install (uninstalls the plugin and removes all plugin's tables and/or rows)
     * @return  void
     */
    public function uninstall()
    {
        $tSettings = Database::get_main_table(TABLE_MAIN_SETTINGS_CURRENT);
        Database::query("DELETE FROM $tSettings WHERE subkey = 'advancedsubscription'");
    }
}
