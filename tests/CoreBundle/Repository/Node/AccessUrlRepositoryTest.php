<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\AccessUrlRelCourse;
use Chamilo\CoreBundle\Entity\ResourceType;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Doctrine\Common\Collections\ArrayCollection;
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
        ;

        $this->assertHasNoEntityViolations($accessUrl);
        $repo->create($accessUrl);

        $accessUrl->addUser($user);
        $repo->update($accessUrl);

        /** @var AccessUrl $accessUrl */
        $accessUrl = $repo->find($accessUrl->getId());

        $this->assertTrue($accessUrl->hasUser($user));
        $this->assertSame($accessUrl->getId(), $accessUrl->getResourceIdentifier());

        $this->assertSame(1000, $accessUrl->getLimitCourses());
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
        $accessUrlCourse = (new AccessUrlRelCourse())
            ->setCourse($course)
            ->setUrl($accessUrl)
        ;

        $collection = new ArrayCollection();
        $collection->add($accessUrlCourse);

        $accessUrl->setCourses($collection);

        /** @var AccessUrl $accessUrl */
        $accessUrl = $repo->find($accessUrl->getId());
        $this->assertSame(1, $accessUrl->getCourses()->count());
    }
}
