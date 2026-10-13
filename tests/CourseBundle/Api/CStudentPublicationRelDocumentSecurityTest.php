<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationRelDocument;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * Attaching a document to an assignment makes the document readable by whoever can view
 * that assignment. The relation therefore has to stay inside one course: a teacher must
 * not attach what they cannot see (nor to an assignment they do not manage), and the
 * existence of a relation must not open the document to users of other courses.
 */
final class CStudentPublicationRelDocumentSecurityTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testTeacherCannotAttachDocumentOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('attach_foreign_doc');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacherA']))->request(
            'POST',
            '/api/c_student_publication_rel_documents?cid='.$ctx['courseA']->getId(),
            ['json' => [
                'publication' => '/api/c_student_publications/'.$ctx['assignmentA']->getIid(),
                'document' => '/api/documents/'.$ctx['documentB'],
            ]]
        );

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(0, $this->countRelations($ctx['documentB']));

        // The private document of course B is still closed to the teacher of course A.
        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacherA']))->request(
            'GET',
            '/api/documents/'.$ctx['documentB'].'?cid='.$ctx['courseA']->getId()
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTeacherCannotAttachDocumentToAssignmentOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('attach_foreign_work');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacherA']))->request(
            'POST',
            '/api/c_student_publication_rel_documents?cid='.$ctx['courseA']->getId(),
            ['json' => [
                'publication' => '/api/c_student_publications/'.$ctx['assignmentB']->getIid(),
                'document' => '/api/documents/'.$ctx['documentA'],
            ]]
        );

        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(0, $this->countRelations($ctx['documentA']));
    }

    public function testTeacherCanAttachDocumentOfOwnCourseAndLearnersReadIt(): void
    {
        $ctx = $this->bootstrapScenario('attach_own');
        $cid = '?cid='.$ctx['courseA']->getId();

        // The document is a draft: learners can only reach it through the assignment.
        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['studentA']))->request(
            'GET',
            '/api/documents/'.$ctx['documentA'].$cid
        );
        $this->assertResponseStatusCodeSame(403);

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacherA']))->request(
            'POST',
            '/api/c_student_publication_rel_documents'.$cid,
            ['json' => [
                'publication' => '/api/c_student_publications/'.$ctx['assignmentA']->getIid(),
                'document' => '/api/documents/'.$ctx['documentA'],
            ]]
        );
        $this->assertResponseStatusCodeSame(201);

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['studentA']))->request(
            'GET',
            '/api/documents/'.$ctx['documentA'].$cid
        );
        $this->assertResponseStatusCodeSame(200);
    }

    public function testRelationDoesNotOpenDocumentToUsersOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('relation_open');
        $this->createRelation($ctx['assignmentA'], $ctx['documentA']);

        foreach (['?cid='.$ctx['courseB']->getId(), ''] as $query) {
            $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacherB']))->request(
                'GET',
                '/api/documents/'.$ctx['documentA'].$query
            );
            $this->assertResponseStatusCodeSame(403);
        }
    }

    public function testTeacherCannotDeleteRelationOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('delete_cross');
        $relationId = $this->createRelation($ctx['assignmentB'], $ctx['documentB']);

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacherA']))->request(
            'DELETE',
            '/api/c_student_publication_rel_documents/'.$relationId.'?cid='.$ctx['courseA']->getId()
        );
        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(1, $this->countRelations($ctx['documentB']));

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacherB']))->request(
            'DELETE',
            '/api/c_student_publication_rel_documents/'.$relationId.'?cid='.$ctx['courseB']->getId()
        );
        $this->assertResponseStatusCodeSame(204);
        $this->assertSame(0, $this->countRelations($ctx['documentB']));
    }

    public function testCollectionExcludesRelationsOfAnotherCourse(): void
    {
        $ctx = $this->bootstrapScenario('collection_cross');
        $relationA = $this->createRelation($ctx['assignmentA'], $ctx['documentA']);
        $relationB = $this->createRelation($ctx['assignmentB'], $ctx['documentB']);

        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacherA']))->request(
            'GET',
            '/api/c_student_publication_rel_documents?cid='.$ctx['courseA']->getId()
        );

        $this->assertResponseStatusCodeSame(200);
        $ids = array_column($response->toArray()['hydra:member'], '@id');
        $this->assertContains('/api/c_student_publication_rel_documents/'.$relationA, $ids);
        $this->assertNotContains('/api/c_student_publication_rel_documents/'.$relationB, $ids);
    }

    /**
     * Two private courses, each with an assignment of its teacher and a draft document.
     *
     * @return array{
     *     courseA: Course,
     *     courseB: Course,
     *     teacherA: User,
     *     teacherB: User,
     *     studentA: User,
     *     assignmentA: CStudentPublication,
     *     assignmentB: CStudentPublication,
     *     documentA: int,
     *     documentB: int
     * }
     */
    private function bootstrapScenario(string $suffix): array
    {
        $_SERVER['REMOTE_ADDR'] = 'localhost';

        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        $courseA = $this->createCourse('Rel Doc Course A '.$suffix);
        $courseA->setVisibility(Course::REGISTERED);
        $courseB = $this->createCourse('Rel Doc Course B '.$suffix);
        $courseB->setVisibility(Course::REGISTERED);

        $teacherA = $this->createUser('rel_doc_teacher_a_'.$suffix);
        $studentA = $this->createUser('rel_doc_student_a_'.$suffix);
        $teacherB = $this->createUser('rel_doc_teacher_b_'.$suffix);

        $courseA->addUserAsTeacher($teacherA);
        $courseA->addUserAsStudent($studentA);
        $courseB->addUserAsTeacher($teacherB);
        $courseRepo->update($courseA);
        $courseRepo->update($courseB);

        return [
            'courseA' => $courseA,
            'courseB' => $courseB,
            'teacherA' => $teacherA,
            'teacherB' => $teacherB,
            'studentA' => $studentA,
            'assignmentA' => $this->createAssignment($courseA, $teacherA, 'Assignment A '.$suffix),
            'assignmentB' => $this->createAssignment($courseB, $teacherB, 'Assignment B '.$suffix),
            'documentA' => $this->createDraftDocument($courseA),
            'documentB' => $this->createDraftDocument($courseB),
        ];
    }

    private function createAssignment(Course $course, User $creator, string $title): CStudentPublication
    {
        $assignment = (new CStudentPublication())
            ->setTitle($title)
            ->setParent($course)
            ->setFiletype('folder')
            ->setWeight(100)
            ->setUser($creator)
            ->setCreator($creator)
            ->setActive(1)
            ->addCourseLink($course)
        ;

        $em = $this->getEntityManager();
        $em->persist($assignment);
        $em->flush();

        return $assignment;
    }

    /**
     * Uploads a file document as admin, hidden from learners, and returns its iid.
     */
    private function createDraftDocument(Course $course): int
    {
        $file = $this->getUploadedFile();

        $response = $this->createClientWithCredentials($this->getUserToken([]))->request(
            'POST',
            '/api/documents?cid='.$course->getId(),
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => ['files' => ['uploadFile' => $file]],
                'json' => [
                    'filetype' => 'file',
                    'size' => $file->getSize(),
                    'parentResourceNodeId' => $course->getResourceNode()->getId(),
                    'resourceLinkList' => [['cid' => $course->getId(), 'visibility' => ResourceLink::VISIBILITY_DRAFT]],
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(201);

        return (int) json_decode($response->getContent())->iid;
    }

    private function createRelation(CStudentPublication $assignment, int $documentId): int
    {
        // The HTTP requests of the scenario detach the entities created before them.
        $em = $this->getEntityManager();
        $relation = (new CStudentPublicationRelDocument())
            ->setPublication($em->getRepository(CStudentPublication::class)->find($assignment->getIid()))
            ->setDocument($em->getRepository(CDocument::class)->find($documentId))
        ;
        $em->persist($relation);
        $em->flush();

        return (int) $relation->getIid();
    }

    private function countRelations(int $documentId): int
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->getRepository(CStudentPublicationRelDocument::class)->count(['document' => $documentId]);
    }
}
