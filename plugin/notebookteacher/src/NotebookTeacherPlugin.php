<?php
/* For license terms, see /license.txt */

/**
 * Plugin class for the NotebookTeacher plugin.
 *
 * @package chamilo.plugin.notebookteacher
 *
 * @author Jose Angel Ruiz <desarrollo@nosolored.com>
 * @author Julio Montoya
 */
class NotebookTeacherPlugin extends Plugin
{
    public const TABLE_NOTEBOOKTEACHER = 'plugin_notebook_teacher';
    public $isCoursePlugin = true;

    /**
     * NotebookTeacherPlugin constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '1.1',
            'Jose Angel Ruiz - NoSoloRed (original author), Julio Montoya',
            [
                'enable_plugin_notebookteacher' => 'boolean',
            ]
        );

        $this->isAdminPlugin = true;
    }

    /**
     * @return NotebookTeacherPlugin
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
        // Installing course settings
        $this->install_course_fields_in_all_courses();

        $tablesToBeCompared = [self::TABLE_NOTEBOOKTEACHER];
        $em = Database::getManager();
        $cn = $em->getConnection();
        $sm = $cn->getSchemaManager();
        $tables = $sm->tablesExist($tablesToBeCompared);

        if ($tables) {
            return false;
        }

        $list = [
            '/64/notebookteacher.png',
            '/64/notebookteacher_na.png',
            '/32/notebookteacher.png',
            '/32/notebookteacher_na.png',
            '/32/test2pdf_na.png',
            '/22/notebookteacher.png',
        ];

        foreach ($list as $file) {
            $source = __DIR__.'/../resources/img/'.$file;
            $destination = __DIR__.'/../../../main/img/icons/'.$file;
            $res = @copy($source, $destination);
            if (!$res) {
                break;
            }
        }

        require_once api_get_path(SYS_PLUGIN_PATH).'notebookteacher/database.php';
    }

    /**
     * This method drops the plugin tables.
     */
    public function uninstall()
    {
        // Deleting course settings.
        $this->uninstall_course_fields_in_all_courses();

        $tablesToBeDeleted = [self::TABLE_NOTEBOOKTEACHER];
        foreach ($tablesToBeDeleted as $tableToBeDeleted) {
            $table = Database::get_main_table($tableToBeDeleted);
            $sql = "DROP TABLE IF EXISTS $table";
            Database::query($sql);
        }
        $this->manageTab(false);
    }

    /**
     * Update.
     */
    public function update()
    {
        $tableNotebookTeacher = self::TABLE_NOTEBOOKTEACHER;

        $sql = 'SHOW COLUMNS FROM '.$tableNotebookTeacher.' WHERE Field = "student_id"';
        $rs = Database::query($sql);
        if (Database::num_rows($rs) === 0) {
            $sql = "ALTER TABLE ".$tableNotebookTeacher." ADD student_id INT( 10 ) UNSIGNED NOT NULL AFTER user_id";
            Database::query($sql);
        }

        $srcfile1 = __DIR__.'/../resources/img/64/notebookteacher.png';
        $srcfile2 = __DIR__.'/../resources/img/64/notebookteacher_na.png';
        $srcfile3 = __DIR__.'/../resources/img/32/notebookteacher.png';
        $srcfile4 = __DIR__.'/../resources/img/22/notebookteacher.png';
        $dstfile1 = __DIR__.'/../../../main/img/icons/64/notebookteacher.png';
        $dstfile2 = __DIR__.'/../../../main/img/icons/64/notebookteacher_na.png';
        $dstfile3 = __DIR__.'/../../../main/img/icons/32/notebookteacher.png';
        $dstfile4 = __DIR__.'/../../../main/img/notebookteacher.png';
        copy($srcfile1, $dstfile1);
        copy($srcfile2, $dstfile2);
        copy($srcfile3, $dstfile3);
        copy($srcfile4, $dstfile4);

        Display::display_header(get_lang(ucfirst(self::TABLE_NOTEBOOKTEACHER)));
        echo 'Plugin actualizado';
        Display::display_footer();
    }
}
