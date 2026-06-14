<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseCategoryManagement;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CQuizCategory;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<ExerciseCategoryManagement>
 */
final readonly class ExerciseCategoryManagementProvider implements ProviderInterface
{
    public const TYPE_EXERCISE = 'exercise';
    public const TYPE_QUESTION = 'question';
    public const CSRF_TOKEN_ID = 'exercise_category_management';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
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

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to manage exercise categories in this context.');
        }

        $course = $this->getCourse($request);
        $session = $this->getSession($request);
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
        $response->legacyUrls = $this->getLegacyUrls($categoryType, $request);
        $response->csrfToken = $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID)->getValue();
        $response->canManage = true;

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

    private function canManageExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
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
                ->andWhere('IDENTITY(links.session) = :sessionId')
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

    /**
     * @return array<string, string>
     */
    private function getLegacyUrls(string $categoryType, Request $request): array
    {
        $baseParams = [
            'cid' => $request->query->getInt('cid'),
            'sid' => $request->query->getInt('sid'),
            'gid' => $request->query->getInt('gid'),
        ];
        $queryString = http_build_query(array_filter($baseParams, static fn (int $value): bool => 0 < $value));

        if (self::TYPE_QUESTION === $categoryType) {
            return [
                'export' => '/main/exercise/tests_category.php?action=export_category&'.$queryString,
                'import' => '/main/exercise/tests_category.php?action=import_category&'.$queryString,
            ];
        }

        return [
            'legacy' => '/main/exercise/category.php?'.$queryString,
        ];
    }

    private function isSettingEnabled(string $name): bool
    {
        return 'true' === $this->settingsManager->getSetting($name, true);
    }
}
