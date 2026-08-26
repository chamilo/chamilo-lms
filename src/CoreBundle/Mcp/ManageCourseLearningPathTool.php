<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Service\Document\CourseDocumentContentService;
use Chamilo\CoreBundle\Service\Exercise\ExerciseLearningPathItemFactory;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use Chamilo\CoreBundle\Service\Survey\CourseSurveyContentService;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use learnpath;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

use const DATE_ATOM;
use const PREG_SPLIT_NO_EMPTY;

final readonly class ManageCourseLearningPathTool
{
    private const int MAX_PAGE_CONTENT_LENGTH = 2_000_000;

    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CLpRepository $lpRepository,
        private CLpItemRepository $lpItemRepository,
        private CourseDocumentContentService $documentContentService,
        private CreateCourseDocumentTool $documentTool,
        private CourseSurveyContentService $surveyContentService,
        private ExerciseLearningPathItemFactory $exerciseLearningPathItemFactory,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * @return array{created: true, learning_path: array<string, mixed>, items: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'create_empty_learning_path',
        description: 'Create an empty learning path in a course managed by the authenticated teacher. Use the add_learning_path_page, add_learning_path_document, add_learning_path_test and add_learning_path_survey tools afterwards to build it conversationally.',
    )]
    public function createEmptyLearningPath(
        int $courseId,
        string $title,
        ?string $description = null,
        bool $publish = false,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $title = trim(strip_tags($title));
            if ('' === $title || mb_strlen($title) > 255) {
                throw new InvalidArgumentException('The learning path title is required and cannot be longer than 255 characters.');
            }

            $description = null !== $description ? (string) Security::remove_XSS($description) : '';
            $visibility = $publish ? ResourceLink::VISIBILITY_PUBLISHED : ResourceLink::VISIBILITY_DRAFT;
            $learningPath = (new CLp())
                ->setLpType(CLp::LP_TYPE)
                ->setTitle($title)
                ->setDescription($description)
                ->setParent($course)
                ->addCourseLink($course, null, null, $visibility)
            ;
            $this->lpRepository->createLp($learningPath);
            $this->entityManager->flush();

            $learningPathId = (int) $learningPath->getIid();
            if ($learningPathId <= 0 || !$this->lpItemRepository->getRootItem($learningPathId) instanceof CLpItem) {
                throw new RuntimeException('The learning path was not persisted with its required root item.');
            }

            return [
                'created' => true,
                'learning_path' => $this->normalizeLearningPath($courseId, $learningPath),
                'items' => [],
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The empty learning path could not be created because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{course_id: int, total: int, learning_paths: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'list_learning_paths',
        description: 'List the learning paths in a base course managed by the authenticated teacher, including their internal IDs, titles, visibility and builder URLs. Use this to locate an existing learning path before reading or editing its structure.',
    )]
    public function listLearningPaths(int $courseId): array
    {
        try {
            $context = $this->courseContext->resolve($courseId);

            /** @var list<CLp> $learningPaths */
            $learningPaths = $this->entityManager->createQueryBuilder()
                ->select('learningPath', 'node', 'resourceLink')
                ->from(CLp::class, 'learningPath')
                ->innerJoin('learningPath.resourceNode', 'node')
                ->innerJoin('node.resourceLinks', 'resourceLink')
                ->andWhere('IDENTITY(resourceLink.course) = :courseId')
                ->andWhere('resourceLink.session IS NULL')
                ->andWhere('resourceLink.group IS NULL')
                ->andWhere('resourceLink.userGroup IS NULL')
                ->andWhere('resourceLink.user IS NULL')
                ->setParameter('courseId', (int) $context['course']->getId(), Types::INTEGER)
                ->orderBy('learningPath.title', 'ASC')
                ->addOrderBy('learningPath.iid', 'ASC')
                ->getQuery()
                ->getResult()
            ;

            return [
                'course_id' => (int) $context['course']->getId(),
                'total' => \count($learningPaths),
                'learning_paths' => array_values(array_map(
                    fn (CLp $learningPath): array => $this->normalizeLearningPath($courseId, $learningPath),
                    $learningPaths,
                )),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The learning paths could not be listed because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{learning_path: array<string, mixed>, items: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'get_learning_path_structure',
        description: 'Return the persisted structure of a learning path, including item IDs, types, resource references, parent IDs and display order. Use this after every add, move or remove operation to verify the result.',
    )]
    public function getLearningPathStructure(int $courseId, int $learningPathId): array
    {
        try {
            $this->courseContext->resolve($courseId);
            $learningPath = $this->resolveLearningPath($courseId, $learningPathId);

            return [
                'learning_path' => $this->normalizeLearningPath($courseId, $learningPath),
                'items' => $this->normalizeItems($learningPath),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The learning path structure could not be loaded because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{created: true, document: array<string, mixed>, learning_path: array<string, mixed>, items: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'add_learning_path_page',
        description: 'Create an HTML document from content supplied by the MCP client and add it as a page to an existing learning path. The operation returns the persisted learning path structure.',
    )]
    public function addLearningPathPage(
        int $courseId,
        int $learningPathId,
        string $title,
        string $content,
        ?string $language = null,
        bool $publishDocument = false,
        ?int $afterItemId = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $learningPath = $this->resolveLearningPath($courseId, $learningPathId);
            $title = trim(strip_tags($title));
            $content = trim($content);
            if ('' === $title || mb_strlen($title) > 255) {
                throw new InvalidArgumentException('The page title is required and cannot be longer than 255 characters.');
            }
            if ('' === $content || mb_strlen($content) > self::MAX_PAGE_CONTENT_LENGTH) {
                throw new InvalidArgumentException('The page content is required and must not exceed the maximum document size.');
            }

            $wordCount = max(50, min(5000, $this->countWords($content)));
            $documentResult = $this->documentTool->createCourseDocument(
                $courseId,
                $title,
                $title,
                $wordCount,
                $content,
                $language,
                $publishDocument,
            );
            $document = $documentResult['document'];
            $this->addItem(
                $context['course'],
                (int) $context['user']->getId(),
                $learningPath,
                TOOL_DOCUMENT,
                (int) $document['document_id'],
                (string) $document['title'],
                $afterItemId,
            );

            return [
                'created' => true,
                'document' => $document,
                'learning_path' => $this->normalizeLearningPath($courseId, $learningPath),
                'items' => $this->normalizeItems($learningPath),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The learning path page could not be added because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{updated: true, learning_path: array<string, mixed>, items: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'add_learning_path_document',
        description: 'Add an existing course document to a learning path. Identify the document by documentId or exact title. The operation returns the persisted learning path structure.',
    )]
    public function addLearningPathDocument(
        int $courseId,
        int $learningPathId,
        ?int $documentId = null,
        ?string $documentTitle = null,
        ?int $afterItemId = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $learningPath = $this->resolveLearningPath($courseId, $learningPathId);
            $document = $this->documentContentService->resolveDocument(
                $context['course'],
                $documentId,
                $documentTitle,
            );
            $this->addItem(
                $context['course'],
                (int) $context['user']->getId(),
                $learningPath,
                TOOL_DOCUMENT,
                (int) $document->getIid(),
                $document->getTitle(),
                $afterItemId,
            );

            return [
                'updated' => true,
                'learning_path' => $this->normalizeLearningPath($courseId, $learningPath),
                'items' => $this->normalizeItems($learningPath),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The document could not be added to the learning path because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{updated: true, learning_path: array<string, mixed>, items: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'add_learning_path_test',
        description: 'Add an existing test to a learning path. Identify the test by testId or exact title. The operation returns the persisted learning path structure.',
    )]
    public function addLearningPathTest(
        int $courseId,
        int $learningPathId,
        ?int $testId = null,
        ?string $testTitle = null,
        ?int $afterItemId = null,
    ): array {
        try {
            $this->courseContext->resolve($courseId);
            $learningPath = $this->resolveLearningPath($courseId, $learningPathId);
            $quiz = $this->resolveQuiz($courseId, $testId, $testTitle);
            $this->addExerciseItem(
                $learningPath,
                $quiz,
                $afterItemId,
            );

            return [
                'updated' => true,
                'learning_path' => $this->normalizeLearningPath($courseId, $learningPath),
                'items' => $this->normalizeItems($learningPath),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The test could not be added to the learning path because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{updated: true, learning_path: array<string, mixed>, items: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'add_learning_path_survey',
        description: 'Add an existing survey to a learning path as a native step, the same way the Chamilo learning path builder lets a teacher attach a survey. Identify the survey by surveyId or exact title. The survey must already have at least one question. The operation returns the persisted learning path structure.',
    )]
    public function addLearningPathSurvey(
        int $courseId,
        int $learningPathId,
        ?int $surveyId = null,
        ?string $surveyTitle = null,
        ?int $afterItemId = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $learningPath = $this->resolveLearningPath($courseId, $learningPathId);
            $survey = $this->surveyContentService->resolveSurvey($context['course'], $surveyId, $surveyTitle);
            if ($survey->getQuestions()->isEmpty()) {
                throw new InvalidArgumentException('The survey has no questions yet and cannot be added to the learning path.');
            }
            $this->addItem(
                $context['course'],
                (int) $context['user']->getId(),
                $learningPath,
                TOOL_SURVEY,
                (int) $survey->getIid(),
                $survey->getTitle(),
                $afterItemId,
            );

            return [
                'updated' => true,
                'learning_path' => $this->normalizeLearningPath($courseId, $learningPath),
                'items' => $this->normalizeItems($learningPath),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The survey could not be added to the learning path because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{updated: true, learning_path: array<string, mixed>, items: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'move_learning_path_item',
        description: 'Move a first-level learning path item to a one-based position. Use get_learning_path_structure first to obtain the item ID and current order.',
    )]
    public function moveLearningPathItem(
        int $courseId,
        int $learningPathId,
        int $itemId,
        int $position,
    ): array {
        try {
            $this->courseContext->resolve($courseId);
            $learningPath = $this->resolveLearningPath($courseId, $learningPathId);
            $items = $this->getFirstLevelItems($learningPath);
            $item = null;
            foreach ($items as $index => $candidate) {
                if ((int) $candidate->getIid() === $itemId) {
                    $item = $candidate;
                    array_splice($items, $index, 1);

                    break;
                }
            }
            if (!$item instanceof CLpItem) {
                throw new InvalidArgumentException('The learning path item was not found at the first level.');
            }
            if ($position < 1 || $position > \count($items) + 1) {
                throw new InvalidArgumentException('The requested position is outside the learning path item range.');
            }

            array_splice($items, $position - 1, 0, [$item]);
            $this->persistItemOrder($learningPath, $items);

            return [
                'updated' => true,
                'learning_path' => $this->normalizeLearningPath($courseId, $learningPath),
                'items' => $this->normalizeItems($learningPath),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The learning path item could not be moved because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{updated: true, learning_path: array<string, mixed>, items: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'remove_learning_path_item',
        description: 'Remove an item from a learning path without deleting the underlying document or test. Use get_learning_path_structure first to obtain the item ID.',
    )]
    public function removeLearningPathItem(int $courseId, int $learningPathId, int $itemId): array
    {
        try {
            $this->courseContext->resolve($courseId);
            $learningPath = $this->resolveLearningPath($courseId, $learningPathId);
            $item = $this->lpItemRepository->find($itemId);
            if (!$item instanceof CLpItem || (int) $item->getLp()->getIid() !== $learningPathId || 'root' === $item->getItemType()) {
                throw new InvalidArgumentException('The learning path item was not found or cannot be removed.');
            }

            $this->lpItemRepository->removeFromTree($item);
            $this->entityManager->flush();
            $this->persistItemOrder($learningPath, $this->getFirstLevelItems($learningPath));

            return [
                'updated' => true,
                'learning_path' => $this->normalizeLearningPath($courseId, $learningPath),
                'items' => $this->normalizeItems($learningPath),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The learning path item could not be removed because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{updated: true, learning_path: array<string, mixed>, items: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'update_learning_path_item',
        description: 'Edit the display title of an existing learning path item. Use get_learning_path_structure first to obtain the item ID. Edit linked document content separately with edit_course_document.',
    )]
    public function updateLearningPathItem(
        int $courseId,
        int $learningPathId,
        int $itemId,
        string $title,
    ): array {
        try {
            $this->courseContext->resolve($courseId);
            $learningPath = $this->resolveLearningPath($courseId, $learningPathId);
            $item = $this->lpItemRepository->find($itemId);
            if (!$item instanceof CLpItem || (int) $item->getLp()->getIid() !== $learningPathId || 'root' === $item->getItemType()) {
                throw new InvalidArgumentException('The learning path item was not found or cannot be edited.');
            }

            $title = trim(strip_tags($title));
            if ('' === $title || mb_strlen($title) > 255) {
                throw new InvalidArgumentException('The learning path item title is required and cannot be longer than 255 characters.');
            }
            if ($title === $item->getTitle()) {
                throw new InvalidArgumentException('The learning path item already has this title.');
            }

            $item->setTitle($title);
            $this->entityManager->persist($item);
            $this->entityManager->flush();

            return [
                'updated' => true,
                'learning_path' => $this->normalizeLearningPath($courseId, $learningPath),
                'items' => $this->normalizeItems($learningPath),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The learning path item could not be updated because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    /**
     * @return array{updated: true, learning_path: array<string, mixed>, items: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'update_learning_path',
        description: 'Edit the title, description and/or draft/published visibility of an existing learning path managed by the authenticated teacher.',
    )]
    public function updateLearningPath(
        int $courseId,
        int $learningPathId,
        ?string $title = null,
        ?string $description = null,
        ?bool $publish = null,
    ): array {
        try {
            $this->courseContext->resolve($courseId);
            $learningPath = $this->resolveLearningPath($courseId, $learningPathId);
            $changed = false;

            if (null !== $title) {
                $title = trim(strip_tags($title));
                if ('' === $title || mb_strlen($title) > 255) {
                    throw new InvalidArgumentException('The learning path title is required and cannot be longer than 255 characters.');
                }
                if ($title !== $learningPath->getTitle()) {
                    $learningPath->setTitle($title);
                    $changed = true;
                }
            }
            if (null !== $description) {
                $description = (string) Security::remove_XSS($description);
                if ($description !== $learningPath->getDescription()) {
                    $learningPath->setDescription($description);
                    $changed = true;
                }
            }
            if (null !== $publish) {
                $resourceLink = $this->resolveBaseCourseLink($learningPath, $courseId);
                $visibility = $publish
                    ? ResourceLink::VISIBILITY_PUBLISHED
                    : ResourceLink::VISIBILITY_DRAFT;
                if ($resourceLink->getVisibility() !== $visibility) {
                    $resourceLink->setVisibility($visibility);
                    $this->entityManager->persist($resourceLink);
                    $changed = true;
                }
            }
            if (!$changed) {
                throw new InvalidArgumentException('Provide a new title, description and/or publish value.');
            }

            $this->entityManager->persist($learningPath);
            $this->entityManager->flush();

            return [
                'updated' => true,
                'learning_path' => $this->normalizeLearningPath($courseId, $learningPath),
                'items' => $this->normalizeItems($learningPath),
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The learning path could not be updated because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    private function resolveBaseCourseLink(CLp $learningPath, int $courseId): ResourceLink
    {
        $resourceNode = $learningPath->getResourceNode();
        if (null === $resourceNode) {
            throw new RuntimeException('The learning path resource node could not be resolved.');
        }

        foreach ($resourceNode->getResourceLinks() as $resourceLink) {
            if (!$resourceLink instanceof ResourceLink) {
                continue;
            }
            if ((int) $resourceLink->getCourse()?->getId() === $courseId
                && null === $resourceLink->getSession()
                && null === $resourceLink->getGroup()
                && null === $resourceLink->getUserGroup()
                && null === $resourceLink->getUser()
            ) {
                return $resourceLink;
            }
        }

        throw new RuntimeException('The learning path is not linked to the selected base course.');
    }

    private function resolveLearningPath(int $courseId, int $learningPathId): CLp
    {
        if ($learningPathId <= 0) {
            throw new InvalidArgumentException('The learning path ID must be a positive integer.');
        }

        $learningPath = $this->entityManager->createQueryBuilder()
            ->select('learningPath')
            ->from(CLp::class, 'learningPath')
            ->innerJoin('learningPath.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'resourceLink')
            ->andWhere('learningPath.iid = :learningPathId')
            ->andWhere('IDENTITY(resourceLink.course) = :courseId')
            ->andWhere('resourceLink.session IS NULL')
            ->andWhere('resourceLink.group IS NULL')
            ->andWhere('resourceLink.userGroup IS NULL')
            ->andWhere('resourceLink.user IS NULL')
            ->setParameter('learningPathId', $learningPathId, Types::INTEGER)
            ->setParameter('courseId', $courseId, Types::INTEGER)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$learningPath instanceof CLp) {
            throw new InvalidArgumentException('The learning path was not found in this course.');
        }

        return $learningPath;
    }

    private function resolveQuiz(int $courseId, ?int $testId, ?string $testTitle): CQuiz
    {
        $testId = null !== $testId && $testId > 0 ? $testId : null;
        $testTitle = null !== $testTitle ? trim($testTitle) : '';
        if (null === $testId && '' === $testTitle) {
            throw new InvalidArgumentException('Provide either testId or testTitle.');
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('quiz')
            ->from(CQuiz::class, 'quiz')
            ->innerJoin('quiz.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'resourceLink')
            ->andWhere('IDENTITY(resourceLink.course) = :courseId')
            ->andWhere('resourceLink.session IS NULL')
            ->andWhere('resourceLink.group IS NULL')
            ->andWhere('resourceLink.userGroup IS NULL')
            ->andWhere('resourceLink.user IS NULL')
            ->setParameter('courseId', $courseId, Types::INTEGER)
        ;
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

    private function addExerciseItem(CLp $learningPath, CQuiz $quiz, ?int $afterItemId): void
    {
        // Exercises can contain modern-only question types. Build the CLpItem
        // directly instead of routing test attachment through legacy Question classes.
        $rootItem = $this->lpItemRepository->getRootItem((int) $learningPath->getIid());
        if (!$rootItem instanceof CLpItem) {
            throw new RuntimeException('The learning path root item could not be resolved.');
        }

        $items = $this->getFirstLevelItems($learningPath);
        if (null !== $afterItemId) {
            $valid = false;
            foreach ($items as $item) {
                if ((int) $item->getIid() === $afterItemId) {
                    $valid = true;

                    break;
                }
            }
            if (!$valid) {
                throw new InvalidArgumentException('The afterItemId value does not identify a first-level item in this learning path.');
            }
        }

        $exerciseId = (int) $quiz->getIid();
        if ($exerciseId <= 0) {
            throw new RuntimeException('The exercise must be persisted before it can be added to a learning path.');
        }

        $learningPathItem = null;
        foreach ($learningPath->getItems() as $candidate) {
            if (TOOL_QUIZ === $candidate->getItemType() && (string) $exerciseId === (string) $candidate->getPath()) {
                $learningPathItem = $candidate;

                break;
            }
        }

        if (!$learningPathItem instanceof CLpItem) {
            $displayOrder = 2;
            foreach ($items as $item) {
                $displayOrder = max($displayOrder, (int) $item->getDisplayOrder() + 1);
            }

            $learningPathItem = $this->exerciseLearningPathItemFactory->create(
                $learningPath,
                $quiz,
                $exerciseId,
                $rootItem,
                $displayOrder,
            );
            $this->entityManager->persist($learningPathItem);
            $this->entityManager->flush();
        } elseif ((int) $learningPathItem->getParent()?->getIid() !== (int) $rootItem->getIid()) {
            throw new InvalidArgumentException('The test is already attached inside a nested learning path section.');
        }

        $itemId = (int) $learningPathItem->getIid();
        if ($itemId <= 0) {
            throw new RuntimeException('The exercise learning path item could not be persisted.');
        }

        $items = $this->getFirstLevelItems($learningPath);
        $learningPathItem = null;
        foreach ($items as $index => $item) {
            if ((int) $item->getIid() === $itemId) {
                $learningPathItem = $item;
                array_splice($items, $index, 1);

                break;
            }
        }
        if (!$learningPathItem instanceof CLpItem) {
            throw new RuntimeException('The persisted exercise item could not be resolved in the learning path.');
        }

        if (null === $afterItemId) {
            $items[] = $learningPathItem;
        } else {
            $targetIndex = null;
            foreach ($items as $index => $item) {
                if ((int) $item->getIid() === $afterItemId) {
                    $targetIndex = $index + 1;

                    break;
                }
            }
            if (null === $targetIndex) {
                throw new RuntimeException('The target learning path item could not be resolved after creating the exercise item.');
            }
            array_splice($items, $targetIndex, 0, [$learningPathItem]);
        }

        $learningPath->setModifiedOn(new DateTime());
        $this->entityManager->persist($learningPath);
        $this->persistItemOrder($learningPath, $items);
    }

    private function addItem(
        Course $course,
        int $userId,
        CLp $learningPath,
        string $type,
        int $resourceId,
        string $title,
        ?int $afterItemId,
    ): void {
        $rootItem = $this->lpItemRepository->getRootItem((int) $learningPath->getIid());
        if (!$rootItem instanceof CLpItem) {
            throw new RuntimeException('The learning path root item could not be resolved.');
        }

        $items = $this->getFirstLevelItems($learningPath);
        $previousItemId = 0;
        if (null !== $afterItemId) {
            $valid = false;
            foreach ($items as $item) {
                if ((int) $item->getIid() === $afterItemId) {
                    $valid = true;
                    $previousItemId = $afterItemId;

                    break;
                }
            }
            if (!$valid) {
                throw new InvalidArgumentException('The afterItemId value does not identify a first-level item in this learning path.');
            }
        } elseif ([] !== $items) {
            $lastItem = $items[array_key_last($items)];
            $previousItemId = (int) $lastItem->getIid();
        }

        require_once api_get_path(SYS_CODE_PATH).'lp/learnpath.class.php';

        require_once api_get_path(SYS_CODE_PATH).'exercise/exercise.class.php';
        $courseInfo = api_get_course_info($course->getCode());
        if (!\is_array($courseInfo) || [] === $courseInfo) {
            throw new RuntimeException('The legacy course context could not be resolved.');
        }

        $legacyLearningPath = new learnpath($learningPath, $courseInfo, $userId);
        $itemId = (int) $legacyLearningPath->add_item(
            $rootItem,
            $previousItemId,
            $type,
            $resourceId,
            $title,
        );
        if ($itemId <= 0) {
            throw new RuntimeException('The learning path item could not be added.');
        }

        $items = $this->getFirstLevelItems($learningPath);
        if (null !== $afterItemId) {
            $newItem = null;
            foreach ($items as $index => $item) {
                if ((int) $item->getIid() === $itemId) {
                    $newItem = $item;
                    array_splice($items, $index, 1);

                    break;
                }
            }
            if ($newItem instanceof CLpItem) {
                $targetIndex = 0;
                foreach ($items as $index => $item) {
                    if ((int) $item->getIid() === $afterItemId) {
                        $targetIndex = $index + 1;

                        break;
                    }
                }
                array_splice($items, $targetIndex, 0, [$newItem]);
            }
        }
        $this->persistItemOrder($learningPath, $items);
    }

    /**
     * @return list<CLpItem>
     */
    private function getFirstLevelItems(CLp $learningPath): array
    {
        $root = $this->lpItemRepository->getRootItem((int) $learningPath->getIid());
        if (!$root instanceof CLpItem) {
            return [];
        }

        /** @var list<CLpItem> $items */
        return $this->entityManager->createQueryBuilder()
            ->select('item')
            ->from(CLpItem::class, 'item')
            ->andWhere('IDENTITY(item.lp) = :learningPathId')
            ->andWhere('IDENTITY(item.parent) = :rootItemId')
            ->andWhere('item.itemType != :rootType')
            ->setParameter('learningPathId', (int) $learningPath->getIid(), Types::INTEGER)
            ->setParameter('rootItemId', (int) $root->getIid(), Types::INTEGER)
            ->setParameter('rootType', 'root', Types::STRING)
            ->orderBy('item.displayOrder', 'ASC')
            ->addOrderBy('item.iid', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param list<CLpItem> $items
     */
    private function persistItemOrder(CLp $learningPath, array $items): void
    {
        $root = $this->lpItemRepository->getRootItem((int) $learningPath->getIid());
        if (!$root instanceof CLpItem) {
            throw new RuntimeException('The learning path root item could not be resolved.');
        }

        $root
            ->setDisplayOrder(1)
            ->setPreviousItemId(null)
            ->setNextItemId(null)
        ;
        $this->entityManager->persist($root);

        foreach ($items as $index => $item) {
            $item
                ->setParent($root)
                ->setDisplayOrder($index + 2)
                ->setPreviousItemId(null)
                ->setNextItemId(null)
            ;
            $this->entityManager->persist($item);
        }
        $this->entityManager->flush();

        $this->lpItemRepository->recoverNode($root, 'displayOrder');
        $this->entityManager->persist($root);
        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeLearningPath(int $courseId, CLp $learningPath): array
    {
        $resourceLink = $this->resolveBaseCourseLink($learningPath, $courseId);

        return [
            'learning_path_id' => (int) $learningPath->getIid(),
            'resource_node_id' => (int) $learningPath->getResourceNode()?->getId(),
            'title' => $learningPath->getTitle(),
            'description' => $learningPath->getDescription(),
            'visibility' => $resourceLink->getVisibility(),
            'published' => ResourceLink::VISIBILITY_PUBLISHED === $resourceLink->getVisibility(),
            'verified_in_course' => true,
            'updated_at' => $learningPath->getResourceNode()?->getUpdatedAt()?->format(DATE_ATOM),
            'content_url' => '/resources/lp/'
                .(int) $learningPath->getResourceNode()?->getParent()?->getId()
                .'/'.(int) $learningPath->getIid()
                .'/builder?cid='.$courseId,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeItems(CLp $learningPath): array
    {
        /** @var list<CLpItem> $items */
        $items = $this->entityManager->createQueryBuilder()
            ->select('item', 'parent')
            ->from(CLpItem::class, 'item')
            ->leftJoin('item.parent', 'parent')
            ->andWhere('IDENTITY(item.lp) = :learningPathId')
            ->andWhere('item.itemType != :rootType')
            ->setParameter('learningPathId', (int) $learningPath->getIid(), Types::INTEGER)
            ->setParameter('rootType', 'root', Types::STRING)
            ->orderBy('item.previousItemId', 'ASC')
            ->addOrderBy('item.displayOrder', 'ASC')
            ->addOrderBy('item.iid', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_values(array_map(
            static fn (CLpItem $item): array => [
                'item_id' => (int) $item->getIid(),
                'title' => $item->getTitle(),
                'type' => $item->getItemType(),
                'resource_id' => (int) ($item->getPath() ?: $item->getRef()),
                'parent_item_id' => 'root' === $item->getParent()?->getItemType()
                    ? null
                    : (int) $item->getParent()?->getIid(),
                'display_order' => (int) $item->getDisplayOrder(),
                'previous_item_id' => $item->getPreviousItemId(),
                'next_item_id' => $item->getNextItemId(),
            ],
            $items,
        ));
    }

    private function countWords(string $html): int
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', strip_tags($html)));
        if ('' === $text) {
            return 0;
        }

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

        return \is_array($words) ? \count($words) : 0;
    }
}
