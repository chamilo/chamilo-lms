<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CGroupRelUser;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * A learner reads a resource only while its link is published, whatever the context the request
 * names: the course, a session of the course or a group of the course. Teachers and coaches keep
 * reading drafts, and a draft stays readable when it is opened from a learning path item.
 *
 * The documents are read straight through the item operation of the API, which is authorized by
 * ResourceNodeVoter, the single authority on who reads a resource.
 */
final class ResourceNodeVoterLearnerVisibilityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCourseLearnerReadsPublishedDocumentOnly(): void
    {
        $ctx = $this->bootstrapScenario('course');
        $query = '?cid='.$ctx['course']->getId();

        $this->assertReadStatus($ctx['courseLearner'], $this->document($ctx['course'], ResourceLink::VISIBILITY_PUBLISHED), $query, 200);
        $this->assertReadStatus($ctx['courseLearner'], $this->document($ctx['course'], ResourceLink::VISIBILITY_DRAFT), $query, 403);
    }

    public function testSessionLearnerReadsPublishedSessionDocumentOnly(): void
    {
        $ctx = $this->bootstrapScenario('session');
        $query = '?cid='.$ctx['course']->getId().'&sid='.$ctx['session']->getId();

        $this->assertReadStatus(
            $ctx['sessionLearner'],
            $this->document($ctx['course'], ResourceLink::VISIBILITY_PUBLISHED, $ctx['session']),
            $query,
            200
        );
        $this->assertReadStatus(
            $ctx['sessionLearner'],
            $this->document($ctx['course'], ResourceLink::VISIBILITY_DRAFT, $ctx['session']),
            $query,
            403
        );
    }

    /**
     * Resources of the base course are also shown inside the session of that course.
     */
    public function testSessionLearnerReadsPublishedBaseCourseDocumentOnly(): void
    {
        $ctx = $this->bootstrapScenario('session_base');
        $query = '?cid='.$ctx['course']->getId().'&sid='.$ctx['session']->getId();

        $this->assertReadStatus($ctx['sessionLearner'], $this->document($ctx['course'], ResourceLink::VISIBILITY_PUBLISHED), $query, 200);
        $this->assertReadStatus($ctx['sessionLearner'], $this->document($ctx['course'], ResourceLink::VISIBILITY_DRAFT), $query, 403);
    }

    public function testGroupMemberReadsPublishedGroupDocumentOnly(): void
    {
        $ctx = $this->bootstrapScenario('group');
        $query = '?cid='.$ctx['course']->getId().'&gid='.$ctx['group']->getIid();

        $this->assertReadStatus(
            $ctx['groupLearner'],
            $this->document($ctx['course'], ResourceLink::VISIBILITY_PUBLISHED, null, $ctx['group']),
            $query,
            200
        );
        $this->assertReadStatus(
            $ctx['groupLearner'],
            $this->document($ctx['course'], ResourceLink::VISIBILITY_DRAFT, null, $ctx['group']),
            $query,
            403
        );
    }

    public function testTeachersAndCoachesStillReadDrafts(): void
    {
        $ctx = $this->bootstrapScenario('teachers');
        $cid = '?cid='.$ctx['course']->getId();

        $this->assertReadStatus(
            $ctx['teacher'],
            $this->document($ctx['course'], ResourceLink::VISIBILITY_DRAFT),
            $cid,
            200
        );
        $this->assertReadStatus(
            $ctx['coach'],
            $this->document($ctx['course'], ResourceLink::VISIBILITY_DRAFT, $ctx['session']),
            $cid.'&sid='.$ctx['session']->getId(),
            200
        );
    }

    /**
     * A learning path may carry a document its teacher keeps hidden in the tool: the learner
     * reads it from the item, in the course and also in a session.
     */
    public function testDraftDocumentOfLearningPathItemIsReadableInSession(): void
    {
        $ctx = $this->bootstrapScenario('lp_item');
        $document = $this->document($ctx['course'], ResourceLink::VISIBILITY_DRAFT, $ctx['session']);
        $itemId = $this->createLearningPathItem($ctx['course'], $ctx['session'], $document);
        $query = '?cid='.$ctx['course']->getId().'&sid='.$ctx['session']->getId();

        $this->assertReadStatus($ctx['sessionLearner'], $document, $query, 403);
        $this->assertReadStatus($ctx['sessionLearner'], $document, $query.'&lp_id='.$itemId['lp'].'&lp_item_id='.$itemId['item'], 200);
    }

    /**
     * A private course with a teacher, a learner of the course, a session of the course with a
     * coach and a learner, and a group with a member.
     *
     * @return array{
     *     course: Course,
     *     session: Session,
     *     group: CGroup,
     *     teacher: User,
     *     coach: User,
     *     courseLearner: User,
     *     sessionLearner: User,
     *     groupLearner: User
     * }
     */
    private function bootstrapScenario(string $suffix): array
    {
        $_SERVER['REMOTE_ADDR'] = 'localhost';

        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        /** @var SessionRepository $sessionRepo */
        $sessionRepo = self::getContainer()->get(SessionRepository::class);

        $course = $this->createCourse('RNV '.$suffix);
        $course->setVisibility(Course::REGISTERED);

        $teacher = $this->createUser('rnv_teacher_'.$suffix);
        $coach = $this->createUser('rnv_coach_'.$suffix);
        $courseLearner = $this->createUser('rnv_course_'.$suffix);
        $sessionLearner = $this->createUser('rnv_session_'.$suffix);
        $groupLearner = $this->createUser('rnv_group_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $course->addUserAsStudent($courseLearner);
        $course->addUserAsStudent($groupLearner);
        $courseRepo->update($course);

        $session = $this->createSession('RNV session '.$suffix);
        $session->addCourse($course)->addGeneralCoach($coach);
        $sessionRepo->update($session);
        $sessionRepo->addUserInCourse(Session::STUDENT, $sessionLearner, $course, $session);

        $group = $this->createGroup('RNV group '.$suffix, $course);
        $em = $this->getEntityManager();
        $em->persist(
            (new CGroupRelUser())
                ->setStatus(1)
                ->setUser($groupLearner)
                ->setGroup($group)
                ->setRole('member')
                ->setCId((int) $course->getId())
        );
        $em->flush();

        return [
            'course' => $course,
            'session' => $session,
            'group' => $group,
            'teacher' => $teacher,
            'coach' => $coach,
            'courseLearner' => $courseLearner,
            'sessionLearner' => $sessionLearner,
            'groupLearner' => $groupLearner,
        ];
    }

    /**
     * Uploads a file document as admin, linked to the course (and the session or the group when
     * given) with the given visibility, and returns its iid.
     */
    private function document(Course $course, int $visibility, ?Session $session = null, ?CGroup $group = null): int
    {
        $file = $this->getUploadedFile();
        $link = ['cid' => $course->getId(), 'visibility' => $visibility];
        $query = '?cid='.$course->getId();

        if (null !== $session) {
            $link['sid'] = $session->getId();
            $query .= '&sid='.$session->getId();
        }

        if (null !== $group) {
            $link['gid'] = $group->getIid();
            $query .= '&gid='.$group->getIid();
        }

        // The token is cached: asking for the admin one again after a learner's would return the learner's.
        $adminToken = $this->getUserToken(['username' => 'admin', 'password' => 'admin'], true);
        $response = $this->createClientWithCredentials($adminToken)->request(
            'POST',
            '/api/documents'.$query,
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => ['files' => ['uploadFile' => $file]],
                'json' => [
                    'filetype' => 'file',
                    'size' => $file->getSize(),
                    'parentResourceNodeId' => $course->getResourceNode()->getId(),
                    'resourceLinkList' => [$link],
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(201);

        return (int) json_decode($response->getContent())->iid;
    }

    /**
     * @return array{lp: int, item: int}
     */
    private function createLearningPathItem(Course $course, Session $session, int $documentId): array
    {
        // The HTTP requests of the scenario detach the entities created before them.
        $em = $this->getEntityManager();
        $course = $em->getRepository(Course::class)->find($course->getId());
        $session = $em->getRepository(Session::class)->find($session->getId());

        $lp = (new CLp())
            ->setTitle('RNV learning path')
            ->setLpType(CLp::LP_TYPE)
            ->setParent($course)
            ->setCreator($this->getAdmin())
            ->addCourseLink($course, $session)
        ;
        $em->persist($lp);
        $em->flush();

        $item = (new CLpItem())
            ->setLp($lp)
            ->setItemType('document')
            ->setTitle('RNV item')
            ->setPath((string) $documentId)
            ->setRef((string) $documentId)
            ->setDisplayOrder(1)
        ;
        $em->persist($item);
        $em->flush();

        return ['lp' => (int) $lp->getIid(), 'item' => (int) $item->getIid()];
    }

    private function assertReadStatus(User $user, int $documentId, string $query, int $expected): void
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'GET',
            '/api/documents/'.$documentId.$query
        );
        $this->assertResponseStatusCodeSame($expected);
    }
}
