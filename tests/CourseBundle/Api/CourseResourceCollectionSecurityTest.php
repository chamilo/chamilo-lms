<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Api;

use ApiPlatform\Symfony\Bundle\Test\Client;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Illustration;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\IllustrationRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CAttendance;
use Chamilo\CourseBundle\Entity\CAttendanceCalendar;
use Chamilo\CourseBundle\Entity\CForum;
use Chamilo\CourseBundle\Entity\CForumAttachment;
use Chamilo\CourseBundle\Entity\CForumPost;
use Chamilo\CourseBundle\Entity\CForumThread;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use DateTime;

/**
 * The contextual course roles gating these collections come from the cid of the
 * request, which any member of one course can set to their own course. The rows
 * returned must still belong to that course, never to a course the caller has no
 * access to.
 */
final class CourseResourceCollectionSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testForumAttachmentsOfAnotherCourseAreNotListed(): void
    {
        $ctx = $this->bootstrapScenario('attachments');
        $postA = $this->createForumPost($ctx['courseA'], $ctx['teacherA'], 'A attachments');
        $postB = $this->createForumPost($ctx['courseB'], $ctx['teacherB'], 'B attachments');
        $attachmentA = $this->createForumAttachment($ctx['courseA'], $postA);
        $attachmentB = $this->createForumAttachment($ctx['courseB'], $postB);
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['studentA']));
        $cid = 'cid='.$ctx['courseA']->getId();

        $ids = $this->getIds($client, '/api/forum_attachments?'.$cid.'&post=/api/forum_posts/'.$postB->getIid());
        $this->assertSame([], $ids);

        $ids = $this->getIds($client, '/api/forum_attachments?'.$cid);
        $this->assertContains('/api/forum_attachments/'.$attachmentA->getIid(), $ids);
        $this->assertNotContains('/api/forum_attachments/'.$attachmentB->getIid(), $ids);
    }

    public function testAssignmentCommentsOfAnotherCourseAreNotListed(): void
    {
        $ctx = $this->bootstrapScenario('comments');
        $submissionA = $this->createSubmission($ctx['courseA'], $ctx['teacherA'], $ctx['studentA'], 'A comments');
        $submissionB = $this->createSubmission($ctx['courseB'], $ctx['teacherB'], $ctx['studentB'], 'B comments');
        $commentA = $this->createComment($submissionA, $ctx['teacherA']);
        $commentB = $this->createComment($submissionB, $ctx['teacherB']);
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['studentA']));
        $cid = 'cid='.$ctx['courseA']->getId();

        $ids = $this->getIds($client, '/api/c_student_publication_comments?'.$cid.'&publication.iid='.$submissionB->getIid());
        $this->assertSame([], $ids);

        $ids = $this->getIds($client, '/api/c_student_publication_comments?'.$cid.'&publication.iid='.$submissionA->getIid());
        $this->assertSame(['/api/c_student_publication_comments/'.$commentA->getIid()], $ids);
        $this->assertNotContains('/api/c_student_publication_comments/'.$commentB->getIid(), $ids);
    }

    public function testGroupsOfAnotherCourseAreNotListed(): void
    {
        $ctx = $this->bootstrapScenario('groups');
        $groupA = $this->createCourseGroup($ctx['courseA'], $ctx['teacherA'], 'Group A');
        $groupB = $this->createCourseGroup($ctx['courseB'], $ctx['teacherB'], 'Group B');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['studentA']));
        $cid = 'cid='.$ctx['courseA']->getId();

        $ids = $this->getIds($client, '/api/groups?'.$cid.'&resourceNode.parent='.$ctx['courseB']->getResourceNode()->getId());
        $this->assertSame([], $ids);

        $ids = $this->getIds($client, '/api/groups?'.$cid);
        $this->assertContains('/api/groups/'.$groupA->getIid(), $ids);
        $this->assertNotContains('/api/groups/'.$groupB->getIid(), $ids);
    }

    public function testLinkCategoriesOfAnotherCourseAreNotListed(): void
    {
        $ctx = $this->bootstrapScenario('link_categories');
        $categoryA = $this->createLinkCategory($ctx['courseA'], $ctx['teacherA'], 'Category A');
        $categoryB = $this->createLinkCategory($ctx['courseB'], $ctx['teacherB'], 'Category B');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['studentA']));
        $cid = 'cid='.$ctx['courseA']->getId();

        $ids = $this->getIds($client, '/api/link_categories?'.$cid.'&resourceNode.parent='.$ctx['courseB']->getResourceNode()->getId());
        $this->assertSame([], $ids);

        $ids = $this->getIds($client, '/api/link_categories?'.$cid);
        $this->assertContains('/api/link_categories/'.$categoryA->getIid(), $ids);
        $this->assertNotContains('/api/link_categories/'.$categoryB->getIid(), $ids);
    }

    public function testAttendanceCalendarsOfAnotherCourseAreNotListed(): void
    {
        $ctx = $this->bootstrapScenario('attendance');
        $calendarA = $this->createAttendanceCalendar($ctx['courseA'], $ctx['teacherA'], 'Attendance A');
        $calendarB = $this->createAttendanceCalendar($ctx['courseB'], $ctx['teacherB'], 'Attendance B');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacherA']));

        $ids = $this->getIds($client, '/api/c_attendance_calendars?cid='.$ctx['courseA']->getId());
        $this->assertContains('/api/c_attendance_calendars/'.$calendarA->getIid(), $ids);
        $this->assertNotContains('/api/c_attendance_calendars/'.$calendarB->getIid(), $ids);
    }

    public function testSessionCourseListIsReservedToAdmins(): void
    {
        $ctx = $this->bootstrapScenario('session_courses');

        /** @var SessionRepository $sessionRepo */
        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        $session = $this->createSession('Hidden session courses');
        $session->addCourse($ctx['courseB']);
        $sessionRepo->update($session);

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['studentA']))
            ->request('GET', '/api/session_rel_courses')
        ;
        $this->assertResponseStatusCodeSame(403);

        $this->createClientWithCredentials($this->getUserToken([], true))
            ->request('GET', '/api/session_rel_courses')
        ;
        $this->assertResponseIsSuccessful();
    }

    public function testIllustrationOfAnotherUserCannotBeListedOrDeleted(): void
    {
        $ctx = $this->bootstrapScenario('illustrations');

        /** @var IllustrationRepository $illustrationRepo */
        $illustrationRepo = self::getContainer()->get(IllustrationRepository::class);
        $illustrationRepo->addIllustration($ctx['studentB'], $ctx['studentB'], $this->getUploadedFile());
        $illustrationNode = $illustrationRepo->getIllustrationNodeFromParent($ctx['studentB']->getResourceNode());
        $this->assertNotNull($illustrationNode);
        $illustrationNodeId = $illustrationNode->getId();
        $illustrationId = (string) $illustrationRepo->findOneBy(['resourceNode' => $illustrationNode])->getId();

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['studentA']));

        $client->request('GET', '/api/illustrations');
        $this->assertResponseStatusCodeSame(404);

        $client->request('DELETE', '/api/illustrations/'.$illustrationId);
        $this->assertResponseStatusCodeSame(405);

        $client->request(
            'PATCH',
            '/api/illustrations/'.$illustrationId,
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'json' => ['title' => 'changed'],
            ]
        );
        $this->assertResponseStatusCodeSame(405);

        $client->request('DELETE', '/api/resource_nodes/'.$illustrationNodeId);
        $this->assertResponseStatusCodeSame(403);

        $em = $this->getEntityManager();
        $em->clear();
        $this->assertNotNull($em->getRepository(ResourceNode::class)->find($illustrationNodeId));
        $stored = $em->getRepository(Illustration::class)->find($illustrationId);
        $this->assertNotNull($stored);
        $this->assertNotSame('changed', $stored->getTitle());
    }

    /**
     * Two private courses with a teacher and a learner each.
     *
     * @return array{courseA: Course, courseB: Course, teacherA: User, studentA: User, teacherB: User, studentB: User}
     */
    private function bootstrapScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $courseA = $this->createCourse('Collection Course A '.$suffix);
        $courseA->setVisibility(Course::REGISTERED);
        $courseB = $this->createCourse('Collection Course B '.$suffix);
        $courseB->setVisibility(Course::REGISTERED);

        $teacherA = $this->createUser('coll_teacher_a_'.$suffix);
        $studentA = $this->createUser('coll_student_a_'.$suffix);
        $teacherB = $this->createUser('coll_teacher_b_'.$suffix);
        $studentB = $this->createUser('coll_student_b_'.$suffix);

        $courseA->addUserAsTeacher($teacherA);
        $courseA->addUserAsStudent($studentA);
        $courseB->addUserAsTeacher($teacherB);
        $courseB->addUserAsStudent($studentB);
        $courseRepo->update($courseA);
        $courseRepo->update($courseB);

        return [
            'courseA' => $courseA,
            'courseB' => $courseB,
            'teacherA' => $teacherA,
            'studentA' => $studentA,
            'teacherB' => $teacherB,
            'studentB' => $studentB,
        ];
    }

    private function createForumPost(Course $course, User $author, string $title): CForumPost
    {
        $em = $this->getEntityManager();

        $forum = (new CForum())
            ->setTitle('Forum '.$title)
            ->setParent($course)
            ->setCreator($author)
            ->addCourseLink($course)
        ;
        $em->persist($forum);
        $em->flush();

        $thread = (new CForumThread())
            ->setTitle('Thread '.$title)
            ->setThreadDate(new DateTime())
            ->setForum($forum)
            ->setUser($author)
            ->setParent($forum)
            ->setCreator($author)
            ->addCourseLink($course)
        ;
        $em->persist($thread);
        $em->flush();

        $post = (new CForumPost())
            ->setTitle('Post '.$title)
            ->setPostText('Post text '.$title)
            ->setThread($thread)
            ->setForum($forum)
            ->setUser($author)
            ->setCreator($author)
            ->setPostDate(new DateTime())
            ->setVisible(true)
            ->setStatus(CForumPost::STATUS_VALIDATED)
            ->setParent($thread)
            ->addCourseLink($course)
        ;
        $em->persist($post);
        $em->flush();

        return $post;
    }

    private function createForumAttachment(Course $course, CForumPost $post): CForumAttachment
    {
        $attachment = (new CForumAttachment())
            ->setCId($course->getId())
            ->setComment('')
            ->setFilename('file.txt')
            ->setPath('file.txt')
            ->setSize(1)
            ->setPost($post)
            ->setParent($post)
            ->setCreator($post->getUser())
            ->addCourseLink($course)
        ;

        $em = $this->getEntityManager();
        $em->persist($attachment);
        $em->flush();

        return $attachment;
    }

    private function createSubmission(Course $course, User $teacher, User $student, string $title): CStudentPublication
    {
        $em = $this->getEntityManager();

        $assignment = (new CStudentPublication())
            ->setTitle('Assignment '.$title)
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setCreator($teacher)
            ->setActive(1)
            ->addCourseLink($course)
        ;
        $em->persist($assignment);
        $em->flush();

        $submission = (new CStudentPublication())
            ->setTitle('Submission '.$title)
            ->setParent($assignment)
            ->setPublicationParent($assignment)
            ->setFiletype('file')
            ->setWeight(0)
            ->setCreator($student)
            ->setUser($student)
            ->setActive(1)
            ->addCourseLink($course)
        ;
        $em->persist($submission);
        $em->flush();

        return $submission;
    }

    private function createComment(CStudentPublication $submission, User $teacher): CStudentPublicationComment
    {
        $comment = (new CStudentPublicationComment())
            ->setComment('Feedback')
            ->setUser($teacher)
            ->setPublication($submission)
            ->setParent($submission)
            ->setCreator($teacher)
        ;

        $em = $this->getEntityManager();
        $em->persist($comment);
        $em->flush();

        return $comment;
    }

    private function createCourseGroup(Course $course, User $teacher, string $title): CGroup
    {
        $group = (new CGroup())
            ->setTitle($title)
            ->setParent($course)
            ->setCreator($teacher)
            ->setMaxStudent(100)
            ->addCourseLink($course)
        ;

        $em = $this->getEntityManager();
        $em->persist($group);
        $em->flush();

        return $group;
    }

    private function createLinkCategory(Course $course, User $teacher, string $title): CLinkCategory
    {
        $category = (new CLinkCategory())
            ->setTitle($title)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;

        $em = $this->getEntityManager();
        $em->persist($category);
        $em->flush();

        return $category;
    }

    private function createAttendanceCalendar(Course $course, User $teacher, string $title): CAttendanceCalendar
    {
        $em = $this->getEntityManager();

        $attendance = (new CAttendance())
            ->setTitle($title)
            ->setLocked(0)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($attendance);
        $em->flush();

        $calendar = (new CAttendanceCalendar())
            ->setAttendance($attendance)
            ->setDateTime(new DateTime())
            ->setDoneAttendance(false)
            ->setBlocked(false)
        ;
        $em->persist($calendar);
        $em->flush();

        return $calendar;
    }

    /**
     * @return string[]
     */
    private function getIds(Client $client, string $url): array
    {
        $response = $client->request('GET', $url, ['headers' => ['Accept' => 'application/ld+json']]);
        $this->assertResponseStatusCodeSame(200);
        $data = $response->toArray();

        return array_map(
            static fn (array $item): string => (string) $item['@id'],
            $data['hydra:member'] ?? $data['member'] ?? [],
        );
    }
}
