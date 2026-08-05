<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Exercise;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizAnswer;
use Chamilo\CourseBundle\Entity\CQuizCategory;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use Question;

use const DATE_ATOM;

/**
 * Shared read/normalize mechanics for the read-only MCP tools covering
 * tests (exercises), their questions and proposed answers. Scoped to the
 * base course only (no session), matching every other MCP content tool.
 */
final readonly class CourseTestReaderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return list<CQuiz>
     */
    public function listQuizzes(Course $course): array
    {
        /** @var list<CQuiz> $result */
        return $this->baseQuizQueryBuilder($course)
            ->addOrderBy('quiz.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function resolveQuiz(Course $course, ?int $testId, ?string $testTitle): CQuiz
    {
        $testId = (null !== $testId && $testId > 0) ? $testId : null;
        $testTitle = null !== $testTitle ? trim($testTitle) : '';
        if (null === $testId && '' === $testTitle) {
            throw new InvalidArgumentException('Provide either testId or testTitle.');
        }

        $queryBuilder = $this->baseQuizQueryBuilder($course);

        if (null !== $testId) {
            $queryBuilder
                ->andWhere('quiz.iid = :testId')
                ->setParameter('testId', $testId, Types::INTEGER)
            ;
        } else {
            $queryBuilder
                ->andWhere('quiz.title = :testTitle')
                ->setParameter('testTitle', $testTitle, Types::STRING)
            ;
        }

        /** @var list<CQuiz> $matches */
        $matches = $queryBuilder->getQuery()->getResult();
        if ([] === $matches) {
            throw new InvalidArgumentException('The test was not found in this course.');
        }
        if (\count($matches) > 1) {
            throw new InvalidArgumentException('More than one test has this title. Provide testId to disambiguate.');
        }

        return $matches[0];
    }

    /**
     * @return list<CQuizRelQuestion>
     */
    public function listQuestionLinks(CQuiz $quiz): array
    {
        /** @var list<CQuizRelQuestion> $result */
        return $this->entityManager->createQueryBuilder()
            ->select('rel', 'question')
            ->from(CQuizRelQuestion::class, 'rel')
            ->innerJoin('rel.question', 'question')
            ->andWhere('IDENTITY(rel.quiz) = :quizId')
            ->setParameter('quizId', (int) $quiz->getIid(), Types::INTEGER)
            ->orderBy('rel.questionOrder', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Resolves a question that belongs to the given test, along with its
     * normalized 1-based position (matching the ordinal listQuestionLinks()
     * already exposes to get_course_test_questions).
     *
     * @return array{question: CQuizQuestion, position: int}
     */
    public function resolveQuestionWithPosition(CQuiz $quiz, int $questionId): array
    {
        if ($questionId <= 0) {
            throw new InvalidArgumentException('The question ID must be a positive integer.');
        }

        foreach ($this->listQuestionLinks($quiz) as $index => $rel) {
            if ($questionId === (int) $rel->getQuestion()->getIid()) {
                return ['question' => $rel->getQuestion(), 'position' => $index + 1];
            }
        }

        throw new InvalidArgumentException('The question was not found in this test.');
    }

    /**
     * @return list<CQuizAnswer>
     */
    public function listAnswers(CQuizQuestion $question): array
    {
        /** @var list<CQuizAnswer> $result */
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

    /**
     * @return array<string, mixed>
     */
    public function normalizeTest(CQuiz $quiz, Course $course): array
    {
        $resourceLink = $quiz->getResourceNode()?->getResourceLinkByContext($course, null, null);
        $quizCategory = $quiz->getQuizCategory();
        $duration = $quiz->getDuration();

        return [
            'quiz_id' => (int) $quiz->getIid(),
            'title' => $quiz->getTitle(),
            'description' => (string) $quiz->getDescription(),
            'display_mode' => CQuiz::ONE_PER_PAGE === $quiz->getType() ? 'one_per_page' : 'all_on_one_page',
            'random_answers' => $quiz->getRandomAnswers(),
            'max_attempts' => $quiz->getMaxAttempt(),
            'duration_minutes' => null !== $duration ? (int) round($duration / 60) : 0,
            'start_at' => $quiz->getStartTime()?->format(DATE_ATOM),
            'end_at' => $quiz->getEndTime()?->format(DATE_ATOM),
            'pass_percentage' => $quiz->getPassPercentage(),
            'feedback_type' => $quiz->getFeedbackType(),
            'results_disabled' => $quiz->getResultsDisabled(),
            'review_answers' => (bool) $quiz->getReviewAnswers(),
            'quiz_category' => $quizCategory instanceof CQuizCategory
                ? ['category_id' => $quizCategory->getId(), 'title' => $quizCategory->getTitle()]
                : null,
            'question_count' => \count($quiz->getQuestions()),
            'total_score' => $quiz->getMaxScore(),
            'visibility' => $resourceLink?->getVisibility(),
            'published' => $resourceLink instanceof ResourceLink
                && ResourceLink::VISIBILITY_PUBLISHED === $resourceLink->getVisibility(),
            'content_url' => '/resources/exercise/'.$quiz->getResourceNode()?->getId().'/'.$quiz->getIid().'/overview?cid='.(int) $course->getId(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeQuestion(CQuizQuestion $question, int $position): array
    {
        $category = $this->firstCategory($question);

        return [
            'question_id' => (int) $question->getIid(),
            'position' => $position,
            'text' => $question->getQuestion(),
            'description' => (string) $question->getDescription(),
            'type' => $question->getType(),
            'type_label' => $this->questionTypeLabel($question->getType()),
            'total_score' => (float) $question->getPonderation(),
            'category' => $category instanceof CQuizQuestionCategory
                ? ['category_id' => $category->getIid(), 'title' => $category->getTitle()]
                : null,
            'mandatory' => (bool) $question->getMandatory(),
            'answer_count' => \count($question->getAnswers()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function normalizeAnswer(CQuizAnswer $answer): array
    {
        $correct = $answer->getCorrect();

        return [
            'answer_id' => (int) $answer->getIid(),
            'position' => $answer->getPosition(),
            'text' => $answer->getAnswer(),
            'correct' => $correct,
            'is_correct' => null !== $correct && $correct > 0,
            'feedback' => (string) $answer->getComment(),
            'score' => $answer->getPonderation(),
        ];
    }

    public function questionTypeLabel(int $type): string
    {
        if (class_exists(Question::class) && isset(Question::$questionTypes[$type][2])) {
            return (string) Question::$questionTypes[$type][2];
        }

        return (string) $type;
    }

    private function firstCategory(CQuizQuestion $question): ?CQuizQuestionCategory
    {
        foreach ($question->getCategories() as $category) {
            return $category instanceof CQuizQuestionCategory ? $category : null;
        }

        return null;
    }

    private function baseQuizQueryBuilder(Course $course): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->select('quiz')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'resourceLink')
            ->andWhere('IDENTITY(resourceLink.course) = :courseId')
            ->andWhere('resourceLink.session IS NULL')
            ->andWhere('resourceLink.group IS NULL')
            ->andWhere('resourceLink.userGroup IS NULL')
            ->andWhere('resourceLink.user IS NULL')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;
    }
}
