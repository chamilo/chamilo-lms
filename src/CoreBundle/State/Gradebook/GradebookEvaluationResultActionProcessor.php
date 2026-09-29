<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookEvaluationResultAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookResult;
use Chamilo\CoreBundle\Entity\GradebookResultAttempt;
use Chamilo\CoreBundle\Entity\GradebookResultLog;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTime;
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
 * @implements ProcessorInterface<GradebookEvaluationResultAction, GradebookEvaluationResultAction>
 */
final readonly class GradebookEvaluationResultActionProcessor implements ProcessorInterface
{
    public const CSRF_TOKEN_ID = 'gradebook_evaluation_result_action';

    private const ACTION_SAVE_SCORES = 'save_scores';
    private const ACTION_SET_SCORE = 'set_score';
    private const ACTION_DELETE_SCORE = 'delete_score';
    private const ACTION_DELETE_ALL = 'delete_all';
    private const ACTION_ADD_ATTEMPT = 'add_attempt';
    private const ACTION_DELETE_ATTEMPT = 'delete_attempt';

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private UserRepository $userRepository,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): GradebookEvaluationResultAction
    {
        if (!$data instanceof GradebookEvaluationResultAction) {
            throw new BadRequestHttpException('Invalid Gradebook result action payload.');
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
            throw new AccessDeniedHttpException('You are not allowed to grade learners in this context.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);
        $rootCategory = $this->findRootCategory($course, $session);
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $evaluation = $this->getEvaluationInGradebook((int) ($data->evaluationId ?? 0), $rootCategory, $course, $session);
        if (1 === (int) $evaluation->getLocked() && !$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('This evaluation is locked.');
        }

        $students = $this->getStudents($course, $session);
        $studentsById = [];
        foreach ($students as $student) {
            if ($student instanceof User && null !== $student->getId()) {
                $studentsById[(int) $student->getId()] = $student;
            }
        }

        $action = strtolower(trim($data->action));
        match ($action) {
            self::ACTION_SAVE_SCORES => $this->saveScores($data, $evaluation, $studentsById),
            self::ACTION_SET_SCORE => $this->setSingleScore($data, $evaluation, $studentsById),
            self::ACTION_DELETE_SCORE => $this->deleteScore($data, $evaluation, $studentsById),
            self::ACTION_DELETE_ALL => $this->deleteAllScores($evaluation),
            self::ACTION_ADD_ATTEMPT => $this->addAttempt($data, $evaluation, $studentsById),
            self::ACTION_DELETE_ATTEMPT => $this->deleteAttempt($data, $evaluation, $studentsById),
            default => throw new BadRequestHttpException('Unsupported Gradebook result action.'),
        };

        $this->entityManager->flush();
        $this->updateEvaluationStats($evaluation);
        $this->entityManager->flush();

        $response = new GradebookEvaluationResultAction();
        $response->action = $action;
        $response->evaluationId = (int) $evaluation->getId();
        $response->success = true;

        return $response;
    }

    /**
     * @param array<int, User> $studentsById
     */
    private function saveScores(
        GradebookEvaluationResultAction $data,
        GradebookEvaluation $evaluation,
        array $studentsById,
    ): void {
        $existingResults = $this->entityManager->getRepository(GradebookResult::class)->findBy([
            'evaluation' => $evaluation,
        ]);
        $editMode = [] !== $existingResults;
        $resultsByUser = [];
        foreach ($existingResults as $result) {
            if (!$result instanceof GradebookResult || !$result->getUser() instanceof User) {
                continue;
            }
            $resultsByUser[(int) $result->getUser()->getId()] = $result;
        }

        foreach ($data->scores as $userIdValue => $scoreValue) {
            $userId = (int) $userIdValue;
            if (!isset($studentsById[$userId])) {
                throw new AccessDeniedHttpException('A learner in the request is outside the current course context.');
            }

            $result = $resultsByUser[$userId] ?? null;
            if (!$result instanceof GradebookResult) {
                $result = new GradebookResult();
                $result
                    ->setEvaluation($evaluation)
                    ->setUser($studentsById[$userId])
                    ->setCreatedAt(new DateTime())
                ;
                $this->entityManager->persist($result);
                $resultsByUser[$userId] = $result;
            }

            if (!$editMode && (null === $scoreValue || '' === $scoreValue)) {
                continue;
            }

            if ($editMode && (null === $scoreValue || '' === $scoreValue)) {
                $scoreValue = 0;
            }
            if (!is_numeric($scoreValue)) {
                throw new BadRequestHttpException('Every submitted score must be numeric.');
            }

            $score = $this->normalizeScore((float) $scoreValue, (float) $evaluation->getMax());
            $result->setScore($score);

            if ($editMode && $this->isSettingEnabled('gradebook.gradebook_multiple_evaluation_attempts')) {
                $attempt = new GradebookResultAttempt();
                $attempt
                    ->setResult($result)
                    ->setScore($score)
                    ->setComment('')
                ;
                $this->entityManager->persist($attempt);
            }
        }
    }

    /**
     * @param array<int, User> $studentsById
     */
    private function setSingleScore(
        GradebookEvaluationResultAction $data,
        GradebookEvaluation $evaluation,
        array $studentsById,
    ): void {
        $student = $this->requireStudent((int) ($data->userId ?? 0), $studentsById);
        if (null === $data->score) {
            throw new BadRequestHttpException('A score is required.');
        }

        $this->saveScore($evaluation, $student, $data->score, $data->comment);
    }

    private function saveScore(GradebookEvaluation $evaluation, User $student, float $score, string $comment): void
    {
        $score = $this->normalizeScore($score, (float) $evaluation->getMax());
        $result = $this->entityManager->getRepository(GradebookResult::class)->findOneBy([
            'evaluation' => $evaluation,
            'user' => $student,
        ]);

        if (!$result instanceof GradebookResult) {
            $result = new GradebookResult();
            $result
                ->setEvaluation($evaluation)
                ->setUser($student)
                ->setCreatedAt(new DateTime())
            ;
            $this->entityManager->persist($result);
        } else {
            $this->logResult($result, $evaluation, $student);
        }

        $result->setScore($score);
        if ($this->isSettingEnabled('gradebook.gradebook_multiple_evaluation_attempts')) {
            $attempt = new GradebookResultAttempt();
            $attempt
                ->setResult($result)
                ->setScore($score)
                ->setComment('' !== trim($comment) ? trim($comment) : null)
            ;
            $this->entityManager->persist($attempt);
        }
    }

    /**
     * @param array<int, User> $studentsById
     */
    private function deleteScore(
        GradebookEvaluationResultAction $data,
        GradebookEvaluation $evaluation,
        array $studentsById,
    ): void {
        $student = $this->requireStudent((int) ($data->userId ?? 0), $studentsById);
        $result = $this->getResultForStudent($data, $evaluation, $student);
        if (!$result instanceof GradebookResult) {
            return;
        }

        $attempts = $this->entityManager->getRepository(GradebookResultAttempt::class)->findBy(['result' => $result]);
        foreach ($attempts as $attempt) {
            if ($attempt instanceof GradebookResultAttempt) {
                $this->entityManager->remove($attempt);
            }
        }
        $this->entityManager->remove($result);
    }

    private function deleteAllScores(GradebookEvaluation $evaluation): void
    {
        $results = $this->entityManager->getRepository(GradebookResult::class)->findBy(['evaluation' => $evaluation]);
        foreach ($results as $result) {
            if (!$result instanceof GradebookResult) {
                continue;
            }

            $attempts = $this->entityManager->getRepository(GradebookResultAttempt::class)->findBy(['result' => $result]);
            foreach ($attempts as $attempt) {
                if ($attempt instanceof GradebookResultAttempt) {
                    $this->entityManager->remove($attempt);
                }
            }
            $this->entityManager->remove($result);
        }
    }

    /**
     * @param array<int, User> $studentsById
     */
    private function addAttempt(
        GradebookEvaluationResultAction $data,
        GradebookEvaluation $evaluation,
        array $studentsById,
    ): void {
        if (!$this->isSettingEnabled('gradebook.gradebook_multiple_evaluation_attempts')) {
            throw new BadRequestHttpException('Multiple evaluation attempts are disabled.');
        }

        $student = $this->requireStudent((int) ($data->userId ?? 0), $studentsById);
        if (null === $data->score) {
            throw new BadRequestHttpException('A score is required.');
        }
        $score = $this->normalizeScore($data->score, (float) $evaluation->getMax());

        $result = $this->getResultForStudent($data, $evaluation, $student);
        if (!$result instanceof GradebookResult) {
            $result = new GradebookResult();
            $result
                ->setEvaluation($evaluation)
                ->setUser($student)
                ->setCreatedAt(new DateTime())
                ->setScore($score)
            ;
            $this->entityManager->persist($result);
        } elseif (null === $result->getScore() || $score > (float) $result->getScore()) {
            $this->logResult($result, $evaluation, $student);
            $result->setScore($score);
        }

        $attempt = new GradebookResultAttempt();
        $attempt
            ->setResult($result)
            ->setScore($score)
            ->setComment('' !== trim($data->comment) ? trim($data->comment) : null)
        ;
        $this->entityManager->persist($attempt);
    }

    /**
     * @param array<int, User> $studentsById
     */
    private function deleteAttempt(
        GradebookEvaluationResultAction $data,
        GradebookEvaluation $evaluation,
        array $studentsById,
    ): void {
        if (!$this->isSettingEnabled('gradebook.gradebook_multiple_evaluation_attempts')) {
            throw new BadRequestHttpException('Multiple evaluation attempts are disabled.');
        }

        $student = $this->requireStudent((int) ($data->userId ?? 0), $studentsById);
        $result = $this->getResultForStudent($data, $evaluation, $student);
        if (!$result instanceof GradebookResult) {
            throw new NotFoundHttpException('The requested result was not found.');
        }

        $attemptId = (int) ($data->attemptId ?? 0);
        if ($attemptId <= 0) {
            throw new BadRequestHttpException('A valid attempt id is required.');
        }

        $attempt = $this->entityManager->getRepository(GradebookResultAttempt::class)->find($attemptId);
        if (!$attempt instanceof GradebookResultAttempt || (int) $attempt->getResult()->getId() !== (int) $result->getId()) {
            throw new AccessDeniedHttpException('The requested attempt does not belong to this learner result.');
        }

        $this->entityManager->remove($attempt);
    }

    /**
     * @param array<int, User> $studentsById
     */
    private function requireStudent(int $userId, array $studentsById): User
    {
        if ($userId <= 0 || !isset($studentsById[$userId])) {
            throw new AccessDeniedHttpException('The requested learner is outside the current course context.');
        }

        return $studentsById[$userId];
    }

    private function getResultForStudent(
        GradebookEvaluationResultAction $data,
        GradebookEvaluation $evaluation,
        User $student,
    ): ?GradebookResult {
        $resultId = (int) ($data->resultId ?? 0);
        if ($resultId > 0) {
            $result = $this->entityManager->getRepository(GradebookResult::class)->find($resultId);
            if (!$result instanceof GradebookResult
                || (int) $result->getEvaluation()->getId() !== (int) $evaluation->getId()
                || (int) $result->getUser()->getId() !== (int) $student->getId()
            ) {
                throw new AccessDeniedHttpException('The requested result does not belong to this evaluation and learner.');
            }

            return $result;
        }

        $result = $this->entityManager->getRepository(GradebookResult::class)->findOneBy([
            'evaluation' => $evaluation,
            'user' => $student,
        ]);

        return $result instanceof GradebookResult ? $result : null;
    }

    private function logResult(GradebookResult $result, GradebookEvaluation $evaluation, User $student): void
    {
        $log = new GradebookResultLog();
        $log
            ->setResult($result)
            ->setEvaluation($evaluation)
            ->setUser($student)
            ->setCreatedAt(new DateTime())
        ;
        if (null !== $result->getScore()) {
            $log->setScore((float) $result->getScore());
        }
        $this->entityManager->persist($log);
    }

    private function updateEvaluationStats(GradebookEvaluation $evaluation): void
    {
        if (!$this->isSettingEnabled('gradebook.allow_gradebook_stats')) {
            return;
        }

        $results = $this->entityManager->getRepository(GradebookResult::class)->findBy(['evaluation' => $evaluation]);
        $scoreList = [];
        $sum = 0.0;
        $best = 0.0;
        $count = 0;

        foreach ($results as $result) {
            if (!$result instanceof GradebookResult || !$result->getUser() instanceof User) {
                continue;
            }

            $score = $result->getScore();
            $scoreList[(string) $result->getUser()->getId()] = $score;
            $numericScore = null !== $score ? (float) $score : 0.0;
            $sum += $numericScore;
            $best = max($best, $numericScore);
            ++$count;
        }

        $evaluation
            ->setBestScore($best)
            ->setAverageScore($count > 0 ? $sum / $count : 0.0)
            ->setUserScoreList($scoreList)
        ;
    }

    private function normalizeScore(float $score, float $maxScore): float
    {
        if ($score < 0 || $score > $maxScore) {
            throw new BadRequestHttpException('The score must be between zero and the evaluation maximum score.');
        }

        return round($score, $this->getNumberDecimals());
    }

    private function getNumberDecimals(): int
    {
        $value = $this->settingsManager->getSetting('gradebook.gradebook_number_decimals');
        if (null === $value || '' === $value) {
            $value = $this->settingsManager->getSetting('gradebook_number_decimals');
        }

        return max(0, min(6, (int) ($value ?? 2)));
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

    /**
     * @return list<User>
     */
    private function getStudents(Course $course, ?Session $session): array
    {
        $users = $this->userRepository->findUsersByContext(
            (int) $course->getId(),
            $session instanceof Session ? (int) $session->getId() : null,
            null,
        );

        return array_values(array_filter($users, static fn (mixed $user): bool => $user instanceof User));
    }

    private function validateCsrfToken(string $submittedToken): void
    {
        if ('' === trim($submittedToken)
            || !$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $submittedToken))
        ) {
            throw new AccessDeniedHttpException('The security token is invalid or expired.');
        }
    }

    private function isSettingEnabled(string $name): bool
    {
        return $this->toBool($this->settingsManager->getSetting($name, true));
    }

    private function toBool(mixed $value): bool
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
