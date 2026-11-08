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
