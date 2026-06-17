<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Survey;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Survey\SurveyCopy;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Repository\CSurveyRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<SurveyCopy>
 */
final readonly class SurveyCopyProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CSurveyRepository $surveyRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SurveyCopy
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $user = $this->getCurrentUser();

        if (!$this->canManageSurveys()) {
            throw new AccessDeniedHttpException('You are not allowed to manage surveys in this context.');
        }

        $surveyId = isset($uriVariables['surveyId']) ? (int) $uriVariables['surveyId'] : 0;
        if ($surveyId <= 0) {
            throw new BadRequestHttpException('A valid survey id is required.');
        }

        $survey = $this->getSurveyFromCurrentContext($surveyId, $course, $session);
        $this->assertCanCopySurvey($survey);

        $copy = new SurveyCopy();
        $copy->surveyId = $surveyId;
        $copy->canCopy = true;
        $copy->csrfToken = (string) $this->csrfTokenManager->getToken(SurveyActionProcessor::CSRF_TOKEN_ID);
        $copy->survey = [
            'iid' => $surveyId,
            'title' => $survey->getTitle(),
            'code' => $survey->getCode(),
            'surveyType' => $survey->getSurveyType(),
            'currentCourseId' => (int) $course->getId(),
            'currentSessionId' => null === $session ? 0 : (int) $session->getId(),
        ];
        $copy->targets = $this->getTargetCourses(
            $user,
            $course,
            $session,
            trim((string) $request->query->get('q', '')),
        );

        return $copy;
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if ($courseId <= 0) {
            throw new BadRequestHttpException('A valid course id is required.');
        }

        $course = $this->entityManager->getRepository(Course::class)->find($courseId);
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('The requested course was not found.');
        }

        return $course;
    }

    private function getSession(Request $request): ?Session
    {
        $sessionId = $request->query->getInt('sid');
        if ($sessionId <= 0) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
    }

    private function canManageSurveys(): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')) {
            return true;
        }

        if (!$this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')) {
            return false;
        }

        return $this->isSettingEnabled('survey.extend_rights_for_coach_on_survey');
    }

    private function getSurveyFromCurrentContext(int $surveyId, Course $course, ?Session $session): CSurvey
    {
        $survey = $this->surveyRepository->find($surveyId);
        if (!$survey instanceof CSurvey) {
            throw new NotFoundHttpException('The requested survey was not found.');
        }

        if ($this->isSurveyInContext($survey, $course, $session)) {
            return $survey;
        }

        throw new AccessDeniedHttpException('The requested survey does not belong to the current course context.');
    }

    private function isSurveyInContext(CSurvey $survey, Course $course, ?Session $session): bool
    {
        $contexts = [$session];
        if (null !== $session && $this->isSettingEnabled('survey.show_surveys_base_in_sessions')) {
            $contexts[] = null;
        }

        foreach ($contexts as $currentSession) {
            $queryBuilder = $this->surveyRepository->getResourcesByCourse(
                $course,
                $currentSession,
                null,
                null,
                false,
                true,
            );

            $queryBuilder
                ->andWhere('resource.iid = :surveyId')
                ->setParameter('surveyId', (int) $survey->getIid(), Types::INTEGER)
            ;

            if (null !== $queryBuilder->getQuery()->getOneOrNullResult()) {
                return true;
            }
        }

        return false;
    }

    private function assertCanCopySurvey(CSurvey $survey): void
    {
        if (3 === $survey->getSurveyType()) {
            throw new BadRequestHttpException('Meeting polls must be managed from the meeting poll view.');
        }

        if ($this->isSurveyEditionHidden($survey)) {
            throw new AccessDeniedHttpException('This survey cannot be copied because edition is disabled by configuration.');
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getTargetCourses(User $user, Course $currentCourse, ?Session $currentSession, string $search): array
    {
        $connection = $this->entityManager->getConnection();
        $currentCourseId = (int) $currentCourse->getId();
        $currentSessionId = null === $currentSession ? 0 : (int) $currentSession->getId();

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return $this->getAdminTargetCourses($connection, $currentCourseId, $currentSessionId, $search);
        }

        return $this->getUserTargetCourses($connection, (int) $user->getId(), $currentCourseId, $currentSessionId, $search);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAdminTargetCourses(Connection $connection, int $currentCourseId, int $currentSessionId, string $search): array
    {
        $searchSql = $this->getSearchSql($search, false);
        $params = $this->getSearchParams($search) + [
            'currentCourseId' => $currentCourseId,
            'currentSessionId' => $currentSessionId,
        ];

        $sql = "
            SELECT c.id AS courseId, c.code AS courseCode, c.title AS courseTitle, 0 AS sessionId, NULL AS sessionTitle, 'course' AS targetType
            FROM course c
            WHERE NOT (c.id = :currentCourseId AND 0 = :currentSessionId)
            $searchSql
            UNION
            SELECT c.id AS courseId, c.code AS courseCode, c.title AS courseTitle, s.id AS sessionId, s.title AS sessionTitle, 'session' AS targetType
            FROM session_rel_course src
            INNER JOIN course c ON c.id = src.c_id
            INNER JOIN session s ON s.id = src.session_id
            WHERE NOT (c.id = :currentCourseId AND s.id = :currentSessionId)
            {$this->getSearchSql($search, true)}
            ORDER BY courseTitle ASC, sessionTitle ASC
            LIMIT 50
        ";

        $rows = $connection->executeQuery(
            $sql,
            $params,
            ['currentCourseId' => ParameterType::INTEGER, 'currentSessionId' => ParameterType::INTEGER],
        )->fetchAllAssociative();

        return $this->normalizeTargets($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getUserTargetCourses(Connection $connection, int $userId, int $currentCourseId, int $currentSessionId, string $search): array
    {
        $params = $this->getSearchParams($search) + [
            'userId' => $userId,
            'teacherStatus' => $this->getCourseManagerStatus(),
            'coachStatus' => Session::COURSE_COACH,
            'currentCourseId' => $currentCourseId,
            'currentSessionId' => $currentSessionId,
        ];

        $sql = "
            SELECT c.id AS courseId, c.code AS courseCode, c.title AS courseTitle, 0 AS sessionId, NULL AS sessionTitle, 'course' AS targetType
            FROM course c
            INNER JOIN course_rel_user cru ON cru.c_id = c.id AND cru.user_id = :userId AND cru.status = :teacherStatus
            WHERE NOT (c.id = :currentCourseId AND 0 = :currentSessionId)
            {$this->getSearchSql($search, false)}
            UNION
            SELECT c.id AS courseId, c.code AS courseCode, c.title AS courseTitle, s.id AS sessionId, s.title AS sessionTitle, 'session' AS targetType
            FROM session_rel_course src
            INNER JOIN course c ON c.id = src.c_id
            INNER JOIN session s ON s.id = src.session_id
            INNER JOIN session_rel_course_rel_user srcru ON srcru.c_id = c.id AND srcru.session_id = s.id AND srcru.user_id = :userId AND srcru.status = :coachStatus
            WHERE NOT (c.id = :currentCourseId AND s.id = :currentSessionId)
            {$this->getSearchSql($search, true)}
            ORDER BY courseTitle ASC, sessionTitle ASC
            LIMIT 50
        ";

        $rows = $connection->executeQuery(
            $sql,
            $params,
            [
                'userId' => ParameterType::INTEGER,
                'teacherStatus' => ParameterType::INTEGER,
                'coachStatus' => ParameterType::INTEGER,
                'currentCourseId' => ParameterType::INTEGER,
                'currentSessionId' => ParameterType::INTEGER,
            ],
        )->fetchAllAssociative();

        return $this->normalizeTargets($rows);
    }

    private function getSearchSql(string $search, bool $includeSession): string
    {
        if ('' === $search) {
            return '';
        }

        $sessionCondition = $includeSession ? ' OR LOWER(s.title) LIKE :search' : '';

        return " AND (LOWER(c.title) LIKE :search OR LOWER(c.code) LIKE :search$sessionCondition)";
    }

    /**
     * @return array<string, mixed>
     */
    private function getSearchParams(string $search): array
    {
        if ('' === $search) {
            return [];
        }

        return ['search' => '%'.mb_strtolower($search).'%'];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeTargets(array $rows): array
    {
        $targets = [];
        $registered = [];

        foreach ($rows as $row) {
            $courseId = (int) $row['courseId'];
            $sessionId = (int) ($row['sessionId'] ?? 0);
            $key = $courseId.'_'.$sessionId;
            if (isset($registered[$key])) {
                continue;
            }

            $registered[$key] = true;
            $courseTitle = trim((string) ($row['courseTitle'] ?? ''));
            $courseCode = trim((string) ($row['courseCode'] ?? ''));
            $sessionTitle = trim((string) ($row['sessionTitle'] ?? ''));
            $label = '' !== $sessionTitle ? \sprintf('%s (%s)', $courseTitle, $sessionTitle) : $courseTitle;

            $targets[] = [
                'id' => $key,
                'label' => $label,
                'sublabel' => '' !== $courseCode ? $courseCode : null,
                'targetCourseId' => $courseId,
                'targetSessionId' => $sessionId,
                'targetType' => $sessionId > 0 ? 'session' : 'course',
            ];
        }

        return $targets;
    }

    private function getCourseManagerStatus(): int
    {
        return \defined('COURSEMANAGER') ? (int) \constant('COURSEMANAGER') : 1;
    }

    private function isSurveyEditionHidden(CSurvey $survey): bool
    {
        $value = $this->settingsManager->getSetting('survey.hide_survey_edition', true);
        if (true === $value || 'true' === $value) {
            return true;
        }

        if ('*' === $value) {
            return true;
        }

        $surveyCode = (string) $survey->getCode();
        if ('' === $surveyCode) {
            return false;
        }

        $hiddenCodes = array_filter(array_map('trim', explode(',', (string) $value)));

        return \in_array($surveyCode, $hiddenCodes, true);
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }
}
