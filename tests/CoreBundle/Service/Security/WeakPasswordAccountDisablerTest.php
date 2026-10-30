<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Service\Security;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AccessUrlScopeHelper;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CoreBundle\Service\Security\WeakPasswordAccountDisabler;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class WeakPasswordAccountDisablerTest extends TestCase
{
    private function user(int $id, array $roles = [], int $active = User::ACTIVE): User
    {
        $user = (new User())->setRoles($roles)->setActive($active);
        (new ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }

    private function disabler(bool $allow, UserRepository $repository): WeakPasswordAccountDisabler
    {
        $connection = $this->createMock(Connection::class);
        // AccessUrlScopeHelper::isUnrestricted() tests the actor's root URL first.
        $connection->method('fetchOne')->willReturn($allow ? 1 : false);
        $scope = new AccessUrlScopeHelper($connection);

        return new WeakPasswordAccountDisabler($scope, $repository);
    }

    public function testCannotDisableOwnAccount(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::never())->method('updateUser');
        $user = $this->user(42, ['ROLE_ADMIN']);

        self::assertFalse($this->disabler(true, $repository)->disable($user, $user));
        self::assertSame(User::ACTIVE, $user->getActive());
    }

    public function testCannotDisableAnotherAdministrator(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::never())->method('updateUser');
        $actor = $this->user(1, ['ROLE_GLOBAL_ADMIN']);
        $target = $this->user(2, ['ROLE_ADMIN']);

        self::assertFalse($this->disabler(true, $repository)->disable($actor, $target));
        self::assertSame(User::ACTIVE, $target->getActive());
    }

    public function testCannotDisableInactiveAccount(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::never())->method('updateUser');
        $target = $this->user(2, [], User::INACTIVE);

        self::assertFalse($this->disabler(true, $repository)->disable($this->user(1), $target));
    }

    public function testCannotDisableAccountOutsideAdministrativeScope(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::never())->method('updateUser');
        $actor = $this->user(1, ['ROLE_ADMIN']);
        $target = $this->user(2);

        self::assertFalse($this->disabler(false, $repository)->disable($actor, $target));
        self::assertSame(User::ACTIVE, $target->getActive());
    }

    public function testAuthorizedAccountCanBeDisabled(): void
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::once())->method('updateUser')->with(self::callback(
            static fn (User $user): bool => User::INACTIVE === $user->getActive()
        ));
        $actor = $this->user(1, ['ROLE_GLOBAL_ADMIN']);
        $target = $this->user(2);

        self::assertTrue($this->disabler(true, $repository)->disable($actor, $target));
        self::assertSame(User::INACTIVE, $target->getActive());
    }
}
