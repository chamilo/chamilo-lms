<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

/**
 * Regression tests for the cross-course assignment-comment advisory.
 *
 * The advisory reported that authenticated users outside the course/session
 * scope could create comments and overwrite grading fields on arbitrary
 * student-publication submissions via:
 *   - POST /api/c_student_publication_comments/upload
 *
 * These tests assert that an unrelated authenticated user (the attacker) is
 * denied (403) for both commenting and grading paths, that the submission
 * owner cannot grade themselves, and that a course teacher still works.
 */
final class CStudentPublicationCommentTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * Builds a private (REGISTERED) course with a teacher and a student
     * subscribed to it, creates an assignment (parent publication) owned by
     * the teacher, and a submission (child publication) authored by the
     * student. Also returns an attacker user with no relation to the course.
     *
     * @return array{
     *     course: Course,
     *     teacher: User,
     *     student: User,
     *     attacker: User,
     *     assignment: CStudentPublication,
     *     submission: CStudentPublication
     * }
     */
    private function bootstrapSubmissionScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Work Sec Course '.$suffix);
        // REGISTERED → only enrolled users get ROLE_CURRENT_COURSE_* via CourseVoter.
        $course->setVisibility(Course::REGISTERED);

        $teacher = $this->createUser('work_sec_teacher_'.$suffix);
        $student = $this->createUser('work_sec_student_'.$suffix);
        $attacker = $this->createUser('work_sec_attacker_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $courseRepo->update($course);

        $em = $this->getEntityManager();

        $assignment = (new CStudentPublication())
            ->setTitle('Victim Assignment '.$suffix)
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($teacher)
            ->setActive(1)
            ->addCourseLink($course)
        ;
        $em->persist($assignment);

        $submission = (new CStudentPublication())
            ->setTitle('Victim Submission '.$suffix)
            ->setDescription('victim body')
            ->setPublicationParent($assignment)
            ->setParent($assignment)
            ->setFiletype('file')
            ->setWeight(100)
            ->setCreator($student)
            ->setUser($student)
            ->setActive(1)
            ->setAccepted(true)
            ->setSentDate(new DateTime())
            ->setQualification(0.0)
            ->setQualificatorId(0)
            ->setDateOfQualification(new DateTime())
            ->addCourseLink($course)
        ;
        $em->persist($submission);
        $em->flush();

        return [
            'course' => $course,
            'teacher' => $teacher,
            'student' => $student,
            'attacker' => $attacker,
            'assignment' => $assignment,
            'submission' => $submission,
        ];
    }

    /**
     * Reloads the submission from the database to assert untouched grading
     * fields without hitting stale identity-map entities.
     */
    private function reloadSubmission(int $submissionIid): ?CStudentPublication
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->getRepository(CStudentPublication::class)->find($submissionIid);
    }

    // -------------------------------------------------------------------------
    // Attacker — qualification-only payload
    // -------------------------------------------------------------------------

    public function testQualifyAsForeignAttackerIsForbidden(): void
    {
        $ctx = $this->bootstrapSubmissionScenario('qualify_foreign');

        $token = $this->getUserTokenFromUser($ctx['attacker']);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_student_publication_comments/upload',
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => [
                        'submissionId' => (string) $ctx['submission']->getIid(),
                        'qualification' => '99',
                    ],
                ],
            ]
        );

        // Object-level authorization rejects the request before any persistence.
        $this->assertResponseStatusCodeSame(403);

        $fresh = $this->reloadSubmission($ctx['submission']->getIid());
        $this->assertNotNull($fresh);
        // Qualification fields must remain untouched.
        $this->assertSame(0.0, $fresh->getQualification());
        $this->assertSame(0, $fresh->getQualificatorId());
    }

    // -------------------------------------------------------------------------
    // Attacker — comment payload (cross-course write)
    // -------------------------------------------------------------------------

    public function testCommentAsForeignAttackerIsForbidden(): void
    {
        $ctx = $this->bootstrapSubmissionScenario('comment_foreign');

        $token = $this->getUserTokenFromUser($ctx['attacker']);

        $parentNodeId = (int) $ctx['submission']->getResourceNode()?->getId();
        // Sanity check: the test scenario must have produced a real resource node.
        $this->assertGreaterThan(0, $parentNodeId);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_student_publication_comments/upload',
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => [
                        'submissionId' => (string) $ctx['submission']->getIid(),
                        'comment' => 'attacker cross-course comment',
                        'parentResourceNodeId' => (string) $parentNodeId,
                        'filetype' => 'folder',
                    ],
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(403);

        $em = $this->getEntityManager();
        $em->clear();
        $count = $em->getRepository(CStudentPublicationComment::class)
            ->count(['publication' => $ctx['submission']->getIid()])
        ;
        // No comment may have been created on the victim submission.
        $this->assertSame(0, $count);
    }

    // -------------------------------------------------------------------------
    // Student owner — must be allowed to self-comment but never to self-grade
    // -------------------------------------------------------------------------

    public function testStudentCannotGradeOwnSubmission(): void
    {
        $ctx = $this->bootstrapSubmissionScenario('self_grade');

        $token = $this->getUserTokenFromUser($ctx['student']);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_student_publication_comments/upload?cid='.$ctx['course']->getId(),
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => [
                        'submissionId' => (string) $ctx['submission']->getIid(),
                        'qualification' => '100',
                    ],
                ],
            ]
        );

        // The student owner passes the VIEW check (they own the resource), but
        // grading is reserved to teachers/coaches, so the second guard rejects.
        $this->assertResponseStatusCodeSame(403);

        $fresh = $this->reloadSubmission($ctx['submission']->getIid());
        $this->assertNotNull($fresh);
        $this->assertSame(0.0, $fresh->getQualification());
        $this->assertSame(0, $fresh->getQualificatorId());
    }

    // -------------------------------------------------------------------------
    // Course teacher — happy path: comment + qualification accepted
    // -------------------------------------------------------------------------

    public function testEnrolledTeacherCanCommentAndGrade(): void
    {
        $ctx = $this->bootstrapSubmissionScenario('teacher_ok');

        $token = $this->getUserTokenFromUser($ctx['teacher']);

        $parentNodeId = (int) $ctx['submission']->getResourceNode()?->getId();
        $this->assertGreaterThan(0, $parentNodeId);

        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_student_publication_comments/upload?cid='.$ctx['course']->getId(),
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => [
                        'submissionId' => (string) $ctx['submission']->getIid(),
                        'comment' => 'good work',
                        'qualification' => '85',
                        'parentResourceNodeId' => (string) $parentNodeId,
                        'filetype' => 'folder',
                    ],
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(201);

        $fresh = $this->reloadSubmission($ctx['submission']->getIid());
        $this->assertNotNull($fresh);
        $this->assertSame(85.0, $fresh->getQualification());
        $this->assertSame($ctx['teacher']->getId(), $fresh->getQualificatorId());
    }

    // -------------------------------------------------------------------------
    // Admin — bypass through ROLE_ADMIN must still work
    // -------------------------------------------------------------------------

    public function testAdminCanGradeAnySubmission(): void
    {
        $ctx = $this->bootstrapSubmissionScenario('admin_ok');

        $parentNodeId = (int) $ctx['submission']->getResourceNode()?->getId();
        $this->assertGreaterThan(0, $parentNodeId);

        // Default token returned by getUserToken() is admin/admin.
        $this->createClientWithCredentials()->request(
            'POST',
            '/api/c_student_publication_comments/upload',
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => [
                        'submissionId' => (string) $ctx['submission']->getIid(),
                        'comment' => 'admin feedback',
                        'qualification' => '50',
                        'parentResourceNodeId' => (string) $parentNodeId,
                        'filetype' => 'folder',
                    ],
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(201);

        $fresh = $this->reloadSubmission($ctx['submission']->getIid());
        $this->assertNotNull($fresh);
        $this->assertSame(50.0, $fresh->getQualification());
    }
}
