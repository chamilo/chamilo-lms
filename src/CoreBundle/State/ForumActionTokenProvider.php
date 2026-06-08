<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Forum\ForumActionToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<ForumActionToken>
 */
final readonly class ForumActionTokenProvider implements ProviderInterface
{
    private const FORUM_ACTION_TOKEN_INTENTION = 'forum_action';

    public function __construct(
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ForumActionToken
    {
        $actionToken = new ForumActionToken();
        $actionToken->token = $this->csrfTokenManager->getToken(self::FORUM_ACTION_TOKEN_INTENTION)->getValue();

        return $actionToken;
    }
}
