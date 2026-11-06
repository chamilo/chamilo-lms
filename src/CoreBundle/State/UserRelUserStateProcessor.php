<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\UserRelUser;
use Chamilo\CoreBundle\Helpers\UserRelUserHelper;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProcessorInterface<UserRelUser, UserRelUser|void>
 */
final class UserRelUserStateProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly ProcessorInterface $removeProcessor,
        private readonly UserRelUserHelper $helper,
    ) {}

    public function process($data, Operation $operation, array $uriVariables = [], array $context = []): ?UserRelUser
    {
        if ($operation instanceof DeleteOperationInterface) {
            return $this->removeProcessor->process($data, $operation, $uriVariables, $context);
        }

        \assert($data instanceof UserRelUser);

        if ($operation instanceof Post) {
            $this->helper->assertValidCreate($data);
        } else {
            $previous = $context['previous_data'] ?? null;
            if (!$previous instanceof UserRelUser) {
                throw new AccessDeniedHttpException('The relation to update could not be verified.');
            }

            $this->helper->assertValidUpdate($data, $previous);
        }

        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        \assert($result instanceof UserRelUser);

        // Keep the friendship symmetric; the helper no-ops unless the row is a friendship.
        $this->helper->ensureInverseFriendship($result);

        return $result;
    }
}
