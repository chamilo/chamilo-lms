<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\Ai;

use Chamilo\CoreBundle\Entity\AiRequests;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

final readonly class AiRequestQuotaGuard
{
    public function __construct(
        private SettingsManager $settingsManager,
        private EntityManagerInterface $entityManager,
    ) {}

    public function assertCanRequest(
        User $user,
        string $provider,
        string $serviceType,
    ): void {
        $userId = (int) ($user->getId() ?? 0);
        if ($userId <= 0) {
            throw new RuntimeException('An authenticated Chamilo user is required.');
        }

        $provider = trim($provider);
        $serviceType = trim($serviceType);
        $config = $this->readProvidersConfig();

        if (
            '' === $provider
            || !isset($config[$provider])
            || !\is_array($config[$provider])
        ) {
            return;
        }

        $providerConfig = $config[$provider];
        $dailyLimit = max(0, (int) ($providerConfig['daily_token_limit'] ?? 0));
        $monthlyLimit = max(0, (int) ($providerConfig['monthly_token_limit'] ?? 0));

        if ($dailyLimit <= 0 && $monthlyLimit <= 0) {
            return;
        }

        $reservedTokens = $this->getConfiguredRequestTokenCost(
            $providerConfig,
            $serviceType,
        );

        if ($dailyLimit > 0) {
            $dailyUsed = $this->getTokensUsedSince(
                $userId,
                $provider,
                new DateTimeImmutable('today'),
            );

            if ($dailyUsed + $reservedTokens > $dailyLimit) {
                throw new RuntimeException(
                    sprintf(
                        'The daily AI token limit for provider "%s" has been reached (used: %d, reserved for this request: %d, limit: %d).',
                        $provider,
                        $dailyUsed,
                        $reservedTokens,
                        $dailyLimit,
                    )
                );
            }
        }

        if ($monthlyLimit > 0) {
            $monthlyUsed = $this->getTokensUsedSince(
                $userId,
                $provider,
                new DateTimeImmutable('first day of this month 00:00:00'),
            );

            if ($monthlyUsed + $reservedTokens > $monthlyLimit) {
                throw new RuntimeException(
                    sprintf(
                        'The monthly AI token limit for provider "%s" has been reached (used: %d, reserved for this request: %d, limit: %d).',
                        $provider,
                        $monthlyUsed,
                        $reservedTokens,
                        $monthlyLimit,
                    )
                );
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function readProvidersConfig(): array
    {
        $raw = $this->settingsManager->getSetting(
            'ai_helpers.ai_providers',
            true,
        );

        if (\is_array($raw)) {
            return $raw;
        }

        if (!\is_string($raw) || '' === trim($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return \is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $providerConfig
     */
    private function getConfiguredRequestTokenCost(
        array $providerConfig,
        string $serviceType,
    ): int {
        $typeConfig = $providerConfig[$serviceType] ?? [];
        if (!\is_array($typeConfig)) {
            $typeConfig = [];
        }

        $configuredCost = $typeConfig['token_cost']
            ?? $typeConfig['estimated_token_cost']
            ?? $providerConfig['token_cost']
            ?? $providerConfig['estimated_token_cost']
            ?? $typeConfig['max_tokens']
            ?? $typeConfig['max_output_tokens']
            ?? 0;

        return max(0, (int) $configuredCost);
    }

    private function getTokensUsedSince(
        int $userId,
        string $provider,
        DateTimeImmutable $start,
    ): int {
        $value = $this->entityManager
            ->createQueryBuilder()
            ->select('COALESCE(SUM(aiRequest.totalTokens), 0)')
            ->from(AiRequests::class, 'aiRequest')
            ->andWhere('aiRequest.userId = :userId')
            ->andWhere('aiRequest.aiProvider = :provider')
            ->andWhere('aiRequest.requestedAt >= :start')
            ->setParameter('userId', $userId)
            ->setParameter('provider', $provider)
            ->setParameter('start', $start)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $value;
    }
}
