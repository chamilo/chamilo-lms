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
     *     locale: string,
     *     roles: list<string>
     * }
     */
    #[McpTool(
        name: 'get_current_user',
        description: 'Return the identity, locale and roles of the Chamilo user authenticated on this MCP connection. Takes no parameters. locale is the user\'s own Chamilo language code (e.g. "en_US", "es") — the default language used by create_course and create_course_document when their language parameter is omitted. Roles are Symfony role strings (e.g. ROLE_TEACHER, ROLE_ADMIN) describing what this user is allowed to do on the platform. Call this first to confirm who is authenticated, or before calling teacher/admin-only tools such as create_course, create_course_document or list_my_teacher_courses to check whether the user actually has that access.',
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
            'locale' => $user->getLocale(),
            'roles' => $roles,
        ];
    }
}
