<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\OAuthServer;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Service\OAuthServer\OAuthGrantManager;
use LogicException;

/**
 * @implements ProcessorInterface<mixed, null>
 */
final readonly class OAuthConnectedAppProcessor implements ProcessorInterface
{
    public function __construct(
        private OAuthGrantManager $grantManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($operation instanceof Delete) {
            $this->grantManager->revokeForCurrentUser((string) $uriVariables['id']);

            return null;
        }

        throw new LogicException('Unsupported OAuth connected app operation.');
    }
}
