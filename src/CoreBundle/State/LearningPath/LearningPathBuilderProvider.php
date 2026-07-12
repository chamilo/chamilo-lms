<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathBuilder;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\GradebookCategory;
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
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProviderInterface<LearningPathBuilder> */
final readonly class LearningPathBuilderProvider implements ProviderInterface
{
    use LearningPathStateHelperTrait;

    private const ACTION_TOKEN_INTENTION = 'learning_path_action';
    private const AUDIO_EXTENSIONS = ['aac', 'm4a', 'mp3', 'ogg', 'wav', 'webm'];
    private const DEFAULT_FINAL_ITEM_CONTENT = '<div>Congratulations! You have finished this learning path</div>((certificate)) <br />((skill))';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SettingsManager $settingsManager,
        private CLpRepository $lpRepository,
        private CLpItemRepository $lpItemRepository,
        private CDocumentRepository $documentRepository,
        private ExtraFieldRepository $extraFieldRepository,
        private ResourceNodeRepository $resourceNodeRepository,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): LearningPathBuilder
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->assertLearningPathTeacher($this->security);
        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);

        $lpId = (int) ($uriVariables['lpId'] ?? 0);
        $lp = $this->lpRepository->find($lpId);
        if (!$lp instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }

        $this->getEditableResourceLink($lp, $course, $session, $group, $this->security);

        $result = new LearningPathBuilder();
        $result->lpId = (int) $lp->getIid();
        $result->title = $this->plainTitle($lp->getTitle());
        $result->lpType = $lp->getLpType();
        $result->canManageStructure = CLp::LP_TYPE === $lp->getLpType();
        $result->titleAsHtml = $this->settingEnabled('editor.save_titles_as_html');
        $result->csrfToken = $this->csrfTokenManager->getToken(self::ACTION_TOKEN_INTENTION)->getValue();
        $documents = [];
        foreach ($this->findContextResources(CDocument::class, $course, $session, $group) as $resource) {
            if ($resource instanceof CDocument) {
                $documents[] = $resource;
            }
        }
        $result->items = $this->buildTree($lpId, $course, $session, $group, $documents);
        $result->resources = $this->buildResources($course, $session, $group, $documents);
        $documentsRoot = $this->documentRepository->getCourseDocumentsRootNode($course);
        $result->documentsRootNodeId = (int) ($documentsRoot?->getId() ?? 0);
        $result->searchEnabled = $this->settingEnabled('search.search_enabled');
        $result->certificate = $this->buildCertificate($lp, $course, $session, $group);
        $result->bulkAuthorPrice = $this->buildBulkAuthorPrice($lpId);

        return $result;
    }

    /**
     * @param CDocument[] $documents
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildTree(
        int $lpId,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        array $documents,
    ): array {
        /** @var CLpItem[] $items */
        $items = $this->lpItemRepository->createQueryBuilder('item')
            ->andWhere('item.lp = :lpId')
            ->andWhere('item.itemType != :rootType')
            ->setParameter('lpId', $lpId)
            ->setParameter('rootType', 'root')
            ->orderBy('item.displayOrder', 'ASC')
            ->addOrderBy('item.iid', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        $audioDocumentsByReference = [];
        foreach ($documents as $document) {
            if (!$this->isAudioDocument($document)) {
                continue;
            }

            foreach ($this->getAudioDocumentReferences($document) as $reference) {
                if (!array_key_exists($reference, $audioDocumentsByReference)) {
                    $audioDocumentsByReference[$reference] = $document;
                    continue;
                }

                if ($audioDocumentsByReference[$reference] !== $document) {
                    $audioDocumentsByReference[$reference] = null;
                }
            }
        }

        $itemExtraFields = $this->buildItemExtraFieldDefinitions($items);
        $rows = [];
        foreach ($items as $item) {
            $itemId = (int) $item->getIid();
            $parent = $item->getParent();
            $parentId = $parent instanceof CLpItem && 'root' !== $parent->getItemType()
                ? (int) $parent->getIid()
                : null;
            $type = trim($item->getItemType());
            $path = trim((string) $item->getPath());
            $resourceId = ctype_digit($path) ? (int) $path : 0;
            $forumId = 0;
            $editableContent = false;
            $exportConfigurable = false;
            $content = '';
            $documentParentResourceNodeId = 0;
            $resourceUrl = '';
            $audioPath = trim((string) $item->getAudio());
            $audioReference = $this->normalizeAudioReference($audioPath);
            $audioDocument = $audioDocumentsByReference[$audioReference] ?? null;
            $audioDocumentId = $audioDocument instanceof CDocument ? (int) $audioDocument->getIid() : 0;
            $audioTitle = $audioDocument instanceof CDocument ? $this->plainTitle($audioDocument->getTitle()) : '';
            $audioUrl = $audioDocument instanceof CDocument
                ? $this->buildDocumentResourceUrl($audioDocument, $course, $session, $group)
                : '';
            $maxScore = (float) ($item->getMaxScore() ?? 0.0);

            if ('thread' === $type && $resourceId > 0) {
                $thread = $this->entityManager->getRepository(CForumThread::class)->find($resourceId);
                if ($thread instanceof CForumThread && $thread->getForum() instanceof CForum) {
                    $forumId = (int) $thread->getForum()->getIid();
                }
            }

            if ('link' === $type && $resourceId > 0) {
                $link = $this->entityManager->getRepository(CLink::class)->find($resourceId);
                if ($link instanceof CLink
                    && $this->getContextResourceLink($link, $course, $session, $group) instanceof ResourceLink
                ) {
                    $resourceUrl = (string) $link->getUrl();
                }
            }

            if ('quiz' === $type && $resourceId > 0) {
                $quiz = $this->entityManager->getRepository(CQuiz::class)->find($resourceId);
                if ($quiz instanceof CQuiz
                    && $this->getContextResourceLink($quiz, $course, $session, $group) instanceof ResourceLink
                ) {
                    $maxScore = (float) $quiz->getMaxScore();
                }
            }

            if (\in_array($type, ['document', 'video', 'readout_text', 'final_item'], true) && $resourceId > 0) {
                $document = $this->documentRepository->find($resourceId);
                if ($document instanceof CDocument
                    && $this->getContextResourceLink($document, $course, $session, $group) instanceof ResourceLink
                ) {
                    $resourceNode = $document->getResourceNode();
                    $resourceFile = $resourceNode?->getFirstResourceFile();
                    $mimeType = strtolower(trim((string) $resourceFile?->getMimeType()));
                    $editableContent = null !== $resourceNode && $resourceNode->hasEditableTextContent();
                    $exportConfigurable = 'document' === $type && (
                        $editableContent
                        || \in_array($mimeType, ['text/html', 'application/xhtml+xml'], true)
                    );
                    if ($editableContent) {
                        $content = $this->getDocumentContent($document);
                    }
                    $documentParentResourceNodeId = (int) ($resourceNode?->getParent()?->getId() ?? 0);
                }
            }

            $prerequisite = trim((string) $item->getPrerequisite());
            $rows[$itemId] = [
                'id' => $itemId,
                'title' => $item->getTitle(),
                'displayTitle' => $this->plainTitle($item->getTitle()),
                'description' => $this->plainTitle((string) $item->getDescription()),
                'itemType' => $type,
                'path' => $item->getPath(),
                'resourceId' => $resourceId,
                'resourceType' => $this->normalizeResourceType($type),
                'forumId' => $forumId,
                'parentId' => $parentId,
                'level' => (int) ($item->getLvl() ?? 0),
                'displayOrder' => (int) $item->getDisplayOrder(),
                'hasPrerequisite' => '' !== $prerequisite && '0' !== $prerequisite,
                'prerequisiteId' => ctype_digit($prerequisite) ? (int) $prerequisite : 0,
                'prerequisiteMinScore' => (float) $item->getPrerequisiteMinScore(),
                'prerequisiteMaxScore' => (float) $item->getPrerequisiteMaxScore(),
                'maxScore' => $maxScore,
                'masteryScore' => (float) $item->getMasteryScore(),
                'editableContent' => $editableContent,
                'exportConfigurable' => $exportConfigurable,
                'content' => $content,
                'documentParentResourceNodeId' => $documentParentResourceNodeId,
                'resourceUrl' => $resourceUrl,
                'hasAudio' => '' !== $audioPath,
                'audioDocumentId' => $audioDocumentId,
                'audioTitle' => $audioTitle,
                'audioUrl' => $audioUrl,
                'exportAllowed' => $item->isExportAllowed(),
                'isSection' => 'dir' === $type,
                'isFinal' => 'final_item' === $type,
                'extraFields' => $itemExtraFields[$itemId] ?? [],
                'children' => [],
            ];
        }

        $tree = [];
        foreach (array_keys($rows) as $itemId) {
            $parentId = $rows[$itemId]['parentId'];
            if (\is_int($parentId) && isset($rows[$parentId])) {
                $rows[$parentId]['children'][] = &$rows[$itemId];
                continue;
            }

            $tree[] = &$rows[$itemId];
        }

        return $tree;
    }

    /**
     * @param CLpItem[] $items
     *
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function buildItemExtraFieldDefinitions(array $items): array
    {
        $fields = array_values(array_filter(
            $this->extraFieldRepository->getExtraFields(ExtraField::LP_ITEM_FIELD_TYPE),
            fn (ExtraField $field): bool => $this->itemExtraFieldAllowed($field),
        ));
        if ([] === $fields || [] === $items) {
            return [];
        }

        $itemIds = array_values(array_map(
            static fn (CLpItem $item): int => (int) $item->getIid(),
            $items,
        ));
        $fieldIds = array_values(array_filter(array_map(
            static fn (ExtraField $field): int => (int) $field->getId(),
            $fields,
        )));

        $valuesByItemAndField = [];
        if ([] !== $itemIds && [] !== $fieldIds) {
            /** @var ExtraFieldValues[] $storedValues */
            $storedValues = $this->entityManager->createQueryBuilder()
                ->select('extraFieldValue')
                ->addSelect('field')
                ->from(ExtraFieldValues::class, 'extraFieldValue')
                ->innerJoin('extraFieldValue.field', 'field')
                ->andWhere('extraFieldValue.itemId IN (:itemIds)')
                ->andWhere('field.id IN (:fieldIds)')
                ->setParameter('itemIds', $itemIds, ArrayParameterType::INTEGER)
                ->setParameter('fieldIds', $fieldIds, ArrayParameterType::INTEGER)
                ->getQuery()
                ->getResult()
            ;

            foreach ($storedValues as $storedValue) {
                $valuesByItemAndField[$storedValue->getItemId()][
                    (int) $storedValue->getField()->getId()
                ] = $storedValue;
            }
        }

        $definitionsByItem = [];
        foreach ($itemIds as $itemId) {
            foreach ($fields as $field) {
                if (!$field instanceof ExtraField || null === $field->getId()) {
                    continue;
                }

                $storedValue = $valuesByItemAndField[$itemId][(int) $field->getId()] ?? null;
                $value = $storedValue instanceof ExtraFieldValues
                    ? ($storedValue->getFieldValue() ?? '')
                    : ($field->getDefaultValue() ?? '');
                $options = [];
                foreach ($field->getOptions() as $option) {
                    $options[] = [
                        'label' => $option->getDisplayText() ?: (string) $option->getValue(),
                        'value' => (string) $option->getValue(),
                    ];
                }

                $definitionsByItem[$itemId][] = [
                    'id' => (int) $field->getId(),
                    'variable' => $field->getVariable(),
                    'label' => $field->getDisplayText() ?: $field->getVariable(),
                    'helpText' => $field->getHelperText() ?? '',
                    'valueType' => $field->getValueType(),
                    'value' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE === $field->getValueType()
                        ? array_values(array_filter(
                            explode(';', $value),
                            static fn (string $item): bool => '' !== $item,
                        ))
                        : $value,
                    'options' => $options,
                    'assetName' => $storedValue instanceof ExtraFieldValues
                        ? $storedValue->getAsset()?->getOriginalName()
                        : null,
                ];
            }
        }

        return $definitionsByItem;
    }

    /** @return array<string, mixed> */
    private function buildBulkAuthorPrice(int $lpId): array
    {
        $result = [
            'enabled' => false,
            'authorsEnabled' => false,
            'priceEnabled' => false,
            'authors' => [],
            'values' => [],
        ];
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return $result;
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

        $authorsEnabled = $authorFlagField instanceof ExtraField && $itemAuthorField instanceof ExtraField;
        $priceEnabled = $priceField instanceof ExtraField;
        $result['authorsEnabled'] = $authorsEnabled;
        $result['priceEnabled'] = $priceEnabled;
        $result['enabled'] = $authorsEnabled || $priceEnabled;
        if (!$result['enabled']) {
            return $result;
        }

        /** @var CLpItem[] $items */
        $items = $this->lpItemRepository->createQueryBuilder('item')
            ->andWhere('item.lp = :lpId')
            ->andWhere('item.itemType != :rootType')
            ->setParameter('lpId', $lpId, Types::INTEGER)
            ->setParameter('rootType', 'root', Types::STRING)
            ->orderBy('item.displayOrder', 'ASC')
            ->addOrderBy('item.iid', 'ASC')
            ->getQuery()
            ->getResult()
        ;
        if ([] === $items) {
            return $result;
        }

        $itemIds = array_values(array_map(
            static fn (CLpItem $item): int => (int) $item->getIid(),
            $items,
        ));
        $fieldIds = [];
        if ($itemAuthorField instanceof ExtraField && null !== $itemAuthorField->getId()) {
            $fieldIds[] = (int) $itemAuthorField->getId();
        }
        if ($priceField instanceof ExtraField && null !== $priceField->getId()) {
            $fieldIds[] = (int) $priceField->getId();
        }

        $storedByItemAndField = [];
        if ([] !== $fieldIds) {
            /** @var ExtraFieldValues[] $storedValues */
            $storedValues = $this->entityManager->createQueryBuilder()
                ->select('extraFieldValue')
                ->addSelect('field')
                ->from(ExtraFieldValues::class, 'extraFieldValue')
                ->innerJoin('extraFieldValue.field', 'field')
                ->andWhere('extraFieldValue.itemId IN (:itemIds)')
                ->andWhere('field.id IN (:fieldIds)')
                ->setParameter('itemIds', $itemIds, ArrayParameterType::INTEGER)
                ->setParameter('fieldIds', $fieldIds, ArrayParameterType::INTEGER)
                ->getQuery()
                ->getResult()
            ;
            foreach ($storedValues as $storedValue) {
                $storedByItemAndField[$storedValue->getItemId()][
                    (int) $storedValue->getField()->getId()
                ] = $storedValue;
            }
        }

        $candidateAuthorIds = [];
        if ($authorsEnabled && $authorFlagField instanceof ExtraField && null !== $authorFlagField->getId()) {
            $authorFlagValues = $this->entityManager->createQueryBuilder()
                ->select('extraFieldValue.itemId AS itemId')
                ->from(ExtraFieldValues::class, 'extraFieldValue')
                ->andWhere('IDENTITY(extraFieldValue.field) = :fieldId')
                ->andWhere('extraFieldValue.fieldValue = :enabledValue')
                ->setParameter('fieldId', (int) $authorFlagField->getId(), Types::INTEGER)
                ->setParameter('enabledValue', '1', Types::STRING)
                ->getQuery()
                ->getArrayResult()
            ;
            foreach ($authorFlagValues as $authorFlagValue) {
                $authorId = (int) ($authorFlagValue['itemId'] ?? 0);
                if ($authorId > 0) {
                    $candidateAuthorIds[$authorId] = $authorId;
                }
            }
        }

        $values = [];
        $assignedAuthorIds = [];
        $itemAuthorFieldId = (int) ($itemAuthorField?->getId() ?? 0);
        $priceFieldId = (int) ($priceField?->getId() ?? 0);
        foreach ($itemIds as $itemId) {
            $authorIds = [];
            $storedAuthors = $storedByItemAndField[$itemId][$itemAuthorFieldId] ?? null;
            if ($storedAuthors instanceof ExtraFieldValues) {
                foreach (explode(';', (string) $storedAuthors->getFieldValue()) as $authorId) {
                    $authorId = (int) trim($authorId);
                    if ($authorId > 0) {
                        $authorIds[$authorId] = $authorId;
                        $assignedAuthorIds[$authorId] = $authorId;
                    }
                }
            }

            $storedPrice = $storedByItemAndField[$itemId][$priceFieldId] ?? null;
            $values[$itemId] = [
                'authorIds' => array_values($authorIds),
                'authorNames' => [],
                'price' => $storedPrice instanceof ExtraFieldValues
                    ? trim((string) $storedPrice->getFieldValue())
                    : '',
            ];
        }

        $allUserIds = array_values($candidateAuthorIds + $assignedAuthorIds);
        $usersById = [];
        if ([] !== $allUserIds) {
            /** @var User[] $users */
            $users = $this->entityManager->getRepository(User::class)->createQueryBuilder('user')
                ->andWhere('user.id IN (:userIds)')
                ->setParameter('userIds', $allUserIds, ArrayParameterType::INTEGER)
                ->getQuery()
                ->getResult()
            ;
            foreach ($users as $user) {
                $userId = (int) $user->getId();
                $label = trim($user->getFullName());
                $usersById[$userId] = '' !== $label ? $label : (string) $user->getUsername();
            }
        }

        $authors = [];
        foreach ($candidateAuthorIds as $authorId) {
            if (!isset($usersById[$authorId])) {
                continue;
            }
            $authors[] = [
                'label' => $usersById[$authorId],
                'value' => $authorId,
            ];
        }
        usort(
            $authors,
            static fn (array $left, array $right): int => strcasecmp(
                (string) $left['label'],
                (string) $right['label'],
            ),
        );

        foreach ($values as $itemId => $itemValue) {
            $storedAuthorIds = array_map(static fn (mixed $authorId): int => (int) $authorId, $itemValue['authorIds']);
            $values[$itemId]['authorNames'] = array_values(array_filter(array_map(
                static fn (int $authorId): ?string => $usersById[$authorId] ?? null,
                $storedAuthorIds,
            )));
        }

        $result['authors'] = $authors;
        $result['values'] = $values;

        return $result;
    }

    private function itemExtraFieldAllowed(ExtraField $field): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return !\in_array($field->getVariable(), ['authors', 'authorlp', 'authorlpitem', 'price'], true);
    }

    private function normalizeResourceType(string $itemType): string
    {
        return match ($itemType) {
            'document', 'video', 'quiz', 'link', 'student_publication', 'forum', 'thread', 'survey' => $itemType,
            default => '',
        };
    }

    /** @return array<string, mixed> */
    private function buildCertificate(CLp $lp, Course $course, ?Session $session, ?CGroup $group): array
    {
        $finalItem = $this->lpItemRepository->createQueryBuilder('item')
            ->andWhere('item.lp = :lpId')
            ->andWhere('item.itemType = :itemType')
            ->setParameter('lpId', (int) $lp->getIid())
            ->setParameter('itemType', 'final_item')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        $documentId = 0;
        $content = self::DEFAULT_FINAL_ITEM_CONTENT;
        $title = '';
        $itemId = 0;
        $gradebookCategoryId = null;

        if ($finalItem instanceof CLpItem) {
            $itemId = (int) $finalItem->getIid();
            $title = $this->plainTitle($finalItem->getTitle());
            $documentId = (int) $finalItem->getPath();
            $storedRef = trim($finalItem->getRef());
            if ('' !== $storedRef && ctype_digit($storedRef)) {
                $gradebookCategoryId = (int) $storedRef;
            }

            $document = $this->documentRepository->find($documentId);
            if ($document instanceof CDocument
                && $this->getContextResourceLink($document, $course, $session, $group) instanceof ResourceLink
            ) {
                $savedContent = $this->getDocumentContent($document);
                if ('' !== trim($savedContent)) {
                    $content = $savedContent;
                }
            }
        }

        $categories = [];
        foreach ($this->entityManager->getRepository(GradebookCategory::class)->findBy(
            [
                'course' => $course,
                'session' => $session,
                'gradeModel' => null,
            ],
            ['id' => 'ASC'],
        ) as $category) {
            $categories[] = [
                'value' => (int) $category->getId(),
                'label' => $category->getTitle(),
            ];
        }

        return [
            'exists' => $finalItem instanceof CLpItem,
            'itemId' => $itemId,
            'documentId' => $documentId,
            'title' => $title,
            'content' => $content,
            'defaultContent' => self::DEFAULT_FINAL_ITEM_CONTENT,
            'gradebookCategoryId' => $gradebookCategoryId,
            'gradebookCategories' => $categories,
        ];
    }

    /**
     * @param CDocument[] $documents
     *
     * @return array<string, mixed>
     */
    private function buildResources(
        Course $course,
        ?Session $session,
        ?CGroup $group,
        array $documents,
    ): array {
        $audioRows = [];
        $documentRows = [];
        $videoRows = [];
        $folderRows = [];
        foreach ($documents as $resource) {
            if (!$resource instanceof CDocument) {
                continue;
            }

            $fileType = strtolower(trim($resource->getFiletype()));
            $row = $this->resourceRow($resource, 'document');
            $resourceNode = $resource->getResourceNode();
            $row['fileType'] = $fileType;
            $row['isFolder'] = 'folder' === $fileType;
            $row['canAdd'] = 'folder' !== $fileType;
            $row['resourceNodeId'] = (int) ($resourceNode?->getId() ?? 0);
            $row['parentResourceNodeId'] = (int) ($resourceNode?->getParent()?->getId() ?? 0);
            $row['path'] = (string) ($resourceNode?->getPath() ?? '');
            $row['children'] = [];

            if ($this->isAudioDocument($resource)) {
                $resourceFile = $resourceNode?->getFirstResourceFile();
                $audioRow = $row;
                $audioRow['audioUrl'] = $this->buildDocumentResourceUrl($resource, $course, $session, $group);
                $audioRow['mimeType'] = strtolower(trim((string) $resourceFile?->getMimeType()));
                $audioRow['originalName'] = (string) ($resourceFile?->getOriginalName() ?? '');
                $audioRows[] = $audioRow;
            }

            if ('folder' === $fileType) {
                $folderRows[] = $row;
                continue;
            }

            if ('video' === $fileType) {
                $row['resourceType'] = 'video';
                $videoRows[] = $row;
                continue;
            }

            if (\in_array($fileType, ['file', 'html', 'link'], true)) {
                $documentRows[] = $row;
            }
        }

        $showInvisibleExercises = $this->settingEnabled('lp.show_invisible_exercise_in_lp_toc')
            || $this->settingEnabled('lp.show_invisible_exercise_in_lp_list');
        $tests = [];
        foreach ($this->findContextResources(CQuiz::class, $course, $session, $group) as $resource) {
            if (!$resource instanceof CQuiz) {
                continue;
            }

            $visible = $resource->isVisible($course, $session);
            if (!$visible && !$showInvisibleExercises) {
                continue;
            }

            $row = $this->resourceRow($resource, 'quiz');
            $row['visible'] = $visible;
            $tests[] = $row;
        }

        $linkGroups = [];
        foreach ($this->findContextResources(CLink::class, $course, $session, $group) as $resource) {
            if (!$resource instanceof CLink || !$resource->isVisible($course, $session)) {
                continue;
            }

            $category = $resource->getCategory();
            $categoryId = (int) ($category?->getIid() ?? 0);
            if (!isset($linkGroups[$categoryId])) {
                $linkGroups[$categoryId] = [
                    'id' => 'link-category-'.$categoryId,
                    'title' => null === $category ? '' : $this->plainTitle($category->getTitle()),
                    'titleKey' => null === $category ? 'Uncategorized' : '',
                    'resourceType' => 'link_category',
                    'isFolder' => true,
                    'canAdd' => false,
                    'children' => [],
                ];
            }

            $row = $this->resourceRow($resource, 'link');
            $linkGroups[$categoryId]['children'][] = $row;
        }

        $assignments = [];
        foreach ($this->findContextResources(CStudentPublication::class, $course, $session, $group) as $resource) {
            if (!$resource instanceof CStudentPublication || null !== $resource->getPublicationParent()) {
                continue;
            }
            $assignments[] = $this->resourceRow($resource, 'student_publication');
        }

        $forumRows = [];
        $forumsById = [];
        foreach ($this->findContextResources(CForum::class, $course, $session, $group) as $resource) {
            if (!$resource instanceof CForum || !$resource->isVisible($course, $session)) {
                continue;
            }
            $row = $this->resourceRow($resource, 'forum');
            $row['threads'] = [];
            $forumsById[(int) $resource->getIid()] = \count($forumRows);
            $forumRows[] = $row;
        }

        foreach ($this->findContextResources(CForumThread::class, $course, $session, $group) as $resource) {
            if (!$resource instanceof CForumThread || !$resource->getForum() instanceof CForum) {
                continue;
            }
            $forumId = (int) $resource->getForum()->getIid();
            if (!isset($forumsById[$forumId])) {
                continue;
            }
            $threadRow = $this->resourceRow($resource, 'thread');
            $threadRow['forumId'] = $forumId;
            $forumRows[$forumsById[$forumId]]['threads'][] = $threadRow;
        }

        $surveys = [];
        foreach ($this->findContextResources(CSurvey::class, $course, $session, $group) as $resource) {
            if (!$resource instanceof CSurvey) {
                continue;
            }
            $row = $this->resourceRow($resource, 'survey');
            $row['questionCount'] = $resource->getQuestions()->count();
            $row['canAdd'] = 0 < $row['questionCount'];
            $surveys[] = $row;
        }

        return [
            'documents' => [
                'files' => $this->buildNestedResources([...$folderRows, ...$documentRows]),
                'videos' => $this->pruneEmptyResourceFolders(
                    $this->buildNestedResources([...$folderRows, ...$videoRows]),
                ),
            ],
            'audio' => ['items' => $audioRows],
            'tests' => ['items' => $tests],
            'links' => ['items' => array_values($linkGroups)],
            'assignments' => ['items' => $assignments],
            'forums' => ['items' => $forumRows],
            'sections' => ['items' => []],
            'surveys' => ['items' => $surveys],
            'certificate' => ['items' => []],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildNestedResources(array $rows): array
    {
        $rowsByNode = [];
        foreach (array_keys($rows) as $index) {
            $rows[$index]['children'] = [];
            $resourceNodeId = (int) ($rows[$index]['resourceNodeId'] ?? 0);
            if ($resourceNodeId > 0) {
                $rowsByNode[$resourceNodeId] = &$rows[$index];
            }
        }

        $tree = [];
        foreach (array_keys($rows) as $index) {
            $parentResourceNodeId = (int) ($rows[$index]['parentResourceNodeId'] ?? 0);
            if ($parentResourceNodeId > 0 && isset($rowsByNode[$parentResourceNodeId])) {
                $rowsByNode[$parentResourceNodeId]['children'][] = &$rows[$index];
                continue;
            }

            $tree[] = &$rows[$index];
        }

        return $tree;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     *
     * @return array<int, array<string, mixed>>
     */
    private function pruneEmptyResourceFolders(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $children = $item['children'] ?? [];
            $item['children'] = \is_array($children) ? $this->pruneEmptyResourceFolders($children) : [];

            if (($item['isFolder'] ?? false) && [] === $item['children']) {
                continue;
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param class-string<AbstractResource> $resourceClass
     *
     * @return AbstractResource[]
     */
    private function findContextResources(
        string $resourceClass,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): array {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT resource')
            ->from($resourceClass, 'resource')
            ->innerJoin('resource.resourceNode', 'node')
            ->innerJoin('node.resourceLinks', 'links')
            ->andWhere('IDENTITY(links.course) = :courseId')
            ->andWhere('links.deletedAt IS NULL')
            ->setParameter('courseId', (int) $course->getId())
            ->orderBy('resource.title', 'ASC')
        ;

        if ($session instanceof Session) {
            $queryBuilder
                ->andWhere('(IDENTITY(links.session) = :sessionId OR links.session IS NULL)')
                ->setParameter('sessionId', (int) $session->getId())
            ;
        } else {
            $queryBuilder->andWhere('links.session IS NULL');
        }

        if ($group instanceof CGroup) {
            $queryBuilder
                ->andWhere('IDENTITY(links.group) = :groupId')
                ->setParameter('groupId', (int) $group->getIid())
            ;
        } else {
            $queryBuilder->andWhere('links.group IS NULL');
        }

        /** @var AbstractResource[] $resources */
        $resources = $queryBuilder->getQuery()->getResult();

        return $resources;
    }

    /** @return array<string, mixed> */
    private function resourceRow(AbstractResource $resource, string $resourceType): array
    {
        $id = match (true) {
            $resource instanceof CDocument,
            $resource instanceof CQuiz,
            $resource instanceof CLink,
            $resource instanceof CStudentPublication,
            $resource instanceof CForum,
            $resource instanceof CForumThread,
            $resource instanceof CSurvey => (int) $resource->getIid(),
            default => 0,
        };
        $title = match (true) {
            $resource instanceof CDocument,
            $resource instanceof CQuiz,
            $resource instanceof CLink,
            $resource instanceof CStudentPublication,
            $resource instanceof CForum,
            $resource instanceof CForumThread,
            $resource instanceof CSurvey => $resource->getTitle(),
            default => '',
        };

        return [
            'id' => $id,
            'title' => $this->plainTitle($title),
            'resourceType' => $resourceType,
            'canAdd' => true,
        ];
    }

    /** @return string[] */
    private function getAudioDocumentReferences(CDocument $document): array
    {
        $resourceNode = $document->getResourceNode();
        $resourceFile = $resourceNode?->getFirstResourceFile();
        $references = [];

        $nodePath = trim((string) $resourceNode?->getPath());
        if ('' !== $nodePath) {
            $references[] = $nodePath;
        }

        $fullPath = str_replace('\\', '/', trim($document->getFullPath()));
        $segments = array_values(array_filter(explode('/', trim($fullPath, '/')), static fn (string $part): bool => '' !== $part));
        foreach (array_keys($segments) as $index) {
            $relativePath = implode('/', array_slice($segments, $index));
            $references[] = $relativePath;
            $references[] = '/'.$relativePath;
            $references[] = 'document/'.$relativePath;
        }

        $originalName = trim((string) $resourceFile?->getOriginalName());
        if ('' !== $originalName) {
            $references[] = $originalName;
            $references[] = '/audio/'.$originalName;
            $references[] = 'audio/'.$originalName;
        }

        return array_values(array_unique(array_filter(
            array_map($this->normalizeAudioReference(...), $references),
            static fn (string $reference): bool => '' !== $reference,
        )));
    }

    private function normalizeAudioReference(string $reference): string
    {
        return strtolower(trim(str_replace('\\', '/', $reference)));
    }

    private function isAudioDocument(CDocument $document): bool
    {
        $resourceFile = $document->getResourceNode()?->getFirstResourceFile();
        if (null === $resourceFile) {
            return false;
        }

        $mimeType = strtolower(trim((string) $resourceFile->getMimeType()));
        if (str_starts_with($mimeType, 'audio/')) {
            return true;
        }

        $originalName = strtolower(trim((string) $resourceFile->getOriginalName()));
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        return \in_array($extension, self::AUDIO_EXTENSIONS, true);
    }

    private function buildDocumentResourceUrl(
        CDocument $document,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): string {
        $resourceNode = $document->getResourceNode();
        if (null === $resourceNode || !$resourceNode->hasResourceFile()) {
            return '';
        }

        try {
            return $this->resourceNodeRepository->getResourceFileUrl($resourceNode, [
                'cid' => (int) $course->getId(),
                'sid' => (int) ($session?->getId() ?? 0),
                'gid' => (int) ($group?->getIid() ?? 0),
            ]);
        } catch (FileNotFoundException) {
            return '';
        }
    }

    private function getDocumentContent(CDocument $document): string
    {
        $resourceNode = $document->getResourceNode();

        try {
            $content = (string) $this->documentRepository->getResourceFileContent($document);
        } catch (FileNotFoundException) {
            return (string) ($resourceNode?->getContent() ?? '');
        }

        if ('' !== trim($content)) {
            return $content;
        }

        return (string) ($resourceNode?->getContent() ?? '');
    }

    private function plainTitle(string $title): string
    {
        return trim(html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5));
    }

    private function settingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name);

        return true === $value || 1 === $value || '1' === $value || 'true' === strtolower((string) $value);
    }
}
