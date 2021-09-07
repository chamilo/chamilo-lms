<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

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
        $resourceLinkList = [
            [
                'cid' => $course->getId(),
                'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
            ],
        ];

        $folderName = 'folder1';
        $token = $this->getUserToken([]);
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
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Documents',
            '@type' => 'Documents',
            'title' => $folderName,
            'parentResourceNode' => $course->getResourceNode()->getId(),
        ]);

        // Update.
        $id = $response->toArray()['@id'];

        $this->createClientWithCredentials($token)->request(
            'PUT',
            $id,
            [
                'json' => [
                    'title' => 'edited',
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains([
            '@context' => '/api/contexts/Documents',
            '@type' => 'Documents',
            'title' => 'edited',
        ]);
    }

    public function testUploadFile(): void
    {
        $course = $this->createCourse('Test');

        $courseId = $course->getId();
        $resourceLinkList = [
            [
                'cid' => $course->getId(),
                'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
            ],
        ];

        $file = $this->getUploadedFile();

        $token = $this->getUserToken([]);

        // Upload file.
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

        // Test access to file with admin. Use getFile param in order to get more info (resource link) of the document.
        $this->createClientWithCredentials($token)->request(
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
                        'course' => [
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
        $this->assertResponseStatusCodeSame(403); // Unauthorized

        // Test access with another user. He CAN see the file, the cid is pass as a parameter
        // and the course is open to the world by default.
        $client->request(
            'GET',
            "/api/documents/$documentId",
            [
                'headers' => ['Content-Type' => 'application/json'],
                'query' => [
                    'cid' => $courseId,
                ],
            ]
        );
        $this->assertResponseIsSuccessful();

        // Update course visibility to REGISTERED
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $course = $courseRepo->find($courseId);
        $course->setVisibility(Course::REGISTERED);
        $courseRepo->update($course);

        $client->request(
            'GET',
            "/api/documents/$documentId",
            [
                'headers' => ['Content-Type' => 'application/json'],
                'query' => [
                    'cid' => $courseId,
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(403);

        // Update course visibility to CLOSED
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $course = $courseRepo->find($courseId);
        $course->setVisibility(Course::CLOSED);
        $courseRepo->update($course);

        $client->request(
            'GET',
            "/api/documents/$documentId",
            [
                'headers' => ['Content-Type' => 'application/json'],
                'query' => [
                    'cid' => $courseId,
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(403);

        // Update course visibility to HIDDEN
        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $course = $courseRepo->find($courseId);
        $course->setVisibility(Course::HIDDEN);
        $courseRepo->update($course);

        $client->request(
            'GET',
            "/api/documents/$documentId",
            [
                'headers' => ['Content-Type' => 'application/json'],
                'query' => [
                    'cid' => $courseId,
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(403);

        // Change visibility of the document to DRAFT
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $document = $documentRepo->find($documentId);
        $documentRepo->setVisibilityDraft($document);
        $documentRepo->update($document);

        // Change course to OPEN TO THE WORLD but the document is in DRAFT, "another" user cannot have access.
        $course = $courseRepo->find($courseId);
        $course->setVisibility(Course::OPEN_WORLD);
        $courseRepo->update($course);

        $client->request(
            'GET',
            "/api/documents/$documentId",
            [
                'headers' => ['Content-Type' => 'application/json'],
                'query' => [
                    'cid' => $courseId,
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(403);
    }

    public function testUploadFileInSideASubFolder(): void
    {
        $course = $this->createCourse('Test');

        // Create folder.
        $resourceLinkList = [
            [
                'cid' => $course->getId(),
                'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
            ],
        ];

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

    public function testCreateNodeForResource(): void
    {
        self::bootKernel();

        $course = $this->createCourse('Test');
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $admin = $this->getUser('admin');

        $document = (new CDocument())
            ->setFiletype('file')
            ->setTitle('title')
            ->setParent($course)
        ;

        $documentRepo->addResourceNode($document, $admin, $course);

        $this->assertInstanceOf(ResourceNode::class, $document->getResourceNode());
    }

    public function testSetVisibility(): void
    {
        self::bootKernel();

        $course = $this->createCourse('Test');
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $admin = $this->getUser('admin');

        $document = (new CDocument())
            ->setFiletype('file')
            ->setTitle('title')
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course)
        ;

        $documentRepo->create($document);

        $documentRepo->setVisibilityPublished($document);
        $link = $document->getFirstResourceLink();
        $this->assertSame(ResourceLink::VISIBILITY_PUBLISHED, $link->getVisibility());

        $documentRepo->setVisibilityDraft($document);
        $link = $document->getFirstResourceLink();
        $this->assertSame(ResourceLink::VISIBILITY_DRAFT, $link->getVisibility());

        $documentRepo->setVisibilityPending($document);
        $link = $document->getFirstResourceLink();
        $this->assertSame(ResourceLink::VISIBILITY_PENDING, $link->getVisibility());

        $documentRepo->setVisibilityDeleted($document);
        $link = $document->getFirstResourceLink();
        $this->assertSame(ResourceLink::VISIBILITY_DELETED, $link->getVisibility());
    }

    public function testGetTotalSpaceByCourse(): void
    {
        self::bootKernel();
        $course = $this->createCourse('Test');
        $admin = $this->getUser('admin');
        $em = $this->getManager();

        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $total = $documentRepo->getTotalSpaceByCourse($course);
        $this->assertSame(0, $total);

        $document = (new CDocument())
            ->setFiletype('file')
            ->setTitle('title')
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course)
        ;
        $documentRepo->create($document);
        $documentRepo->addFile($document, $this->getUploadedFile());
        $em->flush();

        $total = $documentRepo->getTotalSpaceByCourse($course);
        $this->assertSame($this->getUploadedFile()->getSize(), $total);
    }
}
