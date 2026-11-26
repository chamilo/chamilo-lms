<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * The course settings hold the registration password and decide who may enter the course, so only
 * the platform administrator and the teachers of that course may read or change them. Learners and
 * teachers of other courses can reach the course (it is open to the platform) but must be denied.
 */
final class CourseSettingsApiSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testUnrelatedLearnerCannotReadCourseSettings(): void
    {
        $ctx = $this->bootstrapScenario('read_learner');

        $this->getSettings($ctx['outsider'], $ctx['course']);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUnrelatedLearnerCannotChangeCourseSettings(): void
    {
        $ctx = $this->bootstrapScenario('write_learner');

        $this->postSettings($ctx['outsider'], $ctx['course'], ['subscribe' => false, 'unsubscribe' => true]);
        $this->assertResponseStatusCodeSame(403);

        $this->assertSubscriptionFlagsUnchanged($ctx['course']);
    }

    public function testEnrolledLearnerCannotChangeCourseSettings(): void
    {
        $ctx = $this->bootstrapScenario('write_student');

        $this->getSettings($ctx['student'], $ctx['course']);
        $this->assertResponseStatusCodeSame(403);

        $this->postSettings($ctx['student'], $ctx['course'], ['subscribe' => false, 'unsubscribe' => true]);
        $this->assertResponseStatusCodeSame(403);

        $this->assertSubscriptionFlagsUnchanged($ctx['course']);
    }

    public function testTeacherOfAnotherCourseCannotReadCourseSettings(): void
    {
        $ctx = $this->bootstrapScenario('read_fteacher');

        $this->getSettings($ctx['foreignTeacher'], $ctx['course']);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testCourseTeacherCanReadCourseSettings(): void
    {
        $ctx = $this->bootstrapScenario('read_teacher');

        $this->getSettings($ctx['teacher'], $ctx['course']);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['courseId' => (int) $ctx['course']->getId()]);
    }

    public function testPlatformAdminCanReadCourseSettingsWithoutCourseSubscription(): void
    {
        $ctx = $this->bootstrapScenario('read_admin');
        $admin = $this->createUser('cset_admin_read_admin', '', '', 'ROLE_ADMIN');

        $this->getSettings($admin, $ctx['course']);
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['courseId' => (int) $ctx['course']->getId()]);
    }

    public function testLegacyDepartmentUrlDoesNotBlockSavingOtherSettings(): void
    {
        $ctx = $this->bootstrapScenario('department_url');
        $courseId = (int) $ctx['course']->getId();
        $this->getEntityManager()->getConnection()->update('course', ['department_url' => 'http://'], ['id' => $courseId]);

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']));
        $url = '/api/course-settings?cid='.$courseId;
        $response = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
        $this->assertSame('', $response->toArray()['values']['department_url']);

        $client->request('POST', $url, ['json' => ['values' => ['department_name' => 'Training']]]);
        $this->assertResponseIsSuccessful();
        $this->assertSame('', $this->getEntityManager()->getConnection()->fetchOne('SELECT department_url FROM course WHERE id = ?', [$courseId]));

        $client->request('POST', $url, ['json' => ['values' => ['department_url' => ' HTTPS:// ']]]);
        $this->assertResponseIsSuccessful();
        $this->assertSame('', $this->getEntityManager()->getConnection()->fetchOne('SELECT department_url FROM course WHERE id = ?', [$courseId]));

        $client->request('POST', $url, ['json' => ['values' => ['department_url' => 'https://example.org/training']]]);
        $this->assertResponseIsSuccessful();
        $this->assertSame('https://example.org/training', $this->getEntityManager()->getConnection()->fetchOne('SELECT department_url FROM course WHERE id = ?', [$courseId]));

        $client->request('POST', $url, ['json' => ['values' => ['department_url' => 'not a url']]]);
        $this->assertResponseStatusCodeSame(400);
        $this->assertSame('https://example.org/training', $this->getEntityManager()->getConnection()->fetchOne('SELECT department_url FROM course WHERE id = ?', [$courseId]));
    }

    /**
     * @return array{course: Course, teacher: User, student: User, outsider: User, foreignTeacher: User}
     */
    private function bootstrapScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('CSETA'.$suffix);
        $foreignCourse = $this->createCourse('CSETB'.$suffix);
        $teacher = $this->createUser('cset_teacher_'.$suffix);
        $student = $this->createUser('cset_student_'.$suffix);
        $outsider = $this->createUser('cset_outsider_'.$suffix);
        $foreignTeacher = $this->createUser('cset_fteacher_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $courseRepo->update($course);
        $foreignCourse->addUserAsTeacher($foreignTeacher);
        $foreignCourse->addUserAsStudent($outsider);
        $courseRepo->update($foreignCourse);

        return [
            'course' => $course,
            'teacher' => $teacher,
            'student' => $student,
            'outsider' => $outsider,
            'foreignTeacher' => $foreignTeacher,
        ];
    }

    private function getSettings(User $user, Course $course): void
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'GET',
            '/api/course-settings?cid='.$course->getId(),
            ['headers' => ['Accept' => 'application/json']],
        );
    }

    /**
     * @param array<string, mixed> $values
     */
    private function postSettings(User $user, Course $course, array $values): void
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'POST',
            '/api/course-settings?cid='.$course->getId(),
            [
                'headers' => ['Accept' => 'application/json'],
                'json' => ['values' => $values],
            ],
        );
    }

    private function assertSubscriptionFlagsUnchanged(Course $course): void
    {
        $row = $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT subscribe, unsubscribe FROM course WHERE id = ?',
            [$course->getId()],
        );

        $this->assertSame(1, (int) $row['subscribe']);
        $this->assertSame(0, (int) $row['unsubscribe']);
    }
}
