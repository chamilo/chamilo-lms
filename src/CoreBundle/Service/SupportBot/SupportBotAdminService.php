<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\SupportBot;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\SupportBotConversation;
use Chamilo\CoreBundle\Entity\SupportBotNode;
use Chamilo\CoreBundle\Entity\SupportBotOption;
use Chamilo\CoreBundle\Entity\SupportBotTree;
use Chamilo\CoreBundle\Entity\TicketCategory;
use Chamilo\CoreBundle\Entity\TicketProject;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use const JSON_THROW_ON_ERROR;

final readonly class SupportBotAdminService
{
    private const array ACTION_TYPES = [
        SupportBotOption::ACTION_GOTO_NODE,
        SupportBotOption::ACTION_OPEN_LINK,
        SupportBotOption::ACTION_ESCALATE_TICKET,
        SupportBotOption::ACTION_END_CONVERSATION,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public function saveTree(?int $treeId, User $actor, array $data): SupportBotTree
    {
        $this->assertFeatureEnabled();
        $accessUrl = $this->getCurrentAccessUrl();
        $this->validateGraphPayload($data);

        if (null !== $treeId && $treeId > 0) {
            $tree = $this->getEditableTree($treeId, $accessUrl);
            if (SupportBotTree::STATUS_DRAFT !== $tree->getStatus()) {
                throw new ConflictHttpException('Published or archived support bot trees are immutable. Duplicate the tree to edit it.');
            }
            if ($this->hasConversations($tree)) {
                throw new ConflictHttpException('A support bot tree with conversations cannot be rewritten.');
            }
        } else {
            $tree = (new SupportBotTree())
                ->setCreatedBy($actor)
                ->setAccessUrl($this->getConfigurationAccessUrl($accessUrl))
                ->setStatus(SupportBotTree::STATUS_DRAFT)
                ->setVersion(1)
            ;
            $this->entityManager->persist($tree);
        }

        $tree
            ->setTitle($this->requiredText($data, 'title', 255, 'The support bot tree title is required.'))
            ->setDescription($this->optionalText($data, 'description'))
            ->setDefaultTicketProject($this->resolveDefaultProject($data, $accessUrl))
            ->setEntryNode(null)
        ;

        foreach ($tree->getNodes()->toArray() as $existingNode) {
            if ($existingNode instanceof SupportBotNode) {
                $tree->removeNode($existingNode);
                $this->entityManager->remove($existingNode);
            }
        }

        /** @var array<string, SupportBotNode> $nodesByRef */
        $nodesByRef = [];
        $rawNodes = $data['nodes'];
        foreach ($rawNodes as $nodeIndex => $rawNode) {
            if (!\is_array($rawNode)) {
                throw new BadRequestHttpException('Every support bot node must be an object.');
            }

            $ref = $this->requiredNodeRef($rawNode);
            $node = (new SupportBotNode())
                ->setContent($this->requiredText($rawNode, 'content', null, 'Every support bot node requires content.'))
                ->setSortOrder((int) $nodeIndex)
            ;
            $tree->addNode($node);
            $nodesByRef[$ref] = $node;
        }

        foreach ($rawNodes as $nodeIndex => $rawNode) {
            $node = $nodesByRef[$this->requiredNodeRef($rawNode)];
            $rawOptions = $rawNode['options'];

            foreach ($rawOptions as $optionIndex => $rawOption) {
                if (!\is_array($rawOption)) {
                    throw new BadRequestHttpException('Every support bot option must be an object.');
                }

                $actionType = (string) ($rawOption['actionType'] ?? '');
                $option = (new SupportBotOption())
                    ->setLabel($this->requiredText($rawOption, 'label', 255, 'Every support bot option requires a label.'))
                    ->setSortOrder((int) $optionIndex)
                    ->setActionType($actionType)
                    ->setTargetNode(null)
                    ->setTargetUrl(null)
                    ->setTicketCategory(null)
                    ->setClosingMessage(null)
                ;

                if (SupportBotOption::ACTION_GOTO_NODE === $actionType) {
                    $targetRef = trim((string) ($rawOption['targetRef'] ?? ''));
                    $targetNode = $nodesByRef[$targetRef] ?? null;
                    if (!$targetNode instanceof SupportBotNode) {
                        throw new BadRequestHttpException('A support bot option points to an unknown target node.');
                    }
                    $option->setTargetNode($targetNode);
                } elseif (SupportBotOption::ACTION_OPEN_LINK === $actionType) {
                    $targetUrl = trim((string) ($rawOption['targetUrl'] ?? ''));
                    if (!$this->isSafeRelativeUrl($targetUrl)) {
                        throw new BadRequestHttpException('Support bot links must use a safe relative Chamilo URL.');
                    }
                    $option->setTargetUrl($targetUrl);
                } elseif (SupportBotOption::ACTION_ESCALATE_TICKET === $actionType) {
                    $defaultProject = $tree->getDefaultTicketProject();
                    if (!$defaultProject instanceof TicketProject) {
                        throw new BadRequestHttpException('A default ticket project is required for support bot escalation.');
                    }
                    $category = $this->resolveTicketCategory($rawOption, $accessUrl);
                    if ($category->getProject()->getId() !== $defaultProject->getId()) {
                        throw new BadRequestHttpException('The support bot ticket category must belong to the default ticket project.');
                    }
                    $option->setTicketCategory($category);
                } elseif (SupportBotOption::ACTION_END_CONVERSATION === $actionType) {
                    $option->setClosingMessage($this->optionalText($rawOption, 'closingMessage'));
                }

                $node->addOption($option);
            }
        }

        $entryRef = trim((string) ($data['entryNodeRef'] ?? ''));
        $entryNode = $nodesByRef[$entryRef] ?? null;
        if (!$entryNode instanceof SupportBotNode) {
            throw new BadRequestHttpException('A valid entry node is required.');
        }
        $tree->setEntryNode($entryNode);

        $this->entityManager->persist($tree);
        $this->entityManager->flush();

        return $tree;
    }

    public function publishTree(int $treeId): SupportBotTree
    {
        $this->assertFeatureEnabled();
        $accessUrl = $this->getCurrentAccessUrl();
        $tree = $this->getEditableTree($treeId, $accessUrl);
        if (SupportBotTree::STATUS_DRAFT !== $tree->getStatus()) {
            throw new ConflictHttpException('Only draft support bot trees can be published.');
        }

        $this->validatePersistedTree($tree, $accessUrl);

        $publishedTrees = $this->entityManager->getRepository(SupportBotTree::class)->findBy([
            'status' => SupportBotTree::STATUS_PUBLISHED,
            'accessUrl' => $tree->getAccessUrl(),
        ]);
        foreach ($publishedTrees as $publishedTree) {
            if (!$publishedTree instanceof SupportBotTree || $publishedTree->getId() === $tree->getId()) {
                continue;
            }
            $publishedTree->setStatus(SupportBotTree::STATUS_ARCHIVED);
        }

        $tree->setStatus(SupportBotTree::STATUS_PUBLISHED);
        $this->entityManager->flush();

        return $tree;
    }

    public function archiveTree(int $treeId): SupportBotTree
    {
        $this->assertFeatureEnabled();
        $accessUrl = $this->getCurrentAccessUrl();
        $tree = $this->getEditableTree($treeId, $accessUrl);
        if (SupportBotTree::STATUS_PUBLISHED !== $tree->getStatus()) {
            throw new ConflictHttpException('Only a published support bot tree can be archived.');
        }

        $tree->setStatus(SupportBotTree::STATUS_ARCHIVED);
        $this->entityManager->flush();

        return $tree;
    }

    public function duplicateTree(int $treeId, User $actor): SupportBotTree
    {
        $this->assertFeatureEnabled();
        $accessUrl = $this->getCurrentAccessUrl();
        $source = $this->getAvailableTree($treeId, $accessUrl);

        $duplicate = (new SupportBotTree())
            ->setTitle($this->copyTitle($source->getTitle()))
            ->setDescription($source->getDescription())
            ->setAccessUrl($this->getConfigurationAccessUrl($accessUrl))
            ->setStatus(SupportBotTree::STATUS_DRAFT)
            ->setVersion($source->getVersion() + 1)
            ->setCreatedBy($actor)
        ;

        $defaultProject = $source->getDefaultTicketProject();
        if ($defaultProject instanceof TicketProject && $this->isAvailableProject($defaultProject, $accessUrl)) {
            $duplicate->setDefaultTicketProject($defaultProject);
        }

        /** @var array<int, SupportBotNode> $nodeMap */
        $nodeMap = [];
        foreach ($source->getNodes() as $sourceNode) {
            if (!$sourceNode instanceof SupportBotNode || null === $sourceNode->getId()) {
                continue;
            }

            $newNode = (new SupportBotNode())
                ->setContent($sourceNode->getContent())
                ->setSortOrder($sourceNode->getSortOrder())
            ;
            $duplicate->addNode($newNode);
            $nodeMap[(int) $sourceNode->getId()] = $newNode;
        }

        foreach ($source->getNodes() as $sourceNode) {
            if (!$sourceNode instanceof SupportBotNode || null === $sourceNode->getId()) {
                continue;
            }
            $newNode = $nodeMap[(int) $sourceNode->getId()];

            foreach ($sourceNode->getOptions() as $sourceOption) {
                if (!$sourceOption instanceof SupportBotOption) {
                    continue;
                }

                $newOption = (new SupportBotOption())
                    ->setLabel($sourceOption->getLabel())
                    ->setSortOrder($sourceOption->getSortOrder())
                    ->setActionType($sourceOption->getActionType())
                    ->setTargetUrl($sourceOption->getTargetUrl())
                    ->setClosingMessage($sourceOption->getClosingMessage())
                ;

                $targetNodeId = $sourceOption->getTargetNode()?->getId();
                if (null !== $targetNodeId && isset($nodeMap[(int) $targetNodeId])) {
                    $newOption->setTargetNode($nodeMap[(int) $targetNodeId]);
                }

                $category = $sourceOption->getTicketCategory();
                if ($category instanceof TicketCategory && $this->isAvailableCategory($category, $accessUrl)) {
                    $newOption->setTicketCategory($category);
                }

                $newNode->addOption($newOption);
            }
        }

        $entryNodeId = $source->getEntryNode()?->getId();
        if (null !== $entryNodeId && isset($nodeMap[(int) $entryNodeId])) {
            $duplicate->setEntryNode($nodeMap[(int) $entryNodeId]);
        }

        $this->entityManager->persist($duplicate);
        $this->entityManager->flush();

        return $duplicate;
    }

    public function deleteTree(int $treeId): void
    {
        $this->assertFeatureEnabled();
        $accessUrl = $this->getCurrentAccessUrl();
        $tree = $this->getEditableTree($treeId, $accessUrl);
        if (SupportBotTree::STATUS_PUBLISHED === $tree->getStatus()) {
            throw new ConflictHttpException('A published support bot tree must be archived before it can be deleted.');
        }
        if ($this->hasConversations($tree)) {
            throw new ConflictHttpException('A support bot tree with conversation history cannot be deleted.');
        }

        $this->entityManager->remove($tree);
        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    public function exportTree(int $treeId): array
    {
        $this->assertFeatureEnabled();
        $accessUrl = $this->getCurrentAccessUrl();
        $tree = $this->getAvailableTree($treeId, $accessUrl);

        /** @var array<int, string> $refsByNodeId */
        $refsByNodeId = [];
        $index = 1;
        foreach ($tree->getNodes() as $node) {
            if ($node instanceof SupportBotNode && null !== $node->getId()) {
                $refsByNodeId[(int) $node->getId()] = 'n'.$index;
                ++$index;
            }
        }

        $nodes = [];
        foreach ($tree->getNodes() as $node) {
            if (!$node instanceof SupportBotNode || null === $node->getId()) {
                continue;
            }
            $options = [];
            foreach ($node->getOptions() as $option) {
                if (!$option instanceof SupportBotOption) {
                    continue;
                }
                $targetNodeId = $option->getTargetNode()?->getId();
                $options[] = array_filter([
                    'label' => $option->getLabel(),
                    'action' => $option->getActionType(),
                    'target' => null !== $targetNodeId ? ($refsByNodeId[(int) $targetNodeId] ?? null) : null,
                    'targetUrl' => $option->getTargetUrl(),
                    'ticketCategory' => $option->getTicketCategory()?->getTitle(),
                    'closingMessage' => $option->getClosingMessage(),
                ], static fn (mixed $value): bool => null !== $value && '' !== $value);
            }

            $nodes[] = [
                'ref' => $refsByNodeId[(int) $node->getId()],
                'content' => $node->getContent(),
                'options' => $options,
            ];
        }

        $entryNodeId = $tree->getEntryNode()?->getId();

        return [
            'tree' => array_filter([
                'title' => $tree->getTitle(),
                'description' => $tree->getDescription(),
                'defaultTicketProject' => $tree->getDefaultTicketProject()?->getTitle(),
            ], static fn (mixed $value): bool => null !== $value && '' !== $value),
            'nodes' => $nodes,
            'entryNode' => null !== $entryNodeId ? ($refsByNodeId[(int) $entryNodeId] ?? null) : null,
        ];
    }

    public function importTree(User $actor, string $contents, string $format): SupportBotTree
    {
        $this->assertFeatureEnabled();
        $contents = trim($contents);
        if ('' === $contents) {
            throw new BadRequestHttpException('The support bot import file is empty.');
        }

        $payload = 'csv' === strtolower($format)
            ? $this->parseCsvImport($contents)
            : $this->parseJsonImport($contents);

        return $this->saveTree(null, $actor, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonImport(string $contents): array
    {
        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new BadRequestHttpException('The support bot JSON import is invalid.', $exception);
        }
        if (!\is_array($decoded)) {
            throw new BadRequestHttpException('The support bot JSON import must contain an object.');
        }

        $treeData = $decoded['tree'] ?? [];
        if (!\is_array($treeData)) {
            throw new BadRequestHttpException('The support bot JSON tree section is invalid.');
        }
        $rawNodes = $decoded['nodes'] ?? [];
        if (!\is_array($rawNodes)) {
            throw new BadRequestHttpException('The support bot JSON nodes section is invalid.');
        }

        $accessUrl = $this->getCurrentAccessUrl();
        $projectTitle = trim((string) ($treeData['defaultTicketProject'] ?? ''));
        $defaultProjectId = '' !== $projectTitle ? $this->resolveProjectIdByTitle($projectTitle, $accessUrl) : null;
        $nodes = [];

        foreach ($rawNodes as $rawNode) {
            if (!\is_array($rawNode)) {
                throw new BadRequestHttpException('Every imported support bot node must be an object.');
            }
            $options = [];
            foreach (($rawNode['options'] ?? []) as $rawOption) {
                if (!\is_array($rawOption)) {
                    throw new BadRequestHttpException('Every imported support bot option must be an object.');
                }
                $action = trim((string) ($rawOption['action'] ?? $rawOption['actionType'] ?? ''));
                $categoryId = null;
                if (SupportBotOption::ACTION_ESCALATE_TICKET === $action) {
                    if (null === $defaultProjectId) {
                        throw new BadRequestHttpException('Imported ticket escalation requires a default ticket project.');
                    }
                    $categoryTitle = trim((string) ($rawOption['ticketCategory'] ?? ''));
                    $categoryId = $this->resolveCategoryIdByTitle($categoryTitle, $defaultProjectId, $accessUrl);
                }
                $options[] = [
                    'label' => (string) ($rawOption['label'] ?? ''),
                    'actionType' => $action,
                    'targetRef' => $rawOption['target'] ?? $rawOption['targetRef'] ?? null,
                    'targetUrl' => $rawOption['targetUrl'] ?? null,
                    'ticketCategoryId' => $categoryId,
                    'closingMessage' => $rawOption['closingMessage'] ?? null,
                ];
            }
            $nodes[] = [
                'ref' => (string) ($rawNode['ref'] ?? ''),
                'content' => (string) ($rawNode['content'] ?? ''),
                'options' => $options,
            ];
        }

        return [
            'title' => (string) ($treeData['title'] ?? 'Imported support bot tree'),
            'description' => $treeData['description'] ?? null,
            'defaultTicketProjectId' => $defaultProjectId,
            'entryNodeRef' => (string) ($decoded['entryNode'] ?? $decoded['entryNodeRef'] ?? ''),
            'nodes' => $nodes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseCsvImport(string $contents): array
    {
        $stream = fopen('php://temp', 'r+');
        if (false === $stream) {
            throw new BadRequestHttpException('The support bot CSV import could not be read.');
        }
        fwrite($stream, $contents);
        rewind($stream);
        $header = fgetcsv($stream, null, ',', '"', '');
        if (!\is_array($header)) {
            fclose($stream);

            throw new BadRequestHttpException('The support bot CSV import requires a header row.');
        }
        $header = array_map(
            static fn (mixed $value): string => trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $value)),
            $header,
        );
        $required = ['node_ref', 'node_content', 'option_label', 'option_action'];
        foreach ($required as $column) {
            if (!\in_array($column, $header, true)) {
                fclose($stream);

                throw new BadRequestHttpException('The support bot CSV import is missing the '.$column.' column.');
            }
        }

        $rows = [];
        while (false !== ($values = fgetcsv($stream, null, ',', '"', ''))) {
            if ([null] === $values || [] === $values) {
                continue;
            }
            $values = array_pad($values, \count($header), '');
            $row = array_combine($header, \array_slice($values, 0, \count($header)));
            if (false !== $row) {
                $rows[] = $row;
            }
        }
        fclose($stream);
        if ([] === $rows) {
            throw new BadRequestHttpException('The support bot CSV import contains no data rows.');
        }

        $accessUrl = $this->getCurrentAccessUrl();
        $first = $rows[0];
        $projectTitle = trim((string) ($first['default_ticket_project'] ?? ''));
        $defaultProjectId = '' !== $projectTitle ? $this->resolveProjectIdByTitle($projectTitle, $accessUrl) : null;
        $nodesByRef = [];
        $nodeOrder = [];
        $entryNodeRef = trim((string) ($first['entry_node'] ?? ''));

        foreach ($rows as $row) {
            $ref = trim((string) ($row['node_ref'] ?? ''));
            if ('' === $ref) {
                throw new BadRequestHttpException('Every imported CSV row requires node_ref.');
            }
            if (!isset($nodesByRef[$ref])) {
                $nodesByRef[$ref] = [
                    'ref' => $ref,
                    'content' => (string) ($row['node_content'] ?? ''),
                    'options' => [],
                ];
                $nodeOrder[] = $ref;
            }
            $action = trim((string) ($row['option_action'] ?? ''));
            $categoryId = null;
            if (SupportBotOption::ACTION_ESCALATE_TICKET === $action) {
                if (null === $defaultProjectId) {
                    throw new BadRequestHttpException('Imported ticket escalation requires a default ticket project.');
                }
                $categoryId = $this->resolveCategoryIdByTitle(
                    trim((string) ($row['option_ticket_category'] ?? '')),
                    $defaultProjectId,
                    $accessUrl,
                );
            }
            $nodesByRef[$ref]['options'][] = [
                'label' => (string) ($row['option_label'] ?? ''),
                'actionType' => $action,
                'targetRef' => trim((string) ($row['option_target_ref'] ?? '')) ?: null,
                'targetUrl' => trim((string) ($row['option_target_url'] ?? '')) ?: null,
                'ticketCategoryId' => $categoryId,
                'closingMessage' => trim((string) ($row['option_closing_message'] ?? '')) ?: null,
            ];
            if ('' === $entryNodeRef && '' !== trim((string) ($row['entry_node'] ?? ''))) {
                $entryNodeRef = trim((string) $row['entry_node']);
            }
        }

        if ('' === $entryNodeRef) {
            $entryNodeRef = $nodeOrder[0] ?? '';
        }

        return [
            'title' => trim((string) ($first['tree_title'] ?? '')) ?: 'Imported support bot tree',
            'description' => trim((string) ($first['tree_description'] ?? '')) ?: null,
            'defaultTicketProjectId' => $defaultProjectId,
            'entryNodeRef' => $entryNodeRef,
            'nodes' => array_values(array_map(static fn (string $ref): array => $nodesByRef[$ref], $nodeOrder)),
        ];
    }

    private function resolveProjectIdByTitle(string $title, AccessUrl $accessUrl): int
    {
        if ('' === $title) {
            throw new BadRequestHttpException('The imported default ticket project is required.');
        }
        $matches = $this->entityManager->getRepository(TicketProject::class)->findBy(['title' => $title]);
        $fallback = null;
        foreach ($matches as $project) {
            if (!$project instanceof TicketProject || null === $project->getId() || !$this->isAvailableProject($project, $accessUrl)) {
                continue;
            }
            if ($project->getAccessUrl() instanceof AccessUrl && $project->getAccessUrl()?->getId() === $accessUrl->getId()) {
                return (int) $project->getId();
            }
            $fallback ??= $project;
        }
        if ($fallback instanceof TicketProject && null !== $fallback->getId()) {
            return (int) $fallback->getId();
        }

        throw new BadRequestHttpException('The imported default ticket project was not found on this portal.');
    }

    private function resolveCategoryIdByTitle(string $title, int $projectId, AccessUrl $accessUrl): int
    {
        if ('' === $title) {
            throw new BadRequestHttpException('The imported ticket category is required.');
        }
        $project = $this->entityManager->getRepository(TicketProject::class)->find($projectId);
        if (!$project instanceof TicketProject || !$this->isAvailableProject($project, $accessUrl)) {
            throw new BadRequestHttpException('The imported ticket project is invalid.');
        }
        $categories = $this->entityManager->getRepository(TicketCategory::class)->findBy([
            'project' => $project,
            'title' => $title,
        ]);
        foreach ($categories as $category) {
            if ($category instanceof TicketCategory && null !== $category->getId() && !$category->isCourseRequired()) {
                return (int) $category->getId();
            }
        }

        throw new BadRequestHttpException('The imported ticket category was not found in the selected project.');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateGraphPayload(array $data): void
    {
        $rawNodes = $data['nodes'] ?? null;
        if (!\is_array($rawNodes) || [] === $rawNodes) {
            throw new BadRequestHttpException('The support bot tree requires at least one node.');
        }

        $entryRef = trim((string) ($data['entryNodeRef'] ?? ''));
        if ('' === $entryRef) {
            throw new BadRequestHttpException('A support bot entry node is required.');
        }

        /** @var array<string, array<int, string>> $edges */
        $edges = [];
        $refs = [];

        foreach ($rawNodes as $rawNode) {
            if (!\is_array($rawNode)) {
                throw new BadRequestHttpException('Every support bot node must be an object.');
            }
            $ref = $this->requiredNodeRef($rawNode);
            if (isset($refs[$ref])) {
                throw new BadRequestHttpException('Support bot node references must be unique.');
            }
            $refs[$ref] = true;
            $edges[$ref] = [];
        }

        if (!isset($refs[$entryRef])) {
            throw new BadRequestHttpException('The support bot entry node does not exist in the tree.');
        }

        foreach ($rawNodes as $rawNode) {
            $ref = $this->requiredNodeRef($rawNode);
            $rawOptions = $rawNode['options'] ?? null;
            if (!\is_array($rawOptions) || [] === $rawOptions) {
                throw new BadRequestHttpException('Every support bot node requires at least one option.');
            }

            $hasGoto = false;
            $hasTerminal = false;
            foreach ($rawOptions as $rawOption) {
                if (!\is_array($rawOption)) {
                    throw new BadRequestHttpException('Every support bot option must be an object.');
                }
                $actionType = trim((string) ($rawOption['actionType'] ?? ''));
                if (!\in_array($actionType, self::ACTION_TYPES, true)) {
                    throw new BadRequestHttpException('A support bot option has an invalid action type.');
                }

                $this->requiredText($rawOption, 'label', 255, 'Every support bot option requires a label.');

                if (SupportBotOption::ACTION_GOTO_NODE === $actionType) {
                    $targetRef = trim((string) ($rawOption['targetRef'] ?? ''));
                    if (!isset($refs[$targetRef])) {
                        throw new BadRequestHttpException('A support bot option points to an unknown target node.');
                    }
                    $edges[$ref][] = $targetRef;
                    $hasGoto = true;
                } elseif (SupportBotOption::ACTION_OPEN_LINK === $actionType) {
                    $targetUrl = trim((string) ($rawOption['targetUrl'] ?? ''));
                    if (!$this->isSafeRelativeUrl($targetUrl)) {
                        throw new BadRequestHttpException('Support bot links must use a safe relative Chamilo URL.');
                    }
                } elseif (SupportBotOption::ACTION_ESCALATE_TICKET === $actionType) {
                    if ((int) ($rawOption['ticketCategoryId'] ?? 0) <= 0) {
                        throw new BadRequestHttpException('A ticket category is required for support bot escalation.');
                    }
                    $hasTerminal = true;
                } elseif (SupportBotOption::ACTION_END_CONVERSATION === $actionType) {
                    $hasTerminal = true;
                }
            }

            if (!$hasGoto && !$hasTerminal) {
                throw new BadRequestHttpException('A support bot leaf node must contain a terminal option.');
            }
        }

        $visited = [];
        $visiting = [];
        $this->walkGraph($entryRef, $edges, $visited, $visiting);
        if (\count($visited) !== \count($refs)) {
            $unreachable = array_values(array_diff(array_keys($refs), array_keys($visited)));

            throw new BadRequestHttpException('The support bot tree contains unreachable nodes: '.implode(', ', $unreachable));
        }
    }

    /**
     * @param array<string, array<int, string>> $edges
     * @param array<string, bool>               $visited
     * @param array<string, bool>               $visiting
     */
    private function walkGraph(string $ref, array $edges, array &$visited, array &$visiting): void
    {
        if (isset($visiting[$ref])) {
            throw new BadRequestHttpException('Support bot v1 does not allow cycles between nodes.');
        }
        if (isset($visited[$ref])) {
            return;
        }

        $visiting[$ref] = true;
        foreach ($edges[$ref] ?? [] as $targetRef) {
            $this->walkGraph($targetRef, $edges, $visited, $visiting);
        }
        unset($visiting[$ref]);
        $visited[$ref] = true;
    }

    private function validatePersistedTree(SupportBotTree $tree, AccessUrl $accessUrl): void
    {
        $entryNode = $tree->getEntryNode();
        if (!$entryNode instanceof SupportBotNode || $entryNode->getTree()->getId() !== $tree->getId()) {
            throw new ConflictHttpException('The support bot tree has no valid entry node.');
        }

        $nodes = $tree->getNodes();
        if ($nodes->isEmpty()) {
            throw new ConflictHttpException('The support bot tree has no nodes.');
        }

        $payload = [
            'entryNodeRef' => 'node-'.(int) $entryNode->getId(),
            'nodes' => [],
        ];
        $defaultProject = $tree->getDefaultTicketProject();
        if ($defaultProject instanceof TicketProject && !$this->isAvailableProject($defaultProject, $accessUrl)) {
            throw new ConflictHttpException('The default ticket project is not available in the current portal.');
        }

        foreach ($nodes as $node) {
            if (!$node instanceof SupportBotNode || null === $node->getId()) {
                throw new ConflictHttpException('The support bot tree contains an invalid node.');
            }

            $nodePayload = [
                'ref' => 'node-'.(int) $node->getId(),
                'content' => $node->getContent(),
                'options' => [],
            ];

            foreach ($node->getOptions() as $option) {
                if (!$option instanceof SupportBotOption) {
                    continue;
                }

                $targetNode = $option->getTargetNode();
                if ($targetNode instanceof SupportBotNode && $targetNode->getTree()->getId() !== $tree->getId()) {
                    throw new ConflictHttpException('A support bot option points outside its tree.');
                }

                $optionPayload = [
                    'label' => $option->getLabel(),
                    'actionType' => $option->getActionType(),
                    'targetRef' => $targetNode instanceof SupportBotNode && null !== $targetNode->getId()
                        ? 'node-'.(int) $targetNode->getId()
                        : null,
                    'targetUrl' => $option->getTargetUrl(),
                    'ticketCategoryId' => $option->getTicketCategory()?->getId(),
                    'closingMessage' => $option->getClosingMessage(),
                ];

                if (SupportBotOption::ACTION_ESCALATE_TICKET === $option->getActionType()) {
                    if (!$defaultProject instanceof TicketProject) {
                        throw new ConflictHttpException('A default ticket project is required for support bot escalation.');
                    }
                    $category = $option->getTicketCategory();
                    if (!$category instanceof TicketCategory || !$this->isAvailableCategory($category, $accessUrl)) {
                        throw new ConflictHttpException('A support bot escalation uses an unavailable ticket category.');
                    }
                    if ($category->isCourseRequired()) {
                        throw new ConflictHttpException('Support bot escalation cannot use a course-required ticket category.');
                    }
                    if ($defaultProject instanceof TicketProject
                        && $category->getProject()->getId() !== $defaultProject->getId()
                    ) {
                        throw new ConflictHttpException('A support bot escalation uses a category outside the default ticket project.');
                    }
                }

                $nodePayload['options'][] = $optionPayload;
            }

            $payload['nodes'][] = $nodePayload;
        }

        try {
            $this->validateGraphPayload($payload);
        } catch (BadRequestHttpException $exception) {
            throw new ConflictHttpException($exception->getMessage(), $exception);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveDefaultProject(array $data, AccessUrl $accessUrl): ?TicketProject
    {
        $projectId = (int) ($data['defaultTicketProjectId'] ?? 0);
        if ($projectId <= 0) {
            return null;
        }

        $project = $this->entityManager->getRepository(TicketProject::class)->find($projectId);
        if (!$project instanceof TicketProject || !$this->isAvailableProject($project, $accessUrl)) {
            throw new NotFoundHttpException('The selected default ticket project was not found.');
        }

        return $project;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveTicketCategory(array $data, AccessUrl $accessUrl): TicketCategory
    {
        $categoryId = (int) ($data['ticketCategoryId'] ?? 0);
        $category = $this->entityManager->getRepository(TicketCategory::class)->find($categoryId);
        if (!$category instanceof TicketCategory || !$this->isAvailableCategory($category, $accessUrl)) {
            throw new NotFoundHttpException('The selected ticket category was not found.');
        }
        if ($category->isCourseRequired()) {
            throw new BadRequestHttpException('Support bot escalation cannot use a course-required ticket category.');
        }

        return $category;
    }

    private function getAvailableTree(int $treeId, AccessUrl $accessUrl): SupportBotTree
    {
        $tree = $this->entityManager->getRepository(SupportBotTree::class)->find($treeId);
        if (!$tree instanceof SupportBotTree || !$this->isAvailableTree($tree, $accessUrl)) {
            throw new NotFoundHttpException('The requested support bot tree was not found.');
        }

        return $tree;
    }

    private function getEditableTree(int $treeId, AccessUrl $accessUrl): SupportBotTree
    {
        $tree = $this->getAvailableTree($treeId, $accessUrl);
        if (!$this->isEditableTree($tree, $accessUrl)) {
            throw new NotFoundHttpException('The requested support bot tree cannot be modified from this portal.');
        }

        return $tree;
    }

    private function assertFeatureEnabled(): void
    {
        if ('true' !== $this->settingsManager->getSetting('ticket.ticket_support_bot_enabled')) {
            throw new NotFoundHttpException('Support bot is disabled.');
        }
    }

    private function getCurrentAccessUrl(): AccessUrl
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl || null === $accessUrl->getId()) {
            throw new BadRequestHttpException('The current access URL is required.');
        }

        return $accessUrl;
    }

    private function getConfigurationAccessUrl(AccessUrl $current): ?AccessUrl
    {
        return 1 === (int) $current->getId() ? null : $current;
    }

    private function isAvailableTree(SupportBotTree $tree, AccessUrl $current): bool
    {
        return null === $tree->getAccessUrl() || $tree->getAccessUrl()?->getId() === $current->getId();
    }

    private function isEditableTree(SupportBotTree $tree, AccessUrl $current): bool
    {
        $treeAccessUrl = $tree->getAccessUrl();
        if ($treeAccessUrl instanceof AccessUrl) {
            return $treeAccessUrl->getId() === $current->getId();
        }

        return 1 === (int) $current->getId();
    }

    private function isAvailableProject(TicketProject $project, AccessUrl $current): bool
    {
        $projectAccessUrl = $project->getAccessUrl();

        return null === $projectAccessUrl || $projectAccessUrl->getId() === $current->getId();
    }

    private function isAvailableCategory(TicketCategory $category, AccessUrl $current): bool
    {
        return $this->isAvailableProject($category->getProject(), $current);
    }

    private function hasConversations(SupportBotTree $tree): bool
    {
        return $this->entityManager->getRepository(SupportBotConversation::class)->count(['tree' => $tree]) > 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function requiredNodeRef(array $data): string
    {
        $ref = trim((string) ($data['ref'] ?? ''));
        if ('' === $ref || 1 !== preg_match('/^[A-Za-z0-9_-]{1,80}$/', $ref)) {
            throw new BadRequestHttpException('Every support bot node requires a valid reference.');
        }

        return $ref;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function requiredText(array $data, string $key, ?int $maxLength, string $message): string
    {
        $value = trim((string) ($data[$key] ?? ''));
        if ('' === $value) {
            throw new BadRequestHttpException($message);
        }
        if (null !== $maxLength && mb_strlen($value) > $maxLength) {
            throw new BadRequestHttpException(ucfirst($key).' is too long.');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function optionalText(array $data, string $key): ?string
    {
        $value = trim((string) ($data[$key] ?? ''));

        return '' === $value ? null : $value;
    }

    private function isSafeRelativeUrl(string $targetUrl): bool
    {
        return str_starts_with($targetUrl, '/') && !str_starts_with($targetUrl, '//');
    }

    private function copyTitle(string $title): string
    {
        $suffix = ' (copy)';
        $maxBaseLength = 255 - mb_strlen($suffix);

        return mb_substr($title, 0, $maxBaseLength).$suffix;
    }
}
