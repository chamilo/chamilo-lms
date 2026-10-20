<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Service\Assignment\AssignmentFeedbackNotifier;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CStudentPublicationFollowRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

/**
 * #1407: following an assignment decides who is e-mailed about learners' comments, so
 * only teachers of that assignment's course may change it (a learner or another
 * course's teacher must not subscribe themselves to someone else's feedback), and the
 * new Vue/API screens must create follows and send notifications like the legacy page.
 */
final class StudentPublicationFollowTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * @return array{course: Course, creator: User, teacher: User, student: User, assignment: CStudentPublication, submission: CStudentPublication}
     */
    private function bootstrap(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Follow Api Course '.$suffix);
        $course->setVisibility(Course::REGISTERED);
        $creator = $this->createUser('follow_api_creator_'.$suffix);
        $teacher = $this->createUser('follow_api_teacher_'.$suffix);
        $student = $this->createUser('follow_api_student_'.$suffix);
        $course->addUserAsTeacher($creator);
        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        $courseRepo->update($course);

        $em = $this->getEntityManager();
        $em->persist(
            (new CCourseSetting())
                ->setCId((int) $course->getId())
                ->setVariable(AssignmentFeedbackNotifier::COURSE_SETTING)
                ->setTitle(AssignmentFeedbackNotifier::COURSE_SETTING)
                ->setCategory('student_publication')
                ->setValue('1')
        );

        $assignment = (new CStudentPublication())
            ->setTitle('Follow Api Assignment '.$suffix)
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($creator)
            ->setUser($creator)
            ->setActive(1)
            ->addCourseLink($course)
        ;
        $em->persist($assignment);

        $submission = (new CStudentPublication())
            ->setTitle('Follow Api Submission '.$suffix)
            ->setDescription('learner work')
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
            ->addCourseLink($course)
        ;
        $em->persist($submission);
        $em->flush();

        return [
            'course' => $course,
            'creator' => $creator,
            'teacher' => $teacher,
            'student' => $student,
            'assignment' => $assignment,
            'submission' => $submission,
        ];
    }

    private function followRepository(): CStudentPublicationFollowRepository
    {
        return self::getContainer()->get(CStudentPublicationFollowRepository::class);
    }

    private function isFollowing(CStudentPublication $assignment, User $user): bool
    {
        $this->getEntityManager()->clear();

        return null !== $this->followRepository()->findOneBy([
            'publication' => (int) $assignment->getIid(),
            'user' => (int) $user->getId(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function callFollow(User $user, string $method, int $assignmentId, int $courseId): array
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            $method,
            '/assignments/'.$assignmentId.'/follow?cid='.$courseId,
        );

        return $response->toArray(false);
    }

    public function testCourseTeacherCanFollowAndUnfollow(): void
    {
        $ctx = $this->bootstrap('teacher');
        $id = (int) $ctx['assignment']->getIid();
        $cid = (int) $ctx['course']->getId();

        $this->assertSame(['following' => false], $this->callFollow($ctx['teacher'], 'GET', $id, $cid));

        $this->assertSame(['following' => true], $this->callFollow($ctx['teacher'], 'POST', $id, $cid));
        $this->assertResponseIsSuccessful();
        $this->assertTrue($this->isFollowing($ctx['assignment'], $ctx['teacher']));
        $this->assertSame(['following' => true], $this->callFollow($ctx['teacher'], 'GET', $id, $cid));

        $this->assertSame(['following' => false], $this->callFollow($ctx['teacher'], 'DELETE', $id, $cid));
        $this->assertResponseIsSuccessful();
        $this->assertFalse($this->isFollowing($ctx['assignment'], $ctx['teacher']));
    }

    public function testStudentCannotFollow(): void
    {
        $ctx = $this->bootstrap('student');

        $this->callFollow($ctx['student'], 'POST', (int) $ctx['assignment']->getIid(), (int) $ctx['course']->getId());

        $this->assertResponseStatusCodeSame(403);
        $this->assertFalse($this->isFollowing($ctx['assignment'], $ctx['student']));
    }

    public function testTeacherOfAnotherCourseCannotFollow(): void
    {
        $ctx = $this->bootstrap('foreign');

        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $otherCourse = $this->createCourse('Follow Api Other Course');
        $otherTeacher = $this->createUser('follow_api_other_teacher');
        $otherCourse->addUserAsTeacher($otherTeacher);
        $courseRepo->update($otherCourse);

        $id = (int) $ctx['assignment']->getIid();

        // Neither with their own course in the context...
        $this->callFollow($otherTeacher, 'POST', $id, (int) $otherCourse->getId());
        $this->assertResponseStatusCodeSame(403);

        // ...nor by naming the victim course.
        $this->callFollow($otherTeacher, 'POST', $id, (int) $ctx['course']->getId());
        $this->assertResponseStatusCodeSame(403);

        $this->assertFalse($this->isFollowing($ctx['assignment'], $otherTeacher));
    }

    public function testASubmissionCannotBeFollowed(): void
    {
        $ctx = $this->bootstrap('submission');

        $this->callFollow($ctx['teacher'], 'POST', (int) $ctx['submission']->getIid(), (int) $ctx['course']->getId());

        $this->assertResponseStatusCodeSame(404);
    }

    public function testAssignmentCreatedThroughTheApiIsFollowedByItsCreator(): void
    {
        $ctx = $this->bootstrap('create');

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'POST',
            '/api/c_student_publications?cid='.$ctx['course']->getId(),
            [
                'json' => [
                    'title' => 'Follow Api Created Assignment',
                    'description' => 'Created from the Vue screen',
                    'parentResourceNode' => (int) $ctx['course']->getResourceNode()->getId(),
                    'resourceLinkList' => [['visibility' => 2]],
                    'qualification' => 10,
                    'allowTextAssignment' => 0,
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(201);
        $assignment = $this->getEntityManager()->getRepository(CStudentPublication::class)->find($response->toArray()['iid']);
        $this->assertInstanceOf(CStudentPublication::class, $assignment);
        $this->assertTrue($this->isFollowing($assignment, $ctx['teacher']));
        $this->assertFalse($this->isFollowing($assignment, $ctx['creator']));
    }

    public function testLearnerCommentOnTheNewScreensNotifiesFollowersOnly(): void
    {
        $ctx = $this->bootstrap('comment');
        $this->followRepository()->follow($ctx['assignment'], $ctx['creator']);

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']))->request(
            'POST',
            '/api/c_student_publication_comments/upload?cid='.$ctx['course']->getId(),
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => [
                        'submissionId' => (string) $ctx['submission']->getIid(),
                        'comment' => 'I uploaded a new version',
                        'parentResourceNodeId' => (string) $ctx['submission']->getResourceNode()->getId(),
                        'filetype' => 'file',
                    ],
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(201);
        // Before #1407 this screen notified no teacher at all.
        $this->assertSame(1, $this->countMessagesReceivedBy($ctx['creator']));
        // The other teacher of the course does not follow the assignment.
        $this->assertSame(0, $this->countMessagesReceivedBy($ctx['teacher']));
    }

    public function testTeacherCommentDoesNotNotifyFollowers(): void
    {
        $ctx = $this->bootstrap('teacher_comment');
        $this->followRepository()->follow($ctx['assignment'], $ctx['creator']);

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'POST',
            '/api/c_student_publication_comments/upload?cid='.$ctx['course']->getId(),
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => [
                        'submissionId' => (string) $ctx['submission']->getIid(),
                        'comment' => 'Teacher feedback',
                        'parentResourceNodeId' => (string) $ctx['submission']->getResourceNode()->getId(),
                        'filetype' => 'file',
                    ],
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(201);
        // Followers hear about learners' comments, not about their colleagues' feedback.
        $this->assertSame(0, $this->countMessagesReceivedBy($ctx['creator']));
    }

    private function countMessagesReceivedBy(User $user): int
    {
        $this->getEntityManager()->clear();

        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from(Message::class, 'm')
            ->innerJoin('m.receivers', 'r')
            ->where('r.receiver = :user')
            ->andWhere('m.sender <> :user')
            ->setParameter('user', (int) $user->getId())
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
