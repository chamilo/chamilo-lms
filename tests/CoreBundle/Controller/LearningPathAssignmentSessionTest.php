<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

final class LearningPathAssignmentSessionTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testOpeningBaseAssignmentCreatesOneCopyPerSessionWithoutChangingTheLearningPath(): void
    {
        $course = $this->createCourse('LP assignment session course');
        $course->setVisibility(Course::REGISTERED);
        $student = $this->createUser('lp_assignment_session_student', '', '', 'ROLE_STUDENT');
        $course->addUserAsStudent($student);
        self::getContainer()->get(CourseRepository::class)->update($course);
        $first = $this->createSession('LP assignment first session');
        $second = $this->createSession('LP assignment second session');
        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        foreach ([$first, $second] as $session) {
            $session->setAccessStartDate(null);
            $session->setAccessEndDate(null);
            $session->addCourse($course);
            $sessionRepo->update($session);
            $sessionRepo->addUserInCourse(Session::STUDENT, $student, $course, $session);
        }

        $teacher = $this->getAdmin();
        $assignment = (new CStudentPublication())
            ->setTitle('Original LP assignment')
            ->setUser($teacher)
            ->setCreator($teacher)
            ->setParent($course)
            ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED)
        ;
        $em = $this->getEntityManager();
        $em->persist($assignment);
        $em->flush();
        $lp = (new CLp())
            ->setTitle('LP assignment')
            ->setLpType(CLp::LP_TYPE)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED)
        ;
        self::getContainer()->get(CLpRepository::class)->createLp($lp);
        $itemRepo = self::getContainer()->get(CLpItemRepository::class);
        $item = (new CLpItem())
            ->setLp($lp)
            ->setParent($itemRepo->getRootItem((int) $lp->getIid()))
            ->setTitle('Assignment step')
            ->setItemType('student_publication')
            ->setPath((string) $assignment->getIid())
            ->setRef((string) $assignment->getIid())
            ->setDisplayOrder(1)
            ->setPrerequisite('')
        ;
        $itemRepo->create($item);
        $sourceId = $assignment->getIid();
        $itemId = $item->getIid();
        $courseId = $course->getId();
        $firstId = $first->getId();
        $secondId = $second->getId();
        $url = '/api/learning_paths/'.$lp->getIid().'/runtime';
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($student));

        $copyIds = [];
        foreach ([$firstId, $secondId] as $sid) {
            $query = '?cid='.$courseId.'&sid='.$sid.'&gid=0';
            $before = $client->request('GET', $url.$query.'&itemId='.$itemId);
            self::assertResponseIsSuccessful();
            self::assertSame('', $before->toArray()['contentUrl']);
            self::assertSame(\count($copyIds), $this->getEntityManager()->getRepository(CStudentPublication::class)->count(['publicationParent' => $sourceId, 'filetype' => 'folder']));

            $client->request('POST', $url.'/item'.$query, ['json' => ['itemId' => $itemId]]);
            self::assertResponseStatusCodeSame(204);
            $client->request('POST', $url.'/item'.$query, ['json' => ['itemId' => $itemId]]);
            self::assertResponseStatusCodeSame(204);
            $response = $client->request('GET', $url.$query.'&itemId='.$itemId);
            self::assertResponseIsSuccessful();

            $em = $this->getEntityManager();
            $em->clear();
            $copies = $em->getRepository(CStudentPublication::class)->findBy(['publicationParent' => $sourceId, 'filetype' => 'folder']);
            self::assertCount(\count($copyIds) + 1, $copies);
            $copy = null;
            foreach ($copies as $candidate) {
                if ($sid === $candidate->getFirstResourceLink()->getSession()?->getId()) {
                    $copy = $candidate;
                }
            }
            self::assertInstanceOf(CStudentPublication::class, $copy);
            $copyIds[] = $copy->getIid();
            self::assertStringContainsString('/resources/assignment/'.$copy->getResourceNode()->getId().'/submission/'.$copy->getIid().'?', $response->toArray()['contentUrl']);
            self::assertSame((string) $sourceId, $em->find(CLpItem::class, $itemId)->getPath());
        }
        self::assertNotSame($copyIds[0], $copyIds[1]);
    }
}
