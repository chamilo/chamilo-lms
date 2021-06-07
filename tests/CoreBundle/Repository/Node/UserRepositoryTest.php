<?php

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testCount()
    {
        self::bootKernel();
        $count = self::getContainer()->get(UserRepository::class)->count([]);
        // Admin + anon (registered in the DataFixture\AccessUrlAdminFixtures.php)
        $this->assertEquals(2, $count);
    }

    public function testCreateUser()
    {
        self::bootKernel();

        $this->createUser('user', 'user', 'user@example.org');

        $count = self::getContainer()->get(UserRepository::class)->count([]);
        $this->assertEquals(3, $count);
    }
}
