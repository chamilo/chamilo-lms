<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

final class MoveSelectedDocumentsApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    private function newDocument(Course $course, string $title, string $type = 'file', ?CDocument $parent = null): CDocument
    {
        $document = (new CDocument())
            ->setTitle($title)
            ->setFiletype($type)
            ->setTemplate(false)
            ->setReadonly(false)
            ->setParent($parent ?? $course)
            ->setCreator($this->getAdmin())
            ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED)
        ;

        self::getContainer()->get(CDocumentRepository::class)->create($document);

        return $document;
    }

    /**
     * @param list<int> $ids
     */
    private function move(Course $course, array $ids, int $destinationId): void
    {
        $this->createClientWithCredentials()->request(
            'POST',
            '/api/documents/move-selected?cid='.$course->getId().'&sid=0&gid=0',
            ['json' => ['ids' => $ids, 'parentResourceNodeId' => $destinationId]],
        );
    }

    private function assertDocumentParent(CDocument $document, int $expectedNodeId, ?int $expectedLinkParentId): void
    {
        $em = $this->getEntityManager();
        $em->clear();

        $reloaded = $em->find(CDocument::class, $document->getIid());
        self::assertNotNull($reloaded);
        self::assertSame($expectedNodeId, $reloaded->getResourceNode()->getParent()?->getId());

        $link = $reloaded->getResourceNode()->getResourceLinks()->first();
        self::assertInstanceOf(ResourceLink::class, $link);
        self::assertSame($expectedLinkParentId, $link->getParent()?->getId());
    }

    public function testMovesTwoDocumentsAndRetainsTheirContentTree(): void
    {
        $course = $this->createCourse('Move selected documents');
        $destination = $this->newDocument($course, 'Destination', 'folder');
        $first = $this->newDocument($course, 'First');
        $second = $this->newDocument($course, 'Second');
        $destinationNode = $destination->getResourceNode();
        self::assertNotNull($destinationNode);
        $destinationLinkId = $this->folderLinkId($destination);

        $this->move($course, [(int) $first->getIid(), (int) $second->getIid()], (int) $destinationNode->getId());
        $this->assertResponseStatusCodeSame(200);

        $this->assertDocumentParent($first, (int) $destinationNode->getId(), $destinationLinkId);
        $this->assertDocumentParent($second, (int) $destinationNode->getId(), $destinationLinkId);
    }

    public function testMovingFolderRetainsItsChildren(): void
    {
        $course = $this->createCourse('Move folder subtree');
        $destination = $this->newDocument($course, 'Destination', 'folder');
        $folder = $this->newDocument($course, 'Folder', 'folder');
        $child = $this->newDocument($course, 'Child', 'file', $folder);
        $folderNodeId = (int) $folder->getResourceNode()->getId();
        $destinationNodeId = (int) $destination->getResourceNode()->getId();
        $destinationLinkId = $this->folderLinkId($destination);

        $this->move($course, [(int) $folder->getIid()], $destinationNodeId);
        $this->assertResponseStatusCodeSame(200);
        $this->assertDocumentParent($folder, $destinationNodeId, $destinationLinkId);

        $em = $this->getEntityManager();
        $em->clear();
        $childDocument = $em->find(CDocument::class, $child->getIid());
        self::assertNotNull($childDocument);
        self::assertSame($folderNodeId, $childDocument->getResourceNode()->getParent()?->getId());
    }

    public function testCourseTeacherCanMoveOwnDocuments(): void
    {
        $course = $this->createCourse('Move teacher');
        $teacher = $this->createUser('document_move_teacher_2536');
        $course->addUserAsTeacher($teacher);
        self::getContainer()->get(CourseRepository::class)->update($course);

        $document = $this->newDocument($course, 'Teacher file');
        $destination = $this->newDocument($course, 'Teacher destination', 'folder');
        $destinationId = (int) $destination->getResourceNode()->getId();
        $destinationLinkId = $this->folderLinkId($destination);

        $this->createClientWithCredentials($this->getUserTokenFromUser($teacher))->request(
            'POST',
            '/api/documents/move-selected?cid='.$course->getId(),
            ['json' => [
                'ids' => [(int) $document->getIid()],
                'parentResourceNodeId' => $destinationId,
            ]],
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertDocumentParent($document, $destinationId, $destinationLinkId);
    }

    public function testRejectsForeignCourseDestinationWithoutChangingAnyDocuments(): void
    {
        $course = $this->createCourse('Move source');
        $otherCourse = $this->createCourse('Move foreign');
        $foreignDestination = $this->newDocument($otherCourse, 'Foreign folder', 'folder');
        $first = $this->newDocument($course, 'First');
        $second = $this->newDocument($course, 'Second');

        $this->move($course, [(int) $first->getIid(), (int) $second->getIid()], (int) $foreignDestination->getResourceNode()->getId());
        $this->assertResponseStatusCodeSame(400);

        $this->assertDocumentParent($first, (int) $course->getResourceNode()->getId(), null);
        $this->assertDocumentParent($second, (int) $course->getResourceNode()->getId(), null);
    }

    public function testRejectsMissingSourceWithoutPartiallyMovingSelection(): void
    {
        $course = $this->createCourse('Move missing source');
        $destination = $this->newDocument($course, 'Destination', 'folder');
        $first = $this->newDocument($course, 'First');

        $this->move($course, [(int) $first->getIid(), 2147483647], (int) $destination->getResourceNode()->getId());
        $this->assertResponseStatusCodeSame(400);
        $this->assertDocumentParent($first, (int) $course->getResourceNode()->getId(), null);
    }

    public function testRejectsFolderMoveIntoOwnDescendant(): void
    {
        $course = $this->createCourse('Move cycle');
        $folder = $this->newDocument($course, 'Parent', 'folder');
        $child = $this->newDocument($course, 'Child', 'folder', $folder);

        $this->move($course, [(int) $folder->getIid()], (int) $child->getResourceNode()->getId());
        $this->assertResponseStatusCodeSame(400);
        $this->assertDocumentParent($folder, (int) $course->getResourceNode()->getId(), null);
    }

    public function testStudentCannotMoveDocuments(): void
    {
        $course = $this->createCourse('Move student security');
        $student = $this->createUser('student_move_2536');
        $course->addUserAsStudent($student);
        $this->getEntityManager()->flush();

        $document = $this->newDocument($course, 'Student blocked');
        $destination = $this->newDocument($course, 'Destination', 'folder');

        $this->createClientWithCredentials($this->getUserTokenFromUser($student))->request(
            'POST',
            '/api/documents/move-selected?cid='.$course->getId(),
            ['json' => [
                'ids' => [(int) $document->getIid()],
                'parentResourceNodeId' => (int) $destination->getResourceNode()->getId(),
            ]],
        );

        $this->assertResponseStatusCodeSame(403);
        $this->assertDocumentParent($document, (int) $course->getResourceNode()->getId(), null);
    }

    public function testSingleMoveAlsoRejectsFolderCycles(): void
    {
        $course = $this->createCourse('Move single cycle');
        $folder = $this->newDocument($course, 'Parent', 'folder');
        $child = $this->newDocument($course, 'Child', 'folder', $folder);

        $this->createClientWithCredentials()->request(
            'PATCH',
            '/api/documents/'.$folder->getIid().'/move?cid='.$course->getId().'&sid=0&gid=0',
            [
                'headers' => ['Content-Type' => 'application/merge-patch+json'],
                'json' => ['parentResourceNodeId' => (int) $child->getResourceNode()->getId()],
            ],
        );

        $this->assertResponseStatusCodeSame(400);
    }

    private function folderLinkId(CDocument $folder): int
    {
        $node = $folder->getResourceNode();
        self::assertNotNull($node);
        $link = $node->getResourceLinks()->first();
        self::assertInstanceOf(ResourceLink::class, $link);

        return (int) $link->getId();
    }
}
