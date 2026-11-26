<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\Tests\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Service\Assignment\AssignmentSessionDuplicator;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AssignmentSessionSafetyTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testOriginalCannotBeDeletedWhileSessionCopiesExist(): void
    {
        $ctx = $this->createScenario('delete');
        $originalId = $ctx['original']->getIid();
        $firstId = $ctx['firstCopy']->getIid();
        $secondId = $ctx['secondCopy']->getIid();
        $this->createClientWithCredentials()->request(
            'DELETE',
            '/api/c_student_publications/'.$originalId.'?cid='.$ctx['course']->getId(),
        );
        self::assertResponseStatusCodeSame(409);

        $em = $this->getEntityManager();
        $em->clear();
        self::assertNotNull($em->find(CStudentPublication::class, $originalId));
        self::assertSame($originalId, $em->find(CStudentPublication::class, $firstId)->getPublicationParent()->getIid());
        self::assertSame($originalId, $em->find(CStudentPublication::class, $secondId)->getPublicationParent()->getIid());
    }

    public function testDirectItemAccessRequiresTheExactSessionAndDoesNotExposeTheOriginal(): void
    {
        $ctx = $this->createScenario('item_context');
        $base = '/api/c_student_publications/';
        $query = '?cid='.$ctx['course']->getId().'&sid='.$ctx['first']->getId();
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']));

        foreach ([$ctx['original'], $ctx['secondCopy']] as $assignment) {
            $client->request('GET', $base.$assignment->getIid().$query);
            self::assertResponseStatusCodeSame(403);
        }

        $client->request('GET', $base.$ctx['firstCopy']->getIid().'?cid='.$ctx['course']->getId());
        self::assertResponseStatusCodeSame(403);
        $client->request('GET', $base.$ctx['firstCopy']->getIid());
        self::assertResponseStatusCodeSame(403);

        $response = $client->request('GET', $base.$ctx['firstCopy']->getIid().$query);
        self::assertResponseIsSuccessful();
        $data = $response->toArray();
        self::assertSame($ctx['firstCopy']->getIid(), $data['iid']);
        self::assertNull($data['publicationParent'] ?? null);

        $em = $this->getEntityManager();
        $copy = $em->find(CStudentPublication::class, $ctx['firstCopy']->getIid());
        $course = $em->find(Course::class, $ctx['course']->getId());
        $session = $em->find(Session::class, $ctx['first']->getId());
        $student = $em->find(User::class, $ctx['student']->getId());
        $submission = (new CStudentPublication())
            ->setTitle('Session answer for item context')
            ->setFiletype('file')
            ->setUser($student)
            ->setCreator($student)
            ->setPublicationParent($copy)
            ->setParent($copy)
            ->addCourseLink($course, $session)
        ;
        $em->persist($submission);
        $em->flush();
        $submissionId = $submission->getIid();
        $response = $client->request('GET', $base.$submissionId.$query);
        self::assertResponseIsSuccessful();
        $parent = $response->toArray()['publicationParent'];
        self::assertSame($ctx['firstCopy']->getIid(), $parent['iid']);
        self::assertNull($parent['publicationParent'] ?? null);

        $client = $this->createClientWithCredentials($this->getUserToken([], true));
        $client->request('DELETE', $base.$ctx['secondCopy']->getIid().$query);
        self::assertResponseStatusCodeSame(403);
        self::assertNotNull($this->getEntityManager()->find(CStudentPublication::class, $ctx['secondCopy']->getIid()));
    }

    public function testCustomAssignmentEndpointsNeverFallBackToBaseOrAnotherSession(): void
    {
        $ctx = $this->createScenario('custom_context');
        $base = '/assignments/';
        $query = '?cid='.$ctx['course']->getId().'&sid='.$ctx['first']->getId();
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']));

        foreach ([$ctx['original'], $ctx['secondCopy']] as $assignment) {
            foreach (['/detail', '/submissions'] as $suffix) {
                $client->request('GET', $base.$assignment->getIid().$suffix.$query);
                self::assertResponseStatusCodeSame(404);
            }
        }
        $client->request('GET', $base.$ctx['firstCopy']->getIid().'/detail'.$query);
        self::assertResponseIsSuccessful();
        $client->request('GET', $base.$ctx['firstCopy']->getIid().'/submissions'.$query);
        self::assertResponseIsSuccessful();
        $client->request('GET', $base.$ctx['original']->getIid().'/detail?cid='.$ctx['course']->getId());
        self::assertResponseIsSuccessful();

        $client = $this->createClientWithCredentials($this->getUserToken([], true));
        foreach ([$ctx['original'], $ctx['secondCopy']] as $assignment) {
            foreach (['/submissions/teacher', '/submissions/teacher/groups', '/download-package'] as $suffix) {
                $client->request('GET', $base.$assignment->getIid().$suffix.$query);
                self::assertResponseStatusCodeSame(404);
            }
            $client->request('DELETE', $base.$assignment->getIid().'/corrections/delete'.$query);
            self::assertResponseStatusCodeSame(404);
        }
        $client->request('GET', $base.$ctx['firstCopy']->getIid().'/submissions/teacher'.$query);
        self::assertResponseIsSuccessful();
    }

    public function testBinaryUploadRejectsOriginalAndOtherSessionCopy(): void
    {
        $ctx = $this->createScenario('binary');
        $token = $this->getUserTokenFromUser($ctx['student']);
        $client = $this->createClientWithCredentials($token);
        $path = tempnam(sys_get_temp_dir(), 'assignment-session-');
        self::assertIsString($path);
        file_put_contents($path, 'Test submission');

        try {
            foreach ([$ctx['original'], $ctx['secondCopy']] as $assignment) {
                $client->request(
                    'POST',
                    '/api/c_student_publications/upload?cid='.$ctx['course']->getId().'&sid='.$ctx['first']->getId(),
                    [
                        'headers' => ['Content-Type' => 'multipart/form-data'],
                        'extra' => [
                            'parameters' => [
                                'title' => 'Test submission',
                                'filetype' => 'file',
                                'parentId' => (string) $assignment->getIid(),
                                'parentResourceNodeId' => (string) $assignment->getResourceNode()->getId(),
                            ],
                            'files' => ['uploadFile' => new UploadedFile($path, 'answer.txt', 'text/plain', null, true)],
                        ],
                    ],
                );
                self::assertResponseStatusCodeSame(404);
            }
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $em = $this->getEntityManager();
        $em->clear();
        $repo = $em->getRepository(CStudentPublication::class);
        foreach ([$ctx['original'], $ctx['firstCopy'], $ctx['secondCopy']] as $assignment) {
            self::assertSame(0, $repo->count(['publicationParent' => $assignment->getIid(), 'filetype' => 'file']));
        }
    }

    public function testHtmlSubmissionUsesOnlyTheAuthorizedSessionCopy(): void
    {
        $ctx = $this->createScenario('html');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']));
        $response = $client->request(
            'POST',
            '/api/c_student_publications/upload?cid='.$ctx['course']->getId().'&sid='.$ctx['first']->getId(),
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => [
                    'parameters' => [
                        'title' => 'Session answer',
                        'comment' => '<p>My answer</p>',
                        'filetype' => 'file',
                        'submissionFormat' => 'html',
                        'parentId' => (string) $ctx['firstCopy']->getIid(),
                        // A stale SPA node cannot move the submission to the original assignment.
                        'parentResourceNodeId' => (string) $ctx['original']->getResourceNode()->getId(),
                    ],
                ],
            ],
        );
        self::assertResponseStatusCodeSame(201);

        $id = (int) $response->toArray()['iid'];
        $em = $this->getEntityManager();
        $em->clear();
        $submission = $em->find(CStudentPublication::class, $id);
        self::assertInstanceOf(CStudentPublication::class, $submission);
        self::assertSame($ctx['firstCopy']->getIid(), $submission->getPublicationParent()->getIid());
        self::assertSame($ctx['firstCopy']->getResourceNode()->getId(), $submission->getResourceNode()->getParent()->getId());
        self::assertSame($ctx['first']->getId(), $submission->getFirstResourceLink()->getSession()->getId());
        self::assertSame($ctx['student']->getId(), $submission->getUser()->getId());
    }

    public function testStudentListOnlyShowsAssignmentsInTheRequestedSession(): void
    {
        $ctx = $this->createScenario('list');
        $client = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']));
        $response = $client->request(
            'GET',
            '/assignments/student?cid='.$ctx['course']->getId().'&sid='.$ctx['first']->getId(),
        );
        self::assertResponseStatusCodeSame(200);
        $ids = array_map(static fn (array $assignment): int => (int) $assignment['iid'], $response->toArray()['hydra:member']);
        self::assertContains($ctx['firstCopy']->getIid(), $ids);
        self::assertNotContains($ctx['original']->getIid(), $ids);
        self::assertNotContains($ctx['secondCopy']->getIid(), $ids);
    }

    public function testBinaryUploadToTheSessionCopySucceeds(): void
    {
        $ctx = $this->createScenario('binary_success');
        $path = tempnam(sys_get_temp_dir(), 'assignment-session-');
        self::assertIsString($path);
        file_put_contents($path, 'Session submission');

        try {
            $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['student']))->request(
                'POST',
                '/api/c_student_publications/upload?cid='.$ctx['course']->getId().'&sid='.$ctx['first']->getId(),
                [
                    'headers' => ['Content-Type' => 'multipart/form-data'],
                    'extra' => [
                        'parameters' => [
                            'title' => 'Session submission',
                            'filetype' => 'file',
                            'parentId' => (string) $ctx['firstCopy']->getIid(),
                            'parentResourceNodeId' => (string) $ctx['secondCopy']->getResourceNode()->getId(),
                        ],
                        'files' => ['uploadFile' => new UploadedFile($path, 'answer.txt', 'text/plain', null, true)],
                    ],
                ],
            );
            self::assertResponseStatusCodeSame(201);
            $id = (int) $response->toArray()['iid'];
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        $em = $this->getEntityManager();
        $em->clear();
        $submission = $em->find(CStudentPublication::class, $id);
        self::assertInstanceOf(CStudentPublication::class, $submission);
        self::assertSame($ctx['firstCopy']->getIid(), $submission->getPublicationParent()->getIid());
        self::assertSame($ctx['firstCopy']->getResourceNode()->getId(), $submission->getResourceNode()->getParent()->getId());
        self::assertSame($ctx['first']->getId(), $submission->getFirstResourceLink()->getSession()->getId());
    }

    /**
     * @return array{course: Course, first: Session, student: User, original: CStudentPublication, firstCopy: CStudentPublication, secondCopy: CStudentPublication}
     */
    private function createScenario(string $suffix): array
    {
        $course = $this->createCourse('Assignment session safety '.$suffix);
        $course->setVisibility(Course::REGISTERED);
        $student = $this->createUser('assignment_session_safety_'.$suffix, '', '', 'ROLE_STUDENT');
        $course->addUserAsStudent($student);
        self::getContainer()->get(CourseRepository::class)->update($course);
        $first = $this->createSession('Assignment first '.$suffix);
        $second = $this->createSession('Assignment second '.$suffix);
        $first->setAccessStartDate(null);
        $first->setAccessEndDate(null);
        $second->setAccessStartDate(null);
        $second->setAccessEndDate(null);
        $first->addCourse($course);
        $second->addCourse($course);
        $sessionRepo = self::getContainer()->get(SessionRepository::class);
        $sessionRepo->update($first);
        $sessionRepo->update($second);
        $sessionRepo->addUserInCourse(Session::STUDENT, $student, $course, $first);

        $teacher = $this->getUser('admin');
        $original = (new CStudentPublication())
            ->setTitle('Original assignment '.$suffix)
            ->setDescription('Instructions')
            ->setUser($teacher)
            ->setCreator($teacher)
            ->setParent($course)
            ->setActive(1)
            ->setAllowTextAssignment(0)
            ->addCourseLink($course)
        ;
        $em = $this->getEntityManager();
        $em->persist($original);
        $em->flush();

        $duplicator = self::getContainer()->get(AssignmentSessionDuplicator::class);
        $firstCopy = $duplicator->getOrCreate($original, $course, $first);
        $secondCopy = $duplicator->getOrCreate($original, $course, $second);

        return compact('course', 'first', 'student', 'original', 'firstCopy', 'secondCopy');
    }
}
