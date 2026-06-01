<?php

/* For licensing terms, see /license.txt */

/**
 * Limit session resubscriptions.
 */
class Resubscription extends Plugin
{
    public const LIMIT_CALENDAR_YEAR = 'calendar_year';
    public const LIMIT_NATURAL_YEAR = 'natural_year';

    protected function __construct()
    {
        $parameters = [
            'resubscription_limit' => [
                'type' => 'select',
                'options' => [
                    self::LIMIT_CALENDAR_YEAR => 'CalendarYear',
                    self::LIMIT_NATURAL_YEAR => 'NaturalYear',
                ],
                'translate_options' => true,
            ],
        ];

        parent::__construct('0.2', 'Imanol Losada Oriol', $parameters);
    }

    public static function create(): self
    {
        static $result = null;

        return $result ?: $result = new self();
    }

    public function install(): void
    {
    }

    public function uninstall(): void
    {
    }

    public function getConfiguredLimit(): string
    {
        $limit = (string) $this->get('resubscription_limit');

        if (self::LIMIT_NATURAL_YEAR === $limit) {
            return self::LIMIT_NATURAL_YEAR;
        }

        return self::LIMIT_CALENDAR_YEAR;
    }

    /**
     * Throws an exception when the given user cannot be subscribed to the target session.
     *
     * This public method is intentionally available for both the Symfony event subscriber
     * and the legacy admin enrolment page. Some legacy admin flows do not reliably load
     * plugin event subscribers, so the admin page can enforce the same rule directly
     * without duplicating SQL logic.
     *
     * @throws Exception
     */
    public function assertUserCanResubscribe(int $userId, int $targetSessionId): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        if ($userId <= 0 || $targetSessionId <= 0) {
            return;
        }

        $matchingCourseEndDate = $this->findLatestMatchingCourseEndDate($userId, $targetSessionId);

        if (null === $matchingCourseEndDate) {
            return;
        }

        throw new Exception($this->buildRestrictionMessage($userId, $matchingCourseEndDate));
    }

    public function getRestrictionMessageForUser(int $userId, int $targetSessionId): ?string
    {
        if (!$this->isEnabled()) {
            return null;
        }

        if ($userId <= 0 || $targetSessionId <= 0) {
            return null;
        }

        $matchingCourseEndDate = $this->findLatestMatchingCourseEndDate($userId, $targetSessionId);

        if (null === $matchingCourseEndDate) {
            return null;
        }

        return $this->buildRestrictionMessage($userId, $matchingCourseEndDate);
    }

    private function findLatestMatchingCourseEndDate(int $userId, int $currentSessionId): ?string
    {
        $limit = $this->getConfiguredLimit();
        $limitDateFormat = 'Y-01-01';
        $limitDate = gmdate($limitDateFormat);

        if (self::LIMIT_NATURAL_YEAR === $limit) {
            $limitDateFormat = 'Y-m-d';
            $limitDate = gmdate($limitDateFormat, strtotime(gmdate($limitDateFormat).' -1 year'));
        }

        $sessionUserTable = Database::get_main_table(TABLE_MAIN_SESSION_USER);
        $sessionTable = Database::get_main_table(TABLE_MAIN_SESSION);
        $sessionCourseTable = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);

        /*
         * Chamilo sessions can be configured either with fixed access dates
         * or with a duration in days. Duration-based sessions do not always store
         * an access_end_date in session_rel_user, so the effective end date has to
         * be computed from the user registration date plus the session duration.
         */
        $effectiveEndDateExpression = "
            COALESCE(
                su.access_end_date,
                s.access_end_date,
                CASE
                    WHEN s.duration IS NOT NULL
                        AND s.duration > 0
                        AND su.registered_at IS NOT NULL
                    THEN DATE_ADD(su.registered_at, INTERVAL s.duration DAY)
                    ELSE NULL
                END
            )
        ";

        $userSessions = Database::select(
            'su.session_id, '.$effectiveEndDateExpression.' AS effective_access_end_date',
            $sessionUserTable.' su INNER JOIN '.$sessionTable.' s ON s.id = su.session_id',
            [
                'where' => [
                    'su.user_id = ? AND su.relation_type = ? AND su.session_id <> ? AND '.$effectiveEndDateExpression.' IS NOT NULL AND '.$effectiveEndDateExpression.' >= ?' => [
                        $userId,
                        \Chamilo\CoreBundle\Entity\Session::STUDENT,
                        $currentSessionId,
                        $limitDate,
                    ],
                ],
                'order' => 'effective_access_end_date DESC',
            ]
        );

        if (empty($userSessions)) {
            return null;
        }

        $userSessionCourses = [];

        foreach ($userSessions as $userSession) {
            $userSessionId = (int) $userSession['session_id'];
            $effectiveEndDate = (string) $userSession['effective_access_end_date'];

            $userSessionCourseResult = Database::select(
                'c_id',
                $sessionCourseTable,
                [
                    'where' => [
                        'session_id = ?' => [
                            $userSessionId,
                        ],
                    ],
                ]
            );

            foreach ($userSessionCourseResult as $userSessionCourse) {
                $courseId = (int) $userSessionCourse['c_id'];

                if (!isset($userSessionCourses[$courseId])) {
                    $userSessionCourses[$courseId] = $effectiveEndDate;
                }
            }
        }

        if (empty($userSessionCourses)) {
            return null;
        }

        $currentSessionCourseResult = Database::select(
            'c_id',
            $sessionCourseTable,
            [
                'where' => [
                    'session_id = ?' => [
                        $currentSessionId,
                    ],
                ],
            ]
        );

        foreach ($currentSessionCourseResult as $currentSessionCourse) {
            $courseId = (int) $currentSessionCourse['c_id'];

            if (isset($userSessionCourses[$courseId])) {
                return (string) $userSessionCourses[$courseId];
            }
        }

        return null;
    }

    private function buildRestrictionMessage(int $userId, string $endDate): string
    {
        $limit = $this->getConfiguredLimit();
        $limitDateFormat = 'Y-01-01';

        if (self::LIMIT_NATURAL_YEAR === $limit) {
            $limitDateFormat = 'Y-m-d';
        }

        $resubscriptionDate = gmdate($limitDateFormat, strtotime($endDate.' +1 year'));

        if ($userId !== api_get_user_id()) {
            $userInfo = api_get_user_info($userId);
            $userName = $userInfo
                ? api_get_person_name($userInfo['firstname'] ?? '', $userInfo['lastname'] ?? '')
                : (string) $userId;

            $message = sprintf(
                $this->get_lang('UserCanResubscribeFromX'),
                $userName,
                $resubscriptionDate
            );

            return Display::return_message($message, 'warning', false);
        }

        $message = sprintf(
            $this->get_lang('CanResubscribeFromX'),
            $resubscriptionDate
        );

        return Display::return_message($message, 'info', false);
    }
}
