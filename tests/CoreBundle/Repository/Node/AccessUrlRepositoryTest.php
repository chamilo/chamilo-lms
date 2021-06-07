<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AccessUrlRepositoryTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testCount()
    {
        self::bootKernel();
        $count = self::getContainer()->get(AccessUrlRepository::class)->count([]);
        // In a fresh installation, Chamilo has one default AccessUrl.
        // Added in AccessUrlFixtures.php
        $this->assertEquals(1, $count);
    }

    public function testAdminInAccessUrl()
    {
        self::bootKernel();
        $accessUrl = $this->getAccessUrl();
        $admin = $this->getUser('admin');

        $hasUser = $accessUrl->hasUser($admin);

        $this->assertEquals(true, $hasUser);
    }
}
