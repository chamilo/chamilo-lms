<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Service\Assignment;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Service\Assignment\AssignmentFeedbackNotifier;
use Chamilo\CoreBundle\Service\Assignment\McpCourseAssignmentCreator;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CStudentPublicationFollowRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * #1407: a learner's comment on a submission must e-mail only the teachers who follow
 * the assignment (not every teacher of the course), the course setting
 * email_to_teachers_on_new_work_feedback stays a master switch, and whoever creates an
 * assignment follows it from the start so they keep being told.
 */
final class AssignmentFeedbackNotifierTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * @return array{course: Course, follower: User, otherTeacher: User, student: User, assignment: CStudentPublication, submission: CStudentPublication}
     */
    private function bootstrap(string $suffix, ?string $settingValue = '1'): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Follow Course '.$suffix);
        $course->setVisibility(Course::REGISTERED);
        $follower = $this->createUser('follow_teacher_'.$suffix);
        $otherTeacher = $this->createUser('follow_other_teacher_'.$suffix);
        $student = $this->createUser('follow_student_'.$suffix);
        $course->addUserAsTeacher($follower);
        $course->addUserAsTeacher($otherTeacher);
        $course->addUserAsStudent($student);
        $courseRepo->update($course);

        $em = $this->getEntityManager();

        if (null !== $settingValue) {
            $em->persist(
                (new CCourseSetting())
                    ->setCId((int) $course->getId())
                    ->setVariable(AssignmentFeedbackNotifier::COURSE_SETTING)
                    ->setTitle(AssignmentFeedbackNotifier::COURSE_SETTING)
                    ->setCategory('student_publication')
                    ->setValue($settingValue)
            );
        }

        $assignment = (new CStudentPublication())
            ->setTitle('Follow Assignment '.$suffix)
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($follower)
            ->setUser($follower)
            ->setActive(1)
            ->addCourseLink($course)
        ;
        $em->persist($assignment);

        $submission = (new CStudentPublication())
            ->setTitle('Follow Submission '.$suffix)
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

        $this->followRepository()->follow($assignment, $follower);

        return [
            'course' => $course,
            'follower' => $follower,
            'otherTeacher' => $otherTeacher,
            'student' => $student,
            'assignment' => $assignment,
            'submission' => $submission,
        ];
    }

    private function notifier(): AssignmentFeedbackNotifier
    {
        return self::getContainer()->get(AssignmentFeedbackNotifier::class);
    }

    private function followRepository(): CStudentPublicationFollowRepository
    {
        return self::getContainer()->get(CStudentPublicationFollowRepository::class);
    }

    public function testOnlyFollowersAreNotifiedNotEveryCourseTeacher(): void
    {
        $ctx = $this->bootstrap('followers_only');

        $recipients = $this->notifier()->getRecipientIds($ctx['assignment'], $ctx['course'], null, $ctx['student']);

        // The issue: the other teacher of the course used to be e-mailed too.
        $this->assertSame([(int) $ctx['follower']->getId()], $recipients);
    }

    public function testMasterSwitchOffSilencesEvenFollowers(): void
    {
        $ctx = $this->bootstrap('switch_off', '2');

        $this->assertSame([], $this->notifier()->getRecipientIds($ctx['assignment'], $ctx['course'], null, $ctx['student']));
    }

    public function testCourseWithoutTheSettingSendsNothingAsTheLegacyPageDid(): void
    {
        $ctx = $this->bootstrap('switch_absent', null);

        $this->assertSame([], $this->notifier()->getRecipientIds($ctx['assignment'], $ctx['course'], null, $ctx['student']));
    }

    public function testFollowerWhoNoLongerTeachesTheCourseIsSkipped(): void
    {
        $ctx = $this->bootstrap('left_course');
        $outsider = $this->createUser('follow_outsider_left_course');
        $this->followRepository()->follow($ctx['assignment'], $outsider);

        $recipients = $this->notifier()->getRecipientIds($ctx['assignment'], $ctx['course'], null, $ctx['student']);

        // A follow row alone must not leak learners' comments to someone outside the course.
        $this->assertSame([(int) $ctx['follower']->getId()], $recipients);
    }

    public function testFollowingTwiceKeepsOneRowAndUnfollowStopsTheMails(): void
    {
        $ctx = $this->bootstrap('unfollow');
        $this->followRepository()->follow($ctx['assignment'], $ctx['follower']);

        $this->assertCount(1, $this->followRepository()->findFollowers($ctx['assignment']));

        // The creator may opt out too.
        $this->followRepository()->unfollow($ctx['assignment'], $ctx['follower']);

        $this->assertSame([], $this->notifier()->getRecipientIds($ctx['assignment'], $ctx['course'], null, $ctx['student']));
    }

    public function testLearnerFeedbackSendsTheMessageToFollowersOnly(): void
    {
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        Container::setSession(new Session(new MockArraySessionStorage()));

        $ctx = $this->bootstrap('send');

        $sent = $this->notifier()->notifyLearnerFeedback(
            $ctx['submission'],
            $ctx['course'],
            null,
            null,
            $ctx['student'],
            'Please check my <b>revised</b> work'
        );

        $this->assertSame([(int) $ctx['follower']->getId()], $sent);
        $this->assertSame(1, $this->countMessagesReceivedBy($ctx['follower']));
        $this->assertSame(0, $this->countMessagesReceivedBy($ctx['otherTeacher']));
    }

    public function testMcpCreatedAssignmentIsFollowedByItsCreator(): void
    {
        $container = self::getContainer();
        Container::setContainer($container);
        Container::setLegacyServices($container);
        Container::setSession(new Session(new MockArraySessionStorage()));

        $course = $this->createCourse('Follow Course mcp');
        $teacher = $this->createUser('follow_teacher_mcp');
        $container->get(TokenStorageInterface::class)->setToken(
            new UsernamePasswordToken($teacher, 'api', $teacher->getRoles())
        );

        $created = $container->get(McpCourseAssignmentCreator::class)->create(
            $course,
            $teacher,
            'Follow MCP assignment',
            'Created through MCP.',
            10.0,
            true,
            0,
        );

        $assignment = $this->getEntityManager()->getRepository(CStudentPublication::class)->find($created['assignment_id']);
        $this->assertInstanceOf(CStudentPublication::class, $assignment);
        $this->assertTrue($this->followRepository()->isFollowing($assignment, $teacher));
    }

    private function countMessagesReceivedBy(User $user): int
    {
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
