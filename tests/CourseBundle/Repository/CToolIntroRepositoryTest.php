<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\Tests\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
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
        $em = $this->getEntityManager();

        $courseRepo = self::getContainer()->get(CourseRepository::class);
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
        $this->assertNotNull($intro->getIid());
        $this->assertNotEmpty($intro->getResourceName());
        $this->assertSame(1, $repo->count([]));

        // Delete intro.
        $repo->delete($intro);
        $this->assertSame(0, $repo->count([]));
        $this->assertSame(1, $courseRepo->count([]));
    }

    public function testCreateAndDeleteCourse(): void
    {
        $em = $this->getEntityManager();

        $courseRepo = self::getContainer()->get(CourseRepository::class);
        $repo = self::getContainer()->get(CToolIntroRepository::class);

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

        $this->assertSame(1, $courseRepo->count([]));
        $this->assertSame(1, $repo->count([]));

        // Delete course.
        $courseRepo->delete($course);
        $this->assertSame(0, $repo->count([]));
        $this->assertSame(0, $courseRepo->count([]));
    }

    public function testCreateInSession(): void
    {
        $em = $this->getEntityManager();
        $repo = self::getContainer()->get(CToolIntroRepository::class);

        $course = $this->createCourse('new');
        $teacher = $this->createUser('teacher');

        $session = $this->createSession('my session');
        $session->addCourse($course);
        $em->persist($session);
        $em->flush();

        /** @var CTool $courseTool */
        $courseTool = $course->getTools()->first();

        // Create Intro for the base course.
        $intro = (new CToolIntro())
            ->setIntroText('test')
            ->setCourseTool($courseTool)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course)
        ;
        $em->persist($intro);
        $em->flush();

        $this->assertSame(1, $repo->count([]));

        // Create intro in session.
        $intro2 = (new CToolIntro())
            ->setIntroText('test in session')
            ->setCourseTool($courseTool)
            ->setParent($course)
            ->setCreator($teacher)
            ->addCourseLink($course, $session)
        ;
        $this->assertHasNoEntityViolations($intro2);
        $em->persist($intro2);
        $em->flush();

        $this->assertSame(2, $repo->count([]));

        $token = $this->getUserToken([]);
        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/c_tool_intros',
            [
                'query' => [
                    'cid' => $course->getId(),
                ],
            ]
        );
        $this->assertResponseIsSuccessful();
        // Asserts that the returned JSON is a superset of this one
        $this->assertJsonContains([
            '@context' => '/api/contexts/CToolIntro',
            '@id' => '/api/c_tool_intros',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
        ]);

        $this->createClientWithCredentials($token)->request(
            'GET',
            '/api/c_tool_intros',
            [
                'query' => [
                    'cid' => $course->getId(),
                    'sid' => $session->getId(),
                ],
            ]
        );

        $this->assertResponseIsSuccessful();
        // Asserts that the returned JSON is a superset of this one
        $this->assertJsonContains([
            '@context' => '/api/contexts/CToolIntro',
            '@id' => '/api/c_tool_intros',
            '@type' => 'hydra:Collection',
            'hydra:totalItems' => 1,
            'hydra:member' => [
                [
                    '@type' => 'CToolIntro',
                    'introText' => 'test in session',
                ],
            ],
        ]);
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
                            'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
                        ],
                    ],
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
