<?php
/* For license terms, see /license.txt
/* To show the plugin course icons you need to add these icons:
 * main/img/icons/22/ranking.png
 * main/img/icons/64/ranking.png
 * main/img/icons/64/ranking_na.png
 */
/**
 * Description of test2pdf_plugin
 * @package chamilo.plugin.test2pdf
 * @author Jose Angel Ruiz    <jaruiz@nosolored.com>
 */
/**
 * Plugin class for the Test2pdf plugin
 */
class Test2pdfPlugin extends Plugin
{
    public $isCoursePlugin = true;
    /**
     *
     * @return StaticPlugin
     */
    public static function create()
    {
        static $result = null;
        return $result ? $result : $result = new self();
    }

    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Jose Angel Ruiz - NoSoloRed (original author)',
            [
                'enable_plugin' => 'boolean'
            ]
        );
    }

    /**
     * This method creates the tables required to this plugin
     */
    public function install()
    {
        //Installing course settings
        $this->install_course_fields_in_all_courses();
        require_once api_get_path(SYS_PLUGIN_PATH) . 'test2pdf/database.php';
    }

    /**
     * This method drops the plugin tables
     */
    public function uninstall()
    {
        //Deleting course settings
        $this->uninstall_course_fields_in_all_courses($this->course_settings);
        
        $tablesToBeDeleted = [
            TABLE_TEST2PDF
        ];
        foreach ($tablesToBeDeleted as $tableToBeDeleted) {
            $table = Database::get_main_table($tableToBeDeleted);
            $sql = "DROP TABLE IF EXISTS $tableToBeDeleted";
            Database::query($sql);
        }
        $this->manageTab(false);
    }
}
