<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookEvaluationAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookLinkevalLog;
use Chamilo\CoreBundle\Entity\GradebookResult;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<GradebookEvaluationAction, GradebookEvaluationAction>
 */
final readonly class GradebookEvaluationActionProcessor implements ProcessorInterface
{
    public const CSRF_TOKEN_ID = 'gradebook_evaluation_action';

    private const ACTION_CREATE = 'create';
    private const ACTION_UPDATE = 'update';
    private const ACTION_DELETE = 'delete';
    private const ACTION_MOVE = 'move';
    private const ACTION_SET_VISIBILITY = 'set_visibility';
    private const ACTION_LOCK = 'lock';
    private const ACTION_UNLOCK = 'unlock';

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): GradebookEvaluationAction
    {
        if (!$data instanceof GradebookEvaluationAction) {
            throw new BadRequestHttpException('Invalid Gradebook evaluation action payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        if ($session instanceof Session && !$session->hasCourse($course)) {
            throw new AccessDeniedHttpException('The requested session does not belong to the current course.');
        }
        $this->validateCourseResourceNode($request, $course);
        $this->validateGroupContext($operation, $course);
        $user = $this->getCurrentUser();

        if (!$this->isAllowedToEditHelper->check(coach: true, course: $course, session: $session)) {
            throw new AccessDeniedHttpException('You are not allowed to manage manual evaluations in this context.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);
        $rootCategory = $this->findRootCategory($course, $session);
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $action = strtolower(trim($data->action));
        $evaluation = match ($action) {
            self::ACTION_CREATE => $this->createEvaluation($data, $rootCategory, $course, $session),
            self::ACTION_UPDATE => $this->updateEvaluation($data, $rootCategory, $course, $session, $user),
            self::ACTION_DELETE => $this->deleteEvaluation($data, $rootCategory, $course, $session),
            self::ACTION_MOVE => $this->moveEvaluation($data, $rootCategory, $course, $session, $user),
            self::ACTION_SET_VISIBILITY => $this->setEvaluationVisibility($data, $rootCategory, $course, $session, $user),
            self::ACTION_LOCK => $this->setEvaluationLock($data, $rootCategory, $course, $session, true),
            self::ACTION_UNLOCK => $this->setEvaluationLock($data, $rootCategory, $course, $session, false),
            default => throw new BadRequestHttpException('Unsupported Gradebook evaluation action.'),
        };

        $this->entityManager->flush();

        $response = new GradebookEvaluationAction();
        $response->action = $action;
        $response->evaluationId = $evaluation instanceof GradebookEvaluation ? (int) $evaluation->getId() : $data->evaluationId;
        $response->success = true;

        return $response;
    }

    private function createEvaluation(
        GradebookEvaluationAction $data,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
    ): GradebookEvaluation {
        $category = $this->getCategoryInGradebook((int) ($data->categoryId ?? 0), $rootCategory, $course, $session);
        $this->assertCategoryCanContainEvaluation($category);
        $this->assertCategoryEditable($category);
        [$title, $description, $weight, $maxScore, $minScore] = $this->validateEvaluationForm($data, $course, null);

        $evaluation = new GradebookEvaluation();
        $evaluation
            ->setTitle($title)
            ->setDescription($description)
            ->setCourse($course)
            ->setCategory($category)
            ->setWeight($weight)
            ->setMax($maxScore)
            ->setMinScore($minScore)
            ->setVisible(1)
            ->setType('evaluation')
            ->setLocked(0)
        ;

        $this->entityManager->persist($evaluation);

        return $evaluation;
    }

    private function updateEvaluation(
        GradebookEvaluationAction $data,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
        User $user,
    ): GradebookEvaluation {
        $evaluation = $this->requireEvaluation($data, $rootCategory, $course, $session);
        $this->assertEvaluationEditable($evaluation);
        $category = $this->getCategoryInGradebook(
            (int) ($data->categoryId ?? $evaluation->getCategory()->getId()),
            $rootCategory,
            $course,
            $session,
        );
        $this->assertCategoryCanContainEvaluation($category);
        $this->assertCategoryEditable($category);
        [$title, $description, $weight, $maxScore, $minScore] = $this->validateEvaluationForm($data, $course, $evaluation);

        $this->logEvaluation($evaluation, $user);
        $evaluation
            ->setTitle($title)
            ->setDescription($description)
            ->setCategory($category)
            ->setWeight($weight)
            ->setMax($maxScore)
            ->setMinScore($minScore)
            ->setVisible(1)
        ;

        return $evaluation;
    }

    private function deleteEvaluation(
        GradebookEvaluationAction $data,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
    ): ?GradebookEvaluation {
        $evaluation = $this->requireEvaluation($data, $rootCategory, $course, $session);
        $this->assertEvaluationEditable($evaluation);
        $this->entityManager->remove($evaluation);

        return null;
    }

    private function moveEvaluation(
        GradebookEvaluationAction $data,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
        User $user,
    ): GradebookEvaluation {
        $evaluation = $this->requireEvaluation($data, $rootCategory, $course, $session);
        $this->assertEvaluationEditable($evaluation);
        $target = $this->getCategoryInGradebook((int) ($data->targetCategoryId ?? 0), $rootCategory, $course, $session);
        $this->assertCategoryCanContainEvaluation($target);
        $this->assertCategoryEditable($target);

        $this->logEvaluation($evaluation, $user);
        $evaluation->setCategory($target);

        return $evaluation;
    }

    private function setEvaluationVisibility(
        GradebookEvaluationAction $data,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
        User $user,
    ): GradebookEvaluation {
        $evaluation = $this->requireEvaluation($data, $rootCategory, $course, $session);
        $this->assertEvaluationEditable($evaluation);
        if (null === $data->visible) {
            throw new BadRequestHttpException('A visibility value is required.');
        }

        $this->logEvaluation($evaluation, $user);
        $evaluation->setVisible($data->visible ? 1 : 0);

        return $evaluation;
    }

    private function setEvaluationLock(
        GradebookEvaluationAction $data,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
        bool $locked,
    ): GradebookEvaluation {
        if (!$this->isSettingEnabled('gradebook.gradebook_locking_enabled')) {
            throw new BadRequestHttpException('Gradebook locking is disabled.');
        }
        if (!$locked && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Only a platform administrator can unlock an evaluation.');
        }

        $evaluation = $this->requireEvaluation($data, $rootCategory, $course, $session);
        $evaluation->setLocked($locked ? 1 : 0);

        return $evaluation;
    }

    /**
     * @return array{0: string, 1: string, 2: float, 3: float, 4: ?float}
     */
    private function validateEvaluationForm(
        GradebookEvaluationAction $data,
        Course $course,
        ?GradebookEvaluation $existingEvaluation,
    ): array {
        $title = trim($data->title);
        if ('' === $title) {
            throw new BadRequestHttpException('The evaluation title is required.');
        }
        if (mb_strlen($title) > 50) {
            throw new BadRequestHttpException('The evaluation title cannot exceed 50 characters.');
        }

        $weight = $data->weight;
        if (null === $weight || $weight < 0) {
            throw new BadRequestHttpException('The evaluation weight must be zero or greater.');
        }

        $scoreModelMax = $this->getCourseScoreModelMax($course);
        $maxScore = $data->maxScore;
        if (null !== $scoreModelMax) {
            $maxScore = $scoreModelMax;
        }
        if ($existingEvaluation instanceof GradebookEvaluation && $this->evaluationHasScoredResults($existingEvaluation)) {
            $maxScore = (float) $existingEvaluation->getMax();
        }
        if (null === $maxScore || $maxScore < 0) {
            throw new BadRequestHttpException('The maximum score must be zero or greater.');
        }

        $minScore = $data->minScore;
        if (null !== $minScore && $minScore < 0) {
            throw new BadRequestHttpException('The minimum score must be zero or greater.');
        }

        return [$title, trim($data->description), (float) $weight, (float) $maxScore, $minScore];
    }

    private function evaluationHasScoredResults(GradebookEvaluation $evaluation): bool
    {
        $count = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(result.id)')
            ->from(GradebookResult::class, 'result')
            ->where('result.evaluation = :evaluation')
            ->andWhere('result.score IS NOT NULL')
            ->setParameter('evaluation', (int) $evaluation->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count > 0;
    }

    private function getCourseScoreModelMax(Course $course): ?float
    {
        $setting = $this->settingsManager->getSetting('exercise.score_grade_model', true);
        if (!\is_array($setting) || !isset($setting['models']) || !\is_array($setting['models']) || [] === $setting['models']) {
            return null;
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
            return null;
        }

        $max = null;
        foreach ($selectedModel['score_list'] as $item) {
            if (!\is_array($item) || !isset($item['max']) || !is_numeric($item['max'])) {
                continue;
            }

            // Legacy EvalForm uses the last score model item as the evaluation maximum.
            $max = (float) $item['max'];
        }

        return $max;
    }

    private function logEvaluation(GradebookEvaluation $evaluation, User $user): void
    {
        $log = new GradebookLinkevalLog();
        $log
            ->setIdLinkevalLog((int) $evaluation->getId())
            ->setTitle((string) $evaluation->getTitle())
            ->setDescription((string) $evaluation->getDescription())
            ->setWeight((int) round((float) $evaluation->getWeight()))
            ->setVisible(1 === (int) $evaluation->getVisible())
            ->setType('evaluation')
            ->setUser($user)
            ->setCreatedAt(new DateTime())
        ;
        $this->entityManager->persist($log);
    }

    private function requireEvaluation(
        GradebookEvaluationAction $data,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
    ): GradebookEvaluation {
        return $this->getEvaluationInGradebook((int) ($data->evaluationId ?? 0), $rootCategory, $course, $session);
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

    private function getCategoryInGradebook(
        int $categoryId,
        GradebookCategory $rootCategory,
        Course $course,
        ?Session $session,
    ): GradebookCategory {
        if ($categoryId <= 0) {
            throw new BadRequestHttpException('A valid Gradebook category id is required.');
        }

        $category = $this->entityManager->getRepository(GradebookCategory::class)->find($categoryId);
        if (!$category instanceof GradebookCategory) {
            throw new NotFoundHttpException('The requested Gradebook category was not found.');
        }
        $this->assertCategoryContext($category, $course, $session);
        if (!$this->isCategoryDescendantOf($category, $rootCategory)) {
            throw new AccessDeniedHttpException('The requested Gradebook category is outside the current Gradebook.');
        }

        return $category;
    }

    private function assertCategoryCanContainEvaluation(GradebookCategory $category): void
    {
        if (null !== $category->getGradeModel()) {
            throw new BadRequestHttpException('Manual evaluations cannot be added to a Gradebook category using a grade model.');
        }
    }

    private function assertCategoryEditable(GradebookCategory $category): void
    {
        if (1 === (int) $category->getLocked() && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('The requested Gradebook category is locked.');
        }
    }

    private function assertEvaluationEditable(GradebookEvaluation $evaluation): void
    {
        if (1 === (int) $evaluation->getLocked() && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('The requested evaluation is locked.');
        }
        $this->assertCategoryEditable($evaluation->getCategory());
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

    private function findRootCategory(Course $course, ?Session $session): ?GradebookCategory
    {
        return $this->entityManager->getRepository(GradebookCategory::class)->findOneBy(
            ['course' => $course, 'session' => $session, 'parent' => null],
            ['id' => 'ASC'],
        );
    }

    private function validateCourseResourceNode(Request $request, Course $course): void
    {
        $nodeId = $request->query->getInt('node');
        $resourceNode = $course->getResourceNode();
        if ($nodeId <= 0 || null === $resourceNode || (int) $resourceNode->getId() !== $nodeId) {
            throw new AccessDeniedHttpException('The requested resource node does not belong to the current course.');
        }
    }

    private function validateGroupContext(Operation $operation, Course $course): void
    {
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        if (!$group instanceof CGroup) {
            return;
        }
        $groupNode = $group->getResourceNode();
        $courseNode = $course->getResourceNode();
        if (null === $groupNode || null === $courseNode
            || (int) ($groupNode->getParent()?->getId() ?? 0) !== (int) $courseNode->getId()
        ) {
            throw new AccessDeniedHttpException('The requested group does not belong to the current course.');
        }
    }

    private function getCurrentUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('A valid user is required.');
        }

        return $user;
    }

    private function validateCsrfToken(string $submittedToken): void
    {
        if ('' === trim($submittedToken)
            || !$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $submittedToken))
        ) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
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
