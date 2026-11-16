<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\PersonalFile;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * The resource links sent in a request body decide where a resource becomes visible, so they
 * may only name the context that authorized the request: the current course for course
 * content, other users for a personal file being shared. Otherwise anyone able to create or
 * edit one resource could publish it into any course or into any user's files.
 *
 * Every denied case has a positive twin proving the 403 does not come from a broken setup.
 */
final class ResourceLinkListContextApiTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testUserCannotCreatePersonalFileLinkedToCourse(): void
    {
        $course = $this->createCourse('RLLC personal course');
        $student = $this->createUser('rllc_student_create');

        $this->postPersonalFile($student, [['cid' => (int) $course->getId(), 'visibility' => 2]]);

        $this->assertSame(0, $this->countCourseLinks(PersonalFile::class, $course));
    }

    public function testUserCanCreateAndSharePersonalFile(): void
    {
        $owner = $this->createUser('rllc_owner_share');
        $receiver = $this->createUser('rllc_receiver_share');

        $fileId = $this->postPersonalFile($owner, []);

        $this->createClientWithCredentials($this->getUserTokenFromUser($owner))->request(
            'PUT',
            '/api/personal_files/'.$fileId,
            ['json' => ['resourceLinkListFromEntity' => [['uid' => (int) $receiver->getId(), 'visibility' => 2]]]],
        );
        $this->assertResponseStatusCodeSame(200);
    }

    public function testOwnerCannotLinkPersonalFileToCourseOnUpdate(): void
    {
        $course = $this->createCourse('RLLC personal update course');
        $owner = $this->createUser('rllc_owner_update');

        $fileId = $this->postPersonalFile($owner, []);

        $this->createClientWithCredentials($this->getUserTokenFromUser($owner))->request(
            'PUT',
            '/api/personal_files/'.$fileId,
            ['json' => ['resourceLinkListFromEntity' => [['cid' => (int) $course->getId(), 'visibility' => 2]]]],
        );
        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(0, $this->countCourseLinks(PersonalFile::class, $course));
    }

    public function testOwnerCannotMovePersonalFileIntoCourseTree(): void
    {
        $course = $this->createCourse('RLLC personal move course');
        $owner = $this->createUser('rllc_owner_move');

        $fileId = $this->postPersonalFile($owner, []);

        $this->createClientWithCredentials($this->getUserTokenFromUser($owner))->request(
            'PUT',
            '/api/personal_files/'.$fileId,
            ['json' => ['parentResourceNodeId' => (int) $course->getResourceNode()->getId()]],
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testTeacherCanChangeVisibilityOfOwnDocument(): void
    {
        $ctx = $this->bootstrapDocumentScenario('own');
        $linkId = (int) $ctx['document']->getResourceNode()->getResourceLinks()->first()->getId();

        $this->putDocument($ctx, [
            'resourceLinkListFromEntity' => [['id' => $linkId, 'visibility' => ResourceLink::VISIBILITY_DRAFT]],
        ]);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testTeacherCannotLinkOwnDocumentToAnotherCourse(): void
    {
        $ctx = $this->bootstrapDocumentScenario('link');

        $this->putDocument($ctx, [
            'resourceLinkListFromEntity' => [['cid' => (int) $ctx['foreignCourse']->getId(), 'visibility' => 2]],
        ]);
        $this->assertResponseStatusCodeSame(403);

        // A link id that does not belong to the document must not turn into a new link either.
        $this->putDocument($ctx, [
            'resourceLinkListFromEntity' => [
                ['id' => 999999999, 'cid' => (int) $ctx['foreignCourse']->getId(), 'visibility' => 2],
            ],
        ]);

        $this->assertSame(0, $this->countCourseLinks(CDocument::class, $ctx['foreignCourse']));
    }

    public function testTeacherCannotChangeVisibilityOfDocumentLinkInAnotherCourse(): void
    {
        $ctx = $this->bootstrapSharedDocumentScenario('shared');

        $this->putDocument($ctx, [
            'resourceLinkListFromEntity' => [
                ['id' => $ctx['foreignLinkId'], 'visibility' => ResourceLink::VISIBILITY_PUBLISHED],
            ],
        ]);
        $this->assertResponseStatusCodeSame(403);
        $this->assertSame(ResourceLink::VISIBILITY_DRAFT, $this->getLinkVisibility($ctx['foreignLinkId']));

        // The same teacher still manages the link of the course that authorized the request.
        $this->putDocument($ctx, [
            'resourceLinkListFromEntity' => [
                ['id' => $ctx['localLinkId'], 'visibility' => ResourceLink::VISIBILITY_DRAFT],
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(ResourceLink::VISIBILITY_DRAFT, $this->getLinkVisibility($ctx['localLinkId']));
    }

    public function testCreatorCannotChangeVisibilityInAnotherCourseByRequestingItsContext(): void
    {
        $ctx = $this->bootstrapSharedDocumentScenario('ctxswitch');

        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        // Any member of the other course, or any user if the course is open, may request its context.
        $ctx['foreignCourse']->addUserAsStudent($ctx['teacher']);
        $courseRepo->update($ctx['foreignCourse']);

        $this->requestDocument($ctx, 'PUT', '', $ctx['foreignCourse'], [
            'resourceLinkListFromEntity' => [
                ['id' => $ctx['foreignLinkId'], 'visibility' => ResourceLink::VISIBILITY_PUBLISHED],
            ],
        ]);
        $this->assertResponseStatusCodeSame(403);

        $this->requestDocument($ctx, 'PATCH', '/toggle_visibility', $ctx['foreignCourse'], []);
        $this->assertResponseStatusCodeSame(403);

        $this->assertSame(ResourceLink::VISIBILITY_DRAFT, $this->getLinkVisibility($ctx['foreignLinkId']));
    }

    public function testTeacherCanToggleVisibilityOfDocumentInOwnCourse(): void
    {
        $ctx = $this->bootstrapSharedDocumentScenario('toggleown');

        $this->requestDocument($ctx, 'PATCH', '/toggle_visibility', $ctx['course'], []);
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(ResourceLink::VISIBILITY_DRAFT, $this->getLinkVisibility($ctx['localLinkId']));
        $this->assertSame(ResourceLink::VISIBILITY_DRAFT, $this->getLinkVisibility($ctx['foreignLinkId']));
    }

    public function testTeacherCanSaveDocumentSharedWithAnotherCourseWithoutChangingItsLink(): void
    {
        $ctx = $this->bootstrapSharedDocumentScenario('keep');

        // The edit form sends back every link it loaded, the other course's included.
        $this->putDocument($ctx, [
            'title' => 'rllc-renamed-keep',
            'resourceLinkListFromEntity' => [
                ['id' => $ctx['localLinkId'], 'visibility' => ResourceLink::VISIBILITY_PUBLISHED],
                ['id' => $ctx['foreignLinkId'], 'visibility' => ResourceLink::VISIBILITY_DRAFT],
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(ResourceLink::VISIBILITY_DRAFT, $this->getLinkVisibility($ctx['foreignLinkId']));
    }

    public function testHidingFolderAlsoHidesItsChildren(): void
    {
        $ctx = $this->bootstrapDocumentScenario('hide');
        $folderLinkId = (int) $ctx['document']->getResourceNode()->getResourceLinks()->first()->getId();
        $childLinkIds = $this->createChildFolder($ctx, 'hide', [
            [$ctx['course'], ResourceLink::VISIBILITY_PUBLISHED],
        ]);

        $this->putDocument($ctx, [
            'resourceLinkListFromEntity' => [['id' => $folderLinkId, 'visibility' => ResourceLink::VISIBILITY_DRAFT]],
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(ResourceLink::VISIBILITY_DRAFT, $this->getLinkVisibility($childLinkIds[(int) $ctx['course']->getId()]));
    }

    public function testHidingSharedFolderOnlyHidesChildrenInTheCurrentCourse(): void
    {
        $ctx = $this->bootstrapSharedDocumentScenario('hideshared');
        $childLinkIds = $this->createChildFolder($ctx, 'hideshared', [
            [$ctx['course'], ResourceLink::VISIBILITY_PUBLISHED],
            [$ctx['foreignCourse'], ResourceLink::VISIBILITY_PUBLISHED],
        ]);

        // The form sends back the other course's link unchanged, after the one that changes.
        $this->putDocument($ctx, [
            'resourceLinkListFromEntity' => [
                ['id' => $ctx['localLinkId'], 'visibility' => ResourceLink::VISIBILITY_DRAFT],
                ['id' => $ctx['foreignLinkId'], 'visibility' => ResourceLink::VISIBILITY_DRAFT],
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(ResourceLink::VISIBILITY_DRAFT, $this->getLinkVisibility($childLinkIds[(int) $ctx['course']->getId()]));
        // The other course decides the visibility of its own children.
        $this->assertSame(ResourceLink::VISIBILITY_PUBLISHED, $this->getLinkVisibility($childLinkIds[(int) $ctx['foreignCourse']->getId()]));
    }

    public function testTeacherCannotMoveOwnDocumentIntoAnotherCourse(): void
    {
        $ctx = $this->bootstrapDocumentScenario('move');

        $this->putDocument($ctx, [
            'parentResourceNodeId' => (int) $ctx['foreignCourse']->getResourceNode()->getId(),
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testLinkCategoryIsOnlyLinkedToTheCurrentCourse(): void
    {
        $ctx = $this->bootstrapDocumentScenario('lcat');

        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'POST',
            '/api/link_categories?cid='.$ctx['course']->getId().'&sid=0&gid=0',
            ['json' => [
                'category_title' => 'rllc category',
                'description' => '',
                'parentResourceNodeId' => (int) $ctx['course']->getResourceNode()->getId(),
                'resourceLinkList' => json_encode([
                    ['cid' => (int) $ctx['course']->getId(), 'visibility' => 2],
                    ['cid' => (int) $ctx['foreignCourse']->getId(), 'visibility' => 2],
                ]),
            ]],
        );
        $this->assertResponseStatusCodeSame(201);

        $this->assertSame(1, $this->countCourseLinks(CLinkCategory::class, $ctx['course']));
        $this->assertSame(0, $this->countCourseLinks(CLinkCategory::class, $ctx['foreignCourse']));
    }

    /**
     * @param array<int, array<string, int>> $resourceLinkList
     */
    private function postPersonalFile(User $user, array $resourceLinkList): int
    {
        $response = $this->createClientWithCredentials($this->getUserTokenFromUser($user))->request(
            'POST',
            '/api/personal_files',
            [
                'headers' => ['Content-Type' => 'multipart/form-data'],
                'extra' => ['files' => ['uploadFile' => $this->getUploadedFile()]],
                'json' => [
                    'filetype' => 'file',
                    'parentResourceNodeId' => (int) $user->getResourceNode()->getId(),
                    'resourceLinkList' => $resourceLinkList,
                ],
            ],
        );
        $this->assertResponseStatusCodeSame(201);

        return (int) $response->toArray()['id'];
    }

    /**
     * @return array{teacher: User, course: Course, foreignCourse: Course, document: CDocument}
     */
    private function bootstrapDocumentScenario(string $suffix): array
    {
        /** @var CourseRepository $courseRepo */
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        /** @var CDocumentRepository $documentRepo */
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);

        $course = $this->createCourse('RLLCA'.$suffix);
        $foreignCourse = $this->createCourse('RLLCB'.$suffix);
        $teacher = $this->createUser('rllc_teacher_'.$suffix);

        $course->addUserAsTeacher($teacher);
        $courseRepo->update($course);

        $document = (new CDocument())
            ->setFiletype('folder')
            ->setTitle('rllc-folder-'.$suffix)
            ->setTemplate(false)
            ->setReadonly(false)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course, null, null, ResourceLink::VISIBILITY_PUBLISHED)
        ;
        $documentRepo->create($document);

        return [
            'teacher' => $teacher,
            'course' => $course,
            'foreignCourse' => $foreignCourse,
            'document' => $document,
        ];
    }

    /**
     * A document whose node is linked to the teacher's course (published) and to another
     * course (draft).
     *
     * @return array{teacher: User, course: Course, foreignCourse: Course, document: CDocument, localLinkId: int, foreignLinkId: int}
     */
    private function bootstrapSharedDocumentScenario(string $suffix): array
    {
        $ctx = $this->bootstrapDocumentScenario($suffix);

        /** @var CDocumentRepository $documentRepo */
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);

        $ctx['document']->addCourseLink($ctx['foreignCourse'], null, null, ResourceLink::VISIBILITY_DRAFT);
        $documentRepo->update($ctx['document']);

        $localLinkId = 0;
        $foreignLinkId = 0;
        foreach ($ctx['document']->getResourceNode()->getResourceLinks() as $link) {
            if ($link->getCourse()?->getId() === $ctx['course']->getId()) {
                $localLinkId = (int) $link->getId();
            } elseif ($link->getCourse()?->getId() === $ctx['foreignCourse']->getId()) {
                $foreignLinkId = (int) $link->getId();
            }
        }
        $this->assertGreaterThan(0, $localLinkId);
        $this->assertGreaterThan(0, $foreignLinkId);

        return $ctx + ['localLinkId' => $localLinkId, 'foreignLinkId' => $foreignLinkId];
    }

    /**
     * Creates a folder inside the scenario's folder, with one link per given course.
     *
     * @param array{teacher: User, course: Course, foreignCourse: Course, document: CDocument} $ctx
     * @param array<int, array{0: Course, 1: int}>                                             $links
     *
     * @return array<int, int> link id by course id
     */
    private function createChildFolder(array $ctx, string $suffix, array $links): array
    {
        /** @var CDocumentRepository $documentRepo */
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);

        $child = (new CDocument())
            ->setFiletype('folder')
            ->setTitle('rllc-child-'.$suffix)
            ->setTemplate(false)
            ->setReadonly(false)
            ->setParent($ctx['document'])
            ->setCreator($ctx['teacher'])
        ;
        foreach ($links as [$course, $visibility]) {
            $child->addCourseLink($course, null, null, $visibility);
        }
        $documentRepo->create($child);

        $linkIds = [];
        foreach ($child->getResourceNode()->getResourceLinks() as $link) {
            $linkIds[(int) $link->getCourse()?->getId()] = (int) $link->getId();
        }
        $this->assertCount(\count($links), $linkIds);

        return $linkIds;
    }

    private function getLinkVisibility(int $linkId): int
    {
        $em = $this->getEntityManager();
        $em->clear();

        return (int) $em->find(ResourceLink::class, $linkId)->getVisibility();
    }

    /**
     * @param array{teacher: User, course: Course, foreignCourse: Course, document: CDocument} $ctx
     * @param array<string, mixed>                                                             $payload
     */
    private function putDocument(array $ctx, array $payload): void
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            'PUT',
            '/api/documents/'.$ctx['document']->getIid().'?cid='.$ctx['course']->getId().'&sid=0&gid=0',
            ['json' => $payload],
        );
    }

    /**
     * Sends a request on the scenario's document as its teacher, in the context of the given course.
     *
     * @param array{teacher: User, course: Course, foreignCourse: Course, document: CDocument} $ctx
     * @param array<string, mixed>                                                             $payload
     */
    private function requestDocument(array $ctx, string $method, string $suffix, Course $course, array $payload): void
    {
        $this->createClientWithCredentials($this->getUserTokenFromUser($ctx['teacher']))->request(
            $method,
            '/api/documents/'.$ctx['document']->getIid().$suffix.'?cid='.$course->getId().'&sid=0&gid=0',
            [
                'headers' => ['Content-Type' => 'PATCH' === $method ? 'application/merge-patch+json' : 'application/json'],
                'json' => $payload,
            ],
        );
    }

    /**
     * @param class-string $resourceClass
     */
    private function countCourseLinks(string $resourceClass, Course $course): int
    {
        $em = $this->getEntityManager();
        $em->clear();

        return (int) $em->createQueryBuilder()
            ->select('COUNT(l.id)')
            ->from($resourceClass, 'r')
            ->innerJoin('r.resourceNode', 'n')
            ->innerJoin('n.resourceLinks', 'l')
            ->where('l.course = :course')
            ->setParameter('course', (int) $course->getId())
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
