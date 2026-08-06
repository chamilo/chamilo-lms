<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\ResourceAclHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class ResourceAclHelperTest extends TestCase
{
    /**
     * Reproduces the "Login as" / session-manager smoke-test crash: the
     * current user's Symfony role set can contain roles the ACL never
     * registers (ROLE_SESSION_MANAGER, ROLE_PREVIOUS_ADMIN,
     * ROLE_ALLOWED_TO_SWITCH, ...), because ResourceAclHelper::init() only
     * ever registers a small fixed set of resource-permission roles.
     * isAllowed() iterates ALL of $user->getRoles() - without a
     * $acl->hasRole() guard, Laminas throws
     * Acl\Exception\InvalidArgumentException for the first unregistered
     * role instead of just skipping it, turning every resource permission
     * check into a 500 for such a user.
     */
    public function testDoesNotThrowWhenUserHasRoleNotRegisteredInAcl(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getRoles')->willReturn([
            'ROLE_SESSION_MANAGER',
            'ROLE_PREVIOUS_ADMIN',
            'ROLE_ALLOWED_TO_SWITCH',
            'ROLE_CURRENT_COURSE_STUDENT',
            'ROLE_STUDENT',
        ]);

        $token = $this->createMock(TokenInterface::class);

        $security = $this->createMock(Security::class);
        $security->method('getToken')->willReturn($token);
        $security->method('getUser')->willReturn($user);

        $helper = new ResourceAclHelper($security);

        $resourceLink = new ResourceLink();

        $result = $helper->isAllowed('VIEW', $resourceLink, []);

        $this->assertFalse($result);
    }
}
