<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\CurrentUserProfile;
use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Helpers\AccessUrlHelper;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @implements ProviderInterface<CurrentUserProfile>
 */
final readonly class CurrentUserProfileStateProvider implements ProviderInterface
{
    public function __construct(
        private UserHelper $userHelper,
        private AccessUrlHelper $accessUrlHelper,
        private AccessUrlRepository $accessUrlRepository,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CurrentUserProfile
    {
        $user = $this->userHelper->getCurrent();
        if (null === $user) {
            throw new AccessDeniedException('Authentication is required.');
        }

        $accessUrl = $this->accessUrlHelper->getCurrent();
        if (!$accessUrl instanceof AccessUrl) {
            throw new RuntimeException('Current access URL was not found.');
        }

        if (!$this->accessUrlRepository->isUrlActiveForUser($accessUrl, $user)) {
            throw new AccessDeniedException('The authenticated user is not active on this access URL.');
        }

        return CurrentUserProfile::fromUser($user);
    }
}
