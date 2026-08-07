<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\CourseReporting;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class CourseReportingContextResolver
{
    private const int HUMAN_RESOURCES_STATUS = 4;
    private const int HUMAN_RESOURCES_RELATION_TYPE = 1;

    public function __construct(
        private CourseRepository $courseRepository,
        private SessionRepository $sessionRepository,
        private Connection $connection,
        private RequestStack $requestStack,
        private Security $security,
        private SettingsManager $settingsManager,
    ) {}

    public function resolve(): CourseReportingContext
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $courseId = $request->query->getInt('cid');
        $sessionId = max(0, $request->query->getInt('sid'));
        $groupId = max(0, $request->query->getInt('gid'));

        if ($courseId <= 0) {
            throw new BadRequestHttpException('Missing or invalid course identifier.');
        }

        $course = $this->courseRepository->find($courseId);
        if (!$course instanceof Course) {
            throw new NotFoundHttpException('Course not found.');
        }

        $session = null;
        if ($sessionId > 0) {
            $session = $this->sessionRepository->find($sessionId);
            if (!$session instanceof Session) {
                throw new NotFoundHttpException('Session not found.');
            }

            $isCourseInSession = (bool) $this->connection->fetchOne(
                'SELECT 1
                   FROM session_rel_course
                  WHERE session_id = :sessionId
                    AND c_id = :courseId',
                [
                    'sessionId' => $sessionId,
                    'courseId' => $courseId,
                ]
            );

            if (!$isCourseInSession) {
                throw new AccessDeniedHttpException('The course does not belong to this session.');
            }
        }

        if ($groupId > 0 && !$this->groupBelongsToContext($groupId, $courseId, $sessionId)) {
            throw new AccessDeniedHttpException('The group does not belong to this course context.');
        }

        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User || null === $currentUser->getId()) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $isAdministrator = $this->security->isGranted('ROLE_ADMIN');
        $isTeacher = $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            || $course->hasUserAsTeacher($currentUser)
            || ($session instanceof Session && (
                $session->hasUserAsGeneralCoach($currentUser)
                || $session->hasCoachInCourseList($currentUser)
            ));

        $isHumanResourcesManager = $this->isHumanResourcesManagerForContext(
            $currentUser,
            $courseId,
            $session
        );

        if (!$isAdministrator && !$isTeacher && !$isHumanResourcesManager) {
            throw new AccessDeniedHttpException('You are not allowed to view course reporting.');
        }

        $showEmailAddresses = $this->isEnabled(
            $this->settingsManager->getSetting('display.show_email_addresses', true)
        );
        $hideCharts = $this->isEnabled(
            $this->settingsManager->getSetting('course.hide_course_report_graph', true)
        );

        $useMaximumLearningPathProgress = $this->isEnabled(
            $this->settingsManager->getSetting('lp.lp_show_max_progress_instead_of_average', true)
        );

        if ($this->isEnabled(
            $this->settingsManager->getSetting(
                'lp.lp_show_max_progress_or_average_enable_course_level_redefinition',
                true
            )
        )) {
            $courseSetting = $this->connection->fetchOne(
                'SELECT value
                   FROM c_course_setting
                  WHERE c_id = :courseId
                    AND variable = :variable
                  ORDER BY iid DESC
                  LIMIT 1',
                [
                    'courseId' => $courseId,
                    'variable' => 'lp_show_max_or_average_progress',
                ]
            );

            if (\in_array($courseSetting, ['max', 'average'], true)) {
                $useMaximumLearningPathProgress = 'max' === $courseSetting;
            }
        }

        $hideSessionList = $this->isEnabled(
            $this->settingsManager->getSetting('session.hide_reporting_session_list', true)
        );
        $allowMessageTracking = $this->isEnabled(
            $this->settingsManager->getSetting('message.allow_user_message_tracking', true)
        );
        $configuredExerciseIds = [];
        $configuredExercisesSetting = $this->settingsManager->getSetting(
            'exercise.add_exercise_best_attempt_in_report',
            true
        );
        if (
            \is_array($configuredExercisesSetting)
            && isset($configuredExercisesSetting['courses'][$course->getCode()])
            && \is_array($configuredExercisesSetting['courses'][$course->getCode()])
        ) {
            $configuredExerciseIds = array_values(array_unique(array_filter(array_map(
                'intval',
                $configuredExercisesSetting['courses'][$course->getCode()]
            ))));
        }

        $hiddenColumnIndexes = [0, 8, 9, 10, 11];
        $hiddenColumnsSetting = $this->settingsManager->getSetting(
            'course.course_log_hide_columns',
            true
        );
        if (
            \is_array($hiddenColumnsSetting)
            && isset($hiddenColumnsSetting['columns'])
            && \is_array($hiddenColumnsSetting['columns'])
        ) {
            $hiddenColumnIndexes = array_values(array_unique(array_map(
                'intval',
                $hiddenColumnsSetting['columns']
            )));
        }

        $defaultExtraFieldVariables = [];
        $defaultExtraFieldsSetting = $this->settingsManager->getSetting(
            'course.course_log_default_extra_fields',
            true
        );
        if (
            \is_array($defaultExtraFieldsSetting)
            && isset($defaultExtraFieldsSetting['extra_fields'])
            && \is_array($defaultExtraFieldsSetting['extra_fields'])
        ) {
            $defaultExtraFieldVariables = array_values(array_unique(array_filter(array_map(
                static fn (mixed $value): string => trim((string) $value),
                $defaultExtraFieldsSetting['extra_fields']
            ))));
        }

        return new CourseReportingContext(
            $course,
            $session,
            $groupId,
            $currentUser,
            $isAdministrator,
            $isTeacher,
            $isHumanResourcesManager,
            $showEmailAddresses,
            $hideCharts,
            $useMaximumLearningPathProgress,
            $hideSessionList,
            $allowMessageTracking,
            $configuredExerciseIds,
            $hiddenColumnIndexes,
            $defaultExtraFieldVariables,
        );
    }

    private function groupBelongsToContext(int $groupId, int $courseId, int $sessionId): bool
    {
        $sessionSql = $sessionId > 0
            ? 'AND (rl.session_id IS NULL OR rl.session_id = 0 OR rl.session_id = :sessionId)'
            : 'AND (rl.session_id IS NULL OR rl.session_id = 0)';

        $parameters = [
            'groupId' => $groupId,
            'courseId' => $courseId,
        ];

        if ($sessionId > 0) {
            $parameters['sessionId'] = $sessionId;
        }

        return (bool) $this->connection->fetchOne(
            "SELECT 1
               FROM c_group_info group_info
               INNER JOIN resource_link rl
                   ON rl.resource_node_id = group_info.resource_node_id
              WHERE group_info.iid = :groupId
                AND rl.c_id = :courseId
                $sessionSql
              LIMIT 1",
            $parameters
        );
    }

    private function isHumanResourcesManagerForContext(
        User $user,
        int $courseId,
        ?Session $session
    ): bool {
        if (!$this->security->isGranted('ROLE_HR')) {
            return false;
        }

        $courseRelation = (bool) $this->connection->fetchOne(
            'SELECT 1
               FROM course_rel_user
              WHERE user_id = :userId
                AND c_id = :courseId
                AND status = :status
                AND relation_type = :relationType
              LIMIT 1',
            [
                'userId' => (int) $user->getId(),
                'courseId' => $courseId,
                'status' => self::HUMAN_RESOURCES_STATUS,
                'relationType' => self::HUMAN_RESOURCES_RELATION_TYPE,
            ]
        );

        if ($courseRelation) {
            return true;
        }

        if (!$session instanceof Session) {
            return false;
        }

        foreach ($user->getDRHSessions() as $drhSession) {
            if ($drhSession->getId() === $session->getId()) {
                return true;
            }
        }

        return false;
    }

    private function isEnabled(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (\is_int($value)) {
            return 1 === $value;
        }

        if (\is_string($value)) {
            return \in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }
}
