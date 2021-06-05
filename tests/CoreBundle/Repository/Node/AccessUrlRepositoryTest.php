<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AccessUrlRepositoryTest extends KernelTestCase
{
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
        $urlRepo = self::getContainer()->get(AccessUrlRepository::class);
        /** @var AccessUrl $accessUrl */
        $accessUrl = $urlRepo->findOneBy(['url' => AccessUrl::DEFAULT_ACCESS_URL]);
        $userRepository = self::getContainer()->get(UserRepository::class);
        $admin = $userRepository->findByUsername('admin');
        $hasUser = $accessUrl->hasUser($admin);

        $this->assertEquals(true, $hasUser);
    }
}
