<?php
/* For licensing terms, see /license.txt */

/**
 * Class MindmapPlugin
 * This class defines the course plugin "MindMap", storing its data in the plugin_mindmap table.
 */
class MindmapPlugin extends Plugin
{
    public $isCoursePlugin = true;
    public $course_settings = [];
    public $table = 'plugin_mindmap';

    /**
     * MindmapPlugin constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '1.1', 'Damien Renou - Batisseurs Numériques',
            [
                'tool_enable' => 'boolean',
            ]
        );
    }

    /**
     * Create instance of a Mindmap plugin object.
     *
     * @return MindmapPlugin|null
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Install the table structure.
     */
    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS ".$this->table."(
            id INT NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description VARCHAR(512),
            mindmap_type VARCHAR(155),
            mindmap_data TEXT,
            user_id INT,
            is_public INT,
            is_shared INT,
            c_id INT,
            session_id INT,
            url_id INT,
            PRIMARY KEY (id));";
        Database::query($sql);

        // Copy icons to the main Chamilo directory
        $p1 = api_get_path(SYS_PATH).'plugin/mindmap/img/mindmap64.png';
        $p2 = api_get_path(SYS_PATH).'main/img/icons/64/mindmap.png';
        copy($p1, $p2);

        $p3 = api_get_path(SYS_PATH).'plugin/mindmap/img/mindmap64_na.png';
        $p4 = api_get_path(SYS_PATH).'main/img/icons/64/mindmap_na.png';
        copy($p3, $p4);

        // Installing course settings
        $this->install_course_fields_in_all_courses();
    }

    public function uninstall()
    {
        // Remove table
        $em = Database::getManager();
        $sm = $em->getConnection()->getSchemaManager();
        if ($sm->tablesExist('plugin_mindmap')) {
            Database::query('DROP TABLE IF EXISTS plugin_mindmap');
        }
        // Deleting course settings and course home icons
        $this->uninstall_course_fields_in_all_courses();

        $p2 = api_get_path(SYS_PATH).'main/img/icons/64/mindmap.png';
        if (file_exists($p2) && is_writable($p2)) {
            unlink($p2);
        }
        $p4 = api_get_path(SYS_PATH).'main/img/icons/64/mindmap_na.png';
        if (file_exists($p4) && is_writable($p4)) {
            unlink($p4);
        }
    }
}
