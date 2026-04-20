<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CoreBundle\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CopyDocumentToPersonalFileTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    /**
     * Creates a file document in a course as admin and returns its iid.
     * Requires $_SERVER['REMOTE_ADDR'] to be set before calling.
     */
    private function createFileDocument(int $courseId, int $parentNodeId): int
    {
        $adminToken = $this->getUserToken([]);
        $file = $this->getUploadedFile();
        $resourceLinkList = [['cid' => $courseId, 'visibility' => ResourceLink::VISIBILITY_PUBLISHED]];

        $response = $this
            ->createClientWithCredentials($adminToken)
            ->request(
                'POST',
                '/api/documents',
                [
                    'headers' => ['Content-Type' => 'multipart/form-data'],
                    'extra' => ['files' => ['uploadFile' => $file]],
                    'json' => [
                        'filetype' => 'file',
                        'size' => $file->getSize(),
                        'parentResourceNodeId' => $parentNodeId,
                        'resourceLinkList' => $resourceLinkList,
                    ],
                ]
            )
        ;

        $this->assertResponseStatusCodeSame(201);

        return json_decode($response->getContent())->iid;
    }

    /**
     * Creates a folder document in a course as admin and returns its iid.
     */
    private function createFolderDocument(int $courseId, int $parentNodeId): int
    {
        $adminToken = $this->getUserToken([]);
        $resourceLinkList = [['cid' => $courseId, 'visibility' => ResourceLink::VISIBILITY_PUBLISHED]];

        $response = $this
            ->createClientWithCredentials($adminToken)
            ->request(
                'POST',
                '/api/documents',
                [
                    'json' => [
                        'title' => 'test-folder',
                        'filetype' => 'folder',
                        'parentResourceNodeId' => $parentNodeId,
                        'resourceLinkList' => $resourceLinkList,
                    ],
                ]
            )
        ;

        $this->assertResponseStatusCodeSame(201);

        return json_decode($response->getContent())->iid;
    }

    /**
     * Enrolls a user in a course with the given status (TEACHER or STUDENT).
     */
    private function enrollUserInCourse(User $user, Course $course, int $status): void
    {
        $em = $this->getEntityManager();
        $enrollment = (new CourseRelUser())
            ->setCourse($course)
            ->setUser($user)
            ->setStatus($status)
            ->setRelationType(0)
        ;
        $em->persist($enrollment);
        $em->flush();
    }

    public function testCopyDocumentRequiresAuthentication(): void
    {
        global $_SERVER;
        $_SERVER['REMOTE_ADDR'] = 'localhost';

        // A real document is needed: the provider runs before the security check,
        // so an invalid ID would return 404 before AP can enforce the 401.
        $course = $this->createCourse('Auth Test Course');
        $documentId = $this->createFileDocument($course->getId(), $course->getResourceNode()->getId());

        static::createClient()->request('POST', "/api/documents/{$documentId}/personal_files");

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCopyNonExistentDocumentReturns404(): void
    {
        $token = $this->getUserToken([]);

        $this
            ->createClientWithCredentials($token)
            ->request(
                'POST',
                '/api/documents/999999/personal_files'
            )
        ;

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCopyDocumentForbiddenWithoutViewPermission(): void
    {
        global $_SERVER;
        $_SERVER['REMOTE_ADDR'] = 'localhost';

        $course = $this->createCourse('Forbidden Test Course');
        $course->setVisibility(Course::REGISTERED);
        $this->getEntityManager()->flush();

        $documentId = $this->createFileDocument($course->getId(), $course->getResourceNode()->getId());

        $outsider = $this->createUser('outsider_user');
        $outsiderToken = $this->getUserTokenFromUser($outsider);

        $this
            ->createClientWithCredentials($outsiderToken)
            ->request(
                'POST',
                "/api/documents/{$documentId}/personal_files",
                [
                    'query' => ['cid' => $course->getId()],
                ]
            )
        ;

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCopyFolderDocumentReturns400(): void
    {
        $course = $this->createCourse('Copy Folder Test');
        $teacher = $this->createUser('folder_teacher');
        $this->enrollUserInCourse($teacher, $course, CourseRelUser::TEACHER);

        $folderId = $this->createFolderDocument($course->getId(), $course->getResourceNode()->getId());

        $teacherToken = $this->getUserTokenFromUser($teacher);

        $this->createClientWithCredentials($teacherToken)->request(
            'POST',
            "/api/documents/{$folderId}/personal_files",
            [
                'query' => ['cid' => $course->getId()],
            ]
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCopyFileDocumentAsTeacherCreatesPersonalFile(): void
    {
        global $_SERVER;
        $_SERVER['REMOTE_ADDR'] = 'localhost';

        $course = $this->createCourse('Copy File Teacher Test');
        $teacher = $this->createUser('copy_teacher');
        $this->enrollUserInCourse($teacher, $course, CourseRelUser::TEACHER);

        $documentId = $this->createFileDocument($course->getId(), $course->getResourceNode()->getId());

        $teacherToken = $this->getUserTokenFromUser($teacher);
        $copyResponse = $this->createClientWithCredentials($teacherToken)
            ->request(
                'POST',
                "/api/documents/{$documentId}/personal_files"
            )
        ;

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/PersonalFile',
            '@type' => 'PersonalFile',
        ]);

        $data = $copyResponse->toArray();
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('resourceNode', $data);
    }

    public function testCopyFileDocumentAsStudentCreatesPersonalFile(): void
    {
        global $_SERVER;
        $_SERVER['REMOTE_ADDR'] = 'localhost';

        $course = $this->createCourse('Copy File Student Test');
        $student = $this->createUser('copy_student');
        $this->enrollUserInCourse($student, $course, CourseRelUser::STUDENT);

        $documentId = $this->createFileDocument($course->getId(), $course->getResourceNode()->getId());

        $studentToken = $this->getUserTokenFromUser($student);
        $this->createClientWithCredentials($studentToken)
            ->request(
                'POST',
                "/api/documents/{$documentId}/personal_files"
            )
        ;

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/PersonalFile',
            '@type' => 'PersonalFile',
        ]);
    }
}
