<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

final class AssignmentSessionManualDuplicationTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testTeacherCanSeeAndDuplicateBaseAssignmentsOnlyInTheirSession(): void
    {
        $course = $this->createCourse('Manual session assignment copy');
        $course->setVisibility(Course::REGISTERED);
        $coach = $this->createUser('assignment_manual_coach', '', '', 'ROLE_TEACHER');
        $student = $this->createUser('assignment_manual_student', '', '', 'ROLE_STUDENT');
        $course->addUserAsStudent($student);
        self::getContainer()->get(CourseRepository::class)->update($course);
        $session = $this->createSession('Manual copy session');
        $session->setAccessStartDate(null);
        $session->setAccessEndDate(null);
        $session->addCourse($course);
        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        $sessionRepo->update($session);
        $sessionRepo->addUserInCourse(Session::COURSE_COACH, $coach, $course, $session);
        $sessionRepo->addUserInCourse(Session::STUDENT, $student, $course, $session);
        $other = $this->createCourse('Unrelated manual copy course');
        $teacher = $this->getAdmin();
        $original = (new CStudentPublication())
            ->setTitle('Original manual assignment')
            ->setDescription('Instructions')
            ->setUser($teacher)
            ->setCreator($teacher)
            ->setParent($course)
            ->addCourseLink($course)
        ;
        $unrelated = (new CStudentPublication())
            ->setTitle('Unrelated assignment')
            ->setUser($teacher)
            ->setCreator($teacher)
            ->setParent($other)
            ->addCourseLink($other)
        ;
        $em = $this->getEntityManager();
        $em->persist($original);
        $em->persist($unrelated);
        $em->flush();
        $originalId = $original->getIid();
        $otherId = $unrelated->getIid();
        $sessionId = $session->getId();
        $query = '?cid='.$course->getId().'&sid='.$sessionId.'&gid=0';
        $list = '/assignments/base-course'.$query;
        $duplicate = '/assignments/'.$originalId.'/duplicate-to-session'.$query;

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($coach));
        $response = $client->request('GET', $list);
        self::assertResponseIsSuccessful();
        self::assertSame([[
            'id' => $originalId,
            'title' => 'Original manual assignment',
            'copied' => false,
            'canDuplicate' => true,
        ]], $response->toArray()['items']);
        $client->request('POST', '/assignments/'.$otherId.'/duplicate-to-session'.$query);
        self::assertResponseStatusCodeSame(404);
        $response = $client->request('POST', $duplicate);
        self::assertResponseIsSuccessful();
        $copyId = $response->toArray()['id'];
        self::assertNotSame($originalId, $copyId);
        $response = $client->request('POST', $duplicate);
        self::assertResponseIsSuccessful();
        self::assertSame($copyId, $response->toArray()['id']);
        $response = $client->request('GET', $list);
        self::assertResponseIsSuccessful();
        self::assertTrue($response->toArray()['items'][0]['copied']);
        self::assertFalse($response->toArray()['items'][0]['canDuplicate']);

        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($student));
        $client->request('GET', $list);
        self::assertResponseStatusCodeSame(403);
        $client->request('POST', $duplicate);
        self::assertResponseStatusCodeSame(403);

        $em = $this->getEntityManager();
        $em->clear();
        $repo = $em->getRepository(CStudentPublication::class);
        $copy = $repo->find($copyId);
        self::assertSame($originalId, $copy->getPublicationParent()->getIid());
        self::assertSame($sessionId, $copy->getFirstResourceLink()->getSession()->getId());
        self::assertSame(1, $repo->count(['publicationParent' => $originalId, 'filetype' => 'folder']));
    }
}
