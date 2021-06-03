<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class CourseRepositoryTest extends WebTestCase
{
    public function testCreateNoLogin()
    {
        self::bootKernel();
        $urlRepo = self::getContainer()->get(AccessUrlRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $accessUrl = $urlRepo->findOneBy(['url' => 'http://localhost/']);

        $this->expectException(UserNotFoundException::class);
        $course = (new Course())
            ->setTitle('test_course')
            ->addAccessUrl($accessUrl)
        ;
        $courseRepo->create($course);

        $count = $courseRepo->count([]);
        $this->assertEquals(1, $count);
    }

    public function testCreate()
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->getContainer()->get(UserRepository::class);

        $admin = $userRepository->findByUsername('admin');

        $urlRepo = self::getContainer()->get(AccessUrlRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $accessUrl = $urlRepo->findOneBy(['url' => 'http://localhost/']);

        $course = (new Course())
            ->setTitle('Test course')
            ->setCode('test_course')
            ->addAccessUrl($accessUrl)
            ->setCreator($admin)
        ;
        $courseRepo->create($course);

        $count = $courseRepo->count([]);
        $this->assertEquals(1, $count);
    }
}
