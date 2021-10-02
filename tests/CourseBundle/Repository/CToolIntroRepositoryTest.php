<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CTool;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Chamilo\CourseBundle\Repository\CToolIntroRepository;
use Chamilo\Tests\AbstractApiTest;
use Chamilo\Tests\ChamiloTestTrait;

class CToolIntroRepositoryTest extends AbstractApiTest
{
    use ChamiloTestTrait;

    public function testCreate(): void
    {
        self::bootKernel();

        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CToolIntroRepository::class);

        $this->assertSame(0, $repo->count([]));

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        /** @var CTool $courseTool */
        $courseTool = $course->getTools()->first();

        $intro = (new CToolIntro())
            ->setIntroText('test')
            ->setCourseTool($courseTool)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $this->assertHasNoEntityViolations($intro);
        $em->persist($intro);
        $em->flush();

        $this->assertNotEmpty($intro->getIntroText());

        $this->assertSame(1, $repo->count([]));

        $repo->delete($intro);

        $this->assertSame(0, $repo->count([]));
    }

    public function testGetToolIntros(): void
    {
        $token = $this->getUserToken([]);
        $response = $this->createClientWithCredentials($token)->request('GET', '/api/c_tool_intros');
        $this->assertResponseIsSuccessful();

        // Asserts that the returned content type is JSON-LD (the default)
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        // Asserts that the returned JSON is a superset of this one
        $this->assertJsonContains([
            '@context' => '/api/contexts/CToolIntro',
            '@id' => '/api/c_tool_intros',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 0,
        ]);

        $this->assertCount(0, $response->toArray()['hydra:member']);
        $this->assertMatchesResourceCollectionJsonSchema(CToolIntro::class);
    }

    public function testCreateIntroApi(): void
    {
        $course = $this->createCourse('new');
        $token = $this->getUserToken();
        $resourceNodeId = $course->getResourceNode()->getId();

        /** @var CTool $courseTool */
        $courseTool = $course->getTools()->first();

        $iri = '/api/c_tools/'.$courseTool->getIid();

        $response = $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_tool_intros',
            [
                'json' => [
                    'introText' => 'introduction here',
                    'courseTool' => $iri,
                    'parentResourceNodeId' => $resourceNodeId,
                    'resourceLinkList' => [
                        [
                            'cid' => $course->getId(),
                            'visibility' => ResourceLink::VISIBILITY_PUBLISHED
                        ]
                    ]
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/CToolIntro',
            '@type' => 'CToolIntro',
            'introText' => 'introduction here',
        ]);

        $repo = self::getContainer()->get(CToolIntroRepository::class);
        $id = $response->toArray()['iid'];

        /** @var CToolIntro $toolIntro */
        $toolIntro = $repo->find($id);
        $this->assertNotNull($toolIntro);
        $this->assertNotNull($toolIntro->getResourceNode());
        $this->assertsame(1, $toolIntro->getResourceNode()->getResourceLinks()->count());

        $this->assertSame(1, $repo->count([]));
    }

    public function testUpdateIntroApi(): void
    {
        $course = $this->createCourse('new');

        $token = $this->getUserToken();

        $resourceNodeId = $course->getResourceNode()->getId();

        /** @var CTool $courseTool */
        $courseTool = $course->getTools()->first();

        $iri = '/api/c_tools/'.$courseTool->getIid();

        $response = $this->createClientWithCredentials($token)->request(
            'POST',
            '/api/c_tool_intros',
            [
                'json' => [
                    'introText' => 'introduction here',
                    'courseTool' => $iri,
                    'parentResourceNodeId' => $resourceNodeId,
                ],
            ]
        );
        $this->assertResponseIsSuccessful();

        $iri = $response->toArray()['@id'];
        $this->createClientWithCredentials($token)->request(
            'PUT',
            $iri,
            [
                'json' => [
                    'introText' => 'MODIFIED',
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');
        $this->assertJsonContains([
            '@context' => '/api/contexts/CToolIntro',
            '@type' => 'CToolIntro',
            'introText' => 'MODIFIED',
        ]);
    }
}
