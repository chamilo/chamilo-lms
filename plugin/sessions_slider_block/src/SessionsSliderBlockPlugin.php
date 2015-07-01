<?php
/* For licensing terms, see /license.txt */
/**
 * SessionsBlockSliderPlugin class
 * Plugin to add a sessions slider in homepage
 * @package chamilo.plugin.sessions_slider_block
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class SessionsSliderBlockPlugin extends Plugin
{
    const CONFIG_SHOW_SLIDER = 'show_slider';
    const CONFIG_WITHING_DAYS_TO_OPEN = 'within_days_to_open';
    const FIELD_VARIABLE_SHOW_IN_SLIDER = 'show_in_slider';
    const FIELD_VARIABLE_COURSE_LEVEL = 'course_level';
    const FIELD_VARIABLE_COURSE_TAG = 'tag';

    /**
     * Class constructor
     */
    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Angel Fernando Quiroz Campos',
            [
                self::CONFIG_SHOW_SLIDER => 'boolean',
                self::CONFIG_WITHING_DAYS_TO_OPEN => 'text'
            ]
        );
    }

    /**
     * Instance the plugin
     * @staticvar SessionsBlockSliderPlugin $result
     * @return Tour
     */
    public static function create(){
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Returns the "system" name of the plugin in lowercase letters
     * @return string
     */
    public function get_name()
    {
        return 'sessions_slider_block';
    }

    /**
     * Install the plugin
     */
    public function install()
    {
        $this->createExtraFields();
    }

    /**
     * Create the new extra fields
     */
    private function createExtraFields()
    {
        $sessionExtraField = new ExtraField('session');

        $sessionExtraField->save([
            'field_type' => ExtraField::FIELD_TYPE_CHECKBOX,
            'variable' => self::FIELD_VARIABLE_SHOW_IN_SLIDER,
            'display_text' => $this->get_lang('ShowInSliderBlock'),
            'default_value' => null,
            'field_order' => null,
            'visible' => true,
            'changeable' => true,
            'filter' => null
        ]);

        $levelOptions = array(
            get_lang('Beginner')
        );

        $courseExtraField = new ExtraField('course');
        $courseExtraField->save([
            'field_type' => ExtraField::FIELD_TYPE_SELECT,
            'variable' => self::FIELD_VARIABLE_COURSE_LEVEL,
            'display_text' => $this->get_lang('Level'),
            'default_value' => null,
            'field_order' => null,
            'visible' => true,
            'changeable' => true,
            'field_options' => implode('; ', $levelOptions)
        ]);

        $courseFieldId = $courseExtraField->save([
            'field_type' => ExtraField::FIELD_TYPE_TAG,
            'variable' => self::FIELD_VARIABLE_COURSE_TAG,
            'display_text' => $this->get_lang('Tag'),
            'default_value' => null,
            'field_order' => null,
            'visible' => true,
            'changeable' => true
        ]);
    }

    /**
     * Uninstall the plugin
     */
    public function uninstall()
    {
        $this->deleteExtraFields();
    }

    /**
     * Get the extra field information by its variable
     * @param sstring $fieldVariable The field variable
     * @return array The info
     */
    private function getExtraFieldInfo($fieldVariable)
    {
        $extraField = new ExtraField('session');
        $extraFieldHandler = $extraField->get_handler_field_info_by_field_variable($fieldVariable);

        return $extraFieldHandler;
    }

    /**
     * Get the created extrafields variables for session by this plugin
     * @return array The variables
     */
    public function getSessionExtrafields(){
        return [
            self::FIELD_VARIABLE_SHOW_IN_SLIDER
        ];
    }

    /**
     * Get the created extrafields variables for courses by this plugin
     * @return array The variables
     */
    public function getCourseExtrafields(){
        return [
            self::FIELD_VARIABLE_COURSE_LEVEL,
            self::FIELD_VARIABLE_COURSE_TAG
        ];
    }

    /**
     * Delete extra field and their values
     */
    private function deleteExtraFields()
    {
        $sessionVariables = $this->getSessionExtrafields();

        foreach ($sessionVariables as $variable) {
            $fieldInfo = $this->getExtraFieldInfo($variable);
            $fieldExists = $fieldInfo !== false;

            if (!$fieldExists) {
                continue;
            }

            $extraField = new ExtraField('session');
            $extraField->delete($fieldInfo['id']);
        }

        $courseVariables = $this->getCourseExtrafields();

        foreach ($courseVariables as $variable) {
            $fieldInfo = $this->getExtraFieldInfo($variable);
            $fieldExists = $fieldInfo !== false;

            if (!$fieldExists) {
                continue;
            }

            $extraField = new ExtraField('course');
            $extraField->delete($fieldInfo['id']);
        }
    }

    /**
     * Get the session to show in slider
     * @return array The session list
     */
    public function getSessionList()
    {
        $sessions = [];

        if (!api_is_anonymous()) {
            $sessions = $this->getSessionsForLoggedUser();
        }

        if (count($sessions) === 0 || api_is_anonymous()) {
            $sessions = $this->getSessionsForAnonymousUser();
        }

        if (count($sessions) <= 0) {
            return [];
        }

        $sessionToShow = [];

        foreach ($sessions as $sessionId) {
            $sql = "
                SELECT s.id
                FROM " . Database::get_main_table(TABLE_MAIN_SESSION) . " s
                INNER JOIN " . Database::get_main_table(TABLE_EXTRA_FIELD_VALUES) . " fv
                    ON s.id = fv.item_id
                INNER JOIN " . Database::get_main_table(TABLE_EXTRA_FIELD) . " f
                    ON fv.field_id = f.id
                WHERE
                    f.variable = '" . self::FIELD_VARIABLE_SHOW_IN_SLIDER . "' AND
                    s.id = " . intval($sessionId);

            $result = Database::query($sql);

            if (Database::num_rows($result) <= 0) {
                continue;
            }

            $sessionToShow[] = api_get_session_info($sessionId);
        }

        return $sessionToShow;
    }

    /**
     * Get the course tags for a user
     * @param int $userId The user ID
     * @return array
     */
    private function getUserCoursesTags($userId)
    {
        $sql = "
            SELECT scu.c_id, fo.display_text, fv.value, count(fv.value) count
            FROM " . Database::get_main_table(TABLE_EXTRA_FIELD_VALUES) . " fv
            INNER JOIN " . Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER) . " scu
                ON fv.item_id = scu.c_id
            INNER JOIN " . Database::get_main_table(TABLE_EXTRA_FIELD) . " f
                ON fv.field_id = f.id
            INNER JOIN " . Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS) . " fo
                ON fv.value = fo.id
            WHERE
                scu.user_id = " . intval($userId) . " AND
                f.variable = '" . self::FIELD_VARIABLE_COURSE_TAG . "'
            GROUP BY scu.c_id
            ORDER BY count DESC";

        $result = Database::query($sql);
        $num = Database::num_rows($result);
        if (empty($num)) {
            return [];
        }

        $list = [];

        while ($row = Database::fetch_assoc($result)) {
            $list[] = [
                'count' => $row['count'],
                'value' => $row['value']
            ];
        }

        return $list;
    }

    /**
     * Get the open sessions and session to open by date
     * @return array The sessions
     */
    public function getOpenSessions()
    {
        $daysBeforeStart = intval($this->get(self::CONFIG_WITHING_DAYS_TO_OPEN));

        $currentUtcDateTime = api_get_utc_datetime();
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);

        $beforeStart = [];

        if ($daysBeforeStart > 0) {
            $beforeStart = Database::select(
                'id',
                $sessionTable,
                [
                    'where' => [
                        '(? + INTERVAL ? DAY) >= access_start_date AND ' => [$currentUtcDateTime, $daysBeforeStart],
                        '? <= access_start_date' => $currentUtcDateTime
                    ]
                ]
            );
        }

        $betweenDates = Database::select(
            'id',
            $sessionTable,
            [
                'where' => [
                    '? >= access_start_date AND ? <= access_end_date ' => [$currentUtcDateTime, $currentUtcDateTime]
                ]
            ]
        );

        $dateWithoutEnd = Database::select(
            'id',
            $sessionTable,
            [
                'where' => [
                    "access_start_date <= ? AND " => $currentUtcDateTime,
                    "(access_end_date = ? OR access_end_date IS NULL)" => '0000-00-00 00:00:00'
                ]
            ]
        );

        $sessions = $beforeStart + $betweenDates + $dateWithoutEnd;

        return array_keys($sessions);
    }

    /**
     * Get the sessions ID for show slider to non-anonymous users
     * @return type
     */
    private function getSessionsForLoggedUser()
    {
        $sessionsIdList = $this->getOpenSessions();

        if (empty($sessionsIdList)) {
            return [];
        }

        $userCoursesTag = $this->getUserCoursesTags(api_get_user_id());

        if (count($userCoursesTag) <= 0) {
            return [];
        }

        $tagList = [];

        foreach ($sessionsIdList as $sessionId) {
            $courses = SessionManager::get_course_list_by_session_id($sessionId);

            foreach ($courses as $course) {
                $sql = "
                    SELECT fo.display_text, fv.value
                    FROM " . Database::get_main_table(TABLE_EXTRA_FIELD_VALUES) . " fv
                    INNER JOIN " . Database::get_main_table(TABLE_EXTRA_FIELD) . " f
                        ON fv.field_id = f.id
                    INNER JOIN " . Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS) . " fo
                        ON fv.value = fo.id
                    WHERE
                        fv.item_id = " . $course['real_id'] . " AND
                        f.variable = '" . self::FIELD_VARIABLE_COURSE_TAG . "'";

                $result = Database::query($sql);

                if (Database::num_rows($result) <= 0) {
                    continue;
                }

                while ($row = Database::fetch_assoc($result)) {
                    $tagList[] = [
                        'session' => $sessionId,
                        'course' => $course['real_id'],
                        'tag' => $row['value']
                    ];
                }
            }
        }

        $sessionToShow = [];

        foreach ($userCoursesTag as $userTag) {
            foreach ($tagList as $sessionCourseTag) {
                if ($sessionCourseTag['tag'] != $userTag['value']) {
                    continue;
                }

                $sessionToShow[] = $sessionCourseTag['session'];
            }
        }

        return array_unique($sessionToShow);
    }

    /**
     * Get the sessions ID for show slider to anonymous users
     * @return array
     */
    private function getSessionsForAnonymousUser()
    {
        $sessions = $this->getOpenSessions();

        return array_unique($sessions);
    }

}
