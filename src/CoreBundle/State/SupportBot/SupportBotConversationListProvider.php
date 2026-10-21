<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State\SupportBot;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\SupportBot\SupportBotConversationList;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\SupportBotConversation;
use Chamilo\CoreBundle\Entity\SupportBotTree;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<SupportBotConversationList>
 */
final readonly class SupportBotConversationListProvider implements ProviderInterface
{
    private const array ALLOWED_OUTCOMES = [
        SupportBotConversation::OUTCOME_IN_PROGRESS,
        SupportBotConversation::OUTCOME_RESOLVED,
        SupportBotConversation::OUTCOME_ESCALATED,
        SupportBotConversation::OUTCOME_ABANDONED,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccessUrlHelper $accessUrlHelper,
        private SettingsManager $settingsManager,
        private RequestStack $requestStack,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SupportBotConversationList
    {
        $this->assertFeatureEnabled();
        $treeId = (int) ($uriVariables['treeId'] ?? 0);
        $accessUrl = $this->getCurrentAccessUrl();
        $tree = $this->getTree($treeId, $accessUrl);
        $request = $this->requestStack->getCurrentRequest();

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('DISTINCT conversation')
            ->from(SupportBotConversation::class, 'conversation')
            ->innerJoin('conversation.user', 'user')
            ->andWhere('conversation.tree = :tree')
            ->andWhere('conversation.accessUrl = :accessUrl')
            ->setParameter('tree', $treeId)
            ->setParameter('accessUrl', (int) $accessUrl->getId())
            ->orderBy('conversation.startedAt', 'DESC')
            ->addOrderBy('conversation.id', 'DESC')
            ->setMaxResults(500)
        ;

        $outcome = trim((string) $request?->query->get('outcome', ''));
        if ('' !== $outcome) {
            if (!\in_array($outcome, self::ALLOWED_OUTCOMES, true)) {
                throw new BadRequestHttpException('Invalid support bot conversation outcome filter.');
            }
            $queryBuilder->andWhere('conversation.outcome = :outcome')->setParameter('outcome', $outcome);
        }

        $startDate = $this->parseDate((string) $request?->query->get('startDate', ''), false);
        if ($startDate instanceof DateTimeImmutable) {
            $queryBuilder
                ->andWhere('conversation.startedAt >= :startDate')
                ->setParameter('startDate', $startDate, Types::DATETIME_IMMUTABLE)
            ;
        }

        $endDate = $this->parseDate((string) $request?->query->get('endDate', ''), true);
        if ($endDate instanceof DateTimeImmutable) {
            $queryBuilder
                ->andWhere('conversation.startedAt <= :endDate')
                ->setParameter('endDate', $endDate, Types::DATETIME_IMMUTABLE)
            ;
        }

        $keyword = mb_substr(trim((string) $request?->query->get('keyword', '')), 0, 100);
        if ('' !== $keyword) {
            $queryBuilder
                ->leftJoin('conversation.steps', 'step')
                ->andWhere(
                    '(user.username LIKE :keyword OR user.firstname LIKE :keyword OR user.lastname LIKE :keyword '
                    .'OR step.nodeContent LIKE :keyword OR step.selectedOptionLabel LIKE :keyword)',
                )
                ->setParameter('keyword', '%'.$keyword.'%', Types::STRING)
            ;
        }

        $result = new SupportBotConversationList();
        $result->treeId = (int) $tree->getId();
        $result->treeTitle = $tree->getTitle();

        foreach ($queryBuilder->getQuery()->getResult() as $conversation) {
            if (!$conversation instanceof SupportBotConversation || null === $conversation->getId()) {
                continue;
            }
            $user = $conversation->getUser();
            $ticketId = $conversation->getResultingTicket()?->getId();
            $fullName = trim($user->getFullName());
            $result->conversations[] = [
                'id' => (int) $conversation->getId(),
                'userId' => (int) $user->getId(),
                'username' => $user->getUsername(),
                'userName' => '' !== $fullName ? $fullName : $user->getUsername(),
                'startedAt' => $conversation->getStartedAt()->format(DateTimeInterface::ATOM),
                'endedAt' => $conversation->getEndedAt()?->format(DateTimeInterface::ATOM),
                'outcome' => $conversation->getOutcome(),
                'treeVersion' => $conversation->getTreeVersion(),
                'ticketId' => null !== $ticketId ? (int) $ticketId : null,
                'ticketUrl' => null !== $ticketId ? '/tickets/'.(int) $ticketId : null,
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

    private function getTree(int $treeId, AccessUrl $accessUrl): SupportBotTree
    {
        $tree = $this->entityManager->getRepository(SupportBotTree::class)->find($treeId);
        if (!$tree instanceof SupportBotTree || !$this->isTreeAvailable($tree, $accessUrl)) {
            throw new NotFoundHttpException('The support bot tree was not found.');
        }

        return $tree;
    }

    private function isTreeAvailable(SupportBotTree $tree, AccessUrl $accessUrl): bool
    {
        $treeAccessUrl = $tree->getAccessUrl();

        return !$treeAccessUrl instanceof AccessUrl || $treeAccessUrl->getId() === $accessUrl->getId();
    }

    private function parseDate(string $value, bool $endOfDay): ?DateTimeImmutable
    {
        $value = trim($value);
        if ('' === $value) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date instanceof DateTimeImmutable || $date->format('Y-m-d') !== $value) {
            throw new BadRequestHttpException('Invalid support bot conversation date filter.');
        }

        return $endOfDay ? $date->setTime(23, 59, 59) : $date->setTime(0, 0, 0);
    }

    private function assertFeatureEnabled(): void
    {
        if ('true' !== $this->settingsManager->getSetting('ticket.ticket_support_bot_enabled')) {
            throw new NotFoundHttpException('Support bot is disabled.');
        }
    }
}
