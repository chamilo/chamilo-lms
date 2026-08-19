<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookEvaluationResults;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookResult;
use Chamilo\CoreBundle\Entity\GradebookResultAttempt;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CGroup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const DATE_ATOM;

/**
 * @implements ProviderInterface<GradebookEvaluationResults>
 */
final readonly class GradebookEvaluationResultsProvider implements ProviderInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
        private UserRepository $userRepository,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookEvaluationResults
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        return $this->buildReport($request);
    }

    public function buildReport(Request $request): GradebookEvaluationResults
    {
        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if ($session instanceof Session && !$session->hasCourse($course)) {
            throw new AccessDeniedHttpException('The requested session does not belong to the current course.');
        }
        $this->validateCourseResourceNode($request, $course);
        $groupId = $this->validateGroupContext($course);
        $user = $this->getCurrentUser();

        if (!$this->canViewEvaluationResults()) {
            throw new AccessDeniedHttpException('You are not allowed to view manual evaluation results in this context.');
        }

        $rootCategory = $this->findRootCategory($course, $session);
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $evaluationId = $request->query->getInt('evaluationId');
        $evaluation = $this->getEvaluationInGradebook($evaluationId, $rootCategory, $course, $session);
        $canManage = $this->canManageEvaluationResults($course, $session, $user, $evaluation);
        $allowMultipleAttempts = $this->isSettingEnabled('gradebook.gradebook_multiple_evaluation_attempts');

        $resultRows = $this->entityManager->getRepository(GradebookResult::class)->findBy([
            'evaluation' => $evaluation,
        ]);
        $resultsByUser = [];
        foreach ($resultRows as $result) {
            if (!$result instanceof GradebookResult) {
                continue;
            }

            $resultUser = $result->getUser();
            if (!$resultUser instanceof User || null === $resultUser->getId()) {
                continue;
            }

            $resultsByUser[(int) $resultUser->getId()] = $result;
        }

        $students = $this->userRepository->findUsersByContext(
            (int) $course->getId(),
            $session instanceof Session ? (int) $session->getId() : null,
            null,
        );

        $results = [];
        foreach ($students as $student) {
            if (!$student instanceof User || null === $student->getId()) {
                continue;
            }

            $studentId = (int) $student->getId();
            $result = $resultsByUser[$studentId] ?? null;
            $attempts = [];

            if ($allowMultipleAttempts && $result instanceof GradebookResult) {
                $attemptEntities = $this->entityManager->getRepository(GradebookResultAttempt::class)->findBy(
                    ['result' => $result],
                    ['createdAt' => 'DESC', 'id' => 'DESC'],
                );
                foreach ($attemptEntities as $attempt) {
                    if (!$attempt instanceof GradebookResultAttempt) {
                        continue;
                    }

                    $attempts[] = [
                        'id' => $attempt->getId(),
                        'score' => $attempt->getScore(),
                        'comment' => (string) ($attempt->getComment() ?? ''),
                        'createdAt' => $attempt->getCreatedAt()->format(DATE_ATOM),
                    ];
                }
            }

            $results[] = [
                'userId' => $studentId,
                'resultId' => $result instanceof GradebookResult ? (int) $result->getId() : null,
                'officialCode' => (string) ($student->getOfficialCode() ?? ''),
                'username' => $student->getUsername(),
                'firstname' => (string) ($student->getFirstname() ?? ''),
                'lastname' => (string) ($student->getLastname() ?? ''),
                'score' => $result instanceof GradebookResult ? $result->getScore() : null,
                'createdAt' => $result instanceof GradebookResult ? $result->getCreatedAt()->format(DATE_ATOM) : null,
                'attempts' => $attempts,
            ];
        }

        $response = new GradebookEvaluationResults();
        $response->evaluation = [
            'id' => (int) $evaluation->getId(),
            'categoryId' => (int) $evaluation->getCategory()->getId(),
            'title' => (string) $evaluation->getTitle(),
            'description' => trim(strip_tags((string) $evaluation->getDescription())),
            'weight' => (float) $evaluation->getWeight(),
            'maxScore' => (float) $evaluation->getMax(),
            'minScore' => $evaluation->getMinScore(),
            'visible' => 1 === (int) $evaluation->getVisible(),
            'locked' => 1 === (int) $evaluation->getLocked(),
            'bestScore' => $evaluation->getBestScore(),
            'averageScore' => $evaluation->getAverageScore(),
        ];
        $response->results = $results;
        $response->scoreOptions = $this->getScoreOptions($course, (float) $evaluation->getMax());
        $response->settings = [
            'multipleEvaluationAttempts' => $allowMultipleAttempts,
            'numberDecimals' => $this->getNumberDecimals(),
            'allowStats' => $this->isSettingEnabled('gradebook.allow_gradebook_stats'),
        ];
        $response->context = [
            'cid' => (int) $course->getId(),
            'sid' => $session instanceof Session ? (int) $session->getId() : 0,
            'gid' => $groupId,
            'node' => $request->query->getInt('node'),
        ];
        $response->canManage = $canManage;
        if ($canManage) {
            $response->csrfToken = $this->csrfTokenManager
                ->getToken(GradebookEvaluationResultActionProcessor::CSRF_TOKEN_ID)
                ->getValue()
            ;
            $response->importCsrfToken = $this->csrfTokenManager
                ->getToken(GradebookEvaluationImportProcessor::CSRF_TOKEN_ID)
                ->getValue()
            ;
        }

        return $response;
    }

    private function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
    }

    private function validateCourseResourceNode(Request $request, Course $course): void
    {
        $nodeId = $request->query->getInt('node');
        $resourceNode = $course->getResourceNode();
        if ($nodeId <= 0 || null === $resourceNode || (int) $resourceNode->getId() !== $nodeId) {
            throw new AccessDeniedHttpException('The requested resource node does not belong to the current course.');
        }
    }

    private function validateGroupContext(Course $course): int
    {
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        if (!$group instanceof CGroup) {
            return 0;
        }

        $groupId = (int) $group->getIid();

        $groupNode = $group->getResourceNode();
        $courseNode = $course->getResourceNode();
        if (null === $groupNode || null === $courseNode
            || (int) ($groupNode->getParent()?->getId() ?? 0) !== (int) $courseNode->getId()
        ) {
            throw new AccessDeniedHttpException('The requested group does not belong to the current course.');
        }

        return $groupId;
    }

    private function findRootCategory(Course $course, ?Session $session): ?GradebookCategory
    {
        return $this->entityManager->getRepository(GradebookCategory::class)->findOneBy(
            ['course' => $course, 'session' => $session, 'parent' => null],
            ['id' => 'ASC'],
        );
    }

    private function getEvaluationInGradebook(
        int $evaluationId,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
    ): GradebookEvaluation {
        if ($evaluationId <= 0) {
            throw new BadRequestHttpException('A valid evaluation id is required.');
        }

        $evaluation = $this->entityManager->getRepository(GradebookEvaluation::class)->find($evaluationId);
        if (!$evaluation instanceof GradebookEvaluation) {
            throw new NotFoundHttpException('The requested evaluation was not found.');
        }

        if ((int) $evaluation->getCourse()->getId() !== (int) $course->getId()) {
            throw new AccessDeniedHttpException('The requested evaluation belongs to another course.');
        }

        $category = $evaluation->getCategory();
        $this->assertCategoryContext($category, $course, $session);
        if (!$this->isCategoryDescendantOf($category, $rootCategory)) {
            throw new AccessDeniedHttpException('The requested evaluation is outside the current Gradebook.');
        }

        return $evaluation;
    }

    private function assertCategoryContext(GradebookCategory $category, Course $course, ?Session $session): void
    {
        if ((int) $category->getCourse()->getId() !== (int) $course->getId()) {
            throw new AccessDeniedHttpException('The requested Gradebook category belongs to another course.');
        }

        $categorySessionId = null !== $category->getSession() ? (int) $category->getSession()->getId() : 0;
        $sessionId = $session instanceof Session ? (int) $session->getId() : 0;
        if ($categorySessionId !== $sessionId) {
            throw new AccessDeniedHttpException('The requested Gradebook category belongs to another session context.');
        }
    }

    private function isCategoryDescendantOf(GradebookCategory $category, GradebookCategory $rootCategory): bool
    {
        $visited = [];
        $current = $category;

        while (null !== $current) {
            $currentId = (int) $current->getId();
            if ($currentId === (int) $rootCategory->getId()) {
                return true;
            }
            if (isset($visited[$currentId])) {
                return false;
            }

            $visited[$currentId] = true;
            $current = $current->getParent();
        }

        return false;
    }

    private function canViewEvaluationResults(): bool
    {
        return $this->security->isGranted('ROLE_ADMIN')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            || ($this->security->isGranted('ROLE_SESSION_MANAGER')
                && $this->isSettingEnabled('session.session_admins_edit_courses_content'));
    }

    private function canManageEvaluationResults(
        Course $course,
        ?Session $session,
        User $user,
        GradebookEvaluation $evaluation,
    ): bool {
        if (1 === (int) $evaluation->getLocked() && !$this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if ($this->security->isGranted('ROLE_SESSION_MANAGER')
            && $this->isSettingEnabled('session.session_admins_edit_courses_content')
        ) {
            return true;
        }

        if ($session instanceof Session && $this->isSessionCourseReadOnly($course)) {
            return false;
        }

        $isCourseTeacher = $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER');
        if ($session instanceof Session
            && !$isCourseTeacher
            && Session::READ_ONLY === $session->setAccessVisibilityByUser($user)
        ) {
            return false;
        }

        if ($isCourseTeacher) {
            return true;
        }

        return $session instanceof Session
            && $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER')
            && $this->isSettingEnabled('session.allow_coach_to_edit_course_session');
    }

    private function isSessionCourseReadOnly(Course $course): bool
    {
        if (!$this->isSettingEnabled('session.session_courses_read_only_mode')) {
            return false;
        }

        $value = $this->extraFieldValuesRepository->getValueByVariableAndItem(
            'session_courses_read_only_mode',
            (int) $course->getId(),
            ExtraField::COURSE_FIELD_TYPE,
        );
        if (!$value instanceof ExtraFieldValues) {
            return false;
        }

        $rawValue = strtolower(trim((string) $value->getFieldValue()));

        return '' !== $rawValue && !\in_array($rawValue, ['0', 'false', 'no', 'off'], true);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getScoreOptions(Course $course, float $maxScore): array
    {
        $setting = $this->settingsManager->getSetting('exercise.score_grade_model', true);
        if (!\is_array($setting) || !isset($setting['models']) || !\is_array($setting['models']) || [] === $setting['models']) {
            return [];
        }

        $modelId = -1;
        $courseSettings = $this->entityManager->getRepository(CCourseSetting::class)->findBy(
            ['cId' => (int) $course->getId(), 'variable' => 'score_model_id'],
            ['iid' => 'ASC'],
        );
        foreach ($courseSettings as $courseSetting) {
            if (!$courseSetting instanceof CCourseSetting || null === $courseSetting->getValue()) {
                continue;
            }

            $modelId = (int) $courseSetting->getValue();

            break;
        }

        $selectedModel = $setting['models'][0];
        if (-1 !== $modelId) {
            foreach ($setting['models'] as $model) {
                if (\is_array($model) && (int) ($model['id'] ?? 0) === $modelId) {
                    $selectedModel = $model;

                    break;
                }
            }
        }

        if (!\is_array($selectedModel) || !isset($selectedModel['score_list']) || !\is_array($selectedModel['score_list'])) {
            return [];
        }

        $options = [];
        foreach ($selectedModel['score_list'] as $scoreItem) {
            if (!\is_array($scoreItem)) {
                continue;
            }

            $percentage = (float) ($scoreItem['score_to_qualify'] ?? 0);
            $options[] = [
                'label' => (string) ($scoreItem['variable'] ?? $scoreItem['name'] ?? $percentage),
                'value' => round(($percentage / 100) * $maxScore, 2),
            ];
        }

        return $options;
    }

    private function getNumberDecimals(): int
    {
        return max(
            0,
            (int) ($this->settingsManager->getSetting('gradebook.gradebook_number_decimals', true) ?: 0),
        );
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);
        if (\is_bool($value)) {
            return $value;
        }
        if (!\is_scalar($value)) {
            return false;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
