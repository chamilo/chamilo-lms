<?php

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @covers \UserRepository
 */
class UserRepositoryTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testCount(): void
    {
        self::bootKernel();
        $count = self::getContainer()->get(UserRepository::class)->count([]);
        // Admin + anon (registered in the DataFixture\AccessUrlAdminFixtures.php)
        $this->assertSame(2, $count);
    }

    public function testCreateUser(): void
    {
        self::bootKernel();

        $this->createUser('user', 'user');

        $count = self::getContainer()->get(UserRepository::class)->count([]);
        $this->assertSame(3, $count);
    }
}
