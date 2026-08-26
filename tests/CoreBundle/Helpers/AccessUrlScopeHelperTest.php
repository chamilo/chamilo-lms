<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A global admin registered in a URL with no parent (the topmost URL of a tree) is
 * "unrestricted" and manages every URL. One registered only in a non-root URL is scoped to
 * that URL's subtree. See AccessUrlScopeHelper.
 */
class AccessUrlScopeHelperTest extends KernelTestCase
{
    use ChamiloTestTrait;

    private function createUserOnUrl(string $username, AccessUrl $url, string $role = ''): User
    {
        /** @var UserRepository $repo */
        $repo = static::getContainer()->get(UserRepository::class);
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
     * Builds root -> child -> grandchild (root is the fixture default URL), plus an
     * unrelated separate-tree root, and registers one admin at each of the three main-tree
     * levels.
     *
     * @return array{root: AccessUrl, child: AccessUrl, grandchild: AccessUrl, orphanRoot: AccessUrl,
     *               rootAdmin: User, childAdmin: User, grandchildAdmin: User}
     */
    private function buildTree(): array
    {
        /** @var AccessUrlRepository $urlRepo */
        $urlRepo = static::getContainer()->get(AccessUrlRepository::class);
        $admin = $this->getAdmin();
        $root = $this->getAccessUrl();

        $child = (new AccessUrl())
            ->setUrl('https://scope-child.example.org/')
            ->setActive(1)
            ->setCreator($admin)
            ->setSuperior($root)
        ;
        $urlRepo->create($child);

        $grandchild = (new AccessUrl())
            ->setUrl('https://scope-grandchild.example.org/')
            ->setActive(1)
            ->setCreator($admin)
            ->setSuperior($child)
        ;
        $urlRepo->create($grandchild);

        // A separate, unrelated tree -- must never appear in the child/grandchild admins' scope.
        $orphanRoot = (new AccessUrl())
            ->setUrl('https://scope-orphan.example.org/')
            ->setActive(1)
            ->setCreator($admin)
        ;
        $urlRepo->create($orphanRoot);

        $rootAdmin = $this->createUserOnUrl('scoperootadmin', $root);
        $childAdmin = $this->createUserOnUrl('scopechildadmin', $child);
        $grandchildAdmin = $this->createUserOnUrl('scopegrandchildadmin', $grandchild);

        return compact('root', 'child', 'grandchild', 'orphanRoot', 'rootAdmin', 'childAdmin', 'grandchildAdmin');
    }

    public function testRootAdminIsUnrestrictedAndManagesEverything(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();

        /** @var AccessUrlScopeHelper $scope */
        $scope = static::getContainer()->get(AccessUrlScopeHelper::class);

        $this->assertTrue($scope->isUnrestricted($tree['rootAdmin']));
        $this->assertNull($scope->getManagedUrlIds($tree['rootAdmin']));
        // null means "no filter"; an orphan root (parent_id never set) is not silently excluded.
        $this->assertTrue($scope->isUrlManaged($tree['rootAdmin'], $tree['orphanRoot']->getId()));
    }

    public function testChildAdminManagesItsOwnSubtreeOnlyNotTheRootOrAnOrphanTree(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();

        /** @var AccessUrlScopeHelper $scope */
        $scope = static::getContainer()->get(AccessUrlScopeHelper::class);

        $this->assertFalse($scope->isUnrestricted($tree['childAdmin']));

        $managedIds = $scope->getManagedUrlIds($tree['childAdmin']);
        $this->assertIsArray($managedIds);
        $this->assertContains($tree['child']->getId(), $managedIds);
        $this->assertContains($tree['grandchild']->getId(), $managedIds);
        $this->assertNotContains($tree['root']->getId(), $managedIds);
        $this->assertNotContains($tree['orphanRoot']->getId(), $managedIds);

        $this->assertTrue($scope->isUrlManaged($tree['childAdmin'], $tree['child']->getId()));
        $this->assertTrue($scope->isUrlManaged($tree['childAdmin'], $tree['grandchild']->getId()));
        $this->assertFalse($scope->isUrlManaged($tree['childAdmin'], $tree['root']->getId()));
    }

    public function testGrandchildAdminManagesOnlyItself(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();

        /** @var AccessUrlScopeHelper $scope */
        $scope = static::getContainer()->get(AccessUrlScopeHelper::class);

        $managedIds = $scope->getManagedUrlIds($tree['grandchildAdmin']);
        $this->assertSame([$tree['grandchild']->getId()], $managedIds);
        $this->assertFalse($scope->isUrlManaged($tree['grandchildAdmin'], $tree['child']->getId()));
    }

    public function testIsUserAndIsCourseManagedFollowUrlScope(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();

        /** @var AccessUrlScopeHelper $scope */
        $scope = static::getContainer()->get(AccessUrlScopeHelper::class);

        // The grandchild admin is itself registered only in the grandchild URL, so it is a
        // "managed" user from the child admin's point of view, but the root admin's own
        // admin account (registered at the root) is not.
        $this->assertTrue($scope->isUserManaged($tree['childAdmin'], (int) $tree['grandchildAdmin']->getId()));
        $this->assertFalse($scope->isUserManaged($tree['childAdmin'], (int) $tree['rootAdmin']->getId()));
        $this->assertTrue($scope->isUserManaged($tree['rootAdmin'], (int) $tree['childAdmin']->getId()));
    }

    public function testCanEditUserAlwaysAllowsSelf(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();

        /** @var AccessUrlScopeHelper $scope */
        $scope = static::getContainer()->get(AccessUrlScopeHelper::class);

        $this->assertTrue($scope->canEditUser($tree['childAdmin'], $tree['childAdmin']));
        $this->assertTrue($scope->canEditUser($tree['grandchildAdmin'], $tree['grandchildAdmin']));
    }

    public function testCanEditUserUnrestrictedAdminMayEditAnyone(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();

        /** @var AccessUrlScopeHelper $scope */
        $scope = static::getContainer()->get(AccessUrlScopeHelper::class);

        $this->assertTrue($scope->canEditUser($tree['rootAdmin'], $tree['childAdmin']));
        $this->assertTrue($scope->canEditUser($tree['rootAdmin'], $tree['grandchildAdmin']));
    }

    public function testCanEditUserGlobalAdminManagesItsWholeSubtreeButNotAbove(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();
        $tree['childAdmin']->addRole('ROLE_GLOBAL_ADMIN');

        /** @var AccessUrlScopeHelper $scope */
        $scope = static::getContainer()->get(AccessUrlScopeHelper::class);

        // A ROLE_GLOBAL_ADMIN scoped to "child" manages the whole subtree, including
        // "grandchild"...
        $this->assertTrue($scope->canEditUser($tree['childAdmin'], $tree['grandchildAdmin']));
        // ...but never a user registered above its own scope.
        $this->assertFalse($scope->canEditUser($tree['childAdmin'], $tree['rootAdmin']));
    }

    public function testCanEditUserPlainAdminIsConfinedToItsExactUrlEvenWithChildren(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();

        /** @var AccessUrlScopeHelper $scope */
        $scope = static::getContainer()->get(AccessUrlScopeHelper::class);

        // childAdmin here deliberately holds no ROLE_GLOBAL_ADMIN: unlike the global-admin
        // case above, a plain admin must NOT get the descendant ("grandchild") expansion
        // just because the URL they are registered on happens to have children.
        $this->assertFalse($scope->canEditUser($tree['childAdmin'], $tree['grandchildAdmin']));

        // Still allowed to edit a peer actually registered on their own exact URL.
        $peerOnChild = $this->createUserOnUrl('scope_child_peer_'.uniqid(), $tree['child']);
        $this->assertTrue($scope->canEditUser($tree['childAdmin'], $peerOnChild));
    }

    public function testCanGrantGlobalAdminRoleOnlyForAnUnrestrictedGlobalAdmin(): void
    {
        self::bootKernel();
        $tree = $this->buildTree();

        /** @var AccessUrlScopeHelper $scope */
        $scope = static::getContainer()->get(AccessUrlScopeHelper::class);

        $tree['rootAdmin']->addRole('ROLE_GLOBAL_ADMIN');
        $tree['childAdmin']->addRole('ROLE_GLOBAL_ADMIN');

        $this->assertTrue($scope->canGrantGlobalAdminRole($tree['rootAdmin']));
        $this->assertFalse($scope->canGrantGlobalAdminRole($tree['childAdmin']));
        // Holding the role is not enough on its own without ROLE_GLOBAL_ADMIN either.
        $this->assertFalse($scope->canGrantGlobalAdminRole($tree['grandchildAdmin']));
    }
}
