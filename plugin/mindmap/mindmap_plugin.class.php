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

        // Copy icons into the main/img/icons folder
        $iconName = 'mindmap';
        $iconsList = [
            '64/'.$iconName.'.png',
            '64/'.$iconName.'_na.png',
            '32/'.$iconName.'.png',
            '32/'.$iconName.'_na.png',
            '22/'.$iconName.'.png',
            '22/'.$iconName.'_na.png',
        ];
        $sourceDir = api_get_path(SYS_PLUGIN_PATH).'mindmap/img/';
        $destinationDir = api_get_path(SYS_CODE_PATH).'img/icons/';
        foreach ($iconsList as $icon) {
            $src = $sourceDir.$icon;
            $dest = $destinationDir.$icon;
            copy($src, $dest);
        }

        // Installing course settings
        $this->install_course_fields_in_all_courses(true, 'mindmap.png');
    }

    public function uninstall()
    {
        // Remove table
        $em = Database::getManager();
        $sm = $em->getConnection()->getSchemaManager();
        if ($sm->tablesExist('plugin_mindmap')) {
            Database::query('DROP TABLE IF EXISTS plugin_mindmap');
        }

        // Remove icons from the main/img/icons folder
        $iconName = 'mindmap';
        $iconsList = [
            '64/'.$iconName.'.png',
            '64/'.$iconName.'_na.png',
            '32/'.$iconName.'.png',
            '32/'.$iconName.'_na.png',
            '22/'.$iconName.'.png',
            '22/'.$iconName.'_na.png',
        ];
        $destinationDir = api_get_path(SYS_CODE_PATH).'img/icons/';
        foreach ($iconsList as $icon) {
            $dest = $destinationDir.$icon;
            if (is_file($dest)) {
                @unlink($dest);
            }
        }

        // Deleting course settings and course home icons
        $this->uninstall_course_fields_in_all_courses();
    }
}
