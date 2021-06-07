<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\AccessUrl;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Repository\Node\AccessUrlRepository;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\Node\UserRepository;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CDocumentRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testGetDocuments()
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
            /*'hydra:view' => [
                '@id' => '/api/documents?page=1',
                '@type' => 'hydra:PartialCollectionView',
            ],*/
        ]);

        $this->assertCount(0, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(CDocument::class);
    }

    public function testCreateFolder(): void
    {
        $courseRepo = self::getContainer()->get(CourseRepository::class);

        // Get admin.
        $admin = $this->getUser('admin');
        // Get access url.
        $accessUrl = $this->getAccessUrl();

        $course = (new Course())
            ->setTitle('Test course')
            ->setCode('test_course')
            ->addAccessUrl($accessUrl)
            ->setCreator($admin)
        ;
        $courseRepo->create($course);

        // Create folder.
        $resourceLinkList = [
            'cid' => $course->getId(),
            'visibility' => 2,
        ];

        $token = $this->getUserToken([]);
        $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/documents',
            [
                'json' => [
                    'title' => 'folder1',
                    'parentResourceNodeId' => $course->getResourceNode()->getId(),
                    'resourceLinkList' => json_encode($resourceLinkList),
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/Documents',
            '@type' => 'Documents',
            'title' => 'folder1',
        ]);
    }
}
