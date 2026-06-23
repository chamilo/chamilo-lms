<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathActionToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<LearningPathActionToken>
 */
final readonly class LearningPathActionTokenProvider implements ProviderInterface
{
    private const ACTION_TOKEN_INTENTION = 'learning_path_action';

    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): LearningPathActionToken
    {
        $result = new LearningPathActionToken();
        $result->token = $this->csrfTokenManager->getToken(self::ACTION_TOKEN_INTENTION)->getValue();

        return $result;
    }
}
