<?php
/**
 * Plugin class for the Google Meet plugin.
 *
 * @package chamilo.plugin.googlemeet
 *
 * @author Alex Aragón Calixto    <alex.aragon@tunqui.pe>
 */

class GoogleMeetPlugin extends Plugin
{

    const TABLE_MEET_COURSES = 'plugin_meet_courses';
    const TABLE_MEET_LIST = 'plugin_meet_room';
    const SETTING_TITLE = 'tool_title';
    const SETTING_ENABLED = 'google_meet_enabled';

    public $isCoursePlugin = true;

    protected function __construct()
    {
        parent::__construct(
            '1.0',
            '
                Alex Aragón Calixto',
            [
                self::SETTING_ENABLED => 'boolean',
                self::SETTING_TITLE => 'text',
            ]
        );

        $this->isAdminPlugin = true;
    }

    /**
     * @return string
     */
    public function getToolTitle()
    {
        $title = $this->get(self::SETTING_TITLE);

        if (!empty($title)) {
            return $title;
        }

        return $this->get_title();
    }

    /**
     * @return GoogleMeetPlugin
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
        $sql = "CREATE TABLE IF NOT EXISTS ".self::TABLE_MEET_LIST." (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            meet_name VARCHAR(250) NULL,
            meet_url VARCHAR(250) NULL,
            type_meet INT NOT NULL,
            user_id INT NULL NOT NULL,
            cd_id INT NULL NOT NULL,
            start_time DATETIME NULL,
            end_time DATETIME NULL,
            session_id INT,
            activate INT
        )";

        Database::query($sql);

        $src1 = api_get_path(SYS_PLUGIN_PATH).'google_meet/resources/img/64/meet.png';
        $src2 = api_get_path(SYS_PLUGIN_PATH).'google_meet/resources/img/64/meet_na.png';
        $dest1 = api_get_path(SYS_CODE_PATH).'img/icons/64/meet.png';
        $dest2 = api_get_path(SYS_CODE_PATH).'img/icons/64/meet_na.png';

        copy($src1, $dest1);
        copy($src2, $dest2);
    }

    /**
     * This method drops the plugin tables.
     */
    public function uninstall()
    {
        $this->deleteCourseToolLinks();

        $tablesToBeDeleted = [
            self::TABLE_MEET_COURSES,
            self::TABLE_MEET_LIST,
        ];

        foreach ($tablesToBeDeleted as $tableToBeDeleted) {
            $table = Database::get_main_table($tableToBeDeleted);
            $sql = "DROP TABLE IF EXISTS $table";
            Database::query($sql);
        }

        $this->manageTab(false);

    }

    /**
     * @return GoogleMeetPlugin
     */
    public function performActionsAfterConfigure()
    {
        $em = Database::getManager();

        $this->deleteCourseToolLinks();

        if ('true' === $this->get(self::SETTING_ENABLED)) {
            $courses = $em->createQuery('SELECT c.id FROM ChamiloCoreBundle:Course c')->getResult();

            foreach ($courses as $course) {
                $this->createLinkToCourseTool($this->getToolTitle(), $course['id']);
            }
        }

        return $this;
    }

    private function deleteCourseToolLinks()
    {
        Database::getManager()
            ->createQuery('DELETE FROM ChamiloCourseBundle:CTool t WHERE t.category = :category AND t.link LIKE :link')
            ->execute(['category' => 'plugin', 'link' => 'zoom/start.php%']);
    }


    public function saveMeet($values){
        if (!is_array($values) || empty($values['meet_name'])) {
            return false;
        }
        $table = Database::get_main_table(self::TABLE_MEET_LIST);

        $params = [
            'meet_name' => $values['meet_name'],
            'meet_url' => $values['meet_url'],
            'type_meet' => $values['type_meet'],
            'user_id' => api_get_user_id(),
            'cd_id' => $values['cd_id'],
            'start_time' => $values['start_time'],
            'end_time' => $values['end_time'],
            'session_id' => null,
            'activate' => 1,
        ];

        $id = Database::insert($table, $params);

        if ($id > 0) {
            return $id;
        }
    }

}