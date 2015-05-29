<?php
/* For licensing terms, see /license.txt */
/**
 * CurrentCoursesBlock class
 * Plugin to add a block on the homepage to show the current session for a user
 *
 * @package chamilo.plugin.current_sessions_block
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
class CurrentSessionsBlockPlugin extends Plugin
{

    const CONFIG_NUMBER_OF_SESSIONS = 'number_of_sessions';
    const CONFIG_DAYS_BEFORE = 'days_before_start';
    const CONFIG_SHOW_BLOCK = 'show_block';
    const FIELD_VARIABLE_IMAGE = 'image_for_current_sessions_plugin';

    private $numberOfSessions;

    /**
     * Class constructor
     */
    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Angel Fernando Quiroz Campos',
            [
                self::CONFIG_SHOW_BLOCK => 'boolean',
                self::CONFIG_NUMBER_OF_SESSIONS => 'text',
                self::CONFIG_DAYS_BEFORE => 'text'
            ]
        );

        $this->numberOfSessions = intval($this->get(self::CONFIG_NUMBER_OF_SESSIONS));

        if ($this->numberOfSessions === 0) {
            $this->numberOfSessions = 3;
        }
    }

    /**
     * Instance the plugin
     * @staticvar SessionsBlockSliderPlugin $result
     * @return Tour
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * Returns the "system" name of the plugin in lowercase letters
     * @return string
     */
    public function get_name()
    {
        return 'current_sessions_block';
    }

    /**
     * Install the plugin
     */
    public function install()
    {
        $this->createExtraFields();
    }

    /**
     * Uninstall the plugin
     */
    public function uninstall()
    {
        $this->deleteExtraFields();
    }

    /**
     * Get the session list by user
     * @return array The user session
     */
    private function getUserSessions()
    {
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
        $sessionUserTable = Database::get_main_table(TABLE_MAIN_SESSION_USER);

        $fakeFrom = <<<SQL
            $sessionTable s
            INNER JOIN $sessionUserTable su
                ON s.id = su.session_id
SQL;

        $sessions = Database::select(
            's.id, s.name, s.date_start, s.date_end',
            $fakeFrom,
            [
                'where' => ['su.user_id = ?' => api_get_user_id()],
                'order' => 'su.registered_at DESC',
                'limit' => $this->numberOfSessions
            ]
        );

        return $sessions;
    }

    /**
     * Get the current active sessions by date
     * @return array The sessions
     */
    private function getActiveSessions()
    {
        $daysBeforeStart = intval($this->get(self::CONFIG_DAYS_BEFORE));

        $currentUtcDateTime = api_get_utc_datetime();
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);

        $beforeStart = [];

        if ($daysBeforeStart > 0) {
            $beforeStart = Database::select(
                'id, name, date_start, date_end',
                $sessionTable,
                [
                    'where' => [
                        'date_start >= DATE(?) - INTERVAL ? DAY' => [$currentUtcDateTime, $daysBeforeStart]
                    ]
                ]
            );
        }

        $currentSessions = Database::select(
            'id, name, date_start, date_end',
            $sessionTable,
            [
                'where' => [
                    'DATE(?) >= date_start AND DATE(?) <= date_end' => [$currentUtcDateTime, $currentUtcDateTime]
                ]
            ]
        );

        $dateWithoutEnd = Database::select(
            'id, name, date_start, date_end',
            $sessionTable,
            [
                'where' => [
                    "date_start <= DATE(?) AND date_end = '0000-00-00'" => $currentUtcDateTime
                ]
            ]
        );

        $sessions = $beforeStart + $currentSessions + $dateWithoutEnd;

        return $sessions;
    }

    /**
     * Get the finished session for a user
     * @return array
     */
    private function getFinishedSessions()
    {
        //TODO: update this function to get the finished sessions
        return [];
    }

    /**
     * Get the session to show in block
     * @return array The session list
     */
    public function getSessionList()
    {
        $userSessions = $this->getUserSessions();
        $activeSessions = $finishedSessions = [];

        if (count($userSessions) < $this->numberOfSessions) {
            $activeSessions = $this->getActiveSessions();
            $finishedSessions = $this->getFinishedSessions();
        }

        $sessions = $userSessions + $activeSessions + $finishedSessions;
        $sessions = array_slice($sessions, 0, $this->numberOfSessions, true);

        foreach ($sessions as &$session) {
            if ($session['date_start'] != '0000-00-00') {
                $session['date_start'] = api_format_date($session['date_start'], DATE_FORMAT_NUMBER);
            }

            if ($session['date_end'] != '0000-00-00') {
                $session['date_end'] = api_format_date($session['date_end'], DATE_FORMAT_NUMBER);
            }

            $session['stars'] = $this->getNumberOfStars($session['id']);
            $session['progress'] = $this->getSessionProgress($session['id']);

            $fieldImage = new ExtraFieldValue('session');
            $fieldValueInfo = $fieldImage->get_values_by_handler_and_field_variable(
                $session['id'],
                self::FIELD_VARIABLE_IMAGE
            );

            if (!empty($fieldValueInfo)) {
                $session['image'] = $fieldValueInfo['value'];
            }
        }

        return $sessions;
    }

    /**
     * Get the calculated progress for a session
     * @param int $sessionId The session id
     * @return int The progress
     */
    private function getSessionProgress($sessionId)
    {
        $courses = SessionManager::get_course_list_by_session_id($sessionId);
        $progress = 0;

        if (empty($courses)) {
            return 0;
        }

        foreach ($courses as $course) {
            $courseProgress = Tracking::get_avg_student_progress(
                api_get_user_id(),
                $course['code'],
                [],
                $sessionId,
                false,
                true
            );

            if ($courseProgress === false) {
                continue;
            }

            $progress += $courseProgress;
        }

        return $progress / count($courses);
    }

    /**
     * Get the number of stars achieved in a session
     * @param int $sessionId The session id
     * @return int The count
     */
    private function getNumberOfStars($sessionId)
    {
        $totalStars = 0;
        $userId = api_get_user_id();
        $courses = SessionManager::get_course_list_by_session_id($sessionId);

        if (empty($courses)) {
            return 0;
        }

        foreach ($courses as $course) {
            $learnPathListObject = new LearnpathList($userId, $course['code'], $sessionId);
            $learnPaths = $learnPathListObject->get_flat_list();

            $stars = 0;

            foreach ($learnPaths as $learnPathId => $learnPathInfo) {
                if (empty($learnPathInfo['seriousgame_mode'])) {
                    continue;
                }

                $learnPath = new learnpath($course['code'], $learnPathId, $userId);

                $stars += $learnPath->getCalculateStars();
            }

            $totalStars += $stars;
        }

        return $totalStars / count($courses);
    }

    /**
     * Create the new extra fields
     */
    private function createExtraFields()
    {
        $sessionExtraField = new ExtraField('session');
        $sessionExtraField->save([
            'field_type' => ExtraField::FIELD_TYPE_FILE_IMAGE,
            'variable' => self::FIELD_VARIABLE_IMAGE,
            'display_text' => $this->get_lang('ImageForCurrentSessionsBlock'),
            'default_value' => null,
            'field_order' => null,
            'visible' => true,
            'changeable' => true,
            'filter' => null
        ]);
    }

    /**
     * Get the created extrafields variables by this plugin
     * @return array The variables
     */
    public function getExtrafields(){
        return [
            self::FIELD_VARIABLE_SHOW_IN_SLIDER,
            self::FIELD_VARIABLE_IMAGE,
            self::FIELD_VARIABLE_URL
        ];
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
     * Delete extra field and their values
     */
    private function deleteExtraFields()
    {
        $fieldVariables = $this->getExtrafields();

        foreach ($fieldVariables as $variable) {
            $fieldInfo = $this->getExtraFieldInfo($variable);
            $fieldExists = $fieldInfo !== false;

            if (!$fieldExists) {
                continue;
            }

            $extraField = new ExtraField('session');
            $extraField->delete($fieldInfo['id']);
        }
    }

}
