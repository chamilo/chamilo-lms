<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
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
        $user = $this->createUser('test');

        $accessUrl = (new AccessUrl())
            ->setUrl('https://example.org')
            ->setActive(1)
            ->setCreator($admin)
            ->setCreatedBy($admin->getId())
            ->setDescription('test')
            ->setEmail('test@example.com')
            ->setLimitDiskSpace(1000)
            ->setLimitCourses(1000)
            ->setLimitSessions(1000)
            ->setLimitTeachers(1000)
            ->setLimitUsers(1000)
            ->setLimitActiveCourses(1000)
            ->setTms(new DateTime())
            ->setCreatedBy(1)
            ->setUrlType(true)
        ;

        $this->assertHasNoEntityViolations($accessUrl);
        $repo->create($accessUrl);

        $accessUrl->addUser($user);
        $repo->update($accessUrl);

        /** @var AccessUrl $accessUrl */
        $accessUrl = $repo->find($accessUrl->getId());

        $this->assertNotNull($accessUrl->getTms());
        $this->assertSame(1, $accessUrl->getLft());
        $this->assertSame(2, $accessUrl->getRgt());
        $this->assertSame(0, $accessUrl->getLvl());
        $this->assertSame(1, $accessUrl->getCreatedBy());
        $this->assertTrue($accessUrl->getUrlType());

        $this->assertSame(1000, $accessUrl->getLimitActiveCourses());
        $this->assertSame(1000, $accessUrl->getLimitCourses());
        $this->assertSame(1000, $accessUrl->getLimitSessions());
        $this->assertSame(1000, $accessUrl->getLimitTeachers());
        $this->assertSame(1000, $accessUrl->getLimitUsers());
        $this->assertSame(1000, $accessUrl->getLimitDiskSpace());

        $this->assertTrue($accessUrl->hasUser($user));
        $this->assertSame($accessUrl->getId(), $accessUrl->getResourceIdentifier());

        $this->assertSame('test', $accessUrl->getDescription());
        $this->assertSame('https://example.org', (string) $accessUrl);
        $this->assertSame('test@example.com', $accessUrl->getEmail());
        $this->assertSame(2, $repo->count([]));
        $this->assertSame(0, $accessUrl->getSettings()->count());
    }

    public function testSetCourses(): void
    {
        self::bootKernel();

        $repo = self::getContainer()->get(AccessUrlRepository::class);

        $admin = $this->getUser('admin');

        $accessUrl = (new AccessUrl())
            ->setUrl('https://example.org')
            ->setActive(1)
            ->setCreator($admin)
        ;
        $repo->create($accessUrl);

        /** @var AccessUrl $accessUrl */
        $accessUrl = $repo->find($accessUrl->getId());

        $course = $this->createCourse('test');
        $accessUrl->addCourse($course);

        $this->getEntityManager()->flush();

        $this->assertSame(1, $accessUrl->getCourses()->count());
    }
}
