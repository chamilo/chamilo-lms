<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AccessUrlRepositoryTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testCount(): void
    {
        self::bootKernel();
        $repo = self::getContainer()->get(AccessUrlRepository::class);
        $count = $repo->count([]);
        // In a fresh installation, Chamilo has one default AccessUrl.
        // Added in AccessUrlFixtures.php
        $this->assertSame(1, $count);

        $this->assertIsInt($repo->getFirstId());
        $this->assertTrue($repo->getFirstId() > 0);

        $this->assertInstanceOf(ResourceType::class, $repo->getResourceType());
    }

    public function testAdminInAccessUrl(): void
    {
        self::bootKernel();
        $accessUrl = $this->getAccessUrl();
        $admin = $this->getUser('admin');

        $hasUser = $accessUrl->hasUser($admin);

        $this->assertTrue($hasUser);
    }

    public function testCreateAccessUrl(): void
    {
        self::bootKernel();

        $repo = self::getContainer()->get(AccessUrlRepository::class);

        $admin = $this->getUser('admin');

        $accessUrl = (new AccessUrl())
            ->setUrl('https://example.org')
            ->setActive(1)
            ->setCreator($admin)
        ;

        $this->assertHasNoEntityViolations($accessUrl);
        $repo->create($accessUrl);

        $this->assertSame(2, $repo->count([]));
        $this->assertSame(0, $accessUrl->getSettings()->count());
    }
}
