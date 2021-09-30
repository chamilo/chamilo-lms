<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Repository\CToolRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CToolRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testDelete(): void
    {
        self::bootKernel();

        $repo = self::getContainer()->get(CToolRepository::class);
        $this->assertSame(0, $repo->count([]));

        $course = $this->createCourse('new');
        $defaultCount = $repo->count([]);

        /** @var CTool $courseTool */
        $courseTool = $course->getTools()->first();
        $repo->delete($courseTool);

        $this->assertSame($defaultCount - 1, $repo->count([]));
    }

    public function testGetTools(): void
    {
        $token = $this->getUserToken([]);
        $response = $this->createClientWithCredentials($token)->request('GET', '/api/c_tools');
        $this->assertResponseIsSuccessful();

        // Asserts that the returned content type is JSON-LD (the default)
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        // Asserts that the returned JSON is a superset of this one
        $this->assertJsonContains([
            '@context' => '/api/contexts/CTool',
            '@id' => '/api/c_tools',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 0,
        ]);

        $this->assertCount(0, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(CTool::class);

        $this->createCourse('new');
        $response = $this->createClientWithCredentials($token)->request('GET', '/api/c_tools');
        $this->assertResponseIsSuccessful();

        $repo = self::getContainer()->get(CToolRepository::class);
        $defaultCount = $repo->count([]);
        $this->assertCount($defaultCount, $response->toArray()['hydra:member']);

        $test = $this->createUser('student');
        $studentToken = $this->getUserTokenFromUser($test);
        $this->createClientWithCredentials($studentToken)->request('GET', '/api/c_tools');
        $this->assertResponseStatusCodeSame(403);
    }
}
