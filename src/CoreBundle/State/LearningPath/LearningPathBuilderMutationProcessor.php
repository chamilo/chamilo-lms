<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathBuilderBulkAuthorPriceInput;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathBuilderDeleteInput;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathBuilderFinalItemInput;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathBuilderItemAudioInput;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathBuilderItemInput;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathBuilderItemPrerequisiteInput;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathBuilderItemUpdateInput;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathBuilderOrderInput;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathBuilderPrerequisiteInput;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathBuilderResourceInput;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CQuiz;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use DateTime;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

/** @implements ProcessorInterface<object, object|null> */
final readonly class LearningPathBuilderMutationProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    /** @var string[] */
    private const AUDIO_EXTENSIONS = ['aac', 'm4a', 'mp3', 'ogg', 'wav', 'webm'];

    private const FILE_EXTRA_FIELD_TYPES = [
        ExtraField::FIELD_TYPE_FILE_IMAGE,
        ExtraField::FIELD_TYPE_FILE,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SettingsManager $settingsManager,
        private CLpRepository $lpRepository,
        private CLpItemRepository $lpItemRepository,
        private ResourceNodeRepository $resourceNodeRepository,
        private ExtraFieldRepository $extraFieldRepository,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): object|null
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->assertLearningPathTeacher($this->security);
        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);

        if ($data instanceof LearningPathBuilderItemUpdateInput
            && 'update_learning_path_builder_item_form' === $operation->getName()
        ) {
            $this->hydrateItemUpdateInput($data, $request);
        }

        $lpId = $this->resolveLearningPathId($data, $uriVariables);
        $lp = $this->lpRepository->find($lpId);
        if (!$lp instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }

        $this->getEditableResourceLink($lp, $course, $session, $group, $this->security);
        if ($data instanceof LearningPathBuilderBulkAuthorPriceInput) {
            return $this->updateBulkAuthorPrice($data, $lp);
        }
        if (CLp::LP_TYPE !== $lp->getLpType()) {
            throw new BadRequestHttpException('The structure of an imported learning path cannot be changed here.');
        }

        $root = $this->lpItemRepository->getRootItem($lpId);
        if (!$root instanceof CLpItem) {
            throw new NotFoundHttpException('Learning path root item not found.');
        }

        return match (true) {
            $data instanceof LearningPathBuilderItemInput => $this->createSection($data, $lp, $root),
            $data instanceof LearningPathBuilderItemUpdateInput => $this->updateItem(
                $data,
                $lp,
                $root,
                $course,
                $session,
                $group,
                $request,
                (int) ($uriVariables['itemId'] ?? $data->itemId ?? 0),
            ),
            $data instanceof LearningPathBuilderItemAudioInput => $this->updateItemAudio(
                $data,
                $lp,
                $course,
                $session,
                $group,
                (int) ($uriVariables['itemId'] ?? $data->itemId ?? 0),
            ),
            $data instanceof LearningPathBuilderItemPrerequisiteInput => $this->updateItemPrerequisite(
                $data,
                $lp,
                (int) ($uriVariables['itemId'] ?? $data->itemId ?? 0),
            ),
            $data instanceof LearningPathBuilderDeleteInput => $this->deleteItem(
                $data,
                $lp,
                $root,
                (int) ($uriVariables['itemId'] ?? $data->itemId ?? 0),
            ),
            $data instanceof LearningPathBuilderOrderInput => $this->reorder($data, $lp, $root),
            $data instanceof LearningPathBuilderResourceInput => $this->addResource(
                $data,
                $lp,
                $root,
                $course,
                $session,
                $group,
            ),
            $data instanceof LearningPathBuilderFinalItemInput => $this->saveFinalItem(
                $data,
                $lp,
                $root,
                $course,
                $session,
                $group,
            ),
            $data instanceof LearningPathBuilderPrerequisiteInput => $this->updatePrerequisites($data, $lp),
            default => throw new BadRequestHttpException('Unsupported learning path builder operation.'),
        };
    }

    /**
     * @param array<string, mixed> $uriVariables
     */
    private function resolveLearningPathId(mixed $data, array $uriVariables): int
    {
        $lpId = (int) ($uriVariables['lpId'] ?? 0);
        if ($lpId > 0) {
            return $lpId;
        }

        $lpId = match (true) {
            $data instanceof LearningPathBuilderBulkAuthorPriceInput => (int) $data->lpId,
            $data instanceof LearningPathBuilderItemInput => (int) $data->lpId,
            $data instanceof LearningPathBuilderItemAudioInput => $data->lpId,
            $data instanceof LearningPathBuilderItemUpdateInput => $data->lpId,
            $data instanceof LearningPathBuilderItemPrerequisiteInput => $data->lpId,
            $data instanceof LearningPathBuilderDeleteInput => $data->lpId,
            $data instanceof LearningPathBuilderOrderInput => (int) $data->lpId,
            $data instanceof LearningPathBuilderResourceInput => (int) $data->lpId,
            $data instanceof LearningPathBuilderFinalItemInput => (int) $data->lpId,
            $data instanceof LearningPathBuilderPrerequisiteInput => (int) $data->lpId,
            default => 0,
        };

        if ($lpId > 0) {
            return $lpId;
        }

        throw new BadRequestHttpException('Learning path id is required.');
    }

    private function createSection(
        LearningPathBuilderItemInput $data,
        CLp $lp,
        CLpItem $root,
    ): LearningPathBuilderItemInput {
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);
        $parent = $this->resolveParent($lp, $root, $data->parentId);
        $title = $this->sanitizeTitle($data->title);

        $item = (new CLpItem())
            ->setLp($lp)
            ->setRoot($root)
            ->setParent($parent)
            ->setItemType('dir')
            ->setTitle($title)
            ->setDescription('')
            ->setPath('0')
            ->setRef('')
            ->setPrerequisite('')
            ->setMaxTimeAllowed('0')
            ->setDisplayOrder($this->nextDisplayOrder($lp))
        ;

        $this->entityManager->persist($item);
        $this->touchLearningPath($lp);
        $this->entityManager->flush();
        $this->recoverTree($root);

        $data->lpId = (int) $lp->getIid();
        $data->id = $item->getIid();
        $data->title = $title;
        $data->parentId = $parent === $root ? null : $parent->getIid();

        return $data;
    }

    private function updateItem(
        LearningPathBuilderItemUpdateInput $data,
        CLp $lp,
        CLpItem $root,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        Request $request,
        int $itemId,
    ): LearningPathBuilderItemUpdateInput {
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);
        $item = $this->findValidatedItem($lp, $itemId);
        $this->assertEditableItem($item);

        $parent = $this->resolveParent($lp, $root, $data->parentId);
        $this->assertValidParent($item, $parent);
        $title = $this->sanitizeTitle($data->title);

        $item
            ->setTitle($title)
            ->setParent($parent)
            ->setRoot($root)
        ;
        $this->entityManager->persist($item);

        $type = trim($item->getItemType());
        if (\in_array($type, ['document', 'video', 'readout_text'], true)) {
            $path = trim((string) $item->getPath());
            $documentId = ctype_digit($path) ? (int) $path : 0;
            $document = $this->findValidatedResource('document', $documentId, $course, $session, $group);
            if (!$document instanceof CDocument) {
                throw new NotFoundHttpException('Learning path document not found.');
            }

            if (null !== $data->content) {
                $resourceNode = $document->getResourceNode();
                if (null === $resourceNode || !$resourceNode->hasEditableTextContent()) {
                    throw new BadRequestHttpException('The document content cannot be edited.');
                }

                $document->setTitle($title);
                $this->writeDocumentContent($document, $data->content);
                $this->entityManager->persist($document);
            }

            $item->setExportAllowed(
                'document' === $type
                && $this->documentAllowsPdfExport($document)
                && $data->exportAllowed
            );
        }

        $this->touchLearningPath($lp);
        if ([] !== $data->extraFields || 0 < $request->files->count()) {
            $this->saveItemExtraFields($itemId, $data->extraFields, $request);
        }
        $this->entityManager->flush();
        $this->recoverTree($root);

        $data->itemId = $itemId;
        $data->title = $item->getTitle();
        $data->parentId = $parent === $root ? null : $parent->getIid();
        $data->exportAllowed = $item->isExportAllowed();

        return $data;
    }

    private function updateItemAudio(
        LearningPathBuilderItemAudioInput $data,
        CLp $lp,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        int $itemId,
    ): LearningPathBuilderItemAudioInput {
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);
        $item = $this->findValidatedItem($lp, $itemId);
        $this->assertEditableItem($item);

        if ('dir' === $item->getItemType()) {
            throw new BadRequestHttpException('Audio cannot be attached to a learning path section.');
        }

        $documentId = (int) ($data->documentId ?? 0);
        if ($documentId <= 0) {
            $item->setAudio('');
            $data->documentId = null;
        } else {
            $document = $this->findValidatedResource('document', $documentId, $course, $session, $group);
            if (!$document instanceof CDocument) {
                throw new NotFoundHttpException('Audio document not found.');
            }

            $resourceNode = $document->getResourceNode();
            $resourceFile = $resourceNode->getFirstResourceFile();
            if (!$resourceFile instanceof ResourceFile || !$this->isAudioResourceFile($resourceFile)) {
                throw new BadRequestHttpException('The selected document is not an audio file.');
            }

            $audioPath = trim((string) $resourceNode->getPath());
            if ('' === $audioPath) {
                throw new BadRequestHttpException('The selected audio document has no storage path.');
            }
            if (250 < \strlen($audioPath)) {
                throw new BadRequestHttpException('The selected audio document path is too long.');
            }

            $item->setAudio($audioPath);
            $data->documentId = $documentId;
        }

        $this->entityManager->persist($item);
        $this->touchLearningPath($lp);
        $this->entityManager->flush();

        $data->itemId = $itemId;
        $data->saved = true;

        return $data;
    }

    private function updateItemPrerequisite(
        LearningPathBuilderItemPrerequisiteInput $data,
        CLp $lp,
        int $itemId,
    ): LearningPathBuilderItemPrerequisiteInput {
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);
        $item = $this->findValidatedItem($lp, $itemId);
        if ('dir' === $item->getItemType() || 'root' === $item->getItemType()) {
            throw new BadRequestHttpException('Prerequisites cannot be assigned to this learning path item.');
        }

        $prerequisiteId = max(0, $data->prerequisiteId);
        $minScore = $data->minScore;
        $maxScore = $data->maxScore;

        if (0 === $prerequisiteId) {
            $item
                ->setPrerequisite('')
                ->setPrerequisiteMinScore(0.0)
                ->setPrerequisiteMaxScore(100.0)
            ;
        } else {
            $prerequisite = $this->findValidatedItem($lp, $prerequisiteId);
            if ('dir' === $prerequisite->getItemType() || 'root' === $prerequisite->getItemType()) {
                throw new BadRequestHttpException('The selected prerequisite is invalid.');
            }
            if ($prerequisite->getIid() === $item->getIid()
                || $prerequisite->getDisplayOrder() > $item->getDisplayOrder()
            ) {
                throw new BadRequestHttpException('The selected prerequisite is not available for this item.');
            }

            if (\in_array($prerequisite->getItemType(), ['quiz', 'hotpotatoes'], true)) {
                $availableMaxScore = $this->getCurrentItemMaxScore($prerequisite);
                $prerequisite->setMaxScore($availableMaxScore);
                $this->entityManager->persist($prerequisite);
                if ($availableMaxScore > 0.0) {
                    $minScore = max(0.0, min($minScore, $availableMaxScore));
                    $maxScore = max(0.0, min($maxScore, $availableMaxScore));
                } else {
                    $minScore = max(0.0, $minScore);
                    $maxScore = max(0.0, $maxScore);
                }
                if ($maxScore < $minScore) {
                    throw new BadRequestHttpException(
                        'The maximum prerequisite score must be greater than or equal to the minimum score.'
                    );
                }
            } else {
                $minScore = 0.0;
                $maxScore = 100.0;
            }

            $item
                ->setPrerequisite((string) $prerequisiteId)
                ->setPrerequisiteMinScore($minScore)
                ->setPrerequisiteMaxScore($maxScore)
            ;
        }

        $this->entityManager->persist($item);
        $this->touchLearningPath($lp);
        $this->entityManager->flush();

        $data->itemId = $itemId;
        $data->prerequisiteId = $prerequisiteId;
        $data->minScore = $minScore;
        $data->maxScore = $maxScore;
        $data->saved = true;

        return $data;
    }

    private function addResource(
        LearningPathBuilderResourceInput $data,
        CLp $lp,
        CLpItem $root,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): LearningPathBuilderResourceInput {
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);
        $resource = $this->findValidatedResource(
            $data->resourceType,
            $data->resourceId,
            $course,
            $session,
            $group,
        );
        $parent = $this->resolveParent($lp, $root, $data->parentId);
        $itemType = $this->resolveItemType($data->resourceType, $resource);
        $title = $this->resourceTitle($resource);

        if ($resource instanceof CSurvey && $resource->getQuestions()->isEmpty()) {
            throw new BadRequestHttpException('A survey must contain at least one question.');
        }

        $item = (new CLpItem())
            ->setLp($lp)
            ->setRoot($root)
            ->setParent($parent)
            ->setItemType($itemType)
            ->setTitle($title)
            ->setDescription('')
            ->setPath((string) $data->resourceId)
            ->setRef('')
            ->setPrerequisite('')
            ->setMaxTimeAllowed('0')
            ->setDisplayOrder($this->nextDisplayOrder($lp))
        ;

        if ($resource instanceof CDocument && 'document' === $itemType) {
            $resourceNode = $resource->getResourceNode();
            $item->setExportAllowed(
                $data->exportAllowed
                && null !== $resourceNode
                && $resourceNode->hasEditableTextContent()
            );
        }

        if ($resource instanceof CQuiz) {
            $item->setMaxScore((float) $resource->getMaxScore());
            $link = $this->getContextResourceLink($resource, $course, $session, $group);
            if ($link instanceof ResourceLink) {
                $link->setVisibility(ResourceLink::VISIBILITY_DRAFT);
                $this->entityManager->persist($link);
            }
        }

        $this->entityManager->persist($item);
        $this->touchLearningPath($lp);
        $this->entityManager->flush();
        $this->normalizeCurrentOrder($lp, $root);

        $data->lpId = (int) $lp->getIid();
        $data->id = $item->getIid();
        $data->title = $title;
        $data->itemType = $itemType;
        $data->exportAllowed = $item->isExportAllowed();

        return $data;
    }

    private function saveFinalItem(
        LearningPathBuilderFinalItemInput $data,
        CLp $lp,
        CLpItem $root,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): LearningPathBuilderFinalItemInput {
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);
        $title = $this->sanitizeTitle($data->title);
        $resource = $this->findValidatedResource('document', $data->documentId, $course, $session, $group);
        if (!$resource instanceof CDocument || 'certificate' !== strtolower(trim($resource->getFiletype()))) {
            throw new BadRequestHttpException('The selected document is not a learning path final item.');
        }

        $gradebookCategoryId = $this->validateGradebookCategory(
            $data->gradebookCategoryId,
            $course,
            $session,
        );

        $item = $this->lpItemRepository->createQueryBuilder('item')
            ->andWhere('item.lp = :lpId')
            ->andWhere('item.itemType = :itemType')
            ->setParameter('lpId', (int) $lp->getIid())
            ->setParameter('itemType', 'final_item')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        if (!$item instanceof CLpItem) {
            $item = (new CLpItem())
                ->setLp($lp)
                ->setRoot($root)
                ->setParent($root)
                ->setItemType('final_item')
                ->setDescription('')
                ->setPrerequisite('')
                ->setMaxTimeAllowed('0')
                ->setDisplayOrder($this->nextDisplayOrder($lp))
            ;
        }

        $item
            ->setRoot($root)
            ->setParent($root)
            ->setTitle($title)
            ->setPath((string) $data->documentId)
            ->setRef(null === $gradebookCategoryId ? '' : (string) $gradebookCategoryId)
            ->setExportAllowed(false)
        ;

        $this->entityManager->persist($item);
        $this->touchLearningPath($lp);
        $this->entityManager->flush();
        $this->normalizeCurrentOrder($lp, $root);

        $data->lpId = (int) $lp->getIid();
        $data->itemId = $item->getIid();
        $data->title = $title;
        $data->gradebookCategoryId = $gradebookCategoryId;
        $data->saved = true;

        return $data;
    }

    private function validateGradebookCategory(
        ?int $categoryId,
        Course $course,
        ?Session $session,
    ): ?int {
        if (null === $categoryId || $categoryId <= 0) {
            return null;
        }

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('category')
            ->from(GradebookCategory::class, 'category')
            ->andWhere('category.id = :categoryId')
            ->andWhere('IDENTITY(category.course) = :courseId')
            ->andWhere('category.gradeModel IS NULL')
            ->setParameter('categoryId', $categoryId, Types::INTEGER)
            ->setParameter('courseId', (int) $course->getId(), Types::INTEGER)
        ;

        if ($session instanceof Session) {
            $queryBuilder
                ->andWhere('IDENTITY(category.session) = :sessionId')
                ->setParameter('sessionId', (int) $session->getId(), Types::INTEGER)
            ;
        } else {
            $queryBuilder->andWhere('category.session IS NULL');
        }

        $category = $queryBuilder->getQuery()->getOneOrNullResult();
        if (!$category instanceof GradebookCategory) {
            throw new BadRequestHttpException('The selected gradebook category is invalid.');
        }

        return (int) $category->getId();
    }

    private function deleteItem(
        LearningPathBuilderDeleteInput $data,
        CLp $lp,
        CLpItem $root,
        int $itemId,
    ): LearningPathBuilderDeleteInput {
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);
        $item = $this->findValidatedItem($lp, $itemId);
        if ('root' === $item->getItemType()) {
            throw new BadRequestHttpException('The learning path root item cannot be deleted.');
        }

        $this->touchLearningPath($lp);
        $this->lpItemRepository->removeFromTree($item);
        $this->entityManager->flush();

        foreach ($this->getItemsById($lp) as $candidate) {
            if ((string) $candidate->getPrerequisite() !== (string) $itemId) {
                continue;
            }
            $candidate->setPrerequisite('');
            $this->entityManager->persist($candidate);
        }

        $this->normalizeCurrentOrder($lp, $root);
        $data->itemId = $itemId;
        $data->deleted = true;

        return $data;
    }

    private function reorder(
        LearningPathBuilderOrderInput $data,
        CLp $lp,
        CLpItem $root,
    ): LearningPathBuilderOrderInput {
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);
        if ([] === $data->order) {
            throw new BadRequestHttpException('The item order is required.');
        }

        $items = $this->getItemsById($lp);
        $normalized = $this->validateAndNormalizeOrder($data->order, $items);
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $root
                ->setDisplayOrder(1)
                ->setPreviousItemId(null)
                ->setNextItemId(null)
            ;
            $this->entityManager->persist($root);

            $position = 2;
            foreach ($normalized as $entry) {
                $item = $items[$entry['id']];
                $parent = null === $entry['parentId'] ? $root : $items[$entry['parentId']];
                $item
                    ->setRoot($root)
                    ->setParent($parent)
                    ->setDisplayOrder($position)
                    ->setPreviousItemId(null)
                    ->setNextItemId(null)
                ;
                $this->entityManager->persist($item);
                ++$position;
            }

            $this->touchLearningPath($lp);
            $this->entityManager->flush();
            $this->recoverTree($root);
            $connection->commit();
        } catch (Throwable $throwable) {
            $connection->rollBack();
            throw $throwable;
        }

        $data->lpId = (int) $lp->getIid();
        $data->saved = true;

        return $data;
    }

    private function updatePrerequisites(
        LearningPathBuilderPrerequisiteInput $data,
        CLp $lp,
    ): LearningPathBuilderPrerequisiteInput {
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);
        $items = array_values($this->getItemsById($lp));

        if ('clear' === $data->action) {
            foreach ($items as $item) {
                $item->setPrerequisite('');
                $this->entityManager->persist($item);
            }

            $this->entityManager->createQueryBuilder()
                ->update(CLpItem::class, 'item')
                ->set('item.masteryScore', 'NULL')
                ->andWhere('item.lp = :lpId')
                ->andWhere('item.itemType = :quizType')
                ->setParameter('lpId', (int) $lp->getIid())
                ->setParameter('quizType', 'quiz')
                ->getQuery()
                ->execute()
            ;
            $this->touchLearningPath($lp);
            $this->entityManager->flush();
            $data->lpId = (int) $lp->getIid();
            $data->saved = true;

            return $data;
        }

        if ('set_previous' !== $data->action) {
            throw new BadRequestHttpException('Invalid prerequisite action.');
        }

        $lastNonSection = null;
        foreach ($items as $item) {
            if ('dir' === $item->getItemType()) {
                continue;
            }

            if ($lastNonSection instanceof CLpItem) {
                $item->setPrerequisite((string) $lastNonSection->getIid());
                if ('quiz' === $lastNonSection->getItemType()) {
                    $maxScore = $this->getCurrentItemMaxScore($lastNonSection);
                    $lastNonSection
                        ->setMaxScore($maxScore)
                        ->setMasteryScore($maxScore)
                    ;
                    $this->entityManager->persist($lastNonSection);
                }
            } else {
                $item->setPrerequisite('');
            }

            $this->entityManager->persist($item);
            $lastNonSection = $item;
        }

        $this->touchLearningPath($lp);
        $this->entityManager->flush();
        $data->lpId = (int) $lp->getIid();
        $data->saved = true;

        return $data;
    }

    private function hydrateItemUpdateInput(
        LearningPathBuilderItemUpdateInput $data,
        Request $request,
    ): void {
        $rawPayload = $request->request->get('payload');
        if (!\is_string($rawPayload) || '' === trim($rawPayload)) {
            throw new BadRequestHttpException('Learning path item payload is required.');
        }

        $payload = json_decode($rawPayload, true);
        if (!\is_array($payload)) {
            throw new BadRequestHttpException('Invalid learning path item payload.');
        }

        $data->lpId = (int) ($payload['lpId'] ?? 0);
        $data->title = (string) ($payload['title'] ?? '');
        $data->parentId = isset($payload['parentId']) ? (int) $payload['parentId'] : null;
        $data->content = array_key_exists('content', $payload) && null !== $payload['content']
            ? (string) $payload['content']
            : null;
        $data->exportAllowed = filter_var($payload['exportAllowed'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data->extraFields = \is_array($payload['extraFields'] ?? null) ? $payload['extraFields'] : [];
        $data->csrfToken = (string) ($payload['csrfToken'] ?? '');
    }

    private function updateBulkAuthorPrice(
        LearningPathBuilderBulkAuthorPriceInput $data,
        CLp $lp,
    ): LearningPathBuilderBulkAuthorPriceInput {
        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedHttpException('Only platform administrators can update learning path authors and prices.');
        }

        $itemIds = array_values(array_unique(array_filter(array_map(
            static fn (mixed $itemId): int => (int) $itemId,
            $data->itemIds,
        ), static fn (int $itemId): bool => $itemId > 0)));
        if ([] === $itemIds) {
            throw new BadRequestHttpException('Select at least one learning path item.');
        }

        /** @var CLpItem[] $items */
        $items = $this->lpItemRepository->createQueryBuilder('item')
            ->andWhere('item.lp = :lpId')
            ->andWhere('item.iid IN (:itemIds)')
            ->andWhere('item.itemType != :rootType')
            ->setParameter('lpId', (int) $lp->getIid(), Types::INTEGER)
            ->setParameter('itemIds', $itemIds, ArrayParameterType::INTEGER)
            ->setParameter('rootType', 'root', Types::STRING)
            ->getQuery()
            ->getResult()
        ;
        if (count($items) !== count($itemIds)) {
            throw new BadRequestHttpException('One or more learning path items are invalid.');
        }

        $authorIds = array_values(array_unique(array_filter(array_map(
            static fn (mixed $authorId): int => (int) $authorId,
            $data->authorIds,
        ), static fn (int $authorId): bool => $authorId > 0)));
        if ($data->removeAuthors) {
            $authorIds = [];
        }

        $updateAuthors = $data->removeAuthors || [] !== $authorIds;
        $updatePrice = null !== $data->price && $data->price > 0;
        if (!$updateAuthors && !$updatePrice) {
            throw new BadRequestHttpException('Select authors to update, remove authors, or enter a price greater than zero.');
        }

        $authorFlagField = $this->extraFieldRepository->findByVariable(
            ExtraField::USER_FIELD_TYPE,
            'authorlp',
        );
        $itemAuthorField = $this->extraFieldRepository->findByVariable(
            ExtraField::LP_ITEM_FIELD_TYPE,
            'authorlpitem',
        );
        $priceField = $this->extraFieldRepository->findByVariable(
            ExtraField::LP_ITEM_FIELD_TYPE,
            'price',
        );

        if ($updateAuthors
            && (!($authorFlagField instanceof ExtraField) || !($itemAuthorField instanceof ExtraField))
        ) {
            throw new BadRequestHttpException('Learning path author extra fields are not configured.');
        }
        if ($updatePrice && !($priceField instanceof ExtraField)) {
            throw new BadRequestHttpException('Learning path item price extra field is not configured.');
        }

        if ([] !== $authorIds && $authorFlagField instanceof ExtraField && null !== $authorFlagField->getId()) {
            $allowedAuthorRows = $this->entityManager->createQueryBuilder()
                ->select('extraFieldValue.itemId AS itemId')
                ->from(ExtraFieldValues::class, 'extraFieldValue')
                ->andWhere('IDENTITY(extraFieldValue.field) = :fieldId')
                ->andWhere('extraFieldValue.fieldValue = :enabledValue')
                ->andWhere('extraFieldValue.itemId IN (:authorIds)')
                ->setParameter('fieldId', (int) $authorFlagField->getId(), Types::INTEGER)
                ->setParameter('enabledValue', '1', Types::STRING)
                ->setParameter('authorIds', $authorIds, ArrayParameterType::INTEGER)
                ->getQuery()
                ->getArrayResult()
            ;
            $allowedAuthorIds = array_values(array_unique(array_map(
                static fn (array $row): int => (int) ($row['itemId'] ?? 0),
                $allowedAuthorRows,
            )));
            /** @var User[] $authors */
            $authors = $this->entityManager->getRepository(User::class)->findBy(['id' => $authorIds]);
            if (count($allowedAuthorIds) !== count($authorIds) || count($authors) !== count($authorIds)) {
                throw new BadRequestHttpException('One or more selected authors are invalid.');
            }
        }

        $authorValue = $data->removeAuthors ? '0' : implode(';', $authorIds);
        $priceValue = $updatePrice ? (string) $data->price : null;
        foreach ($items as $item) {
            $itemId = (int) $item->getIid();
            if ($updateAuthors && $itemAuthorField instanceof ExtraField) {
                $this->saveItemExtraFieldValue($itemAuthorField, $itemId, $authorValue);
            }
            if ($updatePrice && $priceField instanceof ExtraField) {
                $this->saveItemExtraFieldValue($priceField, $itemId, $priceValue);
            }
        }

        $this->touchLearningPath($lp);
        $this->entityManager->flush();

        return $data;
    }

    /** @param array<array-key, mixed> $submitted */
    private function saveItemExtraFields(int $itemId, array $submitted, Request $request): void
    {
        foreach ($this->extraFieldRepository->getExtraFields(ExtraField::LP_ITEM_FIELD_TYPE) as $field) {
            if (!$field instanceof ExtraField || null === $field->getId() || !$this->itemExtraFieldAllowed($field)) {
                continue;
            }

            $fieldId = (int) $field->getId();
            if (\in_array($field->getValueType(), self::FILE_EXTRA_FIELD_TYPES, true)) {
                $file = $request->files->get('extraFile_'.$fieldId);
                if ($file instanceof UploadedFile) {
                    $this->saveItemExtraFieldFile($field, $itemId, $file);
                }

                continue;
            }

            $value = $submitted[(string) $fieldId] ?? $submitted[$fieldId] ?? null;
            $this->saveItemExtraFieldValue(
                $field,
                $itemId,
                $this->normalizeItemExtraFieldValue($field, $value),
            );
        }
    }

    private function saveItemExtraFieldValue(ExtraField $field, int $itemId, ?string $value): void
    {
        $stored = $this->entityManager->getRepository(ExtraFieldValues::class)->findOneBy([
            'field' => $field,
            'itemId' => $itemId,
        ]);
        if (!$stored instanceof ExtraFieldValues) {
            $stored = (new ExtraFieldValues())->setField($field)->setItemId($itemId);
        }

        $stored->setFieldValue($value);
        $this->entityManager->persist($stored);
    }

    private function saveItemExtraFieldFile(ExtraField $field, int $itemId, UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new BadRequestHttpException('Invalid extra field file.');
        }
        if (ExtraField::FIELD_TYPE_FILE_IMAGE === $field->getValueType()
            && !\in_array((string) $file->getMimeType(), ['image/jpeg', 'image/png', 'image/gif'], true)
        ) {
            throw new BadRequestHttpException('Only PNG, JPG or GIF images are allowed.');
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $file->getClientOriginalName()) ?: 'file';
        $asset = (new Asset())
            ->setCategory(Asset::EXTRA_FIELD)
            ->setTitle('lp_item_'.$itemId.'_'.$safeName)
            ->setFile($file)
        ;
        $this->entityManager->persist($asset);

        $stored = $this->entityManager->getRepository(ExtraFieldValues::class)->findOneBy([
            'field' => $field,
            'itemId' => $itemId,
        ]);
        if (!$stored instanceof ExtraFieldValues) {
            $stored = (new ExtraFieldValues())->setField($field)->setItemId($itemId);
        }

        $oldAsset = $stored->getAsset();
        $stored->setFieldValue('1')->setAsset($asset);
        $this->entityManager->persist($stored);
        $this->entityManager->flush();

        if (null !== $oldAsset) {
            $this->entityManager->remove($oldAsset);
        }
    }

    private function normalizeItemExtraFieldValue(ExtraField $field, mixed $value): string
    {
        if (ExtraField::FIELD_TYPE_CHECKBOX === $field->getValueType()) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }
        if (\is_array($value)) {
            return implode(';', array_map(static fn (mixed $item): string => trim((string) $item), $value));
        }
        if (null === $value) {
            return '';
        }

        $value = trim((string) $value);
        if (ExtraField::FIELD_TYPE_INTEGER === $field->getValueType()) {
            return (string) (int) $value;
        }
        if (ExtraField::FIELD_TYPE_FLOAT === $field->getValueType()) {
            return (string) (float) $value;
        }

        return $value;
    }

    private function itemExtraFieldAllowed(ExtraField $field): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return !\in_array($field->getVariable(), ['authors', 'authorlp', 'authorlpitem', 'price'], true);
    }

    private function getCurrentItemMaxScore(CLpItem $item): float
    {
        if ('quiz' !== $item->getItemType()) {
            return (float) ($item->getMaxScore() ?? 0.0);
        }

        $path = trim((string) $item->getPath());
        $quizId = ctype_digit($path) ? (int) $path : 0;
        $quiz = $quizId > 0 ? $this->entityManager->getRepository(CQuiz::class)->find($quizId) : null;

        return $quiz instanceof CQuiz ? (float) $quiz->getMaxScore() : (float) ($item->getMaxScore() ?? 0.0);
    }

    private function isAudioResourceFile(ResourceFile $resourceFile): bool
    {
        $mimeType = strtolower(trim((string) $resourceFile->getMimeType()));
        if (str_starts_with($mimeType, 'audio/')) {
            return true;
        }

        $originalName = strtolower(trim((string) $resourceFile->getOriginalName()));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        return \in_array($extension, self::AUDIO_EXTENSIONS, true);
    }

    private function documentAllowsPdfExport(CDocument $document): bool
    {
        $resourceNode = $document->getResourceNode();
        if (null === $resourceNode) {
            return false;
        }

        $mimeType = strtolower(trim((string) $resourceNode->getFirstResourceFile()?->getMimeType()));

        return $resourceNode->hasEditableTextContent()
            || \in_array($mimeType, ['text/html', 'application/xhtml+xml'], true);
    }

    private function touchLearningPath(CLp $lp): void
    {
        $lp->setModifiedOn(new DateTime());
        $this->entityManager->persist($lp);
    }

    private function findValidatedResource(
        string $resourceType,
        int $resourceId,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): AbstractResource {
        $class = match ($resourceType) {
            'document', 'video' => CDocument::class,
            'quiz' => CQuiz::class,
            'link' => CLink::class,
            'student_publication' => CStudentPublication::class,
            'forum' => CForum::class,
            'thread' => CForumThread::class,
            'survey' => CSurvey::class,
            default => throw new BadRequestHttpException('Unsupported learning path resource type.'),
        };

        $resource = $this->entityManager->getRepository($class)->find($resourceId);
        if (!$resource instanceof AbstractResource) {
            throw new NotFoundHttpException('Learning path resource not found.');
        }

        if (!$this->getContextResourceLink($resource, $course, $session, $group) instanceof ResourceLink) {
            throw new AccessDeniedHttpException('The resource is not linked to the current context.');
        }

        if ($resource instanceof CStudentPublication && null !== $resource->getPublicationParent()) {
            throw new BadRequestHttpException('Only assignment roots can be added to a learning path.');
        }

        return $resource;
    }

    private function resolveItemType(string $resourceType, AbstractResource $resource): string
    {
        if ($resource instanceof CDocument && 'video' === strtolower($resource->getFiletype())) {
            return 'video';
        }

        return match ($resourceType) {
            'document', 'video', 'quiz', 'link', 'student_publication', 'forum', 'thread', 'survey' => $resourceType,
            default => throw new BadRequestHttpException('Unsupported learning path item type.'),
        };
    }

    private function resourceTitle(AbstractResource $resource): string
    {
        $title = match (true) {
            $resource instanceof CDocument,
            $resource instanceof CQuiz,
            $resource instanceof CLink,
            $resource instanceof CStudentPublication,
            $resource instanceof CForum,
            $resource instanceof CForumThread,
            $resource instanceof CSurvey => $resource->getTitle(),
            default => throw new BadRequestHttpException('The selected resource has no title.'),
        };

        $title = trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5));
        if ('' === $title) {
            throw new BadRequestHttpException('The selected resource has no title.');
        }

        return $title;
    }

    /**
     * @param array<int, array{id?: mixed, parentId?: mixed}> $order
     * @param array<int, CLpItem>                            $items
     *
     * @return array<int, array{id:int, parentId:int|null}>
     */
    private function validateAndNormalizeOrder(array $order, array $items): array
    {
        if (\count($order) !== \count($items)) {
            throw new BadRequestHttpException('The complete learning path item order is required.');
        }

        $normalized = [];
        $seen = [];

        foreach ($order as $entry) {
            if (!\is_array($entry)) {
                throw new BadRequestHttpException('Invalid item order.');
            }

            if (!isset($entry['id'])) {
                throw new BadRequestHttpException('Invalid learning path item.');
            }

            $itemId = (int) $entry['id'];
            $parentId = isset($entry['parentId']) && null !== $entry['parentId']
                ? (int) $entry['parentId']
                : null;

            if ($itemId <= 0 || !isset($items[$itemId]) || isset($seen[$itemId])) {
                throw new BadRequestHttpException('Invalid or duplicated learning path item.');
            }
            if (null !== $parentId && !isset($items[$parentId])) {
                throw new BadRequestHttpException('Invalid parent learning path item.');
            }
            if ($itemId === $parentId) {
                throw new BadRequestHttpException('An item cannot be its own parent.');
            }
            if (null !== $parentId && 'dir' !== $items[$parentId]->getItemType()) {
                throw new BadRequestHttpException('Only a section can contain other learning path items.');
            }

            $seen[$itemId] = true;
            $normalized[] = ['id' => $itemId, 'parentId' => $parentId];
        }

        $parents = [];
        foreach ($normalized as $entry) {
            $parents[$entry['id']] = $entry['parentId'];
        }
        foreach (array_keys($parents) as $itemId) {
            $visited = [];
            $parentId = $parents[$itemId];
            while (null !== $parentId) {
                if ($parentId === $itemId || isset($visited[$parentId])) {
                    throw new BadRequestHttpException('The learning path item hierarchy contains a cycle.');
                }
                $visited[$parentId] = true;
                $parentId = $parents[$parentId] ?? null;
            }
        }

        foreach ($normalized as $index => $entry) {
            $item = $items[$entry['id']];
            if ('final_item' !== $item->getItemType()) {
                continue;
            }
            if (null !== $entry['parentId'] || $index !== array_key_last($normalized)) {
                throw new BadRequestHttpException('The final item must remain at the end of the learning path.');
            }
        }

        return $normalized;
    }

    /** @return array<int, CLpItem> */
    private function getItemsById(CLp $lp): array
    {
        /** @var CLpItem[] $items */
        $items = $this->lpItemRepository->createQueryBuilder('item')
            ->andWhere('item.lp = :lpId')
            ->andWhere('item.itemType != :rootType')
            ->setParameter('lpId', (int) $lp->getIid())
            ->setParameter('rootType', 'root')
            ->orderBy('item.displayOrder', 'ASC')
            ->addOrderBy('item.iid', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $indexed = [];
        foreach ($items as $item) {
            $indexed[(int) $item->getIid()] = $item;
        }

        return $indexed;
    }

    private function normalizeCurrentOrder(CLp $lp, CLpItem $root): void
    {
        $items = $this->getItemsById($lp);
        $orderedItems = [];
        $finalItem = null;
        foreach ($items as $item) {
            if ('final_item' === $item->getItemType()) {
                $finalItem = $item;
                continue;
            }
            $orderedItems[] = $item;
        }
        if ($finalItem instanceof CLpItem) {
            $finalItem->setParent($root);
            $orderedItems[] = $finalItem;
        }

        $position = 2;
        foreach ($orderedItems as $item) {
            $item
                ->setRoot($root)
                ->setDisplayOrder($position)
                ->setPreviousItemId(null)
                ->setNextItemId(null)
            ;
            $this->entityManager->persist($item);
            ++$position;
        }

        $this->entityManager->flush();
        $this->recoverTree($root);
    }

    private function recoverTree(CLpItem $root): void
    {
        $this->lpItemRepository->recoverNode($root, 'displayOrder');
        $this->entityManager->flush();
    }

    private function nextDisplayOrder(CLp $lp): int
    {
        $maximum = $this->lpItemRepository->createQueryBuilder('item')
            ->select('MAX(item.displayOrder)')
            ->andWhere('item.lp = :lpId')
            ->setParameter('lpId', (int) $lp->getIid())
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $maximum + 1;
    }

    private function writeDocumentContent(CDocument $document, string $content): void
    {
        $resourceNode = $document->getResourceNode();
        if (null === $resourceNode) {
            throw new BadRequestHttpException('The document resource node is missing.');
        }

        $resourceFile = $resourceNode->getFirstResourceFile();
        if (!$resourceFile instanceof ResourceFile) {
            throw new BadRequestHttpException('The document resource file is missing.');
        }

        $filename = $this->resourceNodeRepository->getFilename($resourceFile);
        if (null === $filename || '' === trim($filename)) {
            throw new BadRequestHttpException('The document storage path is missing.');
        }

        try {
            $this->resourceNodeRepository->getFileSystem()->write($filename, $content);
        } catch (Throwable $throwable) {
            throw new BadRequestHttpException('The document content could not be stored.', $throwable);
        }

        $resourceNode->setContent($content);
        $resourceFile->setSize(\strlen($content));
        $this->entityManager->persist($resourceNode);
        $this->entityManager->persist($resourceFile);
    }

    private function resolveParent(CLp $lp, CLpItem $root, ?int $parentId): CLpItem
    {
        if (null === $parentId || $parentId <= 0 || $parentId === $root->getIid()) {
            return $root;
        }

        $parent = $this->findValidatedItem($lp, $parentId);
        if ('dir' !== $parent->getItemType()) {
            throw new BadRequestHttpException('Only a section can contain other learning path items.');
        }

        return $parent;
    }

    private function findValidatedItem(CLp $lp, int $itemId): CLpItem
    {
        $item = $this->lpItemRepository->find($itemId);
        if (!$item instanceof CLpItem || $item->getLp()->getIid() !== $lp->getIid()) {
            throw new NotFoundHttpException('Learning path item not found.');
        }

        return $item;
    }

    private function assertValidParent(CLpItem $item, CLpItem $parent): void
    {
        $candidate = $parent;
        while (null !== $candidate) {
            if ($candidate->getIid() === $item->getIid()) {
                throw new BadRequestHttpException('A section cannot be moved inside itself or one of its descendants.');
            }
            $candidate = $candidate->getParent();
        }
    }

    private function assertEditableItem(CLpItem $item): void
    {
        if ('root' === $item->getItemType() || 'final_item' === $item->getItemType()) {
            throw new BadRequestHttpException('This learning path item cannot be changed here.');
        }
    }

    private function sanitizeTitle(string $title): string
    {
        $title = trim($title);
        if ('' === trim(strip_tags($title))) {
            throw new BadRequestHttpException('The title is required.');
        }

        if ($this->settingEnabled('editor.save_titles_as_html')) {
            return strip_tags($title, '<b><strong><i><em><u><sub><sup><span><br>');
        }

        return trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5));
    }

    private function settingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name);

        return true === $value || 1 === $value || '1' === $value || 'true' === strtolower((string) $value);
    }
}
