<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Repository\Node;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseCategory;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class CourseRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * Create a course with no creator.
     */
    public function testCreateNoCreator(): void
    {
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $this->expectException(UserNotFoundException::class);

        $course = (new Course())
            ->setTitle('test')
            ->setCode('test')
            ->setVisualCode('test')
            ->setDepartmentUrl('https://chamilo.org')
            ->addAccessUrl($this->getAccessUrl())
        ;
        $courseRepo->create($course);

        $this->assertTrue($course->isActive());
        $this->assertIsArray(Course::getStatusList());
    }

    public function testCreateEntity(): void
    {
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $em = $this->getEntityManager();
        $category = (new CourseCategory())
            ->setCode('Course cat')
            ->setName('Course cat')
            ->setDescription('desc')
            ->setAuthCatChild('cat')
            ->setAuthCourseChild('cat')
            ->setChildrenCount(0)
            ->setTreePos(0)
        ;
        $em->persist($category);
        $em->flush();

        $this->assertFalse($category->hasAsset());

        $course = (new Course())
            ->setTitle('test julio')
            ->setCreator($this->getUser('admin'))
            ->addAccessUrl($this->getAccessUrl())
            ->setCourseLanguage('en')
            ->setDescription('desc')
            ->setShowScore(0)
            ->setDiskQuota(0)
            ->setLastVisit(new DateTime())
            ->setCreationDate(new DateTime())
            ->setExpirationDate(new DateTime())
            ->setSubscribe(true)
            ->setUnsubscribe(false)
            ->setVideoUrl('https://example.com/video.mp4')
            ->setSticky(false)
            ->setRegistrationCode('123')
            ->setLegal('123')
            ->setActivateLegal(123)
            ->setCourseTypeId(1)
            ->setIntroduction('intro')
            ->addCategory($category)
        ;
        $courseRepo->create($course);

        $course = $this->getCourse($course->getId());

        $this->assertSame('test julio', $course->getName());
        $this->assertSame('test julio (TESTJULIO)', $course->getTitleAndCode());
        $this->assertSame('TESTJULIO', $course->getCode());
        $this->assertSame(1, $course->getCategories()->count());
        $this->assertNotNull($course->getLastVisit());
        $this->assertNotNull($course->getCreationDate());
    }

    public function testCreateCourseSameTitle(): void
    {
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

    public function testDeleteCourse(): void
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Test course');

        $courseRepo->deleteCourse($course);

        $this->assertSame(0, $courseRepo->count([]));
    }

    public function testGetCoursesByUser(): void
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $student = $this->createUser('student');
        $course = $this->createCourse('Test course');

        $courses = $courseRepo->getCoursesByUser($student, $this->getAccessUrl());
        $this->assertCount(0, $courses);

        $course->addSubscriptionForUser($student, 0, '', CourseRelUser::STUDENT);
        $courseRepo->update($course);

        $this->assertTrue($course->hasSubscriptionByUser($student));
        $this->assertTrue($course->hasUserAsStudent($student));
        $this->assertFalse($course->hasUserAsTeacher($student));

        $courses = $courseRepo->getCoursesByUser($student, $this->getAccessUrl());
        $this->assertCount(1, $courses);
    }

    public function testGetSubscribedUsers(): void
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $student = $this->createUser('student');
        $course = $this->createCourse('Test course');

        $qb = $courseRepo->getSubscribedUsers($course);
        $this->assertCount(0, $qb->getQuery()->getResult());

        $course->addSubscriptionForUser($student, 0, '', CourseRelUser::STUDENT);
        $courseRepo->update($course);

        $qb = $courseRepo->getSubscribedUsers($course);
        $this->assertCount(1, $qb->getQuery()->getResult());

        $qb = $courseRepo->getSubscribedStudents($course);
        $this->assertCount(1, $qb->getQuery()->getResult());

        $qb = $courseRepo->getSubscribedCoaches($course);
        $this->assertCount(1, $qb->getQuery()->getResult());

        $qb = $courseRepo->getSubscribedTeachers($course);
        $this->assertCount(0, $qb->getQuery()->getResult());
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
        $course->addSubscriptionForUser($student, 0, null, CourseRelUser::STUDENT);
        $courseRepo->update($course);

        $this->assertTrue($course->hasUserAsStudent($student));
        $this->assertFalse($course->hasUserAsTeacher($student));

        $this->assertSame(1, $course->getUsers()->count());

        // Add the same user again:
        $course->addSubscriptionForUser($student, 0, null, CourseRelUser::STUDENT);
        $courseRepo->update($course);

        $this->assertSame(1, $course->getUsers()->count());
        $this->assertSame(1, $course->getStudentSubscriptions()->count());
        $this->assertSame(0, $course->getTeachersSubscriptions()->count());

        $client->request('GET', sprintf('/course/%s/home', $course->getId()));
        $this->assertResponseIsSuccessful();
    }

    public function testCourseTeacherSubscription(): void
    {
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
        $course->addSubscriptionForUser($teacher, 0, null, CourseRelUser::TEACHER);
        $courseRepo->update($course);

        $this->assertFalse($course->hasUserAsStudent($teacher));
        $this->assertTrue($course->hasUserAsTeacher($teacher));

        $course->addUserAsTeacher($teacher2);
        $courseRepo->update($course);

        $this->assertFalse($course->hasUserAsStudent($teacher2));
        $this->assertTrue($course->hasUserAsTeacher($teacher2));

        $this->assertSame(2, $course->getUsers()->count());
        $this->assertSame(0, $course->getStudentSubscriptions()->count());
        $this->assertSame(2, $course->getTeachersSubscriptions()->count());

        // Test adding again.
        $course->addSubscriptionForUser($teacher, 0, null, CourseRelUser::TEACHER);
        $courseRepo->update($course);
        $this->assertSame(2, $course->getTeachersSubscriptions()->count());

        $teacher = $this->getUser('teacher');

        $token = $this->getUserTokenFromUser($teacher);
        $this->createClientWithCredentials($token)->request('GET', sprintf('/course/%s/home', $course->getId()));
        $this->assertResponseIsSuccessful();
    }

    public function testCourseRegisteredVisibility(): void
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        // Create default course.
        $course = $this->createCourse('Test course');
        $course->setVisibility(Course::REGISTERED);
        $courseRepo->update($course);

        // Create a user.
        $student = $this->createUser('student', 'student');

        // Add user to the course.
        $course->addSubscriptionForUser($student, 0, null, 5);
        $courseRepo->update($course);

        $this->assertSame(1, $course->getUsers()->count());

        // retrieve the admin
        $student = $this->getUser('student');
        $token = $this->getUserTokenFromUser($student);
        $this->createClientWithCredentials($token)->request('GET', sprintf('/course/%s/home', $course->getId()));
        $this->assertResponseIsSuccessful();
    }

    public function testGetCourses(): void
    {
        $this->createCourse('new');

        // Test as admin.
        $token = $this->getUserToken([]);
        $this->createClientWithCredentials($token)->request('GET', '/api/courses');
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Course',
            '@id' => '/api/courses',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
        ]);

        $student = $this->createUser('student');
        $token = $this->getUserTokenFromUser($student);
        $response = $this->createClientWithCredentials($token)->request('GET', '/api/courses');
        $this->assertResponseIsSuccessful();

        // Asserts that the returned JSON is a superset of this one
        $this->assertJsonContains([
            '@context' => '/api/contexts/Course',
            '@id' => '/api/courses',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
        ]);
        $this->assertCount(1, $response->toArray()['hydra:member']);
    }
}
