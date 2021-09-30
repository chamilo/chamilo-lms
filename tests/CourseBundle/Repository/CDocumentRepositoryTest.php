<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\ResourceNode;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;
use LogicException;
use Symfony\Component\HttpFoundation\Request;

class CDocumentRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testGetDocuments(): void
    {
        // Test as admin.
        $token = $this->getUserToken([]);
        $this->createClientWithCredentials($token)->request('GET', '/api/documents');
        $this->assertResponseStatusCodeSame(403);

        $course = $this->createCourse('test');
        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/documents',
            [
                'query' => [
                    'cid' => $course->getId(),
                    'resourceNode.parent' => $course->getResourceNode()->getId(),
                ],
            ]
        );

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
        $courseId = $course->getId();

        // Create folder.
        $resourceLinkList = [
            [
                'cid' => $courseId,
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
    }

    public function testUpdateFolder(): void
    {
        $course = $this->createCourse('Test');
        $courseId = $course->getId();

        // Create folder.
        $resourceLinkList = [
            [
                'cid' => $courseId,
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

        // Update.
        $iri = $response->toArray()['@id'];

        $this->createClientWithCredentials($token)->request(
            'PUT',
            $iri,
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

    public function testDeleteFolder(): void
    {
        $course = $this->createCourse('Test');
        $courseId = $course->getId();

        // Create folder.
        $resourceLinkList = [
            [
                'cid' => $courseId,
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

        $iri = $response->toArray()['@id'];

        $this->createClientWithCredentials($token)->request(
            'DELETE',
            $iri
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(204);

        $this->createClientWithCredentials($token)->request(
            'GET',
            $iri,
            [
                'query' => [
                    'getFile' => true,
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(404);
    }

    public function testAccessFolder(): void
    {
        $course = $this->createCourse('Test');
        $courseId = $course->getId();

        // Create folder.
        $resourceLinkList = [
            [
                'cid' => $courseId,
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

        // Test access.
        $iri = $response->toArray()['@id'];

        $this->createClientWithCredentials($token)->request(
            'GET',
            $iri,
            [
                'query' => [
                    'getFile' => true,
                ],
            ]
        );
        $this->assertResponseIsSuccessful();

        // Test as student.
        $this->createUser('student');

        $studentToken = $this->getUserToken(
            [
                'username' => 'student',
                'password' => 'student',
            ],
            true
        );

        $this->createClientWithCredentials($studentToken)->request(
            'GET',
            $iri,
            [
                'query' => [
                    'cid' => 'abc',
                    'sid' => 'abc',
                    'gip' => 'abc',
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(403);

        $this->createClientWithCredentials($studentToken)->request(
            'GET',
            $iri,
            [
                'query' => [
                    'cid' => $courseId,
                ],
            ]
        );
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains([
            '@context' => '/api/contexts/Documents',
            '@type' => 'Documents',
            'title' => 'folder1',
        ]);

        $documentId = $response->toArray()['iid'];

        // Change visibility to draft.
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $document = $documentRepo->find($documentId);
        $documentRepo->setVisibilityDraft($document);

        // Admin access.
        $this->createClientWithCredentials($token)->request(
            'GET',
            $iri,
            [
                'query' => [
                    'cid' => $courseId,
                ],
            ]
        );
        $this->assertResponseIsSuccessful();

        $response = $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/documents',
            [
                'query' => [
                    'cid' => $courseId,
                    'resourceNode.parent' => $course->getResourceNode()->getId(),
                ],
            ]
        );
        $this->assertCount(1, $response->toArray()['hydra:member']);

        // Student access.
        $this->createClientWithCredentials($studentToken)->request(
            'GET',
            $iri,
            [
                'query' => [
                    'cid' => $courseId,
                ],
            ]
        );
        $this->assertResponseStatusCodeSame(403);

        $response = $this->createClientWithCredentials($studentToken)->request(
            'GET',
            '/api/documents',
            [
                'query' => [
                    'cid' => $courseId,
                    'resourceNode.parent' => $course->getResourceNode()->getId(),
                ],
            ]
        );
        $this->assertCount(0, $response->toArray()['hydra:member']);
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

        $client->request('GET', '/api/documents', [
            'query' => [
                'loadNode' => 1,
                'resourceNode.parent' => $course->getResourceNode()->getId(),
                'cid' => $courseId,
            ],
        ]);
        $this->assertResponseStatusCodeSame(200);

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

        $client->request('GET', '/api/documents', [
            'query' => [
                'loadNode' => 1,
                'resourceNode.parent' => $course->getResourceNode()->getId(),
                'cid' => $courseId,
            ],
        ]);
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

    public function testChangeVisibility(): void
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

        // Get document iid
        $data = json_decode($response->getContent());
        $documentId = $data->iid;

        // Test access to file with admin. Use getFile param in order to get more info (resource link) of the document.
        $this->createClientWithCredentials($token)->request(
            'PUT',
            '/api/documents/'.$documentId.'',
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
        $this->assertMatchesRegularExpression('~'.$folderName.'~', $response->toArray()['resourceNode']['path']);

        $data = json_decode($response->getContent());
        $resourceNodeId = $data->resourceNode->id;

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

        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $document = $documentRepo->find($response->toArray()['iid']);
        $this->assertInstanceOf(CDocument::class, $document);
        $parent = $documentRepo->getParent($document);
        $this->assertInstanceOf(CDocument::class, $parent);

        $size = $documentRepo->getFolderSize($parent->getResourceNode(), $course);
        $this->assertSame($file->getSize(), $size);

        $docs = $documentRepo->findDocumentsByAuthor($this->getUser('admin')->getId());
        $this->assertSame(0, \count($docs));
    }

    public function testAddFileFromString(): void
    {
        self::bootKernel();

        $course = $this->createCourse('Test');
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $admin = $this->getUser('admin');

        $document = (new CDocument())
            ->setFiletype('file')
            ->setTitle('title 123')
            ->setTemplate(false)
            ->setReadonly(false)
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course)
        ;

        $documentRepo->create($document);

        $this->assertInstanceOf(ResourceNode::class, $document->getResourceNode());
        $this->assertNull($documentRepo->getParent($document));
        $this->assertFalse($document->hasUploadFile());
        $this->assertFalse($document->isTemplate());
        $this->assertFalse($document->getReadonly());

        $this->assertSame($document->getIid(), $document->getResourceIdentifier());
        $this->assertSame(1, $documentRepo->count([]));

        $documentRepo->addFileFromString($document, 'test', 'text/html', 'my file', true);

        /** @var CDocument $document */
        $document = $documentRepo->find($document->getIid());
        $this->assertTrue($document->getResourceNode()->hasResourceFile());
    }

    public function testAddFileFromPath(): void
    {
        self::bootKernel();

        $course = $this->createCourse('Test');
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $admin = $this->getUser('admin');

        $document = (new CDocument())
            ->setFiletype('file')
            ->setTitle('title 123')
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course)
        ;

        $documentRepo->create($document);

        $this->assertSame(1, $documentRepo->count([]));

        $path = $this->getUploadedFile()->getRealPath();
        $resourceFile = $documentRepo->addFileFromPath($document, 'logo.png', $path, true);

        $this->assertNotNull($resourceFile);

        /** @var CDocument $document */
        $document = $documentRepo->find($document->getIid());
        $this->assertTrue($document->getResourceNode()->hasResourceFile());
    }

    public function testAddFileFromFileRequest(): void
    {
        self::bootKernel();

        $course = $this->createCourse('Test');
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $admin = $this->getUser('admin');

        $document = (new CDocument())
            ->setFiletype('file')
            ->setTitle('title 123')
            ->setParent($course)
            ->setCreator($admin)
            ->addCourseLink($course)
        ;

        $documentRepo->create($document);

        $this->assertSame(1, $documentRepo->count([]));

        $file = $this->getUploadedFileArray();

        $request = new Request([], [], [], [], ['upload_file' => $file]);
        $this->getContainer()->get('request_stack')->push($request);

        $resourceFile = $documentRepo->addFileFromFileRequest($document, 'upload_file');

        $this->assertNotNull($resourceFile);

        /** @var CDocument $document */
        $document = $documentRepo->find($document->getIid());
        $this->assertTrue($document->getResourceNode()->hasResourceFile());
    }

    public function testCreateWithAddResourceNode(): void
    {
        self::bootKernel();

        $course = $this->createCourse('Test');
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $admin = $this->getUser('admin');
        $em = $this->getEntityManager();

        $document = (new CDocument())
            ->setFiletype('file')
            ->setTitle('title 123')
            ->setParent($course)
        ;
        $documentRepo->addResourceNode($document, $admin, $course);
        $em->flush();

        $this->assertInstanceOf(ResourceNode::class, $document->getResourceNode());
        $this->assertSame('title 123', (string) $document);
        $this->assertNull($documentRepo->getParent($document));

        $documentRepo->hardDelete($document);

        $this->assertSame(0, $documentRepo->count([]));
    }

    public function testCreateDocumentWithLinks(): void
    {
        self::bootKernel();

        $course = $this->createCourse('Test');
        $documentRepo = self::getContainer()->get(CDocumentRepository::class);
        $admin = $this->getUser('admin');
        $em = $this->getEntityManager();

        $document = (new CDocument())
            ->setFiletype('file')
            ->setTitle('title 123')
            ->setParent($course)
            ->addCourseLink($course)
        ;
        $documentRepo->addResourceNode($document, $admin, $course);
        $em->flush();

        /** @var CDocument $document */
        $document = $documentRepo->find($document->getIid());

        $count = $document->getResourceNode()->getResourceLinks()->count();
        $this->assertSame(1, $count);

        $count = $documentRepo->countUserDocuments($admin, $course);
        $this->assertSame(1, $count);

        $link = $document->getFirstResourceLink();
        $this->assertInstanceOf(ResourceLink::class, $link);
        $this->assertSame($link->getVisibility(), ResourceLink::VISIBILITY_PUBLISHED);
        $this->assertSame($link->getCourse(), $course);
        $this->assertNull($link->getGroup());
        $this->assertNull($link->getUser());
        $this->assertNull($link->getUserGroup());

        $teacher = $this->createUser('teacher');

        $session = (new Session())
            ->setName('session 1')
            ->addGeneralCoach($teacher)
            ->addAccessUrl($this->getAccessUrl())
        ;
        $em->persist($session);
        $em->flush();

        $group = (new CGroup())
            ->setName('Group')
            ->setParent($course)
            ->setCreator($teacher)
            ->setMaxStudent(100)
        ;
        $em->persist($group);
        $em->flush();

        $document->addGroupLink($course, $group, $session);
        $documentRepo->update($document);

        $document->addGroupLink($course, $group);
        $documentRepo->update($document);

        /** @var CDocument $document */
        $document = $documentRepo->find($document->getIid());
        $count = $document->getResourceNode()->getResourceLinks()->count();
        $this->assertSame(2, $count);

        $link = $document->getFirstResourceLink();
        $this->assertInstanceOf(ResourceLink::class, $link);

        $firstLink = $document->getResourceNode()->getResourceLinks()[0];
        $secondLink = $document->getResourceNode()->getResourceLinks()[1];

        $this->assertInstanceOf(ResourceLink::class, $firstLink);
        $this->assertInstanceOf(ResourceLink::class, $secondLink);

        $this->assertSame($firstLink->getCourse(), $course);
        $this->assertNull($firstLink->getGroup());
        $this->assertNull($firstLink->getSession());
        $this->assertNull($firstLink->getUser());

        $this->assertSame($secondLink->getCourse(), $course);
        $this->assertSame($secondLink->getGroup(), $group);
        $this->assertSame($secondLink->getSession(), $session);
        $this->assertNull($secondLink->getUser());

        $user = $this->createUser('test2');
        $document->addResourceToUserList([$user]);
        $em->flush();

        $thirdLink = $document->getResourceNode()->getResourceLinks()[2];

        $this->assertInstanceOf(ResourceLink::class, $thirdLink);
        $this->assertSame($thirdLink->getUser(), $user);
        $this->assertSame($thirdLink->getSession(), null);
        $this->assertSame($thirdLink->getGroup(), null);

        $group2 = (new CGroup())
            ->setName('Group2')
            ->setParent($course)
            ->setCreator($teacher)
            ->setMaxStudent(100)
        ;
        $em->persist($group2);
        $em->flush();

        $document->addResourceToGroupList([$group2], $course);
        $em->flush();

        /** @var CDocument $document */
        $document = $documentRepo->find($document->getIid());

        $fourthLink = $document->getResourceNode()->getResourceLinks()[3];
        $this->assertInstanceOf(ResourceLink::class, $fourthLink);
        $this->assertNull($fourthLink->getUser());
        $this->assertNull($fourthLink->getSession());
        $this->assertSame($fourthLink->getGroup(), $group2);

        $this->assertTrue($document->isVisible($course));
        $this->assertTrue($document->isVisible($course, $session));

        $link = $document->getFirstResourceLinkFromCourseSession($course, $session);

        $this->assertInstanceOf(ResourceLink::class, $link);
        $this->assertSame($secondLink, $link);

        $usersAndGroups = $document->getUsersAndGroupSubscribedToResource();
        $this->assertFalse($usersAndGroups['everyone']);
        $this->assertCount(1, $usersAndGroups['users']);
        $this->assertCount(2, $usersAndGroups['groups']);
    }

    public function testSeparateUsersGroups(): void
    {
        $usersAndGroupsSeparated = CDocument::separateUsersGroups(['USER:1']);

        $this->assertCount(1, $usersAndGroupsSeparated['users']);
        $this->assertCount(0, $usersAndGroupsSeparated['groups']);

        $usersAndGroupsSeparated = CDocument::separateUsersGroups(['USER:1', 'GROUP:1']);

        $this->assertCount(1, $usersAndGroupsSeparated['users']);
        $this->assertCount(1, $usersAndGroupsSeparated['groups']);
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

        $this->expectException(LogicException::class);
        $link->setVisibility(888);

        $link->setUserGroup(null);

        $this->assertFalse($link->hasGroup());
        $this->assertFalse($link->hasSession());
        $this->assertTrue($link->isPublished());
        $this->assertFalse($link->isDraft());
        $this->assertFalse($link->isPending());

        $this->assertSame(ResourceLink::VISIBILITY_PUBLISHED, $link->getVisibility());
        $this->assertSame('Published', $link->getVisibilityName());

        $documentRepo->setVisibilityDraft($document);
        $link = $document->getFirstResourceLink();
        $this->assertSame(ResourceLink::VISIBILITY_DRAFT, $link->getVisibility());
        $this->assertSame('Draft', $link->getVisibilityName());

        $documentRepo->toggleVisibilityPublishedDraft($document);
        $link = $document->getFirstResourceLink();
        $this->assertSame(ResourceLink::VISIBILITY_PUBLISHED, $link->getVisibility());
        $this->assertSame('Published', $link->getVisibilityName());

        $documentRepo->toggleVisibilityPublishedDraft($document);
        $link = $document->getFirstResourceLink();
        $this->assertSame(ResourceLink::VISIBILITY_DRAFT, $link->getVisibility());

        $documentRepo->setVisibilityPending($document);
        $link = $document->getFirstResourceLink();
        $this->assertSame(ResourceLink::VISIBILITY_PENDING, $link->getVisibility());
        $this->assertSame('Pending', $link->getVisibilityName());

        $documentRepo->setVisibilityDeleted($document);
        $link = $document->getFirstResourceLink();
        $this->assertSame(ResourceLink::VISIBILITY_DELETED, $link->getVisibility());
        $this->assertSame('Deleted', $link->getVisibilityName());

        $documentRepo->softDelete($document);
        $link = $document->getFirstResourceLink();
        $this->assertSame(ResourceLink::VISIBILITY_DELETED, $link->getVisibility());
        $this->assertSame('Deleted', $link->getVisibilityName());
    }

    public function testGetTotalSpaceByCourse(): void
    {
        self::bootKernel();
        $course = $this->createCourse('Test');
        $admin = $this->getUser('admin');
        $em = $this->getEntityManager();

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

        $documentRepo->delete($document);

        $this->assertSame(0, $documentRepo->count([]));
    }
}
