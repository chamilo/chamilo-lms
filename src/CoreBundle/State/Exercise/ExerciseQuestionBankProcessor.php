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
use Chamilo\CourseBundle\Entity\CQuiz;
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

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private Security $security,
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
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $action = strtolower(trim($data->action));
        if (self::ACTION_REUSE !== $action) {
            throw new BadRequestHttpException('Unsupported question bank action.');
        }

        [$addedCount, $skippedCount] = $this->reuseQuestions($quiz, $data, $course, $session);
        $this->entityManager->flush();

        $response = new ExerciseQuestionBank();
        $response->exerciseId = $exerciseId;
        $response->action = $action;
        $response->questionId = $data->questionId;
        $response->questionIds = $data->questionIds;
        $response->success = true;
        $response->addedCount = $addedCount;
        $response->skippedCount = $skippedCount;
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
        if (!$quiz instanceof CQuiz) {
            throw new NotFoundHttpException('The requested exercise was not found.');
        }

        if ($this->isExerciseInContext($exerciseId, $course, $session)) {
            return $quiz;
        }

        throw new AccessDeniedHttpException('The requested exercise does not belong to the current course context.');
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
    private function reuseQuestions(CQuiz $quiz, ExerciseQuestionBank $data, Course $course, ?Session $session): array
    {
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
