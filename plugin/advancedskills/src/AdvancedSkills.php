<?php
/* For licensing terms, see /license.txt */

/**
 * Plugin to add extra columns to skill_rel_user tablee
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.plugin.advancedskills
 */
class AdvancedSkills extends Plugin
{

    /**
     * Class constructor
     */
    protected function __construct()
    {
        parent::__construct('1.0', 'Angel Fernando Quiroz Campos');
    }

    /**
     * Instance the plugin
     * @staticvar null $result
     * @return Tour
     */
    static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install the plugin
     */
    public function install()
    {
        $this->addTableColumns();
        $this->addIndex();
    }

    /**
     * Uninstall the plugin
     */
    public function uninstall()
    {
        $this->removeTableColumns();
        $this->removeIndex();
    }

    /**
     * Add the course_id and session_id columns on skill_rel_user table
     */
    private function addTableColumns()
    {
        $skillUserTable = Database::get_main_table(TABLE_MAIN_SKILL_REL_USER);

        $sql = "ALTER TABLE $skillUserTable "
            . "ADD COLUMN course_id INT NOT NULL DEFAULT 0 AFTER id";

        Database::query($sql);

        $sql = "ALTER TABLE $skillUserTable "
            . "ADD COLUMN session_id INT NOT NULL DEFAULT 0 AFTER course_id";

        Database::query($sql);
    }

    /**
     * Remove the course_id and session_id columns on skill_rel_user table
     */
    private function removeTableColumns()
    {
        $skillUserTable = Database::get_main_table(TABLE_MAIN_SKILL_REL_USER);

        $sql = "ALTER TABLE $skillUserTable "
            . "DROP course_id";

        Database::query($sql);

        $sql = "ALTER TABLE $skillUserTable "
            . "DROP session_id";

        Database::query($sql);
    }

    /**
     * Add a index to course_id and session_id on skill_rel_user table
     */
    private function addIndex()
    {
        $skillUserTable = Database::get_main_table(TABLE_MAIN_SKILL_REL_USER);

        $sql = "ALTER TABLE $skillUserTable "
            . "ADD INDEX idx_select_cs (course_id, session_id)";

        Database::query($sql);
    }

    /**
     * Remove a index to course_id and session_id on skill_rel_user table
     */
    private function removeIndex()
    {
        $skillUserTable = Database::get_main_table(TABLE_MAIN_SKILL_REL_USER);

        $sql = "DROP INDEX idx_select_cs ON $skillUserTable";

        Database::query($sql);
    }

    /**
     * 
     * @return boolean
     */
    public static function extraColumnsExists()
    {
        $skillUserTable = Database::get_main_table(TABLE_MAIN_SKILL_REL_USER);

        $sql = "SHOW COLUMNS FROM $skillUserTable";

        $result = Database::query($sql);

        while ($resultData = Database::fetch_assoc($result)) {
            if ($resultData['Field'] == 'course_id' || $resultData['Field'] == 'session_id') {
                return true;
            }
        }
    }

}
