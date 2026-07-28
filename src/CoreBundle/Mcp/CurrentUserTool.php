<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\User;
use Mcp\Capability\Attribute\McpTool;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final readonly class CurrentUserTool
{
    public function __construct(
        private Security $security,
    ) {}

    /**
     * @return array{
     *     user_id: int,
     *     username: string,
     *     full_name: string,
     *     roles: list<string>
     * }
     */
    #[McpTool(
        name: 'get_current_user',
        description: 'Return the identity and roles of the authenticated Chamilo user.',
    )]
    public function getCurrentUser(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof User || null === $user->getId()) {
            throw new AccessDeniedException('An authenticated Chamilo user is required.');
        }

        $roles = $user->getRoles();
        sort($roles);

        return [
            'user_id' => $user->getId(),
            'username' => $user->getUsername(),
            'full_name' => $user->getFullName(),
            'roles' => $roles,
        ];
    }
}
