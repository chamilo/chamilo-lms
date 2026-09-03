<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AccessUrlRepositoryTest extends KernelTestCase
{
    use ChamiloTestTrait;

    public function testAdminInAccessUrl(): void
    {
        self::bootKernel();
        $accessUrl = $this->getAccessUrl();
        $admin = $this->getUser('admin');

        $hasUser = $accessUrl->hasUser($admin);

        $this->assertTrue($hasUser);
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
