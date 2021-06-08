<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\ToolChain;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

/**
 * @covers \CourseRepository
 */
class CourseRepositoryTest extends WebTestCase
{
    use ChamiloTestTrait;

    /**
     * Create a course with no creator.
     */
    public function testCreateNoCreator(): void
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
    public function testCreate(): void
    {
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $course = $this->createCourse('Test course');

        $count = $courseRepo->count([]);
        $this->assertSame(1, $count);

        // Check tools.
        $this->assertSame(25, \count($course->getTools()));

        // Check course code.
        $this->assertSame('TEST-COURSE', $course->getCode());

        // The course should connected with a Access URL
        $this->assertSame(1, $course->getUrls()->count());
    }

    public function testCourseAccess(): void
    {
        self::bootKernel();
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        //$toolChain = self::getContainer()->get(ToolChain::class);
        $course = $this->createCourse('Test course');

        $student = $this->createUser('student', 'student', 'student@student.com');

        $course->addUser($student, 0, null, 5);

        $courseRepo->update($course);

        $this->assertSame(1, $course->getUsers()->count());
    }
}
