<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State\SupportBot;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\SupportBot\SupportBotTreeDetail;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\SupportBotNode;
use Chamilo\CoreBundle\Entity\SupportBotOption;
use Chamilo\CoreBundle\Entity\SupportBotTree;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<SupportBotTreeDetail>
 */
final readonly class SupportBotTreeDetailProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SupportBotTreeDetail
    {
        $this->assertFeatureEnabled();
        $accessUrl = $this->getCurrentAccessUrl();
        $treeId = isset($uriVariables['treeId']) ? (int) $uriVariables['treeId'] : 0;
        if ($treeId <= 0) {
            $result = new SupportBotTreeDetail();
            $result->scope = 1 === (int) $accessUrl->getId() ? 'shared' : 'portal';
            $result->scopeLabel = 1 === (int) $accessUrl->getId()
                ? 'Shared configuration'
                : $accessUrl->getUrl();

            return $result;
        }

        return $this->buildResponse($this->getAvailableTree($treeId, $accessUrl), $accessUrl);
    }

    public function buildResponse(SupportBotTree $tree, AccessUrl $accessUrl): SupportBotTreeDetail
    {
        $result = new SupportBotTreeDetail();
        $result->treeId = (int) $tree->getId();
        $result->mode = 'edit';
        $result->title = $tree->getTitle();
        $result->description = (string) $tree->getDescription();
        $result->status = $tree->getStatus();
        $result->version = $tree->getVersion();
        $result->entryNodeRef = null !== $tree->getEntryNode()?->getId()
            ? $this->nodeRef((int) $tree->getEntryNode()->getId())
            : null;
        $result->defaultTicketProjectId = $tree->getDefaultTicketProject()?->getId();
        $result->scope = $tree->getAccessUrl() instanceof AccessUrl ? 'portal' : 'shared';
        $result->scopeLabel = $tree->getAccessUrl()?->getUrl() ?? 'Shared configuration';
        $result->editable = $this->isEditableScope($tree, $accessUrl)
            && SupportBotTree::STATUS_DRAFT === $tree->getStatus();

        foreach ($tree->getNodes() as $node) {
            if (!$node instanceof SupportBotNode || null === $node->getId()) {
                continue;
            }

            $nodeData = [
                'ref' => $this->nodeRef((int) $node->getId()),
                'content' => $node->getContent(),
                'sortOrder' => $node->getSortOrder(),
                'options' => [],
            ];

            foreach ($node->getOptions() as $option) {
                if (!$option instanceof SupportBotOption) {
                    continue;
                }

                $nodeData['options'][] = [
                    'label' => $option->getLabel(),
                    'sortOrder' => $option->getSortOrder(),
                    'actionType' => $option->getActionType(),
                    'targetRef' => null !== $option->getTargetNode()?->getId()
                        ? $this->nodeRef((int) $option->getTargetNode()->getId())
                        : null,
                    'targetUrl' => $option->getTargetUrl(),
                    'ticketCategoryId' => $option->getTicketCategory()?->getId(),
                    'closingMessage' => $option->getClosingMessage(),
                ];
            }

            $result->nodes[] = $nodeData;
        }

        return $result;
    }

    public function getAvailableTree(int $treeId, AccessUrl $accessUrl): SupportBotTree
    {
        $tree = $this->entityManager->getRepository(SupportBotTree::class)->find($treeId);
        if (!$tree instanceof SupportBotTree || !$this->isAvailableScope($tree, $accessUrl)) {
            throw new NotFoundHttpException('The requested support bot tree was not found.');
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

    private function isAvailableScope(SupportBotTree $tree, AccessUrl $current): bool
    {
        return null === $tree->getAccessUrl() || $tree->getAccessUrl()?->getId() === $current->getId();
    }

    private function isEditableScope(SupportBotTree $tree, AccessUrl $current): bool
    {
        $treeAccessUrl = $tree->getAccessUrl();
        if ($treeAccessUrl instanceof AccessUrl) {
            return $treeAccessUrl->getId() === $current->getId();
        }

        return 1 === (int) $current->getId();
    }

    private function nodeRef(int $id): string
    {
        return 'node-'.$id;
    }
}
