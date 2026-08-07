<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathActionToken;
use Chamilo\CoreBundle\Settings\SettingsManager;

/**
 * @implements ProviderInterface<LearningPathActionToken>
 */
final readonly class LearningPathActionTokenProvider implements ProviderInterface
{
    public function __construct(
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): LearningPathActionToken
    {
        $result = new LearningPathActionToken();
        $result->allowChamiloExport = $this->isTruthy(
            $this->settingsManager->getSetting('lp.allow_lp_chamilo_export', true),
        );

        return $result;
    }

    private function isTruthy(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
