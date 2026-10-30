<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Helpers;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Helpers\WeakPasswordCheckerHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

final class WeakPasswordCheckerScopeTest extends TestCase
{
    public function testPlainAdminSeesOnlyDirectlyRegisteredUrls(): void
    {
        $admin = (new User())->setRoles(['ROLE_ADMIN']);
        $url = new AccessUrl();
        (new ReflectionProperty(AccessUrl::class, 'id'))->setValue($url, 17);
        $admin->setPortal((new AccessUrlRelUser())->setUrl($url));

        self::assertSame([17], $this->visibleUrls($admin));
    }

    public function testPlainAdminWithoutRegisteredUrlSeesNobody(): void
    {
        $admin = (new User())->setRoles(['ROLE_ADMIN']);

        self::assertSame([], $this->visibleUrls($admin));
    }

    public function testScopedGlobalAdminSeesItsSubtree(): void
    {
        $admin = (new User())->setRoles(['ROLE_GLOBAL_ADMIN']);
        (new ReflectionProperty(User::class, 'id'))->setValue($admin, 2);

        self::assertSame([17, 18], $this->visibleUrls($admin, false, [17, 18]));
    }

    public function testUnrestrictedGlobalAdminSeesAllUrls(): void
    {
        $admin = (new User())->setRoles(['ROLE_GLOBAL_ADMIN']);
        (new ReflectionProperty(User::class, 'id'))->setValue($admin, 2);

        self::assertNull($this->visibleUrls($admin, true));
    }

    private function visibleUrls(User $admin, bool $unrestricted = false, array $descendants = []): ?array
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchOne')->willReturn($unrestricted ? 1 : false);
        $connection->method('fetchFirstColumn')->willReturn($descendants);
        $scope = new AccessUrlScopeHelper($connection);
        $checker = new WeakPasswordCheckerHelper(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserRepository::class),
            $scope,
        );

        return (new ReflectionMethod(WeakPasswordCheckerHelper::class, 'getVisibleAccessUrlIds'))
            ->invoke($checker, $admin)
        ;
    }
}
