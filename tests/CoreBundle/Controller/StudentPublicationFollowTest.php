<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
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
    private function callFollow(User $user, string $method, int $assignmentId, int $courseId, int $sessionId = 0): array
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            $method,
            '/assignments/'.$assignmentId.'/follow?cid='.$courseId.($sessionId > 0 ? '&sid='.$sessionId : ''),
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

    /**
     * A session holding the course, with one course coach.
     */
    private function createSessionWithCoach(Course $course, string $suffix): array
    {
        /** @var SessionRepository $sessionRepo */
        $sessionRepo = self::getContainer()->get(SessionRepository::class);

        $session = $this->createSession('Follow Api Session '.$suffix);
        $session->addCourse($course);
        $sessionRepo->update($session);

        // Only a teacher account can be made a course coach.
        $coach = $this->createUser('follow_api_coach_'.$suffix, '', '', 'ROLE_TEACHER');
        $sessionRepo->addUserInCourse(Session::COURSE_COACH, $coach, $course, $session);

        return [$session, $coach];
    }

    public function testCoachOfTheSessionCanFollowAndUnfollow(): void
    {
        $ctx = $this->bootstrap('coach');
        [$session, $coach] = $this->createSessionWithCoach($ctx['course'], 'coach');
        $id = (int) $ctx['assignment']->getIid();
        $cid = (int) $ctx['course']->getId();
        $sid = (int) $session->getId();

        // The base-course assignment is shown in the session the coach coaches.
        $this->assertSame(['following' => false], $this->callFollow($coach, 'GET', $id, $cid, $sid));
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(['following' => true], $this->callFollow($coach, 'POST', $id, $cid, $sid));
        $this->assertResponseStatusCodeSame(200);
        $this->assertTrue($this->isFollowing($ctx['assignment'], $coach));

        $this->assertSame(['following' => false], $this->callFollow($coach, 'DELETE', $id, $cid, $sid));
        $this->assertResponseStatusCodeSame(200);
        $this->assertFalse($this->isFollowing($ctx['assignment'], $coach));
    }

    public function testCoachOfAnotherSessionCannotFollow(): void
    {
        $ctx = $this->bootstrap('other_coach');
        [$session] = $this->createSessionWithCoach($ctx['course'], 'other_coach_a');
        [$otherSession, $otherCoach] = $this->createSessionWithCoach($ctx['course'], 'other_coach_b');
        $id = (int) $ctx['assignment']->getIid();
        $cid = (int) $ctx['course']->getId();

        // Coaching session B does not open session A's context...
        $this->callFollow($otherCoach, 'POST', $id, $cid, (int) $session->getId());
        $this->assertResponseStatusCodeSame(403);

        // ...nor the course outside any session.
        $this->callFollow($otherCoach, 'POST', $id, $cid);
        $this->assertResponseStatusCodeSame(403);

        // An assignment published in session A only is not available in session B.
        $em = $this->getEntityManager();
        $em->clear();
        $course = $em->find(Course::class, $cid);
        $creator = $em->find(User::class, $ctx['creator']->getId());
        $sessionAssignment = (new CStudentPublication())
            ->setTitle('Follow Api Session A Assignment')
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($creator)
            ->setUser($creator)
            ->setActive(1)
            ->addCourseLink($course, $em->find(Session::class, $session->getId()))
        ;
        $em->persist($sessionAssignment);
        $em->flush();

        $this->callFollow($otherCoach, 'POST', (int) $sessionAssignment->getIid(), $cid, (int) $otherSession->getId());
        $this->assertResponseStatusCodeSame(403);

        $this->assertFalse($this->isFollowing($ctx['assignment'], $otherCoach));
        $this->assertFalse($this->isFollowing($sessionAssignment, $otherCoach));
    }

    public function testCoachFollowerIsNotifiedOfALearnerCommentInTheirSession(): void
    {
        $ctx = $this->bootstrap('coach_comment');
        [$session, $coach] = $this->createSessionWithCoach($ctx['course'], 'coach_comment_a');
        [, $otherCoach] = $this->createSessionWithCoach($ctx['course'], 'coach_comment_b');

        /** @var SessionRepository $sessionRepo */
        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        $learner = $this->createUser('follow_api_session_learner');
        $sessionRepo->addUserInCourse(Session::STUDENT, $learner, $ctx['course'], $session);

        $em = $this->getEntityManager();
        $submission = (new CStudentPublication())
            ->setTitle('Follow Api Session Submission')
            ->setDescription('session learner work')
            ->setPublicationParent($ctx['assignment'])
            ->setParent($ctx['assignment'])
            ->setFiletype('file')
            ->setWeight(100)
            ->setCreator($learner)
            ->setUser($learner)
            ->setActive(1)
            ->setAccepted(true)
            ->setSentDate(new DateTime())
            ->setQualification(0.0)
            ->setQualificatorId(0)
            ->addCourseLink($ctx['course'], $em->find(Session::class, $session->getId()))
        ;
        $em->persist($submission);
        $em->flush();

        // A coach of another session who follows the same assignment.
        $this->followRepository()->follow($ctx['assignment'], $otherCoach);

        $cid = (int) $ctx['course']->getId();
        $sid = (int) $session->getId();
        $this->callFollow($coach, 'POST', (int) $ctx['assignment']->getIid(), $cid, $sid);
        $this->assertResponseStatusCodeSame(200);

        $this->createClientWithCredentials($this->getUserTokenFromUser($learner))->request(
            'POST',
            '/api/c_student_publication_comments/upload?cid='.$cid.'&sid='.$sid,
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => [
                        'submissionId' => (string) $submission->getIid(),
                        'comment' => 'Session learner comment',
                        'parentResourceNodeId' => (string) $submission->getResourceNode()->getId(),
                        'filetype' => 'file',
                    ],
                ],
            ]
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame(1, $this->countMessagesReceivedBy($coach));
        // Comments made in session A are none of session B's coach's business.
        $this->assertSame(0, $this->countMessagesReceivedBy($otherCoach));
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
