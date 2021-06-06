<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class CourseRepositoryTest extends WebTestCase
{
    /**
     * Create a course with no creator.
     */
    public function testCreateNoCreator()
    {
        self::bootKernel();
        $urlRepo = self::getContainer()->get(AccessUrlRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $accessUrl = $urlRepo->findOneBy(['url' => AccessUrl::DEFAULT_ACCESS_URL]);

        $this->expectException(UserNotFoundException::class);
        $course = (new Course())
            ->setTitle('test_course')
            ->addAccessUrl($accessUrl)
        ;
        $courseRepo->create($course);

        $count = $courseRepo->count([]);
        $this->assertEquals(1, $count);
    }

    /**
     * Create a course with a creator.
     */
    public function testCreate()
    {
        /** @var UserRepository $userRepository */
        $userRepository = self::getContainer()->get(UserRepository::class);

        $admin = $userRepository->findByUsername('admin');

        $urlRepo = self::getContainer()->get(AccessUrlRepository::class);
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $accessUrl = $urlRepo->findOneBy(['url' => AccessUrl::DEFAULT_ACCESS_URL]);

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
