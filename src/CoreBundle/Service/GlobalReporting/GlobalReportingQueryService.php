<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\GlobalReporting;

use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

final readonly class GlobalReportingQueryService
{
    private const int USER_STATUS_TEACHER = 1;
    private const int USER_STATUS_HUMAN_RESOURCES = 4;
    private const int USER_STATUS_STUDENT = 5;
    private const int USER_STATUS_STUDENT_BOSS = 17;

    private const int USER_RELATION_TYPE_HUMAN_RESOURCES = 7;
    private const int USER_RELATION_TYPE_BOSS = 8;

    private const int COURSE_STATUS_TEACHER = 1;
    private const int COURSE_RELATION_TYPE_COURSE_MANAGER = 1;
    private const int COURSE_RELATION_TYPE_HUMAN_RESOURCES = 1;

    private const int SESSION_RELATION_TYPE_HUMAN_RESOURCES = 1;
    private const int SESSION_STATUS_COURSE_COACH = 2;
    private const int SESSION_RELATION_TYPE_GENERAL_COACH = 3;
    private const int SESSION_RELATION_TYPE_SESSION_ADMIN = 4;

    private const int USER_ACTIVE = 1;

    public function __construct(
        private Connection $connection,
        private AccessUrlScopeHelper $accessUrlScope,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getDashboard(GlobalReportingContext $context): array
    {
        $baseData = [
            'currentUserId' => $context->currentUserId(),
            'isAdministrator' => $context->isAdministrator,
            'isHumanResourcesManager' => $context->isHumanResourcesManager,
            'isSessionAdministratorOnly' => $context->isSessionAdministratorOnly,
            'canViewGlobalReports' => $context->canViewGlobalReports,
            'isStudentBoss' => $context->isStudentBoss,
            'skipGenericData' => $context->skipGenericData,
            'canManageFollowedScope' => $context->addUsersByCoach
                && self::USER_STATUS_TEACHER === $context->currentUser->getStatus(),
            'myProgressEnabled' => !$context->blockMyProgressPage,
            'learningCalendarEnabled' => $context->learningCalendarEnabled,
            'studentFollowUpEnabled' => $context->studentFollowUpEnabled,
        ];

        if ($context->isSessionAdministratorOnly) {
            return [
                ...$baseData,
                'redirectUrl' => '/reporting/sessions',
            ];
        }

        if (!$context->canViewGlobalReports) {
            $redirectUrl = '/reporting/my-progress';
            if ($context->isStudentBoss) {
                $redirectUrl = '/reporting/learners';
            } elseif ($context->blockMyProgressPage) {
                $redirectUrl = '/';
            }

            return [
                ...$baseData,
                'redirectUrl' => $redirectUrl,
            ];
        }

        $studentIds = $this->getFollowedUserIds($context, self::USER_STATUS_STUDENT);
        $studentBossIds = $this->getFollowedUserIds($context, self::USER_STATUS_STUDENT_BOSS);
        $teacherIds = $this->getFollowedUserIds($context, self::USER_STATUS_TEACHER);
        $humanResourcesIds = $this->getFollowedUserIds($context, self::USER_STATUS_HUMAN_RESOURCES);
        $assignedCourses = $this->countAssignedCourses($context);
        $followedCourses = $this->countFollowedCourses($context);
        $followedSessions = $this->countFollowedSessions($context);

        $studentCount = \count($studentIds);
        $genericMetrics = [
            'averageCoursesPerStudent' => null,
            'inactiveStudents' => null,
            'averageTimeSpentSeconds' => null,
            'averageLearningPathProgress' => null,
            'averageScore' => null,
            'forumPosts' => null,
            'averageAssignments' => null,
        ];

        if (!$context->skipGenericData) {
            $genericMetrics = $this->getGenericMetrics(
                $context,
                $studentIds,
                $assignedCourses,
            );
        }

        return [
            ...$baseData,
            'students' => $studentCount,
            'studentBosses' => \count($studentBossIds),
            'teachers' => \count($teacherIds),
            'humanResources' => \count($humanResourcesIds),
            'totalUsers' => $studentCount + \count($studentBossIds) + \count($teacherIds) + \count($humanResourcesIds),
            'assignedCourses' => $assignedCourses,
            'followedCourses' => $followedCourses,
            'followedSessions' => $followedSessions,
            'redirectUrl' => null,
            ...$genericMetrics,
        ];
    }

    /**
     * @return int[]
     */
    public function getScopedUserIds(GlobalReportingContext $context, ?int $userStatus = null): array
    {
        if (null !== $userStatus) {
            return $this->getFollowedUserIds($context, $userStatus);
        }

        $userIds = [];
        foreach ([
            self::USER_STATUS_STUDENT,
            self::USER_STATUS_STUDENT_BOSS,
            self::USER_STATUS_TEACHER,
            self::USER_STATUS_HUMAN_RESOURCES,
        ] as $status) {
            foreach ($this->getFollowedUserIds($context, $status) as $userId) {
                $userIds[$userId] = $userId;
            }
        }

        ksort($userIds);

        return array_values($userIds);
    }

    /**
     * @return int[]
     */
    public function getScopedCourseIds(GlobalReportingContext $context): array
    {
        if ($context->isAdministrator) {
            return $this->fetchIntegerColumn(
                'SELECT DISTINCT course.id
                   FROM course
                   INNER JOIN access_url_rel_course access_course
                       ON access_course.c_id = course.id
                  WHERE access_course.access_url_id = :accessUrlId
               ORDER BY course.id',
                ['accessUrlId' => $context->accessUrlId],
            );
        }

        $courseIds = [];
        $queries = [
            [
                'SELECT DISTINCT subscription.c_id
                   FROM course_rel_user subscription
                   INNER JOIN access_url_rel_course access_course
                       ON access_course.c_id = subscription.c_id
                  WHERE subscription.user_id = :currentUserId
                    AND access_course.access_url_id = :accessUrlId',
                [],
            ],
            [
                'SELECT DISTINCT session_course.c_id
                   FROM session_rel_course session_course
                   INNER JOIN session_rel_user relation
                       ON relation.session_id = session_course.session_id
                   INNER JOIN access_url_rel_session access_session
                       ON access_session.session_id = relation.session_id
                  WHERE relation.user_id = :currentUserId
                    AND access_session.access_url_id = :accessUrlId',
                [],
            ],
            [
                'SELECT DISTINCT relation.c_id
                   FROM session_rel_course_rel_user relation
                   INNER JOIN access_url_rel_session access_session
                       ON access_session.session_id = relation.session_id
                  WHERE relation.user_id = :currentUserId
                    AND access_session.access_url_id = :accessUrlId',
                [],
            ],
        ];

        foreach ($queries as [$sql]) {
            foreach ($this->fetchIntegerColumn($sql, [
                'currentUserId' => $context->currentUserId(),
                'accessUrlId' => $context->accessUrlId,
            ]) as $courseId) {
                $courseIds[$courseId] = $courseId;
            }
        }

        ksort($courseIds);

        return array_values($courseIds);
    }

    /**
     * @return int[]
     */
    public function getAssignedCourseIds(GlobalReportingContext $context): array
    {
        if ($context->isHumanResourcesManager && $context->humanResourcesCanAccessAllSessionContent) {
            return $this->fetchIntegerColumn(
                'SELECT DISTINCT session_course.c_id
                   FROM session_rel_course session_course
                   INNER JOIN session_rel_user human_resources_subscription
                       ON human_resources_subscription.session_id = session_course.session_id
                      AND human_resources_subscription.user_id = :currentUserId
                      AND human_resources_subscription.relation_type = :humanResourcesRelation
                   INNER JOIN access_url_rel_session access_session
                       ON access_session.session_id = session_course.session_id
                  WHERE access_session.access_url_id = :accessUrlId
               ORDER BY session_course.c_id',
                [
                    'currentUserId' => $context->currentUserId(),
                    'humanResourcesRelation' => self::SESSION_RELATION_TYPE_HUMAN_RESOURCES,
                    'accessUrlId' => $context->accessUrlId,
                ],
            );
        }

        return $this->fetchIntegerColumn(
            'SELECT DISTINCT subscription.c_id
               FROM course_rel_user subscription
               INNER JOIN access_url_rel_course access_course
                   ON access_course.c_id = subscription.c_id
              WHERE subscription.user_id = :currentUserId
                AND subscription.status = :teacherStatus
                AND access_course.access_url_id = :accessUrlId
           ORDER BY subscription.c_id',
            [
                'currentUserId' => $context->currentUserId(),
                'teacherStatus' => self::COURSE_STATUS_TEACHER,
                'accessUrlId' => $context->accessUrlId,
            ],
        );
    }

    /**
     * @return int[]
     */
    public function getFollowedCourseIds(GlobalReportingContext $context): array
    {
        if ($context->isHumanResourcesManager && $context->humanResourcesCanAccessAllSessionContent) {
            return [];
        }

        return $this->fetchIntegerColumn(
            'SELECT DISTINCT subscription.c_id
               FROM course_rel_user subscription
               INNER JOIN access_url_rel_course access_course
                   ON access_course.c_id = subscription.c_id
              WHERE subscription.user_id = :currentUserId
                AND subscription.relation_type = :relationType
                AND access_course.access_url_id = :accessUrlId
           ORDER BY subscription.c_id',
            [
                'currentUserId' => $context->currentUserId(),
                'relationType' => $context->isHumanResourcesManager
                    ? self::COURSE_RELATION_TYPE_HUMAN_RESOURCES
                    : self::COURSE_RELATION_TYPE_COURSE_MANAGER,
                'accessUrlId' => $context->accessUrlId,
            ],
        );
    }

    /**
     * @return int[]
     */
    public function getScopedSessionIds(GlobalReportingContext $context): array
    {
        if ($context->isAdministrator) {
            return $this->fetchIntegerColumn(
                'SELECT DISTINCT session.id
                   FROM session
                   INNER JOIN access_url_rel_session access_session
                       ON access_session.session_id = session.id
                  WHERE access_session.access_url_id = :accessUrlId
               ORDER BY session.id',
                ['accessUrlId' => $context->accessUrlId],
            );
        }

        $sessionIds = [];
        foreach ($this->fetchIntegerColumn(
            'SELECT DISTINCT relation.session_id
               FROM session_rel_user relation
               INNER JOIN access_url_rel_session access_session
                   ON access_session.session_id = relation.session_id
              WHERE relation.user_id = :currentUserId
                AND access_session.access_url_id = :accessUrlId',
            [
                'currentUserId' => $context->currentUserId(),
                'accessUrlId' => $context->accessUrlId,
            ],
        ) as $sessionId) {
            $sessionIds[$sessionId] = $sessionId;
        }
        foreach ($this->fetchIntegerColumn(
            'SELECT DISTINCT relation.session_id
               FROM session_rel_course_rel_user relation
               INNER JOIN access_url_rel_session access_session
                   ON access_session.session_id = relation.session_id
              WHERE relation.user_id = :currentUserId
                AND access_session.access_url_id = :accessUrlId',
            [
                'currentUserId' => $context->currentUserId(),
                'accessUrlId' => $context->accessUrlId,
            ],
        ) as $sessionId) {
            $sessionIds[$sessionId] = $sessionId;
        }

        ksort($sessionIds);

        return array_values($sessionIds);
    }

    public function isUserInScope(GlobalReportingContext $context, int $userId): bool
    {
        if ($context->currentUserId() === $userId) {
            return true;
        }

        if ($context->isAdministrator) {
            return $this->accessUrlScope->isUserManaged($context->currentUser, $userId);
        }

        return \in_array($userId, $this->getScopedUserIds($context), true);
    }

    /**
     * @return int[]
     */
    private function getFollowedUserIds(GlobalReportingContext $context, int $userStatus): array
    {
        if ($context->isStudentBoss) {
            return $this->getUsersFromStudentBossScope($context, $userStatus);
        }

        if ($context->isSessionAdministratorOnly) {
            return $this->getUsersFromSessionAdministratorScope($context, $userStatus);
        }

        if ($context->isHumanResourcesManager && $context->humanResourcesCanAccessAllSessionContent) {
            return $this->getUsersFromHumanResourcesScope($context, $userStatus);
        }

        $userIds = [];
        foreach ($this->getDirectlyRelatedUsers($context, $userStatus) as $userId) {
            $userIds[$userId] = $userId;
        }
        foreach ($this->getUsersFromTeacherCourses($context, $userStatus) as $userId) {
            $userIds[$userId] = $userId;
        }
        foreach ($this->getUsersFromGeneralCoachSessions($context, $userStatus) as $userId) {
            $userIds[$userId] = $userId;
        }
        foreach ($this->getUsersFromCourseCoachSessions($context, $userStatus) as $userId) {
            $userIds[$userId] = $userId;
        }

        ksort($userIds);

        return array_values($userIds);
    }

    /**
     * @return int[]
     */
    private function getUsersFromStudentBossScope(GlobalReportingContext $context, int $userStatus): array
    {
        return $this->fetchIntegerColumn(
            'SELECT DISTINCT target.id
               FROM user target
               INNER JOIN user_rel_user relation
                   ON relation.user_id = target.id
               INNER JOIN access_url_rel_user access_user
                   ON access_user.user_id = target.id
              WHERE relation.friend_user_id = :currentUserId
                AND relation.relation_type = :relationType
                AND target.status = :userStatus
                AND access_user.access_url_id = :accessUrlId',
            [
                'currentUserId' => $context->currentUserId(),
                'relationType' => self::USER_RELATION_TYPE_BOSS,
                'userStatus' => $userStatus,
                'accessUrlId' => $context->accessUrlId,
            ],
        );
    }

    /**
     * @return int[]
     */
    private function getDirectlyRelatedUsers(GlobalReportingContext $context, int $userStatus): array
    {
        return $this->fetchIntegerColumn(
            'SELECT DISTINCT target.id
               FROM user target
               INNER JOIN user_rel_user relation
                   ON relation.user_id = target.id
               INNER JOIN access_url_rel_user access_user
                   ON access_user.user_id = target.id
              WHERE relation.friend_user_id = :currentUserId
                AND relation.relation_type = :relationType
                AND target.status = :userStatus
                AND access_user.access_url_id = :accessUrlId',
            [
                'currentUserId' => $context->currentUserId(),
                'relationType' => self::USER_RELATION_TYPE_HUMAN_RESOURCES,
                'userStatus' => $userStatus,
                'accessUrlId' => $context->accessUrlId,
            ],
        );
    }

    /**
     * @return int[]
     */
    private function getUsersFromTeacherCourses(GlobalReportingContext $context, int $userStatus): array
    {
        return $this->fetchIntegerColumn(
            'SELECT DISTINCT target.id
               FROM user target
               INNER JOIN course_rel_user target_subscription
                   ON target_subscription.user_id = target.id
               INNER JOIN course_rel_user teacher_subscription
                   ON teacher_subscription.c_id = target_subscription.c_id
                  AND teacher_subscription.user_id = :currentUserId
                  AND teacher_subscription.status = :teacherStatus
               INNER JOIN access_url_rel_user access_user
                   ON access_user.user_id = target.id
               INNER JOIN access_url_rel_course access_course
                   ON access_course.c_id = target_subscription.c_id
              WHERE target.status = :userStatus
                AND access_user.access_url_id = :accessUrlId
                AND access_course.access_url_id = :accessUrlId',
            [
                'currentUserId' => $context->currentUserId(),
                'teacherStatus' => self::COURSE_STATUS_TEACHER,
                'userStatus' => $userStatus,
                'accessUrlId' => $context->accessUrlId,
            ],
        );
    }

    /**
     * @return int[]
     */
    private function getUsersFromGeneralCoachSessions(GlobalReportingContext $context, int $userStatus): array
    {
        return $this->fetchIntegerColumn(
            'SELECT DISTINCT target.id
               FROM user target
               INNER JOIN session_rel_course_rel_user target_subscription
                   ON target_subscription.user_id = target.id
               INNER JOIN session_rel_user coach_subscription
                   ON coach_subscription.session_id = target_subscription.session_id
                  AND coach_subscription.user_id = :currentUserId
                  AND coach_subscription.relation_type = :generalCoach
               INNER JOIN access_url_rel_user access_user
                   ON access_user.user_id = target.id
               INNER JOIN access_url_rel_session access_session
                   ON access_session.session_id = target_subscription.session_id
              WHERE target.status = :userStatus
                AND access_user.access_url_id = :accessUrlId
                AND access_session.access_url_id = :accessUrlId',
            [
                'currentUserId' => $context->currentUserId(),
                'generalCoach' => self::SESSION_RELATION_TYPE_GENERAL_COACH,
                'userStatus' => $userStatus,
                'accessUrlId' => $context->accessUrlId,
            ],
        );
    }

    /**
     * @return int[]
     */
    private function getUsersFromCourseCoachSessions(GlobalReportingContext $context, int $userStatus): array
    {
        return $this->fetchIntegerColumn(
            'SELECT DISTINCT target.id
               FROM user target
               INNER JOIN session_rel_course_rel_user target_subscription
                   ON target_subscription.user_id = target.id
               INNER JOIN session_rel_course_rel_user coach_subscription
                   ON coach_subscription.session_id = target_subscription.session_id
                  AND coach_subscription.c_id = target_subscription.c_id
                  AND coach_subscription.user_id = :currentUserId
                  AND coach_subscription.status = :courseCoach
               INNER JOIN access_url_rel_user access_user
                   ON access_user.user_id = target.id
               INNER JOIN access_url_rel_session access_session
                   ON access_session.session_id = target_subscription.session_id
              WHERE target.status = :userStatus
                AND access_user.access_url_id = :accessUrlId
                AND access_session.access_url_id = :accessUrlId',
            [
                'currentUserId' => $context->currentUserId(),
                'courseCoach' => self::SESSION_STATUS_COURSE_COACH,
                'userStatus' => $userStatus,
                'accessUrlId' => $context->accessUrlId,
            ],
        );
    }

    /**
     * @return int[]
     */
    private function getUsersFromSessionAdministratorScope(GlobalReportingContext $context, int $userStatus): array
    {
        return $this->fetchIntegerColumn(
            'SELECT DISTINCT target.id
               FROM user target
               INNER JOIN session_rel_course_rel_user target_relation
                   ON target_relation.user_id = target.id
               INNER JOIN session_rel_user administrator_relation
                   ON administrator_relation.session_id = target_relation.session_id
                  AND administrator_relation.user_id = :currentUserId
                  AND administrator_relation.relation_type = :sessionAdministrator
               INNER JOIN access_url_rel_user access_user
                   ON access_user.user_id = target.id
               INNER JOIN access_url_rel_session access_session
                   ON access_session.session_id = target_relation.session_id
              WHERE target.status = :userStatus
                AND access_user.access_url_id = :accessUrlId
                AND access_session.access_url_id = :accessUrlId
              UNION
             SELECT DISTINCT target.id
               FROM user target
               INNER JOIN session_rel_user target_relation
                   ON target_relation.user_id = target.id
               INNER JOIN session_rel_user administrator_relation
                   ON administrator_relation.session_id = target_relation.session_id
                  AND administrator_relation.user_id = :currentUserId
                  AND administrator_relation.relation_type = :sessionAdministrator
               INNER JOIN access_url_rel_user access_user
                   ON access_user.user_id = target.id
               INNER JOIN access_url_rel_session access_session
                   ON access_session.session_id = target_relation.session_id
              WHERE target.status = :userStatus
                AND access_user.access_url_id = :accessUrlId
                AND access_session.access_url_id = :accessUrlId',
            [
                'currentUserId' => $context->currentUserId(),
                'sessionAdministrator' => self::SESSION_RELATION_TYPE_SESSION_ADMIN,
                'userStatus' => $userStatus,
                'accessUrlId' => $context->accessUrlId,
            ],
        );
    }

    /**
     * @return int[]
     */
    private function getUsersFromHumanResourcesScope(GlobalReportingContext $context, int $userStatus): array
    {
        return $this->fetchIntegerColumn(
            'SELECT DISTINCT target.id
               FROM user target
               INNER JOIN session_rel_course_rel_user target_subscription
                   ON target_subscription.user_id = target.id
               INNER JOIN session_rel_user human_resources_subscription
                   ON human_resources_subscription.session_id = target_subscription.session_id
                  AND human_resources_subscription.user_id = :currentUserId
                  AND human_resources_subscription.relation_type = :humanResourcesSessionRelation
               INNER JOIN access_url_rel_user access_user
                   ON access_user.user_id = target.id
               INNER JOIN access_url_rel_session access_session
                   ON access_session.session_id = target_subscription.session_id
              WHERE target.status = :userStatus
                AND access_user.access_url_id = :accessUrlId
                AND access_session.access_url_id = :accessUrlId
              UNION
             SELECT DISTINCT target.id
               FROM user target
               INNER JOIN course_rel_user target_subscription
                   ON target_subscription.user_id = target.id
               INNER JOIN course_rel_user human_resources_subscription
                   ON human_resources_subscription.c_id = target_subscription.c_id
                  AND human_resources_subscription.user_id = :currentUserId
                  AND human_resources_subscription.status = :humanResourcesStatus
                  AND human_resources_subscription.relation_type = :humanResourcesCourseRelation
               INNER JOIN access_url_rel_user access_user
                   ON access_user.user_id = target.id
               INNER JOIN access_url_rel_course access_course
                   ON access_course.c_id = target_subscription.c_id
              WHERE target.status = :userStatus
                AND access_user.access_url_id = :accessUrlId
                AND access_course.access_url_id = :accessUrlId
              UNION
             SELECT DISTINCT target.id
               FROM user target
               INNER JOIN user_rel_user relation
                   ON relation.user_id = target.id
               INNER JOIN access_url_rel_user access_user
                   ON access_user.user_id = target.id
              WHERE relation.friend_user_id = :currentUserId
                AND relation.relation_type = :humanResourcesUserRelation
                AND target.status = :userStatus
                AND access_user.access_url_id = :accessUrlId',
            [
                'currentUserId' => $context->currentUserId(),
                'humanResourcesSessionRelation' => self::SESSION_RELATION_TYPE_HUMAN_RESOURCES,
                'humanResourcesStatus' => self::USER_STATUS_HUMAN_RESOURCES,
                'humanResourcesCourseRelation' => self::COURSE_RELATION_TYPE_HUMAN_RESOURCES,
                'humanResourcesUserRelation' => self::USER_RELATION_TYPE_HUMAN_RESOURCES,
                'userStatus' => $userStatus,
                'accessUrlId' => $context->accessUrlId,
            ],
        );
    }

    private function countAssignedCourses(GlobalReportingContext $context): int
    {
        if ($context->isHumanResourcesManager && $context->humanResourcesCanAccessAllSessionContent) {
            return (int) $this->connection->fetchOne(
                'SELECT COUNT(DISTINCT session_course.c_id)
                   FROM session_rel_course session_course
                   INNER JOIN session_rel_user human_resources_subscription
                       ON human_resources_subscription.session_id = session_course.session_id
                      AND human_resources_subscription.user_id = :currentUserId
                      AND human_resources_subscription.relation_type = :humanResourcesRelation
                   INNER JOIN access_url_rel_session access_session
                       ON access_session.session_id = session_course.session_id
                  WHERE access_session.access_url_id = :accessUrlId',
                [
                    'currentUserId' => $context->currentUserId(),
                    'humanResourcesRelation' => self::SESSION_RELATION_TYPE_HUMAN_RESOURCES,
                    'accessUrlId' => $context->accessUrlId,
                ],
            );
        }

        return (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT subscription.c_id)
               FROM course_rel_user subscription
               INNER JOIN access_url_rel_course access_course
                   ON access_course.c_id = subscription.c_id
              WHERE subscription.user_id = :currentUserId
                AND subscription.status = :teacherStatus
                AND access_course.access_url_id = :accessUrlId',
            [
                'currentUserId' => $context->currentUserId(),
                'teacherStatus' => self::COURSE_STATUS_TEACHER,
                'accessUrlId' => $context->accessUrlId,
            ],
        );
    }

    private function countFollowedCourses(GlobalReportingContext $context): int
    {
        if ($context->isHumanResourcesManager && $context->humanResourcesCanAccessAllSessionContent) {
            return 0;
        }

        return (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT subscription.c_id)
               FROM course_rel_user subscription
               INNER JOIN access_url_rel_course access_course
                   ON access_course.c_id = subscription.c_id
              WHERE subscription.user_id = :currentUserId
                AND subscription.relation_type = :relationType
                AND access_course.access_url_id = :accessUrlId',
            [
                'currentUserId' => $context->currentUserId(),
                'relationType' => $context->isHumanResourcesManager
                    ? self::COURSE_RELATION_TYPE_HUMAN_RESOURCES
                    : self::COURSE_RELATION_TYPE_COURSE_MANAGER,
                'accessUrlId' => $context->accessUrlId,
            ],
        );
    }

    private function countFollowedSessions(GlobalReportingContext $context): int
    {
        if ($context->isHumanResourcesManager && $context->humanResourcesCanAccessAllSessionContent) {
            return (int) $this->connection->fetchOne(
                'SELECT COUNT(DISTINCT subscription.session_id)
                   FROM session_rel_user subscription
                   INNER JOIN access_url_rel_session access_session
                       ON access_session.session_id = subscription.session_id
                  WHERE subscription.user_id = :currentUserId
                    AND subscription.relation_type = :humanResourcesRelation
                    AND access_session.access_url_id = :accessUrlId',
                [
                    'currentUserId' => $context->currentUserId(),
                    'humanResourcesRelation' => self::SESSION_RELATION_TYPE_HUMAN_RESOURCES,
                    'accessUrlId' => $context->accessUrlId,
                ],
            );
        }

        return (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT session_id)
               FROM (
                    SELECT general_coach.session_id
                      FROM session_rel_user general_coach
                      INNER JOIN access_url_rel_session access_session
                          ON access_session.session_id = general_coach.session_id
                     WHERE general_coach.user_id = :currentUserId
                       AND general_coach.relation_type = :generalCoach
                       AND access_session.access_url_id = :accessUrlId
                    UNION
                    SELECT course_coach.session_id
                      FROM session_rel_course_rel_user course_coach
                      INNER JOIN access_url_rel_session access_session
                          ON access_session.session_id = course_coach.session_id
                     WHERE course_coach.user_id = :currentUserId
                       AND course_coach.status = :courseCoach
                       AND access_session.access_url_id = :accessUrlId
               ) followed_sessions',
            [
                'currentUserId' => $context->currentUserId(),
                'generalCoach' => self::SESSION_RELATION_TYPE_GENERAL_COACH,
                'courseCoach' => self::SESSION_STATUS_COURSE_COACH,
                'accessUrlId' => $context->accessUrlId,
            ],
        );
    }

    /**
     * @param int[] $studentIds
     *
     * @return array<string, int|float|null>
     */
    private function getGenericMetrics(
        GlobalReportingContext $context,
        array $studentIds,
        int $courseCount,
    ): array {
        $studentCount = \count($studentIds);
        if (0 === $studentCount) {
            return [
                'averageCoursesPerStudent' => 0.0,
                'inactiveStudents' => 0,
                'averageTimeSpentSeconds' => 0,
                'averageLearningPathProgress' => 0.0,
                'averageScore' => 0.0,
                'forumPosts' => 0,
                'averageAssignments' => 0.0,
            ];
        }

        $inactiveLimit = new DateTimeImmutable('-7 days', new DateTimeZone('UTC'));
        $inactiveStudents = (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT user.id)
               FROM user
              WHERE user.id IN (:studentIds)
                AND user.active = :active
                AND (user.last_login IS NULL OR user.last_login <= :inactiveLimit)',
            [
                'studentIds' => $studentIds,
                'active' => self::USER_ACTIVE,
                'inactiveLimit' => $inactiveLimit,
            ],
            [
                'studentIds' => ArrayParameterType::INTEGER,
                'active' => Types::INTEGER,
                'inactiveLimit' => Types::DATETIME_IMMUTABLE,
            ],
        );

        $totalTimeSpent = (int) $this->connection->fetchOne(
            'SELECT COALESCE(SUM(TIMESTAMPDIFF(SECOND, login.login_date, login.logout_date)), 0)
               FROM track_e_login login
               INNER JOIN access_url_rel_user access_user
                   ON access_user.user_id = login.login_user_id
              WHERE login.login_user_id IN (:studentIds)
                AND access_user.access_url_id = :accessUrlId',
            [
                'studentIds' => $studentIds,
                'accessUrlId' => $context->accessUrlId,
            ],
            [
                'studentIds' => ArrayParameterType::INTEGER,
                'accessUrlId' => Types::INTEGER,
            ],
        );

        $progressRows = $this->connection->fetchAllAssociative(
            'SELECT per_user.user_id, AVG(per_user.progress) AS average_progress
               FROM (
                    SELECT user_id, c_id, session_id, lp_id, MAX(progress) AS progress
                      FROM c_lp_view
                     WHERE user_id IN (:studentIds)
                       AND progress IS NOT NULL
                     GROUP BY user_id, c_id, session_id, lp_id
               ) per_user
              GROUP BY per_user.user_id',
            ['studentIds' => $studentIds],
            ['studentIds' => ArrayParameterType::INTEGER],
        );
        $progressSum = 0.0;
        foreach ($progressRows as $row) {
            $progressSum += (float) ($row['average_progress'] ?? 0.0);
        }

        $scoreRow = $this->connection->fetchAssociative(
            "SELECT SUM(item_view.score) AS sum_score,
                    SUM(item.max_score) AS sum_max_score
               FROM c_lp lp
               INNER JOIN c_lp_item item
                   ON item.lp_id = lp.iid
               INNER JOIN c_lp_view lp_view
                   ON lp_view.lp_id = item.lp_id
               INNER JOIN c_lp_item_view item_view
                   ON item_view.lp_item_id = item.iid
                  AND item_view.lp_view_id = lp_view.iid
              WHERE item.item_type IN ('sco', 'quiz')
                AND lp_view.user_id IN (:studentIds)",
            ['studentIds' => $studentIds],
            ['studentIds' => ArrayParameterType::INTEGER],
        );
        $sumMaxScore = (float) ($scoreRow['sum_max_score'] ?? 0.0);
        $averageScore = $sumMaxScore > 0.0
            ? ((float) ($scoreRow['sum_score'] ?? 0.0) / $sumMaxScore) * 100.0
            : 0.0;

        $forumPosts = (int) $this->connection->fetchOne(
            'SELECT COUNT(post.iid)
               FROM c_forum_post post
              WHERE post.poster_id IN (:studentIds)
                AND post.visible = 1',
            ['studentIds' => $studentIds],
            ['studentIds' => ArrayParameterType::INTEGER],
        );

        $assignments = (int) $this->connection->fetchOne(
            'SELECT COUNT(publication.iid)
               FROM c_student_publication publication
              WHERE publication.user_id IN (:studentIds)
                AND publication.parent_id IS NOT NULL
                AND publication.active IN (0, 1)',
            ['studentIds' => $studentIds],
            ['studentIds' => ArrayParameterType::INTEGER],
        );

        return [
            'averageCoursesPerStudent' => $courseCount / $studentCount,
            'inactiveStudents' => $inactiveStudents,
            'averageTimeSpentSeconds' => (int) ($totalTimeSpent / $studentCount),
            'averageLearningPathProgress' => $progressSum / $studentCount,
            'averageScore' => $averageScore,
            'forumPosts' => $forumPosts,
            'averageAssignments' => $assignments / $studentCount,
        ];
    }

    /**
     * @param array<string, int> $parameters
     *
     * @return int[]
     */
    private function fetchIntegerColumn(string $sql, array $parameters): array
    {
        return array_values(array_map(
            'intval',
            $this->connection->fetchFirstColumn($sql, $parameters),
        ));
    }
}
