<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

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
            ->setTitle('test')
            ->addAccessUrl($this->getAccessUrl())
        ;
        $courseRepo->create($course);
    }

    public function testCreateCourseSameTitle(): void
    {
        self::bootKernel();
        $course = $this->createCourse('Test course');
        $this->assertSame('TESTCOURSE', $course->getCode());

        $course = $this->createCourse('Test course');
        $this->assertSame('TESTCOURSE1', $course->getCode());
    }

    /**
     * Create a course with a creator + check course tool creation (ToolChain).
     */
    public function testCreate(): void
    {
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $course = $this->createCourse('Test course');

        $this->assertHasNoEntityViolations($course);

        $count = $courseRepo->count([]);
        $this->assertSame(1, $count);

        // Check tools.
        $this->assertSame(23, \count($course->getTools()));

        // Check resource links for each Tool
        foreach ($course->getTools() as $tool) {
            $this->assertSame(
                1,
                $tool->getResourceNode()->getResourceLinks()->count(),
                sprintf("Tool '%s' needs a ResourceLink ", $tool->getResourceNode()->getTitle())
            );
        }

        // The course should connected with the current Access URL.
        $this->assertSame(1, $course->getUrls()->count());
    }

    public function testCourseStudentSubscription(): void
    {
        $client = static::createClient();

        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        // Create default course.
        $course = $this->createCourse('Test course');
        $course->setVisibility(Course::REGISTERED);

        // Create a user.
        $student = $this->createUser('student', 'student');

        // Add user to the course.
        $course->addUser($student, 0, null, 5);
        $courseRepo->update($course);

        $this->assertSame(1, $course->getUsers()->count());

        // Add the same user again:
        $course->addUser($student, 0, null, 5);
        $courseRepo->update($course);

        $this->assertSame(1, $course->getUsers()->count());

        // Retrieve the admin
        $user = $this->getUser('student');

        $client->loginUser($user);

        $client->request('GET', sprintf('/course/%s/home', $course->getId()));
        $this->assertResponseIsSuccessful();
    }

    public function testCourseRegisteredVisibility(): void
    {
        $client = static::createClient();

        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        // Create default course.
        $course = $this->createCourse('Test course');
        $course->setVisibility(Course::REGISTERED);
        $courseRepo->update($course);

        // Create a user.
        $student = $this->createUser('student', 'student');

        // Add user to the course.
        $course->addUser($student, 0, null, 5);
        $courseRepo->update($course);

        $this->assertSame(1, $course->getUsers()->count());

        // retrieve the admin
        $user = $this->getUser('student');

        $client->loginUser($user);

        $client->request('GET', sprintf('/course/%s/home', $course->getId()));
        $this->assertResponseIsSuccessful();

        // Create a user.
        $student2 = $this->createUser('student2');
    }
}
