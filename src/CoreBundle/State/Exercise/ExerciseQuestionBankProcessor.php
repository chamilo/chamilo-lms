<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionBank;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<ExerciseQuestionBank, ExerciseQuestionBank>
 */
final readonly class ExerciseQuestionBankProcessor implements ProcessorInterface
{
    private const ACTION_REUSE = 'reuse';
    private const ACTION_DELETE = 'delete';
    private const LP_ITEM_TYPE_QUIZ = 'quiz';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseQuestionBank
    {
        if (!$data instanceof ExerciseQuestionBank) {
            throw new BadRequestHttpException('Invalid exercise question bank action payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to manage the question bank in this context.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);
        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        $quiz = 0 < $exerciseId ? $this->getExerciseFromCurrentContext($exerciseId, $course, $session) : null;
        if ($quiz instanceof CQuiz && $this->isExerciseReadOnlyFromLearningPath((int) $quiz->getIid())) {
            throw new AccessDeniedHttpException('This exercise is read-only because it is included in a learning path.');
        }

        $action = strtolower(trim($data->action));
        if (!\in_array($action, [self::ACTION_REUSE, self::ACTION_DELETE], true)) {
            throw new BadRequestHttpException('Unsupported question bank action.');
        }

        if (self::ACTION_REUSE === $action) {
            [$addedCount, $skippedCount] = $this->reuseQuestions($quiz, $data, $course, $session);
        } else {
            $addedCount = $this->deleteQuestions($data, $course, $session);
            $skippedCount = 0;
        }
        $this->entityManager->flush();

        $response = new ExerciseQuestionBank();
        $response->exerciseId = $exerciseId;
        $response->action = $action;
        $response->questionId = $data->questionId;
        $response->questionIds = $data->questionIds;
        $response->success = true;
        $response->addedCount = $addedCount;
        $response->skippedCount = $skippedCount;
        if (self::ACTION_DELETE === $action) {
            $response->message = 1 === $addedCount ? 'Question deleted' : 'Questions deleted';

            return $response;
        }

        $response->message = 1 === $addedCount ? 'Question added to the test' : 'Questions added to the test';

        return $response;
    }

    private function getCourse(Request $request): Course
    {
        $courseId = $request->query->getInt('cid');
        if (0 >= $courseId) {
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
        if (0 >= $sessionId) {
            return null;
        }

        $session = $this->entityManager->getRepository(Session::class)->find($sessionId);
        if (!$session instanceof Session) {
            throw new BadRequestHttpException('The requested session was not found.');
        }

        return $session;
    }

    private function canManageExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function validateCsrfToken(string $submittedCsrfToken): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(ExerciseQuestionBankProvider::CSRF_TOKEN_ID, $submittedCsrfToken))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
    }

    private function getExerciseFromCurrentContext(int $exerciseId, Course $course, ?Session $session): CQuiz
    {
        $quiz = $this->quizRepository->find($exerciseId);
        if (!($quiz instanceof CQuiz)) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        if ($this->isExerciseInContext($exerciseId, $course, $session)) {
            return $quiz;
        }

        throw new AccessDeniedHttpException('The requested exercise does not belong to the current course context.');
    }

    private function isExerciseReadOnlyFromLearningPath(int $exerciseId): bool
    {
        if ($this->isSettingEnabled('lp.force_edit_exercise_in_lp')) {
            return false;
        }

        return null !== $this->entityManager->createQueryBuilder()
            ->select('lpItem.iid')
            ->from(CLpItem::class, 'lpItem')
            ->andWhere('lpItem.itemType = :itemType')
            ->andWhere('lpItem.path = :exerciseId OR lpItem.ref = :exerciseId')
            ->setParameter('itemType', self::LP_ITEM_TYPE_QUIZ, Types::STRING)
            ->setParameter('exerciseId', (string) $exerciseId, Types::STRING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    private function isExerciseInContext(int $exerciseId, Course $course, ?Session $session): bool
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz.iid')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('quiz.iid = :exerciseId')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('exerciseId', $exerciseId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        $this->applySessionFilter($queryBuilder, 'links', $session);

        return null !== $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function reuseQuestions(?CQuiz $quiz, ExerciseQuestionBank $data, Course $course, ?Session $session): array
    {
        if (!($quiz instanceof CQuiz)) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $questionIds = $this->getSubmittedQuestionIds($data);
        if ([] === $questionIds) {
            throw new BadRequestHttpException('Select at least one question.');
        }

        $addedCount = 0;
        $skippedCount = 0;
        $nextOrder = $this->getNextQuestionOrder($quiz);
        foreach ($questionIds as $questionId) {
            $question = $this->getQuestionFromCurrentContext($questionId, $course, $session);
            if (!$this->isQuestionTypeAllowedByFeedback((int) $question->getType(), (int) $quiz->getFeedbackType())) {
                $skippedCount++;
                continue;
            }

            if ($this->hasQuestion($quiz, $questionId)) {
                $skippedCount++;
                continue;
            }

            $relation = new CQuizRelQuestion();
            $relation
                ->setQuiz($quiz)
                ->setQuestion($question)
                ->setQuestionOrder($nextOrder)
            ;
            $this->entityManager->persist($relation);
            $nextOrder++;
            $addedCount++;
        }

        if (0 === $addedCount) {
            throw new BadRequestHttpException('No question was added.');
        }

        return [$addedCount, $skippedCount];
    }

    private function deleteQuestions(ExerciseQuestionBank $data, Course $course, ?Session $session): int
    {
        if (!$this->canRunRestrictedAction()) {
            throw new AccessDeniedHttpException('You are not allowed to delete questions in this context.');
        }

        $questionIds = $this->getSubmittedQuestionIds($data);
        if ([] === $questionIds) {
            throw new BadRequestHttpException('Select at least one question.');
        }

        $deletedCount = 0;
        foreach ($questionIds as $questionId) {
            $question = $this->getQuestionFromCurrentContext($questionId, $course, $session);
            if ($this->isQuestionUsedInActiveQuiz($question, $course, $session)) {
                continue;
            }

            foreach ($this->getAnswers($question) as $answer) {
                $this->entityManager->remove($answer);
            }
            $this->entityManager->remove($question);
            $deletedCount++;
        }

        if (0 === $deletedCount) {
            throw new BadRequestHttpException('No question was deleted.');
        }

        return $deletedCount;
    }

    /**
     * @return array<int, int>
     */
    private function getSubmittedQuestionIds(ExerciseQuestionBank $data): array
    {
        $questionIds = array_map('intval', $data->questionIds);
        if (null !== $data->questionId && 0 < (int) $data->questionId) {
            $questionIds[] = (int) $data->questionId;
        }

        return array_values(array_unique(array_filter($questionIds, static fn (int $questionId): bool => 0 < $questionId)));
    }

    private function getQuestionFromCurrentContext(int $questionId, Course $course, ?Session $session): CQuizQuestion
    {
        $question = $this->entityManager->getRepository(CQuizQuestion::class)->find($questionId);
        if (!$question instanceof CQuizQuestion || null === $question->getIid()) {
            throw new NotFoundHttpException('The requested question was not found.');
        }

        if ($this->isQuestionInContext($question, $course, $session)) {
            return $question;
        }

        throw new AccessDeniedHttpException('The requested question does not belong to the current course context.');
    }

    private function isQuestionInContext(CQuizQuestion $question, Course $course, ?Session $session): bool
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('question.iid')
            ->from(CQuizQuestion::class, 'question')
            ->leftJoin('question.resourceNode', 'questionNode')
            ->andWhere('question = :question')
            ->setParameter('question', $question)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        $existsQuestionLink = $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from(ResourceLink::class, 'questionLink')
            ->where('questionLink.resourceNode = questionNode')
            ->andWhere('IDENTITY(questionLink.course) = :courseId')
        ;
        $this->applyActiveLinkConstraints($existsQuestionLink, 'questionLink', $session, true);

        $existsViaQuiz = $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from(CQuizRelQuestion::class, 'scopeRelation')
            ->innerJoin('scopeRelation.quiz', 'scopeQuiz')
            ->innerJoin('scopeQuiz.resourceNode', 'scopeNode')
            ->innerJoin('scopeNode.resourceLinks', 'scopeLinks')
            ->where('scopeRelation.question = question')
            ->andWhere('IDENTITY(scopeLinks.course) = :courseId')
        ;
        $this->applyActiveLinkConstraints($existsViaQuiz, 'scopeLinks', $session, true);

        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->exists($existsQuestionLink->getDQL()),
                $queryBuilder->expr()->exists($existsViaQuiz->getDQL())
            )
        );

        if (null !== $session) {
            $queryBuilder->setParameter('sessionId', (int) $session->getId(), Types::INTEGER);
        }

        return null !== $queryBuilder->getQuery()->getOneOrNullResult();
    }

    private function hasQuestion(CQuiz $quiz, int $questionId): bool
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('relQuestion.iid')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->andWhere('IDENTITY(relQuestion.question) = :questionId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return null !== $result;
    }

    private function getNextQuestionOrder(CQuiz $quiz): int
    {
        $maxOrder = $this->entityManager->createQueryBuilder()
            ->select('MAX(relQuestion.questionOrder)')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return max(1, (int) $maxOrder + 1);
    }

    private function isQuestionTypeAllowedByFeedback(int $type, int $feedbackType): bool
    {
        if (1 === $feedbackType) {
            return !\in_array($type, [5, 13, 20, 22, 23, 31], true);
        }

        if (3 === $feedbackType) {
            return \in_array($type, [1, 2, 16, 18], true);
        }

        return true;
    }

    /**
     * @return array<int, CQuizAnswer>
     */
    private function getAnswers(CQuizQuestion $question): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('answer')
            ->from(CQuizAnswer::class, 'answer')
            ->andWhere('IDENTITY(answer.question) = :questionId')
            ->setParameter('questionId', (int) $question->getIid(), Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;
    }

    private function canRunRestrictedAction(): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return !$this->isSettingEnabled('exercise.limit_exercise_teacher_access');
    }

    private function isSettingEnabled(string $name): bool
    {
        return 'true' === $this->settingsManager->getSetting($name, true);
    }

    private function isQuestionUsedInActiveQuiz(CQuizQuestion $question, Course $course, ?Session $session): bool
    {
        $questionId = (int) ($question->getIid() ?? 0);
        if (0 >= $questionId) {
            return false;
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('relQuestion.iid')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.quiz', 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('IDENTITY(relQuestion.question) = :questionId')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        $this->applySessionFilter($queryBuilder, 'links', $session, true);
        if (null !== $session) {
            $queryBuilder->setParameter('sessionId', (int) $session->getId(), Types::INTEGER);
        }

        return null !== $queryBuilder->getQuery()->getOneOrNullResult();
    }

    private function applyActiveLinkConstraints(QueryBuilder $queryBuilder, string $alias, ?Session $session, bool $includeCourseWhenSessionSelected): void
    {
        $queryBuilder
            ->andWhere($alias.'.deletedAt IS NULL')
            ->andWhere($alias.'.endVisibilityAt IS NULL')
            ->andWhere($alias.'.visibility IN (0,2)')
        ;

        $this->applySessionFilter($queryBuilder, $alias, $session, $includeCourseWhenSessionSelected);
    }

    private function applySessionFilter(QueryBuilder $queryBuilder, string $alias, ?Session $session, bool $includeCourseWhenSessionSelected = false): void
    {
        if (null !== $session) {
            if ($includeCourseWhenSessionSelected) {
                $queryBuilder->andWhere('(IDENTITY('.$alias.'.session) = :sessionId OR '.$alias.'.session IS NULL)');

                return;
            }

            $queryBuilder->andWhere('IDENTITY('.$alias.'.session) = :sessionId');

            return;
        }

        $queryBuilder->andWhere($alias.'.session IS NULL');
    }
}
