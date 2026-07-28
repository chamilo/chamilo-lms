<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\OAuthServer;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\OAuthServer\OAuthConnectedApp;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthGrantManager;

/**
 * @implements ProviderInterface<OAuthConnectedApp>
 */
final readonly class OAuthConnectedAppProvider implements ProviderInterface
{
    public function __construct(
        private OAuthGrantManager $grantManager,
    ) {}

    /**
     * @return array<int, OAuthConnectedApp>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        return array_map(
            OAuthConnectedApp::fromArray(...),
            $this->grantManager->listForCurrentUser(),
        );
    }
}
