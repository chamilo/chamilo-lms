<?php

/* For licensing terms, see /license.txt */

/**
 * Plugin class for the Google Meet plugin.
 *
 * @author Alex Aragón Calixto <alex.aragon@tunqui.pe>
 */
class GoogleMeetPlugin extends Plugin
{
    public const TABLE_MEET_LIST = 'plugin_google_meet_room';
    public const SETTING_TITLE = 'tool_title';
    public const SETTING_ENABLED = 'google_meet_enabled';
    public const GOOGLE_MEET_URL = 'https://meet.google.com/';

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
    public function getTitle()
    {
        $title = $this->get(self::SETTING_TITLE);

        if (!empty($title)) {
            return $title;
        }

        return $this->get_title();
    }

    /**
     * {@inheritdoc}
     */
    public function get_name()
    {
        return 'google_meet';
    }

    /**
     * Create a plugin instance.
     *
     * @return GoogleMeetPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * This method creates the tables required to this plugin and copies icons
     * to the right places.
     */
    public function install()
    {
        $sql = "CREATE TABLE IF NOT EXISTS ".self::TABLE_MEET_LIST." (
            id INT unsigned NOT NULL auto_increment PRIMARY KEY,
            meet_name VARCHAR(250) NULL,
            meet_url VARCHAR(250) NULL,
            meet_description VARCHAR(250) NULL,
            meet_color VARCHAR(7) NULL,
            type_meet INT NOT NULL,
            start_time DATETIME NULL,
            end_time DATETIME NULL,
            c_id INT NULL NOT NULL,
            session_id INT,
            activate INT
        )";

        Database::query($sql);

        // Copy icons into the main/img/icons folder
        $iconName = 'google_meet';
        $iconsList = [
            '64/'.$iconName.'.png',
            '64/'.$iconName.'_na.png',
            '32/'.$iconName.'.png',
            '32/'.$iconName.'_na.png',
            '22/'.$iconName.'.png',
            '22/'.$iconName.'_na.png',
        ];
        $sourceDir = api_get_path(SYS_PLUGIN_PATH).'google_meet/resources/img/';
        $destinationDir = api_get_path(SYS_CODE_PATH).'img/icons/';
        foreach ($iconsList as $icon) {
            $src = $sourceDir.$icon;
            $dest = $destinationDir.$icon;
            copy($src, $dest);
        }
    }

    /**
     * This method drops the plugin tables and icons.
     */
    public function uninstall()
    {
        $this->deleteCourseToolLinks();

        $tablesToBeDeleted = [
            self::TABLE_MEET_LIST,
        ];

        foreach ($tablesToBeDeleted as $tableToBeDeleted) {
            $table = Database::get_main_table($tableToBeDeleted);
            $sql = "DROP TABLE IF EXISTS $table";
            Database::query($sql);
        }
        $dest1 = api_get_path(SYS_CODE_PATH).'img/icons/64/google_meet.png';
        $dest2 = api_get_path(SYS_CODE_PATH).'img/icons/64/google_meet_na.png';
        if (file_exists($dest1)) {
            @unlink($dest1);
        }
        if (file_exists($dest2)) {
            @unlink($dest2);
        }

        $this->manageTab(false);

        // Remove icons from the main/img/icons folder
        $iconName = 'google_meet';
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
    }

    /**
     * {@inheritdoc}
     *
     * @return GoogleMeetPlugin
     */
    public function performActionsAfterConfigure()
    {
        $em = Database::getManager();

        $this->deleteCourseToolLinks();

        if ('true' === $this->get(self::SETTING_ENABLED)) {
            $courses = $em->createQuery('SELECT c.id FROM ChamiloCoreBundle:Course c')->getResult();

            foreach ($courses as $course) {
                $this->createLinkToCourseTool($this->getTitle(), $course['id']);
            }
        }

        return $this;
    }

    public function saveMeet($values)
    {
        if (!is_array($values) || empty($values['meet_name'])) {
            return false;
        }
        $table = Database::get_main_table(self::TABLE_MEET_LIST);

        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();
        $url = self::filterUrl($values['meet_url']);
        if (!isset($values['type_meet'])) {
            $values['type_meet'] = 1;
        }

        $params = [
            'meet_name' => $values['meet_name'],
            'meet_url' => $url,
            'type_meet' => $values['type_meet'],
            'meet_description' => $values['meet_description'],
            'meet_color' => $values['meet_color'],
            'c_id' => $courseId,
            'start_time' => null,
            'end_time' => null,
            'session_id' => $sessionId,
            'activate' => 1,
        ];

        $id = Database::insert($table, $params);

        if ($id > 0) {
            return $id;
        }
    }

    public function listMeets($courseId, $sessionId = 0)
    {
        $list = [];
        $tableMeetList = Database::get_main_table(self::TABLE_MEET_LIST);
        $courseId = (int) $courseId;
        $sessionId = (int) $sessionId;

        $sql = "SELECT * FROM $tableMeetList WHERE c_id = $courseId AND session_id = $sessionId AND activate = 1";

        $result = Database::query($sql);

        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $action = Display::url(
                    Display::return_icon(
                        'delete.png',
                        get_lang('Delete'),
                        [],
                        ICON_SIZE_SMALL
                    ),
                    'meets.php?action=delete&id_room='.$row['id'],
                    [
                        'onclick' => 'javascript:if(!confirm('."'".
                            addslashes(api_htmlentities(get_lang("ConfirmYourChoice")))
                            ."'".')) return false;',
                    ]
                );
                $active = Display::return_icon('accept.png', null, [], ICON_SIZE_TINY);
                if (intval($row['activate']) != 1) {
                    $active = Display::return_icon('error.png', null, [], ICON_SIZE_TINY);
                }

                $list[] = [
                    'id' => $row['id'],
                    'meet_name' => $row['meet_name'],
                    'meet_url' => $row['meet_url'],
                    'meet_description' => $row['meet_description'],
                    'meet_color' => $row['meet_color'],
                    'type_meet' => $row['type_meet'],
                    'c_id' => $row['c_id'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'session_id' => $row['session_id'],
                    'activate' => $active,
                    'actions' => $action,
                ];
            }
        }

        return $list;
    }

    public function getMeet($idMeet)
    {
        if (empty($idMeet)) {
            return false;
        }
        $meet = [];
        $tableMeetList = Database::get_main_table(self::TABLE_MEET_LIST);
        $idMeet = (int) $idMeet;
        $sql = "SELECT * FROM $tableMeetList WHERE id = $idMeet";

        $result = Database::query($sql);
        if (Database::num_rows($result) > 0) {
            while ($row = Database::fetch_array($result)) {
                $meet = [
                    'id' => $row['id'],
                    'meet_name' => $row['meet_name'],
                    'meet_url' => $row['meet_url'],
                    'meet_description' => $row['meet_description'],
                    'meet_color' => $row['meet_color'],
                    'type_meet' => $row['type_meet'],
                    'c_id' => $row['c_id'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'session_id' => $row['session_id'],
                    'activate' => $row['activate'],
                ];
            }
        }

        return $meet;
    }

    public function updateMeet($values)
    {
        if (!is_array($values) || empty($values['meet_name'])) {
            return false;
        }
        $table = Database::get_main_table(self::TABLE_MEET_LIST);

        $courseId = api_get_course_int_id();
        $sessionId = api_get_session_id();
        $url = self::filterUrl($values['meet_url']);
        if (!isset($values['type_meet'])) {
            $values['type_meet'] = 1;
        }

        $params = [
            'meet_name' => $values['meet_name'],
            'meet_url' => $url,
            'type_meet' => $values['type_meet'],
            'meet_description' => $values['meet_description'],
            'meet_color' => $values['meet_color'],
            'c_id' => $courseId,
            'start_time' => null,
            'end_time' => null,
            'session_id' => $sessionId,
            'activate' => 1,
        ];

        Database::update(
            $table,
            $params,
            [
                'id = ?' => [
                    $values['id'],
                ],
            ]
        );

        return true;
    }

    /**
     * Delete a given meeting.
     *
     * @param int $idMeet Chamilo's internal ID of the meeting
     *
     * @return bool True on success, false on failure
     */
    public function deleteMeet($idMeet)
    {
        if (empty($idMeet)) {
            return false;
        }
        $idMeet = (int) $idMeet;
        $tableMeetList = Database::get_main_table(self::TABLE_MEET_LIST);
        $sql = "DELETE FROM $tableMeetList WHERE id = $idMeet";
        $result = Database::query($sql);

        if (Database::affected_rows($result) != 1) {
            return false;
        }

        return true;
    }

    /**
     * Delete links to the tool from the c_tool table.
     */
    private function deleteCourseToolLinks()
    {
        Database::getManager()
            ->createQuery('DELETE FROM ChamiloCourseBundle:CTool t WHERE t.category = :category AND t.link LIKE :link')
            ->execute(['category' => 'plugin', 'link' => 'google_meet/start.php%']);
    }

    /**
     * Do a bit of prevention on the meeting URL format.
     *
     * @param string $url The URL received from the user
     *
     * @return string Reformatted URL
     */
    private function filterUrl($url)
    {
        if (!empty($url)) {
            if (preg_match('#^'.self::GOOGLE_MEET_URL.'#', $url)) {
                // The URL starts with the right Google Meet protocol and domain, do nothing
            } elseif (preg_match('#^'.substr(self::GOOGLE_MEET_URL, 8).'#', $url)) {
                // The URL starts with meet.google.com without the protocol. Add it
                $url = 'https://'.$url;
            } else {
                // We assume it's just the meeting code. Add the full Google Meet prefix
                if (substr($url, 0, 1) === '/') {
                    // Remove prefixing slash, if any
                    $url = substr($url, 1);
                }
                $url = self::GOOGLE_MEET_URL.$url;
            }
        }

        return $url;
    }
}
