<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Exercise;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Exercise\ExerciseCategoryManagement;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CQuizCategory;
use Chamilo\CourseBundle\Entity\CQuizQuestionCategory;
use Chamilo\CourseBundle\Repository\CQuizQuestionCategoryRepository;
use Doctrine\DBAL\ParameterType;
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
 * @implements ProcessorInterface<ExerciseCategoryManagement, ExerciseCategoryManagement>
 */
final readonly class ExerciseCategoryManagementProcessor implements ProcessorInterface
{
    private const ACTION_CREATE = 'create';
    private const ACTION_UPDATE = 'update';
    private const ACTION_DELETE = 'delete';
    private const ACTION_IMPORT_CSV = 'import_csv';

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private CQuizQuestionCategoryRepository $questionCategoryRepository,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ExerciseCategoryManagement
    {
        if (!$data instanceof ExerciseCategoryManagement) {
            throw new BadRequestHttpException('Invalid exercise category action payload.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        if (!$this->canManageExercises()) {
            throw new AccessDeniedHttpException('You are not allowed to manage exercise categories in this context.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);
        $course = $this->getCourse($request);
        $session = $this->getSession($request);
        $categoryType = $this->getCategoryType($uriVariables);

        if (ExerciseCategoryManagementProvider::TYPE_EXERCISE === $categoryType && !$this->isSettingEnabled('exercise.allow_exercise_categories')) {
            throw new AccessDeniedHttpException('Exercise categories are disabled on this platform.');
        }

        $action = strtolower(trim($data->action));
        $message = match ($action) {
            self::ACTION_CREATE => $this->createCategory($categoryType, $data, $course, $session),
            self::ACTION_UPDATE => $this->updateCategory($categoryType, $data, $course, $session),
            self::ACTION_DELETE => $this->deleteCategory($categoryType, $data, $course, $session),
            self::ACTION_IMPORT_CSV => $this->importQuestionCategoriesFromCsv($categoryType, $data, $course, $session),
            default => throw new BadRequestHttpException('Unsupported exercise category action.'),
        };

        $this->entityManager->flush();

        $response = new ExerciseCategoryManagement();
        $response->id = 'exercise_categories_'.$categoryType;
        $response->categoryType = $categoryType;
        $response->action = $action;
        $response->categoryId = $data->categoryId;
        $response->categoryTitle = $data->categoryTitle;
        $response->importedCount = $data->importedCount;
        $response->skippedCount = $data->skippedCount;
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

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function getCategoryType(array $uriVariables): string
    {
        $categoryType = (string) ($uriVariables['categoryType'] ?? '');
        if (!\in_array($categoryType, [ExerciseCategoryManagementProvider::TYPE_EXERCISE, ExerciseCategoryManagementProvider::TYPE_QUESTION], true)) {
            throw new BadRequestHttpException('Unsupported category type.');
        }

        return $categoryType;
    }

    private function canManageExercises(): bool
    {
        return $this->security->isGranted('ROLE_CURRENT_COURSE_TEACHER')
            || $this->security->isGranted('ROLE_CURRENT_COURSE_SESSION_TEACHER');
    }

    private function validateCsrfToken(string $submittedCsrfToken): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(ExerciseCategoryManagementProvider::CSRF_TOKEN_ID, $submittedCsrfToken))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
    }

    private function createCategory(string $categoryType, ExerciseCategoryManagement $data, Course $course, ?Session $session): string
    {
        $title = trim($data->categoryTitle);
        if ('' === $title) {
            throw new BadRequestHttpException('A category title is required.');
        }

        if (ExerciseCategoryManagementProvider::TYPE_EXERCISE === $categoryType) {
            $this->assertExerciseCategoryTitleIsUnique($title, $course);
            $category = new CQuizCategory();
            $category
                ->setCourse($course)
                ->setTitle($title)
                ->setDescription($data->description)
                ->setPosition($this->getNextExerciseCategoryPosition($course))
                ->setParent($course)
                ->addCourseLink($course);
            $this->entityManager->persist($category);

            return 'Category added';
        }

        $existingCategory = $this->questionCategoryRepository->findCourseResourceByTitle($title, $course->getResourceNode(), $course);
        if (null !== $existingCategory) {
            throw new BadRequestHttpException('A category with this title already exists.');
        }

        $category = new CQuizQuestionCategory();
        $category
            ->setTitle($title)
            ->setDescription($data->description)
            ->setParent($course)
            ->addCourseLink($course, $session);
        $this->questionCategoryRepository->create($category);

        return 'Category added';
    }

    private function updateCategory(string $categoryType, ExerciseCategoryManagement $data, Course $course, ?Session $session): string
    {
        $categoryId = (int) ($data->categoryId ?? 0);
        $title = trim($data->categoryTitle);
        if (0 >= $categoryId || '' === $title) {
            throw new BadRequestHttpException('A valid category id and title are required.');
        }

        if (ExerciseCategoryManagementProvider::TYPE_EXERCISE === $categoryType) {
            $category = $this->getExerciseCategory($categoryId, $course);
            $this->assertExerciseCategoryTitleIsUnique($title, $course, $categoryId);
            $category
                ->setTitle($title)
                ->setDescription($data->description);

            return 'Category updated';
        }

        $category = $this->getQuestionCategory($categoryId, $course, $session);
        $category
            ->setTitle($title)
            ->setDescription($data->description);
        $this->questionCategoryRepository->update($category);

        return 'Category updated';
    }

    private function deleteCategory(string $categoryType, ExerciseCategoryManagement $data, Course $course, ?Session $session): string
    {
        $categoryId = (int) ($data->categoryId ?? 0);
        if (0 >= $categoryId) {
            throw new BadRequestHttpException('A valid category id is required.');
        }

        if (ExerciseCategoryManagementProvider::TYPE_EXERCISE === $categoryType) {
            $category = $this->getExerciseCategory($categoryId, $course);
            $this->entityManager->getConnection()->executeStatement(
                'UPDATE c_quiz SET quiz_category_id = NULL WHERE quiz_category_id = :categoryId',
                ['categoryId' => $categoryId],
                ['categoryId' => ParameterType::INTEGER],
            );
            $this->entityManager->remove($category);

            return 'Category deleted';
        }

        $category = $this->getQuestionCategory($categoryId, $course, $session);
        $this->entityManager->getConnection()->executeStatement(
            'DELETE FROM c_quiz_question_rel_category WHERE category_id = :categoryId',
            ['categoryId' => $categoryId],
            ['categoryId' => ParameterType::INTEGER],
        );
        $this->questionCategoryRepository->hardDelete($category);

        return 'Category deleted';
    }

    private function importQuestionCategoriesFromCsv(string $categoryType, ExerciseCategoryManagement $data, Course $course, ?Session $session): string
    {
        if (ExerciseCategoryManagementProvider::TYPE_QUESTION !== $categoryType) {
            throw new BadRequestHttpException('CSV import is only available for question categories.');
        }

        $csvContent = trim($data->csvContent);
        if ('' === $csvContent) {
            throw new BadRequestHttpException('A CSV file is required.');
        }

        $rows = $this->parseCsvRows($csvContent);
        if ([] === $rows) {
            throw new BadRequestHttpException('The CSV file is empty.');
        }

        $headerMap = $this->getCsvHeaderMap($rows[0]);
        $startsWithHeader = [] !== $headerMap;
        $importedCount = 0;
        $skippedCount = 0;

        foreach ($rows as $index => $row) {
            if ($startsWithHeader && 0 === $index) {
                continue;
            }

            [$title, $description] = $this->getCsvCategoryValues($row, $headerMap);
            if ('' === $title) {
                $skippedCount++;

                continue;
            }

            if (null !== $this->questionCategoryRepository->findCourseResourceByTitle($title, $course->getResourceNode(), $course)) {
                $skippedCount++;

                continue;
            }

            $category = new CQuizQuestionCategory();
            $category
                ->setTitle($title)
                ->setDescription($description)
                ->setParent($course)
                ->addCourseLink($course, $session);
            $this->questionCategoryRepository->create($category);
            $importedCount++;
        }

        if (0 === $importedCount && 0 === $skippedCount) {
            throw new BadRequestHttpException('No categories could be imported from this CSV file.');
        }

        $data->importedCount = $importedCount;
        $data->skippedCount = $skippedCount;

        return 'Categories imported';
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsvRows(string $csvContent): array
    {
        $delimiter = $this->detectCsvDelimiter($csvContent);
        $stream = fopen('php://temp', 'r+');
        if (false === $stream) {
            throw new BadRequestHttpException('The CSV file could not be read.');
        }

        fwrite($stream, $csvContent);
        rewind($stream);

        $rows = [];
        while (false !== ($row = fgetcsv($stream, 0, $delimiter))) {
            if ([null] === $row || [] === $row) {
                continue;
            }

            $cleanRow = [];
            foreach ($row as $value) {
                $cleanRow[] = trim((string) $value);
            }

            if ('' === implode('', $cleanRow)) {
                continue;
            }

            $rows[] = $cleanRow;
        }

        fclose($stream);

        return $rows;
    }

    private function detectCsvDelimiter(string $csvContent): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $csvContent);
        $firstLine = '';
        if (false !== $lines) {
            foreach ($lines as $line) {
                if ('' !== trim($line)) {
                    $firstLine = $line;

                    break;
                }
            }
        }

        $delimiters = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
        arsort($delimiters);
        $delimiter = (string) array_key_first($delimiters);

        return '' !== $delimiter ? $delimiter : ',';
    }

    /**
     * @param array<int, string> $row
     *
     * @return array<string, int>
     */
    private function getCsvHeaderMap(array $row): array
    {
        $headerMap = [];
        foreach ($row as $index => $value) {
            $header = mb_strtolower(trim($value));
            $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;
            if (\in_array($header, ['title', 'category', 'category_name', 'name'], true)) {
                $headerMap['title'] = $index;
            }
            if (\in_array($header, ['description', 'category_description'], true)) {
                $headerMap['description'] = $index;
            }
        }

        return isset($headerMap['title']) ? $headerMap : [];
    }

    /**
     * @param array<int, string> $row
     * @param array<string, int> $headerMap
     *
     * @return array{0: string, 1: string}
     */
    private function getCsvCategoryValues(array $row, array $headerMap): array
    {
        if ([] !== $headerMap) {
            $title = trim((string) ($row[$headerMap['title']] ?? ''));
            $descriptionIndex = $headerMap['description'] ?? null;
            $description = null !== $descriptionIndex ? trim((string) ($row[$descriptionIndex] ?? '')) : '';

            return [$title, $description];
        }

        return [trim((string) ($row[0] ?? '')), trim((string) ($row[1] ?? ''))];
    }

    private function getExerciseCategory(int $categoryId, Course $course): CQuizCategory
    {
        $category = $this->entityManager->getRepository(CQuizCategory::class)->find($categoryId);
        if (!$category instanceof CQuizCategory || (int) $category->getCourse()->getId() !== (int) $course->getId()) {
            throw new NotFoundHttpException('The requested exercise category was not found.');
        }

        return $category;
    }

    private function getQuestionCategory(int $categoryId, Course $course, ?Session $session): CQuizQuestionCategory
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('category')
            ->from(CQuizQuestionCategory::class, 'category')
            ->innerJoin('category.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('category.iid = :categoryId')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->andWhere('links.endVisibilityAt IS NULL')
            ->setParameter('categoryId', $categoryId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setMaxResults(1)
        ;

        if (null !== $session) {
            $queryBuilder
                ->andWhere('(IDENTITY(links.session) = :sessionId OR links.session IS NULL)')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        $category = $queryBuilder->getQuery()->getOneOrNullResult();
        if (!$category instanceof CQuizQuestionCategory) {
            throw new NotFoundHttpException('The requested question category was not found.');
        }

        return $category;
    }

    private function assertExerciseCategoryTitleIsUnique(string $title, Course $course, ?int $exceptCategoryId = null): void
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('category.id')
            ->from(CQuizCategory::class, 'category')
            ->andWhere('IDENTITY(category.course) = :courseId')
            ->andWhere('LOWER(category.title) = :title')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->setParameter('title', mb_strtolower($title), Types::STRING)
            ->setMaxResults(1)
        ;

        if (null !== $exceptCategoryId) {
            $queryBuilder
                ->andWhere('category.id <> :categoryId')
                ->setParameter('categoryId', $exceptCategoryId, Types::INTEGER)
            ;
        }

        if (null !== $queryBuilder->getQuery()->getOneOrNullResult()) {
            throw new BadRequestHttpException('A category with this title already exists.');
        }
    }

    private function getNextExerciseCategoryPosition(Course $course): int
    {
        $position = $this->entityManager->createQueryBuilder()
            ->select('COALESCE(MAX(category.position), 0)')
            ->from(CQuizCategory::class, 'category')
            ->andWhere('IDENTITY(category.course) = :courseId')
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $position + 1;
    }

    private function isSettingEnabled(string $name): bool
    {
        return 'true' === $this->settingsManager->getSetting($name, true);
    }
}
