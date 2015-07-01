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
    }

    /**
     * Uninstall the plugin
     */
    public function uninstall()
    {
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
            's.id',
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
     * @param array $userSessions The user session
     * @return array The sessions
     */
    private function getFilteredActiveSessions(array $userSessions)
    {
        $daysBeforeStart = intval($this->get(self::CONFIG_DAYS_BEFORE));

        $currentUtcDateTime = api_get_utc_datetime();
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);

        $beforeStart = [];

        $placeholders = [];

        if (empty($userSessions)) {
            return [];
        }

        for ($i = 0; $i < count($userSessions); $i++) {
            $placeholders[] = '?';
        }

        if ($daysBeforeStart > 0) {
            $beforeStart = Database::select(
                'id',
                $sessionTable,
                [
                    'where' => [
                        'access_start_date >= DATE(?) - INTERVAL ? DAY AND ' => [$currentUtcDateTime, $daysBeforeStart],
                        'id IN (' . implode(', ', $placeholders) . ')' => array_keys($userSessions)
                    ]
                ]
            );
        }

        $currentSessions = Database::select(
            'id',
            $sessionTable,
            [
                'where' => [
                    'DATE(?) >= access_start_date AND DATE(?) <= access_end_date AND ' => [
                        $currentUtcDateTime,
                        $currentUtcDateTime
                    ],
                    'id IN (' . implode(', ', $placeholders) . ')' => array_keys($userSessions)
                ]
            ]
        );

        $dateWithoutEnd = Database::select(
            'id',
            $sessionTable,
            [
                'where' => [
                    "(access_start_date <= DATE(?) AND access_end_date IS NULL) AND " => $currentUtcDateTime,
                    'id IN (' . implode(', ', $placeholders) . ')' => array_keys($userSessions)
                ]
            ]
        );

        $sessions = $beforeStart + $currentSessions + $dateWithoutEnd;

        return $sessions;
    }

    /**
     * Get the finished sessions for a user
     * @param array $activeSessions The active sessions
     * @return array
     */
    private function getFilteredNotFinishedSessions(array $activeSessions)
    {
        if (empty($activeSessions)) {
            return [];
        }

        $finishedSessions = [];

        $userId = api_get_user_id();

        foreach ($activeSessions as $session) {
            $courses = SessionManager::get_course_list_by_session_id($session['id']);
            $notApprovedCoursesCount = 0;

            if (empty($courses)) {
                continue;
            }

            foreach ($courses as $course) {
                $courseCategories = Category::load(
                    null,
                    null,
                    $course['code'],
                    null,
                    null,
                    $session['id'],
                    false
                );

                if (count($courseCategories) <= 0 || Category::userFinishedCourse($userId, $courseCategories[0])) {
                    continue;
                }

                $notApprovedCoursesCount++;
            }

            if (count($courses) !== $notApprovedCoursesCount) {
                continue;
            }

            $finishedSessions[] = $session;
        }

        return $finishedSessions;
    }

    /**
     * Get the session to show in block
     * @return array The session list
     */
    public function getSessionList()
    {
        $userSessions = $this->getUserSessions();
        $activeSessions = $finishedSessions = [];

        $activeSessions = $this->getFilteredActiveSessions($userSessions);
        $finishedSessions = $this->getFilteredNotFinishedSessions($activeSessions);

        $finishedSessions = array_slice($finishedSessions, 0, $this->numberOfSessions, true);

        $sessions = [];

        foreach ($finishedSessions as $finishedSession) {
            $session = api_get_session_info($finishedSession['id']);

            if ($session['access_start_date'] != '0000-00-00 00:00:00') {
                $session['access_start_date'] = api_format_date($session['access_start_date'], DATE_FORMAT_NUMBER);
            }

            if ($session['access_end_date'] != '0000-00-00 00:00:00') {
                $session['access_end_date'] = api_format_date($session['access_end_date'], DATE_FORMAT_NUMBER);
            }

            $session['stars'] = $this->getNumberOfStars($session['id']);
            $session['progress'] = $this->getSessionProgress($session['id']);

            $fieldImage = new ExtraFieldValue('session');
            $fieldValueInfo = $fieldImage->get_values_by_handler_and_field_variable(
                $session['id'],
                'image'
            );

            if (!empty($fieldValueInfo)) {
                $session['image'] = $fieldValueInfo['value'];
            }

            $sessions[] = $session;
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

                $stars += $learnPath->getCalculateStars($sessionId);
            }

            $totalStars += $stars;
        }

        return $totalStars / count($courses);
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

}
