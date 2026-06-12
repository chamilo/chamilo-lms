<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseList;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\TrackEExercise;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizCategory;
use Chamilo\CourseBundle\Entity\CQuizRelQuestion;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<ExerciseList>
 */
final readonly class ExerciseListProvider implements ProviderInterface
{
    private const VISIBILITY_PUBLISHED = 2;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseList
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $canManage = $this->canManageExercises();
        $canCreate = $canManage;

        if (!$canManage && !$this->canViewExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to view exercises in this context.');
        }

        $items = $this->getItems($request, $course, $session, $canManage);

        $response = new ExerciseList();
        $response->items = $items;
        $response->categories = $this->getCategories($course);
        $response->settings = $this->getSettings();
        $response->totalItems = \count($items);
        $response->canManage = $canManage;
        $response->canCreate = $canCreate;

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

    private function canViewExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_STUDENT')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_STUDENT')
            || $this->canManageExercises();
    }

    private function canManageExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getItems(Request $request, Course $course, ?Session $session, bool $canManage): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz')
            ->addSelect('category')
            ->addSelect('links.visibility AS linkVisibility')
            ->from(CQuiz::class, 'quiz')
            ->leftJoin('quiz.quizCategory', 'category')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('quiz.title', 'ASC')
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(links.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        $search = trim((string) $request->query->get('search', ''));
        if ('' !== $search) {
            $queryBuilder
                ->andWhere('LOWER(quiz.title) LIKE :search OR LOWER(quiz.description) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%', Types::STRING)
            ;
        }

        $categoryId = $request->query->getInt('categoryId');
        if (0 < $categoryId) {
            $queryBuilder
                ->andWhere('IDENTITY(quiz.quizCategory) = :categoryId')
                ->setParameter('categoryId', $categoryId, Types::INTEGER)
            ;
        }

        if (!$canManage) {
            $now = new DateTimeImmutable();
            $queryBuilder
                ->andWhere('links.visibility = :visibility')
                ->andWhere('quiz.startTime IS NULL OR quiz.startTime <= :now')
                ->andWhere('quiz.endTime IS NULL OR quiz.endTime >= :now')
                ->setParameter('visibility', self::VISIBILITY_PUBLISHED, Types::INTEGER)
                ->setParameter('now', $now, Types::DATETIME_IMMUTABLE)
            ;
        }

        $rows = $queryBuilder->getQuery()->getResult();
        $quizzes = [];
        $linkVisibilityByQuiz = [];

        foreach ($rows as $row) {
            $quiz = null;
            $linkVisibility = self::VISIBILITY_PUBLISHED;

            if ($row instanceof CQuiz) {
                $quiz = $row;
            } elseif (\is_array($row)) {
                $candidate = $row[0] ?? $row['quiz'] ?? null;
                if ($candidate instanceof CQuiz) {
                    $quiz = $candidate;
                }

                if (isset($row['linkVisibility'])) {
                    $linkVisibility = (int) $row['linkVisibility'];
                }
            }

            if (!$quiz instanceof CQuiz || null === $quiz->getIid()) {
                continue;
            }

            $quizId = (int) $quiz->getIid();
            $quizzes[$quizId] = $quiz;
            $linkVisibilityByQuiz[$quizId] = $linkVisibility;
        }

        $quizIds = array_keys($quizzes);
        $questionCounts = $this->getQuestionCounts($quizIds);
        $attemptCounts = $this->getAttemptCounts($quizIds, $course, $session);
        $items = [];

        foreach ($quizzes as $quizId => $quiz) {
            $visible = self::VISIBILITY_PUBLISHED === ($linkVisibilityByQuiz[$quizId] ?? self::VISIBILITY_PUBLISHED);
            $items[] = $this->normalizeQuiz(
                $quiz,
                $visible,
                $questionCounts[$quizId] ?? 0,
                $attemptCounts[$quizId] ?? 0,
                $canManage,
            );
        }

        return $items;
    }

    /**
     * @param array<int, int> $quizIds
     *
     * @return array<int, int>
     */
    private function getQuestionCounts(array $quizIds): array
    {
        if ([] === $quizIds) {
            return [];
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(relQuestion.quiz) AS quizId')
            ->addSelect('COUNT(relQuestion.iid) AS questionCount')
            ->from(CQuizRelQuestion::class, 'relQuestion')
            ->andWhere('IDENTITY(relQuestion.quiz) IN (:quizIds)')
            ->setParameter('quizIds', $quizIds, ArrayParameterType::INTEGER)
            ->groupBy('relQuestion.quiz')
            ->getQuery()
            ->getArrayResult()
        ;

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['quizId']] = (int) $row['questionCount'];
        }

        return $counts;
    }

    /**
     * @param array<int, int> $quizIds
     *
     * @return array<int, int>
     */
    private function getAttemptCounts(array $quizIds, Course $course, ?Session $session): array
    {
        if ([] === $quizIds) {
            return [];
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(attempt.quiz) AS quizId')
            ->addSelect('COUNT(attempt.exeId) AS attemptCount')
            ->from(TrackEExercise::class, 'attempt')
            ->andWhere('IDENTITY(attempt.quiz) IN (:quizIds)')
            ->andWhere('IDENTITY(attempt.course) = :courseId')
            ->setParameter('quizIds', $quizIds, ArrayParameterType::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->groupBy('attempt.quiz')
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('IDENTITY(attempt.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('attempt.session IS NULL');
        }

        $rows = $queryBuilder->getQuery()->getArrayResult();
        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['quizId']] = (int) $row['attemptCount'];
        }

        return $counts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCategories(Course $course): array
    {
        if (!$this->isSettingEnabled('exercise.allow_exercise_categories')) {
            return [];
        }

        $categories = $this->entityManager->createQueryBuilder()
            ->select('category')
            ->from(CQuizCategory::class, 'category')
            ->andWhere('IDENTITY(category.course) = :courseId')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('category.position', 'ASC')
            ->addOrderBy('category.title', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $items = [];
        foreach ($categories as $category) {
            if (!$category instanceof CQuizCategory || null === $category->getId()) {
                continue;
            }

            $items[] = [
                'id' => (int) $category->getId(),
                'title' => $category->getTitle(),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        return [
            'allowExerciseCategories' => $this->isSettingEnabled('exercise.allow_exercise_categories'),
            'disableCleanResultsForTeachers' => $this->isSettingEnabled('exercise.disable_clean_exercise_results_for_teachers'),
            'disableNewAttempts' => $this->isSettingEnabled('exercise.exercises_disable_new_attempts'),
            'hideAttemptsTableOnStartPage' => $this->isSettingEnabled('exercise.quiz_hide_attempts_table_on_start_page'),
            'limitTeacherAccess' => $this->isSettingEnabled('exercise.limit_exercise_teacher_access'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeQuiz(
        CQuiz $quiz,
        bool $visible,
        int $questionCount,
        int $attemptCount,
        bool $canManage,
    ): array {
        $category = $quiz->getQuizCategory();
        $startTime = $quiz->getStartTime();
        $endTime = $quiz->getEndTime();
        $availabilityStatus = $this->getAvailabilityStatus($startTime, $endTime);

        return [
            'iid' => (int) $quiz->getIid(),
            'title' => $quiz->getTitle(),
            'description' => (string) $quiz->getDescription(),
            'categoryId' => null !== $category && null !== $category->getId() ? (int) $category->getId() : 0,
            'categoryTitle' => null !== $category ? $category->getTitle() : '',
            'visible' => $visible,
            'availabilityStatus' => $availabilityStatus,
            'startTime' => $this->formatDate($startTime),
            'endTime' => $this->formatDate($endTime),
            'duration' => $quiz->getDuration(),
            'maxAttempt' => $quiz->getMaxAttempt(),
            'passPercentage' => $quiz->getPassPercentage(),
            'questionCount' => $questionCount,
            'attemptCount' => $attemptCount,
            'canOpen' => $visible || $canManage,
            'canEdit' => $canManage,
            'canConfigure' => $canManage,
            'canReport' => $canManage,
            'canExport' => $canManage,
        ];
    }

    private function getAvailabilityStatus(?DateTimeInterface $startTime, ?DateTimeInterface $endTime): string
    {
        $now = new DateTimeImmutable();

        if (null !== $startTime && $startTime > $now) {
            return 'not_started';
        }

        if (null !== $endTime && $endTime < $now) {
            return 'closed';
        }

        return 'open';
    }

    private function formatDate(?DateTimeInterface $date): ?string
    {
        if (null === $date) {
            return null;
        }

        return $date->format(DateTimeInterface::ATOM);
    }

    private function isSettingEnabled(string $name): bool
    {
        return 'true' === $this->settingsManager->getSetting($name);
    }
}
