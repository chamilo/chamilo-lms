<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State\SupportBot;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\SupportBot\SupportBotTreeList;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\SupportBotConversation;
use Chamilo\CoreBundle\Entity\SupportBotTree;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<SupportBotTreeList>
 */
final readonly class SupportBotTreeListProvider implements ProviderInterface
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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SupportBotTreeList
    {
        $this->assertFeatureEnabled();
        $accessUrl = $this->getCurrentAccessUrl();
        $trees = $this->entityManager->createQueryBuilder()
            ->select('tree')
            ->from(SupportBotTree::class, 'tree')
            ->andWhere('(IDENTITY(tree.accessUrl) = :accessUrlId OR tree.accessUrl IS NULL)')
            ->setParameter('accessUrlId', (int) $accessUrl->getId(), Types::INTEGER)
            ->orderBy('tree.updatedAt', 'DESC')
            ->addOrderBy('tree.id', 'DESC')
            ->getQuery()
            ->getResult()
        ;

        $result = new SupportBotTreeList();
        foreach ($trees as $tree) {
            if (!$tree instanceof SupportBotTree || null === $tree->getId()) {
                continue;
            }

            $scopeEditable = $this->isEditableScope($tree, $accessUrl);
            $isDraft = SupportBotTree::STATUS_DRAFT === $tree->getStatus();
            $isPublished = SupportBotTree::STATUS_PUBLISHED === $tree->getStatus();
            $conversationCount = $this->entityManager
                ->getRepository(SupportBotConversation::class)
                ->count(['tree' => $tree])
            ;

            $result->trees[] = [
                'id' => (int) $tree->getId(),
                'title' => $tree->getTitle(),
                'description' => (string) $tree->getDescription(),
                'status' => $tree->getStatus(),
                'version' => $tree->getVersion(),
                'scope' => $tree->getAccessUrl() instanceof AccessUrl ? 'portal' : 'shared',
                'scopeLabel' => $tree->getAccessUrl()?->getUrl() ?? 'Shared configuration',
                'updatedAt' => $tree->getUpdatedAt()->format(DateTimeInterface::ATOM),
                'conversationCount' => $conversationCount,
                'canEdit' => $scopeEditable && $isDraft,
                'canDuplicate' => true,
                'canPublish' => $scopeEditable && $isDraft,
                'canArchive' => $scopeEditable && $isPublished,
                'canDelete' => $scopeEditable && !$isPublished && 0 === $conversationCount,
            ];
        }

        return $result;
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

    private function isEditableScope(SupportBotTree $tree, AccessUrl $current): bool
    {
        $treeAccessUrl = $tree->getAccessUrl();
        if ($treeAccessUrl instanceof AccessUrl) {
            return $treeAccessUrl->getId() === $current->getId();
        }

        return 1 === (int) $current->getId();
    }
}
