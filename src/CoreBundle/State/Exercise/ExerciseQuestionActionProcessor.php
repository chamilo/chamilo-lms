<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseQuestionAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizQuestionOption;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Chamilo\CourseBundle\Repository\CQuizQuestionRepository;
use Chamilo\CourseBundle\Repository\CQuizRepository;
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
 * @implements ProcessorInterface<ExerciseQuestionAction, ExerciseQuestionAction>
 */
final readonly class ExerciseQuestionActionProcessor implements ProcessorInterface
{
    private const CSRF_TOKEN_ID = 'exercise_question_action';
    private const ACTION_DELETE = 'delete';
    private const ACTION_DUPLICATE = 'duplicate';
    private const ACTION_REORDER = 'reorder';
    private const MATCHING = 4;
    private const MATCHING_DRAGGABLE = 19;
    private const MATCHING_DRAGGABLE_COMBINATION = 25;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CQuizRepository $quizRepository,
        private CQuizQuestionRepository $questionRepository,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseQuestionAction
    {
        if (!$data instanceof ExerciseQuestionAction) {
            throw new BadRequestHttpException('Invalid exercise question action payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to manage exercise questions in this context.');
        }

        $exerciseId = isset($uriVariables['exerciseId']) ? (int) $uriVariables['exerciseId'] : (int) ($data->exerciseId ?? 0);
        if (0 >= $exerciseId) {
            throw new BadRequestHttpException('A valid exercise id is required.');
        }

        $quiz = $this->getExerciseFromCurrentContext($exerciseId, $course, $session);
        $action = strtolower(trim($data->action));

        $message = match ($action) {
            self::ACTION_DELETE => $this->deleteQuestion($quiz, (int) $data->questionId),
            self::ACTION_DUPLICATE => $this->duplicateQuestion($quiz, (int) $data->questionId, $course, $session),
            self::ACTION_REORDER => $this->reorderQuestions($quiz, $data->questionIds),
            default => throw new BadRequestHttpException('Unsupported exercise question action.'),
        };

        $this->entityManager->flush();

        $response = new ExerciseQuestionAction();
        $response->exerciseId = $exerciseId;
        $response->action = $action;
        $response->questionId = $data->questionId;
        $response->questionIds = $data->questionIds;
        $response->success = true;
        $response->message = $message;

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

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(links.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        return null !== $queryBuilder->getQuery()->getOneOrNullResult();
    }

    private function getQuestionRelation(CQuiz $quiz, int $questionId): CQuizRelQuestion
    {
        if (0 >= $questionId) {
            throw new BadRequestHttpException('A valid question id is required.');
        }

        $relation = $this->entityManager->createQueryBuilder()
            ->select('relQuestion', 'question')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->andWhere('IDENTITY(relQuestion.question) = :questionId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->setParameter('questionId', $questionId, Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$relation instanceof CQuizRelQuestion) {
            throw new NotFoundHttpException('The requested question was not found in this exercise.');
        }

        return $relation;
    }

    private function deleteQuestion(CQuiz $quiz, int $questionId): string
    {
        $relation = $this->getQuestionRelation($quiz, $questionId);
        $question = $relation->getQuestion();
        $relationCount = $this->countQuestionRelations($question);

        $this->entityManager->remove($relation);

        if (1 >= $relationCount) {
            foreach ($this->getAnswers($question) as $answer) {
                $this->entityManager->remove($answer);
            }
            $this->entityManager->remove($question);
        }

        $this->normalizeQuestionOrder($quiz);

        return 'Question deleted';
    }

    private function duplicateQuestion(CQuiz $quiz, int $questionId, Course $course, ?Session $session): string
    {
        $relation = $this->getQuestionRelation($quiz, $questionId);
        $sourceQuestion = $relation->getQuestion();
        $nextOrder = $this->getNextQuestionOrder($quiz);

        $newQuestion = new CQuizQuestion();
        $newQuestion
            ->setQuestion(trim($sourceQuestion->getQuestion()).' - Copy')
            ->setDescription($sourceQuestion->getDescription())
            ->setFeedback($sourceQuestion->getFeedback())
            ->setType((int) $sourceQuestion->getType())
            ->setLevel(max(1, (int) $sourceQuestion->getLevel()))
            ->setPosition($nextOrder)
            ->setPonderation((float) $sourceQuestion->getPonderation())
            ->setMandatory((int) $sourceQuestion->getMandatory())
            ->setDuration($sourceQuestion->getDuration())
            ->setParentMediaId($sourceQuestion->getParentMediaId())
            ->setExtra($sourceQuestion->getExtra())
            ->setParent($course)
            ->addCourseLink($course, $session)
        ;

        foreach ($sourceQuestion->getCategories() as $category) {
            if ($category instanceof CQuizQuestionCategory) {
                $newQuestion->addCategory($category);
            }
        }

        $this->questionRepository->create($newQuestion);

        if ($this->isMatchingQuestion((int) $sourceQuestion->getType())) {
            $this->copyMatchingAnswers($sourceQuestion, $newQuestion);
        } else {
            $optionIidMap = $this->copyQuestionOptions($sourceQuestion, $newQuestion);

            foreach ($this->getAnswers($sourceQuestion) as $sourceAnswer) {
                $sourceCorrect = (int) $sourceAnswer->getCorrect();
                $answer = new CQuizAnswer();
                $answer
                    ->setQuestion($newQuestion)
                    ->setAnswer($sourceAnswer->getAnswer())
                    ->setCorrect((int) ($optionIidMap[$sourceCorrect] ?? $sourceCorrect))
                    ->setComment((string) $sourceAnswer->getComment())
                    ->setPonderation((float) $sourceAnswer->getPonderation())
                    ->setPosition((int) $sourceAnswer->getPosition())
                ;

                if (null !== $sourceAnswer->getHotspotCoordinates()) {
                    $answer->setHotspotCoordinates($sourceAnswer->getHotspotCoordinates());
                }

                if (null !== $sourceAnswer->getHotspotType()) {
                    $answer->setHotspotType($sourceAnswer->getHotspotType());
                }

                if (null !== $sourceAnswer->getAnswerCode()) {
                    $answer->setAnswerCode($sourceAnswer->getAnswerCode());
                }

                $this->entityManager->persist($answer);
            }
        }

        $newRelation = new CQuizRelQuestion();
        $newRelation
            ->setQuiz($quiz)
            ->setQuestion($newQuestion)
            ->setQuestionOrder($nextOrder)
        ;
        $this->entityManager->persist($newRelation);

        return 'Question copied';
    }

    private function isMatchingQuestion(int $type): bool
    {
        return \in_array($type, [self::MATCHING, self::MATCHING_DRAGGABLE, self::MATCHING_DRAGGABLE_COMBINATION], true);
    }

    private function copyMatchingAnswers(CQuizQuestion $sourceQuestion, CQuizQuestion $newQuestion): void
    {
        $sourceAnswers = $this->getAnswers($sourceQuestion);
        $optionIidMap = [];

        foreach ($sourceAnswers as $sourceAnswer) {
            if (0 < (int) $sourceAnswer->getCorrect()) {
                continue;
            }

            $newOption = $this->cloneAnswer($sourceAnswer, $newQuestion, 0);
            $this->entityManager->persist($newOption);
            $this->entityManager->flush();

            if (null !== $sourceAnswer->getIid() && null !== $newOption->getIid()) {
                $optionIidMap[(int) $sourceAnswer->getIid()] = (int) $newOption->getIid();
            }
        }

        foreach ($sourceAnswers as $sourceAnswer) {
            $sourceCorrect = (int) $sourceAnswer->getCorrect();
            if (0 >= $sourceCorrect) {
                continue;
            }

            $mappedCorrect = (int) ($optionIidMap[$sourceCorrect] ?? 0);
            $newPair = $this->cloneAnswer($sourceAnswer, $newQuestion, $mappedCorrect);
            $this->entityManager->persist($newPair);
        }
    }

    private function cloneAnswer(CQuizAnswer $sourceAnswer, CQuizQuestion $newQuestion, int $correct): CQuizAnswer
    {
        $answer = new CQuizAnswer();
        $answer
            ->setQuestion($newQuestion)
            ->setAnswer($sourceAnswer->getAnswer())
            ->setCorrect($correct)
            ->setComment((string) $sourceAnswer->getComment())
            ->setPonderation((float) $sourceAnswer->getPonderation())
            ->setPosition((int) $sourceAnswer->getPosition())
        ;

        if (null !== $sourceAnswer->getHotspotCoordinates()) {
            $answer->setHotspotCoordinates($sourceAnswer->getHotspotCoordinates());
        }

        if (null !== $sourceAnswer->getHotspotType()) {
            $answer->setHotspotType($sourceAnswer->getHotspotType());
        }

        if (null !== $sourceAnswer->getAnswerCode()) {
            $answer->setAnswerCode($sourceAnswer->getAnswerCode());
        }

        return $answer;
    }

    /**
     * @return array<int, int>
     */
    private function copyQuestionOptions(CQuizQuestion $sourceQuestion, CQuizQuestion $newQuestion): array
    {
        $optionIidMap = [];
        foreach ($sourceQuestion->getOptions() as $sourceOption) {
            if (!$sourceOption instanceof CQuizQuestionOption) {
                continue;
            }

            $newOption = new CQuizQuestionOption();
            $newOption
                ->setQuestion($newQuestion)
                ->setTitle((string) $sourceOption->getTitle())
                ->setPosition((int) $sourceOption->getPosition())
            ;
            $this->entityManager->persist($newOption);
            $this->entityManager->flush();

            if (null !== $sourceOption->getIid() && null !== $newOption->getIid()) {
                $optionIidMap[(int) $sourceOption->getIid()] = (int) $newOption->getIid();
            }
        }

        return $optionIidMap;
    }

    /**
     * @param array<int, mixed> $questionIds
     */
    private function reorderQuestions(CQuiz $quiz, array $questionIds): string
    {
        $orderedQuestionIds = array_values(array_unique(array_map('intval', $questionIds)));
        if (empty($orderedQuestionIds)) {
            throw new BadRequestHttpException('Question order is required.');
        }

        $relations = $this->getQuestionRelations($quiz);
        $relationsByQuestionId = [];
        foreach ($relations as $relation) {
            $relationsByQuestionId[(int) $relation->getQuestion()->getIid()] = $relation;
        }

        $position = 1;
        foreach ($orderedQuestionIds as $questionId) {
            if (!isset($relationsByQuestionId[$questionId])) {
                throw new BadRequestHttpException('Question order contains a question outside this exercise.');
            }

            $relationsByQuestionId[$questionId]->setQuestionOrder($position);
            $relationsByQuestionId[$questionId]->getQuestion()->setPosition($position);
            unset($relationsByQuestionId[$questionId]);
            ++$position;
        }

        foreach ($relationsByQuestionId as $relation) {
            $relation->setQuestionOrder($position);
            $relation->getQuestion()->setPosition($position);
            ++$position;
        }

        return 'Question order updated';
    }

    /**
     * @return array<int, CQuizRelQuestion>
     */
    private function getQuestionRelations(CQuiz $quiz): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('relQuestion', 'question')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->innerJoin('relQuestion.question', 'question')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->orderBy('relQuestion.questionOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    private function normalizeQuestionOrder(CQuiz $quiz): void
    {
        $position = 1;
        foreach ($this->getQuestionRelations($quiz) as $relation) {
            $relation->setQuestionOrder($position);
            $relation->getQuestion()->setPosition($position);
            ++$position;
        }
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
            ->orderBy('answer.position', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    private function countQuestionRelations(CQuizQuestion $question): int
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(relQuestion.iid)')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->andWhere('IDENTITY(relQuestion.question) = :questionId')
            ->setParameter('questionId', (int) $question->getIid(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    private function getNextQuestionOrder(CQuiz $quiz): int
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('MAX(relQuestion.questionOrder)')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->andWhere('IDENTITY(relQuestion.quiz) = :exerciseId')
            ->setParameter('exerciseId', (int) $quiz->getIid(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result + 1;
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
    }
}
