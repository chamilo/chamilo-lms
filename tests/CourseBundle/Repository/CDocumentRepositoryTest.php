<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

/**
 * @covers \Chamilo\CourseBundle\Repository\CDocumentRepository
 */
class CDocumentRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testGetDocumentsAsAdmin(): void
    {
        $token = $this->getUserToken([]);
        $response = $this->createClientWithCredentials($token)->request('GET', '/api/documents');
        $this->assertResponseIsSuccessful();

        // Asserts that the returned content type is JSON-LD (the default)
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        // Asserts that the returned JSON is a superset of this one
        $this->assertJsonContains([
            '@context' => '/api/contexts/Documents',
            '@id' => '/api/documents',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 0,
        ]);

        $this->assertCount(0, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(CDocument::class);
    }

    public function testCreateFolder(): void
    {
        $course = $this->createCourse('Test');

        // Create folder.
        $resourceLinkList = [[
            'cid' => $course->getId(),
            'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
        ]];

        $folderName = 'folder1';
        $token = $this->getUserToken([]);
        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/documents',
            [
                'json' => [
                    'title' => $folderName,
                    'filetype' => 'folder',
                    'parentResourceNodeId' => $course->getResourceNode()->getId(),
                    'resourceLinkList' => $resourceLinkList,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Documents',
            '@type' => 'Documents',
            'title' => $folderName,
            'parentResourceNode' => $course->getResourceNode()->getId(),
        ]);
    }

    public function testUploadFile(): void
    {
        $course = $this->createCourse('Test');
        $courseId = $course->getId();
        $resourceLinkList = [[
            'cid' => $course->getId(),
            'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
        ]];

        $file = $this->getUploadedFile();

        $token = $this->getUserToken([]);
        $response = $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/documents',
            [
                'headers' => [
                    'Content-Type' => 'multipart/form-data',
                ],
                'extra' => [
                    'files' => [
                        'uploadFile' => $file,
                    ],
                ],
                'json' => [
                    'filetype' => 'file',
                    'size' => $file->getSize(),
                    'parentResourceNodeId' => $course->getResourceNode()->getId(),
                    'resourceLinkList' => $resourceLinkList,
                ],
            ]
        );

        // Check uploaded file.
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Documents',
            '@type' => 'Documents',
            'title' => $file->getFilename(),
            'filetype' => 'file',
            'parentResourceNode' => $course->getResourceNode()->getId(),
        ]);

        // Get document iid
        $data = json_decode($response->getContent());
        $documentId = $data->iid;

        // Test access to file with admin use getFile query in order to get more info of the document.
        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/documents/'.$documentId,
            [
                'query' => [
                    'getFile' => true,
                ],
            ]
        );
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains(
            [
                '@context' => '/api/contexts/Documents',
                '@type' => 'Documents',
                'title' => $file->getFilename(),
                'filetype' => 'file',
                'resourceLinkListFromEntity' => [
                    [
                        'session' => null,
                        'course' =>
                            [
                                '@id' => '/api/courses/'.$courseId,
                            ],
                        'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
                    ],
                ],
            ]
        );

        // Test access with another user. He cannot see the file, no cid is pass as a parameter.
        $this->createUser('another');
        $client = $this->getClientWithGuiCredentials('another', 'another');
        $client->request(
            'GET',
            '/api/documents/'.$documentId,
            [
                'headers' => ['Content-Type' => 'application/json'],
            ]
        );
        $this->assertResponseStatusCodeSame(403); // Forbidden

        // Test access with another user. He CAN see the file, the cid is pass as a parameter
        // and the course is open to the world by default.
        $client->request(
            'GET',
            "/api/documents/$documentId",
            [
                'headers' => ['Content-Type' => 'application/json'],
                'query' => [
                    'cid' => $courseId
                ]
            ]
        );
        $this->assertResponseIsSuccessful();
    }

    public function testUploadFileInSideASubFolder(): void
    {
        $course = $this->createCourse('Test');

        // Create folder.
        $resourceLinkList = [[
            'cid' => $course->getId(),
            'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
        ]];

        $token = $this->getUserToken([]);
        // Creates a folder.
        $folderName = 'myfolder';
        $response = $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/documents',
            [
                'json' => [
                    'title' => $folderName,
                    'filetype' => 'folder',
                    'parentResourceNodeId' => $course->getResourceNode()->getId(),
                    'resourceLinkList' => $resourceLinkList,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($response->getContent());
        $resourceNodeId = $data->resourceNode->id;

        $file = $this->getUploadedFile();

        $token = $this->getUserToken([]);
        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/documents',
            [
                'headers' => [
                    'Content-Type' => 'multipart/form-data',
                ],
                'extra' => [
                    'files' => [
                        'uploadFile' => $file,
                    ],
                ],
                'json' => [
                    'filetype' => 'file',
                    'size' => $file->getSize(),
                    'parentResourceNodeId' => $resourceNodeId,
                    'resourceLinkList' => $resourceLinkList,
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Documents',
            '@type' => 'Documents',
            'title' => $file->getFilename(),
            'filetype' => 'file',
        ]);

        $this->assertMatchesRegularExpression('~'.$folderName.'~', $response->toArray()['resourceNode']['path']);
    }
}
