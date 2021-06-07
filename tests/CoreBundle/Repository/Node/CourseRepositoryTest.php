<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class CourseRepositoryTest extends WebTestCase
{
    use ChamiloTestTrait;

    /**
     * Create a course with no creator.
     */
    public function testCreateNoCreator()
    {
        self::bootKernel();
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $this->expectException(UserNotFoundException::class);
        $course = (new Course())
            ->setTitle('test_course')
            ->addAccessUrl($this->getAccessUrl())
        ;
        $courseRepo->create($course);
    }

    /**
     * Create a course with a creator.
     */
    public function testCreate()
    {
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = (new Course())
            ->setTitle('Test course')
            ->setCode('test_course')
            ->addAccessUrl($this->getAccessUrl())
            ->setCreator($this->getUser('admin'))
        ;
        $courseRepo->create($course);

        $count = $courseRepo->count([]);
        $this->assertEquals(1, $count);
    }
}
