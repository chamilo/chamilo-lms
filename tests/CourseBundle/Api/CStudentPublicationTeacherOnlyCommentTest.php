<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Message;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session as HttpSession;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Teacher-only (private) assignment comments, issue #1636.
 *
 * The teachers of a submission (course teachers and tutors, session general and course
 * coaches) write and read them; the learner who sent the submission never sees them, by
 * listing, by embedded serialization or by fetching the comment's resource node.
 */
final class CStudentPublicationTeacherOnlyCommentTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private const PRIVATE_TEXT = 'Private note: suspected copy of last year';
    private const PUBLIC_TEXT = 'Public feedback: good structure';

    public function testTeacherOnlyCommentIsHiddenFromTheLearnerAndShownToTeachersAndTutors(): void
    {
        $ctx = $this->courseScenario('course_read');
        $query = '?publication.iid='.$ctx['submission']->getIid().'&cid='.$ctx['course']->getId();

        $private = $this->postComment($ctx['teacher'], $ctx['submission'], self::PRIVATE_TEXT, $ctx['cidReq'], true, true);
        $this->assertResponseStatusCodeSame(201);
        $this->assertTrue($private['teacherOnly']);
        $this->postComment($ctx['teacher'], $ctx['submission'], self::PUBLIC_TEXT, $ctx['cidReq'], false);
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame([self::PUBLIC_TEXT], $this->listedComments($ctx['student'], $query));
        // Without a submission filter nobody gets private comments, the learner included.
        $this->assertNotContains(
            self::PRIVATE_TEXT,
            $this->listedComments($ctx['student'], '?cid='.$ctx['course']->getId())
        );

        $this->assertEqualsCanonicalizing(
            [self::PRIVATE_TEXT, self::PUBLIC_TEXT],
            $this->listedComments($ctx['teacher'], $query)
        );
        $this->assertEqualsCanonicalizing(
            [self::PRIVATE_TEXT, self::PUBLIC_TEXT],
            $this->listedComments($ctx['tutor'], $query)
        );
    }

    public function testTeacherOnlyCommentSendsNoMailEvenWhenAsked(): void
    {
        $ctx = $this->courseScenario('course_mail');

        $before = $this->countFeedbackMessages($ctx['submission']);
        $this->postComment($ctx['teacher'], $ctx['submission'], self::PRIVATE_TEXT, $ctx['cidReq'], true, true);
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame($before, $this->countFeedbackMessages($ctx['submission']));

        // Control: the same request without the flag does mail the learner.
        $this->postComment($ctx['teacher'], $ctx['submission'], self::PUBLIC_TEXT, $ctx['cidReq'], false, true);
        $this->assertResponseStatusCodeSame(201);
        $this->assertSame($before + 1, $this->countFeedbackMessages($ctx['submission']));
    }

    public function testLearnerCannotWriteTeacherOnlyComment(): void
    {
        $ctx = $this->courseScenario('course_write');

        $this->postComment($ctx['student'], $ctx['submission'], 'trying to hide this', $ctx['cidReq'], true);
        $this->assertResponseStatusCodeSame(403);

        $em = $this->getEntityManager();
        $em->clear();
        $this->assertSame(0, $em->getRepository(CStudentPublicationComment::class)->count([
            'publication' => $ctx['submission']->getIid(),
        ]));
    }

    public function testTutorCanWriteTeacherOnlyComment(): void
    {
        $ctx = $this->courseScenario('course_tutor_write');

        $created = $this->postComment($ctx['tutor'], $ctx['submission'], self::PRIVATE_TEXT, $ctx['cidReq'], true);
        $this->assertResponseStatusCodeSame(201);
        $this->assertTrue($created['teacherOnly']);
        $this->assertSame(
            [],
            $this->listedComments($ctx['student'], '?publication.iid='.$ctx['submission']->getIid().'&cid='.$ctx['course']->getId())
        );
    }

    public function testLearnerCannotFetchTheResourceNodeOfATeacherOnlyComment(): void
    {
        $ctx = $this->courseScenario('course_node');
        $private = $this->postComment($ctx['teacher'], $ctx['submission'], self::PRIVATE_TEXT, $ctx['cidReq'], true);
        $this->assertResponseStatusCodeSame(201);
        $public = $this->postComment($ctx['teacher'], $ctx['submission'], self::PUBLIC_TEXT, $ctx['cidReq'], false);

        $privateNodeId = $this->commentNodeId((int) $private['iid']);
        $publicNodeId = $this->commentNodeId((int) $public['iid']);

        $em = $this->getEntityManager();
        $em->clear();
        $privateNode = $em->find(ResourceNode::class, $privateNodeId);
        $publicNode = $em->find(ResourceNode::class, $publicNodeId);
        $this->assertInstanceOf(ResourceNode::class, $privateNode);
        $this->assertInstanceOf(ResourceNode::class, $publicNode);

        // The voter guards the file download and every other node route.
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->voteOnNode($ctx['student'], $privateNode));
        // Control: the public comment of the same submission stays readable to its learner.
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voteOnNode($ctx['student'], $publicNode));
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->voteOnNode($ctx['teacher'], $privateNode));
        // For the other teachers the flag takes nothing away: they get what a public comment gives them.
        $this->assertSame(
            $this->voteOnNode($ctx['tutor'], $publicNode),
            $this->voteOnNode($ctx['tutor'], $privateNode)
        );

        // The private node title carries none of the comment text.
        $this->assertStringNotContainsStringIgnoringCase('suspected', (string) $privateNode->getTitle());
        $this->assertStringContainsStringIgnoringCase('public', (string) $publicNode->getTitle());
    }

    public function testCourseLinkedTeacherOnlyCommentIsNotListedAmongTheCourseNodesAndFiles(): void
    {
        $ctx = $this->courseScenario('course_listing');

        // Comments written by the legacy page carry a course link, which opens their node and
        // file to the generic listings of every course member.
        $private = $this->createLinkedComment($ctx, 'private linked note', true);
        $public = $this->createLinkedComment($ctx, 'public linked note', false);

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']));

        $nodes = $client->request('GET', '/api/resource_nodes?itemsPerPage=1000')->toArray();
        $nodeIds = array_column($nodes['hydra:member'], 'id');
        $this->assertContains($public, $nodeIds);
        $this->assertNotContains($private, $nodeIds);

        $teacherNodes = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))
            ->request('GET', '/api/resource_nodes?itemsPerPage=1000')
            ->toArray()
        ;
        // Its creator still finds it.
        $this->assertContains($private, array_column($teacherNodes['hydra:member'], 'id'));
    }

    public function testLearnerAssignmentListCountsPublicCommentsOnly(): void
    {
        $ctx = $this->courseScenario('course_count');
        $assignment = $ctx['submission']->getPublicationParent();
        $this->assertInstanceOf(CStudentPublication::class, $assignment);

        $this->createLinkedComment(['submission' => $assignment] + $ctx, 'private count', true);
        $this->createLinkedComment(['submission' => $assignment] + $ctx, 'public count', false);

        // The repository reads the legacy session for the current user id.
        $request = new Request();
        $request->setSession(new HttpSession(new MockArraySessionStorage()));
        self::getContainer()->get('request_stack')->push($request);

        /** @var CStudentPublicationRepository $repo */
        $repo = self::getContainer()->get(CStudentPublicationRepository::class);
        $rows = $repo->findVisibleAssignmentsForStudent($ctx['course']);

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row[0]->getIid()] = (int) $row['commentsCount'];
        }
        $this->assertSame(1, $counts[$assignment->getIid()] ?? null);
    }

    public function testSubmissionListsCountTeacherOnlyCommentsForTeachersOnly(): void
    {
        $ctx = $this->courseScenario('course_lists');
        $this->postComment($ctx['teacher'], $ctx['submission'], self::PRIVATE_TEXT, $ctx['cidReq'], true);
        $this->postComment($ctx['teacher'], $ctx['submission'], self::PUBLIC_TEXT, $ctx['cidReq'], false);
        $assignmentId = (int) $ctx['submission']->getPublicationParent()?->getIid();

        $learnerList = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']))
            ->request('GET', '/assignments/'.$assignmentId.'/submissions'.$ctx['cidReq'])
            ->toArray()
        ;
        $this->assertCount(1, $learnerList['hydra:member']);
        $this->assertCount(1, $learnerList['hydra:member'][0]['comments']);

        $teacherList = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))
            ->request('GET', '/assignments/'.$assignmentId.'/submissions/teacher'.$ctx['cidReq'])
            ->toArray()
        ;
        $this->assertCount(1, $teacherList['hydra:member']);
        $this->assertCount(2, $teacherList['hydra:member'][0]['comments']);
    }

    public function testSessionCoachesReadAndWriteTeacherOnlyComments(): void
    {
        $ctx = $this->sessionScenario('session');
        $query = '?publication.iid='.$ctx['submission']->getIid().$ctx['cidReqTail'];

        $this->postComment($ctx['courseCoach'], $ctx['submission'], self::PRIVATE_TEXT, $ctx['cidReq'], true);
        $this->assertResponseStatusCodeSame(201);
        $this->postComment($ctx['courseCoach'], $ctx['submission'], self::PUBLIC_TEXT, $ctx['cidReq'], false);
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame([self::PUBLIC_TEXT], $this->listedComments($ctx['student'], $query));
        $this->assertEqualsCanonicalizing(
            [self::PRIVATE_TEXT, self::PUBLIC_TEXT],
            $this->listedComments($ctx['courseCoach'], $query)
        );
        $this->assertEqualsCanonicalizing(
            [self::PRIVATE_TEXT, self::PUBLIC_TEXT],
            $this->listedComments($ctx['generalCoach'], $query)
        );
    }

    public function testEmbeddedCommentsOfASubmissionLeaveTeacherOnlyOnesOut(): void
    {
        $ctx = $this->courseScenario('course_embedded');
        $this->postComment($ctx['teacher'], $ctx['submission'], self::PRIVATE_TEXT, $ctx['cidReq'], true);
        $this->postComment($ctx['teacher'], $ctx['submission'], self::PUBLIC_TEXT, $ctx['cidReq'], false);

        $em = $this->getEntityManager();
        $em->clear();
        $submission = $em->getRepository(CStudentPublication::class)->find($ctx['submission']->getIid());
        $this->assertInstanceOf(CStudentPublication::class, $submission);
        $this->assertCount(2, $submission->getComments());

        /** @var SerializerInterface $serializer */
        $serializer = self::getContainer()->get(SerializerInterface::class);
        $groups = ['groups' => ['student_publication:read']];

        $learnerView = json_decode($serializer->serialize($submission, 'json', $groups), true);
        $this->assertCount(1, $learnerView['comments']);

        $teacherView = json_decode($serializer->serialize($submission->exposeTeacherOnlyComments(), 'json', $groups), true);
        $this->assertCount(2, $teacherView['comments']);
    }

    /**
     * @return array{course: Course, teacher: User, tutor: User, student: User, submission: CStudentPublication, cidReq: string}
     */
    private function courseScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $course = $this->createCourse('Private comments '.$suffix);
        $course->setVisibility(Course::REGISTERED);

        $teacher = $this->createUser('pc_teacher_'.$suffix, '', '', 'ROLE_TEACHER');
        $tutor = $this->createUser('pc_tutor_'.$suffix);
        $student = $this->createUser('pc_student_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($student);
        // A tutor keeps the learner status: only the tutor flag makes them one of the teachers.
        $course->addSubscription(
            (new CourseRelUser())
                ->setUser($tutor)
                ->setRelationType(0)
                ->setStatus(CourseRelUser::STUDENT)
                ->setTutor(true)
        );
        $courseRepo->update($course);

        $submission = $this->createSubmission($course, null, $teacher, $student, $suffix);

        return [
            'course' => $course,
            'teacher' => $teacher,
            'tutor' => $tutor,
            'student' => $student,
            'submission' => $submission,
            'cidReq' => '?cid='.$course->getId(),
        ];
    }

    /**
     * @return array{courseCoach: User, generalCoach: User, student: User, submission: CStudentPublication, cidReq: string, cidReqTail: string}
     */
    private function sessionScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        /** @var SessionRepository $sessionRepo */
        $sessionRepo = self::getContainer()->get(SessionRepository::class);

        $course = $this->createCourse('Private comments '.$suffix);
        $course->setVisibility(Course::REGISTERED);
        $courseRepo->update($course);

        $courseCoach = $this->createUser('pc_course_coach_'.$suffix, '', '', 'ROLE_TEACHER');
        $generalCoach = $this->createUser('pc_general_coach_'.$suffix, '', '', 'ROLE_TEACHER');
        $student = $this->createUser('pc_session_student_'.$suffix);

        $session = $this->createSession('Private comments session '.$suffix);
        $session->addCourse($course);
        $session->addGeneralCoach($generalCoach);
        $sessionRepo->update($session);
        $sessionRepo->addUserInCourse(Session::STUDENT, $student, $course, $session);
        $sessionRepo->addUserInCourse(Session::COURSE_COACH, $courseCoach, $course, $session);
        $sessionRepo->update($session);

        $submission = $this->createSubmission($course, $session, $courseCoach, $student, $suffix);
        $tail = '&cid='.$course->getId().'&sid='.$session->getId();

        return [
            'courseCoach' => $courseCoach,
            'generalCoach' => $generalCoach,
            'student' => $student,
            'submission' => $submission,
            'cidReq' => '?'.ltrim($tail, '&'),
            'cidReqTail' => $tail,
        ];
    }

    private function createSubmission(Course $course, ?Session $session, User $teacher, User $student, string $suffix): CStudentPublication
    {
        $em = $this->getEntityManager();

        $assignment = (new CStudentPublication())
            ->setTitle('Assignment '.$suffix)
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($teacher)
            ->setActive(1)
            ->addCourseLink($course, $session)
        ;
        $em->persist($assignment);

        $submission = (new CStudentPublication())
            ->setTitle('Submission '.$suffix)
            ->setDescription('body')
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
            ->addCourseLink($course, $session)
        ;
        $em->persist($submission);
        $em->flush();

        return $submission;
    }

    /**
     * @return array<string, mixed>
     */
    private function postComment(
        User $author,
        CStudentPublication $submission,
        string $text,
        string $cidReq,
        bool $teacherOnly,
        bool $sendMail = false
    ): array {
        $parameters = [
            'submissionId' => (string) $submission->getIid(),
            'comment' => $text,
            'parentResourceNodeId' => (string) $submission->getResourceNode()?->getId(),
            'filetype' => 'file',
            'sendMail' => $sendMail ? '1' : '0',
        ];
        if ($teacherOnly) {
            $parameters['teacherOnly'] = '1';
        }

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($author))->request(
            'POST',
            '/api/c_student_publication_comments/upload'.$cidReq,
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => ['parameters' => $parameters],
            ]
        );

        return $response->toArray(false);
    }

    /**
     * @return list<string>
     */
    private function listedComments(User $reader, string $query): array
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($reader))
            ->request('GET', '/api/c_student_publication_comments'.$query)
        ;
        $this->assertResponseIsSuccessful();

        return array_values(array_map(
            static fn (array $item): string => (string) $item['comment'],
            $response->toArray()['hydra:member']
        ));
    }

    private function commentNodeId(int $commentIid): int
    {
        $em = $this->getEntityManager();
        $em->clear();
        $comment = $em->getRepository(CStudentPublicationComment::class)->find($commentIid);
        $this->assertInstanceOf(CStudentPublicationComment::class, $comment);

        return (int) $comment->getResourceNode()?->getId();
    }

    /**
     * @param array{course: Course, teacher: User, submission: CStudentPublication} $ctx
     */
    private function createLinkedComment(array $ctx, string $text, bool $teacherOnly): int
    {
        $em = $this->getEntityManager();
        $comment = (new CStudentPublicationComment())
            ->setComment($text)
            ->setUser($ctx['teacher'])
            ->setPublication($ctx['submission'])
            ->setTeacherOnly($teacherOnly)
            ->setParent($ctx['submission'])
            ->setCreator($ctx['teacher'])
            ->addCourseLink($ctx['course'])
        ;
        $em->persist($comment);
        $em->flush();

        return (int) $comment->getResourceNode()?->getId();
    }

    private function voteOnNode(User $user, ResourceNode $node): int
    {
        $user = $this->getEntityManager()->find(User::class, $user->getId());
        $this->assertInstanceOf(User::class, $user);
        $token = new UsernamePasswordToken($user, 'api', $user->getRoles());
        self::getContainer()->get('security.token_storage')->setToken($token);

        /** @var ResourceNodeVoter $voter */
        $voter = self::getContainer()->get(ResourceNodeVoter::class);

        return $voter->vote($token, $node, [ResourceNodeVoter::VIEW]);
    }

    private function countFeedbackMessages(CStudentPublication $submission): int
    {
        return (int) $this->getEntityManager()
            ->getRepository(Message::class)
            ->count(['title' => \sprintf('New feedback for your submission "%s"', $submission->getTitle())])
        ;
    }
}
