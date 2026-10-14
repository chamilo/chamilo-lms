<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State\SupportBot;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\SupportBot\SupportBotConversationDetail;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\SupportBotConversation;
use Chamilo\CoreBundle\Entity\SupportBotStep;
use Chamilo\CoreBundle\Entity\SupportBotTree;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<SupportBotConversationDetail>
 */
final readonly class SupportBotConversationDetailProvider implements ProviderInterface
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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SupportBotConversationDetail
    {
        $this->assertFeatureEnabled();
        $treeId = (int) ($uriVariables['treeId'] ?? 0);
        $conversationId = (int) ($uriVariables['conversationId'] ?? 0);
        $accessUrl = $this->getCurrentAccessUrl();
        $tree = $this->entityManager->getRepository(SupportBotTree::class)->find($treeId);
        if (!$tree instanceof SupportBotTree || !$this->isTreeAvailable($tree, $accessUrl)) {
            throw new NotFoundHttpException('The support bot tree was not found.');
        }

        $conversation = $this->entityManager->getRepository(SupportBotConversation::class)->find($conversationId);
        if (!$conversation instanceof SupportBotConversation
            || $conversation->getTree()->getId() !== $tree->getId()
            || $conversation->getAccessUrl()?->getId() !== $accessUrl->getId()
        ) {
            throw new NotFoundHttpException('The support bot conversation was not found.');
        }

        $user = $conversation->getUser();
        $fullName = trim($user->getFullName());
        $ticketId = $conversation->getResultingTicket()?->getId();

        $result = new SupportBotConversationDetail();
        $result->conversationId = (int) $conversation->getId();
        $result->treeId = (int) $tree->getId();
        $result->treeTitle = $tree->getTitle();
        $result->user = [
            'id' => (int) $user->getId(),
            'username' => $user->getUsername(),
            'name' => '' !== $fullName ? $fullName : $user->getUsername(),
        ];
        $result->outcome = $conversation->getOutcome();
        $result->treeVersion = $conversation->getTreeVersion();
        $result->startedAt = $conversation->getStartedAt()->format(DateTimeInterface::ATOM);
        $result->endedAt = $conversation->getEndedAt()?->format(DateTimeInterface::ATOM);
        $result->ticketId = null !== $ticketId ? (int) $ticketId : null;
        $result->ticketUrl = null !== $ticketId ? '/tickets/'.(int) $ticketId : null;

        foreach ($conversation->getSteps() as $step) {
            if (!$step instanceof SupportBotStep) {
                continue;
            }
            $result->steps[] = [
                'id' => (int) ($step->getId() ?? 0),
                'nodeContent' => $step->getNodeContent(),
                'selectedOptionLabel' => $step->getSelectedOptionLabel(),
                'createdAt' => $step->getCreatedAt()->format(DateTimeInterface::ATOM),
            ];
        }

        return $result;
    }

    private function getCurrentAccessUrl(): AccessUrl
    {
        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl || null === $accessUrl->getId()) {
            throw new BadRequestHttpException('The current access URL is required.');
        }

        return $accessUrl;
    }

    private function isTreeAvailable(SupportBotTree $tree, AccessUrl $accessUrl): bool
    {
        $treeAccessUrl = $tree->getAccessUrl();

        return !$treeAccessUrl instanceof AccessUrl || $treeAccessUrl->getId() === $accessUrl->getId();
    }

    private function assertFeatureEnabled(): void
    {
        if ('true' !== $this->settingsManager->getSetting('ticket.ticket_support_bot_enabled')) {
            throw new NotFoundHttpException('Support bot is disabled.');
        }
    }
}
