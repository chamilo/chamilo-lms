<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CourseRelUserTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createAdminUser(string $suffix): User
    {
        return $this->createUser('cru_admin_'.$suffix, 'cru_admin_'.$suffix, '', 'ROLE_ADMIN');
    }

    private function getCourseIri(Course $course): string
    {
        return '/api/courses/'.$course->getId();
    }

    // -------------------------------------------------------------------------
    // GET /api/course_rel_users/{id}
    // -------------------------------------------------------------------------

    public function testGetSingleCourseRelUserAsAdmin(): void
    {
        $course = $this->createCourse('Test Course Get Single');
        $student = $this->createUser('student_get_single');
        $admin = $this->createAdminUser('get_single');

        $em = $this->getEntityManager();
        $subscription = (new CourseRelUser())
            ->setCourse($course)
            ->setUser($student)
            ->setStatus(CourseRelUser::STUDENT)
            ->setRelationType(0)
        ;
        $em->persist($subscription);
        $em->flush();

        $tokenAdmin = $this->getUserTokenFromUser($admin);

        $this->createClientWithCredentials($tokenAdmin)->request(
            'GET',
            '/api/course_rel_users/'.$subscription->getId(),
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/CourseRelUser',
            '@type' => 'CourseRelUser',
            'status' => CourseRelUser::STUDENT,
        ]);
    }

    public function testGetSingleCourseRelUserAsOwnerStudent(): void
    {
        $course = $this->createCourse('Test Course Owner Student');
        $student = $this->createUser('student_owner');

        $em = $this->getEntityManager();
        $subscription = (new CourseRelUser())
            ->setCourse($course)
            ->setUser($student)
            ->setStatus(CourseRelUser::STUDENT)
            ->setRelationType(0)
        ;
        $em->persist($subscription);
        $em->flush();

        $tokenStudent = $this->getUserTokenFromUser($student);

        $this->createClientWithCredentials($tokenStudent)->request(
            'GET',
            '/api/course_rel_users/'.$subscription->getId(),
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }

    public function testGetSingleCourseRelUserForbiddenForOtherUser(): void
    {
        $course = $this->createCourse('Test Course Forbidden Single');
        $student = $this->createUser('student_for_bid');
        $otherUser = $this->createUser('other_user_get');

        $em = $this->getEntityManager();
        $subscription = (new CourseRelUser())
            ->setCourse($course)
            ->setUser($student)
            ->setStatus(CourseRelUser::STUDENT)
            ->setRelationType(0)
        ;
        $em->persist($subscription);
        $em->flush();

        $tokenOther = $this->getUserTokenFromUser($otherUser);

        $this->createClientWithCredentials($tokenOther)->request(
            'GET',
            '/api/course_rel_users/'.$subscription->getId(),
        );

        $this->assertResponseStatusCodeSame(403);
    }

    // -------------------------------------------------------------------------
    // GET /api/course_rel_users (collection)
    // -------------------------------------------------------------------------

    public function testGetCollectionAsAdminReturnsAll(): void
    {
        $course = $this->createCourse('Collection Admin Course');
        $studentA = $this->createUser('student_coll_a');
        $studentB = $this->createUser('student_coll_b');
        $admin = $this->createAdminUser('coll_all');

        $em = $this->getEntityManager();
        foreach ([$studentA, $studentB] as $student) {
            $sub = (new CourseRelUser())
                ->setCourse($course)
                ->setUser($student)
                ->setStatus(CourseRelUser::STUDENT)
                ->setRelationType(0)
            ;
            $em->persist($sub);
        }
        $em->flush();

        $tokenAdmin = $this->getUserTokenFromUser($admin);

        $this->createClientWithCredentials($tokenAdmin)->request(
            'GET',
            '/api/course_rel_users',
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@context' => '/api/contexts/CourseRelUser',
            '@type' => 'hydra:Collection',
        ]);
    }

    public function testGetCollectionAsStudentReturnsOwnCoursesOnly(): void
    {
        $courseA = $this->createCourse('Student Coll Course A');
        $courseB = $this->createCourse('Student Coll Course B');
        $studentX = $this->createUser('student_coll_x');
        $studentY = $this->createUser('student_coll_y');

        $em = $this->getEntityManager();

        // studentX subscribed to courseA
        $subX = (new CourseRelUser())
            ->setCourse($courseA)
            ->setUser($studentX)
            ->setStatus(CourseRelUser::STUDENT)
            ->setRelationType(0)
        ;
        $em->persist($subX);

        // studentY subscribed to courseB (different course, unrelated)
        $subY = (new CourseRelUser())
            ->setCourse($courseB)
            ->setUser($studentY)
            ->setStatus(CourseRelUser::STUDENT)
            ->setRelationType(0)
        ;
        $em->persist($subY);
        $em->flush();

        $tokenX = $this->getUserTokenFromUser($studentX);

        $response = $this->createClientWithCredentials($tokenX)->request(
            'GET',
            '/api/course_rel_users',
        );

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        // studentX must see only subscriptions for courses they belong to.
        // studentY's courseB subscription must NOT appear.
        foreach ($data['hydra:member'] as $member) {
            $courseIri = \is_array($member['course']) ? $member['course']['@id'] : $member['course'];
            $this->assertStringNotContainsString((string) $courseB->getId(), $courseIri);
        }
    }

    public function testGetCollectionUnauthenticatedReturns401(): void
    {
        static::createClient()->request('GET', '/api/course_rel_users');

        $this->assertResponseStatusCodeSame(401);
    }

    // -------------------------------------------------------------------------
    // GET /me/courses (UserCourseSubscriptionsStateProvider)
    // -------------------------------------------------------------------------

    public function testGetMeCoursesAsStudent(): void
    {
        $course = $this->createCourse('Me Courses Student Course');
        $student = $this->createUser('student_me_courses');

        $em = $this->getEntityManager();
        $sub = (new CourseRelUser())
            ->setCourse($course)
            ->setUser($student)
            ->setStatus(CourseRelUser::STUDENT)
            ->setRelationType(0)
        ;
        $em->persist($sub);
        $em->flush();

        $token = $this->getUserTokenFromUser($student);

        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/me/courses.jsonld',
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/CourseRelUser',
            '@type' => 'hydra:Collection',
        ]);

        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(1, $data['hydra:totalItems']);
    }

    public function testGetMeCoursesUnauthenticatedReturns401(): void
    {
        static::createClient()->request('GET', '/api/me/courses.jsonld');

        $this->assertResponseStatusCodeSame(401);
    }

    // -------------------------------------------------------------------------
    // POST /api/course_rel_users
    // -------------------------------------------------------------------------

    public function testPostSubscribeStudentToPublicCourse(): void
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Post Public Course');
        // Make course visible in public catalogue (OPEN_WORLD = 3).
        $course->setVisibility(Course::OPEN_WORLD);
        $courseRepo->update($course);

        $student = $this->createUser('student_post_pub');

        $token = $this->getUserTokenFromUser($student);

        $response = $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/course_rel_users',
            [
                'json' => [
                    'course' => $this->getCourseIri($course),
                    'user' => $student->getIri(),
                    'relationType' => 0,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/CourseRelUser',
            '@type' => 'CourseRelUser',
            'status' => CourseRelUser::STUDENT,
        ]);
    }

    public function testPostSubscribeStudentToNonPublicCourseForbidden(): void
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Post Private Course');
        // CLOSED and HIDDEN courses are excluded from the public catalogue.
        $course->setVisibility(Course::CLOSED);
        $courseRepo->update($course);

        $student = $this->createUser('student_post_priv');

        $token = $this->getUserTokenFromUser($student);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/course_rel_users',
            [
                'json' => [
                    'course' => $this->getCourseIri($course),
                    'user' => $student->getIri(),
                    'relationType' => 0,
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    public function testPostSubscribeAsAdminBypassesCatalogueCheck(): void
    {
        $course = $this->createCourse('Admin Post Private Course');
        $admin = $this->createAdminUser('post_bypass');

        $tokenAdmin = $this->getUserTokenFromUser($admin);

        $response = $this->createClientWithCredentials($tokenAdmin)->request(
            'POST',
            '/api/course_rel_users',
            [
                'json' => [
                    'course' => $this->getCourseIri($course),
                    'user' => $admin->getIri(),
                    'relationType' => 0,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
    }

    public function testPostSubscribeUnauthenticatedReturns401(): void
    {
        $course = $this->createCourse('Unauth Post Course');

        static::createClient()->request(
            'POST',
            '/api/course_rel_users',
            [
                'json' => [
                    'course' => $this->getCourseIri($course),
                    'user' => '/api/users/1',
                    'relationType' => 0,
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(401);
    }

    public function testPostSubscribeStudentForAnotherUserForbidden(): void
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Post Other User Course');
        $course->setVisibility(Course::OPEN_WORLD);
        $courseRepo->update($course);

        $studentA = $this->createUser('student_post_a');
        $studentB = $this->createUser('student_post_b');

        $tokenA = $this->getUserTokenFromUser($studentA);

        // studentA tries to subscribe studentB — securityPostDenormalize blocks this.
        $this->createClientWithCredentials($tokenA)->request(
            'POST',
            '/api/course_rel_users',
            [
                'json' => [
                    'course' => $this->getCourseIri($course),
                    'user' => $studentB->getIri(),
                    'relationType' => 0,
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    // -------------------------------------------------------------------------
    // Status / role checks
    // -------------------------------------------------------------------------

    public function testStudentStatusIsSetByDefault(): void
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Default Status Course');
        $course->setVisibility(Course::OPEN_WORLD);
        $courseRepo->update($course);

        $student = $this->createUser('student_default_status');

        $token = $this->getUserTokenFromUser($student);

        $response = $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/course_rel_users',
            [
                'json' => [
                    'course' => $this->getCourseIri($course),
                    'user' => $student->getIri(),
                    'relationType' => 0,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        $this->assertSame(CourseRelUser::STUDENT, $data['status']);
    }

    public function testAdminCanSubscribeToClosedCourseBypassingCatalogueCheck(): void
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        // A CLOSED course is excluded from the public catalogue.
        $course = $this->createCourse('Admin Closed Course Subscription');
        $course->setVisibility(Course::CLOSED);
        $courseRepo->update($course);

        $admin = $this->createAdminUser('sub_closed');

        $tokenAdmin = $this->getUserTokenFromUser($admin);

        // Admin can subscribe themselves to a course regardless of catalogue status.
        $response = $this->createClientWithCredentials($tokenAdmin)->request(
            'POST',
            '/api/course_rel_users',
            [
                'json' => [
                    'course' => $this->getCourseIri($course),
                    'user' => $admin->getIri(),
                    'relationType' => 0,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
    }

    public function testAdminCannotSubscribeAnotherUserViaApi(): void
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Admin Subscribe Other User Course');
        $course->setVisibility(Course::OPEN_WORLD);
        $courseRepo->update($course);

        $admin = $this->createAdminUser('sub_other');
        $teacher = $this->createUser('teacher_sub_other', '', '', 'ROLE_TEACHER');

        $tokenAdmin = $this->getUserTokenFromUser($admin);

        // securityPostDenormalize: 'object.getUser() == user' blocks this even for admins.
        $this->createClientWithCredentials($tokenAdmin)->request(
            'POST',
            '/api/course_rel_users',
            [
                'json' => [
                    'course' => $this->getCourseIri($course),
                    'user' => $teacher->getIri(),
                    'relationType' => 0,
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(403);
    }

    // -------------------------------------------------------------------------
    // Filtering
    // -------------------------------------------------------------------------

    public function testGetCollectionFilterByCourse(): void
    {
        $course = $this->createCourse('Filter By Course');
        $otherCourse = $this->createCourse('Other Course Filter');
        $student = $this->createUser('student_filter_course');
        $admin = $this->createAdminUser('filter_course');

        $em = $this->getEntityManager();
        $sub = (new CourseRelUser())
            ->setCourse($course)
            ->setUser($student)
            ->setStatus(CourseRelUser::STUDENT)
            ->setRelationType(0)
        ;
        $em->persist($sub);
        $em->flush();

        $tokenAdmin = $this->getUserTokenFromUser($admin);

        $response = $this->createClientWithCredentials($tokenAdmin)->request(
            'GET',
            '/api/course_rel_users?course='.$course->getId(),
        );

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        foreach ($data['hydra:member'] as $member) {
            $courseIri = \is_array($member['course']) ? $member['course']['@id'] : $member['course'];
            $this->assertStringNotContainsString((string) $otherCourse->getId(), $courseIri);
        }
    }

    public function testGetCollectionFilterByStatus(): void
    {
        $course = $this->createCourse('Filter By Status Course');
        $teacher = $this->createUser('teacher_filter_status', '', '', 'ROLE_TEACHER');
        $admin = $this->createAdminUser('filter_status');

        $em = $this->getEntityManager();
        $sub = (new CourseRelUser())
            ->setCourse($course)
            ->setUser($teacher)
            ->setStatus(CourseRelUser::TEACHER)
            ->setRelationType(0)
        ;
        $em->persist($sub);
        $em->flush();

        $tokenAdmin = $this->getUserTokenFromUser($admin);

        $response = $this->createClientWithCredentials($tokenAdmin)->request(
            'GET',
            '/api/course_rel_users?status='.CourseRelUser::TEACHER,
        );

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        foreach ($data['hydra:member'] as $member) {
            $this->assertSame(CourseRelUser::TEACHER, $member['status']);
        }
    }

    // -------------------------------------------------------------------------
    // CourseRelUserRepository helpers
    // -------------------------------------------------------------------------

    public function testRepositoryCountTaughtCoursesForUser(): void
    {
        $course = $this->createCourse('Taught Course Count');
        $teacher = $this->createUser('teacher_count_taught');

        $em = $this->getEntityManager();
        $sub = (new CourseRelUser())
            ->setCourse($course)
            ->setUser($teacher)
            ->setStatus(CourseRelUser::TEACHER)
            ->setRelationType(0)
        ;
        $em->persist($sub);
        $em->flush();

        /** @var CourseRelUserRepository $repo */
        $repo = self::getContainer()->get(CourseRelUserRepository::class);

        $count = $repo->countTaughtCoursesForUser($teacher);

        $this->assertGreaterThanOrEqual(1, $count);
    }
}
