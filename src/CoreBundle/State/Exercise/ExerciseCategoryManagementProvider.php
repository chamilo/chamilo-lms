<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseCategoryManagement;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizCategory;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @implements ProviderInterface<ExerciseCategoryManagement>
 */
final readonly class ExerciseCategoryManagementProvider implements ProviderInterface
{
    public const TYPE_EXERCISE = 'exercise';
    public const TYPE_QUESTION = 'question';

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
        private IsAllowedToEditHelper $isAllowedToEditHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ExerciseCategoryManagement
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->isAllowedToEditHelper->check(coach: true)) {
            throw new AccessDeniedHttpException('You are not allowed to manage exercise categories in this context.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $categoryType = $this->getCategoryType($uriVariables);

        if (self::TYPE_EXERCISE === $categoryType && !$this->isSettingEnabled('exercise.allow_exercise_categories')) {
            throw new AccessDeniedHttpException('Exercise categories are disabled on this platform.');
        }

        $response = new ExerciseCategoryManagement();
        $response->id = 'exercise_categories_'.$categoryType;
        $response->categoryType = $categoryType;
        $response->title = self::TYPE_EXERCISE === $categoryType ? 'Exercise categories' : 'Question categories';
        $response->items = self::TYPE_EXERCISE === $categoryType
            ? $this->getExerciseCategories($course)
            : $this->getQuestionCategories($course, $session);
        $response->canManage = true;

        return $response;
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function getCategoryType(array $uriVariables): string
    {
        $categoryType = (string) ($uriVariables['categoryType'] ?? '');
        if (!\in_array($categoryType, [self::TYPE_EXERCISE, self::TYPE_QUESTION], true)) {
            throw new BadRequestHttpException('Unsupported category type.');
        }

        return $categoryType;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getExerciseCategories(Course $course): array
    {
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

        $categoryIds = [];
        foreach ($categories as $category) {
            if ($category instanceof CQuizCategory && null !== $category->getId()) {
                $categoryIds[] = (int) $category->getId();
            }
        }

        $usageCounts = $this->getExerciseCategoryUsageCounts($categoryIds);
        $items = [];
        foreach ($categories as $category) {
            if (!$category instanceof CQuizCategory || null === $category->getId()) {
                continue;
            }

            $categoryId = (int) $category->getId();
            $items[] = [
                'id' => $categoryId,
                'title' => $category->getTitle(),
                'description' => $category->getDescription(),
                'usageCount' => $usageCounts[$categoryId] ?? 0,
            ];
        }

        return $items;
    }

    /**
     * @param array<int, int> $categoryIds
     *
     * @return array<int, int>
     */
    private function getExerciseCategoryUsageCounts(array $categoryIds): array
    {
        if ([] === $categoryIds) {
            return [];
        }

        $rows = $this->entityManager->createQueryBuilder()
            ->select('IDENTITY(quiz.quizCategory) AS categoryId')
            ->addSelect('COUNT(quiz.iid) AS usageCount')
            ->from(CQuiz::class, 'quiz')
            ->andWhere('IDENTITY(quiz.quizCategory) IN (:categoryIds)')
            ->setParameter('categoryIds', $categoryIds, ArrayParameterType::INTEGER)
            ->groupBy('quiz.quizCategory')
            ->getQuery()
            ->getArrayResult()
        ;

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['categoryId']] = (int) $row['usageCount'];
        }

        return $counts;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getQuestionCategories(Course $course, ?Session $session): array
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('category')
            ->from(CQuizQuestionCategory::class, 'category')
            ->innerJoin('category.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->orderBy('category.title', 'ASC')
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('(IDENTITY(links.session) = :sessionId OR links.session IS NULL)')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        $items = [];
        foreach ($queryBuilder->getQuery()->getResult() as $category) {
            if (!$category instanceof CQuizQuestionCategory || null === $category->getIid()) {
                continue;
            }

            $items[] = [
                'id' => (int) $category->getIid(),
                'title' => $category->getTitle(),
                'description' => (string) $category->getDescription(),
                'usageCount' => $category->getQuestions()->count(),
            ];
        }

        return $items;
    }

    private function isSettingEnabled(string $name): bool
    {
        return 'true' === $this->settingsManager->getSetting($name, true);
    }
}
