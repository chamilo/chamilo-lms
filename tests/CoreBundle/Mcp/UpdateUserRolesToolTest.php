<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Mcp\UpdateUserRolesTool;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Mcp\Exception\ToolCallException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Access-URL scoping matches AccessUrlScopeHelper::canEditUser() exactly (see
 * AccessUrlScopeHelperTest): a plain admin is confined to its exact URL, a
 * ROLE_GLOBAL_ADMIN also manages its URL's descendants, and only an unrestricted
 * (top-of-tree) global admin may grant ROLE_GLOBAL_ADMIN.
 */
final class UpdateUserRolesToolTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private function createUserOnUrl(string $username, AccessUrl $url, string $role = ''): User
    {
        /** @var UserRepository $repo */
        $repo = self::getContainer()->get(UserRepository::class);
        $admin = $this->getAdmin();

        $user = $repo->createUser()
            ->setLastname($username)
            ->setFirstname($username)
            ->setUsername($username)
            ->setStatus(1)
            ->setPlainPassword($username)
            ->setEmail($username.'@example.com')
            ->setCreator($admin)
            ->setCurrentUrl($url)
            ->addAuthSourceByAuthentication(UserAuthSource::PLATFORM, $url)
        ;

        if ('' !== $role) {
            $user->addRole($role);
        }

        $repo->updateUser($user);

        return $user;
    }

    /**
     * root (fixture default) -> child -> grandchild, with a plain admin and a
     * (non-unrestricted) global admin both registered on "child", and a plain user on
     * "grandchild".
     *
     * @return array{root: AccessUrl, child: AccessUrl, grandchild: AccessUrl, rootAdmin: User,
     *               childPlainAdmin: User, childGlobalAdmin: User, grandchildUser: User}
     */
    private function buildTree(): array
    {
        /** @var AccessUrlRepository $urlRepo */
        $urlRepo = self::getContainer()->get(AccessUrlRepository::class);
        $admin = $this->getAdmin();
        $root = $this->getAccessUrl();

        $child = (new AccessUrl())
            ->setUrl('https://update-user-roles-child-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($admin)
            ->setSuperior($root)
        ;
        $urlRepo->create($child);

        $grandchild = (new AccessUrl())
            ->setUrl('https://update-user-roles-grandchild-'.uniqid().'.example.org/')
            ->setActive(1)
            ->setCreator($admin)
            ->setSuperior($child)
        ;
        $urlRepo->create($grandchild);

        $rootAdmin = $this->createUserOnUrl('uurRoot_'.uniqid(), $root, 'ROLE_ADMIN');
        $childPlainAdmin = $this->createUserOnUrl('uurChildPlain_'.uniqid(), $child, 'ROLE_ADMIN');
        $childGlobalAdmin = $this->createUserOnUrl('uurChildGlobal_'.uniqid(), $child, 'ROLE_ADMIN');
        $childGlobalAdmin->addRole('ROLE_GLOBAL_ADMIN');
        $grandchildUser = $this->createUserOnUrl('uurGrandchildUser_'.uniqid(), $grandchild);

        return compact('root', 'child', 'grandchild', 'rootAdmin', 'childPlainAdmin', 'childGlobalAdmin', 'grandchildUser');
    }

    private function actAs(User $user): void
    {
        self::getContainer()->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($user, 'api', $user->getRoles())
        );
    }

    public function testAdminCanGrantAndRevokeARoleForAPeerOnTheExactSameUrl(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();
        $peer = $this->createUserOnUrl('uurPeer_'.uniqid(), $tree['child']);

        $this->actAs($tree['childPlainAdmin']);
        $tool = self::getContainer()->get(UpdateUserRolesTool::class);

        $result = $tool->updateUserRoles(userId: $peer->getId(), addRoles: ['TEACHER']);
        $this->assertTrue($result['updated']);
        $this->assertSame(['ROLE_TEACHER'], $result['added_roles']);
        $this->assertContains('ROLE_TEACHER', $result['user']['roles']);

        $result = $tool->updateUserRoles(userId: $peer->getId(), removeRoles: ['TEACHER']);
        $this->assertSame(['ROLE_TEACHER'], $result['removed_roles']);
        $this->assertNotContains('ROLE_TEACHER', $result['user']['roles']);
    }

    public function testPlainAdminCannotReachAUserOnADescendantUrl(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();

        $this->actAs($tree['childPlainAdmin']);
        $tool = self::getContainer()->get(UpdateUserRolesTool::class);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('No user found for the given identifier, or you do not manage their access URL.');
        $tool->updateUserRoles(userId: $tree['grandchildUser']->getId(), addRoles: ['TEACHER']);
    }

    public function testGlobalAdminManagesItsWholeSubtree(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();

        $this->actAs($tree['childGlobalAdmin']);
        $tool = self::getContainer()->get(UpdateUserRolesTool::class);

        $result = $tool->updateUserRoles(userId: $tree['grandchildUser']->getId(), addRoles: ['SESSION_MANAGER']);
        $this->assertSame(['ROLE_SESSION_MANAGER'], $result['added_roles']);
    }

    public function testOnlyAnUnrestrictedGlobalAdminMayGrantTheGlobalAdminRole(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();

        // childGlobalAdmin holds ROLE_GLOBAL_ADMIN but is registered on a non-root URL, so
        // it is NOT unrestricted and must not be able to grant the role onward.
        $this->actAs($tree['childGlobalAdmin']);
        $tool = self::getContainer()->get(UpdateUserRolesTool::class);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Only an administrator registered at the top of a URL tree may grant the global admin role.');
        $tool->updateUserRoles(userId: $tree['grandchildUser']->getId(), addRoles: ['GLOBAL_ADMIN']);
    }

    public function testAnUnrestrictedGlobalAdminMayGrantTheGlobalAdminRole(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();
        $tree['rootAdmin']->addRole('ROLE_GLOBAL_ADMIN');

        $this->actAs($tree['rootAdmin']);
        $tool = self::getContainer()->get(UpdateUserRolesTool::class);

        $result = $tool->updateUserRoles(userId: $tree['childPlainAdmin']->getId(), addRoles: ['GLOBAL_ADMIN']);
        $this->assertSame(['ROLE_GLOBAL_ADMIN'], $result['added_roles']);
    }

    public function testCannotChangeOwnRoles(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();

        $this->actAs($tree['rootAdmin']);
        $tool = self::getContainer()->get(UpdateUserRolesTool::class);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('You cannot change your own roles with this tool.');
        $tool->updateUserRoles(userId: $tree['rootAdmin']->getId(), addRoles: ['TEACHER']);
    }

    public function testNonAdminCannotUseTheTool(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();
        $student = $this->createUserOnUrl('uurStudent_'.uniqid(), $tree['root']);

        $this->actAs($student);
        $tool = self::getContainer()->get(UpdateUserRolesTool::class);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Only administrators can change user roles.');
        $tool->updateUserRoles(userId: $tree['childPlainAdmin']->getId(), addRoles: ['TEACHER']);
    }

    public function testRejectsAnUnrecognizedRoleCode(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();
        $peer = $this->createUserOnUrl('uurPeer2_'.uniqid(), $tree['root']);

        $this->actAs($tree['rootAdmin']);
        $tool = self::getContainer()->get(UpdateUserRolesTool::class);

        $this->expectException(ToolCallException::class);
        $tool->updateUserRoles(userId: $peer->getId(), addRoles: ['NOT_A_REAL_ROLE']);
    }

    public function testRequiresAtLeastOneRoleChange(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();
        $peer = $this->createUserOnUrl('uurPeer3_'.uniqid(), $tree['root']);

        $this->actAs($tree['rootAdmin']);
        $tool = self::getContainer()->get(UpdateUserRolesTool::class);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Provide at least one role in addRoles or removeRoles.');
        $tool->updateUserRoles(userId: $peer->getId());
    }

    public function testRequiresExactlyOneOfUserIdOrUsername(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();

        $this->actAs($tree['rootAdmin']);
        $tool = self::getContainer()->get(UpdateUserRolesTool::class);

        $this->expectException(ToolCallException::class);
        $this->expectExceptionMessage('Provide exactly one of userId or username to identify the target user.');
        $tool->updateUserRoles(addRoles: ['TEACHER']);
    }
}
