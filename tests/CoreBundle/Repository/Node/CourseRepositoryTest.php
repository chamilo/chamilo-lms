<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Entity\CourseRelUser;
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

    public function testCreateEntity(): void
    {
        self::bootKernel();
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $em = $this->getEntityManager();
        $category = (new CourseCategory())
            ->setCode('Course cat')
            ->setName('Course cat')
        ;
        $em->persist($category);
        $em->flush();

        $course = (new Course())
            ->setTitle('test julio')
            ->setCreator($this->getUser('admin'))
            ->addAccessUrl($this->getAccessUrl())
            ->setCourseLanguage('en')
            ->setDescription('desc')
            ->setShowScore(0)
            ->addCategory($category)
        ;
        $courseRepo->create($course);

        /** @var Course $course */
        $course = $courseRepo->find($course->getId());
        $this->assertSame('test julio', $course->getName());
        $this->assertSame('test julio (TESTJULIO)', $course->getTitleAndCode());
        $this->assertSame('TESTJULIO', $course->getCode());
        $this->assertSame(1, $course->getCategories()->count());
        $this->assertNotNull($course->getLastVisit());
        $this->assertNotNull($course->getCreationDate());
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
        $this->assertCount(25, $course->getTools());

        // Check resource links for each Tool
        foreach ($course->getTools() as $tool) {
            $this->assertSame(
                1,
                $tool->getResourceNode()->getResourceLinks()->count(),
                sprintf("Tool '%s' needs a ResourceLink ", $tool->getResourceNode()->getTitle())
            );
        }

        // The course should be connected with the current Access URL.
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
        $course->addUser($student, 0, null, CourseRelUser::STUDENT);
        $courseRepo->update($course);

        $this->assertTrue($course->hasStudent($student));
        $this->assertFalse($course->hasTeacher($student));

        $this->assertSame(1, $course->getUsers()->count());

        // Add the same user again:
        $course->addUser($student, 0, null, CourseRelUser::STUDENT);
        $courseRepo->update($course);

        $this->assertSame(1, $course->getUsers()->count());
        $this->assertSame(1, $course->getStudents()->count());
        $this->assertSame(0, $course->getTeachers()->count());

        // Retrieve the admin
        $user = $this->getUser('student');

        $client->loginUser($user);

        $client->request('GET', sprintf('/course/%s/home', $course->getId()));
        $this->assertResponseIsSuccessful();
    }

    public function testCourseTeacherSubscription(): void
    {
        $client = static::createClient();

        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        // Create default course.
        $course = $this->createCourse('Test course');
        $course->setVisibility(Course::REGISTERED);

        // Create a teacher.
        $teacher = $this->createUser('teacher', 'teacher');
        $teacher2 = $this->createUser('teacher2', 'teacher2');

        // Add user to the course.
        // Add the same user again:
        $course->addUser($teacher, 0, null, CourseRelUser::TEACHER);
        $courseRepo->update($course);

        $this->assertFalse($course->hasStudent($teacher));
        $this->assertTrue($course->hasTeacher($teacher));

        $course->addTeacher($teacher2);
        $courseRepo->update($course);

        $this->assertFalse($course->hasStudent($teacher2));
        $this->assertTrue($course->hasTeacher($teacher2));

        $this->assertSame(2, $course->getUsers()->count());
        $this->assertSame(0, $course->getStudents()->count());
        $this->assertSame(2, $course->getTeachers()->count());

        // Test adding again.
        $course->addUser($teacher, 0, null, CourseRelUser::TEACHER);
        $courseRepo->update($course);
        $this->assertSame(2, $course->getTeachers()->count());

        // Retrieve the admin
        $user = $this->getUser('teacher');

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
        //$student2 = $this->createUser('student2');
    }
}
