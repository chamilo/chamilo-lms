<?php
/* For license terms, see /license.txt */

/**
 * Plugin class for the Extended Question Pool plugin.
 *
 * @package chamilo.plugin.extendedquestionpool
 *
 * @author Nosolored <desarrollo@nosolored.com>
 */
class ExtendedQuestionPoolPlugin extends Plugin
{
    //public $isCoursePlugin = true;

    /**
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'NoSoloRed',
            [
                'enable_plugin' => 'boolean',
                'correct_score' => 'text',
                'error_score' => 'text',
            ]
        );

        $this->isAdminPlugin = true;
    }

    /**
     * Instance the plugin.
     *
     * @staticvar null $result
     *
     * @return ExtendedQuestionPool
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * This method creates the tables required to this plugin.
     */
    public function install()
    {
        require_once api_get_path(SYS_PLUGIN_PATH).'extendedquestionpool/database.php';
    }

    /**
     * This method drops the plugin tables.
     */
    public function uninstall()
    {
        // Deleting course settings.
        $this->uninstall_course_fields_in_all_courses();
        $extraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
        $query = "UPDATE $extraFieldTable 
                SET visible_to_self = 0, 
                visible_to_others = 0, 
                changeable = 0, 
                filter = 0 
                WHERE variable = 'additional_question_category'";
        Database::query($query);
        $query = "UPDATE $extraFieldTable 
                SET visible_to_self = 0, 
                visible_to_others = 0, 
                changeable = 0, 
                filter = 0 
                WHERE variable = 'question_data1'";
        Database::query($query);
        $query = "UPDATE $extraFieldTable 
                SET visible_to_self = 0, 
                visible_to_others = 0, 
                changeable = 0, 
                filter = 0 
                WHERE variable = 'question_data2'";
        Database::query($query);
        $query = "UPDATE $extraFieldTable 
                SET visible_to_self = 0, 
                visible_to_others = 0, 
                changeable = 0, 
                filter = 0 
                WHERE variable = 'question_data3'";
        Database::query($query);
        $query = "UPDATE $extraFieldTable 
                SET visible_to_self = 0, 
                visible_to_others = 0, 
                changeable = 0, 
                filter = 0 
                WHERE variable = 'question_extra_info'";
        Database::query($query);
        $this->manageTab(false);
    }

    /**
     * This method updates plugin.
     */
    public function update()
    {
        //update actions
    }
}